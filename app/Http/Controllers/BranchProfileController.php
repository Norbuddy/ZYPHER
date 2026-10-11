<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class BranchProfileController extends Controller
{
    public function show(Request $request)
    {
        abort_unless($request->user()->isBranchUser(), 403);

        return Inertia::render('Seller/Branch/Profile');
    }
}