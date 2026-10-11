<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLog extends Model
{
    protected $fillable = ['seller_id', 'product_id', 'branch_id', 'type', 'quantity', 'source_type', 'source_id', 'note'];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'seller_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}