<?php
/**
 * Magic Rx Scanner - Image Processor Class
 *
 * Image processing including template subtraction for handwriting extraction
 * Replicates the functionality of Jimp in Node.js using PHP GD
 */

require_once __DIR__ . '/config.php';

class ImageProcessor {
    private $quality;
    private $maxWidth;
    private $maxHeight;

    public function __construct() {
        $this->quality = IMAGE_QUALITY;
        $this->maxWidth = IMAGE_MAX_WIDTH;
        $this->maxHeight = IMAGE_MAX_HEIGHT;
    }

    /**
     * Subtract template from filled document to extract handwriting
     * This replicates the Node.js Jimp analyzeHandwritingWithTemplate function
     *
     * @param string $filledPath Path to filled prescription image
     * @param string $templatePath Path to blank template image
     * @param string $outputPath Path to save extracted handwriting
     * @return array ['success' => bool, 'output_path' => string, 'base64' => string, 'error' => string]
     */
    public function subtractTemplate($filledPath, $templatePath, $outputPath = null) {
        try {
            // Load images
            $filledImage = $this->loadImage($filledPath);
            $templateImage = $this->loadImage($templatePath);

            if (!$filledImage || !$templateImage) {
                return ['success' => false, 'error' => 'Failed to load images'];
            }

            // Get dimensions
            $width = imagesx($filledImage);
            $height = imagesy($filledImage);
            $templateWidth = imagesx($templateImage);
            $templateHeight = imagesy($templateImage);

            // Resize template if dimensions don't match
            if ($width !== $templateWidth || $height !== $templateHeight) {
                $templateImage = $this->resizeImage($templateImage, $width, $height);
            }

            // Create output image
            $outputImage = imagecreatetruecolor($width, $height);
            $white = imagecolorallocate($outputImage, 255, 255, 255);
            imagefill($outputImage, 0, 0, $white);

            // Subtract template from filled image pixel by pixel
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $filledColor = imagecolorat($filledImage, $x, $y);
                    $templateColor = imagecolorat($templateImage, $x, $y);

                    $filledRGB = $this->colorToRGB($filledColor);
                    $templateRGB = $this->colorToRGB($templateColor);

                    // Calculate difference
                    $diffR = abs($filledRGB['r'] - $templateRGB['r']);
                    $diffG = abs($filledRGB['g'] - $templateRGB['g']);
                    $diffB = abs($filledRGB['b'] - $templateRGB['b']);

                    // Average difference
                    $avgDiff = ($diffR + $diffG + $diffB) / 3;

                    // Threshold: if difference is significant, it's handwriting
                    $threshold = 30; // Adjust this value for sensitivity
                    if ($avgDiff > $threshold) {
                        // Keep the filled pixel (handwriting)
                        $newColor = imagecolorallocate($outputImage, $filledRGB['r'], $filledRGB['g'], $filledRGB['b']);
                        imagesetpixel($outputImage, $x, $y, $newColor);
                    }
                    // Otherwise, leave it white (background)
                }
            }

            // Apply edge enhancement and contrast
            $outputImage = $this->enhanceHandwriting($outputImage);

            // Save output image
            if ($outputPath === null) {
                $outputPath = PROCESSED_DIR . 'handwriting_' . uniqid() . '.png';
            }

            imagepng($outputImage, $outputPath, 9);

            // Convert to base64
            $base64 = $this->imageToBase64($outputPath);

            // Clean up
            imagedestroy($filledImage);
            imagedestroy($templateImage);
            imagedestroy($outputImage);

            return [
                'success' => true,
                'output_path' => $outputPath,
                'base64' => $base64,
                'error' => ''
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Load image from file
     *
     * @param string $path Image file path
     * @return resource|false GD image resource
     */
    private function loadImage($path) {
        if (!file_exists($path)) {
            return false;
        }

        $imageInfo = getimagesize($path);
        if ($imageInfo === false) {
            return false;
        }

        $mimeType = $imageInfo['mime'];

        switch ($mimeType) {
            case 'image/jpeg':
                return imagecreatefromjpeg($path);
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/gif':
                return imagecreatefromgif($path);
            default:
                return false;
        }
    }

    /**
     * Resize image
     *
     * @param resource $image GD image resource
     * @param int $newWidth New width
     * @param int $newHeight New height
     * @return resource Resized image resource
     */
    private function resizeImage($image, $newWidth, $newHeight) {
        $resized = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG
        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, imagesx($image), imagesy($image));

        return $resized;
    }

