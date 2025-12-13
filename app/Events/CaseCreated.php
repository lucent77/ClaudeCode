<?php

namespace App\Events;

use App\Models\CaseModel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CaseCreated implements ShouldBroadcast
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
            new Channel('global'),
        ];

        foreach ($this->case->activeRoutes as $route) {
            $channels[] = new Channel('dept:' . $route->target_dept);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'case.created';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'case.created',
            'case' => [
                'id' => $this->case->id,
                'case_number' => $this->case->case_number,
                'patient' => $this->case->patient,
                'lab' => $this->case->lab,
                'due_date' => $this->case->due_date?->format('Y-m-d'),
                'status' => $this->case->status,
                'nychv' => $this->case->nychv,
                'routes' => $this->case->activeRoutes->pluck('target_dept'),
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
