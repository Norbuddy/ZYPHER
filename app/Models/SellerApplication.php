<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerApplication extends Model
{
    protected $fillable = [
        'user_id',
        'business_name',
        'business_email',
        'contact_number',
        'business_location',
        'permit_path',
        'product_legitimation_path',
        'requested_category_ids',
        'reason',
        'status',
        'admin_note',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'requested_category_ids' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}