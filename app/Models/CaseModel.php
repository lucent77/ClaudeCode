<?php

namespace App\Models;

class CaseModel
{
    protected $table = 'cases';

    protected $fillable = [
        'slack_case_id',
        'patient_name',
        'assignee_name',
        'assignee_user_id',
        'created_time',
        'due_date',
        'preop_scan_date',
        'surgery_date',
        'surgery_time',
        'status',
        'priority',
        'notes',
        'slack_canvas_url',
        'metadata',
        'completed',
        'created_by',
        'arch',
        'existing_implants',
        'last_edited_by',
        'last_edited_time',
        'ready_for_surgery',
        'preop_scans',
        'postop_scans',
        'photos',
        'stls',
        'preop_cbct',
        'postop_cbct',
    ];

    protected $casts = [
        'created_time' => 'datetime',
        'due_date' => 'date',
        'preop_scan_date' => 'date',
        'surgery_date' => 'date',
        'last_edited_time' => 'datetime',
        'metadata' => 'array',
        'completed' => 'boolean',
        'ready_for_surgery' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }

    public function attachments()
    {
        return $this->hasMany(CaseAttachment::class, 'case_id');
    }

    public function steps()
    {
        return $this->hasMany(CaseStep::class, 'case_id')->orderBy('step_order');
    }

    public function activityLogs()
    {
        return $this->hasMany(CaseActivityLog::class, 'case_id')->orderBy('created_at', 'desc');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('surgery_date', '>=', date('Y-m-d'))->orderBy('surgery_date');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', date('Y-m-d'))->where('status', '!=', 'completed');
    }

    // Helper methods
    public function getDaysUntilSurgery()
    {
        if (!$this->surgery_date) {
            return null;
        }
        $now = new \DateTime();
        $surgeryDate = new \DateTime($this->surgery_date);
        $diff = $now->diff($surgeryDate);
        return $diff->days * ($diff->invert ? -1 : 1);
    }

    public function isOverdue()
    {
        if (!$this->due_date) {
            return false;
        }
        return $this->due_date < date('Y-m-d') && $this->status !== 'completed';
    }

    public function getPreopScansArray()
    {
        return $this->preop_scans ? explode(',', $this->preop_scans) : [];
    }

    public function getPostopScansArray()
    {
        return $this->postop_scans ? explode(',', $this->postop_scans) : [];
    }

    public function getPhotosArray()
    {
        return $this->photos ? explode(',', $this->photos) : [];
    }

    public function getStlsArray()
    {
        return $this->stls ? explode(',', $this->stls) : [];
    }
}
