<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'actor_user_id',
        'entity_type',
        'entity_id',
        'action',
        'diff',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'diff' => 'array',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    // Scopes
    public function scopeByEntity($query, string $entityType, int $entityId)
    {
        return $query->where('entity_type', $entityType)
                     ->where('entity_id', $entityId);
    }

    public function scopeByActor($query, int $userId)
    {
        return $query->where('actor_user_id', $userId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // Helpers
    public static function log(
        string $entityType,
        int $entityId,
        string $action,
        ?array $diff = null,
        ?int $actorId = null
    ): self {
        return self::create([
            'actor_user_id' => $actorId ?? auth()->id(),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'diff' => $diff,
            'ip_address' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? '', 0, 500),
        ]);
    }

    public function getFormattedDiff(): array
    {
        $diff = $this->diff ?? [];
        $formatted = [];

        foreach ($diff as $field => $changes) {
            if (is_array($changes) && isset($changes['old'], $changes['new'])) {
                $formatted[] = [
                    'field' => $field,
                    'old' => $changes['old'],
                    'new' => $changes['new'],
                ];
            }
        }

        return $formatted;
    }
}
