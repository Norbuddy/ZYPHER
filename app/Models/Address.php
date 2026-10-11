<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = [
    'user_id',
    'full_name',
    'phone',
    'line1',
    'line2',
    'city',
    'province',
    'postal_code',
    'latitude',
    'longitude',
    'is_default',
];

    protected $casts = [
    'is_default' => 'boolean',
    'latitude' => 'decimal:7',
    'longitude' => 'decimal:7',
];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
