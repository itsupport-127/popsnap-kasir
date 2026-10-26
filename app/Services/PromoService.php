<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Promo;
use App\Models\PromoBuyGetItem;
use App\Models\PromoQtyBreak;
use App\Models\TransactionDetail;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Satu mesin promo untuk semua jenis diskon (nominal, persen, grosir
 * bertingkat, bundle, beli-X-gratis-Y), baik yang aktif otomatis begitu
 * syarat keranjang cocok (activation_mode = auto) maupun yang harus
 * diaktifkan manual oleh kasir lewat kode/tombol (activation_mode = manual).
 *
 * Menggantikan PricingService (promo otomatis) + bagian voucher dari
 * LoyaltyService (promo manual) yang sebelumnya terpisah.
 */
class PromoService
{
    public function getActivePromos(?CarbonInterface $at = null): Collection
    {
        $at = $at ?? now();

        return Promo::query()
            ->with([
                'product:id,title,sell_price,category_id',
                'category:id,name',
                'getProduct:id,title,sell_price,category_id',
                'qtyBreaks',
                'bundleItems.product:id,title,sell_price,category_id',
                'buyGetItems.product:id,title,sell_price,category_id',
            ])
            ->where('is_active', true)
            ->where(function ($query) use ($at) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $at);
            })
            ->where(function ($query) use ($at) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $at);
            })
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();
    }

    /**
     * Promo otomatis saja (siap diterapkan tanpa interaksi kasir).
     * $paymentMethod dipakai buat nolak promo otomatis yang punya syarat
     * "hanya cashless"/"hanya cash" tapi metode bayar yang dipakai kasir
     * tidak cocok. NULL = belum tahu metode bayarnya (masih preview awal),
     * jadi tetap ditampilkan dulu — sama seperti voucher manual.
     */
    public function getAutoPromos(?CarbonInterface $at = null, ?string $paymentMethod = null): Collection
    {
        return $this->getActivePromos($at)
            ->filter(fn (Promo $promo) => $promo->isAuto())
            ->filter(fn (Promo $promo) => $promo->matchesPaymentMethod($paymentMethod))
            ->values();
    }

    /**
     * Hitung & terapkan semua promo otomatis ke isi keranjang, lalu kembalikan
     * struktur item + ringkasan harga. Promo manual TIDAK diterapkan di sini —
     * itu ditangani terpisah lewat previewManualPromo()/applyManualPromo()
     * supaya kasir bisa pilih kapan promo manual diaktifkan.
     */
    public function previewCart(iterable $carts, ?Customer $customer = null, ?CarbonInterface $at = null, ?string $paymentMethod = null): array
    {
        $cartCollection = collect($carts)
            ->filter(fn ($cart) => $cart instanceof Cart && $cart->product)
            ->values();

        return $this->buildAutoPreview($cartCollection, $customer, $this->getAutoPromos($at, $paymentMethod));
    }

    public function previewCartWithPromos(iterable $carts, ?Customer $customer, Collection $autoPromos): array
    {
        $cartCollection = collect($carts)
            ->filter(fn ($cart) => $cart instanceof Cart && $cart->product)
            ->values();

        return $this->buildAutoPreview($cartCollection, $customer, $autoPromos->values());
    }

    public function previewProducts(iterable $products, ?Customer $customer = null, ?CarbonInterface $at = null): Collection
    {
        $promos = $this->getAutoPromos($at);

        return collect($products)
            ->filter(fn ($product) => $product instanceof Product)
            ->mapWithKeys(function (Product $product) use ($customer, $promos) {
                return [$product->id => $this->calculateProductPrice($product, 1, $customer, $promos)];
            });
    }

    public function calculateProductPrice(
        Product $product,
        int $qty = 1,
        ?Customer $customer = null,
        ?Collection $promos = null
    ): array {
        $promos = $promos ?? $this->getAutoPromos();
        $quantity = max(1, $qty);
        $matchingPromos = $promos
            ->filter(fn (Promo $promo) => $promo->matchesCustomerScope($customer))
            ->filter(fn (Promo $promo) => $this->promoTouchesProduct($promo, $product));

        $directCandidate = $matchingPromos
            ->filter(fn (Promo $promo) => in_array($promo->discount_kind, [
                Promo::KIND_STANDARD_DISCOUNT,
                Promo::KIND_QTY_BREAK,
            ], true))
            ->map(function (Promo $promo) use ($product, $quantity) {
                $previewQuantity = $promo->discount_kind === Promo::KIND_QTY_BREAK
                    ? max($quantity, (int) ($promo->preview_quantity_multiplier ?: $promo->qtyBreaks->max('min_qty') ?: 1))
                    : $quantity;

                return $this->calculateLineCandidate($promo, $product, $previewQuantity);
            })
            ->filter()
            ->sortBy([
                ['promo.priority', 'desc'],
                ['line_discount', 'desc'],
                ['promo.id', 'asc'],
            ])
            ->first();

        if ($directCandidate) {
            $baseUnitPrice = (int) $directCandidate['base_unit_price'];
            $effectiveUnitPrice = (int) round(
                $directCandidate['line_total'] / max(1, (int) $directCandidate['quantity'])
            );
            $promo = $directCandidate['promo'];

            return [
                'base_unit_price' => $baseUnitPrice,
                'effective_unit_price' => $effectiveUnitPrice,
                'quantity' => (int) $directCandidate['quantity'],
                'line_base_total' => (int) $directCandidate['line_base_total'],
                'line_total' => (int) $directCandidate['line_total'],
                'line_discount_total' => (int) $directCandidate['line_discount'],
                'promo' => $this->serializePromo($promo),
            ];
        }

        $complexPromo = $matchingPromos
            ->filter(fn (Promo $promo) => in_array($promo->discount_kind, [
                Promo::KIND_BUNDLE_PRICE,
                Promo::KIND_BUY_X_GET_Y,
            ], true))
            ->sortBy([
                ['priority', 'desc'],
                ['id', 'asc'],
            ])
            ->first();

        return [
            'base_unit_price' => (int) $product->sell_price,
            'effective_unit_price' => (int) $product->sell_price,
            'quantity' => $quantity,
            'line_base_total' => (int) $product->sell_price * $quantity,
            'line_total' => (int) $product->sell_price * $quantity,
            'line_discount_total' => 0,
            'promo' => $complexPromo ? $this->serializePromo($complexPromo, false) : null,
        ];
    }

    public function promoLabel(Promo $promo): string
    {
        return $promo->discountKindLabel();
    }

    private function buildAutoPreview(Collection $carts, ?Customer $customer, Collection $promos): array
    {
        $items = $carts->map(function (Cart $cart) {
            $baseUnitPrice = (int) $cart->product->sell_price;

            return [
                'cart_id' => $cart->id,
                'product_id' => $cart->product_id,
                'product_title' => $cart->product?->title,
                'category_id' => $cart->product?->category_id,
                'qty' => (int) $cart->qty,
                'base_unit_price' => $baseUnitPrice,
                'effective_unit_price' => $baseUnitPrice,
                'line_base_total' => $baseUnitPrice * (int) $cart->qty,
                'line_total' => $baseUnitPrice * (int) $cart->qty,
                'line_discount_total' => 0,
                'promo' => null,
                'promo_group_key' => null,
                'promo_group_label' => null,
                'applied_promos' => [],
            ];
        })->keyBy('cart_id');

        $remainingQuantities = $items
            ->mapWithKeys(fn (array $item, int|string $cartId) => [(int) $cartId => (int) $item['qty']])
            ->all();

        $eligiblePromos = $promos
            ->filter(fn (Promo $promo) => $promo->matchesCustomerScope($customer))
            ->values();

        $appliedGroups = [];

        $bundlePromos = $eligiblePromos
            ->filter(fn (Promo $promo) => $promo->discount_kind === Promo::KIND_BUNDLE_PRICE)
            ->values();
        $appliedGroups = array_merge(
            $appliedGroups,
            $this->applyComplexStage($bundlePromos, $items, $remainingQuantities, 'bundle')
        );

        $buyGetPromos = $eligiblePromos
            ->filter(fn (Promo $promo) => $promo->discount_kind === Promo::KIND_BUY_X_GET_Y)
            ->values();
        $appliedGroups = array_merge(
            $appliedGroups,
            $this->applyComplexStage($buyGetPromos, $items, $remainingQuantities, 'buy_get')
        );

        foreach ($items as $cartId => $item) {
            $remainingQty = max(0, (int) ($remainingQuantities[$cartId] ?? 0));
            if ($remainingQty === 0) {
                continue;
            }

            $cartProduct = $carts->firstWhere('id', $cartId)?->product;
            if (! $cartProduct) {
                continue;
            }

            $candidate = $eligiblePromos
                ->filter(fn (Promo $promo) => in_array($promo->discount_kind, [
                    Promo::KIND_QTY_BREAK,
                    Promo::KIND_STANDARD_DISCOUNT,
                ], true))
                ->map(fn (Promo $promo) => $this->calculateLineCandidate($promo, $cartProduct, $remainingQty))
                ->filter()
                ->sortBy([
                    ['promo.priority', 'desc'],
                    ['line_discount', 'desc'],
                    ['promo.id', 'asc'],
                ])
                ->first();

            if (! $candidate || (int) $candidate['line_discount'] <= 0) {
                continue;
            }

            $currentItem = $items->get($cartId);
            $currentItem['line_total'] -= (int) $candidate['line_discount'];
            $currentItem['line_discount_total'] += (int) $candidate['line_discount'];
            $currentItem['promo'] = $this->serializePromo($candidate['promo']);
            $currentItem['applied_promos'][] = $this->serializePromo($candidate['promo']);
            $currentItem['promo_group_key'] ??= 'promo-'.$candidate['promo']->id;
            $currentItem['promo_group_label'] ??= $candidate['promo']->name;
            $items->put($cartId, $currentItem);
        }

        $items = $items->map(function (array $item) {
            $lineTotal = max(0, (int) $item['line_total']);
            $item['line_total'] = $lineTotal;
            $item['line_discount_total'] = max(0, (int) $item['line_discount_total']);
            $item['effective_unit_price'] = (int) round($lineTotal / max(1, (int) $item['qty']));

            return $item;
        })->values();

        $baseSubtotal = (int) $items->sum('line_base_total');
        $promoDiscountTotal = (int) $items->sum('line_discount_total');
        $subtotalAfterPromo = max(0, $baseSubtotal - $promoDiscountTotal);

        return [
            'items' => $items->all(),
            'applied_groups' => array_values($appliedGroups),
            'consumed_quantities' => collect($remainingQuantities)
                ->mapWithKeys(function (int $qty, int $cartId) use ($items) {
                    $original = (int) collect($items)->firstWhere('cart_id', $cartId)['qty'];

                    return [$cartId => max(0, $original - $qty)];
                })
                ->all(),
            'unmatched_items' => collect($remainingQuantities)
                ->filter(fn (int $qty) => $qty > 0)
                ->mapWithKeys(fn (int $qty, int $cartId) => [$cartId => $qty])
                ->all(),
            'summary' => [
                'base_subtotal' => $baseSubtotal,
                'promo_discount_total' => $promoDiscountTotal,
                'subtotal_after_promo' => $subtotalAfterPromo,
            ],
        ];
    }

    private function applyComplexStage(
        Collection $promos,
        Collection &$items,
        array &$remainingQuantities,
        string $stage
    ): array {
        $groups = [];

        while (true) {
            $candidates = $promos
                ->map(function (Promo $promo) use ($items, $remainingQuantities, $stage) {
                    return $stage === 'bundle'
                        ? $this->buildBundleCandidate($promo, $items, $remainingQuantities)
                        : $this->buildBuyGetCandidate($promo, $items, $remainingQuantities);
                })
                ->filter()
                ->sortBy([
                    ['priority', 'desc'],
                    ['discount_total', 'desc'],
                    ['promo_id', 'asc'],
                ])
                ->values();

            $candidate = $candidates->first();
            if (! $candidate) {
                break;
            }

            foreach ($candidate['participants'] as $participant) {
                $cartId = (int) $participant['cart_id'];
                $consumeQty = (int) $participant['quantity'];
                $remainingQuantities[$cartId] = max(0, (int) ($remainingQuantities[$cartId] ?? 0) - $consumeQty);

                $currentItem = $items->get($cartId);
                $currentItem['line_total'] -= (int) $participant['discount_total'];
                $currentItem['line_discount_total'] += (int) $participant['discount_total'];
                $currentItem['promo_group_key'] = $candidate['group_key'];
                $currentItem['promo_group_label'] = $candidate['group_label'];
                $currentItem['promo'] = $this->serializePromo($candidate['promo']);
                $currentItem['applied_promos'][] = $this->serializePromo($candidate['promo']);
                $items->put($cartId, $currentItem);
            }

            $groups[] = [
                'key' => $candidate['group_key'],
                'label' => $candidate['group_label'],
                'promo' => $this->serializePromo($candidate['promo']),
                'discount_total' => (int) $candidate['discount_total'],
                'participants' => $candidate['participants'],
            ];
        }

        return $groups;
    }

    private function buildBundleCandidate(Promo $promo, Collection $items, array $remainingQuantities): ?array
    {
        if ($promo->bundleItems->isEmpty()) {
            return null;
        }

        $participants = [];
        $tempRemaining = $remainingQuantities;

        foreach ($promo->bundleItems as $bundleItem) {
            $matched = $this->consumeMatchingItems(
                $items,
                $tempRemaining,
                fn (array $item) => (int) $item['product_id'] === (int) $bundleItem->product_id,
                (int) $bundleItem->quantity
            );

            if ($matched === null) {
                return null;
            }

            $participants = array_merge($participants, $matched);
        }

        $baseTotal = 0;
        foreach ($participants as $participant) {
            $baseTotal += (int) $participant['base_total'];
        }

        $bundlePrice = (int) round((float) $promo->discount_value);
        if ($bundlePrice >= $baseTotal) {
            return null;
        }

        $allocations = $this->allocateDiscount(
            $participants,
            $baseTotal - $bundlePrice
        );

        return [
            'promo' => $promo,
            'promo_id' => (int) $promo->id,
            'priority' => (int) $promo->priority,
            'group_key' => 'bundle-'.$promo->id.'-'.str()->uuid(),
            'group_label' => $promo->name,
            'discount_total' => $baseTotal - $bundlePrice,
            'participants' => $allocations,
        ];
    }

    /**
     * Buy-get OTOMATIS untuk produk spesifik (lewat promo_buy_get_items).
     * Untuk BOGO generik/manual ("semua produk", tanpa pilih produk), lihat
     * calculateManualBogoDiscount() — jalur yang berbeda karena tidak
     * butuh pivot produk sama sekali.
     */
    private function buildBuyGetCandidate(Promo $promo, Collection $items, array $remainingQuantities): ?array
    {
        $buyItems = $promo->buyGetItems
            ->where('role', PromoBuyGetItem::ROLE_BUY)
            ->values();
        $getItems = $promo->buyGetItems
            ->where('role', PromoBuyGetItem::ROLE_GET)
            ->values();

        if ($buyItems->isEmpty() || $getItems->isEmpty()) {
            return null;
        }

        $participants = [];
        $tempRemaining = $remainingQuantities;

        foreach ($buyItems as $buyItem) {
            $matched = $this->consumeMatchingItems(
                $items,
                $tempRemaining,
                fn (array $item) => (int) $item['product_id'] === (int) $buyItem->product_id,
                (int) $buyItem->quantity
            );

            if ($matched === null) {
                return null;
            }

            foreach ($matched as $match) {
                $match['discount_total'] = 0;
                $participants[] = $match;
            }
        }

        $rewardParticipants = [];
        foreach ($getItems as $getItem) {
            $matched = $this->consumeMatchingItems(
                $items,
                $tempRemaining,
                fn (array $item) => (int) $item['product_id'] === (int) $getItem->product_id,
                (int) $getItem->quantity
            );

            if ($matched === null) {
                return null;
            }

            foreach ($matched as $match) {
                $match['discount_total'] = (int) $match['base_total'];
                $rewardParticipants[] = $match;
                $participants[] = $match;
            }
        }

        $discountTotal = (int) collect($rewardParticipants)->sum('discount_total');
        if ($discountTotal <= 0) {
            return null;
        }

        return [
            'promo' => $promo,
            'promo_id' => (int) $promo->id,
            'priority' => (int) $promo->priority,
            'group_key' => 'bxgy-'.$promo->id.'-'.str()->uuid(),
            'group_label' => $promo->name,
            'discount_total' => $discountTotal,
            'participants' => $participants,
        ];
    }

    private function consumeMatchingItems(
        Collection $items,
        array &$remainingQuantities,
        callable $matcher,
        int $requiredQuantity
    ): ?array {
        $required = max(1, $requiredQuantity);
        $matches = [];

        foreach ($items as $cartId => $item) {
            if ($required <= 0) {
                break;
            }

            if (! $matcher($item)) {
                continue;
            }

            $availableQty = (int) ($remainingQuantities[$cartId] ?? 0);
            if ($availableQty <= 0) {
                continue;
            }

            $take = min($availableQty, $required);
            $matches[] = [
                'cart_id' => (int) $cartId,
                'product_id' => (int) $item['product_id'],
                'product_title' => $item['product_title'],
                'quantity' => $take,
                'base_total' => (int) $item['base_unit_price'] * $take,
            ];
            $required -= $take;
        }

        return $required === 0 ? $matches : null;
    }

    private function allocateDiscount(array $participants, int $discountTotal): array
    {
        $baseTotal = max(1, (int) collect($participants)->sum('base_total'));
        $allocated = [];
        $running = 0;
        $lastIndex = array_key_last($participants);

        foreach ($participants as $index => $participant) {
            $share = $index === $lastIndex
                ? $discountTotal - $running
                : (int) floor($discountTotal * ((int) $participant['base_total'] / $baseTotal));
            $share = max(0, min((int) $participant['base_total'], $share));
            $running += $share;
            $participant['discount_total'] = $share;
            $allocated[] = $participant;
        }

        return $allocated;
    }

    private function calculateLineCandidate(Promo $promo, Product $product, int $quantity): ?array
    {
        if (! $this->matchesTarget($promo, $product)) {
            return null;
        }

        $baseUnitPrice = (int) $product->sell_price;
        $lineBaseTotal = $baseUnitPrice * $quantity;

        if ($promo->discount_kind === Promo::KIND_QTY_BREAK) {
            $break = $promo->qtyBreaks
                ->filter(fn (PromoQtyBreak $break) => $quantity >= (int) $break->min_qty)
                ->sortBy([
                    ['min_qty', 'desc'],
                    ['sort_order', 'asc'],
                    ['id', 'asc'],
                ])
                ->first();

            if (! $break) {
                return null;
            }

            $discount = $this->resolveLineDiscount(
                $break->discount_type,
                (float) $break->discount_value,
                $baseUnitPrice,
                $quantity
            );

            return [
                'promo' => $promo,
                'quantity' => $quantity,
                'base_unit_price' => $baseUnitPrice,
                'line_base_total' => $lineBaseTotal,
                'line_total' => max(0, $lineBaseTotal - $discount),
                'line_discount' => $discount,
            ];
        }

        $discount = $this->resolveLineDiscount(
            $promo->discount_type,
            (float) $promo->discount_value,
            $baseUnitPrice,
            $quantity
        );

        return [
            'promo' => $promo,
            'quantity' => $quantity,
            'base_unit_price' => $baseUnitPrice,
            'line_base_total' => $lineBaseTotal,
            'line_total' => max(0, $lineBaseTotal - $discount),
            'line_discount' => $discount,
        ];
    }

    private function matchesTarget(Promo $promo, Product $product): bool
    {
        return match ($promo->target_type) {
            Promo::TARGET_ALL => true,
            Promo::TARGET_PRODUCT => (int) $promo->product_id === (int) $product->id,
            Promo::TARGET_CATEGORY => (int) $promo->category_id === (int) $product->category_id,
            default => false,
        };
    }

    private function resolveLineDiscount(
        string $discountType,
        float $discountValue,
        int $baseUnitPrice,
        int $quantity
    ): int {
        $lineBaseTotal = $baseUnitPrice * $quantity;

        $discount = match ($discountType) {
            Promo::TYPE_PERCENTAGE => (int) round($lineBaseTotal * ($discountValue / 100)),
            Promo::TYPE_FIXED_AMOUNT => (int) round($discountValue) * $quantity,
            Promo::TYPE_FIXED_PRICE => max(0, $lineBaseTotal - ((int) round($discountValue) * $quantity)),
            default => 0,
        };

        return min($lineBaseTotal, max(0, $discount));
    }

    private function promoTouchesProduct(Promo $promo, Product $product): bool
    {
        if ($this->matchesTarget($promo, $product)) {
            return true;
        }

        if ($promo->discount_kind === Promo::KIND_BUNDLE_PRICE) {
            return $promo->bundleItems->contains(fn ($item) => (int) $item->product_id === (int) $product->id);
        }

        if ($promo->discount_kind === Promo::KIND_BUY_X_GET_Y) {
            // BOGO generik (target_type=all, tanpa pivot produk) selalu
            // dianggap menyentuh produk apa pun.
            if ($promo->buyGetItems->isEmpty()) {
                return $promo->target_type === Promo::TARGET_ALL || $this->matchesTarget($promo, $product);
            }

            return $promo->buyGetItems->contains(fn ($item) => (int) $item->product_id === (int) $product->id);
        }

        return false;
    }

    public function serializePromo(Promo $promo, bool $includePriceContext = true): array
    {
        return [
            'id' => $promo->id,
            'name' => $promo->name,
            'discount_kind' => $promo->discount_kind,
            'label' => $this->promoLabel($promo),
            'priority' => (int) $promo->priority,
            'target_type' => $promo->target_type,
            'customer_scope' => $promo->customer_scope,
            'eligible_loyalty_tiers' => $promo->eligible_loyalty_tiers,
            'price_context' => $includePriceContext,
        ];
    }

    // =========================================================================
    // PROMO MANUAL — kasir aktifkan lewat kode/tombol (dulu: Customer Voucher)
    // =========================================================================

    /**
     * Daftar promo manual yang BERHAK dipakai untuk konteks transaksi saat
     * ini: promo personal milik customer yang dipilih, ditambah promo umum
     * (all / member_only / walk_in) yang masih berlaku dan belum melebihi
     * kuota. $cartItems memakai struktur item dari previewCart() (product_id,
     * category_id, qty, effective_unit_price, line_total, dst).
     */
    public function eligibleManualPromos(
        ?Customer $customer,
        int $subtotalAfterAutoPromo = 0,
        ?CarbonInterface $at = null,
        array $cartItems = [],
        ?string $paymentMethod = null
    ): Collection {
        $at = $at ?? now();

        $personal = $customer
            ? $customer->vouchers()
                ->where('is_active', true)
                ->where('is_used', false)
                ->where(function ($query) use ($at) {
                    $query->whereNull('starts_at')->orWhere('starts_at', '<=', $at);
                })
                ->where(function ($query) use ($at) {
                    $query->whereNull('ends_at')->orWhere('ends_at', '>=', $at);
                })
                ->get()
            : collect();

        $general = Promo::query()
            ->where('activation_mode', Promo::ACTIVATION_MANUAL)
            ->whereNull('customer_id')
            ->where('is_active', true)
            ->where(function ($query) use ($at) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $at);
            })
            ->where(function ($query) use ($at) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $at);
            })
            ->get()
            ->filter(fn (Promo $promo) => $promo->matchesCustomerScope($customer));

        return $personal->merge($general)
            ->filter(fn (Promo $promo) => $promo->usageAvailable())
            ->filter(fn (Promo $promo) => $promo->matchesPaymentMethod($paymentMethod))
            ->filter(fn (Promo $promo) => $subtotalAfterAutoPromo >= (int) $promo->minimum_order)
            ->filter(function (Promo $promo) use ($customer) {
                if ($promo->usage_limit_mode !== Promo::USAGE_LIMIT_PER_CUSTOMER || ! $customer) {
                    return true;
                }

                return ! $this->customerAlreadyUsedPromo($customer, $promo);
            })
            ->filter(function (Promo $promo) use ($cartItems) {
                // Promo dengan target produk/kategori tertentu hanya tampil
                // kalau ada item yang cocok di keranjang saat ini. Target
                // "all" / scope keranjang kosong selalu lolos di sini.
                if ($promo->target_type === Promo::TARGET_ALL) {
                    return true;
                }

                return collect($cartItems)->contains(function ($item) use ($promo) {
                    $productId  = (int) ($item['product_id'] ?? 0);
                    $categoryId = (int) ($item['category_id'] ?? 0);

                    return match ($promo->target_type) {
                        Promo::TARGET_PRODUCT  => (int) $promo->product_id === $productId,
                        Promo::TARGET_CATEGORY => (int) $promo->category_id === $categoryId,
                        default => false,
                    };
                });
            })
            ->filter(function (Promo $promo) use ($subtotalAfterAutoPromo, $cartItems) {
                // Promo BOGO/grosir hanya ditawarkan kalau syaratnya benar2
                // terpenuhi oleh isi keranjang saat ini — supaya kasir tidak
                // melihat tombol yang ujung-ujungnya diskonnya Rp 0.
                if (! in_array($promo->discount_kind, [Promo::KIND_BUY_X_GET_Y, Promo::KIND_QTY_BREAK], true)) {
                    return true;
                }

                return $this->calculateManualPromoDiscount($promo, $subtotalAfterAutoPromo, $cartItems)['discount'] > 0;
            })
            ->values();
    }

    /**
     * Validasi ulang promo manual yang dipilih kasir, sebelum benar-benar
     * dipakai (dipanggil saat preview & saat transaksi disimpan). Tidak boleh
     * percaya begitu saja pada id yang dikirim dari frontend.
     */
    public function validateManualPromo(
        ?Customer $customer,
        mixed $promo,
        int $subtotalAfterAutoPromo,
        ?CarbonInterface $at = null,
        array $cartItems = [],
        ?string $paymentMethod = null
    ): ?Promo {
        $at = $at ?? now();

        if (! $promo instanceof Promo || ! $promo->isManual()) {
            return null;
        }

        if (! $promo->matchesCustomerScope($customer)) {
            return null;
        }

        if (! $promo->matchesPaymentMethod($paymentMethod)) {
            return null;
        }

        if (! $promo->is_active || ! $promo->usageAvailable()) {
            return null;
        }

        if ($promo->starts_at && $promo->starts_at->gt($at)) {
            return null;
        }

        if ($promo->ends_at && $promo->ends_at->lt($at)) {
            return null;
        }

        if ($subtotalAfterAutoPromo < (int) $promo->minimum_order) {
            return null;
        }

        // Mode "1x per pelanggan" — begitu pelanggan ini pernah berhasil
        // pakai kode ini (di transaksi manapun, kapanpun, dicek dari
        // transaction_details.promo_id), kode ini nggak muncul/ditolak lagi
        // buat pelanggan yang sama. Kalau transaksinya belum ada pelanggan
        // terdaftar (walk-in / null), pengecekan ini dilewati — nggak ada
        // cara ngelacak "pelanggan yang sama" tanpa identitas.
        if ($promo->usage_limit_mode === Promo::USAGE_LIMIT_PER_CUSTOMER && $customer) {
            if ($this->customerAlreadyUsedPromo($customer, $promo)) {
                return null;
            }
        }

        if (in_array($promo->discount_kind, [Promo::KIND_BUY_X_GET_Y, Promo::KIND_QTY_BREAK], true) && $cartItems !== []) {
            $result = $this->calculateManualPromoDiscount($promo, $subtotalAfterAutoPromo, $cartItems);
            if ((int) $result['discount'] <= 0 && empty($result['free_items'])) {
                return null;
            }
        }

        return $promo;
    }

    /**
     * Cek apakah pelanggan ini sudah PERNAH berhasil pakai kode promo ini,
     * di transaksi manapun sebelumnya (dilihat dari transaction_details.
     * promo_id, yang sekarang tercatat baik buat promo otomatis maupun
     * voucher manual). Dipakai buat mode usage_limit_mode="per_customer".
     */
    public function customerAlreadyUsedPromo(Customer $customer, Promo $promo): bool
    {
        return TransactionDetail::query()
            ->where('promo_id', $promo->id)
            ->whereHas('transaction', fn ($query) => $query->where('customer_id', $customer->id))
            ->exists();
    }

    /**
     * @return array{discount: int, free_items: array}
     */
    public function calculateManualPromoDiscount(Promo $promo, int $subtotalAfterAutoPromo, array $cartItems = [], ?array &$remainingByCartId = null): array
    {
        if ($promo->discount_kind === Promo::KIND_BUY_X_GET_Y) {
            return $this->calculateManualBogoDiscount($promo, $cartItems);
        }

        if ($promo->discount_kind === Promo::KIND_QTY_BREAK) {
            return $this->calculateManualQtyBreakDiscount($promo, $cartItems);
        }

        // PENTING: diskon nominal/persen manual (mis. kode "PANJAT17") harus
        // dihitung dari harga item yang COCOK TARGET-nya saja (produk/kategori
        // tertentu), bukan dari total seluruh keranjang. Sebelumnya kode ini
        // langsung pakai $subtotalAfterAutoPromo (total keranjang) tanpa
        // filter sama sekali — jadi kalau target_type=kategori, item di luar
        // kategori itu ikut kepotong juga. Sekarang di-scope dulu ke item
        // yang cocok, sama seperti BOGO & grosir bertingkat manual di atas.
        if ($promo->target_type === Promo::TARGET_ALL) {
            $eligibleItems = collect($cartItems);
        } else {
            $eligibleItems = collect($cartItems)->filter(function ($item) use ($promo) {
                return match ($promo->target_type) {
                    Promo::TARGET_PRODUCT => (int) data_get($item, 'product_id') === (int) $promo->product_id,
                    Promo::TARGET_CATEGORY => (int) data_get($item, 'category_id') === (int) $promo->category_id,
                    default => false,
                };
            })->values();
        }

        if ($eligibleItems->isEmpty()) {
            return ['discount' => 0, 'free_items' => []];
        }

        // Sisa nilai per baris keranjang (cart_id => rupiah) setelah voucher-
        // voucher sebelumnya dipotong. Dikirim by-reference dari LoyaltyService
        // supaya voucher ke-2, ke-3, dst dalam satu transaksi menghitung dari
        // SISA barang itu (kali sisa berturut-turut / successive discount),
        // bukan dari harga awal barang lagi. Kalau null (dipanggil sendirian,
        // misal validasi/preview 1 voucher), dibuat sekali pakai dari $cartItems.
        // INI YANG DULU HILANG — sebelumnya tiap voucher menghitung ulang dari
        // harga ASLI barang, jadi 2 voucher besar (mis. 81% + 45%) di barang
        // yang sama bisa total >100% dan bikin barang itu Rp0 tanpa sengaja.
        if ($remainingByCartId === null) {
            $remainingByCartId = collect($cartItems)->mapWithKeys(fn ($item) => [
                (int) data_get($item, 'cart_id') => (int) data_get($item, 'line_total', data_get($item, 'price', 0)),
            ])->all();
        }

        // Mode "Total Transaksi": diskon dihitung SEKALIGUS dari sisa
        // subtotal semua barang yang eligible (bukan nempel per barang
        // satu-satu kayak mode 'per_item'). applies_to_qty diabaikan di
        // mode ini karena nggak relevan (nggak ada konsep "maks N barang").
        // Kalau kode ini dipanggil berkali-kali dalam 1 transaksi (dimenangkan
        // >1x, applied lewat counter di kasir), tiap panggilan menghitung
        // dari SISA subtotal yang sudah dikurangi panggilan sebelumnya —
        // jadi otomatis compounding, sama polanya kayak 2 kode beda ditumpuk.
        if ($promo->isTotalTransactionScope()) {
            $eligibleRemainingTotal = $eligibleItems->sum(function ($item) use ($remainingByCartId) {
                $cartId = (int) data_get($item, 'cart_id');

                return max(0, (int) ($remainingByCartId[$cartId] ?? data_get($item, 'line_total', data_get($item, 'price', 0))));
            });

            if ($eligibleRemainingTotal <= 0) {
                return ['discount' => 0, 'free_items' => []];
            }

            $discount = match ($promo->discount_type) {
                Promo::TYPE_PERCENTAGE => (int) round($eligibleRemainingTotal * ((float) $promo->discount_value / 100)),
                Promo::TYPE_FIXED_AMOUNT => min($eligibleRemainingTotal, (int) round((float) $promo->discount_value)),
                default => 0,
            };
            $discount = min($eligibleRemainingTotal, max(0, $discount));

            // Sama kayak mode per_item: default nggak boleh sampai Rp0,
            // kecuali allow_zero_price sengaja dinyalain.
            if (! $promo->allow_zero_price && $discount >= $eligibleRemainingTotal) {
                $discount = $eligibleRemainingTotal - 1;
            }

            if ($discount <= 0) {
                return ['discount' => 0, 'free_items' => []];
            }

            // Sebarin potongan ke tiap baris keranjang eligible secara
            // proporsional (biar $remainingByCartId tetap akurat buat
            // voucher/kemenangan berikutnya yang menghitung dari SISA barang).
            $distributed = 0;
            $eligibleItems->each(function ($item) use (&$distributed, $discount, $eligibleRemainingTotal, &$remainingByCartId) {
                $cartId = (int) data_get($item, 'cart_id');
                $remainingLineValue = max(0, (int) ($remainingByCartId[$cartId] ?? data_get($item, 'line_total', data_get($item, 'price', 0))));

                if ($remainingLineValue <= 0) {
                    return;
                }

                $share = (int) round($discount * ($remainingLineValue / $eligibleRemainingTotal));
                $share = min($share, $remainingLineValue);
                $remainingByCartId[$cartId] = max(0, $remainingLineValue - $share);
                $distributed += $share;
            });

            return [
                'discount' => min($subtotalAfterAutoPromo, max(0, $distributed)),
                'free_items' => [],
            ];
        }

        $applyToQty = (int) ($promo->applies_to_qty ?? 0);
        $sorted = $applyToQty > 0
            ? $eligibleItems->sortBy(fn ($item) => (int) data_get($item, 'effective_unit_price', data_get($item, 'base_unit_price', 0)))
            : $eligibleItems;

        $remainingQtyToTouch = $applyToQty > 0 ? $applyToQty : null;
        $discount = 0;

        foreach ($sorted as $item) {
            if ($remainingQtyToTouch !== null && $remainingQtyToTouch <= 0) {
                break;
            }

            $cartId = (int) data_get($item, 'cart_id');
            $qty = max(1, (int) data_get($item, 'qty', 1));
            $remainingLineValue = (int) ($remainingByCartId[$cartId] ?? data_get($item, 'line_total', data_get($item, 'price', 0)));

            if ($remainingLineValue <= 0) {
                continue;
            }

            $qtyAvailableHere = $remainingQtyToTouch === null ? $qty : min($qty, max(0, $remainingQtyToTouch));
            if ($qtyAvailableHere <= 0) {
                continue;
            }

            // Bagian dari sisa nilai baris ini yang kena jatah voucher —
            // proporsional kalau cuma sebagian qty di baris itu yang kepakai.
            $touchedValue = $qtyAvailableHere >= $qty
                ? $remainingLineValue
                : (int) round($remainingLineValue * ($qtyAvailableHere / $qty));
            $touchedValue = min($touchedValue, $remainingLineValue);

            $lineDiscount = match ($promo->discount_type) {
                Promo::TYPE_PERCENTAGE => (int) round($touchedValue * ((float) $promo->discount_value / 100)),
                Promo::TYPE_FIXED_AMOUNT => min($touchedValue, (int) round((float) $promo->discount_value) * $qtyAvailableHere),
                default => 0,
            };
            $lineDiscount = min($touchedValue, max(0, $lineDiscount));

            // Mode "jangan sampai gratis" (default): kalau potongan ini bakal
            // ngenolin harga barang, sisain minimal Rp1 — supaya walau ada 2+
            // voucher gede numpuk (mis. 81% + 45%) di barang yang sama, barang
            // itu tetap ada nilainya. allow_zero_price=true kalau memang
            // sengaja dibuat promo "gratis 1 barang".
            if (! $promo->allow_zero_price && $lineDiscount >= $touchedValue && $touchedValue > 0) {
                $lineDiscount = $touchedValue - 1;
            }

            if ($lineDiscount <= 0) {
                continue;
            }

            $discount += $lineDiscount;
            $remainingByCartId[$cartId] = max(0, $remainingLineValue - $lineDiscount);

            if ($remainingQtyToTouch !== null) {
                $remainingQtyToTouch -= $qtyAvailableHere;
            }
        }

        return [
            'discount' => min($subtotalAfterAutoPromo, max(0, $discount)),
            'free_items' => [],
        ];
    }

    /**
     * BOGO manual — mendukung mode generik ("semua produk", tanpa pilih
     * produk sama sekali, lewat target_type=all) maupun produk/kategori
     * tertentu (target_type=product/category). Produk gratis default sama
     * dengan produk yang dibeli (unit termurah), kecuali get_product_id diisi.
     */
    private function calculateManualBogoDiscount(Promo $promo, array $cartItems): array
    {
        $buyQty = max(1, (int) $promo->buy_qty);
        $getQty = max(1, (int) $promo->get_qty);
        $percent = max(0, min(100, (float) ($promo->get_discount_percent ?? 100)));

        $eligibleItems = collect($cartItems)->filter(function ($item) use ($promo) {
            return match ($promo->target_type) {
                Promo::TARGET_PRODUCT => (int) data_get($item, 'product_id') === (int) $promo->product_id,
                Promo::TARGET_CATEGORY => (int) data_get($item, 'category_id') === (int) $promo->category_id,
                default => true, // TARGET_ALL = berlaku semua produk di keranjang
            };
        })->values();

        $totalBuyQty = (int) $eligibleItems->sum(fn ($item) => (int) data_get($item, 'qty', 0));
        if ($totalBuyQty < $buyQty) {
            return ['discount' => 0, 'free_items' => []];
        }

        $sets = intdiv($totalBuyQty, $buyQty);
        $freeUnitsAvailable = $sets * $getQty;

        $sourceItems = $promo->get_product_id
            ? collect($cartItems)->filter(fn ($item) => (int) data_get($item, 'product_id') === (int) $promo->get_product_id)
            : $eligibleItems;

        if ($sourceItems->isEmpty()) {
            return ['discount' => 0, 'free_items' => []];
        }

        $sorted = $sourceItems->sortBy(fn ($item) => (int) data_get($item, 'effective_unit_price', data_get($item, 'base_unit_price', 0)));

        $remainingFree = $freeUnitsAvailable;
        $discount = 0;
        $freeItems = [];

        foreach ($sorted as $item) {
            if ($remainingFree <= 0) {
                break;
            }

            $qty = (int) data_get($item, 'qty', 0);
            $unitPrice = (int) data_get($item, 'effective_unit_price', data_get($item, 'base_unit_price', 0));
            $take = min($qty, $remainingFree);

            if ($take <= 0) {
                continue;
            }

            $lineDiscount = (int) round($unitPrice * $take * ($percent / 100));
            $discount += $lineDiscount;
            $remainingFree -= $take;

            $freeItems[] = [
                'product_id' => data_get($item, 'product_id'),
                'product_title' => data_get($item, 'product_title'),
                'quantity' => $take,
                'discount_percent' => $percent,
                'discount_total' => $lineDiscount,
            ];
        }

        return ['discount' => max(0, $discount), 'free_items' => $freeItems];
    }

    /**
     * Grosir bertingkat manual — sama seperti qty_break otomatis, tapi
     * dihitung dari TOTAL qty di keranjang (atau qty produk/kategori
     * tertentu jika target_type bukan "all"), dan baru berlaku setelah
     * kasir aktifkan tombolnya.
     */
    private function calculateManualQtyBreakDiscount(Promo $promo, array $cartItems): array
    {
        $eligibleItems = collect($cartItems)->filter(function ($item) use ($promo) {
            return match ($promo->target_type) {
                Promo::TARGET_PRODUCT => (int) data_get($item, 'product_id') === (int) $promo->product_id,
                Promo::TARGET_CATEGORY => (int) data_get($item, 'category_id') === (int) $promo->category_id,
                default => true,
            };
        })->values();

        $totalQty = (int) $eligibleItems->sum(fn ($item) => (int) data_get($item, 'qty', 0));
        $eligibleSubtotal = (int) $eligibleItems->sum(fn ($item) => (int) data_get(
            $item,
            'line_total',
            (int) data_get($item, 'effective_unit_price', 0) * (int) data_get($item, 'qty', 0)
        ));

        if ($totalQty <= 0 || $eligibleSubtotal <= 0) {
            return ['discount' => 0, 'free_items' => []];
        }

        $break = $promo->qtyBreaks
            ->filter(fn (PromoQtyBreak $b) => $totalQty >= (int) $b->min_qty)
            ->sortByDesc('min_qty')
            ->first();

        if (! $break) {
            return ['discount' => 0, 'free_items' => []];
        }

        $discount = match ($break->discount_type) {
            'percentage' => (int) round($eligibleSubtotal * ((float) $break->discount_value / 100)),
            'fixed_amount' => (int) round((float) $break->discount_value) * $totalQty,
            'fixed_price' => max(0, $eligibleSubtotal - ((int) round((float) $break->discount_value) * $totalQty)),
            default => 0,
        };

        return ['discount' => min($eligibleSubtotal, max(0, $discount)), 'free_items' => []];
    }

    public function serializeManualPromo(Promo $promo, int $previewDiscount = 0): array
    {
        return [
            'id' => $promo->id,
            'code' => $promo->code,
            'name' => $promo->name,
            'voucher_type' => $promo->customer_id ? 'personal' : match ($promo->customer_scope) {
                Promo::SCOPE_MEMBER => 'member_only',
                Promo::SCOPE_WALK_IN => 'non_member_only',
                default => 'all',
            },
            'discount_kind' => $promo->discount_kind,
            'discount_type' => $promo->discount_type,
            'discount_kind_label' => $promo->discountKindLabel(),
            'discount_value' => (float) $promo->discount_value,
            'discount_scope' => $promo->discount_scope ?? Promo::DISCOUNT_SCOPE_PER_ITEM,
            'applies_to_qty' => $promo->applies_to_qty,
            'minimum_order' => (int) $promo->minimum_order,
            'target_type' => $promo->target_type,
            'buy_qty' => $promo->buy_qty,
            'get_qty' => $promo->get_qty,
            'get_discount_percent' => $promo->get_discount_percent,
            'product_name' => $promo->product?->title,
            'get_product_name' => $promo->getProduct?->title,
            'category_name' => $promo->category?->name,
            'max_uses' => $promo->max_uses,
            'used_count' => $promo->used_count,
            'starts_at' => optional($promo->starts_at)?->toIso8601String(),
            'ends_at' => optional($promo->ends_at)?->toIso8601String(),
            'used_at' => optional($promo->used_at)?->toIso8601String(),
            'status' => $promo->currentStatusLabel(),
            'preview_discount' => $previewDiscount,
        ];
    }
}