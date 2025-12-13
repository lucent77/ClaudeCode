<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GsheetLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'dept',
        'sheet_id',
        'tab_name',
        'mapping',
        'direction',
        'last_sync_at',
        'active',
    ];

    protected $casts = [
        'mapping' => 'array',
        'last_sync_at' => 'datetime',
        'active' => 'boolean',
    ];

    public function syncQueue()
    {
        return $this->hasMany(GsyncQueue::class, 'gsheet_link_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeByDept($query, string $dept)
    {
        return $query->where('dept', $dept);
    }

    public function scopeByDirection($query, string $direction)
    {
        return $query->where('direction', $direction);
    }

    public function scopePullable($query)
    {
        return $query->whereIn('direction', ['PULL', 'BIDI']);
    }

    public function scopePushable($query)
    {
        return $query->whereIn('direction', ['PUSH', 'BIDI']);
    }

    public function canPull(): bool
    {
        return in_array($this->direction, ['PULL', 'BIDI']);
    }

    public function canPush(): bool
    {
        return in_array($this->direction, ['PUSH', 'BIDI']);
    }

    public function getColumnMapping(): array
    {
        return $this->mapping['columns'] ?? [];
    }

    public function getKeyColumn(): ?string
    {
        return $this->mapping['key_column'] ?? null;
    }
}
