<?php

namespace App\Models;

use App\Traits\HasOptimisticLocking;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolidexTooth extends Model
{
    use HasFactory, HasOptimisticLocking, Auditable;

    protected $table = 'solidex_teeth';

    protected $fillable = [
        'case_id',
        'tooth_number',
        'status',
        'version',
    ];

    protected $casts = [
        'version' => 'integer',
    ];

    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function stages()
    {
        return $this->hasMany(SolidexToothStage::class, 'tooth_id');
    }

    public function currentStage()
    {
        return $this->stages()
            ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
            ->orderBy('id')
            ->first();
    }

    public function notes()
    {
        return $this->hasMany(Note::class, 'scope_id')->where('scope', 'TOOTH');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'OPEN');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'IN_PROGRESS');
    }

    public function scopeDone($query)
    {
        return $query->where('status', 'DONE');
    }

    // Helpers
    public function isComplete(): bool
    {
        return $this->status === 'DONE';
    }

    public function updateStatusFromStages(): void
    {
        $stages = $this->stages;

        if ($stages->isEmpty()) {
            return;
        }

        $allDone = $stages->every(fn ($stage) => $stage->status === 'DONE');
        $anyInProgress = $stages->contains(fn ($stage) => $stage->status === 'IN_PROGRESS');

        if ($allDone) {
            $this->status = 'DONE';
        } elseif ($anyInProgress) {
            $this->status = 'IN_PROGRESS';
        } else {
            $this->status = 'OPEN';
        }

        $this->save();
    }

    public function getProgressPercentage(): float
    {
        $stages = $this->stages;
        if ($stages->isEmpty()) {
            return 0;
        }
        $done = $stages->where('status', 'DONE')->count();
        return round(($done / $stages->count()) * 100, 1);
    }
}
