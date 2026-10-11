<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'contact_number',
        'password',
        'role',
        'branch_role',
        'branch_id',
        'current_team_id',
        'is_suspended',
        'is_admin',
        'is_supplier',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_supplier' => 'boolean',
            'is_suspended' => 'boolean',
        ];
    }

    public function cart(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function addresses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function branches(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isBranchUser(): bool
    {
        return in_array($this->role, ['branch_user', 'branch_manager', 'cashier'], true);
    }

    public function isBranchManager(): bool
    {
        return in_array($this->role, ['branch_user', 'branch_manager'], true)
            && ($this->role === 'branch_manager' || $this->branch_role === 'manager');
    }

    public function conversations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isSupplier(): bool
    {
        return (bool) $this->is_supplier;
    }

    /** A user can own multiple shops via Jetstream teams, but typically owns one primary shop. */
    public function primaryShop(): ?Team
    {
        return $this->ownedTeams()->first();
    }

    public function searchHistories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SearchHistory::class);
    }

    /**
     * Get the URL to the user's profile photo with resilient fallback protection.
     */
    protected function profilePhotoUrl(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->profile_photo_path) {
                if (filter_var($this->profile_photo_path, FILTER_VALIDATE_URL)) {
                    return $this->profile_photo_path;
                }

                try {
                    $disk = $this->profilePhotoDisk();
                    if (Storage::disk($disk)->exists($this->profile_photo_path)) {
                        if (app()->runningInConsole() || !request()->hasHeader('Host')) {
                            return Storage::disk($disk)->url($this->profile_photo_path);
                        }
                        return url('/storage/' . ltrim($this->profile_photo_path, '/'));
                    }
                } catch (\Throwable $e) {
                    // Fall back to default avatar
                }
            }

            return $this->defaultProfilePhotoUrl();
        });
    }
}
