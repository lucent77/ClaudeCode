<?php

namespace App\Models;

class CaseStep
{
    protected $table = 'case_steps';

    protected $fillable = [
        'case_id',
        'step_name',
        'step_order',
        'status',
        'assigned_to',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function getDurationMinutes()
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        $start = new \DateTime($this->started_at);
        $end = new \DateTime($this->completed_at);
        $diff = $start->diff($end);

        return ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
    }
}
