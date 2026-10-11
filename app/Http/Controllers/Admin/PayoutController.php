<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\OrderShopStatus;
use App\Enums\PayoutStatus;
use App\Models\OrderShop;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PayoutController extends Controller
{
    public function index(): Response
    {
        $payouts = OrderShop::where('status', OrderShopStatus::Completed)
            ->where('payout_status', PayoutStatus::Unpaid)
            ->with('team:id,name')
            ->get()
            ->groupBy('team_id')
            ->map(fn ($group) => [
                'team' => $group->first()->team,
                'total_owed' => $group->sum('seller_payout'),
                'order_count' => $group->count(),
            ])
            ->values();

        return Inertia::render('Admin/Payouts/Index', ['payouts' => $payouts]);
    }

    public function markPaid(OrderShop $orderShop): RedirectResponse
    {
        $orderShop->update(['payout_status' => PayoutStatus::PaidOut]);

        $owner = $orderShop->team?->owner;
        if ($owner?->email) {
            \Illuminate\Support\Facades\Mail::to($owner->email)->send(new \App\Mail\PayoutNotificationMail($orderShop));
        }

        return back()->with('success', 'Marked as paid out.');
    }

    public function markPaidTeam(\App\Models\Team $team): RedirectResponse
    {
        $unpaidShops = OrderShop::where('team_id', $team->id)
            ->where('status', OrderShopStatus::Completed)
            ->where('payout_status', PayoutStatus::Unpaid)
            ->get();

        if ($unpaidShops->isEmpty()) {
            return back()->with('error', 'No unpaid payouts for this shop.');
        }

        foreach ($unpaidShops as $orderShop) {
            $orderShop->update(['payout_status' => PayoutStatus::PaidOut]);
        }

        $owner = $team->owner;
        if ($owner?->email && $unpaidShops->isNotEmpty()) {
            \Illuminate\Support\Facades\Mail::to($owner->email)->send(new \App\Mail\PayoutNotificationMail($unpaidShops->first()));
        }

        return back()->with('success', "Payouts marked as paid for {$team->name}.");
    }
}
