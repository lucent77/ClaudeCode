<?php

namespace App\Models;

class CaseAttachment
{
    protected $table = 'case_attachments';

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
        'filesize' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
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

    public function isImage()
    {
        return $this->type === 'photo' ||
               in_array(strtolower(pathinfo($this->filename, PATHINFO_EXTENSION)),
                        ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    public function getDownloadUrl()
    {
        if ($this->local_path && file_exists(storage_path('app/' . $this->local_path))) {
            return url('storage/' . $this->local_path);
        }
        return $this->file_url;
    }
}
