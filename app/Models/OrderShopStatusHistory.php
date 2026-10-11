<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderShopStatusHistory extends Model
{
    protected $fillable = ['order_shop_id', 'from_status', 'to_status', 'changed_by', 'note'];

    public function orderShop(): BelongsTo
    {
        return $this->belongsTo(OrderShop::class);
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
