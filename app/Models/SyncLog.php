<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

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
        'sync_details' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_seconds' => 'decimal:2',
    ];

    // Relationships
    public function triggeredBy()
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    // Helpers
    public static function startSync($syncType = 'manual', $userId = null)
    {
        return self::create([
            'sync_type' => $syncType,
            'status' => 'started',
            'triggered_by' => $userId ?? auth()->id(),
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted($casesSynced = 0, $attachmentsSynced = 0, $errors = 0)
    {
        $this->update([
            'status' => $errors > 0 ? 'partial' : 'success',
            'cases_synced' => $casesSynced,
            'attachments_synced' => $attachmentsSynced,
            'errors_count' => $errors,
            'completed_at' => now(),
            'duration_seconds' => $this->started_at->diffInSeconds(now()),
        ]);
    }

    public function markAsFailed($errorMessage)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
            'duration_seconds' => $this->started_at->diffInSeconds(now()),
        ]);
    }
}
