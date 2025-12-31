<?php

namespace App\Livewire;

use App\Models\Friendship;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FriendRequests extends Component
{
    public function acceptRequest(int $friendshipId): void
    {
        $friendship = Friendship::query()
            ->where('friend_id', Auth::id())
            ->where('status', 'pending')
            ->findOrFail($friendshipId);

        $friendship->accept();

        $this->dispatch('friend-request-accepted');
    }

    public function declineRequest(int $friendshipId): void
    {
        $friendship = Friendship::query()
            ->where('friend_id', Auth::id())
            ->where('status', 'pending')
            ->findOrFail($friendshipId);

        $friendship->decline();

        $this->dispatch('friend-request-declined');
    }

    public function render()
    {
        $pendingRequests = Auth::user()->pendingFriendRequests();
        $sentRequests = Auth::user()->sentFriendRequests()
            ->where('status', 'pending')
            ->with('friend')
            ->get();

        return view('livewire.friend-requests', [
            'pendingRequests' => $pendingRequests,
            'sentRequests' => $sentRequests,
        ]);
    }
}
