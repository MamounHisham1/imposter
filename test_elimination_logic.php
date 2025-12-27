<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Player;
use App\Models\Room;
use App\Services\GameService;

echo "=== Testing Elimination Logic ===\n\n";

$gameService = app(GameService::class);

// 1. Setup Room & Players
$room = $gameService->createRoom('حيوان');
$creator = $gameService->joinRoom($room, 'Creator'); // Player 1
$p2 = $gameService->joinRoom($room, 'P2');
$p3 = $gameService->joinRoom($room, 'P3');
$p4 = $gameService->joinRoom($room, 'P4');

echo "Room Created: {$room->code}\n";
echo "Players: 4\n\n";

// 2. Start Game
$gameService->startGame($room, $creator);
$room->refresh();
$imposter = $room->players()->where('is_imposter', true)->first();
echo "Game Started. Imposter is: {$imposter->name}\n\n";

// 3. Hints Phase
echo "Submitting hints...\n";
foreach ($room->players as $p) {
    $gameService->submitHint($p, 'Hint');
}
$room->refresh();
if ($room->status !== 'voting') {
    exit("Error: Status should be voting, is {$room->status}\n");
}
echo "Phase: Voting\n\n";

// 4. Voting Phase - Vote to eliminate a NON-imposter (P2, assuming P2 is not imposter)
// If P2 is imposter, we switch to P3.
$victim = ($imposter->id === $p2->id) ? $p3 : $p2;
echo "Targeting victim: {$victim->name} (Imposter is {$imposter->name})\n";

foreach ($room->players as $p) {
    if ($p->id !== $victim->id) {
        $gameService->submitVote($p, $victim);
    }
}
// Victim can't vote for self? Code says: "Check if voting for self".
// So victim votes for creator.
if ($victim->id !== $creator->id) {
    $gameService->submitVote($victim, $creator);
} else {
    // If victim is creator, vote for P4
    $gameService->submitVote($victim, $p4);
}

$room->refresh();
echo "Status after voting: {$room->status}\n"; // Should be results

// 5. Check Elimination
$victim->refresh();
echo "Victim Status: {$victim->status}\n";
if (! $victim->isEliminated()) {
    echo "FAILED: Victim was not eliminated.\n";
} else {
    echo "SUCCESS: Victim eliminated.\n";
}

// 6. Check Game Continuation
// Since victim was NOT imposter (we chose carefully), and players > 2 (4-1=3), game should be 'ongoing'.
// But `calculateResults` sets status to `results` and broadcasts 'ongoing'.
// The ROOM status stays `results` until someone clicks "Next Turn".

// Let's Simulate Next Turn
echo "Starting Next Turn...\n";
$gameService->startNextTurn($room, $creator);
$room->refresh();
echo "New Status: {$room->status}\n"; // Should be hints

if ($room->status === 'hints') {
    echo "SUCCESS: Game continued to hints phase.\n";
} else {
    echo "FAILED: Game did not continue properly.\n";
}

// 7. Verify Victim Cannot Vote/Hint
echo "Testing Victim Constraint...\n";
try {
    $gameService->submitHint($victim, 'Illegal Hint');
    echo "FAILED: Victim was allowed to hint.\n";
} catch (\Exception $e) {
    echo "SUCCESS: Victim blocked from hinting: {$e->getMessage()}\n";
}

echo "\n=== Test Complete ===\n";
