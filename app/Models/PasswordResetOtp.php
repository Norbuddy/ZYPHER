<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PasswordResetOtp extends Model
{
    protected $fillable = ['email', 'otp', 'expires_at', 'used'];

    protected $casts = ['expires_at' => 'datetime', 'used' => 'boolean'];
    /**
     * Check if OTP has expired.
     */
    public function isExpired(): bool
    {
        return now()->greaterThan($this->expires_at);
    }

    /**
     * Increase failed attempts.
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    
}