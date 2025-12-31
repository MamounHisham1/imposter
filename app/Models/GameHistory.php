<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameHistory extends Model
{
    /** @use HasFactory<\Database\Factories\GameHistoryFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'room_id',
        'was_imposter',
        'won',
        'score',
        'eliminated',
        'game_outcome',
        'game_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'was_imposter' => 'boolean',
            'won' => 'boolean',
            'eliminated' => 'boolean',
            'game_completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
