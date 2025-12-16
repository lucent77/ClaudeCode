<?php
/**
 * Email Monitor Cron Job
 *
 * This script monitors incoming emails, analyzes them with AI,
 * and updates case statuses accordingly.
 *
 * Recommended cron: * * * * * php /path/to/monitor_emails.php
 * (Run every minute)
 */

// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set execution time limit
set_time_limit(300);

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Load environment and dependencies
require_once BASE_PATH . '/config/env.php';
require_once BASE_PATH . '/src/Database.php';
require_once BASE_PATH . '/src/Services/ImapService.php';
require_once BASE_PATH . '/src/Services/AiService.php';
require_once BASE_PATH . '/src/Services/SheetsService.php';
require_once BASE_PATH . '/src/Services/SlackService.php';
require_once BASE_PATH . '/src/Services/NotificationService.php';

// Load vendor autoloader if exists
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

/**
 * Email Monitor Class
 */
class EmailMonitor
{
    private $db;
    private $imapService;
    private $aiService;
    private $sheetsService;
    private $slackService;
    private $notificationService;
    private $logFile;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logFile = BASE_PATH . '/logs/monitor.log';

        // Ensure logs directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Run the email monitoring process
     */
    public function run()
    {
        $this->log("Starting email monitor...");

        // Check if monitoring is enabled
        if (!$this->isMonitoringEnabled()) {
            $this->log("Email monitoring is disabled. Exiting.");
            return;
        }

        try {
            // Initialize services
            $this->initializeServices();

            // Fetch unread emails
            $emails = $this->fetchUnreadEmails();

            if (empty($emails)) {
                $this->log("No unread emails found.");
                return;
            }

            $this->log("Found " . count($emails) . " unread email(s).");

            // Process each email
            foreach ($emails as $email) {
                $this->processEmail($email);
            }

            $this->log("Email monitoring completed successfully.");

        } catch (Exception $e) {
            $this->log("Error: " . $e->getMessage(), 'error');
            $this->notifyError($e);
        }
    }

    /**
     * Initialize services
     */
    private function initializeServices()
    {
        $this->imapService = new ImapService();
        $this->aiService = new AiService();
        $this->notificationService = new NotificationService();

        // Optional services
        try {
            $this->sheetsService = new SheetsService();
        } catch (Exception $e) {
            $this->log("Sheets service not available: " . $e->getMessage(), 'warning');
        }

        try {
            $this->slackService = new SlackService();
        } catch (Exception $e) {
            $this->log("Slack service not available: " . $e->getMessage(), 'warning');
        }
    }

    /**
     * Check if monitoring is enabled
     */
    private function isMonitoringEnabled()
    {
        $setting = $this->db->fetch(
            "SELECT setting_value FROM system_settings WHERE setting_key = 'email_monitor_enabled'"
        );

        return $setting && $setting['setting_value'] === 'true';
    }

    /**
     * Fetch unread emails from IMAP
     */
    private function fetchUnreadEmails()
    {
        return $this->imapService->getUnreadEmails(20);
    }

    /**
     * Process a single email
     */
    private function processEmail($email)
    {
        $this->log("Processing email from {$email['from_email']}: {$email['subject']}");

        try {
            // 1. Extract case number from email
            $caseNumber = $this->extractCaseNumber($email);

            if (!$caseNumber) {
                $this->log("Could not extract case number from email. Skipping.");
                $this->imapService->markAsRead($email['message_number']);
                return;
            }

            $this->log("Found case number: {$caseNumber}");

            // 2. Find case in database
            $case = $this->findCase($caseNumber);

            if (!$case) {
                $this->log("Case not found in database: {$caseNumber}");
                $this->imapService->markAsRead($email['message_number']);
                return;
            }

            // 3. Analyze email with AI
            $analysis = $this->analyzeEmail($email, $case);

            $this->log("AI Analysis: Status = {$analysis['status']}, Confidence = {$analysis['confidence']}");

            // 4. Update case status
            $this->updateCaseStatus($case, $analysis, $email);

            // 5. Update Google Sheets
            $this->updateGoogleSheets($case, $analysis);

            // 6. Log email
            $this->logEmail($case, $email, $analysis);

            // 7. Send notifications
            $this->sendNotifications($case, $analysis);

            // 8. Mark email as read
            $this->imapService->markAsRead($email['message_number']);

            $this->log("Email processed successfully for case {$caseNumber}");

        } catch (Exception $e) {
            $this->log("Error processing email: " . $e->getMessage(), 'error');
        }
    }

    /**
     * Extract case number from email
     */
    private function extractCaseNumber($email)
    {
        // Try to extract from subject first
        $caseNumber = $this->aiService->extractCaseNumber($email['subject']);

        if (!$caseNumber) {
            // Try to extract from body
            $body = $email['body_plain'] ?: strip_tags($email['body']);
            $caseNumber = $this->aiService->extractCaseNumber($body);
        }

        return $caseNumber;
    }

