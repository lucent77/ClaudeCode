<?php

namespace App\Models;

use App\Traits\HasOptimisticLocking;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintMeta extends Model
{
    use HasFactory, HasOptimisticLocking, Auditable;

    protected $table = 'print_meta';

    protected $fillable = [
        'case_id',
        'note',
        'type',
        'implant',
        'version',
    ];

    protected $casts = [
        'version' => 'integer',
    ];

    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function stages()
    {
        return $this->hasMany(PrintStage::class, 'case_id', 'case_id');
    }

    public function notes()
    {
        return $this->hasMany(Note::class, 'scope_id')->where('scope', 'PRINT');
    }

    public function isComplete(): bool
    {
        $stages = $this->stages;
        if ($stages->isEmpty()) {
            return false;
        }
        return $stages->every(fn ($stage) => $stage->status === 'DONE');
    }
}
