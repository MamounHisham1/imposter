<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class UserProfile extends Component
{
    use WithPagination;

    public ?User $user = null;

    public bool $isOwnProfile = false;

    public function mount(?User $user = null): void
    {
        $this->user = $user ?? Auth::user();
        $this->isOwnProfile = Auth::check() && Auth::id() === $this->user->id;
    }

    public function sendFriendRequest(): void
    {
        if (! Auth::check()) {
            $this->redirect(route('login'));

            return;
        }

        if ($this->isOwnProfile) {
            return;
        }

        Auth::user()->sendFriendRequestTo($this->user);

        $this->dispatch('friend-request-sent');
    }

    public function render()
    {
        $stats = $this->user->getStats();
        $gameHistories = $this->user->gameHistories()
            ->with('room')
            ->latest('game_completed_at')
            ->paginate(10);

        $isFriend = Auth::check() ? Auth::user()->isFriendsWith($this->user) : false;
        $hasPendingRequest = Auth::check() ? Auth::user()->hasPendingFriendRequestWith($this->user) : false;

        return view('livewire.user-profile', [
            'stats' => $stats,
            'gameHistories' => $gameHistories,
            'isFriend' => $isFriend,
            'hasPendingRequest' => $hasPendingRequest,
        ]);
    }
}
