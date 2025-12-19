<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SseController extends Controller
{
    public function stream(Room $room)
    {
        return response()->stream(function () use ($room) {
            $lastEventId = 0;
            $cacheKey = "sse_room_{$room->id}";

            while (true) {
                if (connection_aborted()) {
                    break;
                }

                // Get new events from cache
                $events = Cache::get($cacheKey, []);
                $newEvents = array_filter($events, function ($event) use ($lastEventId) {
                    return $event['id'] > $lastEventId;
                });

                foreach ($newEvents as $event) {
                    $this->sendEvent($event);
                    $lastEventId = $event['id'];
                }

                // Clear processed events (keep last 10 for reconnection)
                if (count($events) > 10) {
                    $events = array_slice($events, -10);
                    Cache::put($cacheKey, $events, now()->addHours(1));
                }

                sleep(1); // Check for new events every second
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
        echo "data: " . json_encode($event['data']) . "\n\n";
        ob_flush();
        flush();
    }

    public static function broadcast(Room $room, string $event, array $data): void
    {
        $cacheKey = "sse_room_{$room->id}";
        $events = Cache::get($cacheKey, []);

        $events[] = [
            'id' => count($events) + 1,
            'event' => $event,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ];

        Cache::put($cacheKey, $events, now()->addHours(1));
    }
}
