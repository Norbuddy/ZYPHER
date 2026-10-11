<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportController extends Controller
{
    /**
     * List the customer's support tickets / conversations.
     */
    public function index(Request $request): Response
    {
        $conversations = Conversation::support()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('Support/Index', [
            'conversations' => $conversations,
        ]);
    }

    /**
     * Create a new support ticket with category (quick-reply issue type) and description.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'category' => ['required', 'string', 'in:order,payment,delivery,account,product,other'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $conversation = Conversation::create([
            'user_id' => $request->user()->id,
            'team_id' => null,
            'is_support' => true,
            'support_subject' => $data['subject'],
            'support_category' => $data['category'],
            'support_status' => 'waiting_support',
            'last_message_at' => now(),
        ]);

        $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => strip_tags($data['body']),
        ]);

        return redirect()->route('support.show', $conversation->id);
    }

    /**
     * Show a specific support conversation / chat.
     */
    public function show(Request $request, Conversation $conversation): Response
    {
        abort_unless(
            $conversation->is_support && $conversation->user_id === $request->user()->id,
            404
        );

        $conversation->load('messages.sender:id,name,is_admin');

        return Inertia::render('Support/Show', [
            'conversation' => $conversation,
        ]);
    }

    /**
     * Send a message in an existing support conversation.
     */
    public function storeMessage(Request $request, Conversation $conversation): RedirectResponse
    {
        abort_unless(
            $conversation->is_support && $conversation->user_id === $request->user()->id,
            404
        );

        abort_if(
            $conversation->isClosed(),
            403,
            'This support request is already closed.'
        );

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => strip_tags($data['body']),
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'support_status' => 'waiting_support',
        ]);

        return back();
    }
}
