<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    use HasFactory;

    // Cara aktivasi — pengganti konsep "Pricing Rule (otomatis) vs Voucher (manual)"
    public const ACTIVATION_AUTO = 'auto';

    public const ACTIVATION_MANUAL = 'manual';

    // Jenis diskon — sama untuk auto maupun manual
    public const KIND_STANDARD_DISCOUNT = 'standard_discount';

    public const KIND_QTY_BREAK = 'qty_break';

    public const KIND_BUNDLE_PRICE = 'bundle_price';

    public const KIND_BUY_X_GET_Y = 'buy_x_get_y';

    // Target produk/kategori
    public const TARGET_ALL = 'all';

    public const TARGET_PRODUCT = 'product';

    public const TARGET_CATEGORY = 'category';

    // Scope pelanggan
    public const SCOPE_ALL = 'all';

    public const SCOPE_WALK_IN = 'walk_in'; // tanpa pelanggan terdaftar / non-member

    public const SCOPE_REGISTERED = 'registered'; // pelanggan terdaftar (member atau bukan)

    public const SCOPE_MEMBER = 'member'; // khusus loyalty member

    // Mode batas pemakaian voucher manual per pelanggan
    public const USAGE_LIMIT_PER_TRANSACTION = 'per_transaction'; // default, boleh berkali-kali

    public const USAGE_LIMIT_PER_CUSTOMER = 'per_customer'; // 1x seumur hidup per pelanggan

    // Cakupan diskon (untuk standard_discount) — lihat migration
    // add_discount_scope_to_promos_table untuk penjelasan lengkap.
    public const DISCOUNT_SCOPE_PER_ITEM = 'per_item';

    public const DISCOUNT_SCOPE_TOTAL_TRANSACTION = 'total_transaction';

    // Tipe nilai diskon (untuk standard_discount & qty_break)
    public const TYPE_PERCENTAGE = 'percentage';

    public const TYPE_FIXED_AMOUNT = 'fixed_amount';

    public const TYPE_FIXED_PRICE = 'fixed_price';

    protected $fillable = [
        'name',
        'code',
        'notes',
        'is_active',
        'priority',
        'activation_mode',
        'discount_kind',
        'discount_type',
        'discount_value',
        'applies_to_qty',
        'discount_scope',
        'allow_zero_price',
        'usage_limit_mode',
        'target_type',
        'product_id',
        'category_id',
        'customer_scope',
        'payment_method_restriction',
        'eligible_loyalty_tiers',
        'customer_id',
        'max_uses',
        'used_count',
        'is_used',
        'used_at',
        'used_transaction_id',
        'minimum_order',
        'buy_qty',
        'get_qty',
        'get_product_id',
        'get_discount_percent',
        'preview_quantity_multiplier',
        'starts_at',
        'ends_at',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'product_id' => 'integer',
        'category_id' => 'integer',
        'eligible_loyalty_tiers' => 'array',
        'customer_id' => 'integer',
        'max_uses' => 'integer',
        'used_count' => 'integer',
        'is_used' => 'boolean',
        'used_at' => 'datetime',
        'used_transaction_id' => 'integer',
        'discount_value' => 'float',
        'applies_to_qty' => 'integer',
        'discount_scope' => 'string',
        'allow_zero_price' => 'boolean',
        'minimum_order' => 'integer',
        'buy_qty' => 'integer',
        'get_qty' => 'integer',
        'get_product_id' => 'integer',
        'get_discount_percent' => 'integer',
        'preview_quantity_multiplier' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'created_by' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function getProduct()
    {
        return $this->belongsTo(Product::class, 'get_product_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function usedTransaction()
    {
        return $this->belongsTo(Transaction::class, 'used_transaction_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function qtyBreaks()
    {
        return $this->hasMany(PromoQtyBreak::class)
            ->orderByDesc('min_qty')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function bundleItems()
    {
        return $this->hasMany(PromoBundleItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function buyGetItems()
    {
        return $this->hasMany(PromoBuyGetItem::class)
            ->orderBy('role')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function isAuto(): bool
    {
        return $this->activation_mode === self::ACTIVATION_AUTO;
    }

    public function isManual(): bool
    {
        return $this->activation_mode === self::ACTIVATION_MANUAL;
    }

    public function isBogo(): bool
    {
        return $this->discount_kind === self::KIND_BUY_X_GET_Y;
    }

    /**
     * true kalau diskon dihitung sekaligus dari sisa subtotal barang yang
     * eligible (1x per pemakaian kode), bukan nempel per barang satu-satu.
     * Default 'per_item' kalau kolomnya kosong/null (jaga-jaga data lama).
     */
    public function isTotalTransactionScope(): bool
    {
        return ($this->discount_scope ?? self::DISCOUNT_SCOPE_PER_ITEM) === self::DISCOUNT_SCOPE_TOTAL_TRANSACTION;
    }

    public function isPersonal(): bool
    {
        return $this->isManual() && $this->customer_id !== null;
    }

    public function isScheduled(): bool
    {
        return $this->starts_at && $this->starts_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    /**
     * Cek apakah promo manual ini masih boleh dipakai (belum mencapai
     * kuota). Voucher personal sekali pakai dicek lewat is_used.
     */
    public function usageAvailable(): bool
    {
        if ($this->isPersonal()) {
            return ! $this->is_used;
        }

        if ($this->max_uses === null) {
            return true;
        }

        return $this->used_count < $this->max_uses;
    }

    /**
     * Cek kecocokan scope pelanggan untuk promo ini.
     */
    public function matchesCustomerScope(?Customer $customer): bool
    {
        // Voucher personal: hanya untuk 1 pelanggan tertentu.
        if ($this->isPersonal()) {
            return $customer && (int) $this->customer_id === (int) $customer->id;
        }

        $isMember = (bool) ($customer?->is_loyalty_member ?? false);
        $tiers = $this->eligible_loyalty_tiers ?? [];

        return match ($this->customer_scope) {
            self::SCOPE_ALL => true,
            self::SCOPE_WALK_IN => ! $customer,
            self::SCOPE_REGISTERED => (bool) $customer,
            self::SCOPE_MEMBER => $isMember && (empty($tiers) || in_array($customer?->loyalty_tier, $tiers, true)),
            default => false,
        };
    }

    /**
     * Cek kecocokan metode bayar untuk promo ini. NULL = tidak dibatasi,
     * berlaku untuk semua metode bayar. "cash" dianggap satu-satunya metode
     * tunai murni — semua yang lain (QRIS, transfer, dual, dst) dianggap
     * cashless.
     */
    public function matchesPaymentMethod(?string $paymentMethod): bool
    {
        if (empty($this->payment_method_restriction)) {
            return true;
        }

        // Kalau metode bayar belum dipilih kasir (misal masih preview awal),
        // jangan langsung nolak — biar promo tetap kelihatan di daftar, baru
        // ditolak beneran begitu payment_method sudah pasti dipilih.
        if ($paymentMethod === null) {
            return true;
        }

        $isCash = $paymentMethod === 'cash';

        return match ($this->payment_method_restriction) {
            'cashless_only' => ! $isCash,
            'cash_only' => $isCash,
            default => true,
        };
    }

    public function currentStatusLabel(): string
    {
        if ($this->isPersonal() && $this->is_used) {
            return 'used';
        }

        if (! $this->is_active) {
            return 'inactive';
        }

        if ($this->isScheduled()) {
            return 'scheduled';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        if ($this->isManual() && ! $this->isPersonal() && $this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return 'used';
        }

        return 'active';
    }

    public function discountKindLabel(): string
    {
        return match ($this->discount_kind) {
            self::KIND_STANDARD_DISCOUNT => $this->discount_type === self::TYPE_PERCENTAGE
                ? rtrim(rtrim(number_format((float) $this->discount_value, 2, '.', ''), '0'), '.').'% OFF'
                : 'Hemat Rp '.number_format((float) $this->discount_value, 0, ',', '.'),
            self::KIND_QTY_BREAK => 'Diskon Grosir Bertingkat',
            self::KIND_BUNDLE_PRICE => 'Bundle Harga',
            self::KIND_BUY_X_GET_Y => 'Beli '.(int) $this->buy_qty.' Gratis '.(int) $this->get_qty,
            default => $this->name,
        };
    }
}
