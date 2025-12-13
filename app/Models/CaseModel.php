<?php

namespace App\Models;

use App\Traits\HasOptimisticLocking;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseModel extends Model
{
    use HasFactory, HasOptimisticLocking, Auditable;

    protected $table = 'cases';

    protected $fillable = [
        'case_number',
        'nychv',
        'time_stamp',
        'combo',
        'due_date',
        'ld',
        'pan',
        'lab',
        'patient',
        'tooth_count',
        'tooth_map',
        'instructions',
        'preferences',
        'status',
        'version',
    ];

    protected $casts = [
        'time_stamp' => 'datetime',
        'due_date' => 'date',
        'combo' => 'boolean',
        'tooth_map' => 'array',
        'version' => 'integer',
    ];

    // Relationships
    public function routes()
    {
        return $this->hasMany(CaseRoute::class, 'case_id');
    }

    public function activeRoutes()
    {
        return $this->hasMany(CaseRoute::class, 'case_id')->where('active', true);
    }

    public function cocrMeta()
    {
        return $this->hasOne(CocrMeta::class, 'case_id');
    }

    public function cocrStages()
    {
        return $this->hasMany(CocrStage::class, 'case_id');
    }

    public function solidexMeta()
    {
        return $this->hasOne(SolidexCaseMeta::class, 'case_id');
    }

    public function solidexTeeth()
    {
        return $this->hasMany(SolidexTooth::class, 'case_id');
    }

    public function printMeta()
    {
        return $this->hasOne(PrintMeta::class, 'case_id');
    }

    public function printStages()
    {
        return $this->hasMany(PrintStage::class, 'case_id');
    }

    public function notes()
    {
        return $this->hasMany(Note::class, 'scope_id')->where('scope', 'CASE');
    }

    // Scopes
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

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

    public function scopeDueBefore($query, $date)
    {
        return $query->where('due_date', '<=', $date);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now()->toDateString())
                     ->whereNotIn('status', ['DONE', 'ON_HOLD']);
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', now()->toDateString());
    }

    public function scopeDueSoon($query, int $hours = 24)
    {
        return $query->where('due_date', '<=', now()->addHours($hours)->toDateString())
                     ->whereNotIn('status', ['DONE', 'ON_HOLD']);
    }

    public function scopeByDept($query, string $dept)
    {
        return $query->whereHas('activeRoutes', function ($q) use ($dept) {
            $q->where('target_dept', $dept);
        });
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('case_number', 'LIKE', "%{$search}%")
              ->orWhere('patient', 'LIKE', "%{$search}%")
              ->orWhere('lab', 'LIKE', "%{$search}%")
              ->orWhere('pan', 'LIKE', "%{$search}%");
        });
    }

    // Helpers
    public function isRoutedTo(string $dept): bool
    {
        return $this->activeRoutes()->where('target_dept', $dept)->exists();
    }

    public function isComplete(): bool
    {
        return $this->status === 'DONE';
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !$this->isComplete();
    }

    public function isDueSoon(int $hours = 24): bool
    {
        return $this->due_date &&
               $this->due_date->lte(now()->addHours($hours)) &&
               !$this->isComplete();
    }

    public function getProgressAttribute(): array
    {
        $progress = [];

        if ($this->isRoutedTo('COCR')) {
            $stages = $this->cocrStages;
            $done = $stages->where('status', 'DONE')->count();
            $progress['COCR'] = [
                'total' => $stages->count(),
                'done' => $done,
                'percent' => $stages->count() > 0 ? round(($done / $stages->count()) * 100) : 0,
            ];
        }

        if ($this->isRoutedTo('SOLIDEX')) {
            $teeth = $this->solidexTeeth;
            $done = $teeth->where('status', 'DONE')->count();
            $progress['SOLIDEX'] = [
                'total' => $teeth->count(),
                'done' => $done,
                'percent' => $teeth->count() > 0 ? round(($done / $teeth->count()) * 100) : 0,
            ];
        }

        if ($this->isRoutedTo('PRINT')) {
            $stages = $this->printStages;
            $done = $stages->where('status', 'DONE')->count();
            $progress['PRINT'] = [
                'total' => $stages->count(),
                'done' => $done,
                'percent' => $stages->count() > 0 ? round(($done / $stages->count()) * 100) : 0,
            ];
        }

        return $progress;
    }
}
