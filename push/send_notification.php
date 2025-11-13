<?php
/**
 * Send Web Push Notification
 * Requires: composer require minishlink/web-push
 */

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * Send a web push notification
 *
 * @param array $subscriptionData Array with 'endpoint', 'p256dh', 'auth'
 * @param string $title Notification title
 * @param string $body Notification body
 * @param string $icon Notification icon URL
 * @param string $url URL to open when clicked
 * @return bool Success status
 */
function sendWebPush($subscriptionData, $title, $body, $icon, $url) {
    try {
        // Create subscription object
        $subscription = Subscription::create([
            'endpoint' => $subscriptionData['endpoint'],
            'publicKey' => $subscriptionData['p256dh'],
            'authToken' => $subscriptionData['auth'],
        ]);

        // VAPID authentication details
        $auth = [
            'VAPID' => [
                'subject' => VAPID_SUBJECT,
                'publicKey' => VAPID_PUBLIC_KEY,
                'privateKey' => VAPID_PRIVATE_KEY,
            ]
        ];

        // Initialize WebPush
        $webPush = new WebPush($auth);

        // Prepare payload
        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => $icon,
            'badge' => $icon,
            'url' => $url,
            'timestamp' => time(),
        ]);

        // Send notification
        $report = $webPush->sendOneNotification(
            $subscription,
            $payload
        );

        // Check if successful
        if ($report->isSuccess()) {
            return true;
        } else {
            $reason = $report->getReason();
            error_log("Push notification failed: " . $reason);

            // If subscription is expired/invalid, we should remove it from database
            if (in_array($report->getStatusCode(), [404, 410])) {
                // Subscription expired - remove from database
                global $db;
                if (isset($db)) {
                    $stmt = $db->prepare("DELETE FROM subscribers WHERE endpoint = ?");
                    $stmt->execute([$subscriptionData['endpoint']]);
                }
            }

            return false;
        }

    } catch (Exception $e) {
        error_log("Send Push Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send notification to all user's subscriptions
 *
 * @param int $userId User ID
 * @param string $title Notification title
 * @param string $body Notification body
 * @param string $icon Notification icon URL
 * @param string $url URL to open when clicked
 * @return int Number of successful sends
 */
function sendToUser($userId, $title, $body, $icon, $url) {
    global $db;

    // Get all user subscriptions
    $stmt = $db->prepare("SELECT endpoint, p256dh, auth FROM subscribers WHERE user_id = ?");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll();

    $successCount = 0;

    foreach ($subscriptions as $sub) {
        if (sendWebPush($sub, $title, $body, $icon, $url)) {
            $successCount++;
        }
    }

    return $successCount;
}
