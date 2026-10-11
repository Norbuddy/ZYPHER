<?php

namespace App\Models;

use App\Enums\OrderShopStatus;
use App\Enums\PayoutStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderShop extends Model
{
    protected $fillable = [
        'order_id', 'team_id', 'status', 'subtotal', 'shipping_fee',
        'shop_voucher_discount', 'shop_voucher_id', 'commission_rate',
        'platform_fee', 'seller_payout', 'payout_status',
        'courier_name', 'tracking_number', 'shipping_method',
        'processing_at', 'shipped_at', 'completed_at', 'cancelled_at',
        'store_address', 'store_latitude', 'store_longitude', 'delivery_distance_km', 'estimated_delivery_minutes',
    ];

    protected $casts = [
        'status' => OrderShopStatus::class,
        'payout_status' => PayoutStatus::class,
        'subtotal' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'shop_voucher_discount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'seller_payout' => 'decimal:2',
        'processing_at' => 'datetime',
        'shipped_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'store_latitude' => 'decimal:7',
        'store_longitude' => 'decimal:7',
        'delivery_distance_km' => 'decimal:2',
        'estimated_delivery_minutes' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function shopVoucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'shop_voucher_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderShopStatusHistory::class);
    }

    public function conversation(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Move to a new status, stamping the relevant timestamp and logging history.
     * Centralizing this here keeps controllers from hand-rolling status transitions.
     */
    public function transitionTo(OrderShopStatus $newStatus, ?int $changedBy = null, ?string $note = null): void
    {
        $from = $this->status;

        $timestampColumn = match ($newStatus) {
            OrderShopStatus::Processing => 'processing_at',
            OrderShopStatus::Shipped => 'shipped_at',
            OrderShopStatus::Completed => 'completed_at',
            OrderShopStatus::Cancelled => 'cancelled_at',
            default => null,
        };

        $this->status = $newStatus;
        if ($timestampColumn) {
            $this->{$timestampColumn} = now();
        }

        // Only fire completion side-effects once, the moment it genuinely becomes Completed
        // (guards against re-saving/duplicate calls double-counting sold_count).
        $justPaidOut = false;
        if ($newStatus === OrderShopStatus::Completed && $from !== OrderShopStatus::Completed) {
            $this->payout_status = PayoutStatus::PaidOut;
            $justPaidOut = true;
        }

        $this->save();

        if ($justPaidOut) {
            $owner = $this->team?->owner;
            if ($owner?->email) {
                \Illuminate\Support\Facades\Mail::to($owner->email)->send(new \App\Mail\PayoutNotificationMail($this));
            }
        }

        if ($newStatus === OrderShopStatus::Completed && $from !== OrderShopStatus::Completed) {
            foreach ($this->items()->with('variant.product')->get() as $item) {
                $product = $item->variant?->product;
                if ($product) {
                    $product->increment('sold_count', $item->quantity);
                }
            }
        }

        $this->statusHistories()->create([
            'from_status' => $from?->value,
            'to_status' => $newStatus->value,
            'changed_by' => $changedBy,
            'note' => $note,
        ]);
    }
}
