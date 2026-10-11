<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ForgotPasswordOtpController extends Controller
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Check if email exists
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'We could not find that email address.',
            ]);
        }

        // Send OTP
        $this->otpService->send($request->email);

        return back()->with('success', 'OTP has been sent to your email.');
    }
}