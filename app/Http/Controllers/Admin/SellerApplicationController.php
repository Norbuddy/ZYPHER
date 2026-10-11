<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SellerApplication;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SellerApplicationController extends Controller
{
    public function index(): Response
    {
        $applications = SellerApplication::with('user')->latest()->get()->map(function ($app) {
            $app->requested_category_names = \App\Models\Category::whereIn('id', $app->requested_category_ids ?? [])->pluck('name');
            return $app;
        });

        return Inertia::render('Admin/SellerApplication/Index', [
            'applications' => $applications,
        ]);
    }

    public function approve(SellerApplication $sellerApplication)
    {
        DB::transaction(function () use ($sellerApplication) {
            $user = $sellerApplication->user;

            $team = $user->ownedTeams()->where('personal_team', false)->first();

            if (!$team) {
                $team = Team::create([
                    'user_id' => $user->id,
                    'name' => $sellerApplication->business_name,
                    'slug' => Str::slug($sellerApplication->business_name) . '-' . Str::random(5),
                    'status' => 'approved',
                    'personal_team' => false,
                ]);

                $user->teams()->attach($team);
            } else {
                $team->update(['status' => 'approved']);
            }

            $team->allowedCategories()->syncWithoutDetaching($sellerApplication->requested_category_ids);

            $user->update(['current_team_id' => $team->id]);

            $sellerApplication->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);
        });

        return back()->with('success', 'Seller application approved successfully.');
    }

    public function reject(Request $request, SellerApplication $sellerApplication)
    {
        $request->validate(['admin_note' => 'nullable|string|max:1000']);

        $sellerApplication->update([
            'status' => 'rejected',
            'admin_note' => $request->admin_note,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Seller application rejected.');
    }
}