    /**
     * Convert color integer to RGB array
     *
     * @param int $color Color integer
     * @return array RGB array
     */
    private function colorToRGB($color) {
        return [
            'r' => ($color >> 16) & 0xFF,
            'g' => ($color >> 8) & 0xFF,
            'b' => $color & 0xFF
        ];
    }

    /**
     * Enhance handwriting image (increase contrast, sharpen)
     *
     * @param resource $image GD image resource
     * @return resource Enhanced image resource
     */
    private function enhanceHandwriting($image) {
        // Increase contrast
        imagefilter($image, IMG_FILTER_CONTRAST, -20);

        // Sharpen
        $sharpenMatrix = [
            [-1, -1, -1],
            [-1, 16, -1],
            [-1, -1, -1]
        ];
        $divisor = 8;
        $offset = 0;
        imageconvolution($image, $sharpenMatrix, $divisor, $offset);

        return $image;
    }

    /**
     * Convert image to base64 data URI
     *
     * @param string $imagePath Path to image file
     * @return string Base64 data URI
     */
    public function imageToBase64($imagePath) {
        if (!file_exists($imagePath)) {
            return '';
        }

        $imageData = file_get_contents($imagePath);
        $mimeType = mime_content_type($imagePath);
        $base64 = base64_encode($imageData);

        return "data:{$mimeType};base64,{$base64}";
    }

    /**
     * Resize image to fit maximum dimensions while preserving aspect ratio
     *
     * @param string $inputPath Input image path
     * @param string $outputPath Output image path
     * @return array ['success' => bool, 'width' => int, 'height' => int, 'error' => string]
     */
    public function resizeToMaxDimensions($inputPath, $outputPath = null) {
        try {
            $image = $this->loadImage($inputPath);
            if (!$image) {
                return ['success' => false, 'error' => 'Failed to load image'];
            }

            $width = imagesx($image);
            $height = imagesy($image);

            // Calculate new dimensions if image exceeds max
            if ($width > $this->maxWidth || $height > $this->maxHeight) {
                $ratio = min($this->maxWidth / $width, $this->maxHeight / $height);
                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);

                $resized = $this->resizeImage($image, $newWidth, $newHeight);
                imagedestroy($image);
                $image = $resized;
                $width = $newWidth;
                $height = $newHeight;
            }

            // Save image
            if ($outputPath === null) {
                $outputPath = $inputPath;
            }

            $this->saveImage($image, $outputPath);
            imagedestroy($image);

            return [
                'success' => true,
                'width' => $width,
                'height' => $height,
                'error' => ''
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Save image to file
     *
     * @param resource $image GD image resource
     * @param string $path Output path
     * @return bool Success status
     */
    private function saveImage($image, $path) {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return imagejpeg($image, $path, $this->quality);
            case 'png':
                return imagepng($image, $path, 9);
            case 'gif':
                return imagegif($image, $path);
            default:
                return imagepng($image, $path, 9);
        }
    }

    /**
     * Convert image to grayscale
     *
     * @param string $inputPath Input image path
     * @param string $outputPath Output image path
     * @return array ['success' => bool, 'output_path' => string, 'error' => string]
     */
    public function convertToGrayscale($inputPath, $outputPath = null) {
        try {
            $image = $this->loadImage($inputPath);
            if (!$image) {
                return ['success' => false, 'error' => 'Failed to load image'];
            }

            imagefilter($image, IMG_FILTER_GRAYSCALE);

            if ($outputPath === null) {
                $outputPath = PROCESSED_DIR . 'grayscale_' . uniqid() . '.png';
            }

            $this->saveImage($image, $outputPath);
            imagedestroy($image);

            return [
                'success' => true,
                'output_path' => $outputPath,
                'error' => ''
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
