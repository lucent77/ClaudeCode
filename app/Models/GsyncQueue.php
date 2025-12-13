<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GsyncQueue extends Model
{
    use HasFactory;

    protected $table = 'gsync_queue';

    protected $fillable = [
        'gsheet_link_id',
        'payload',
        'direction',
        'status',
        'error_text',
        'retry_count',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'retry_count' => 'integer',
        'processed_at' => 'datetime',
    ];

    public function gsheetLink()
    {
        return $this->belongsTo(GsheetLink::class, 'gsheet_link_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'PROCESSING');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'FAILED');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'COMPLETED');
    }

    public function scopeRetryable($query, int $maxRetries = 3)
    {
        return $query->where('status', 'FAILED')
                     ->where('retry_count', '<', $maxRetries);
    }

    public function markProcessing(): void
    {
        $this->update(['status' => 'PROCESSING']);
    }

    public function markCompleted(): void
    {
        $this->update([
            'status' => 'COMPLETED',
            'processed_at' => now(),
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'FAILED',
            'error_text' => $error,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    public function canRetry(int $maxRetries = 3): bool
    {
        return $this->status === 'FAILED' && $this->retry_count < $maxRetries;
    }
}
