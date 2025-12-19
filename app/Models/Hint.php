<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hint extends Model
{
    protected $fillable = [
        'player_id',
        'text',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
