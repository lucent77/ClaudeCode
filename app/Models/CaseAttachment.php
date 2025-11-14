<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id',
        'type',
        'label',
        'slack_file_id',
        'file_url',
        'local_path',
        'preview_url',
        'filename',
        'mimetype',
        'filesize',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'filesize' => 'integer',
    ];

    // Relationships
    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    // Scopes
    public function scopePhotos($query)
    {
        return $query->where('type', 'photo');
    }

    public function scopeStls($query)
    {
        return $query->where('type', 'stl');
    }

    public function scopeCbcts($query)
    {
        return $query->where('type', 'cbct');
    }

    // Helpers
    public function isImage()
    {
        return $this->type === 'photo' ||
               in_array($this->mimetype, ['image/jpeg', 'image/png', 'image/gif']);
    }

    public function getDownloadUrl()
    {
        if ($this->local_path) {
            return url('storage/' . $this->local_path);
        }
        return route('api.attachments.proxy', ['id' => $this->id]);
    }

    public function getThumbnailUrl()
    {
        if ($this->preview_url) {
            return $this->preview_url;
        }
        if ($this->isImage() && $this->local_path) {
            return url('storage/' . $this->local_path);
        }
        return null;
    }

    public function getFileSizeFormatted()
    {
        if (!$this->filesize) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->filesize;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
