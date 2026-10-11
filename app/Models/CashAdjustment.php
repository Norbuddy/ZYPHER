<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashAdjustment extends Model
{
    protected $fillable = ['team_id', 'user_id', 'type', 'amount', 'note'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}