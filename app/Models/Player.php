<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Player extends Model
{
    protected $fillable = [
        'room_id',
        'name',
        'is_imposter',
        'status',
        'score',
        'session_id',
    ];

    protected $casts = [
        'is_imposter' => 'boolean',
    ];

    public function isAlive(): bool
    {
        return $this->status === 'alive';
    }

    public function isEliminated(): bool
    {
        return $this->status === 'eliminated';
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hint(): HasOne
    {
        return $this->hasOne(Hint::class);
    }

    public function votesReceived(): HasMany
    {
        return $this->hasMany(Vote::class, 'target_player_id');
    }

    public function vote(): HasOne
    {
        return $this->hasOne(Vote::class, 'voter_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function getWordToShow(Room $room): string
    {
        if ($this->isEliminated()) {
            return 'لقد تم إقصاؤك من اللعبة';
        }

        if ($this->is_imposter) {
            return 'أنت المخادع، حاول ألا تنكشف';
        }

        return $room->current_word ?? 'جاري تحميل الكلمة...';
    }

    public function hasSubmittedHint(): bool
    {
        return $this->hint()->exists();
    }

    public function hasVoted(): bool
    {
        return $this->vote()->exists();
    }

    public function getVoteCount(): int
    {
        return $this->votesReceived()->count();
    }

    public function canChat(Room $room): bool
    {
        // Eliminated players cannot chat (spectate only)
        if ($this->isEliminated()) {
            return false;
        }

        // Players can only chat during discussion phase
        if ($room->status !== 'discussion') {
            return false;
        }

        // Player must be alive
        if (! $this->isAlive()) {
            return false;
        }

        return true;
    }
}
