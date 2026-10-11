<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PopupAd extends Model
{
    protected $fillable = [
        'title',
        'message',
        'image_path',
        'button_text',
        'button_link',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Active *and* currently within its optional scheduling window.
     */
    public function scopeCurrentlyLive($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }
}
