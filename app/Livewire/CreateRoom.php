<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\GameService;
use App\Services\AiWordGenerator;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class CreateRoom extends Component
{
    public string $playerName = '';
    public string $category = 'شيء';
    public ?string $roomCode = null;
    public bool $creating = false;

    protected $rules = [
        'playerName' => 'required|string|min:2|max:20',
        'category' => 'required|string',
    ];

    public function mount()
    {
        // Get available categories
        $this->categories = app(AiWordGenerator::class)->getCategories();
    }

    public function createRoom()
    {
        $this->validate();
        $this->creating = true;

        try {
            $gameService = app(GameService::class);

            // Create room
            $room = $gameService->createRoom($this->category);

            // Join room as first player
            $player = $gameService->joinRoom($room, $this->playerName);

            $this->roomCode = $room->code;

            // Redirect to game room
            $this->redirect(route('game-room', ['room' => $room->code]), navigate: true);

        } catch (\Exception $e) {
            $this->addError('create', 'حدث خطأ أثناء إنشاء الغرفة: ' . $e->getMessage());
            $this->creating = false;
        }
    }

    public function render()
    {
        return view('livewire.create-room', [
            'categories' => app(AiWordGenerator::class)->getCategories(),
        ]);
    }
}
