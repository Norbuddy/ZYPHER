<?php

namespace App\Models;

use App\Models\OrderShop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'team_id',
        'order_shop_id',
        'is_support',
        'support_token',
        'support_subject',
        'support_category',
        'support_status',
        'closed_at',
        'last_message_at',
    ];

    protected $casts = [
        'is_support' => 'boolean',
        'last_message_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Conversation $conversation) {
            if ($conversation->is_support && empty($conversation->support_token)) {
                $conversation->support_token = 'SUP-' . strtoupper(Str::random(8));
            }
            if ($conversation->is_support && empty($conversation->support_status)) {
                $conversation->support_status = 'waiting_support';
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function orderShop(): BelongsTo
    {
        return $this->belongsTo(OrderShop::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function scopeSupport($query)
    {
        return $query->where('is_support', true);
    }

    public function scopeShopChats($query)
    {
        return $query->where('is_support', false);
    }

    public function scopeOpen($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('support_status')
                ->orWhereNotIn('support_status', ['closed', 'resolved']);
        });
    }

    public function isClosed(): bool
    {
        return in_array($this->support_status, ['closed', 'resolved'], true)
            || $this->closed_at !== null;
    }

    public function close(): void
    {
        $this->update([
            'support_status' => 'closed',
            'closed_at' => now(),
        ]);
    }
}
