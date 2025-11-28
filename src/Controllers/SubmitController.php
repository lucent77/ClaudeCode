<?php
/**
 * Submit Controller - Anonymous Feedback Submission
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ContentFilter;
use App\Services\UlidGenerator;
use App\Services\RateLimiter;

class SubmitController extends BaseController
{
    private array $categories = [
        'process' => [
            'label' => 'Process',
            'tip' => 'Workflows, procedures, or how work gets done'
        ],
        'tools' => [
            'label' => 'Tools & Facilities',
            'tip' => 'Software, equipment, workspace, or resources'
        ],
        'communication' => [
            'label' => 'Communication',
            'tip' => 'Information flow, meetings, or clarity'
        ],
        'leadership' => [
            'label' => 'Leadership',
            'tip' => 'Management practices, decisions, or direction'
        ],
        'benefits' => [
            'label' => 'Benefits & Policy',
            'tip' => 'Compensation, perks, or company policies'
        ],
        'other' => [
            'label' => 'Other',
            'tip' => 'Anything else that doesn\'t fit above'
        ],
    ];

    public function index(): void
    {
        $this->render('submit/index', [
            'title' => 'Submit Feedback - Creodent Voice',
            'categories' => $this->categories,
            'csrf' => $this->csrfToken(),
            'maxFileSize' => $this->getMaxFileSize(),
        ]);
    }

    public function store(): void
    {
        // Validate CSRF
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token. Please try again.');
            $this->redirect('/submit');
        }

        if (!$this->db) {
            $this->flash('error', 'Service temporarily unavailable. Please try again later.');
            $this->redirect('/submit');
        }

        // Rate limiting
        $clientIp = $this->getClientIp();
        $rateLimiter = new RateLimiter($this->db, 3600, (int) ($_ENV['RATE_LIMIT_POSTS'] ?? 5));

        if ($rateLimiter->isLimited($clientIp, 'submit')) {
            $this->flash('error', 'You\'ve submitted too many posts recently. Please try again later.');
            $this->redirect('/submit');
        }

        // Get and validate input
        $category = $this->input('category', '');
        $title = trim($this->input('title', ''));
        $body = trim($this->input('body', ''));
        $departmentHint = trim($this->input('department_hint', ''));

        $errors = [];

        if (!array_key_exists($category, $this->categories)) {
            $errors['category'] = 'Please select a valid category.';
        }

        if (empty($title)) {
            $errors['title'] = 'Title is required.';
        } elseif (strlen($title) > 160) {
            $errors['title'] = 'Title must be 160 characters or less.';
        } elseif (strlen($title) < 10) {
            $errors['title'] = 'Title must be at least 10 characters.';
        }

        if (empty($body)) {
            $errors['body'] = 'Description is required.';
        } elseif (strlen($body) > 5000) {
            $errors['body'] = 'Description must be 5000 characters or less.';
        } elseif (strlen($body) < 30) {
            $errors['body'] = 'Description must be at least 30 characters.';
        }

        if (strlen($departmentHint) > 100) {
            $errors['department_hint'] = 'Department hint must be 100 characters or less.';
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = compact('category', 'title', 'body', 'departmentHint');
            $this->redirect('/submit');
        }

        // Content filtering
        $filter = new ContentFilter();
        $titleAnalysis = $filter->analyze($title);
        $bodyAnalysis = $filter->analyze($body);

        // Block if content score is too high
        if ($titleAnalysis['blocked'] || $bodyAnalysis['blocked']) {
            $this->flash('error', 'Your submission contains content that violates our community guidelines. Please revise and try again.');
            $_SESSION['form_data'] = compact('category', 'title', 'body', 'departmentHint');
            $this->redirect('/submit');
        }

        // Generate ULID
        $ulid = (new UlidGenerator())->generate();

        // Prepare IP for storage (binary format)
        $ipBinary = inet_pton($clientIp) ?: null;

        // Insert post
        $stmt = $this->db->prepare("
            INSERT INTO posts (public_id, category, title, body, department_hint, created_ip, created_ua, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'queued')
        ");
        $stmt->execute([
            $ulid,
            $category,
            $title,
            $body,
            $departmentHint ?: null,
            $ipBinary,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);

        $postId = (int) $this->db->lastInsertId();

        // Store content flags
        $allFlags = array_merge($titleAnalysis['flags'], $bodyAnalysis['flags']);
        if (!empty($allFlags)) {
            $stmt = $this->db->prepare("
                INSERT INTO content_flags (post_id, rule_code, matched_snippet)
                VALUES (?, ?, ?)
            ");
            foreach ($allFlags as $flag) {
                $stmt->execute([$postId, $flag['rule_code'], $flag['matched_snippet']]);
            }
        }

        // Handle file upload
        if (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $this->handleUpload($postId, $_FILES['attachment']);
        }

        // Record rate limit hit
        $rateLimiter->hit($clientIp, 'submit');

        // Clear form data
        unset($_SESSION['form_errors'], $_SESSION['form_data']);

        $this->redirect('/thanks/' . $ulid);
    }

    public function thanks(string $public_id): void
    {
        $this->render('submit/thanks', [
            'title' => 'Thank You - Creodent Voice',
            'publicId' => $public_id,
        ]);
    }

    private function handleUpload(int $postId, array $file): void
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        $maxSize = (int) ($_ENV['UPLOAD_MAX_SIZE'] ?? 3145728); // 3MB

        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes, true)) {
            return; // Silently ignore invalid file types
        }

        // Validate file size
        if ($file['size'] > $maxSize) {
            return;
        }

        // Generate unique filename
        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
            default => 'bin',
        };

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $uploadPath = PUBLIC_PATH . '/uploads/' . $filename;

        // Move file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return;
        }

        // Store attachment record
        $stmt = $this->db->prepare("
            INSERT INTO attachments (post_id, filename, path, mime, size)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $postId,
            $file['name'],
            '/uploads/' . $filename,
            $mimeType,
            $file['size'],
        ]);
    }

    private function getMaxFileSize(): int
    {
        $uploadMax = $this->parseSize(ini_get('upload_max_filesize'));
        $postMax = $this->parseSize(ini_get('post_max_size'));
        $configMax = (int) ($_ENV['UPLOAD_MAX_SIZE'] ?? 3145728);

        return min($uploadMax, $postMax, $configMax);
    }

    private function parseSize(string $size): int
    {
        $unit = strtolower(substr($size, -1));
        $value = (int) $size;

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
}
