<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierApplication extends Model
{
    protected $fillable = [
        'user_id',
        'business_name',
        'business_email',
        'contact_number',
        'business_address',
        'reason',
        'permit_path',
        'status',
        'admin_note',
        'reviewed_at',
        'reviewed_by',
    ];

    public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
}