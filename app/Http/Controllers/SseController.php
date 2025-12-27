<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class SseController extends Controller
{
    public function stream(Room $room)
    {
        // Set a longer timeout for SSE connections
        set_time_limit(300); // 5 minutes

        return response()->stream(function () use ($room) {
            $lastEventId = (int) (request()->header('Last-Event-ID') ?? 0);
            $cacheKey = "sse_room_{$room->id}";
            $channel = "sse_room_{$room->id}_events";

            // Send initial keep-alive to establish connection
            echo ": connected\n\n";
            ob_flush();
            flush();

            $lastActivityTime = time();
            $pingInterval = 30; // Send ping every 30 seconds

            try {
                // Use Laravel's Redis facade
                $redis = Redis::connection();

                // We need to handle pings separately since subscribe() blocks
                // Let's use a simpler approach: check for events with a short timeout
                while (true) {
                    // Check if connection was closed by client
                    if (connection_aborted()) {
                        break;
                    }

                    // Send ping if connection has been idle too long
                    if ((time() - $lastActivityTime) > $pingInterval) {
                        echo ": ping\n\n";
                        ob_flush();
                        flush();
                        $lastActivityTime = time();
                    }

                    // Check for new events in cache (fallback if Redis fails)
                    $events = Cache::get($cacheKey, []);
                    $newEvents = [];
                    foreach ($events as $event) {
                        if ($event['id'] > $lastEventId) {
                            $newEvents[] = $event;
                        }
                    }

                    // Send any new events
                    foreach ($newEvents as $event) {
                        $this->sendEvent($event);
                        $lastEventId = $event['id'];
                        $lastActivityTime = time();
                    }

                    // Clear old events
                    if (count($events) > 20) {
                        $events = array_slice($events, -20);
                        Cache::put($cacheKey, $events, now()->addHours(1));
                    }

                    // Try to get a message from Redis with 1 second timeout
                    // Use brpop to block for up to 1 second
                    $message = $redis->brpop("{$channel}_queue", 1);

                    if ($message) {
                        $eventData = json_decode($message[1], true);
                        if ($eventData['id'] > $lastEventId) {
                            $this->sendEvent($eventData);
                            $lastEventId = $eventData['id'];
                            $lastActivityTime = time();
                        }
                    }
                }

            } catch (\Exception $e) {
                // Log error and send error event
                \Log::error('SSE Redis error: '.$e->getMessage());
                echo "event: error\n";
                echo 'data: '.json_encode(['message' => 'Connection error'])."\n\n";
                ob_flush();
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    private function sendEvent(array $event): void
    {
        echo "id: {$event['id']}\n";
        echo "event: {$event['event']}\n";
        echo 'data: '.json_encode($event['data'])."\n\n";
        ob_flush();
        flush();
    }

    public static function broadcast(Room $room, string $event, array $data): void
    {
        $cacheKey = "sse_room_{$room->id}";
        $channel = "sse_room_{$room->id}_events";

        // Get current events to generate next ID
        $events = Cache::get($cacheKey, []);
        $nextId = count($events) + 1;

        $eventData = [
            'id' => $nextId,
            'event' => $event,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ];

        // Store in cache for reconnection/history
        $events[] = $eventData;
        Cache::put($cacheKey, $events, now()->addHours(1));

        // Push to Redis list for real-time delivery
        try {
            $redis = Redis::connection();

            $redis->lpush("{$channel}_queue", json_encode($eventData));
            // Keep only last 100 messages in queue
            $redis->ltrim("{$channel}_queue", 0, 99);
        } catch (\Exception $e) {
            \Log::error('Failed to push SSE event to Redis: '.$e->getMessage());
            // Fallback: still store in cache, clients will get it on next poll/reconnection
        }

        \Log::info("Broadcasting event: {$event}", [
            'room_id' => $room->id,
            'event_id' => $nextId,
            'data' => $data,
        ]);
    }
}
