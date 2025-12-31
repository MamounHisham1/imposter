<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
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
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function gameHistories(): HasMany
    {
        return $this->hasMany(GameHistory::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function sentFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'user_id');
    }

    public function receivedFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'friend_id');
    }

    /**
     * Get all accepted friends (both sent and received)
     */
    public function friends()
    {
        $sentFriends = $this->sentFriendRequests()
            ->where('status', 'accepted')
            ->with('friend')
            ->get()
            ->pluck('friend');

        $receivedFriends = $this->receivedFriendRequests()
            ->where('status', 'accepted')
            ->with('user')
            ->get()
            ->pluck('user');

        return $sentFriends->merge($receivedFriends);
    }

    /**
     * Get pending friend requests received by this user
     */
    public function pendingFriendRequests()
    {
        return $this->receivedFriendRequests()
            ->where('status', 'pending')
            ->with('user')
            ->get();
    }

    /**
     * Check if this user is friends with another user
     */
    public function isFriendsWith(User $user): bool
    {
        return $this->sentFriendRequests()
            ->where('friend_id', $user->id)
            ->where('status', 'accepted')
            ->exists()
            || $this->receivedFriendRequests()
                ->where('user_id', $user->id)
                ->where('status', 'accepted')
                ->exists();
    }

    /**
     * Check if there's a pending friend request between users
     */
    public function hasPendingFriendRequestWith(User $user): bool
    {
        return $this->sentFriendRequests()
            ->where('friend_id', $user->id)
            ->where('status', 'pending')
            ->exists()
            || $this->receivedFriendRequests()
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->exists();
    }

    /**
     * Send a friend request to another user
     */
    public function sendFriendRequestTo(User $user): ?Friendship
    {
        if ($this->id === $user->id) {
            return null;
        }

        if ($this->isFriendsWith($user) || $this->hasPendingFriendRequestWith($user)) {
            return null;
        }

        return $this->sentFriendRequests()->create([
            'friend_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Get user statistics
     */
    public function getStats(): array
    {
        $histories = $this->gameHistories;

        return [
            'total_games' => $histories->count(),
            'games_won' => $histories->where('won', true)->count(),
            'games_as_imposter' => $histories->where('was_imposter', true)->count(),
            'imposter_wins' => $histories->where('was_imposter', true)->where('won', true)->count(),
            'total_score' => $histories->sum('score'),
            'times_eliminated' => $histories->where('eliminated', true)->count(),
            'win_rate' => $histories->count() > 0
                ? round(($histories->where('won', true)->count() / $histories->count()) * 100, 1)
                : 0,
        ];
    }
}
