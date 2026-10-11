<?php

namespace App\Notifications;

use App\Models\OrderShop;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderShipped extends Notification
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
            'type' => 'order_shipped',
            'order_shop_id' => $this->orderShop->id,
            'message' => "Your order has shipped via {$this->orderShop->courier_name} (Tracking: {$this->orderShop->tracking_number})",
        ];
    }
}
