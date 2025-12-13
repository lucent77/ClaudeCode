<?php

namespace App\Models;

use App\Traits\HasOptimisticLocking;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolidexCaseMeta extends Model
{
    use HasFactory, HasOptimisticLocking, Auditable;

    protected $table = 'solidex_case_meta';

    protected $fillable = [
        'case_id',
        'note',
        'count',
        'implant_system',
        'lot',
        'version',
    ];

    protected $casts = [
        'count' => 'integer',
        'version' => 'integer',
    ];

    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function teeth()
    {
        return $this->hasMany(SolidexTooth::class, 'case_id', 'case_id');
    }

    public function notes()
    {
        return $this->hasMany(Note::class, 'scope_id')->where('scope', 'SOLIDEX');
    }

    public function isComplete(): bool
    {
        $teeth = $this->teeth;
        if ($teeth->isEmpty()) {
            return false;
        }
        return $teeth->every(fn ($tooth) => $tooth->status === 'DONE');
    }

    public function getCompletionPercentage(): float
    {
        $teeth = $this->teeth;
        if ($teeth->isEmpty()) {
            return 0;
        }
        $done = $teeth->where('status', 'DONE')->count();
        return round(($done / $teeth->count()) * 100, 1);
    }
}
