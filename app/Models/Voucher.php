<?php

namespace App\Models;

use App\Enums\VoucherDiscountType;
use App\Enums\VoucherOwnerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    protected $fillable = [
        'owner_type', 'team_id', 'code', 'discount_type', 'discount_value',
        'min_spend', 'max_discount_amount', 'usage_limit_total', 'usage_limit_per_user',
        'starts_at', 'expires_at', 'status',
    ];

    protected $casts = [
        'owner_type' => VoucherOwnerType::class,
        'discount_type' => VoucherDiscountType::class,
        'discount_value' => 'decimal:2',
        'min_spend' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(VoucherUsage::class);
    }

    public function isValidNow(): bool
    {
        if ($this->status !== 'active') return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->usage_limit_total && $this->usages()->count() >= $this->usage_limit_total) return false;

        return true;
    }

    public function calculateDiscount(float $subtotal): float
    {
        if ($subtotal < (float) $this->min_spend) return 0;

        $discount = $this->discount_type === VoucherDiscountType::Percentage
            ? $subtotal * ((float) $this->discount_value / 100)
            : (float) $this->discount_value;

        if ($this->max_discount_amount) {
            $discount = min($discount, (float) $this->max_discount_amount);
        }

        return min($discount, $subtotal);
    }
}
