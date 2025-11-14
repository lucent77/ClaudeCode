<?php
/**
 * File Classifier
 * Automatically classifies files based on naming patterns
 * Creodent AoX Elevate Dashboard
 */

class FileClassifier {

    /**
     * Classify file based on filename and extension
     *
     * @param string $filename
     * @return string File type (photo, stl, preop_scan, etc.)
     */
    public static function classify($filename) {
        $filename = strtolower($filename);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $patterns = FILE_PATTERNS;

        // Priority order for classification (more specific first)
        $priorityOrder = [
            'preop_cbct',
            'postop_cbct',
            'preop_scan',
            'postop_scan',
            'design',
            'radiograph',
            'stl',
            'photo'
        ];

        foreach ($priorityOrder as $type) {
            if (!isset($patterns[$type])) continue;

            $pattern = $patterns[$type];

            // Check extension first
            if (in_array($extension, $pattern['extensions'])) {
                // Then check keywords
                foreach ($pattern['keywords'] as $keyword) {
                    if (stripos($filename, $keyword) !== false) {
                        return $type;
                    }
                }

                // If extension matches but no keyword, check if it's a generic match
                if ($type === 'stl' && in_array($extension, ['stl', 'obj', 'ply'])) {
                    return 'stl';
                }
                if ($type === 'photo' && in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'heic'])) {
                    return 'photo';
                }
            }
        }

        return 'other';
    }

    /**
     * Extract case code from filename
     * Supports patterns like:
     * - Nicolas_S_2025_11_18_preop_scan.stl
     * - Tom_Bischof_postop_cbct.zip
     * - Margaret_Colarusso_photos_01.jpg
     *
     * @param string $filename
     * @return string|null Case code or null if not found
     */
    public static function extractCaseCode($filename) {
        $filename = str_replace(['.stl', '.zip', '.jpg', '.jpeg', '.png', '.dcm', '.obj', '.ply'], '', $filename);
        $filename = preg_replace('/_(preop|postop|photo|scan|cbct|design|final|initial).*$/i', '', $filename);

        // Pattern 1: Name_Date format (e.g., Nicolas_S_2025_11_18)
        if (preg_match('/^([A-Za-z_]+)_(\d{4}_\d{2}_\d{2})/', $filename, $matches)) {
            return $matches[1] . '_' . $matches[2];
        }

        // Pattern 2: Name only (e.g., Tom_Bischof)
        if (preg_match('/^([A-Za-z_]+)/', $filename, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract patient name from case code
     *
     * @param string $caseCode
     * @return string Patient name
     */
    public static function extractPatientName($caseCode) {
        // Remove date portion if exists
        $name = preg_replace('/_\d{4}_\d{2}_\d{2}$/', '', $caseCode);

        // Replace underscores with spaces
        $name = str_replace('_', ' ', $name);

        // Capitalize each word
        return ucwords($name);
    }

    /**
     * Extract surgery date from case code
     *
     * @param string $caseCode
     * @return string|null Surgery date (YYYY-MM-DD) or null
     */
    public static function extractSurgeryDate($caseCode) {
        if (preg_match('/(\d{4})_(\d{2})_(\d{2})$/', $caseCode, $matches)) {
            return $matches[1] . '-' . $matches[2] . '-' . $matches[3];
        }
        return null;
    }

    /**
     * Extract arch from filename
     *
     * @param string $filename
     * @return string|null 'Upper', 'Lower', 'Both', or null
     */
    public static function extractArch($filename) {
        $filename = strtolower($filename);

        if (stripos($filename, 'upper') !== false) {
            return 'Upper';
        }
        if (stripos($filename, 'lower') !== false) {
            return 'Lower';
        }
        if (stripos($filename, 'both') !== false || stripos($filename, 'full') !== false) {
            return 'Both';
        }

        return null;
    }

    /**
     * Determine if file is pre-operative
     *
     * @param string $filename
     * @return bool
     */
    public static function isPreOp($filename) {
        $filename = strtolower($filename);
        $preOpKeywords = ['preop', 'pre-op', 'pre_op', 'initial', 'before'];

        foreach ($preOpKeywords as $keyword) {
            if (stripos($filename, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if file is post-operative
     *
     * @param string $filename
     * @return bool
     */
    public static function isPostOp($filename) {
        $filename = strtolower($filename);
        $postOpKeywords = ['postop', 'post-op', 'post_op', 'final', 'after'];

        foreach ($postOpKeywords as $keyword) {
            if (stripos($filename, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get file icon based on file type
     *
     * @param string $fileType
     * @return string SVG icon class or emoji
     */
    public static function getFileIcon($fileType) {
        $icons = [
            'photo' => '📷',
            'stl' => '🧊',
            'preop_scan' => '🔬',
            'postop_scan' => '✅',
            'preop_cbct' => '🦴',
            'postop_cbct' => '🏆',
            'design' => '🎨',
            'radiograph' => '📊',
            'other' => '📄'
        ];

        return $icons[$fileType] ?? $icons['other'];
    }

    /**
     * Get human-readable file type name
     *
     * @param string $fileType
     * @return string
     */
    public static function getFileTypeName($fileType) {
        $names = [
            'photo' => 'Photo',
            'stl' => 'STL Model',
            'preop_scan' => 'Pre-op Scan',
            'postop_scan' => 'Post-op Scan',
            'preop_cbct' => 'Pre-op CBCT',
            'postop_cbct' => 'Post-op CBCT',
            'design' => 'Design File',
            'radiograph' => 'Radiograph',
            'other' => 'Other'
        ];

        return $names[$fileType] ?? 'Unknown';
    }
}

?>
