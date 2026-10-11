<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'branch_id', 'sku', 'label', 'price', 'stock', 'reserved_stock', 'stock_update_status', 'image_path', 'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductOptionValue::class,
            'product_variant_values'
        );
    }

    /** Sellable stock = physical stock minus whatever is currently locked in pending checkouts. */
    public function availableStock(): int
    {
        return max(0, $this->stock - $this->reserved_stock);
    }

    public function hasStockFor(int $quantity): bool
    {
        return $this->availableStock() >= $quantity;
    }
}
