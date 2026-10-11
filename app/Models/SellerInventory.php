<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerInventory extends Model
{
    protected $fillable = ['seller_id', 'product_id', 'branch_id', 'quantity', 'source_type', 'source_id'];

    protected $casts = ['quantity' => 'integer'];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'seller_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}