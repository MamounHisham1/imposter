<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Room;
use App\Models\Player;
use App\Services\GameService;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class GameRoom extends Component
{
    public Room $room;
    public ?Player $player = null;
    public string $hintText = '';
    public ?int $voteTargetId = null;
    public array $events = [];

    protected $listeners = [
        'player-joined' => 'handlePlayerJoined',
        'phase-changed' => 'handlePhaseChanged',
        'hint-submitted-event' => 'handleHintSubmitted',
        'vote-cast-event' => 'handleVoteCast',
        'round-finished' => 'handleRoundFinished',
    ];

    public function mount(Room $room)
    {
        $this->room = $room;

        // Try to find player by session
        $sessionId = Session::getId();
        $this->player = $room->players()->where('session_id', $sessionId)->first();

        if (!$this->player) {
            // Redirect to join page if player not found
            return redirect()->route('join-room', ['code' => $room->code]);
        }
    }

    public function render()
    {
        return view('livewire.game-room', [
            'players' => $this->room->players,
            'hints' => $this->room->hints()->with('player')->get(),
            'votes' => $this->room->votes()->with(['voter', 'targetPlayer'])->get(),
            'wordToShow' => $this->player ? $this->player->getWordToShow($this->room) : '',
            'isImposter' => $this->player?->is_imposter ?? false,
            'hasSubmittedHint' => $this->player?->hasSubmittedHint() ?? false,
            'hasVoted' => $this->player?->hasVoted() ?? false,
        ]);
    }

    public function submitHint()
    {
        $this->validate([
            'hintText' => 'required|string|max:50',
        ]);

        try {
            app(GameService::class)->submitHint($this->player, $this->hintText);
            $this->hintText = '';
            $this->dispatch('hint-submitted');
        } catch (\Exception $e) {
            $this->addError('hintText', $e->getMessage());
        }
    }

    public function submitVote()
    {
        $this->validate([
            'voteTargetId' => 'required|exists:players,id',
        ]);

        try {
            $target = Player::find($this->voteTargetId);
            app(GameService::class)->submitVote($this->player, $target);
            $this->voteTargetId = null;
            $this->dispatch('vote-cast');
        } catch (\Exception $e) {
            $this->addError('voteTargetId', $e->getMessage());
        }
    }

    public function startGame()
    {
        if (!$this->room->canStartGame()) {
            $this->addError('game', 'يجب أن يكون عدد اللاعبين بين 3 و 8 للبدء');
            return;
        }

        try {
            app(GameService::class)->startGame($this->room, $this->player);
            $this->dispatch('game-started');
        } catch (\Exception $e) {
            $this->addError('game', $e->getMessage());
        }
    }

    public function startNewRound()
    {
        try {
            app(GameService::class)->startNewRound($this->room, $this->player);
            $this->dispatch('new-round-started');
        } catch (\Exception $e) {
            $this->addError('game', $e->getMessage());
        }
    }

    // Event handlers for real-time updates
    public function handlePlayerJoined($event = null)
    {
        $this->room->refresh();
        $this->dispatch('player-joined', $event);
    }

    public function handlePhaseChanged($event = null)
    {
        $this->room->refresh();
        $this->dispatch('phase-changed', $event);
    }

    public function handleHintSubmitted($event = null)
    {
        $this->room->refresh();
        $this->dispatch('hint-submitted-event', $event);
    }

    public function handleVoteCast($event = null)
    {
        $this->room->refresh();
        $this->dispatch('vote-cast-event', $event);
    }

    public function handleRoundFinished($event = null)
    {
        $this->room->refresh();
        $this->dispatch('round-finished', $event);
    }
}
