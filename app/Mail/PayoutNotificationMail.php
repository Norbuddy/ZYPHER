<?php

namespace App\Mail;

use App\Models\OrderShop;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayoutNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public OrderShop $orderShop)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You've received a payout on Zypher",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payout-notification',
            with: [
                'shopName' => $this->orderShop->team->name,
                'amount' => number_format((float) $this->orderShop->seller_payout, 2),
                'orderId' => $this->orderShop->order_id,
            ],
        );
    }
}