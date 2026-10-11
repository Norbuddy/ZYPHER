<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewReview extends Notification
{
    use Queueable;

    public function __construct(protected Review $review) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'new_review',
            'review_id' => $this->review->id,
            'message' => "New {$this->review->rating}-star review on your product.",
        ];
    }
}
