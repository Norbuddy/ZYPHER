<?php

namespace App\Notifications;

use App\Models\OrderShop;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OrderPlaced extends Notification
{
    use Queueable;

    public function __construct(protected OrderShop $orderShop) {}

    public function via($notifiable): array
    {
        return ['database']; // add 'mail' if you want email too
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'order_placed',
            'order_shop_id' => $this->orderShop->id,
            'message' => "New order received - ₱{$this->orderShop->subtotal}",
        ];
    }
}
