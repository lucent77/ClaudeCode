<?php

namespace App\Models;

class User
{
    protected $table = 'users';
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'slack_user_id',
        'avatar_url',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function cases()
    {
        // One user can be assigned to many cases
        return $this->hasMany(CaseModel::class, 'assignee_user_id');
    }

    public function activityLogs()
    {
        return $this->hasMany(CaseActivityLog::class, 'user_id');
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isManager()
    {
        return in_array($this->role, ['admin', 'manager']);
    }
}
