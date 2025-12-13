<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StageCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Model $stage;
    public string $dept;

    public function __construct(Model $stage, string $dept)
    {
        $this->stage = $stage;
        $this->dept = $dept;
    }

    public function broadcastOn(): array
    {
        $caseId = $this->dept === 'SOLIDEX'
            ? $this->stage->tooth->case_id
            : $this->stage->case_id;

        return [
            new Channel('dept:' . $this->dept),
            new Channel('case:' . $caseId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'stage.completed';
    }

    public function broadcastWith(): array
    {
        $case = $this->dept === 'SOLIDEX'
            ? $this->stage->tooth->case
            : $this->stage->case;

        return [
            'event' => 'stage.completed',
            'dept' => $this->dept,
            'stage' => [
                'id' => $this->stage->id,
                'stage' => $this->stage->stage,
                'status' => $this->stage->status,
                'completed_by_initials' => $this->stage->completed_by_initials,
                'completed_at' => $this->stage->completed_at?->format('Y-m-d H:i:s'),
            ],
            'case' => [
                'id' => $case->id,
                'case_number' => $case->case_number,
                'status' => $case->status,
            ],
            'tooth_number' => $this->dept === 'SOLIDEX' ? $this->stage->tooth->tooth_number : null,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
