<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Room extends Model
{
    protected $fillable = [
        'code',
        'status',
        'game_status',
        'winner',
        'current_word',
        'category',
        'creator_id',
        'discussion_time',
        'phase_started_at',
    ];

    protected $casts = [
        'is_imposter' => 'boolean',
        'phase_started_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(Player::class, 'creator_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function hints(): HasManyThrough
    {
        return $this->hasManyThrough(Hint::class, Player::class);
    }

    public function votes(): HasManyThrough
    {
        return $this->hasManyThrough(Vote::class, Player::class, 'room_id', 'voter_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function generateCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid()), 0, 6));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function canStartGame(): bool
    {
        return $this->players()->count() >= 3 && $this->players()->count() <= 8;
    }

    public function getImposter(): ?Player
    {
        return $this->players()->where('is_imposter', true)->first();
    }

    public function allPlayersSubmittedHints(): bool
    {
        $alivePlayerCount = $this->players()->where('status', 'alive')->count();
        $hintCount = $this->hints()->count();

        return $alivePlayerCount > 0 && $hintCount === $alivePlayerCount;
    }

    public function allPlayersVoted(): bool
    {
        $alivePlayerCount = $this->players()->where('status', 'alive')->count();
        $voteCount = $this->votes()->count();

        return $alivePlayerCount > 0 && $voteCount === $alivePlayerCount;
    }

    public function getTimeRemaining(): ?int
    {
        if (! $this->phase_started_at) {
            return null;
        }

        $phaseDuration = 0;
        switch ($this->status) {
            case 'reveal_word':
                $phaseDuration = 10; // 10 seconds to see and memorize the word
                break;
            case 'discussion':
                $phaseDuration = $this->discussion_time;
                break;
                // Add other phases if they have time limits
            default:
                return null;
        }

        // Calculate elapsed seconds (must be positive)
        $elapsed = $this->phase_started_at->diffInSeconds(now());
        $remaining = $phaseDuration - (int) $elapsed;

        return max(0, $remaining);
    }

    public function isPhaseTimeUp(): bool
    {
        $remaining = $this->getTimeRemaining();

        return $remaining !== null && $remaining <= 0;
    }
}
