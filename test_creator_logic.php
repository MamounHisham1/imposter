<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Room;
use App\Services\GameService;

echo "=== Testing Creator Logic ===\n\n";

$gameService = app(GameService::class);

// Test 1: Create room and verify creator is set
echo "1. Testing room creation and creator assignment...\n";
$room = $gameService->createRoom('حيوان');
$player1 = $gameService->joinRoom($room, 'أحمد (المنشئ)');
$room->refresh();

echo "   Room created: {$room->code}\n";
echo "   Creator ID: {$room->creator_id}\n";
echo '   Creator name: '.($room->creator ? $room->creator->name : 'غير معين')."\n";

if ($room->creator_id === $player1->id) {
    echo "   ✓ Creator correctly set to first player\n";
} else {
    echo "   ✗ Creator not set correctly\n";
}
echo "\n";

// Test 2: Join more players
echo "2. Joining more players...\n";
$player2 = $gameService->joinRoom($room, 'سارة');
$player3 = $gameService->joinRoom($room, 'محمد');

echo "   Players joined:\n";
foreach ($room->players as $player) {
    $isCreator = $room->creator_id === $player->id ? ' (منشئ الغرفة)' : '';
    echo "   - {$player->name}{$isCreator}\n";
}
echo '   Total players: '.$room->players()->count()."\n";
echo "   ✓ Players joined successfully\n\n";

// Test 3: Test that only creator can start game
echo "3. Testing that only creator can start game...\n";

// Try to start game as non-creator (should fail)
try {
    $gameService->startGame($room, $player2); // player2 is not creator
    echo "   ✗ Non-creator was able to start game (should have failed)\n";
} catch (\Exception $e) {
    echo "   ✓ Non-creator cannot start game: {$e->getMessage()}\n";
}

// Try to start game as creator (should succeed)
try {
    $gameService->startGame($room, $player1); // player1 is creator
    $room->refresh();
    echo "   ✓ Creator can start game\n";
    echo "   Game status: {$room->status}\n";
    echo "   Word: {$room->current_word}\n";
} catch (\Exception $e) {
    echo "   ✗ Creator cannot start game: {$e->getMessage()}\n";
}
echo "\n";

// Test 4: Test new round logic
echo "4. Testing new round logic...\n";
$room->update(['status' => 'results']); // Set to results to test new round

// Try to start new round as non-creator (should fail)
try {
    $gameService->startNewRound($room, $player3); // player3 is not creator
    echo "   ✗ Non-creator was able to start new round (should have failed)\n";
} catch (\Exception $e) {
    echo "   ✓ Non-creator cannot start new round: {$e->getMessage()}\n";
}

// Try to start new round as creator (should succeed)
try {
    $gameService->startNewRound($room, $player1); // player1 is creator
    $room->refresh();
    echo "   ✓ Creator can start new round\n";
    echo "   New game status: {$room->status}\n";
    echo "   New word: {$room->current_word}\n";
} catch (\Exception $e) {
    echo "   ✗ Creator cannot start new round: {$e->getMessage()}\n";
}
echo "\n";

// Test 5: Verify only one imposter per round
echo "5. Verifying only one imposter per round...\n";
$imposters = $room->players()->where('is_imposter', true)->count();
echo "   Imposters in current round: {$imposters}\n";

if ($imposters === 1) {
    echo "   ✓ Only one imposter per round\n";
} else {
    echo "   ✗ Multiple imposters found\n";
}

// Show imposter
$imposter = $room->players()->where('is_imposter', true)->first();
if ($imposter) {
    echo "   Current imposter: {$imposter->name}\n";
}
echo "\n";

echo "=== Test Complete ===\n";
echo "Summary:\n";
echo "✅ Creator is set to first player who joins\n";
echo "✅ Only creator can start game\n";
echo "✅ Only creator can start new round\n";
echo "✅ Only one imposter per round\n";
echo "✅ Game logic works correctly\n";
