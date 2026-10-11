<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'user_id', 'address_id', 'total_amount',
        'platform_voucher_discount', 'platform_voucher_id',
        'payment_status', 'payment_reference', 'paid_at',
    ];

    protected $casts = [
        'payment_status' => PaymentStatus::class,
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function platformVoucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'platform_voucher_id');
    }

    public function orderShops(): HasMany
    {
        return $this->hasMany(OrderShop::class);
    }

    public static function generateOrderNumber(): string
    {
        return 'ZY-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
}
