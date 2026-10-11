<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    public function index(Request $request): Response
    {
        $conversations = $request->user()->currentTeam->conversations()
            ->with('user:id,name')
            ->orderByDesc('last_message_at')
            ->get();

        return Inertia::render('Seller/Messages/Index', ['conversations' => $conversations]);
    }

    public function show(Request $request, Conversation $conversation): Response
    {
        abort_unless($conversation->team_id === $request->user()->currentTeam->id, 403);

        $conversation->load('messages.sender:id,name', 'user:id,name');

        return Inertia::render('Seller/Messages/Show', ['conversation' => $conversation]);
    }

    public function store(Request $request, Conversation $conversation): RedirectResponse
    {
        abort_unless($conversation->team_id === $request->user()->currentTeam->id, 403);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        return back();
    }
}
