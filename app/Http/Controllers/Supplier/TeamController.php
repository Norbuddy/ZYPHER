<?php

namespace App\Http\Controllers\Supplier;

use App\Enums\ShopStatus;
use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    private function resolveSupplier(Request $request): ?Supplier
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        if ($user->currentTeam) {
            $supplier = Supplier::where('team_id', $user->currentTeam->id)->first();
            if ($supplier) {
                return $supplier;
            }
        }

        $teamIds = $user->teams()->pluck('teams.id')->toArray();
        if (!empty($teamIds)) {
            $supplier = Supplier::whereIn('team_id', $teamIds)->first();
            if ($supplier) {
                return $supplier;
            }
        }

        $ownedTeam = $user->ownedTeams()->first();

        return $ownedTeam ? Supplier::where('team_id', $ownedTeam->id)->first() : null;
    }

    private function supplierTeamForUser(Request $request): ?Team
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        if ($user->currentTeam) {
            return $user->currentTeam;
        }

        $team = $user->ownedTeams()->first();
        if ($team) {
            return $team;
        }

        $team = $user->teams()->first();
        if ($team) {
            return $team;
        }

        $team = $user->ownedTeams()->create([
            'name' => $user->name . ' Supplier Team',
            'personal_team' => true,
            'status' => ShopStatus::Approved,
        ]);

        $user->switchTeam($team);

        return $team;
    }

    private function resolveOrCreateSupplier(Request $request): ?Supplier
    {
        $supplier = $this->resolveSupplier($request);

        if ($supplier) {
            return $supplier;
        }

        $team = $this->supplierTeamForUser($request);

        if (!$team) {
            return null;
        }

        if (!$team->exists) {
            return null;
        }

        return Supplier::create([
            'team_id' => $team->id,
            'name' => $team->name,
            'contact_name' => $request->user()?->name,
            'email' => $request->user()?->email,
        ]);
    }

    public function index(Request $request): Response
    {
        $supplier = $this->resolveSupplier($request);

        $allTeams = Team::where('personal_team', false)
            ->where('status', '!=', 'rejected')
            ->orderBy('name')
            ->get();

        $myTeams = $supplier
            ? $supplier->teams()->wherePivot('active', true)->orderByDesc('supplier_team.created_at')->get()
            : collect();

        return Inertia::render('Supplier/Teams/Index', [
            'teams' => $allTeams->map(function (Team $team) use ($supplier) {
                $isSupplied = false;
                $isActive = false;

                if ($supplier) {
                    $relation = $supplier->teams()
                        ->where('teams.id', $team->id)
                        ->wherePivot('active', true)
                        ->first();
                    $isSupplied = (bool) $relation;
                    $isActive = $relation?->pivot?->active ? true : false;
                }

                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'status' => $team->status?->value ?? 'unknown',
                    'is_supplied' => $isSupplied,
                    'active' => $isActive,
                ];
            }),
            'myTeams' => $myTeams->map(function (Team $team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'status' => 'Currently Supplying',
                    'added_at' => $team->pivot->created_at?->format('M d, Y'),
                    'active' => (bool) $team->pivot->active,
                ];
            }),
            'supplierId' => $supplier?->id,
        ]);
    }

    public function supply(Request $request, Team $team): RedirectResponse
    {
        $supplier = $this->resolveOrCreateSupplier($request);

        if (!$supplier) {
            return back()->withErrors(['supplier' => 'Supplier account could not be resolved. Please refresh or log in again.']);
        }

        if ($team->personal_team) {
            return back()->withErrors(['team' => 'Personal teams cannot be supplied.']);
        }

        $supplier->teams()->syncWithoutDetaching([
            $team->id => ['active' => true],
        ]);

        return back()->with('success', 'Team marked as supplied.');
    }

    public function bulkSupply(Request $request): RedirectResponse
    {
        $request->validate([
            'team_ids' => ['required', 'array'],
            'team_ids.*' => ['required', 'integer', 'exists:teams,id'],
        ]);

        $supplier = $this->resolveOrCreateSupplier($request);

        if (!$supplier) {
            return back()->withErrors(['supplier' => 'Supplier account could not be resolved. Please refresh or log in again.']);
        }

        $syncData = array_fill_keys($request->input('team_ids'), ['active' => true]);
        $supplier->teams()->syncWithoutDetaching($syncData);

        return back()->with('success', 'Selected teams are now supplied.');
    }

    public function remove(Request $request, Team $team): RedirectResponse
    {
        $supplier = $this->resolveSupplier($request);

        if (!$supplier) {
            return back()->withErrors(['supplier' => 'Supplier account could not be resolved. Please refresh or log in again.']);
        }

        if (!$supplier->teams()->where('teams.id', $team->id)->exists()) {
            return back()->withErrors(['team' => 'The team is not currently supplied by this supplier.']);
        }

        $supplier->teams()->detach($team->id);

        return back()->with('success', 'Team supply removed.');
    }
}
