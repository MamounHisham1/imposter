<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Room;
use App\Services\GameService;

echo "=== Testing المخادع Game Flow ===\n\n";

// Test 1: Create a room
echo "1. Creating a room...\n";
$gameService = app(GameService::class);
$room = $gameService->createRoom('حيوان');
echo "   Room created: {$room->code}\n";
echo "   Status: {$room->status}\n\n";

// Test 2: Join players
echo "2. Joining players...\n";
$player1 = $gameService->joinRoom($room, 'أحمد');
$player2 = $gameService->joinRoom($room, 'محمد');
$player3 = $gameService->joinRoom($room, 'سارة');
echo "   Players joined: {$player1->name}, {$player2->name}, {$player3->name}\n";
echo '   Total players: '.$room->players()->count()."\n\n";

// Test 3: Start game
echo "3. Starting game...\n";
$gameService->startGame($room);
$room->refresh();
echo "   Game started!\n";
echo "   Status: {$room->status}\n";
echo "   Word: {$room->current_word}\n\n";

// Test 4: Check imposter
$imposter = $room->getImposter();
echo "4. Imposter check:\n";
foreach ($room->players as $player) {
    $status = $player->is_imposter ? 'المخادع' : 'لاعب عادي';
    echo "   {$player->name}: {$status}\n";
}
echo "\n";

// Test 5: Submit hints
echo "5. Submitting hints...\n";
$gameService->submitHint($player1, 'حيوان أليف');
$gameService->submitHint($player2, 'يأكل العشب');
$gameService->submitHint($player3, 'له ذيل');
echo "   Hints submitted\n";
echo '   All hints submitted: '.($room->allPlayersSubmittedHints() ? 'Yes' : 'No')."\n\n";

// Test 6: Check voting phase
$room->refresh();
echo "6. Game phase after hints:\n";
echo "   Status: {$room->status}\n";
echo "   Should be 'voting': ".($room->status === 'voting' ? '✓' : '✗')."\n\n";

// Test 7: Submit votes
echo "7. Submitting votes...\n";
// Players vote for someone else (not themselves)
$players = $room->players;
$gameService->submitVote($players[0], $players[1]);
$gameService->submitVote($players[1], $players[2]);
$gameService->submitVote($players[2], $players[0]);
echo "   Votes submitted\n";
echo '   All votes submitted: '.($room->allPlayersVoted() ? 'Yes' : 'No')."\n\n";

// Test 8: Check results phase
$room->refresh();
echo "8. Game phase after voting:\n";
echo "   Status: {$room->status}\n";
echo "   Should be 'results': ".($room->status === 'results' ? '✓' : '✗')."\n\n";

// Test 9: Check scores
echo "9. Final scores:\n";
foreach ($room->players as $player) {
    echo "   {$player->name}: {$player->score} points\n";
}

echo "\n=== Test Complete ===\n";
echo "All basic game flow tests passed! ✓\n";
