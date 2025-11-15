<?php

namespace App\Models;

class SyncLog
{
    protected $table = 'sync_logs';

    protected $fillable = [
        'sync_type',
        'status',
        'cases_synced',
        'attachments_synced',
        'errors_count',
        'error_message',
        'sync_details',
        'triggered_by',
        'started_at',
        'completed_at',
        'duration_seconds',
    ];

    protected $casts = [
        'cases_synced' => 'integer',
        'attachments_synced' => 'integer',
        'errors_count' => 'integer',
        'sync_details' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_seconds' => 'float',
    ];

    public $timestamps = false; // Manual timestamp management

    public function triggeredByUser()
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('started_at', '>=', date('Y-m-d H:i:s', strtotime("-{$days} days")));
    }

    public static function startSync($type = 'manual', $userId = null)
    {
        $log = new self();
        $log->sync_type = $type;
        $log->status = 'started';
        $log->triggered_by = $userId;
        $log->started_at = date('Y-m-d H:i:s');
        $log->save();

        return $log;
    }

    public function complete($status = 'success', $details = [])
    {
        $this->status = $status;
        $this->completed_at = date('Y-m-d H:i:s');

        if ($this->started_at && $this->completed_at) {
            $start = new \DateTime($this->started_at);
            $end = new \DateTime($this->completed_at);
            $this->duration_seconds = $end->getTimestamp() - $start->getTimestamp();
        }

        if (!empty($details)) {
            $this->sync_details = array_merge($this->sync_details ?? [], $details);
        }

        $this->save();
        return $this;
    }

    public function addError($errorMessage)
    {
        $this->errors_count++;
        $this->error_message = $errorMessage;
        $this->save();
    }

    public function incrementCasesSynced($count = 1)
    {
        $this->cases_synced += $count;
        $this->save();
    }

    public function incrementAttachmentsSynced($count = 1)
    {
        $this->attachments_synced += $count;
        $this->save();
    }
}
