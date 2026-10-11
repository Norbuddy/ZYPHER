<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Supplier;
use App\Models\SupplierOrder;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $supplierNotifications = [
            'unread_messages' => 0,
            'pending_orders' => 0,
        ];

        $user = $request->user();
        $team = $user?->currentTeam;
        $unreadMessageCount = 0;

        if ($user) {
            $unreadMessageCount = Message::query()
                ->where('read_at', null)
                ->where('sender_id', '<>', $user->id)
                ->whereHas('conversation', fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->where('is_support', false)
                )
                ->count();
        }

        if ($user && $team) {
            $supplier = Supplier::where('team_id', $team->id)->first();

            if ($supplier) {
                $supplierNotifications['unread_messages'] = Message::query()
                    ->where('read_at', null)
                    ->where('sender_id', '<>', $user->id)
                    ->whereHas('conversation', fn ($query) => $query->where('team_id', $team->id))
                    ->count();

                $supplierNotifications['pending_orders'] = SupplierOrder::where('supplier_id', $supplier->id)
                    ->where('status', 'pending')
                    ->count();
            }
        }

        return [
            ...parent::share($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'receipt' => fn () => $request->session()->get('receipt'),
                'paymongo_checkout_url' => fn () => $request->session()->get('paymongo_checkout_url'),
                'pending_sale_id' => fn () => $request->session()->get('pending_sale_id'),
                'demo_payment' => fn () => $request->session()->get('demo_payment'),
            ],
            'activePopupAd' => fn () => $request->routeIs('home')
                ? \App\Models\PopupAd::currentlyLive()
                    ->inRandomOrder()
                    ->first(['id', 'title', 'message', 'image_path', 'button_text', 'button_link', 'updated_at'])
                : null,
            'supplierNotifications' => $supplierNotifications,
            'unreadMessageCount' => $unreadMessageCount,
            'branches' => $request->user()
                ? Branch::query()->where('user_id', $request->user()->id)->latest()->get()
                : [],

            'cartItemCount' => $request->user()
                ? (int) (\App\Models\Cart::where('user_id', $request->user()->id)->first()?->items()->sum('quantity') ?? 0)
                : 0,
        ];
    }
}