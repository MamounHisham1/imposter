<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vote extends Model
{
    protected $fillable = [
        'voter_id',
        'target_player_id',
    ];

    public function voter(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'voter_id');
    }

    public function targetPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'target_player_id');
    }
}
