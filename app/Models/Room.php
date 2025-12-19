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
        'current_word',
        'category',
        'creator_id',
    ];

    protected $casts = [
        'is_imposter' => 'boolean',
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
        $playerCount = $this->players()->count();
        $hintCount = $this->hints()->count();

        return $playerCount > 0 && $hintCount === $playerCount;
    }

    public function allPlayersVoted(): bool
    {
        $playerCount = $this->players()->count();
        $voteCount = $this->votes()->count();

        return $playerCount > 0 && $voteCount === $playerCount;
    }
}
