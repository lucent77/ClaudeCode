<?php
/**
 * Cron Job: Fetch Prices for All Products
 * Schedule: */30 * * * * (every 30 minutes recommended)
 * Command: php -q /path/to/fetch_prices.php
 */

// Set execution time limit
set_time_limit(600); // 10 minutes max

// Get absolute path
$rootPath = dirname(__DIR__);

require_once $rootPath . '/api/config.php';
require_once $rootPath . '/api/db.php';
require_once $rootPath . '/cron/scrapers/store_scrapers.php';

// Log file
$logFile = $rootPath . '/logs/cron.log';

// Ensure logs directory exists
if (!is_dir($rootPath . '/logs')) {
    mkdir($rootPath . '/logs', 0755, true);
}

/**
 * Log message to file
 */
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Send push notification to user
 */
function sendPushNotification($userId, $productName, $price, $store) {
    global $rootPath;

    // Get user's push subscriptions
    $db = getDB();
    $stmt = $db->prepare("SELECT endpoint, p256dh, auth FROM subscribers WHERE user_id = ?");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll();

    if (empty($subscriptions)) {
        logMessage("No push subscriptions found for user $userId");
        return;
    }

    // Prepare notification payload
    $title = "🔥 Price Drop Alert!";
    $body = "$productName is now $$price at $store";
    $icon = APP_URL . "/assets/icons/icon-192.png";
    $url = APP_URL . "/pages/dashboard.html";

    // Send to each subscription
    foreach ($subscriptions as $subscription) {
        try {
            // Use web-push library (will be included via composer)
            // For now, log the notification
            logMessage("Would send push to user $userId: $body");

            // TODO: Implement actual push sending with web-push library
            // require_once $rootPath . '/push/send_notification.php';
            // sendWebPush($subscription, $title, $body, $icon, $url);

        } catch (Exception $e) {
            logMessage("Push notification failed for user $userId: " . $e->getMessage());
        }
    }
}

/**
 * Main execution
 */
try {
    logMessage("=== Starting price fetch cron job ===");

    $db = getDB();
    $scraper = new StoreScraper();

    // Get all products from all users
    $stmt = $db->query("
        SELECT p.id, p.user_id, p.name, p.store, p.url, p.target_price, p.current_price
        FROM products p
        ORDER BY p.user_id, p.id
    ");
    $products = $stmt->fetchAll();

    $totalProducts = count($products);
    $successCount = 0;
    $failCount = 0;
    $alertCount = 0;

    logMessage("Found $totalProducts products to check");

    foreach ($products as $product) {
        $productId = $product['id'];
        $userId = $product['user_id'];
        $name = $product['name'];
        $store = $product['store'];
        $url = $product['url'];
        $targetPrice = floatval($product['target_price']);
        $previousPrice = floatval($product['current_price']);

        logMessage("Checking product #$productId: $name ($store)");

        // Scrape price with retry logic
        $price = null;
        $retryCount = 0;

        while ($price === null && $retryCount < SCRAPE_RETRY_COUNT) {
            try {
                $price = $scraper->scrapePrice($url, $store);

                if ($price !== null) {
                    logMessage("  ✓ Price found: $$price");
                    $successCount++;
                } else {
                    $retryCount++;
                    if ($retryCount < SCRAPE_RETRY_COUNT) {
                        logMessage("  ⚠ Price not found, retrying... ($retryCount/" . SCRAPE_RETRY_COUNT . ")");
                        sleep(2); // Wait 2 seconds before retry
                    }
                }
            } catch (Exception $e) {
                $retryCount++;
                logMessage("  ✗ Scraping error: " . $e->getMessage());
                if ($retryCount < SCRAPE_RETRY_COUNT) {
                    sleep(2);
                }
            }
        }

        if ($price === null) {
            logMessage("  ✗ Failed to get price after " . SCRAPE_RETRY_COUNT . " attempts");
            $failCount++;
            continue;
        }

        // Update product's current price
        $updateStmt = $db->prepare("
            UPDATE products
            SET current_price = ?, last_checked = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([$price, $productId]);

        // Insert price history
        $historyStmt = $db->prepare("
            INSERT INTO price_history (product_id, price, checked_at)
            VALUES (?, ?, NOW())
        ");
        $historyStmt->execute([$productId, $price]);

        // Check for price alerts
        $shouldAlert = false;
        $alertReason = '';

        // Alert if price is at or below target
        if ($price <= $targetPrice) {
            $shouldAlert = true;
            $alertReason = "Target price reached ($price <= $targetPrice)";
        }
        // Alert if price dropped significantly (5% or more)
        elseif ($previousPrice > 0) {
            $dropPercentage = (($previousPrice - $price) / $previousPrice);
            if ($dropPercentage >= PRICE_DROP_THRESHOLD) {
                $shouldAlert = true;
                $alertReason = sprintf("Significant price drop (%.1f%%)", $dropPercentage * 100);
            }
        }

        if ($shouldAlert) {
            logMessage("  🔔 ALERT: $alertReason");
            sendPushNotification($userId, $name, $price, $store);
            $alertCount++;
        }

        // Small delay between requests to avoid rate limiting
        usleep(500000); // 0.5 seconds
    }

    logMessage("=== Cron job completed ===");
    logMessage("Total: $totalProducts | Success: $successCount | Failed: $failCount | Alerts: $alertCount");

} catch (Exception $e) {
    logMessage("FATAL ERROR: " . $e->getMessage());
    logMessage($e->getTraceAsString());
}

logMessage("");
