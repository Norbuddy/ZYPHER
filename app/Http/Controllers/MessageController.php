<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    public function index(Request $request): Response
    {
        $conversations = $request->user()->conversations()
            ->shopChats()
            ->with('team:id,name,slug')
            ->orderByDesc('last_message_at')
            ->get();

        return Inertia::render('Messages/Index', ['conversations' => $conversations]);
    }

    public function show(Request $request, Conversation $conversation): Response
    {
        abort_unless($conversation->user_id === $request->user()->id, 403);

        $conversation->load('messages.sender:id,name', 'team:id,name,slug');

        return Inertia::render('Messages/Show', ['conversation' => $conversation]);
    }

    /** Start (or resume) a conversation with a shop - e.g. from a product page "Contact Seller" button. */
    public function start(Request $request): RedirectResponse
    {
        $data = $request->validate(['team_id' => ['required', 'exists:teams,id']]);

        $conversation = Conversation::query()
            ->where('user_id', $request->user()->id)
            ->where('team_id', $data['team_id'])
            ->where('is_support', false)
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'user_id' => $request->user()->id,
                'team_id' => $data['team_id'],
                'is_support' => false,
            ]);
        }

        return redirect()->route('messages.show', $conversation->id);
    }

    public function store(Request $request, Conversation $conversation): RedirectResponse
    {
        abort_unless($conversation->user_id === $request->user()->id, 403);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $data['body'] = strip_tags($data['body']);

        $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        return back();
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'team_id' => ['required', 'integer', 'exists:teams,id'],
            'body' => ['required', 'string', 'max:2000'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'product_name' => ['nullable', 'string', 'max:255'],
        ]);
        $data['body'] = strip_tags($data['body']);

        $conversation = Conversation::query()
            ->where('user_id', $request->user()->id)
            ->where('team_id', $data['team_id'])
            ->where('is_support', false)
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'user_id' => $request->user()->id,
                'team_id' => $data['team_id'],
                'is_support' => false,
            ]);
        }

        $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $conversation->update([
            'last_message_at' => now(),
        ]);

        return back();
    }
}
