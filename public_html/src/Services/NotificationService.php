<?php
/**
 * Notification Service
 *
 * Unified notification service that coordinates Email, SMS, Slack, and ABS
 */

class NotificationService
{
    private $db;
    private $emailService;
    private $smsService;
    private $slackService;
    private $absService;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Send notifications for a case status update
     *
     * @param array $case Case data with client information
     * @param string $newStatus The new status
     * @param array $options Additional options
     * @return array Results of each notification channel
     */
    public function notifyCaseStatusChange($case, $newStatus, $options = [])
    {
        $results = [
            'email' => null,
            'sms' => null,
            'slack' => null,
            'abs' => null,
        ];

        // Get client notification preferences
        $notificationPref = $case['client_notification_pref'] ?? 'Email_Only';

        // Prepare case data for notifications
        $caseData = [
            'case_number' => $case['case_number'],
            'patient_name' => $case['patient_name'],
            'client_name' => $case['client_name'],
            'client_email' => $case['client_email'],
            'client_phone' => $case['client_phone'] ?? null,
            'location' => $case['client_location'] ?? 'NYC',
            'notes' => $options['notes'] ?? '',
        ];

        // 1. Send Email (if enabled)
        if ($this->shouldSendEmail($notificationPref)) {
            try {
                $results['email'] = $this->sendEmailNotification($caseData, $newStatus, $options);
            } catch (Exception $e) {
                $results['email'] = ['error' => $e->getMessage()];
                $this->log("Email notification failed: " . $e->getMessage(), 'error');
            }
        }

        // 2. Send SMS (if enabled)
        if ($this->shouldSendSms($notificationPref) && !empty($caseData['client_phone'])) {
            try {
                $results['sms'] = $this->sendSmsNotification($caseData, $newStatus);
            } catch (Exception $e) {
                $results['sms'] = ['error' => $e->getMessage()];
                $this->log("SMS notification failed: " . $e->getMessage(), 'error');
            }
        }

        // 3. Send Slack notification (always, if configured)
        try {
            $results['slack'] = $this->sendSlackNotification($caseData, $newStatus);
        } catch (Exception $e) {
            $results['slack'] = ['error' => $e->getMessage()];
            $this->log("Slack notification failed: " . $e->getMessage(), 'error');
        }

        // 4. Update ABS (always, if configured)
        try {
            $results['abs'] = $this->updateABS($caseData, $newStatus, $options);
        } catch (Exception $e) {
            $results['abs'] = ['error' => $e->getMessage()];
            $this->log("ABS update failed: " . $e->getMessage(), 'error');
        }

        return $results;
    }

    /**
     * Check if email should be sent
     */
    private function shouldSendEmail($pref)
    {
        return in_array($pref, ['Email_Only', 'Both']);
    }

    /**
     * Check if SMS should be sent
     */
    private function shouldSendSms($pref)
    {
        return in_array($pref, ['Text_Only', 'Both']);
    }

    /**
     * Send email notification
     */
    private function sendEmailNotification($caseData, $status, $options = [])
    {
        if (!$this->emailService) {
            require_once __DIR__ . '/EmailService.php';
            $this->emailService = new EmailService();
        }

        // Determine template based on status
        $templateName = $this->getEmailTemplateForStatus($status);

        // Prepare variables for template
        $variables = [
            'case_number' => $caseData['case_number'],
            'patient_name' => $caseData['patient_name'],
            'client_name' => $caseData['client_name'],
            'status' => $status,
            'feedback' => $options['notes'] ?? '',
            'design_link' => $options['design_link'] ?? '',
        ];

        try {
            // Try to use template
            $result = $this->emailService->sendTemplate(
                $caseData['client_email'],
                $templateName,
                $variables
            );

            return ['sent' => true, 'template' => $templateName];

        } catch (Exception $e) {
            // Fallback to plain email if template fails
            $subject = "Case {$caseData['case_number']} - {$status}";
            $body = $this->buildEmailBody($caseData, $status, $options);

            $this->emailService->send($caseData['client_email'], $subject, $body);

            return ['sent' => true, 'fallback' => true];
        }
    }

    /**
     * Get email template name for status
     */
    private function getEmailTemplateForStatus($status)
    {
        $templates = [
            'Confirmed' => 'status_confirmed',
            'Action Needed' => 'status_action_needed',
            'Confirmed with Action Needed' => 'status_confirmed',
            'Pending' => 'design_confirmation_request',
            'Resolved' => 'status_confirmed',
        ];

        return $templates[$status] ?? 'status_action_needed';
    }

    /**
     * Build fallback email body
     */
    private function buildEmailBody($caseData, $status, $options = [])
    {
        $body = "Dear {$caseData['client_name']},\n\n";
        $body .= "This is an update regarding Case #{$caseData['case_number']}.\n\n";
        $body .= "Patient: {$caseData['patient_name']}\n";
        $body .= "Status: {$status}\n\n";

        if (!empty($options['notes'])) {
            $body .= "Notes:\n{$options['notes']}\n\n";
        }

        $body .= "Best regards,\nDesign Confirm System";

        return $body;
    }

