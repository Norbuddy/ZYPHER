<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BranchAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->isBranchUser()) {
            if ($user->role === 'cashier') {
                if ($request->route()?->getName() === 'seller.dashboard') {
                    return redirect()->route('seller.pos.index');
                }

                if (! in_array($request->route()?->getName(), ['seller.pos.index', 'seller.pos.store'], true)) {
                    abort(403, 'Cashiers can only access the POS.');
                }

                return $next($request);
            }

            if (! in_array($request->route()?->getName(), [
                'seller.dashboard',
                'seller.pos.index',
                'seller.pos.store',
                'seller.inventory.index',
                'seller.inventory.manual.store',
                'seller.inventory.items.store',
                'seller.inventory.shop-products.import',
                'branch.staff.index',
                'branch.staff.store',
                'branch.profile',
            ], true)) {
                abort(403, 'Branch staff can only access the branch dashboard, POS, and inventory.');
            }

            return $next($request);
        }

        if (! in_array($user->role, ['admin', 'seller_admin'], true)) {
            abort(403, 'You are not authorized to access the Seller Center.');
        }

        return $next($request);
    }
}