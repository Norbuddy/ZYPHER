<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = ['user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /** Group cart items by shop - mirrors how checkout will split into order_shops. */
    public function itemsGroupedByShop()
    {
        return $this->items()
    ->with([
        'variant.product.team',
        'variant.product.images',
        'variant.optionValues.option',
    ])
    ->get()
    ->groupBy(fn (CartItem $item) => $item->variant->product->team_id);
    }
}
