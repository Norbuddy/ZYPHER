<?php

namespace App\Models;

use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;
use App\Enums\ShopStatus;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Team extends JetstreamTeam
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    // A "Team" here doubles as a Shop (Zypher). The team owner is the primary seller.

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'personal_team',
        'slug',
        'description',
        'logo_path',
        'status',
        'rating_avg',
        'rating_count',
        'commission_rate',
        'starting_cash_balance',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
            'status' => ShopStatus::class,
            'commission_rate' => 'decimal:2',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orderShops(): HasMany
    {
        return $this->hasMany(OrderShop::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function supplier(): HasOne
    {
        return $this->hasOne(Supplier::class, 'team_id');
    }

    public function allowedCategories(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'team_category');
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'supplier_team')
            ->withPivot(['active'])
            ->withTimestamps();
    }

    public function isApproved(): bool
    {
        return $this->status === ShopStatus::Approved;
    }

    protected static function booted(): void
    {
        static::creating(function (Team $team) {
            if (empty($team->slug)) {
                $base = \Illuminate\Support\Str::slug($team->name) ?: 'shop';
                $slug = $base;
                $i = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $team->slug = $slug;
            }
        });
    }
}
