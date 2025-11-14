<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CaseModel extends Model
{
    use HasFactory, SoftDeletes;

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
        'status',
        'priority',
        'notes',
        'slack_canvas_url',
        'metadata',
    ];

    protected $casts = [
        'created_time' => 'datetime',
        'due_date' => 'date',
        'preop_scan_date' => 'date',
        'surgery_date' => 'date',
        'metadata' => 'array',
    ];

    // Relationships
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
        return $this->hasMany(CaseActivityLog::class, 'case_id')->latest();
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

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                     ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('surgery_date', '>=', now())
                     ->whereNotIn('status', ['completed', 'cancelled']);
    }

    // Helpers
    public function isOverdue()
    {
        return $this->due_date &&
               $this->due_date->isPast() &&
               !in_array($this->status, ['completed', 'cancelled']);
    }

    public function getPhotos()
    {
        return $this->attachments()->where('type', 'photo')->get();
    }

    public function getStlFiles()
    {
        return $this->attachments()->where('type', 'stl')->get();
    }

    public function getCbctFiles()
    {
        return $this->attachments()->where('type', 'cbct')->get();
    }
}
