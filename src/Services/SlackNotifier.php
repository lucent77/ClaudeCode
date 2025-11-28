<?php
/**
 * Slack Notifier Service
 *
 * Sends notifications to Slack via incoming webhooks.
 */

declare(strict_types=1);

namespace App\Services;

class SlackNotifier
{
    private ?string $webhookUrl;

    public function __construct(?string $webhookUrl = null)
    {
        $this->webhookUrl = $webhookUrl ?? ($_ENV['SLACK_WEBHOOK_URL'] ?? null);
    }

    /**
     * Send a notification to Slack
     */
    public function notify(string $text, array $blocks = []): bool
    {
        if (empty($this->webhookUrl)) {
            return false;
        }

        $payload = ['text' => $text];

        if (!empty($blocks)) {
            $payload['blocks'] = $blocks;
        }

        return $this->send($payload);
    }

    /**
     * Notify about a new approved post
     */
    public function notifyNewPost(array $post): bool
    {
        $categories = [
            'process' => 'Process',
            'tools' => 'Tools & Facilities',
            'communication' => 'Communication',
            'leadership' => 'Leadership',
            'benefits' => 'Benefits & Policy',
            'other' => 'Other',
        ];

        $category = $categories[$post['category']] ?? $post['category'];
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost';

        $blocks = [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => 'New Feedback Submitted',
                    'emoji' => true,
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "*{$post['title']}*\n_{$category}_",
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'plain_text',
                    'text' => substr($post['body'], 0, 200) . (strlen($post['body']) > 200 ? '...' : ''),
                ],
            ],
            [
                'type' => 'actions',
                'elements' => [
                    [
                        'type' => 'button',
                        'text' => [
                            'type' => 'plain_text',
                            'text' => 'View in Admin',
                        ],
                        'url' => "{$appUrl}/admin/posts/{$post['id']}",
                    ],
                    [
                        'type' => 'button',
                        'text' => [
                            'type' => 'plain_text',
                            'text' => 'View Public',
                        ],
                        'url' => "{$appUrl}/posts/{$post['public_id']}",
                    ],
                ],
            ],
        ];

        return $this->notify("New feedback: {$post['title']}", $blocks);
    }

    /**
     * Notify about a status change
     */
    public function notifyStatusChange(array $post, string $oldStatus, string $newStatus, ?string $changedBy = null): bool
    {
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost';

        $statusEmojis = [
            'open' => ':large_blue_circle:',
            'in_progress' => ':large_yellow_circle:',
            'resolved' => ':white_check_mark:',
            'rejected' => ':x:',
            'duplicate' => ':record_button:',
        ];

        $emoji = $statusEmojis[$newStatus] ?? ':grey_question:';

        $text = "{$emoji} *{$post['title']}* status changed: `{$oldStatus}` → `{$newStatus}`";

        if ($changedBy) {
            $text .= " by {$changedBy}";
        }

        $blocks = [
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => $text,
                ],
            ],
            [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "<{$appUrl}/posts/{$post['public_id']}|View feedback>",
                    ],
                ],
            ],
        ];

        return $this->notify("Feedback status updated: {$post['title']}", $blocks);
    }

    /**
     * Notify about resolution
     */
    public function notifyResolved(array $post, ?string $resolvedBy = null): bool
    {
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost';

        $blocks = [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => 'Feedback Resolved',
                    'emoji' => true,
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "*{$post['title']}*" . ($resolvedBy ? "\nResolved by {$resolvedBy}" : ''),
                ],
            ],
            [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "<{$appUrl}/posts/{$post['public_id']}|View feedback>",
                    ],
                ],
            ],
        ];

        return $this->notify("Feedback resolved: {$post['title']}", $blocks);
    }

    /**
     * Send weekly summary
     */
    public function notifyWeeklySummary(int $newPosts, int $resolved, int $pending): bool
    {
        $blocks = [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => 'Weekly Feedback Summary',
                    'emoji' => true,
                ],
            ],
            [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "*New submissions*\n{$newPosts}",
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Resolved*\n{$resolved}",
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Pending*\n{$pending}",
                    ],
                ],
            ],
        ];

        return $this->notify("Weekly summary: {$newPosts} new, {$resolved} resolved, {$pending} pending", $blocks);
    }

    /**
     * Send the payload to Slack
     */
    private function send(array $payload): bool
    {
        $ch = curl_init($this->webhookUrl);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return $httpCode === 200;
    }

    /**
     * Check if Slack is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->webhookUrl);
    }
}
