<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\ShopStatus;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopApprovalController extends Controller
{
    public function index(): Response
    {
        $shops = Team::where('personal_team', false)->latest()->paginate(20);

        return Inertia::render('Admin/Shops/Index', ['shops' => $shops]);
    }

    public function approve(Team $team): RedirectResponse
    {
        $team->update(['status' => ShopStatus::Approved]);

        $team->owner?->notify(new \App\Notifications\ShopApproved($team));

        return back()->with('success', "{$team->name} approved.");
    }

    public function reject(Request $request, Team $team): RedirectResponse
    {
        $team->update(['status' => ShopStatus::Rejected]);

        return back()->with('success', "{$team->name} rejected.");
    }

    public function suspend(Team $team): RedirectResponse
    {
        $team->update(['status' => ShopStatus::Suspended]);

        return back()->with('success', "{$team->name} suspended.");
    }
}
