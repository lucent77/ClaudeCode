<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'scope',
        'scope_id',
        'tags',
        'body',
        'created_by',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeByScope($query, string $scope)
    {
        return $query->where('scope', $scope);
    }

    public function scopeByScopeId($query, int $scopeId)
    {
        return $query->where('scope_id', $scopeId);
    }

    public function scopeWithTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function scopeWithAnyTags($query, array $tags)
    {
        return $query->where(function ($q) use ($tags) {
            foreach ($tags as $tag) {
                $q->orWhereJsonContains('tags', $tag);
            }
        });
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where('body', 'LIKE', "%{$search}%");
    }

    // Helpers
    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? []);
    }

    public function addTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->tags = $tags;
            $this->save();
        }
    }

    public function removeTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        $this->tags = array_values(array_filter($tags, fn ($t) => $t !== $tag));
        $this->save();
    }

    public static function getAvailableTags(): array
    {
        return [
            '3D PRINT',
            'CR',
            'COCR',
            'SOLIDEX',
            'URGENT',
            'QC ISSUE',
            'REDO',
            'CUSTOMER REQUEST',
            'DESIGN CHANGE',
            'MATERIAL ISSUE',
        ];
    }
}
