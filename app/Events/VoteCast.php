<?php

namespace App\Events;

use App\Models\Room;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoteCast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Room $room,
        public array $data
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('room.'.$this->room->code);
    }

    public function broadcastAs(): string
    {
        return 'vote.cast';
    }

    public function broadcastWith(): array
    {
        return $this->data;
    }
}
