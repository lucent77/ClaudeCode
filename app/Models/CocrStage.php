<?php

namespace App\Models;

use App\Traits\HasOptimisticLocking;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CocrStage extends Model
{
    use HasFactory, HasOptimisticLocking, Auditable;

    protected $table = 'cocr_stages';

    public const STAGES = ['TRANS', 'DESIGN', 'CAM', 'CNC', 'OVENS', 'QC'];

    protected $fillable = [
        'case_id',
        'stage',
        'status',
        'assignee_user_id',
        'completed_by_initials',
        'completed_at',
        'machine',
        'oven',
        'note',
        'version',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'version' => 'integer',
    ];

    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }

    // Scopes
    public function scopeByStage($query, string $stage)
    {
        return $query->where('stage', $stage);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'IN_PROGRESS');
    }

    public function scopeDone($query)
    {
        return $query->where('status', 'DONE');
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assignee_user_id', $userId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assignee_user_id');
    }

    public function scopeReadyToWork($query)
    {
        return $query->whereIn('status', ['PENDING', 'IN_PROGRESS']);
    }

    // Helpers
    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'IN_PROGRESS';
    }

    public function isDone(): bool
    {
        return $this->status === 'DONE';
    }

    public function getNextStage(): ?string
    {
        $currentIndex = array_search($this->stage, self::STAGES);
        if ($currentIndex === false || $currentIndex >= count(self::STAGES) - 1) {
            return null;
        }
        return self::STAGES[$currentIndex + 1];
    }

    public function getPreviousStage(): ?string
    {
        $currentIndex = array_search($this->stage, self::STAGES);
        if ($currentIndex === false || $currentIndex <= 0) {
            return null;
        }
        return self::STAGES[$currentIndex - 1];
    }

    public function getStageOrder(): int
    {
        return array_search($this->stage, self::STAGES);
    }

    public function requiresMachine(): bool
    {
        return $this->stage === 'CNC';
    }

    public function requiresOven(): bool
    {
        return $this->stage === 'OVENS';
    }

    public function canComplete(): bool
    {
        if ($this->requiresMachine() && empty($this->machine)) {
            return false;
        }
        if ($this->requiresOven() && empty($this->oven)) {
            return false;
        }
        return true;
    }
}
