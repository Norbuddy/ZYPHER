<?php

namespace App\Notifications;

use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ShopApproved extends Notification
{
    use Queueable;

    public function __construct(protected Team $team) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'shop_approved',
            'team_id' => $this->team->id,
            'message' => "Your shop '{$this->team->name}' has been approved and is now live!",
        ];
    }
}
