<?php
/**
 * URL Collector - Analysis Cron Job
 *
 * Run via cron: * * * * * /usr/bin/php /path/to/cron/analyze.php
 *
 * Processes pending URLs:
 * 1. Fetches HTML content
 * 2. Extracts metadata (OG tags, title, text)
 * 3. Calls Gemini API for analysis
 * 4. Updates database with results
 */

declare(strict_types=1);

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', '0');

// CLI only
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo 'CLI access only';
    exit(1);
}

// Define app root
define('APP_ROOT', dirname(__DIR__));

// Load configuration
$config = require APP_ROOT . '/config/config.php';

// Load libraries
require_once APP_ROOT . '/lib/db.php';
require_once APP_ROOT . '/lib/http.php';
require_once APP_ROOT . '/lib/parser.php';
require_once APP_ROOT . '/lib/gemini.php';
require_once APP_ROOT . '/lib/util.php';

// Initialize
Database::init($config['db']);
Util::init($config);

// Settings
$batchSize = $config['analyze']['batch_size'] ?? 10;
$maxRetries = $config['analyze']['max_retries'] ?? 3;
$maxTextLength = $config['analyze']['max_text_length'] ?? 8000;

Util::logInfo("=== Analysis cron started (batch size: {$batchSize}) ===");

try {
    // Get pending URLs (skip those with too many retries)
    $pendingLinks = Database::fetchAll(
        'SELECT id, url, site, note
         FROM links
         WHERE status = ? AND retry_count < ?
         ORDER BY created_at ASC
         LIMIT ?',
        ['pending', $maxRetries, $batchSize]
    );

    $count = count($pendingLinks);
    Util::logInfo("Found {$count} pending URLs to process");

    if ($count === 0) {
        Util::logInfo('No pending URLs. Exiting.');
        exit(0);
    }

    // Initialize clients
    $httpClient = new HttpClient($config['http']);
    $parser = new HtmlParser($maxTextLength, $config['categories']);
    $geminiClient = new GeminiClient($config['gemini'], $config['categories']);

    $processed = 0;
    $succeeded = 0;
    $failed = 0;

    foreach ($pendingLinks as $link) {
        $id = $link['id'];
        $url = $link['url'];

        Util::logInfo("Processing ID {$id}: {$url}");
        $processed++;

        try {
            // Step 1: Fetch HTML
            Util::logDebug("Fetching URL: {$url}");
            $fetchResult = $httpClient->fetch($url);

            if (!$fetchResult['success']) {
                throw new RuntimeException('Fetch failed: ' . ($fetchResult['error'] ?? 'Unknown error'));
            }

            $html = $fetchResult['html'];
            $finalUrl = $fetchResult['final_url'] ?? $url;

            // Step 2: Parse HTML
            Util::logDebug('Parsing HTML...');
            $parsed = $parser->parse($html, $finalUrl);

            $title = $parsed['title'] ?? '';
            $description = $parsed['description'] ?? '';
            $imageUrl = $parsed['image_url'] ?? '';
            $siteName = $parsed['site_name'] ?? $link['site'];
            $rawText = $parsed['text'] ?? '';

            // Step 3: Rule-based category detection
            $ruleCategory = $parser->detectCategory($url, $siteName);

            // Step 4: Gemini Analysis
            Util::logDebug('Calling Gemini API...');
            $geminiResult = $geminiClient->analyze($url, $siteName, $title, $rawText);

            if (!$geminiResult['success']) {
                Util::logWarning("Gemini analysis failed: " . ($geminiResult['error'] ?? 'Unknown'));
                // Continue with rule-based fallback
                $category = $ruleCategory ?: 'other';
                $summary = $description ?: mb_substr($rawText, 0, 200);
                $keywords = $parser->extractKeywords($rawText, 5);
                $autoScore = null;
            } else {
                $geminiData = $geminiResult['data'];
                $category = $geminiData['category'] ?? $ruleCategory ?: 'other';
                $summary = $geminiData['summary'] ?? $description;
                $keywords = $geminiData['keywords'] ?? [];
                $autoScore = $geminiData['auto_score'] ?? null;

                // Prefer rule-based category for known domains
                if (!empty($ruleCategory) && $ruleCategory !== 'other') {
                    $category = $ruleCategory;
                }
            }

            // Step 5: Update database
            $updateData = [
                'title'       => $title ?: null,
                'description' => $description ?: null,
                'image_url'   => $imageUrl ?: null,
                'site'        => $siteName,
                'raw_text'    => $rawText ?: null,
                'summary'     => $summary ?: null,
                'keywords'    => !empty($keywords) ? json_encode($keywords, JSON_UNESCAPED_UNICODE) : null,
                'category'    => $category,
                'auto_score'  => $autoScore,
                'status'      => 'done',
                'analyzed_at' => Util::now(),
            ];

            Database::update('links', $updateData, 'id = ?', [$id]);

            Util::logInfo("ID {$id} analyzed successfully (category: {$category}, auto_score: " . ($autoScore ?? 'N/A') . ")");
            $succeeded++;

        } catch (Throwable $e) {
            $errorMsg = $e->getMessage();
            Util::logError("ID {$id} failed: {$errorMsg}");

            // Update with error status
            Database::query(
                'UPDATE links
                 SET status = ?, error_message = ?, retry_count = retry_count + 1
                 WHERE id = ?',
                ['error', mb_substr($errorMsg, 0, 500), $id]
            );

            // Check if max retries reached
            $currentRetries = Database::fetchValue(
                'SELECT retry_count FROM links WHERE id = ?',
                [$id]
            );

            if ($currentRetries < $maxRetries) {
                // Reset to pending for retry
                Database::update('links', ['status' => 'pending'], 'id = ?', [$id]);
                Util::logInfo("ID {$id} queued for retry ({$currentRetries}/{$maxRetries})");
            } else {
                Util::logWarning("ID {$id} exceeded max retries, marked as error");
            }

            $failed++;
        }

        // Small delay between requests to avoid rate limiting
        usleep(500000); // 0.5 seconds
    }

    Util::logInfo("=== Analysis complete: {$processed} processed, {$succeeded} succeeded, {$failed} failed ===");

} catch (Throwable $e) {
    Util::logError('Cron fatal error: ' . $e->getMessage());
    exit(1);
}

exit(0);
