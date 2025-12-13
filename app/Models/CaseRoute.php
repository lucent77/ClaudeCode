<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id',
        'target_dept',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeByDept($query, string $dept)
    {
        return $query->where('target_dept', $dept);
    }
}
