<?php

namespace App\Notifications;

use App\Models\OrderShop;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DisputeOpened extends Notification
{
    use Queueable;

    public function __construct(protected OrderShop $orderShop) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'dispute_opened',
            'order_shop_id' => $this->orderShop->id,
            'message' => "A dispute was opened on order #{$this->orderShop->id}.",
        ];
    }
}
