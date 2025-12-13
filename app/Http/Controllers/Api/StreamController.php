<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamController extends Controller
{
    public function stream(Request $request): StreamedResponse
    {
        $user = auth()->user();

        $response = new StreamedResponse(function () use ($user) {
            // Set headers for SSE
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no');

            // Send initial connection message
            $this->sendEvent('connected', [
                'user_id' => $user->id,
                'timestamp' => now()->toIso8601String(),
            ]);

            // Subscribe to relevant channels
            $channels = $this->getSubscriptionChannels($user);

            $pubsub = Redis::connection()->pubSubLoop();
            $pubsub->subscribe(...$channels);

            // Keep connection alive and listen for messages
            $lastPing = time();

            foreach ($pubsub as $message) {
                if ($message->kind === 'message') {
                    $data = json_decode($message->payload, true);
                    $this->sendEvent($data['event'] ?? 'update', $data);
                }

                // Send ping every 30 seconds to keep connection alive
                if (time() - $lastPing >= 30) {
                    $this->sendEvent('ping', ['timestamp' => now()->toIso8601String()]);
                    $lastPing = time();
                }

                // Check if client disconnected
                if (connection_aborted()) {
                    break;
                }

                // Flush output buffer
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }

            $pubsub->unsubscribe();
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    protected function getSubscriptionChannels($user): array
    {
        $channels = [
            'user:' . $user->id,
        ];

        // Subscribe to department channels based on access
        if ($user->isAdmin() || $user->dept === 'MULTI') {
            $channels[] = 'dept:COCR';
            $channels[] = 'dept:SOLIDEX';
            $channels[] = 'dept:PRINT';
        } else {
            $channels[] = 'dept:' . $user->dept;
        }

        // Global channel for system-wide updates
        $channels[] = 'global';

        return $channels;
    }

    protected function sendEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}
