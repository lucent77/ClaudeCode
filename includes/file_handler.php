<?php
/**
 * Magic Rx Scanner - File Handler Class
 *
 * Secure file upload, validation, and management
 */

require_once __DIR__ . '/config.php';

class FileHandler {
    private $allowedTypes;
    private $allowedExtensions;
    private $maxFileSize;

    public function __construct() {
        $this->allowedTypes = ALLOWED_IMAGE_TYPES;
        $this->allowedExtensions = ALLOWED_EXTENSIONS;
        $this->maxFileSize = UPLOAD_MAX_SIZE;
    }

    /**
     * Upload and validate a file
     *
     * @param array $file $_FILES array element
     * @param string $destination Destination directory
     * @param string $type File type ('document' or 'template')
     * @return array ['success' => bool, 'filename' => string, 'path' => string, 'error' => string]
     */
    public function uploadFile($file, $destination, $type = 'document') {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'error' => 'No file uploaded'];
        }

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => $this->getUploadErrorMessage($file['error'])];
        }

        // Validate file size
        if ($file['size'] > $this->maxFileSize) {
            return [
                'success' => false,
                'error' => 'File size exceeds maximum allowed size (' . ($this->maxFileSize / 1024 / 1024) . 'MB)'
            ];
        }

        // Validate file type
        $validation = $this->validateFileType($file);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }

        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $uniqueFilename = $this->generateUniqueFilename($extension);

        // Ensure destination directory exists
        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        // Move uploaded file
        $filePath = $destination . $uniqueFilename;
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => false, 'error' => 'Failed to save uploaded file'];
        }

        // Set file permissions
        chmod($filePath, 0644);

        return [
            'success' => true,
            'filename' => $uniqueFilename,
            'original_filename' => $file['name'],
            'path' => $filePath,
            'size' => $file['size']
        ];
    }

    /**
     * Validate file type and extension
     *
     * @param array $file $_FILES array element
     * @return array ['valid' => bool, 'error' => string]
     */
    private function validateFileType($file) {
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedTypes)) {
            return [
                'valid' => false,
                'error' => 'Invalid file type. Allowed types: ' . implode(', ', $this->allowedTypes)
            ];
        }

        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            return [
                'valid' => false,
                'error' => 'Invalid file extension. Allowed extensions: ' . implode(', ', $this->allowedExtensions)
            ];
        }

        // Additional security: Check if it's actually an image
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return ['valid' => false, 'error' => 'File is not a valid image'];
        }

        return ['valid' => true, 'error' => ''];
    }

    /**
     * Generate a unique filename
     *
     * @param string $extension File extension
     * @return string Unique filename
     */
    private function generateUniqueFilename($extension) {
        return uniqid('rx_', true) . '_' . time() . '.' . $extension;
    }

    /**
     * Delete a file
     *
     * @param string $filePath Full path to the file
     * @return bool Success status
     */
    public function deleteFile($filePath) {
        if (file_exists($filePath) && is_file($filePath)) {
            return unlink($filePath);
        }
        return false;
    }

    /**
     * Delete files associated with a prescription
     *
     * @param array $prescription Prescription data with file paths
     * @return bool Success status
     */
    public function deletePrescriptionFiles($prescription) {
        $success = true;

        if (!empty($prescription['file_path']) && file_exists($prescription['file_path'])) {
            $success = $success && $this->deleteFile($prescription['file_path']);
        }

        if (!empty($prescription['template_path']) && file_exists($prescription['template_path'])) {
            $success = $success && $this->deleteFile($prescription['template_path']);
        }

        return $success;
    }

    /**
     * Get upload error message
     *
     * @param int $errorCode Upload error code
     * @return string Error message
     */
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'File size exceeds maximum allowed size';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }

    /**
     * Clean up old files (for cron job)
     *
     * @param string $directory Directory to clean
     * @param int $daysOld Files older than this many days will be deleted
     * @return int Number of files deleted
     */
    public function cleanupOldFiles($directory, $daysOld = 30) {
        $deletedCount = 0;
        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);

        if (!is_dir($directory)) {
            return 0;
        }

        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $directory . $file;
            if (is_file($filePath) && filemtime($filePath) < $cutoffTime) {
                if (unlink($filePath)) {
                    $deletedCount++;
                }
            }
        }

        return $deletedCount;
    }

    /**
     * Get file MIME type
     *
     * @param string $filePath Path to file
     * @return string MIME type
     */
    public function getMimeType($filePath) {
        if (!file_exists($filePath)) {
            return '';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return $mimeType;
    }
}
