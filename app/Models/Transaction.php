<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $casts = [
        'cashier_id' => 'integer',
        'cashier_shift_id' => 'integer',
        'customer_id' => 'integer',
        'cash' => 'integer',
        'change' => 'integer',
        'dual_cash' => 'integer',
        'dual_qris' => 'integer',
        'discount' => 'integer',
        'loyalty_points_earned' => 'integer',
        'loyalty_points_redeemed' => 'integer',
        'loyalty_discount_total' => 'integer',
        'customer_voucher_discount' => 'integer',
        'item_manual_discount_total' => 'integer',
        'shipping_cost' => 'integer',
        'grand_total' => 'integer',
        'bank_account_id' => 'integer',
    ];

    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'cashier_id',
        'cashier_shift_id',
        'customer_id',
        'invoice',
        'cash',
        'change',
        'dual_cash',
        'dual_qris',
        'discount',
        'loyalty_points_earned',
        'loyalty_points_redeemed',
        'loyalty_discount_total',
        'customer_voucher_discount',
        'item_manual_discount_total',
        'customer_voucher_code',
        'customer_voucher_name',
        'shipping_cost',
        'grand_total',
        'payment_method',
        'payment_status',
        'payment_reference',
        'payment_url',
        'bank_account_id',
    ];

    /**
     * details
     *
     * @return void
     */
    /**
     * Label metode pembayaran untuk struk (huruf besar).
     * Dipakai semua output cetak: struk 58mm, 80mm (PDF) dan cetak langsung
     * ESC/POS (QZ Tray / RawBT), supaya labelnya selalu sama.
     */
    public static function receiptPaymentLabel(?string $method): string
    {
        $method = strtolower((string) ($method ?: 'cash'));

        return match ($method) {
            'cash' => 'TUNAI',
            'bank_transfer' => 'TRANSFER BANK',
            'qris_static', 'midtrans', 'xendit', 'doku', 'flip' => 'QRIS',
            'pay_later' => 'PIUTANG',
            'dual_cash_qris' => 'TUNAI+QRIS',
            default => strtoupper(str_replace('_', ' ', $method)),
        };
    }

    public function details()
    {
        return $this->hasMany(TransactionDetail::class);
    }

    /**
     * customer
     *
     * @return void
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * cashier
     *
     * @return void
     */
    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function cashierShift()
    {
        return $this->belongsTo(CashierShift::class);
    }

    /**
     * bankAccount
     *
     * @return void
     */
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * profits
     *
     * @return void
     */
    public function profits()
    {
        return $this->hasMany(Profit::class);
    }

    public function receivable()
    {
        return $this->hasOne(Receivable::class);
    }

    public function salesReturns()
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function usedPromo()
    {
        return $this->hasOne(Promo::class, 'used_transaction_id');
    }

    public function campaignLogs()
    {
        return $this->hasMany(CustomerCampaignLog::class);
    }

    /**
     * createdAt
     */
    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value)->setTimezone('Asia/Jakarta')->format('d-M-Y H:i:s'),
        );
    }
}