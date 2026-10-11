<?php

namespace App\Http\Middleware;

use App\Enums\ShopStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsApprovedSeller
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isBranchUser()) {
            $branch = $user->loadMissing('branch.user')->branch;

            if (! $branch) {
                return redirect()->route('branch.pending');
            }

            $branchStatus = strtolower((string) $branch->getRawOriginal('status'));
            $ownerShop = $branch->user?->ownedTeams()
                ->where('personal_team', false)
                ->first();
            $ownerShopStatus = $ownerShop?->getRawOriginal('status');

            $isOwnerShopApproved = ! $ownerShop || in_array(strtolower((string) $ownerShopStatus), ['approved', 'active'], true);

            if ($branchStatus === 'suspended') {
                return redirect()->route('branch.pending');
            }

            if (! $isOwnerShopApproved) {
                return redirect()->route('seller.pending.approval');
            }

            $team = $ownerShop;
            $user->forceFill(['current_team_id' => $team->id])->save();
            $user->setRelation('currentTeam', $team);

            return $next($request);
        }

        $team = $user?->currentTeam;

        if ($user && (!$team || $team->personal_team)) {
            $team = $user->teams()
                ->where('personal_team', false)
                ->where('status', ShopStatus::Approved)
                ->first();

            if ($team) {
                $user->switchTeam($team);
            }
        }

        if (!$team || $team->personal_team) {
            abort(403, 'You need a shop to access the Seller Center.');
        }

        if ($team->status === ShopStatus::Rejected) {
            return redirect()->route('seller.denied');
        }

        if ($team->status === ShopStatus::Pending) {
            return redirect()->route('seller.pending.approval');
        }

        if (!$team->isApproved()) {
            abort(403, 'Your shop is pending approval.');
        }

        return $next($request);
    }
}
