<?php

namespace App\Models;

class CaseActivityLog
{
    protected $table = 'case_activity_logs';

    protected $fillable = [
        'case_id',
        'user_id',
        'action',
        'description',
        'changes',
        'ip_address',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    public $timestamps = false; // Only created_at

    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function log($caseId, $action, $description, $changes = null, $userId = null, $ipAddress = null)
    {
        $log = new self();
        $log->case_id = $caseId;
        $log->user_id = $userId;
        $log->action = $action;
        $log->description = $description;
        $log->changes = $changes;
        $log->ip_address = $ipAddress ?: ($_SERVER['REMOTE_ADDR'] ?? null);
        $log->save();

        return $log;
    }
}
