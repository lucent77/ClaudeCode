<?php
/**
 * Slack Service
 *
 * Handles Slack notifications via Webhook and Bot Token
 */

class SlackService
{
    private $webhookUrl;
    private $botToken;
    private $defaultChannel;

    public function __construct()
    {
        $this->webhookUrl = env('SLACK_WEBHOOK_URL');
        $this->botToken = env('SLACK_BOT_TOKEN');
        $this->defaultChannel = env('SLACK_CHANNEL', '#general');
    }

    /**
     * Send a message using webhook
     *
     * @param string $message The message text
     * @param string|null $channel Channel to send to (webhook ignores this)
     * @param array $options Additional options (attachments, blocks, etc.)
     * @return bool
     */
    public function sendMessage($message, $channel = null, $options = [])
    {
        if (!empty($this->webhookUrl)) {
            return $this->sendViaWebhook($message, $options);
        }

        if (!empty($this->botToken)) {
            return $this->sendViaBot($message, $channel ?? $this->defaultChannel, $options);
        }

        throw new Exception('Slack is not configured. Set SLACK_WEBHOOK_URL or SLACK_BOT_TOKEN');
    }

    /**
     * Send message via webhook
     */
    private function sendViaWebhook($message, $options = [])
    {
        $payload = array_merge([
            'text' => $message,
        ], $options);

        $ch = curl_init($this->webhookUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->log("Webhook error: {$error}", 'error');
            throw new Exception("Slack webhook failed: {$error}");
        }

        if ($httpCode !== 200) {
            $this->log("Webhook returned {$httpCode}: {$response}", 'error');
            return false;
        }

        $this->log("Message sent via webhook");
        return true;
    }

    /**
     * Send message via Bot Token (chat.postMessage)
     */
    private function sendViaBot($message, $channel, $options = [])
    {
        $url = 'https://slack.com/api/chat.postMessage';

        $payload = array_merge([
            'channel' => $channel,
            'text' => $message,
        ], $options);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
                'Authorization: Bearer ' . $this->botToken,
            ],
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->log("Bot API error: {$error}", 'error');
            throw new Exception("Slack API failed: {$error}");
        }

        $data = json_decode($response, true);

        if (!$data['ok']) {
            $this->log("Bot API error: " . ($data['error'] ?? 'Unknown'), 'error');
            return false;
        }

        $this->log("Message sent via bot to {$channel}");
        return true;
    }

    /**
     * Send a direct message to a user
     *
     * @param string $userId Slack user ID (e.g., U12345678)
     * @param string $message The message text
     * @param array $options Additional options
     * @return bool
     */
    public function sendDirectMessage($userId, $message, $options = [])
    {
        if (empty($this->botToken)) {
            throw new Exception('Bot token required for direct messages');
        }

        // Open conversation with user
        $conversationUrl = 'https://slack.com/api/conversations.open';

        $ch = curl_init($conversationUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['users' => $userId]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
                'Authorization: Bearer ' . $this->botToken,
            ],
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if (!$data['ok']) {
            $this->log("Failed to open DM: " . ($data['error'] ?? 'Unknown'), 'error');
            return false;
        }

        $channelId = $data['channel']['id'];

        // Send message to DM channel
        return $this->sendViaBot($message, $channelId, $options);
    }

    /**
     * Send a case notification
     *
     * @param array $caseData Case information
     * @param string $status The new status
     * @param string|null $channel Channel to send to
     * @return bool
     */
    public function sendCaseNotification($caseData, $status, $channel = null)
    {
        $statusEmoji = [
            'Confirmed' => ':white_check_mark:',
            'Action Needed' => ':warning:',
            'Confirmed with Action Needed' => ':large_orange_diamond:',
            'Pending' => ':hourglass:',
            'Resolved' => ':ballot_box_with_check:',
        ];

        $emoji = $statusEmoji[$status] ?? ':question:';

        $message = "{$emoji} *Case Update*\n" .
                   "Case: *{$caseData['case_number']}*\n" .
                   "Patient: {$caseData['patient_name']}\n" .
                   "Status: *{$status}*\n" .
                   "Client: {$caseData['client_name']}";

        $blocks = [
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => $message,
                ],
            ],
        ];

        if (!empty($caseData['notes'])) {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "*Notes:* {$caseData['notes']}",
                ],
            ];
        }

        return $this->sendMessage($message, $channel, ['blocks' => $blocks]);
    }

    /**
     * Send a formatted block message
     *
     * @param array $blocks Slack block array
     * @param string|null $channel Channel to send to
     * @param string $fallbackText Fallback text for notifications
     * @return bool
     */
    public function sendBlocks($blocks, $channel = null, $fallbackText = 'New notification')
    {
        return $this->sendMessage($fallbackText, $channel, ['blocks' => $blocks]);
    }

    /**
     * Log Slack service activity
     */
    private function log($message, $level = 'info')
    {
        $logFile = dirname(dirname(__DIR__)) . '/logs/slack.log';
        $timestamp = date('Y-m-d H:i:s');

        $logMessage = "[{$timestamp}] [{$level}] {$message}\n";

        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}
