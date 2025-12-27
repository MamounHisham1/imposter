<?php

namespace App\Livewire;

use App\Models\Player;
use App\Models\Room;
use App\Services\GameService;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class GameRoom extends Component
{
    public Room $room;

    public ?Player $player = null;

    public string $hintText = '';

    public ?int $voteTargetId = null;

    public array $events = [];

    // Chat state
    public string $chatMessage = '';

    public array $messages = [];

    // Results state
    public ?string $winner = null;

    public ?string $gameStatus = null;

    public ?array $roundResults = null;

    protected $listeners = [
        'player-joined' => 'handlePlayerJoined',
        'phase-changed' => 'handlePhaseChanged',
        'hint-submitted-event' => 'handleHintSubmitted',
        'vote-cast-event' => 'handleVoteCast',
        'round-finished' => 'handleRoundFinished',
        'message-sent' => 'handleMessageSent',
    ];

    public function mount(Room $room)
    {
        $this->room = $room;

        // Try to find player by session
        $sessionId = Session::getId();
        $this->player = $room->players()->where('session_id', $sessionId)->first();

        if (! $this->player) {
            // Redirect to join page if player not found
            return redirect()->route('join-room', ['code' => $room->code]);
        }

        // Load existing messages
        $this->loadMessages();
    }

    public function render()
    {
        // Eager load all relationships to avoid N+1 queries
        $this->room->load([
            'players',
            'hints.player',
            'votes.voter',
            'votes.targetPlayer',
            'messages.player',
        ]);

        return view('livewire.game-room', [
            'players' => $this->room->players,
            'hints' => $this->room->hints,
            'votes' => $this->room->votes,
            'messages' => $this->messages,
            'wordToShow' => $this->player ? $this->player->getWordToShow($this->room) : '',
            'isImposter' => $this->player?->is_imposter ?? false,
            'isEliminated' => $this->player?->isEliminated() ?? false,
            'hasSubmittedHint' => $this->player?->hasSubmittedHint() ?? false,
            'hasVoted' => $this->player?->hasVoted() ?? false,
            'canChat' => $this->player ? $this->player->canChat($this->room) : false,
            'timeRemaining' => $this->room->getTimeRemaining(),
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

    public function submitMessage()
    {
        $this->validate([
            'chatMessage' => 'required|string|max:500',
        ]);

        try {
            app(GameService::class)->submitMessage($this->player, $this->chatMessage);
            $this->chatMessage = '';
            $this->dispatch('message-sent');
        } catch (\Exception $e) {
            $this->addError('chatMessage', $e->getMessage());
        }
    }

    public function startGame()
    {
        if (! $this->room->canStartGame()) {
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

    public function startNextTurn()
    {
        try {
            app(GameService::class)->startNextTurn($this->room, $this->player);
            $this->resetResults();
        } catch (\Exception $e) {
            $this->addError('game', $e->getMessage());
        }
    }

    public function startNewRound()
    {
        try {
            app(GameService::class)->startNewRound($this->room, $this->player);
            $this->resetResults();
            $this->dispatch('new-round-started');
        } catch (\Exception $e) {
            $this->addError('game', $e->getMessage());
        }
    }

    private function resetResults()
    {
        $this->winner = null;
        $this->gameStatus = null;
        $this->roundResults = null;
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
        $this->resetResults(); // Clear results when phase changes
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
        // Store results data for display
        $this->roundResults = $event;
        $this->gameStatus = $event['game_status'] ?? 'ongoing';
        $this->winner = $event['winner'] ?? null;

        $this->dispatch('round-finished', $event);
    }

    private function loadMessages(): void
    {
        $this->messages = $this->room->messages()
            ->with('player')
            ->orderBy('created_at', 'asc')
            ->limit(100)
            ->get()
            ->map(function ($message) {
                return [
                    'message_id' => $message->id,
                    'player_id' => $message->player_id,
                    'player_name' => $message->player->name,
                    'message' => $message->message,
                    'timestamp' => $message->created_at->toISOString(),
                ];
            })
            ->toArray();
    }

    public function handleMessageSent($event = null)
    {
        if ($event) {
            $this->messages[] = $event;
            $this->dispatch('message-sent-event', $event);
        }
    }
}
