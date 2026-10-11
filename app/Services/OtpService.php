<?php

namespace App\Services;

use App\Mail\PasswordOtpMail;
use App\Models\PasswordResetOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    /**
     * Generate and send OTP.
     */
    public function send(string $email): void
    {
        // Delete old OTP for this email
        PasswordResetOtp::where('email', $email)->delete();

        // Generate 6-digit OTP
        $otp = random_int(100000, 999999);

        // Save hashed OTP
        PasswordResetOtp::create([
            'email' => $email,
            'otp' => Hash::make($otp),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        // Send email
        Mail::to($email)->send(
    new PasswordOtpMail((string) $otp)
);
    }

    /**
     * Verify OTP.
     */
    public function verify(string $email, string $otp): bool
    {
        $record = PasswordResetOtp::where('email', $email)->first();

        if (!$record) {
            return false;
        }

        if ($record->isExpired()) {
            return false;
        }

        if ($record->attempts >= 5) {
            return false;
        }

        if (!Hash::check($otp, $record->otp)) {
            $record->incrementAttempts();
            return false;
        }

        return true;
    }

    /**
     * Delete OTP after successful verification.
     */
    public function destroy(string $email): void
    {
        PasswordResetOtp::where('email', $email)->delete();
    }
}