<?php
/**
 * Mailer Service
 *
 * Simple SMTP mailer for notifications.
 */

declare(strict_types=1);

namespace App\Services;

class Mailer
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $from;

    public function __construct()
    {
        $this->host = $_ENV['SMTP_HOST'] ?? '';
        $this->port = (int) ($_ENV['SMTP_PORT'] ?? 587);
        $this->username = $_ENV['SMTP_USER'] ?? '';
        $this->password = $_ENV['SMTP_PASS'] ?? '';
        $this->from = $_ENV['SMTP_FROM'] ?? 'noreply@creodent.com';
    }

    /**
     * Send an email
     */
    public function send(string $to, string $subject, string $body, bool $isHtml = false): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $headers = [
            'From' => $this->from,
            'Reply-To' => $this->from,
            'X-Mailer' => 'Creodent Voice',
            'MIME-Version' => '1.0',
        ];

        if ($isHtml) {
            $headers['Content-Type'] = 'text/html; charset=UTF-8';
        } else {
            $headers['Content-Type'] = 'text/plain; charset=UTF-8';
        }

        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= "{$key}: {$value}\r\n";
        }

        // Use PHP's built-in mail() function
        // For production, consider using PHPMailer or Symfony Mailer
        return mail($to, $subject, $body, $headerString);
    }

    /**
     * Send digest summary email
     */
    public function sendDigestNotification(string $to, array $digest): bool
    {
        $subject = "Weekly Feedback Summary - Week of {$digest['week_start']}";

        $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #0ea5e9; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 20px; border-radius: 0 0 8px 8px; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0;">Weekly Feedback Summary</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Week of {$digest['week_start']} - {$digest['week_end']}</p>
        </div>
        <div class="content">
            {$digest['summary']}
        </div>
        <div class="footer">
            <p>Creodent Anonymous Voice</p>
        </div>
    </div>
</body>
</html>
HTML;

        return $this->send($to, $subject, $body, true);
    }

    /**
     * Send SLA breach alert
     */
    public function sendSlaAlert(string $to, int $count): bool
    {
        $subject = "[Alert] {$count} Feedback Items Breaching SLA";

        $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .alert { background: #fef2f2; border: 1px solid #fecaca; padding: 20px; border-radius: 8px; }
        .alert h2 { color: #dc2626; margin-top: 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="alert">
            <h2>SLA Breach Warning</h2>
            <p><strong>{$count}</strong> feedback item(s) have not received a response within 5 business days.</p>
            <p>Please review the moderation queue to address these items.</p>
        </div>
    </div>
</body>
</html>
HTML;

        return $this->send($to, $subject, $body, true);
    }

    /**
     * Check if mailer is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->host) && !empty($this->username);
    }
}
