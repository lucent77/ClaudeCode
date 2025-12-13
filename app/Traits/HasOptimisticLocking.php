<?php

namespace App\Traits;

use App\Exceptions\OptimisticLockException;
use Illuminate\Database\Eloquent\Builder;

trait HasOptimisticLocking
{
    public static function bootHasOptimisticLocking(): void
    {
        static::updating(function ($model) {
            if ($model->isDirty() && $model->getOriginal('version') !== null) {
                $model->version = $model->getOriginal('version') + 1;
            }
        });
    }

    public function updateWithLock(array $attributes, int $expectedVersion): bool
    {
        $query = $this->newQuery()
            ->where($this->getKeyName(), $this->getKey())
            ->where('version', $expectedVersion);

        $attributes['version'] = $expectedVersion + 1;
        $attributes['updated_at'] = now();

        $affected = $query->update($attributes);

        if ($affected === 0) {
            $current = $this->fresh();
            if ($current && $current->version !== $expectedVersion) {
                throw new OptimisticLockException(
                    'This record has been modified by another user. Please refresh and try again.',
                    $current->version
                );
            }
            return false;
        }

        $this->fill($attributes);
        $this->syncOriginal();

        return true;
    }

    public function scopeWithVersion(Builder $query, int $version): Builder
    {
        return $query->where('version', $version);
    }

    public function getVersionForHeader(): string
    {
        return (string) $this->version;
    }

    public function checkVersion(int $expectedVersion): bool
    {
        return $this->version === $expectedVersion;
    }
}
