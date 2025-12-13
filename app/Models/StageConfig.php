<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StageConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'dept',
        'stage',
        'order_index',
        'enable_subtasks',
        'allowed_fields',
        'required_fields',
        'active',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'enable_subtasks' => 'boolean',
        'allowed_fields' => 'array',
        'required_fields' => 'array',
        'active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeByDept($query, string $dept)
    {
        return $query->where('dept', $dept);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }

    public static function getStagesForDept(string $dept): array
    {
        return self::active()
            ->byDept($dept)
            ->ordered()
            ->pluck('stage')
            ->toArray();
    }

    public static function getConfigForStage(string $dept, string $stage): ?self
    {
        return self::active()
            ->byDept($dept)
            ->where('stage', $stage)
            ->first();
    }

    public function isFieldRequired(string $field): bool
    {
        return in_array($field, $this->required_fields ?? []);
    }

    public function isFieldAllowed(string $field): bool
    {
        $allowed = $this->allowed_fields ?? [];
        return empty($allowed) || in_array($field, $allowed);
    }
}
