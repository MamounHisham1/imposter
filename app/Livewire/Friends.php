<?php

namespace App\Livewire;

use App\Models\Friendship;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Friends extends Component
{
    public string $searchTerm = '';

    public function removeFriend(int $friendshipId): void
    {
        $friendship = Friendship::query()
            ->where(function ($query) {
                $query->where('user_id', Auth::id())
                    ->orWhere('friend_id', Auth::id());
            })
            ->where('id', $friendshipId)
            ->first();

        if ($friendship) {
            $friendship->delete();
            $this->dispatch('friend-removed');
        }
    }

    public function render()
    {
        $friends = Auth::user()->friends();

        if ($this->searchTerm) {
            $friends = $friends->filter(function ($friend) {
                return str_contains(
                    strtolower($friend->name),
                    strtolower($this->searchTerm)
                );
            });
        }

        return view('livewire.friends', [
            'friends' => $friends,
        ]);
    }
}
