<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::latest()->paginate(20);

        return Inertia::render('Admin/Users/Index', ['users' => $users]);
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        // Prevent self-suspension and suspending other admins without care
        abort_if($user->id === $request->user()->id, 403, 'You cannot suspend your own account.');
        abort_if($user->is_admin && ! $request->user()->is_admin, 403);

        $user->update(['is_suspended' => true]);

        return back()->with('success', 'User has been suspended.');
    }

    public function activate(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 403);

        $user->update(['is_suspended' => false]);

        return back()->with('success', 'User has been reactivated.');
    }
}
