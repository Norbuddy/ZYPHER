<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Product;
use App\Models\SupplierOrder;
use App\Models\Team;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'notes',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'supplier_team')
            ->withPivot(['active'])
            ->withTimestamps();
    }

    public function scopeForTeam(Builder $query, Team $team): Builder
    {
        return $query->where(function (Builder $query) use ($team) {
            $query->whereHas('teams', function (Builder $query) use ($team) {
                $query->where('teams.id', $team->id)->where('supplier_team.active', true);
            })->orWhere('team_id', $team->id);
        });
    }

    public function scopeSuppliedToTeam(Builder $query, Team $team): Builder
    {
        return $this->scopeForTeam($query, $team);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function supplierOrders(): HasMany
    {
        return $this->hasMany(SupplierOrder::class);
    }
}
