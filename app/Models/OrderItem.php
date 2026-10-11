<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    protected $fillable = [
        'order_shop_id', 'product_variant_id', 'product_name', 'variant_label',
        'sku', 'price_at_purchase', 'quantity', 'applied_voucher_id', 'item_discount',
    ];

    protected $casts = [
        'price_at_purchase' => 'decimal:2',
        'item_discount' => 'decimal:2',
    ];

    public function orderShop(): BelongsTo
    {
        return $this->belongsTo(OrderShop::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function appliedVoucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'applied_voucher_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function lineTotal(): float
    {
        return ((float) $this->price_at_purchase * $this->quantity) - (float) $this->item_discount;
    }
}
