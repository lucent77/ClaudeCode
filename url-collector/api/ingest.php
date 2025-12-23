<?php
/**
 * URL Collector - Ingest API Endpoint
 *
 * POST /api/ingest.php
 *
 * Receives URLs from iPhone Shortcuts or other clients
 * Validates, stores, and queues for analysis
 */

declare(strict_types=1);

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Define app root
define('APP_ROOT', dirname(__DIR__));

// Load configuration
$config = require APP_ROOT . '/config/config.php';

// Load libraries
require_once APP_ROOT . '/lib/db.php';
require_once APP_ROOT . '/lib/http.php';
require_once APP_ROOT . '/lib/security.php';
require_once APP_ROOT . '/lib/util.php';

// Initialize
Database::init($config['db']);
Security::init($config);
Util::init($config);

// Set response headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Log request
Util::logInfo('Ingest request from: ' . Security::getClientIp());

try {
    // Only allow POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Security::jsonError('Method not allowed. Use POST.', 405);
    }

    // Rate limiting
    $clientIp = Security::getClientIp();
    $rateLimit = Security::checkRateLimit($clientIp);

    if (!$rateLimit['allowed']) {
        header('X-RateLimit-Remaining: 0');
        header('X-RateLimit-Reset: ' . $rateLimit['reset']);
        Util::logWarning("Rate limit exceeded for IP: {$clientIp}");
        Security::jsonError('Rate limit exceeded. Try again later.', 429);
    }

    header('X-RateLimit-Remaining: ' . $rateLimit['remaining']);

    // Verify API key
    if (!Security::verifyApiKey()) {
        Util::logWarning("Invalid API key from IP: {$clientIp}");
        Security::jsonError('Unauthorized. Invalid or missing API key.', 401);
    }

    // Get input data
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $input = [];

    if (str_contains($contentType, 'application/json')) {
        $rawBody = file_get_contents('php://input');
        $input = json_decode($rawBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Security::jsonError('Invalid JSON body', 400);
        }
    } else {
        // Form data
        $input = $_POST;
    }

    // Extract and validate URL
    $url = trim($input['url'] ?? '');

    if (empty($url)) {
        Security::jsonError('URL is required', 400);
    }

    // Basic URL validation
    $urlValidation = Security::validateUrl($url);
    if (!$urlValidation['valid']) {
        Security::jsonError($urlValidation['error'], 400);
    }

    // SSRF protection - validate URL before accepting
    $httpClient = new HttpClient($config['http']);
    $ssrfCheck = $httpClient->validateUrl($url);

    if (!$ssrfCheck['valid']) {
        Util::logWarning("SSRF blocked: {$url} - {$ssrfCheck['error']}");
        Security::jsonError('URL blocked: ' . $ssrfCheck['error'], 400);
    }

    // Optional parameters
    $source = trim($input['source'] ?? 'unknown');
    $note = trim($input['note'] ?? '');

    // Sanitize inputs
    $source = mb_substr($source, 0, 50);
    $note = mb_substr($note, 0, 2000);

    // Generate URL hash for duplicate detection
    $urlHash = Security::generateUrlHash($url);

    // Check for duplicate
    $existing = Database::fetch(
        'SELECT id, status, review_status FROM links WHERE url_hash = ?',
        [$urlHash]
    );

    if ($existing) {
        Util::logInfo("Duplicate URL detected: {$url} (ID: {$existing['id']})");

        Security::jsonSuccess([
            'message'       => 'URL already exists',
            'id'            => (int) $existing['id'],
            'status'        => $existing['status'],
            'review_status' => $existing['review_status'],
            'duplicate'     => true,
        ]);
    }

    // Extract domain for quick reference
    $parser = new HtmlParser();
    $domain = $parser->extractDomain($url);

    // Insert new record
    $id = Database::insert('links', [
        'url'           => $url,
        'url_hash'      => $urlHash,
        'site'          => $domain,
        'source'        => $source,
        'note'          => !empty($note) ? $note : null,
        'status'        => 'pending',
        'review_status' => 'inbox',
        'retry_count'   => 0,
        'created_at'    => Util::now(),
    ]);

    Util::logInfo("URL ingested: {$url} (ID: {$id}, Source: {$source})");

    Security::jsonSuccess([
        'message'       => 'URL queued for analysis',
        'id'            => $id,
        'url'           => $url,
        'status'        => 'pending',
        'review_status' => 'inbox',
    ], 201);

} catch (PDOException $e) {
    Util::logError('Database error in ingest: ' . $e->getMessage());
    Security::jsonError('Database error occurred', 500);
} catch (Throwable $e) {
    Util::logError('Ingest error: ' . $e->getMessage());
    Security::jsonError('An error occurred', 500);
}