    /**
     * Find case in database
     */
    private function findCase($caseNumber)
    {
        return $this->db->fetch(
            "SELECT c.*, cl.name as client_name, cl.email as client_email,
                    cl.phone as client_phone, cl.type as client_type,
                    cl.location as client_location, cl.notification_pref as client_notification_pref
             FROM cases c
             LEFT JOIN clients cl ON c.client_id = cl.id
             WHERE c.case_number = ?",
            [$caseNumber]
        );
    }

    /**
     * Analyze email with AI
     */
    private function analyzeEmail($email, $case)
    {
        $content = $email['body_plain'] ?: strip_tags($email['body']);

        $context = [
            'case_number' => $case['case_number'],
            'patient_name' => $case['patient_name'],
            'client_name' => $case['client_name'],
            'current_status' => $case['status'],
        ];

        return $this->aiService->analyzeEmail($content, $context);
    }

    /**
     * Update case status in database
     */
    private function updateCaseStatus($case, $analysis, $email)
    {
        $newStatus = $analysis['status'];
        $resolvedAt = ($newStatus === 'Resolved') ? ", resolved_at = NOW()" : "";

        $this->db->update(
            "UPDATE cases
             SET status = ?,
                 last_email_received_at = NOW(),
                 notes = CONCAT(IFNULL(notes, ''), '\n[', NOW(), '] AI Analysis: ', ?)
                 {$resolvedAt}
             WHERE id = ?",
            [
                $newStatus,
                $analysis['reasoning'],
                $case['id'],
            ]
        );

        $this->log("Case {$case['case_number']} status updated to {$newStatus}");
    }

    /**
     * Update Google Sheets
     */
    private function updateGoogleSheets($case, $analysis)
    {
        if (!$this->sheetsService) {
            return;
        }

        try {
            // Get product sheet IDs for this case
            $products = $this->db->fetchAll(
                "SELECT p.google_sheet_id, p.case_col
                 FROM case_products cp
                 JOIN products p ON cp.product_id = p.id
                 WHERE cp.case_id = ? AND p.google_sheet_id IS NOT NULL",
                [$case['id']]
            );

            foreach ($products as $product) {
                if (empty($product['google_sheet_id'])) {
                    continue;
                }

                // Find the case row in the sheet
                $row = $this->sheetsService->findCase(
                    $product['google_sheet_id'],
                    $case['case_number'],
                    $product['case_col'] ?? 'A'
                );

                if ($row) {
                    // Update row color based on status
                    $this->sheetsService->updateRowColor(
                        $product['google_sheet_id'],
                        $row['row_number'],
                        $analysis['status']
                    );

                    $this->log("Updated Google Sheet row {$row['row_number']}");
                }
            }

        } catch (Exception $e) {
            $this->log("Google Sheets update failed: " . $e->getMessage(), 'warning');
        }
    }

    /**
     * Log email to database
     */
    private function logEmail($case, $email, $analysis)
    {
        $this->db->insert(
            "INSERT INTO email_logs
             (case_id, client_id, direction, from_email, to_email, subject, body, ai_analysis, status)
             VALUES (?, ?, 'Inbound', ?, ?, ?, ?, ?, 'Processed')",
            [
                $case['id'],
                $case['client_id'],
                $email['from_email'],
                $email['to_email'],
                $email['subject'],
                substr($email['body_plain'] ?? $email['body'], 0, 10000),
                json_encode($analysis),
            ]
        );
    }

    /**
     * Send notifications
     */
    private function sendNotifications($case, $analysis)
    {
        $caseData = [
            'case_number' => $case['case_number'],
            'patient_name' => $case['patient_name'],
            'client_name' => $case['client_name'],
            'client_email' => $case['client_email'],
            'client_phone' => $case['client_phone'],
            'client_location' => $case['client_location'],
            'client_notification_pref' => $case['client_notification_pref'],
            'notes' => $analysis['reasoning'],
        ];

        $results = $this->notificationService->notifyCaseStatusChange(
            $caseData,
            $analysis['status'],
            ['notes' => $analysis['reasoning']]
        );

        $this->log("Notifications sent: " . json_encode($results));
    }

    /**
     * Notify error to admin
     */
    private function notifyError($exception)
    {
        if (!$this->slackService) {
            return;
        }

        try {
            $message = ":warning: *Email Monitor Error*\n" .
                       "```" . $exception->getMessage() . "```\n" .
                       "Time: " . date('Y-m-d H:i:s');

            $this->slackService->sendMessage($message);
        } catch (Exception $e) {
            // Silently fail
        }
    }

    /**
     * Log message
     */
    private function log($message, $level = 'info')
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}\n";

        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);

        // Also output to console if running from CLI
        if (php_sapi_name() === 'cli') {
            echo $logMessage;
        }
    }
}

// Run the monitor
$monitor = new EmailMonitor();
$monitor->run();
