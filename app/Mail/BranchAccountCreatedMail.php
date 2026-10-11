<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class BranchAccountCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $temporaryPassword,
        public string $branchName,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Zypher branch account is ready');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.branch-account-created',
            with: ['loginUrl' => route('login')],
        );
    }
}