    /**
     * Send SMS notification
     */
    private function sendSmsNotification($caseData, $status)
    {
        if (!$this->smsService) {
            require_once __DIR__ . '/SmsService.php';
            $this->smsService = new SmsService();
        }

        if (!$this->smsService->isAvailable()) {
            return ['skipped' => true, 'reason' => 'SMS not configured'];
        }

        $result = $this->smsService->sendCaseNotification(
            $caseData['client_phone'],
            $caseData,
            $status
        );

        return $result;
    }

    /**
     * Send Slack notification
     */
    private function sendSlackNotification($caseData, $status)
    {
        if (!$this->slackService) {
            require_once __DIR__ . '/SlackService.php';
            $this->slackService = new SlackService();
        }

        $result = $this->slackService->sendCaseNotification($caseData, $status);

        return ['sent' => $result];
    }

    /**
     * Update ABS system
     */
    private function updateABS($caseData, $status, $options = [])
    {
        if (!$this->absService) {
            require_once __DIR__ . '/ABSService.php';
            $this->absService = new ABSService();
        }

        if (!$this->absService->isAvailable()) {
            return ['skipped' => true, 'reason' => 'ABS not configured'];
        }

        // Add note to ABS
        $noteAdded = $this->absService->addStatusNote(
            $caseData['case_number'],
            $status,
            $options['notes'] ?? '',
            $caseData['location']
        );

        return ['note_added' => $noteAdded];
    }

    /**
     * Send a direct message to a user
     *
     * @param int $userId User ID
     * @param string $message Message content
     * @param array $channels Which channels to use (email, sms, slack)
     * @return array Results
     */
    public function notifyUser($userId, $message, $channels = ['email', 'slack'])
    {
        $user = $this->db->fetch(
            "SELECT * FROM users WHERE id = ?",
            [$userId]
        );

        if (!$user) {
            return ['error' => 'User not found'];
        }

        $results = [];

        // Email
        if (in_array('email', $channels) && !empty($user['email'])) {
            try {
                if (!$this->emailService) {
                    require_once __DIR__ . '/EmailService.php';
                    $this->emailService = new EmailService();
                }

                $this->emailService->send(
                    $user['email'],
                    'Notification from Design Confirm System',
                    $message
                );

                $results['email'] = ['sent' => true];
            } catch (Exception $e) {
                $results['email'] = ['error' => $e->getMessage()];
            }
        }

        // Slack DM
        if (in_array('slack', $channels) && !empty($user['slack_member_id'])) {
            try {
                if (!$this->slackService) {
                    require_once __DIR__ . '/SlackService.php';
                    $this->slackService = new SlackService();
                }

                $this->slackService->sendDirectMessage(
                    $user['slack_member_id'],
                    $message
                );

                $results['slack'] = ['sent' => true];
            } catch (Exception $e) {
                $results['slack'] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Send a bulk notification to multiple clients
     *
     * @param array $clientIds Array of client IDs
     * @param string $subject Email subject
     * @param string $message Message content
     * @return array Results
     */
    public function notifyClients($clientIds, $subject, $message)
    {
        $results = [];

        foreach ($clientIds as $clientId) {
            $client = $this->db->fetch(
                "SELECT * FROM clients WHERE id = ? AND is_active = 1",
                [$clientId]
            );

            if (!$client) {
                $results[$clientId] = ['error' => 'Client not found or inactive'];
                continue;
            }

            $notificationPref = $client['notification_pref'];

            // Email
            if ($this->shouldSendEmail($notificationPref)) {
                try {
                    if (!$this->emailService) {
                        require_once __DIR__ . '/EmailService.php';
                        $this->emailService = new EmailService();
                    }

                    $this->emailService->send($client['email'], $subject, $message);
                    $results[$clientId]['email'] = ['sent' => true];
                } catch (Exception $e) {
                    $results[$clientId]['email'] = ['error' => $e->getMessage()];
                }
            }

            // SMS
            if ($this->shouldSendSms($notificationPref) && !empty($client['phone'])) {
                try {
                    if (!$this->smsService) {
                        require_once __DIR__ . '/SmsService.php';
                        $this->smsService = new SmsService();
                    }

                    $this->smsService->send($client['phone'], $message);
                    $results[$clientId]['sms'] = ['sent' => true];
                } catch (Exception $e) {
                    $results[$clientId]['sms'] = ['error' => $e->getMessage()];
                }
            }
        }

        return $results;
    }

    /**
     * Log notification activity
     */
    private function log($message, $level = 'info')
    {
        $logFile = dirname(dirname(__DIR__)) . '/logs/notifications.log';
        $timestamp = date('Y-m-d H:i:s');

        $logMessage = "[{$timestamp}] [{$level}] {$message}\n";

        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}
