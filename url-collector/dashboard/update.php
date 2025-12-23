<?php
/**
 * URL Collector - Update Link Handler
 *
 * Handles POST requests to update link attributes:
 * - review_status (inbox/keep/archive)
 * - interest_score (0-100)
 * - category
 * - note
 * - reset_analysis (re-queue for analysis)
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$config = require APP_ROOT . '/config/config.php';

require_once APP_ROOT . '/lib/db.php';
require_once APP_ROOT . '/lib/security.php';
require_once APP_ROOT . '/lib/util.php';

// Initialize
Database::init($config['db']);
Security::init($config);
Util::init($config);
Security::startSession();

// Require login
Security::requireLogin('login.php');

// Only POST allowed
if (!Util::isPost()) {
    Util::setFlash('error', 'Invalid request method');
    Util::redirect('index.php');
}

// Verify CSRF token
$csrfToken = $_POST['csrf_token'] ?? '';
if (!Security::verifyCsrfToken($csrfToken)) {
    Util::logWarning('CSRF token verification failed from IP: ' . Security::getClientIp());
    Util::setFlash('error', 'Security verification failed. Please try again.');
    Util::redirect('index.php');
}

// Get link ID
$id = Security::sanitizeInt($_POST['id'] ?? null, 1);
if (!$id) {
    Util::setFlash('error', 'Invalid link ID');
    Util::redirect('index.php');
}

// Check if link exists
$link = Database::fetch('SELECT id, review_status FROM links WHERE id = ?', [$id]);
if (!$link) {
    Util::setFlash('error', 'Link not found');
    Util::redirect('index.php');
}

// Redirect URL
$redirect = $_POST['redirect'] ?? 'index.php';
// Validate redirect is local
if (!str_starts_with($redirect, '/') && !str_starts_with($redirect, 'index.php') && !str_starts_with($redirect, 'edit.php')) {
    $redirect = 'index.php';
}

try {
    $updateData = [];
    $messages = [];

    // Handle reset_analysis request
    if (!empty($_POST['reset_analysis'])) {
        $updateData['status'] = 'pending';
        $updateData['retry_count'] = 0;
        $updateData['error_message'] = null;
        $updateData['analyzed_at'] = null;
        $messages[] = 'Re-queued for analysis';
    }

    // Update review_status
    if (isset($_POST['review_status'])) {
        $newStatus = Security::validateReviewStatus($_POST['review_status']);
        if ($newStatus !== null) {
            $updateData['review_status'] = $newStatus;

            // Set reviewed_at when moving out of inbox
            if ($newStatus !== 'inbox' && $link['review_status'] === 'inbox') {
                $updateData['reviewed_at'] = Util::now();
            }

            $messages[] = "Marked as {$newStatus}";
        }
    }

    // Update interest_score
    if (isset($_POST['interest_score']) && $_POST['interest_score'] !== '') {
        $score = Security::sanitizeInt($_POST['interest_score'], 0, 100);
        if ($score !== null) {
            $updateData['interest_score'] = $score;
            $messages[] = "Score set to {$score}";
        }
    } elseif (isset($_POST['interest_score']) && $_POST['interest_score'] === '') {
        // Allow clearing the score
        $updateData['interest_score'] = null;
    }

    // Update category
    if (isset($_POST['category'])) {
        $category = Security::validateCategory($_POST['category'], $config['categories']);
        if ($category !== null || $_POST['category'] === '') {
            $updateData['category'] = $category;
            if ($category) {
                $messages[] = "Category set to {$category}";
            }
        }
    }

    // Update note
    if (isset($_POST['note'])) {
        $note = trim($_POST['note']);
        $note = mb_substr($note, 0, 2000); // Limit length
        $updateData['note'] = !empty($note) ? $note : null;
    }

    // Perform update if there are changes
    if (!empty($updateData)) {
        Database::update('links', $updateData, 'id = ?', [$id]);

        $message = !empty($messages) ? implode('. ', $messages) : 'Link updated successfully';
        Util::logInfo("Link {$id} updated: " . implode(', ', array_keys($updateData)));

        // AJAX response
        if (Util::isAjax()) {
            Security::jsonSuccess(['message' => $message]);
        }

        Util::setFlash('success', $message);
    } else {
        if (Util::isAjax()) {
            Security::jsonSuccess(['message' => 'No changes made']);
        }
    }

} catch (PDOException $e) {
    Util::logError("Database error updating link {$id}: " . $e->getMessage());

    if (Util::isAjax()) {
        Security::jsonError('Database error occurred', 500);
    }

    Util::setFlash('error', 'Failed to update link');
} catch (Throwable $e) {
    Util::logError("Error updating link {$id}: " . $e->getMessage());

    if (Util::isAjax()) {
        Security::jsonError('An error occurred', 500);
    }

    Util::setFlash('error', 'An error occurred');
}

Util::redirect($redirect);
