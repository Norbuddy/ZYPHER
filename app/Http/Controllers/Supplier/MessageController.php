<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Supplier;
use App\Models\SupplierOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $supplier = Supplier::where('team_id', $user->currentTeam?->id)->first();

        $conversations = $supplier
            ? Conversation::query()
                ->whereIn('team_id', $supplier->teams()->pluck('teams.id'))
                ->with('user:id,name', 'team:id,name', 'orderShop:id,order_id')
                ->orderByDesc('last_message_at')
                ->get()
            : collect();

        $newOrders = $supplier
            ? SupplierOrder::where('supplier_id', $supplier->id)
                ->where('status', 'pending')
                ->count()
            : 0;

        return Inertia::render('Supplier/Messages/Index', [
            'conversations' => $conversations,
            'new_order_notifications' => $newOrders,
        ]);
    }

    public function show(Request $request, Conversation $conversation): Response
    {
        $supplier = Supplier::where('team_id', $request->user()->currentTeam?->id)->first();
        abort_unless($supplier && $supplier->teams()->where('teams.id', $conversation->team_id)->exists(), 403);

        $conversation->load('messages.sender:id,name', 'user:id,name', 'team:id,name', 'orderShop:id,order_id');

        return Inertia::render('Supplier/Messages/Show', ['conversation' => $conversation]);
    }

    public function start(Request $request): RedirectResponse
    {
        $supplier = Supplier::where('team_id', $request->user()->currentTeam?->id)->first();
        $data = $request->validate([
            'team_id' => ['required', 'integer', 'exists:teams,id'],
        ]);

        abort_unless($supplier && $supplier->teams()->where('teams.id', $data['team_id'])->exists(), 403);

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

        return redirect()->route('supplier.messages.show', $conversation->id);
    }

    public function store(Request $request, Conversation $conversation): RedirectResponse
    {
        $supplier = Supplier::where('team_id', $request->user()->currentTeam?->id)->first();
        abort_unless($supplier && $supplier->teams()->where('teams.id', $conversation->team_id)->exists(), 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        return back();
    }
}
