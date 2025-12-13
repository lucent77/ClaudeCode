<?php
/**
 * Helper Functions
 */

/**
 * Escape HTML output
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF hidden input
 */
function csrfField(): string
{
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . auth()->generateCsrfToken() . '">';
}

/**
 * Get CSRF token for AJAX
 */
function csrfToken(): string
{
    return auth()->generateCsrfToken();
}

/**
 * Redirect to URL
 */
function redirect(string $url, int $status = 302): void
{
    header('Location: ' . $url, true, $status);
    exit;
}

/**
 * Redirect back
 */
function back(): void
{
    $referer = $_SERVER['HTTP_REFERER'] ?? APP_URL;
    redirect($referer);
}

/**
 * Flash message
 */
function flash(string $key, ?string $value = null)
{
    if ($value === null) {
        $message = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $message;
    }
    $_SESSION['flash'][$key] = $value;
}

/**
 * Check if flash message exists
 */
function hasFlash(string $key): bool
{
    return isset($_SESSION['flash'][$key]);
}

/**
 * Format date for display
 */
function formatDate(?string $date, string $format = DATE_FORMAT): string
{
    if (empty($date)) {
        return '';
    }
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime(?string $datetime): string
{
    return formatDate($datetime, DATETIME_FORMAT);
}

/**
 * Get relative time (e.g., "2 hours ago")
 */
function timeAgo(string $datetime): string
{
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
    return formatDate($datetime);
}

/**
 * Get days until due date
 */
function daysUntilDue(string $dueDate): int
{
    $due = strtotime($dueDate);
    $today = strtotime(date('Y-m-d'));
    return (int) floor(($due - $today) / 86400);
}

/**
 * Get due date badge class
 */
function dueDateClass(string $dueDate): string
{
    $days = daysUntilDue($dueDate);
    if ($days < 0) {
        return 'bg-red-100 text-red-800';
    } elseif ($days === 0) {
        return 'bg-orange-100 text-orange-800';
    } elseif ($days === 1) {
        return 'bg-yellow-100 text-yellow-800';
    }
    return 'bg-green-100 text-green-800';
}

/**
 * Get status badge class
 */
function statusBadgeClass(string $status): string
{
    $classes = [
        'ACTIVE' => 'bg-green-100 text-green-800',
        'ON_HOLD' => 'bg-yellow-100 text-yellow-800',
        'COMPLETED' => 'bg-blue-100 text-blue-800',
        'CANCELLED' => 'bg-gray-100 text-gray-800'
    ];
    return $classes[$status] ?? 'bg-gray-100 text-gray-800';
}

/**
 * Get step badge class
 */
function stepBadgeClass(string $stepCode): string
{
    $classes = [
        'TRANS' => 'bg-purple-100 text-purple-800',
        'TRANSCAN' => 'bg-purple-100 text-purple-800',
        'TRANSSCAN' => 'bg-purple-100 text-purple-800',
        'DESIGN' => 'bg-blue-100 text-blue-800',
        'CAD' => 'bg-blue-100 text-blue-800',
        'PRECAD' => 'bg-indigo-100 text-indigo-800',
        'CAM' => 'bg-cyan-100 text-cyan-800',
        'PRECAM' => 'bg-cyan-100 text-cyan-800',
        'CNC' => 'bg-orange-100 text-orange-800',
        'OVENS' => 'bg-red-100 text-red-800',
        'QC' => 'bg-green-100 text-green-800',
        'NESTING' => 'bg-teal-100 text-teal-800'
    ];
    return $classes[$stepCode] ?? 'bg-gray-100 text-gray-800';
}

/**
 * Get department color
 */
function departmentColor(string $department): string
{
    $colors = [
        'COCR' => '#3B82F6',
        'SOLIDEX' => '#10B981',
        '3D_PRINT' => '#8B5CF6'
    ];
    return $colors[$department] ?? '#6B7280';
}

/**
 * Get department badge class
 */
function departmentBadgeClass(string $department): string
{
    $classes = [
        'COCR' => 'bg-blue-100 text-blue-800',
        'SOLIDEX' => 'bg-emerald-100 text-emerald-800',
        '3D_PRINT' => 'bg-violet-100 text-violet-800'
    ];
    return $classes[$department] ?? 'bg-gray-100 text-gray-800';
}

/**
 * Parse note tags from JSON
 */
function parseNoteTags(?string $jsonTags): array
{
    if (empty($jsonTags)) {
        return [];
    }
    $tagIds = json_decode($jsonTags, true);
    if (!is_array($tagIds)) {
        return [];
    }
    return $tagIds;
}

/**
 * Render note tag badges
 */
function renderNoteTags(array $tagIds, array $allTags): string
{
    if (empty($tagIds)) {
        return '';
    }

    $html = '';
    foreach ($tagIds as $tagId) {
        foreach ($allTags as $tag) {
            if ($tag['id'] == $tagId) {
                $color = $tag['tag_color'] ?? '#6B7280';
                $html .= sprintf(
                    '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" style="background-color: %s20; color: %s;">%s</span> ',
                    $color,
                    $color,
                    e($tag['tag_name'])
                );
                break;
            }
        }
    }
    return $html;
}

/**
 * JSON response
 */
function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Success JSON response
 */
function jsonSuccess($data = null, string $message = 'Success'): void
{
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

/**
 * Error JSON response
 */
function jsonError(string $message, int $status = 400, $errors = null): void
{
    jsonResponse([
        'success' => false,
        'message' => $message,
        'errors' => $errors
    ], $status);
}

/**
 * Validate required fields
 */
function validateRequired(array $data, array $required): array
{
    $errors = [];
    foreach ($required as $field => $label) {
        if (empty($data[$field])) {
            $errors[$field] = "$label is required";
        }
    }
    return $errors;
}

/**
 * Get request input
 */
function input(string $key, $default = null)
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

/**
 * Get all request input
 */
function allInput(): array
{
    return array_merge($_GET, $_POST);
}

/**
 * Check if request is POST
 */
function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check if request is AJAX
 */
function isAjax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get pagination data
 */
function paginate(int $total, int $page, int $perPage): array
{
    $totalPages = (int) ceil($total / $perPage);

    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'has_prev' => $page > 1,
        'has_next' => $page < $totalPages,
        'prev_page' => $page > 1 ? $page - 1 : null,
        'next_page' => $page < $totalPages ? $page + 1 : null
    ];
}

/**
 * Build query string preserving existing params
 */
function buildQueryString(array $params, array $preserve = []): string
{
    $existing = [];
    foreach ($preserve as $key) {
        if (isset($_GET[$key])) {
            $existing[$key] = $_GET[$key];
        }
    }
    return http_build_query(array_merge($existing, $params));
}

/**
 * Include view with data
 */
function view(string $name, array $data = []): void
{
    extract($data);
    include APP_ROOT . '/views/' . $name . '.php';
}

/**
 * Include component
 */
function component(string $name, array $data = []): void
{
    extract($data);
    include APP_ROOT . '/views/components/' . $name . '.php';
}

/**
 * Get asset URL
 */
function asset(string $path): string
{
    return APP_URL . '/assets/' . ltrim($path, '/');
}

/**
 * Generate URL
 */
function url(string $path): string
{
    return APP_URL . '/' . ltrim($path, '/');
}

/**
 * Get current URL
 */
function currentUrl(): string
{
    return APP_URL . $_SERVER['REQUEST_URI'];
}

/**
 * Check if current URL matches
 */
function isCurrentUrl(string $path): bool
{
    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    return $currentPath === '/' . ltrim($path, '/');
}

/**
 * Active class helper for navigation
 */
function activeClass(string $path, string $class = 'bg-gray-100'): string
{
    return isCurrentUrl($path) ? $class : '';
}

/**
 * Format file size
 */
function formatFileSize(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Get file icon class based on extension
 */
function fileIconClass(string $filename): string
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $icons = [
        'pdf' => 'text-red-500',
        'doc' => 'text-blue-500',
        'docx' => 'text-blue-500',
        'xls' => 'text-green-500',
        'xlsx' => 'text-green-500',
        'jpg' => 'text-yellow-500',
        'jpeg' => 'text-yellow-500',
        'png' => 'text-yellow-500',
        'gif' => 'text-yellow-500',
        'stl' => 'text-purple-500',
        'obj' => 'text-purple-500',
        'zip' => 'text-gray-500',
        'rar' => 'text-gray-500'
    ];
    return $icons[$ext] ?? 'text-gray-400';
}

/**
 * Truncate text
 */
function truncate(string $text, int $length = 50, string $suffix = '...'): string
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Generate a random string
 */
function randomString(int $length = 32): string
{
    return bin2hex(random_bytes($length / 2));
}

/**
 * Sanitize filename
 */
function sanitizeFilename(string $filename): string
{
    return preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
}

/**
 * Log activity
 */
function logActivity(string $action, string $description, ?int $caseId = null): void
{
    try {
        db()->insert('audit_log', [
            'table_name' => 'activity',
            'record_id' => $caseId ?? 0,
            'action' => $action,
            'new_values' => json_encode(['description' => $description]),
            'user_id' => auth()->userId(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        error_log('Activity log error: ' . $e->getMessage());
    }
}
