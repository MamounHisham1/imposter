<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Player;
use Illuminate\Support\Facades\DB;

class GameService
{
    public function __construct(
        private AiWordGenerator $aiWordGenerator
    ) {}

    public function createRoom(string $category = 'شيء'): Room
    {
        return DB::transaction(function () use ($category) {
            $room = Room::create([
                'code' => (new Room)->generateCode(),
                'status' => 'waiting',
                'category' => $category,
            ]);

            return $room;
        });
    }

    public function joinRoom(Room $room, string $playerName, ?string $sessionId = null): Player
    {
        return DB::transaction(function () use ($room, $playerName, $sessionId) {
            $player = Player::create([
                'room_id' => $room->id,
                'name' => $playerName,
                'session_id' => $sessionId ?? session()->getId(),
            ]);

            // If this is the first player, set them as the room creator
            if ($room->players()->count() === 1 && !$room->creator_id) {
                $room->update(['creator_id' => $player->id]);
            }

            // Broadcast player joined event
            $this->broadcastEvent($room, 'player_joined', [
                'player_id' => $player->id,
                'player_name' => $player->name,
                'player_count' => $room->players()->count(),
                'is_creator' => $room->creator_id === $player->id,
            ]);

            return $player;
        });
    }

    public function startGame(Room $room, Player $player): void
    {
        // Check if the player is the room creator
        if ($room->creator_id !== $player->id) {
            throw new \Exception('فقط منشئ الغرفة يمكنه بدء اللعبة');
        }

        DB::transaction(function () use ($room) {
            // Generate word using AI
            $word = $this->aiWordGenerator->generateWord($room->category);

            // Select random imposter
            $players = $room->players;
            $imposterIndex = array_rand($players->toArray());
            $players[$imposterIndex]->update(['is_imposter' => true]);

            // Update room
            $room->update([
                'status' => 'hints',
                'current_word' => $word,
            ]);

            // Clear previous hints and votes
            $room->hints()->delete();
            $room->votes()->delete();

            // Broadcast game started
            $this->broadcastEvent($room, 'phase_changed', [
                'phase' => 'hints',
                'word' => $word,
                'imposter_id' => $players[$imposterIndex]->id,
            ]);
        });
    }

    public function submitHint(Player $player, string $hintText): void
    {
        DB::transaction(function () use ($player, $hintText) {
            // Validate hint
            if (str_word_count($hintText) > 3) {
                throw new \Exception('التلميح يجب أن يكون كلمة واحدة أو جملة قصيرة (3 كلمات كحد أقصى)');
            }

            // Check if player already submitted hint
            if ($player->hasSubmittedHint()) {
                throw new \Exception('لقد قدمت تلميحًا بالفعل');
            }

            // Create hint
            $player->hint()->create([
                'text' => $hintText,
            ]);

            // Broadcast hint submitted
            $this->broadcastEvent($player->room, 'hint_submitted', [
                'player_id' => $player->id,
                'player_name' => $player->name,
                'all_submitted' => $player->room->allPlayersSubmittedHints(),
            ]);

            // If all players submitted hints, move to voting
            if ($player->room->allPlayersSubmittedHints()) {
                $this->moveToVotingPhase($player->room);
            }
        });
    }

    public function submitVote(Player $voter, Player $target): void
    {
        DB::transaction(function () use ($voter, $target) {
            // Check if voter already voted
            if ($voter->hasVoted()) {
                throw new \Exception('لقد صوتت بالفعل');
            }

            // Check if voting for self
            if ($voter->id === $target->id) {
                throw new \Exception('لا يمكنك التصويت لنفسك');
            }

            // Create vote
            $voter->vote()->create([
                'target_player_id' => $target->id,
            ]);

            // Broadcast vote cast
            $this->broadcastEvent($voter->room, 'vote_cast', [
                'voter_id' => $voter->id,
                'voter_name' => $voter->name,
                'target_id' => $target->id,
                'target_name' => $target->name,
                'all_voted' => $voter->room->allPlayersVoted(),
            ]);

            // If all players voted, calculate results
            if ($voter->room->allPlayersVoted()) {
                $this->calculateResults($voter->room);
            }
        });
    }

    private function moveToVotingPhase(Room $room): void
    {
        $room->update(['status' => 'voting']);

        $this->broadcastEvent($room, 'phase_changed', [
            'phase' => 'voting',
            'hints' => $room->hints()->with('player')->get()->map(function ($hint) {
                return [
                    'player_id' => $hint->player_id,
                    'player_name' => $hint->player->name,
                    'text' => $hint->text,
                ];
            }),
        ]);
    }

    private function calculateResults(Room $room): void
    {
        DB::transaction(function () use ($room) {
            $room->update(['status' => 'results']);

            $imposter = $room->getImposter();
            $votes = $room->votes()->with('targetPlayer')->get();

            // Calculate scores
            foreach ($votes as $vote) {
                $voter = $vote->voter;
                $target = $vote->targetPlayer;

                // Voter gets +1 if they guessed correctly
                if ($target->is_imposter) {
                    $voter->increment('score');
                }

                // Imposter gets +1 if not caught
                if ($imposter && !$imposter->votesReceived()->exists()) {
                    $imposter->increment('score');
                }
            }

            // Prepare results data
            $results = [
                'secret_word' => $room->current_word,
                'imposter' => $imposter ? [
                    'id' => $imposter->id,
                    'name' => $imposter->name,
                ] : null,
                'votes' => $votes->map(function ($vote) {
                    return [
                        'voter_name' => $vote->voter->name,
                        'target_name' => $vote->targetPlayer->name,
                    ];
                }),
                'vote_counts' => $room->players->map(function ($player) {
                    return [
                        'player_id' => $player->id,
                        'player_name' => $player->name,
                        'vote_count' => $player->getVoteCount(),
                    ];
                }),
                'scores' => $room->players->map(function ($player) {
                    return [
                        'player_id' => $player->id,
                        'player_name' => $player->name,
                        'score' => $player->score,
                    ];
                }),
            ];

            // Broadcast results
            $this->broadcastEvent($room, 'round_finished', $results);
        });
    }

    public function startNewRound(Room $room, Player $player): void
    {
        // Check if the player is the room creator
        if ($room->creator_id !== $player->id) {
            throw new \Exception('فقط منشئ الغرفة يمكنه بدء جولة جديدة');
        }

        DB::transaction(function () use ($room, $player) {
            // Clear previous game data
            $room->players()->update(['is_imposter' => false]);
            $room->hints()->delete();
            $room->votes()->delete();

            // Start new game
            $this->startGame($room, $player);
        });
    }

    private function broadcastEvent(Room $room, string $event, array $data): void
    {
        \App\Http\Controllers\SseController::broadcast($room, $event, $data);

        \Log::info("Broadcasting event: {$event}", [
            'room_id' => $room->id,
            'data' => $data,
        ]);
    }
}
