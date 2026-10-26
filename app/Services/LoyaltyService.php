<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Promo;
use App\Models\LoyaltyPointHistory;
use App\Models\Setting;
use App\Models\Transaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LoyaltyService
{
    public const TIER_REGULAR = 'regular';

    public const TIER_SILVER = 'silver';

    public const TIER_GOLD = 'gold';

    public const TIER_PLATINUM = 'platinum';

    public function __construct(
        private readonly PromoService $promoService
    ) {}

    public function settings(): array
    {
        return [
            'enable_earn' => Setting::getBool('loyalty_enable_earn', true),
            'enable_redeem' => Setting::getBool('loyalty_enable_redeem', true),
            'earn_rate_amount' => max(1, Setting::getInt('loyalty_earn_rate_amount', 10000)),
            'redeem_point_value' => max(1, Setting::getInt('loyalty_redeem_point_value', 100)),
            'tiers' => $this->tiers(),
        ];
    }

    public function tiers(): array
    {
        return [
            self::TIER_REGULAR => [
                'label' => 'Regular',
                'minimum_total_spent' => Setting::getInt('loyalty_tier_regular_threshold', 0),
            ],
            self::TIER_SILVER => [
                'label' => 'Silver',
                'minimum_total_spent' => Setting::getInt('loyalty_tier_silver_threshold', 500000),
            ],
            self::TIER_GOLD => [
                'label' => 'Gold',
                'minimum_total_spent' => Setting::getInt('loyalty_tier_gold_threshold', 1500000),
            ],
            self::TIER_PLATINUM => [
                'label' => 'Platinum',
                'minimum_total_spent' => Setting::getInt('loyalty_tier_platinum_threshold', 3000000),
            ],
        ];
    }

    public function tierOptions(): array
    {
        return collect($this->tiers())
            ->map(fn (array $tier, string $key) => [
                'value' => $key,
                'label' => $tier['label'],
            ])
            ->values()
            ->all();
    }

    public function settingsPayload(): array
    {
        $settings = $this->settings();

        return [
            'enable_earn' => $settings['enable_earn'],
            'enable_redeem' => $settings['enable_redeem'],
            'earn_rate_amount' => $settings['earn_rate_amount'],
            'redeem_point_value' => $settings['redeem_point_value'],
            'tiers' => collect($settings['tiers'])
                ->map(fn (array $config, string $key) => [
                    'key' => $key,
                    ...$config,
                ])
                ->values()
                ->all(),
        ];
    }

    public function updateSettings(array $payload): void
    {
        Setting::setMany([
            'loyalty_enable_earn' => [
                'value' => $payload['enable_earn'] ? '1' : '0',
                'description' => 'Aktifkan perolehan poin loyalty',
            ],
            'loyalty_enable_redeem' => [
                'value' => $payload['enable_redeem'] ? '1' : '0',
                'description' => 'Aktifkan redeem poin loyalty',
            ],
            'loyalty_earn_rate_amount' => [
                'value' => (string) $payload['earn_rate_amount'],
                'description' => 'Nominal belanja untuk mendapatkan 1 poin',
            ],
            'loyalty_redeem_point_value' => [
                'value' => (string) $payload['redeem_point_value'],
                'description' => 'Nilai rupiah untuk 1 poin redeem',
            ],
            'loyalty_tier_regular_threshold' => [
                'value' => (string) $payload['tiers'][self::TIER_REGULAR],
                'description' => 'Ambang total belanja tier Regular',
            ],
            'loyalty_tier_silver_threshold' => [
                'value' => (string) $payload['tiers'][self::TIER_SILVER],
                'description' => 'Ambang total belanja tier Silver',
            ],
            'loyalty_tier_gold_threshold' => [
                'value' => (string) $payload['tiers'][self::TIER_GOLD],
                'description' => 'Ambang total belanja tier Gold',
            ],
            'loyalty_tier_platinum_threshold' => [
                'value' => (string) $payload['tiers'][self::TIER_PLATINUM],
                'description' => 'Ambang total belanja tier Platinum',
            ],
        ]);
    }

    public function ensureMembership(Customer $customer, bool $force = false): Customer
    {
        if (! $customer->is_loyalty_member && ! $force) {
            return $customer;
        }

        $payload = [
            'is_loyalty_member' => true,
            'member_code' => $customer->member_code ?: $this->generateMemberCode(),
            'loyalty_member_since' => $customer->loyalty_member_since ?: now(),
        ];

        $customer->fill($payload);

        if ($customer->isDirty()) {
            $customer->save();
        }

        return $customer->refresh();
    }

    public function issueMemberCode(): string
    {
        return $this->generateMemberCode();
    }

    public function syncTier(Customer $customer): Customer
    {
        $tiers = $this->tiers();
        $tier = self::TIER_REGULAR;
        $totalSpent = (int) $customer->loyalty_total_spent;

        foreach ($tiers as $key => $config) {
            if ($totalSpent >= (int) $config['minimum_total_spent']) {
                $tier = $key;
            }
        }

        if ($customer->loyalty_tier !== $tier) {
            $customer->forceFill(['loyalty_tier' => $tier])->save();
        }

        return $customer->refresh();
    }

    public function previewCheckout(
        array $pricingPreview,
        ?Customer $customer = null,
        array $options = [],
        ?CarbonInterface $at = null
    ): array {
        $at = $at ?? now();
        $settings = $this->settings();
        $subtotalAfterPromo = max(0, (int) data_get($pricingPreview, 'summary.subtotal_after_promo', 0));
        $manualDiscountRequested = max(0, (int) ($options['manual_discount'] ?? 0));
        $shippingCost = max(0, (int) ($options['shipping_cost'] ?? 0));
        $requestedRedeemPoints = max(0, (int) ($options['redeem_points'] ?? 0));
        // Metode bayar yang lagi dipilih kasir (cash / qris_static / dst) —
        // dipakai buat filter promo yang punya syarat "hanya cashless" atau
        // "hanya cash".
        $paymentMethod = $options['payment_method'] ?? null;
        // Dukung banyak voucher sekaligus ('vouchers' = array) sekaligus tetap
        // kompatibel dengan pemanggil lama yang masih kirim 'voucher' tunggal.
        // PENTING: sengaja TIDAK di-unique() by promo id. Kode voucher yang
        // sama boleh muncul berkali-kali di array ini kalau kasir applied-nya
        // >1x (mis. customer menang kode PANJAT17 2x lewat undian) — setiap
        // kemunculan mewakili 1 "kemenangan"/pemakaian yang harus dihitung
        // sendiri-sendiri (lihat counter +/- di kasir). Dulu ada ->unique()
        // di sini yang bikin kode yang dipakai 2x cuma kehitung 1x — itu bug-nya.
        $manualPromos = collect($options['vouchers'] ?? [])
            ->filter()
            ->when(
                empty($options['vouchers']) && ($options['voucher'] ?? $options['promo'] ?? null),
                fn (Collection $c) => $c->push($options['voucher'] ?? $options['promo'])
            )
            ->values();
        $cartItems = data_get($pricingPreview, 'items', []);

        $availablePoints = $customer?->is_loyalty_member ? (int) $customer->loyalty_points : 0;

        // Validasi & hitung diskon tiap voucher secara berurutan.
        // - $runningSubtotal tetap dipertahankan buat cek syarat "minimum_order".
        // - $remainingByCartId dilacak PER BARIS KERANJANG, supaya voucher yang
        //   nempel ke 1 produk/kategori tertentu (Panjat Promo) cuma motong
        //   harga barang itu — dan kalau ada voucher ke-2 nempel di barang yang
        //   sama, dia menghitung dari SISA barang itu (kali berturut-turut),
        //   bukan dari harga awal barang lagi.
        $validatedPromos = collect();
        $voucherFreeItems = [];
        $runningSubtotal = $subtotalAfterPromo;
        $remainingByCartId = collect($cartItems)->mapWithKeys(fn ($item) => [
            (int) data_get($item, 'cart_id') => (int) data_get($item, 'line_total', data_get($item, 'price', 0)),
        ])->all();
        // cart_id => array nama voucher yang BENERAN nyumbang potongan ke
        // baris itu. PENTING: array, bukan string tunggal — karena 1 barang
        // bisa kena kontribusi dari LEBIH DARI 1 voucher sekaligus (paling
        // sering kejadian di mode "Total Transaksi": 2 kode dipakai bareng,
        // dua2nya menyebar proporsional ke semua barang eligible). Kalau
        // cuma nyimpen 1 nama (overwrite), voucher yang diproses TERAKHIR
        // bakal "menutupi" kontribusi voucher lain yang sama2 motong barang
        // itu — struk jadi salah bilang cuma 1 kode yang berlaku padahal 2.
        $voucherNameByCartId = [];
        $voucherPromoIdByCartId = [];

        foreach ($manualPromos as $candidatePromo) {
            $validated = $this->promoService->validateManualPromo($customer, $candidatePromo, $runningSubtotal, $at, $cartItems, $paymentMethod);

            if (! $validated) {
                continue;
            }

            $beforeRemaining = $remainingByCartId;
            $result = $this->promoService->calculateManualPromoDiscount($validated, $runningSubtotal, $cartItems, $remainingByCartId);
            $lineDiscount = min($runningSubtotal, max(0, (int) $result['discount']));

            if ($lineDiscount <= 0 && empty($result['free_items'])) {
                continue;
            }

            // Catat nama voucher ini di baris keranjang yang beneran kepotong —
            // dipakai TransactionController::store() buat nampilin nama promo
            // per item di struk/riwayat (bukan cuma di ringkasan transaksi).
            foreach ($remainingByCartId as $cartId => $remaining) {
                if ($remaining < ($beforeRemaining[$cartId] ?? $remaining)) {
                    $voucherNameByCartId[$cartId] = array_values(array_unique([
                        ...($voucherNameByCartId[$cartId] ?? []),
                        $validated->name,
                    ]));
                    // promo_id di transaction_details cuma muat 1 FK — kalau
                    // lebih dari 1 voucher nyumbang, simpan yang PERTAMA kali
                    // nempel (bukan yang terakhir) sebagai referensi utama,
                    // biar konsisten nunjuk ke kode yang duluan berlaku.
                    $voucherPromoIdByCartId[$cartId] ??= $validated->id;
                }
            }

            $runningSubtotal = max(0, $runningSubtotal - $lineDiscount);
            $voucherFreeItems = array_merge($voucherFreeItems, $result['free_items'] ?? []);
            $validatedPromos->push(['promo' => $validated, 'discount' => $lineDiscount]);
        }

        $voucherDiscount = (int) $validatedPromos->sum('discount');
        $validatedPromo = $validatedPromos->first()['promo'] ?? null;

        // Breakdown potongan voucher per baris keranjang (cart_id => rupiah),
        // dipakai TransactionController::store() buat nyimpen discount_total
        // per item di transaction_details — biar struk & riwayat transparan
        // barang mana kena voucher berapa.
        $voucherDiscountByCartId = collect($cartItems)
            ->mapWithKeys(function ($item) use ($remainingByCartId) {
                $cartId = (int) data_get($item, 'cart_id');
                $original = (int) data_get($item, 'line_total', data_get($item, 'price', 0));
                $remaining = (int) ($remainingByCartId[$cartId] ?? $original);

                return [$cartId => max(0, $original - $remaining)];
            })
            ->filter(fn (int $amount) => $amount > 0)
            ->all();

        // Diskon manual PER ITEM — kasir pilih 1 barang spesifik di keranjang
        // dan kasih diskon Rp atau % khusus buat barang itu (beda dari
        // "Diskon Manual" transaksi yang motong TOTAL keranjang di bawah).
        // Basisnya SISA harga barang itu setelah promo otomatis & voucher
        // (pakai $remainingByCartId yang sama, jadi tetap konsisten kalau
        // barang itu juga kena voucher).
        $itemManualDiscountByCartId = [];

        foreach (($options['item_discounts'] ?? []) as $entry) {
            $cartId = (int) data_get($entry, 'cart_id');
            $mode = data_get($entry, 'mode') === 'percent' ? 'percent' : 'nominal';
            $value = max(0, (float) data_get($entry, 'value', 0));

            $remainingLineValue = (int) ($remainingByCartId[$cartId] ?? 0);
            if ($remainingLineValue <= 0) {
                continue;
            }

            $discount = $mode === 'percent'
                ? (int) round($remainingLineValue * (min(100, $value) / 100))
                : (int) round($value);
            $discount = max(0, min($remainingLineValue, $discount));

            if ($discount <= 0) {
                continue;
            }

            $remainingByCartId[$cartId] = $remainingLineValue - $discount;
            $itemManualDiscountByCartId[$cartId] = $discount;
        }

        $itemManualDiscountTotal = (int) array_sum($itemManualDiscountByCartId);

        $afterVoucher = max(0, $subtotalAfterPromo - $voucherDiscount - $itemManualDiscountTotal);
        $redeemPointValue = (int) $settings['redeem_point_value'];
        $maxRedeemPoints = $settings['enable_redeem']
            ? (int) floor($afterVoucher / max(1, $redeemPointValue))
            : 0;
        $appliedRedeemPoints = $settings['enable_redeem']
            ? min($requestedRedeemPoints, $availablePoints, $maxRedeemPoints)
            : 0;
        $pointsDiscount = $appliedRedeemPoints * $redeemPointValue;

        $afterLoyalty = max(0, $afterVoucher - $pointsDiscount);
        $manualDiscountApplied = min($manualDiscountRequested, $afterLoyalty);
        $grandTotal = max(0, $afterLoyalty - $manualDiscountApplied + $shippingCost);
        $pointsEarnedPreview = $this->calculateEarnPoints(
            $customer,
            max(0, $grandTotal - $shippingCost),
            $settings
        );

        return [
            'items' => data_get($pricingPreview, 'items', []),
            'applied_groups' => data_get($pricingPreview, 'applied_groups', []),
            'consumed_quantities' => data_get($pricingPreview, 'consumed_quantities', []),
            'unmatched_items' => data_get($pricingPreview, 'unmatched_items', []),
            'summary' => [
                'base_subtotal' => (int) data_get($pricingPreview, 'summary.base_subtotal', 0),
                'promo_discount_total' => (int) data_get($pricingPreview, 'summary.promo_discount_total', 0),
                'subtotal_after_promo' => $subtotalAfterPromo,
                'voucher_discount_total' => $voucherDiscount,
                'voucher_discount_by_cart_id' => $voucherDiscountByCartId,
                'voucher_name_by_cart_id' => $voucherNameByCartId,
                'voucher_promo_id_by_cart_id' => $voucherPromoIdByCartId,
                'voucher_free_items' => $voucherFreeItems,
                'item_manual_discount_total' => $itemManualDiscountTotal,
                'item_manual_discount_by_cart_id' => $itemManualDiscountByCartId,
                'loyalty_discount_total' => $pointsDiscount,
                'manual_discount_total' => $manualDiscountApplied,
                'shipping_cost' => $shippingCost,
                'grand_total' => $grandTotal,
                'available_loyalty_points' => $availablePoints,
                'requested_redeem_points' => $requestedRedeemPoints,
                'applied_redeem_points' => $appliedRedeemPoints,
                'points_value' => $redeemPointValue,
                'points_earned_preview' => $pointsEarnedPreview,
            ],
            'customer' => $customer ? [
                'id' => $customer->id,
                'is_loyalty_member' => (bool) $customer->is_loyalty_member,
                'member_code' => $customer->member_code,
                'loyalty_tier' => $customer->loyalty_tier,
                'loyalty_points' => $availablePoints,
            ] : null,
            // 'voucher' dipertahankan (voucher pertama yang berhasil dipakai) demi
            // kompatibilitas mundur; 'vouchers' berisi SEMUA voucher yang aktif
            // dipakai pada transaksi ini (mendukung multi-voucher).
            'voucher' => $validatedPromo ? $this->promoService->serializeManualPromo($validatedPromo, $validatedPromos->first()['discount'] ?? 0) : null,
            'vouchers' => $validatedPromos
                ->map(fn (array $applied) => $this->promoService->serializeManualPromo($applied['promo'], $applied['discount']))
                ->values()
                ->all(),
            'eligible_vouchers' => $this->promoService
                ->eligibleManualPromos($customer, $runningSubtotal, $at, $cartItems, $paymentMethod)
                ->map(function (Promo $eligiblePromo) use ($runningSubtotal, $cartItems, $remainingByCartId) {
                    $previewRemaining = $remainingByCartId;

                    return $this->promoService->serializeManualPromo(
                        $eligiblePromo,
                        $this->promoService->calculateManualPromoDiscount($eligiblePromo, $runningSubtotal, $cartItems, $previewRemaining)['discount']
                    );
                })
                ->values()
                ->all(),
            'settings' => $this->settingsPayload(),
        ];
    }

    public function finalizeTransaction(
        Transaction $transaction,
        ?Customer $customer,
        array $checkoutPreview
    ): ?Customer {
        if (! $customer) {
            return null;
        }

        $settings = $this->settings();
        $customer = $customer->fresh();

        if ($customer->is_loyalty_member) {
            $customer = $this->ensureMembership($customer);
        }

        $redeemedPoints = (int) ($transaction->loyalty_points_redeemed ?? 0);
        if ($redeemedPoints > 0 && $customer->is_loyalty_member && $settings['enable_redeem']) {
            $customer->decrement('loyalty_points', $redeemedPoints);

            $this->recordHistory(
                $customer->fresh(),
                $transaction,
                LoyaltyPointHistory::TYPE_REDEEM,
                -$redeemedPoints,
                (int) ($transaction->loyalty_discount_total ?? 0),
                'Redeem poin pada transaksi '.$transaction->invoice
            );
        }

        $voucherCode = (string) ($transaction->customer_voucher_code ?? '');
        if ($voucherCode !== '' && (int) ($transaction->customer_voucher_discount ?? 0) > 0) {
            $this->recordHistory(
                $customer->fresh(),
                $transaction,
                LoyaltyPointHistory::TYPE_VOUCHER,
                0,
                (int) ($transaction->customer_voucher_discount ?? 0),
                'Voucher '.$voucherCode.' digunakan'
            );
        }

        $eligibleSpendForPoints = max(
            0,
            (int) $transaction->grand_total - (int) $transaction->shipping_cost
        );
        $earnedPoints = $this->calculateEarnPoints($customer, $eligibleSpendForPoints, $settings);

        $transaction->forceFill([
            'loyalty_points_earned' => $earnedPoints,
        ])->save();

        $customer->forceFill([
            'loyalty_points' => max(0, (int) $customer->loyalty_points) + $earnedPoints,
            'loyalty_total_spent' => (int) $customer->loyalty_total_spent + (int) $transaction->grand_total,
            'loyalty_transaction_count' => (int) $customer->loyalty_transaction_count + 1,
            'last_purchase_at' => now(),
        ])->save();

        if ($earnedPoints > 0) {
            $this->recordHistory(
                $customer->fresh(),
                $transaction,
                LoyaltyPointHistory::TYPE_EARN,
                $earnedPoints,
                (int) $transaction->grand_total,
                'Poin transaksi '.$transaction->invoice
            );
        }

        return $this->syncTier($customer->fresh());
    }

    public function syncAllMemberTiers(): void
    {
        Customer::query()
            ->where('is_loyalty_member', true)
            ->orderBy('id')
            ->chunkById(100, function ($customers) {
                foreach ($customers as $customer) {
                    $this->syncTier($customer);
                }
            });
    }

    private function calculateEarnPoints(?Customer $customer, int $eligibleSpend, array $settings): int
    {
        if (! $customer?->is_loyalty_member || ! $settings['enable_earn']) {
            return 0;
        }

        return (int) floor($eligibleSpend / max(1, (int) $settings['earn_rate_amount']));
    }

    private function recordHistory(
        Customer $customer,
        Transaction $transaction,
        string $type,
        int $pointsDelta,
        int $amountDelta,
        string $notes
    ): void {
        LoyaltyPointHistory::create([
            'customer_id' => $customer->id,
            'transaction_id' => $transaction->id,
            'type' => $type,
            'points_delta' => $pointsDelta,
            'balance_after' => max(0, (int) $customer->loyalty_points),
            'amount_delta' => max(0, $amountDelta),
            'reference' => $transaction->invoice,
            'notes' => $notes,
        ]);
    }

    private function generateMemberCode(): string
    {
        do {
            $code = 'MEM-'.Str::upper(Str::random(8));
        } while (Customer::query()->where('member_code', $code)->exists());

        return $code;
    }
}