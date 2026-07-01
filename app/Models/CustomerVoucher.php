<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerVoucher extends Model
{
    use HasFactory;

    public const TYPE_FIXED_AMOUNT  = 'fixed_amount';
    public const TYPE_PERCENTAGE    = 'percentage';

    // voucher_type
    public const VOUCHER_PERSONAL       = 'personal';      // assign ke 1 pelanggan
    public const VOUCHER_ALL            = 'all';           // semua pelanggan
    public const VOUCHER_MEMBER_ONLY    = 'member_only';   // khusus loyalty member
    public const VOUCHER_NON_MEMBER     = 'non_member_only'; // khusus non-member

    protected $fillable = [
        'customer_id',
        'voucher_type',
        'max_uses',
        'used_count',
        'code',
        'name',
        'discount_type',
        'discount_value',
        'minimum_order',
        'is_active',
        'is_used',
        'starts_at',
        'expires_at',
        'used_at',
        'used_transaction_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'max_uses' => 'integer',
        'used_count' => 'integer',
        'discount_value' => 'float',
        'minimum_order' => 'integer',
        'is_active' => 'boolean',
        'is_used' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'used_transaction_id' => 'integer',
        'created_by' => 'integer',
    ];

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

    public function isGeneral(): bool
    {
        return $this->voucher_type !== self::VOUCHER_PERSONAL;
    }

    public function isEligibleForCustomer(?\App\Models\Customer $customer): bool
    {
        if (! $customer) return false;

        return match ($this->voucher_type) {
            self::VOUCHER_PERSONAL    => (int) $this->customer_id === (int) $customer->id,
            self::VOUCHER_ALL         => true,
            self::VOUCHER_MEMBER_ONLY => (bool) $customer->is_loyalty_member,
            self::VOUCHER_NON_MEMBER  => ! $customer->is_loyalty_member,
            default => false,
        };
    }

    public function currentStatusLabel(): string
    {
        if ($this->is_used) {
            return 'used';
        }

        if (! $this->is_active) {
            return 'inactive';
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'scheduled';
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'expired';
        }

        return 'active';
    }
}