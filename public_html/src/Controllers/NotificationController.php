<?php
/**
 * Notification Controller
 *
 * Handles notification testing and sending
 */

class NotificationController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Test email sending
     * POST /api/notifications/test-email
     */
    public function testEmail()
    {
        $data = Router::getRequestData();

        if (empty($data['to'])) {
            Router::error('Recipient email is required', 400);
            return;
        }

        if (!filter_var($data['to'], FILTER_VALIDATE_EMAIL)) {
            Router::error('Invalid email address', 400);
            return;
        }

        try {
            require_once dirname(__DIR__) . '/Services/EmailService.php';
            $emailService = new EmailService();

            $subject = $data['subject'] ?? 'Test Email from Design Confirm System';
            $body = $data['body'] ?? 'This is a test email to verify email configuration is working correctly.';

            $result = $emailService->send($data['to'], $subject, $body);

            if ($result) {
                Router::success(null, 'Test email sent successfully');
            } else {
                Router::error('Failed to send test email', 500);
            }

        } catch (Exception $e) {
            Router::error('Email error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Test SMS sending
     * POST /api/notifications/test-sms
     */
    public function testSms()
    {
        $data = Router::getRequestData();

        if (empty($data['to'])) {
            Router::error('Phone number is required', 400);
            return;
        }

        try {
            require_once dirname(__DIR__) . '/Services/SmsService.php';
            $smsService = new SmsService();

            $message = $data['message'] ?? 'Test SMS from Design Confirm System';

            $result = $smsService->send($data['to'], $message);

            if ($result) {
                Router::success(null, 'Test SMS sent successfully');
            } else {
                Router::error('Failed to send test SMS', 500);
            }

        } catch (Exception $e) {
            Router::error('SMS error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Test Slack notification
     * POST /api/notifications/test-slack
     */
    public function testSlack()
    {
        $data = Router::getRequestData();

        try {
            require_once dirname(__DIR__) . '/Services/SlackService.php';
            $slackService = new SlackService();

            $message = $data['message'] ?? 'Test notification from Design Confirm System';
            $channel = $data['channel'] ?? null;

            $result = $slackService->sendMessage($message, $channel);

            if ($result) {
                Router::success(null, 'Test Slack message sent successfully');
            } else {
                Router::error('Failed to send test Slack message', 500);
            }

        } catch (Exception $e) {
            Router::error('Slack error: ' . $e->getMessage(), 500);
        }
    }
}
