<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Where to send the user right after login.
     *
     * - Admins go to the Admin Panel.
     * - Everyone else lands on the storefront homepage (not Jetstream's generic /dashboard).
     *   Sellers can switch into the Seller Center from the storefront nav afterward.
     */
    public function toResponse($request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        if ($user?->isBranchUser()) {
            return redirect()->route('seller.dashboard');
        }

        if ($user?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user?->isSupplier()) {
            return redirect()->route('supplier.dashboard');
        }

        $currentTeam = $user?->currentTeam;
        if ($currentTeam && !$currentTeam->personal_team) {
            return redirect()->route('seller.dashboard');
        }

        return redirect()->route('home');
    }
    
}
