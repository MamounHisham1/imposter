<?php

namespace App\Livewire;

use App\Models\Room;
use App\Services\GameService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class JoinRoom extends Component
{
    public string $roomCode = '';

    public string $playerName = '';

    public bool $joining = false;

    public ?Room $room = null;

    protected $rules = [
        'roomCode' => 'required|string|size:6|exists:rooms,code',
        'playerName' => 'required|string|min:2|max:20',
    ];

    protected $messages = [
        'roomCode.exists' => 'رقم الغرفة غير صحيح',
    ];

    public function updatedRoomCode($value)
    {
        $this->roomCode = strtoupper($value);
        $this->room = Room::where('code', $this->roomCode)->first();
    }

    public function joinRoom()
    {
        $this->validate();
        $this->joining = true;

        try {
            $gameService = app(GameService::class);

            // Find room
            $room = Room::where('code', $this->roomCode)->firstOrFail();

            // Check if room is full (max 8 players)
            if ($room->players()->count() >= 8) {
                throw new \Exception('الغرفة ممتلئة (8 لاعبين كحد أقصى)');
            }

            // Join room
            $player = $gameService->joinRoom($room, $this->playerName);

            // Redirect to game room
            $this->redirect(route('game-room', ['room' => $room->code]), navigate: true);

        } catch (\Exception $e) {
            $this->addError('join', 'حدث خطأ أثناء الانضمام: '.$e->getMessage());
            $this->joining = false;
        }
    }

    public function render()
    {
        return view('livewire.join-room', [
            'room' => $this->room,
            'playerCount' => $this->room?->players()->count() ?? 0,
        ]);
    }
}
