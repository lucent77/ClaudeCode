<?php

namespace App\Traits;

use App\Models\AuditLog;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->logAudit('created', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            $original = [];

            foreach (array_keys($changes) as $key) {
                if ($key === 'updated_at') {
                    continue;
                }
                $original[$key] = $model->getOriginal($key);
            }

            if (!empty($original)) {
                $diff = [];
                foreach ($original as $key => $oldValue) {
                    $diff[$key] = [
                        'old' => $oldValue,
                        'new' => $changes[$key] ?? null,
                    ];
                }
                $model->logAudit('updated', $diff);
            }
        });

        static::deleted(function ($model) {
            $model->logAudit('deleted', $model->getAttributes());
        });
    }

    public function logAudit(string $action, ?array $diff = null, ?array $newData = null): void
    {
        $entityType = class_basename($this);
        $entityId = $this->getKey();

        if ($action === 'created' && $newData) {
            $diff = [];
            foreach ($newData as $key => $value) {
                if (in_array($key, ['created_at', 'updated_at', 'password'])) {
                    continue;
                }
                $diff[$key] = [
                    'old' => null,
                    'new' => $value,
                ];
            }
        }

        AuditLog::log($entityType, $entityId, $action, $diff);
    }

    public function auditLogs()
    {
        return AuditLog::where('entity_type', class_basename($this))
            ->where('entity_id', $this->getKey())
            ->orderByDesc('created_at');
    }

    public function getAuditHistory(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->auditLogs()->get();
    }
}
