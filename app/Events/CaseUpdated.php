<?php

namespace App\Events;

use App\Models\CaseModel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CaseUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public CaseModel $case;

    public function __construct(CaseModel $case)
    {
        $this->case = $case;
    }

    public function broadcastOn(): array
    {
        $channels = [
            new Channel('case:' . $this->case->id),
        ];

        foreach ($this->case->activeRoutes as $route) {
            $channels[] = new Channel('dept:' . $route->target_dept);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'case.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'case.updated',
            'case' => [
                'id' => $this->case->id,
                'case_number' => $this->case->case_number,
                'status' => $this->case->status,
                'due_date' => $this->case->due_date?->format('Y-m-d'),
                'version' => $this->case->version,
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
