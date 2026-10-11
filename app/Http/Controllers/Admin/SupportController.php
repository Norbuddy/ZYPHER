<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportController extends Controller
{
    public function index(): Response
    {
        $conversations = Conversation::support()
            ->with('user:id,name,email')
            ->orderByRaw("CASE WHEN support_status = 'closed' OR support_status = 'resolved' THEN 1 ELSE 0 END")
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('Admin/Support/Index', [
            'conversations' => $conversations,
        ]);
    }

    public function show(Conversation $conversation): Response
    {
        abort_unless($conversation->is_support, 404);

        $conversation->load([
            'messages.sender:id,name,is_admin',
            'user:id,name,email',
        ]);

        return Inertia::render('Admin/Support/Show', [
            'conversation' => $conversation,
        ]);
    }

    public function store(Request $request, Conversation $conversation): RedirectResponse
    {
        abort_unless($conversation->is_support, 404);

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
            'support_status' => 'waiting_customer',
        ]);

        return back();
    }

    public function close(Conversation $conversation): RedirectResponse
    {
        abort_unless($conversation->is_support, 404);

        $conversation->close();

        return back()->with('success', 'Support request closed. Chat state has been reset for future tickets.');
    }
}
