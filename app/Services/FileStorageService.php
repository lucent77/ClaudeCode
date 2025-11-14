<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class FileStorageService
{
    protected $slackApi;

    public function __construct(SlackApiService $slackApi)
    {
        $this->slackApi = $slackApi;
    }

    /**
     * Download file from Slack and store locally
     */
    public function downloadFromSlack($fileUrl, $caseId, $type, $filename)
    {
        $sanitizedFilename = $this->sanitizeFilename($filename);
        $path = $this->generateStoragePath($caseId, $type, $sanitizedFilename);

        $fullPath = storage_path('app/public/' . $path);

        $success = $this->slackApi->downloadFile($fileUrl, $fullPath);

        if ($success) {
            return $path;
        }

        return null;
    }

    /**
     * Upload file from request
     */
    public function uploadFile(UploadedFile $file, $caseId, $type)
    {
        $this->validateFile($file);

        $filename = $this->sanitizeFilename($file->getClientOriginalName());
        $path = $this->generateStoragePath($caseId, $type, $filename);

        $storedPath = $file->storeAs(
            dirname($path),
            basename($path),
            'public'
        );

        return $storedPath;
    }

    /**
     * Delete file
     */
    public function deleteFile($path)
    {
        if (!$path) {
            return false;
        }

        return Storage::disk('public')->delete($path);
    }

    /**
     * Validate file
     */
    protected function validateFile(UploadedFile $file)
    {
        $maxSize = config('services.slack.max_file_size', 52428800); // 50MB
        $allowedMimes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/zip',
            'application/x-zip-compressed',
            'model/stl',
            'application/sla',
            'application/dicom',
            'application/pdf',
        ];

        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size exceeds maximum allowed size');
        }

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('File type not allowed');
        }
    }

    /**
     * Generate storage path
     */
    protected function generateStoragePath($caseId, $type, $filename)
    {
        return sprintf(
            'cases/%d/%s/%s',
            $caseId,
            $type,
            $filename
        );
    }

    /**
     * Sanitize filename
     */
    protected function sanitizeFilename($filename)
    {
        // Remove special characters
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);

        // Add timestamp to prevent collisions
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);

        return sprintf(
            '%s_%s.%s',
            Str::slug($name),
            time(),
            $extension
        );
    }

    /**
     * Get file URL
     */
    public function getFileUrl($path)
    {
        if (!$path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * Check if file exists
     */
    public function fileExists($path)
    {
        return Storage::disk('public')->exists($path);
    }
}
