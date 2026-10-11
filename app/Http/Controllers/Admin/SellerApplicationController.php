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
        $user = $sellerApplication->user;
        abort_unless($user, 404, 'Applicant user account not found.');

        DB::transaction(function () use ($sellerApplication, $user) {
            $team = $user->ownedTeams()->where('personal_team', false)->first();

            if (!$team) {
                $team = Team::forceCreate([
                    'user_id' => $user->id,
                    'name' => $sellerApplication->business_name,
                    'slug' => Str::slug($sellerApplication->business_name) . '-' . Str::random(5),
                    'status' => 'approved',
                    'personal_team' => false,
                ]);

                if (! $user->teams()->where('teams.id', $team->id)->exists()) {
                    $user->teams()->attach($team, ['role' => 'admin']);
                }
            } else {
                $team->update(['status' => 'approved']);
            }

            $categories = is_array($sellerApplication->requested_category_ids)
                ? array_filter($sellerApplication->requested_category_ids)
                : [];

            if (!empty($categories)) {
                $team->allowedCategories()->syncWithoutDetaching($categories);
            }

            $user->forceFill([
                'current_team_id' => $team->id,
                'role' => $user->role === 'admin' ? 'admin' : 'seller_admin',
            ])->save();

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