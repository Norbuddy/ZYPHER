<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierOrder extends Model
{
    protected $fillable = [
        'supplier_id',
        'team_id',
        'order_number',
        'total_amount',
        'status',
        'items',
        'tracking_number',
        'shipment_status',
        'shipped_at',
        'delivered_at',
        'payment_status',
        'payment_reference',
        'payment_method',
        'paid_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'items' => 'array',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
