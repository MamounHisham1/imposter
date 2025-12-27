<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Player;
use App\Models\Room;
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
                'status' => 'alive', // Ensure default status
            ]);

            // If this is the first player, set them as the room creator
            if ($room->players()->count() === 1 && ! $room->creator_id) {
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

        if ($room->players()->count() < 3) {
            throw new \Exception('يجب أن يكون عدد اللاعبين 3 على الأقل لبدء اللعبة');
        }

        DB::transaction(function () use ($room) {
            // Generate word using AI
            $word = $this->aiWordGenerator->generateWord($room->category);

            // Reset all players to default state (Fix for multiple imposters)
            $room->players()->update([
                'is_imposter' => false,
                'status' => 'alive',
                'score' => 0, // Optional: reset score on new game? Maybe keep it. User didn't specify. Let's keep score accumulation across games as a feature, but reset roles/status.
            ]);
            // If we want to reset score per game, uncomment:
            // $room->players()->update(['score' => 0]);
            // Actually, usually "New Game" resets everything. But "Rounds" in the old code implied score tracking.
            // I'll leave score alone for now, focusing on game logic.

            // Select random imposter - ensure only ONE imposter
            $players = $room->players;

            // Double-check: ensure no existing imposters (safety check)
            $room->players()->where('is_imposter', true)->update(['is_imposter' => false]);

            // Select random player
            $imposter = $players->random();
            $imposter->update(['is_imposter' => true]);

            // Note: We don't verify count here because it can cause transaction visibility issues
            // The reset above + single update should guarantee exactly one imposter

            // Update room
            $room->update([
                'status' => 'reveal_word',
                'current_word' => $word,
                'phase_started_at' => now(),
                'game_status' => 'ongoing',
                'winner' => null,
            ]);

            // Clear previous hints and votes
            $room->hints()->delete();
            $room->votes()->delete();

            // Broadcast game started
            $this->broadcastEvent($room, 'phase_changed', [
                'phase' => 'reveal_word',
                'word' => $word,
                // Note: imposter_id is NOT broadcasted for security reasons
                // Each player checks their own is_imposter status locally
            ]);
        });
    }

    public function startNextTurn(Room $room, Player $player): void
    {
        // Check if the player is the room creator
        if ($room->creator_id !== $player->id) {
            throw new \Exception('فقط منشئ الغرفة يمكنه بدء الجولة التالية');
        }

        // Check if there are enough alive players to continue
        $aliveCount = $room->players()->where('status', 'alive')->count();
        if ($aliveCount < 3) {
            throw new \Exception('لا يمكن المتابعة - عدد اللاعبين الأحياء أقل من 3');
        }

        // Check if game is already finished (imposter was caught)
        // This shouldn't happen if UI is correct, but safety check
        $imposter = $room->players()->where('is_imposter', true)->first();
        if ($imposter && $imposter->status === 'eliminated') {
            throw new \Exception('اللعبة انتهت - تم القبض على المخادع!');
        }

        DB::transaction(function () use ($room) {
            // Update room status
            $room->update([
                'status' => 'reveal_word',
                'phase_started_at' => now(),
                'game_status' => 'ongoing',
                'winner' => null,
            ]);

            // Clear hints and votes for the new turn
            $room->hints()->delete();
            $room->votes()->delete();

            // Broadcast phase change
            $this->broadcastEvent($room, 'phase_changed', [
                'phase' => 'reveal_word',
                'word' => $room->current_word,
            ]);
        });
    }

    public function transitionToDiscussion(Room $room): void
    {
        DB::transaction(function () use ($room) {
            $room->update([
                'status' => 'discussion',
                'phase_started_at' => now(),
            ]);

            // Clear messages from previous discussion
            $room->messages()->delete();

            // Broadcast phase change
            $this->broadcastEvent($room, 'phase_changed', [
                'phase' => 'discussion',
                'discussion_time' => $room->discussion_time,
            ]);
        });
    }

    public function updateDiscussionTime(Room $room, Player $player, int $seconds): void
    {
        // Check if the player is the room creator
        if ($room->creator_id !== $player->id) {
            throw new \Exception('فقط منشئ الغرفة يمكنه تغيير وقت النقاش');
        }

        if ($seconds < 10 || $seconds > 300) {
            throw new \Exception('يجب أن يكون وقت النقاش بين 10 ثوانٍ و 5 دقائق');
        }

        // Just update the discussion time, don't reset the timer
        $room->update(['discussion_time' => $seconds]);
    }

    public function transitionToVoting(Room $room): void
    {
        DB::transaction(function () use ($room) {
            $room->update([
                'status' => 'voting',
                'phase_started_at' => now(),
            ]);

            // Broadcast phase change
            $this->broadcastEvent($room, 'phase_changed', [
                'phase' => 'voting',
            ]);
        });
    }

    public function submitHint(Player $player, string $hintText): void
    {
        if (! $player->isAlive()) {
            throw new \Exception('اللاعبون المستبعدون لا يمكنهم تقديم تلميحات');
        }

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
                'all_submitted' => $player->room->allPlayersSubmittedHints(), // This needs to check only ALIVE players
            ]);

            // If all ALIVE players submitted hints, move to voting
            // Need to update `allPlayersSubmittedHints` in Room model or check here.
            // Let's check here manually or update model later.
            // Checking here:
            $aliveCount = $player->room->players()->where('status', 'alive')->count();
            $hintCount = $player->room->hints()->count();

            if ($hintCount >= $aliveCount) {
                $this->moveToDiscussionPhase($player->room);
            }
        });
    }

    public function submitMessage(Player $player, string $message): void
    {
        if (! $player->canChat($player->room)) {
            throw new \Exception('لا يمكنك إرسال رسالة الآن');
        }

        if (empty(trim($message))) {
            throw new \Exception('الرسالة لا يمكن أن تكون فارغة');
        }

        DB::transaction(function () use ($player, $message) {
            $messageRecord = Message::create([
                'room_id' => $player->room_id,
                'player_id' => $player->id,
                'message' => trim($message),
            ]);

            $this->broadcastEvent($player->room, 'message_sent', [
                'message_id' => $messageRecord->id,
                'player_id' => $player->id,
                'player_name' => $player->name,
                'message' => $messageRecord->message,
                'timestamp' => $messageRecord->created_at->toISOString(),
            ]);
        });
    }

    public function submitVote(Player $voter, Player $target): void
    {
        if (! $voter->isAlive()) {
            throw new \Exception('اللاعبون المستبعدون لا يمكنهم التصويت');
        }

        // Target can be anyone? Usually you vote to kick someone. You can vote for eliminated players? No.
        if (! $target->isAlive()) {
            throw new \Exception('لا يمكنك التصويت للاعب مستبعد');
        }

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
                // Check all voted
            ]);

            $aliveCount = $voter->room->players()->where('status', 'alive')->count();
            $voteCount = $voter->room->votes()->count();

            if ($voteCount >= $aliveCount) {
                $this->calculateResults($voter->room);
            }
        });
    }

    public function checkDiscussionTime(Room $room): void
    {
        if ($room->status === 'discussion' && $room->isPhaseTimeUp()) {
            $this->moveToVotingPhase($room);
        }
    }

    private function moveToDiscussionPhase(Room $room): void
    {
        $room->update([
            'status' => 'discussion',
            'phase_started_at' => now(),
        ]);

        $this->broadcastEvent($room, 'phase_changed', [
            'phase' => 'discussion',
            'hints' => $room->hints()->with('player')->get()->map(function ($hint) {
                return [
                    'player_id' => $hint->player_id,
                    'player_name' => $hint->player->name,
                    'text' => $hint->text,
                ];
            }),
            'discussion_time' => $room->discussion_time,
        ]);
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
            // Initialize as ongoing, will update if game ends
            $room->update([
                'status' => 'results',
                'game_status' => 'ongoing',
                'winner' => null,
            ]);

            $imposter = $room->players()->where('is_imposter', true)->first();
            $votes = $room->votes()->with('targetPlayer')->get();

            // Count votes per target
            $voteCounts = [];
            foreach ($votes as $vote) {
                $targetId = $vote->target_player_id;
                if (! isset($voteCounts[$targetId])) {
                    $voteCounts[$targetId] = 0;
                }
                $voteCounts[$targetId]++;
            }

            // Find player(s) with max votes
            $maxVotes = -1;
            $candidates = [];
            foreach ($voteCounts as $playerId => $count) {
                if ($count > $maxVotes) {
                    $maxVotes = $count;
                    $candidates = [$playerId];
                } elseif ($count === $maxVotes) {
                    $candidates[] = $playerId;
                }
            }

            $eliminatedPlayer = null;
            $gameStatus = 'ongoing';
            $winner = null;

            // Elimination Logic: Only eliminate if strict majority or just max?
            // "the player with more voted got kicked"
            // If tie (count($candidates) > 1), we usually do nothing or random?
            // Let's assume if there is a tie, NO ONE is kicked to be safe/fair.
            if (count($candidates) === 1) {
                $eliminatedPlayer = Player::find($candidates[0]);
                if ($eliminatedPlayer) {
                    $eliminatedPlayer->update(['status' => 'eliminated']);

                    \Log::info('Player eliminated', [
                        'player_id' => $eliminatedPlayer->id,
                        'player_name' => $eliminatedPlayer->name,
                        'is_imposter' => $eliminatedPlayer->is_imposter,
                    ]);
                }
            }

            // Win Conditions
            if ($eliminatedPlayer) {
                if ($eliminatedPlayer->is_imposter) {
                    // Imposter caught - GAME ENDS, CREWMATES WIN!
                    $gameStatus = 'finished';
                    $winner = 'crewmates';

                    \Log::info('Game finished - Imposter caught!', [
                        'imposter_id' => $eliminatedPlayer->id,
                        'imposter_name' => $eliminatedPlayer->name,
                    ]);
                } else {
                    // Innocent kicked. Check remaining.
                    $aliveCount = $room->players()->where('status', 'alive')->count();

                    \Log::info('Innocent player eliminated', [
                        'eliminated_player' => $eliminatedPlayer->name,
                        'alive_count_after' => $aliveCount,
                    ]);

                    // If 2 or fewer players remain -> Imposter wins (not enough to catch them)
                    if ($aliveCount <= 2) {
                        $gameStatus = 'finished';
                        $winner = 'imposter';

                        \Log::info('Game finished - Not enough players remain, imposter wins!');
                    }
                    // If 3+ players remain, game continues to next round
                }
            } else {
                // No one eliminated (tie). Game continues if enough players
                $aliveCount = $room->players()->where('status', 'alive')->count();
                if ($aliveCount < 3) {
                    // Edge case: not enough players even without elimination
                    $gameStatus = 'finished';
                    $winner = 'imposter';
                    \Log::info('Game finished - Not enough players for tie vote');
                } else {
                    $gameStatus = 'ongoing';
                }
            }

            // Update room with final game status
            $room->update([
                'game_status' => $gameStatus,
                'winner' => $winner,
            ]);

            \Log::info('Room updated with game status', [
                'room_id' => $room->id,
                'game_status' => $gameStatus,
                'winner' => $winner,
            ]);

            // Prepare results data
            $results = [
                'secret_word' => $room->current_word,
                'imposter' => ($gameStatus === 'finished') ? [ // Reveal imposter only if finished
                    'id' => $imposter->id,
                    'name' => $imposter->name,
                ] : null,
                'eliminated_player' => $eliminatedPlayer ? [
                    'id' => $eliminatedPlayer->id,
                    'name' => $eliminatedPlayer->name,
                    'is_imposter' => $eliminatedPlayer->is_imposter,
                ] : null,
                'game_status' => $gameStatus, // ongoing, finished
                'winner' => $winner, // crewmates, imposter, null
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
            ];

            // Broadcast results
            $this->broadcastEvent($room, 'round_finished', $results);

            // If game continues (tie or non-imposter eliminated with >2 players),
            // clear hints/votes and move to discussion phase
            if ($gameStatus === 'ongoing') {
                $aliveCount = $room->players()->where('status', 'alive')->count();
                // Game continues if:
                // 1. Tie (no elimination) OR
                // 2. Non-imposter eliminated and >2 players remain
                $shouldContinue = false;
                $message = '';

                if (! $eliminatedPlayer) {
                    // Tie - no one eliminated
                    $shouldContinue = true;
                    $message = 'تعادل في التصويت، استمرار اللعبة';
                } elseif (! $eliminatedPlayer->is_imposter && $aliveCount > 2) {
                    // Non-imposter eliminated, >2 players remain
                    $shouldContinue = true;
                    $message = 'استمرار اللعبة مع '.$aliveCount.' لاعبين';
                }

                if ($shouldContinue) {
                    // Clear hints and votes for next round
                    $room->hints()->delete();
                    $room->votes()->delete();

                    // Move to discussion phase
                    $room->update([
                        'status' => 'discussion',
                        'phase_started_at' => now(),
                    ]);

                    $this->broadcastEvent($room, 'phase_changed', [
                        'phase' => 'discussion',
                        'discussion_time' => $room->discussion_time,
                        'message' => $message,
                    ]);
                }
            }
        });
    }

    public function startNewRound(Room $room, Player $player): void
    {
        // Wrapper for startGame to be used as "Play Again"
        // Since startGame now resets everything, we can just call it.
        $this->startGame($room, $player);
    }

    private function broadcastEvent(Room $room, string $event, array $data): void
    {
        $eventClass = match ($event) {
            'player_joined' => \App\Events\PlayerJoined::class,
            'phase_changed' => \App\Events\PhaseChanged::class,
            'hint_submitted' => \App\Events\HintSubmitted::class,
            'vote_cast' => \App\Events\VoteCast::class,
            'round_finished' => \App\Events\RoundFinished::class,
            'message_sent' => \App\Events\MessageSent::class,
            default => throw new \InvalidArgumentException("Unknown event: {$event}"),
        };

        broadcast(new $eventClass($room, $data));

        \Log::info("Broadcasting event: {$event}", [
            'room_id' => $room->id,
            'data' => $data,
        ]);
    }
}
