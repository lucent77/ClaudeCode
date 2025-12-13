<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'dept',
        'initials',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'active' => 'boolean',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // JWT methods
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'role' => $this->role,
            'dept' => $this->dept,
            'initials' => $this->initials,
        ];
    }

    // Role checks
    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    public function isLead(): bool
    {
        return $this->role === 'LEAD';
    }

    public function isWorker(): bool
    {
        return $this->role === 'WORKER';
    }

    public function canAccessDept(string $dept): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->dept === 'MULTI') {
            return true;
        }

        return $this->dept === $dept;
    }

    // Relationships
    public function cocrStages()
    {
        return $this->hasMany(CocrStage::class, 'assignee_user_id');
    }

    public function solidexStages()
    {
        return $this->hasMany(SolidexToothStage::class, 'assignee_user_id');
    }

    public function printStages()
    {
        return $this->hasMany(PrintStage::class, 'assignee_user_id');
    }

    public function notes()
    {
        return $this->hasMany(Note::class, 'created_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'actor_user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeByDept($query, string $dept)
    {
        return $query->where(function ($q) use ($dept) {
            $q->where('dept', $dept)
              ->orWhere('dept', 'MULTI');
        });
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }
}
