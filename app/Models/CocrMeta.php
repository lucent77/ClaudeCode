<?php

namespace App\Models;

use App\Traits\HasOptimisticLocking;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CocrMeta extends Model
{
    use HasFactory, HasOptimisticLocking, Auditable;

    protected $table = 'cocr_meta';

    protected $fillable = [
        'case_id',
        'note',
        'type',
        'disk_material',
        'mi',
        'shade',
        'milling_shade',
        'fc',
        'ah',
        'contact',
        'occ',
        'implant_type',
        'version',
    ];

    protected $casts = [
        'fc' => 'boolean',
        'ah' => 'boolean',
        'version' => 'integer',
    ];

    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function notes()
    {
        return $this->hasMany(Note::class, 'scope_id')->where('scope', 'COCR');
    }
}
