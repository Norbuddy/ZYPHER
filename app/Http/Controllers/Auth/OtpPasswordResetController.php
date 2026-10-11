<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Fortify\ResetUserPassword;
use App\Http\Controllers\Controller;
use App\Models\{PasswordResetOtp, User};
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash, Mail};
use Illuminate\Support\Str;
use Inertia\Inertia;

class OtpPasswordResetController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $otp = random_int(100000, 999999);

        PasswordResetOtp::updateOrCreate(
            ['email' => $request->email],
            ['otp' => Hash::make($otp), 'expires_at' => now()->addMinutes(10), 'used' => false]
        );

        Mail::to($request->email)->send(new OtpMail($otp));

        return back()->with('status', 'OTP sent to your email.');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['email' => 'required|email', 'otp' => 'required|digits:6']);

        $record = PasswordResetOtp::where('email', $request->email)
            ->where('used', false)->latest()->first();

        if (! $record || now()->gt($record->expires_at) || ! Hash::check($request->otp, $record->otp)) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP.']);
        }

        $record->update(['used' => true]);
        $token = Str::random(64);
        cache()->put('otp_reset_'.$request->email, $token, now()->addMinutes(10));

        return redirect()->route('password.reset.form', ['email' => $request->email, 'token' => $token]);
    }

    public function resetPassword(Request $request, ResetUserPassword $resetUserPassword)
    {
        $request->validate(['email' => 'required|email', 'token' => 'required']);

        if (cache('otp_reset_'.$request->email) !== $request->token) {
            return back()->withErrors(['token' => 'Invalid or expired session.']);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $resetUserPassword->reset($user, $request->only('password', 'password_confirmation'));

        cache()->forget('otp_reset_'.$request->email);

        return redirect()->route('login')->with('status', 'Password reset successfully.');
    }
}
