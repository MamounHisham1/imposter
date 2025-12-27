<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Redis;

echo "Testing Redis facade behavior with Predis client\n";
echo "===============================================\n\n";

// Get Redis connection
$redis = Redis::connection();
echo 'Redis client class: '.get_class($redis)."\n";

// Test brpop behavior
$testKey = 'test_brpop_'.time();

// First, push a test value
$redis->lpush($testKey, 'test_value_1');
echo "\nPushed value to list: $testKey\n";

// Test brpop with 1 second timeout
echo "\nTesting brpop with 1 second timeout...\n";
$start = microtime(true);
$result = $redis->brpop($testKey, 1);
$elapsed = microtime(true) - $start;

echo 'Result type: '.gettype($result)."\n";
echo 'Result value: '.var_export($result, true)."\n";
echo 'Time elapsed: '.round($elapsed, 3)." seconds\n";

// Test brpop on empty list (should timeout)
echo "\n\nTesting brpop on empty list (should timeout after 1 second)...\n";
$emptyKey = 'test_empty_'.time();
$start = microtime(true);
$result = $redis->brpop($emptyKey, 1);
$elapsed = microtime(true) - $start;

echo 'Result type: '.gettype($result)."\n";
echo 'Result value: '.var_export($result, true)."\n";
echo 'Time elapsed: '.round($elapsed, 3)." seconds\n";

// Clean up
$redis->del($testKey);
echo "\n\nCleaned up test keys.\n";
