<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = ['cart_id', 'product_variant_id', 'quantity', 'applied_voucher_id'];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function appliedVoucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'applied_voucher_id');
    }

    public function lineTotal(): float
    {
        return (float) $this->variant->price * $this->quantity;
    }
}
