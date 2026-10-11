<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsSupplier
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->isSupplier()) {
            abort(403, 'You need a supplier account to access the Supplier Center.');
        }

        return $next($request);
    }
}
