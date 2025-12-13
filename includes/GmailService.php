<?php
/**
 * Gmail Service
 * Handles Gmail API integration for Design Confirm emails
 */

class GmailService
{
    private Database $db;
    private ?string $accessToken = null;
    private static ?GmailService $instance = null;

    private const API_BASE = 'https://gmail.googleapis.com/gmail/v1/users/me';

    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->loadToken();
    }

    public static function getInstance(): GmailService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Check if Gmail is configured and enabled
     */
    public function isEnabled(): bool
    {
        return GMAIL_ENABLED && !empty($this->accessToken);
    }

    /**
     * Load access token from database
     */
    private function loadToken(): void
    {
        if (!GMAIL_ENABLED) {
            return;
        }

        $token = $this->db->fetchOne(
            "SELECT * FROM google_tokens WHERE token_type = 'GMAIL' AND user_id IS NULL ORDER BY id DESC LIMIT 1"
        );

        if ($token) {
            if (strtotime($token['expires_at']) <= time()) {
                if ($token['refresh_token']) {
                    $this->refreshToken($token['refresh_token'], $token['id']);
                }
            } else {
                $this->accessToken = $token['access_token'];
            }
        }
    }

    /**
     * Refresh access token
     */
    private function refreshToken(string $refreshToken, int $tokenId): void
    {
        $response = $this->httpRequest('https://oauth2.googleapis.com/token', 'POST', [
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token'
        ], false);

        if (!empty($response['access_token'])) {
            $expiresAt = date('Y-m-d H:i:s', time() + ($response['expires_in'] ?? 3600));

            $this->db->update('google_tokens', [
                'access_token' => $response['access_token'],
                'expires_at' => $expiresAt
            ], ['id' => $tokenId]);

            $this->accessToken = $response['access_token'];
        }
    }

    /**
     * Get OAuth authorization URL
     */
    public function getAuthUrl(): string
    {
        $params = [
            'client_id' => GOOGLE_CLIENT_ID,
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/gmail.send https://www.googleapis.com/auth/gmail.readonly',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => 'gmail'
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * Handle OAuth callback
     */
    public function handleCallback(string $code): bool
    {
        $response = $this->httpRequest('https://oauth2.googleapis.com/token', 'POST', [
            'code' => $code,
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'grant_type' => 'authorization_code'
        ], false);

        if (!empty($response['access_token'])) {
            $expiresAt = date('Y-m-d H:i:s', time() + ($response['expires_in'] ?? 3600));

            $this->db->insert('google_tokens', [
                'token_type' => 'GMAIL',
                'access_token' => $response['access_token'],
                'refresh_token' => $response['refresh_token'] ?? null,
                'expires_at' => $expiresAt,
                'scope' => $response['scope'] ?? null
            ]);

            $this->accessToken = $response['access_token'];
            return true;
        }

        return false;
    }

    /**
     * Send Design Confirm email
     */
    public function sendDesignConfirmEmail(int $caseId, array $caseData, string $recipientEmail, ?string $recipientName = null): array
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'error' => 'Gmail integration not enabled'];
        }

        // Prepare email content
        $subject = $this->buildSubject($caseData);
        $htmlBody = $this->buildEmailBody($caseData);

        // Get design images if available
        $driveService = GoogleDriveService::getInstance();
        $files = $driveService->getCaseFiles($caseId, $caseData['department'] ?? null);
        $imageUrls = array_filter(array_column($files, 'thumbnail_url'));

        // Build MIME message
        $message = $this->buildMimeMessage(
            $recipientEmail,
            $recipientName,
            $subject,
            $htmlBody
        );

        // Send via Gmail API
        $response = $this->httpRequest(self::API_BASE . '/messages/send', 'POST', [
            'raw' => $this->base64UrlEncode($message)
        ]);

        if (!empty($response['id'])) {
            // Record email in database
            $emailId = $this->db->insert('design_confirm_emails', [
                'case_id' => $caseId,
                'department' => $caseData['department'] ?? 'COCR',
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'subject' => $subject,
                'body_html' => $htmlBody,
                'image_urls' => json_encode($imageUrls),
                'drive_links' => json_encode($caseData['drive_links'] ?? []),
                'gmail_message_id' => $response['id'],
                'gmail_thread_id' => $response['threadId'] ?? null,
                'status' => 'SENT',
                'sent_at' => date('Y-m-d H:i:s'),
                'sent_by' => Auth::getInstance()->userId()
            ]);

            // Update case design confirm status
            $this->db->update('cases', [
                'design_confirm_status' => DESIGN_CONFIRM_SENT,
                'design_confirm_sent_at' => date('Y-m-d H:i:s')
            ], ['id' => $caseId]);

            return [
                'success' => true,
                'message_id' => $response['id'],
                'email_record_id' => $emailId
            ];
        }

        // Log failure
        $errorMsg = $response['error']['message'] ?? 'Unknown error sending email';

        $this->db->insert('design_confirm_emails', [
            'case_id' => $caseId,
            'department' => $caseData['department'] ?? 'COCR',
            'recipient_email' => $recipientEmail,
            'recipient_name' => $recipientName,
            'subject' => $subject,
            'body_html' => $htmlBody,
            'status' => 'FAILED',
            'error_message' => $errorMsg,
            'sent_by' => Auth::getInstance()->userId()
        ]);

        return ['success' => false, 'error' => $errorMsg];
    }

    /**
     * Build email subject
     */
    private function buildSubject(array $caseData): string
    {
        return sprintf(
            'Design Confirmation Required - Case #%s - %s',
            $caseData['case_number'] ?? 'N/A',
            $caseData['patient_name'] ?? $caseData['lab_name'] ?? 'Patient'
        );
    }

    /**
     * Build email HTML body
     */
    private function buildEmailBody(array $caseData): string
    {
        $caseUrl = APP_URL . '/cases/view.php?id=' . ($caseData['id'] ?? '');
        $driveFolderUrl = $caseData['drive_folder_url'] ?? '#';

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9fafb; }
        .details { background: white; padding: 15px; margin: 15px 0; border-radius: 8px; }
        .details-row { display: flex; padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        .details-label { font-weight: bold; width: 40%; }
        .details-value { width: 60%; }
        .button { display: inline-block; padding: 12px 24px; background: #2563eb; color: white; text-decoration: none; border-radius: 6px; margin: 10px 5px; }
        .button-secondary { background: #6b7280; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
        .instructions { background: #fef3c7; padding: 15px; border-radius: 8px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Design Confirmation Required</h1>
        </div>

        <div class="content">
            <p>Dear Doctor,</p>

            <p>The design for the following case has been completed and requires your confirmation before proceeding to manufacturing.</p>

            <div class="details">
                <div class="details-row">
                    <span class="details-label">Case Number:</span>
                    <span class="details-value">{$caseData['case_number']}</span>
                </div>
                <div class="details-row">
                    <span class="details-label">Patient:</span>
                    <span class="details-value">{$caseData['patient_name']}</span>
                </div>
                <div class="details-row">
                    <span class="details-label">Lab:</span>
                    <span class="details-value">{$caseData['lab_name']}</span>
                </div>
                <div class="details-row">
                    <span class="details-label">Tooth Numbers:</span>
                    <span class="details-value">{$caseData['tooth_numbers']}</span>
                </div>
                <div class="details-row">
                    <span class="details-label">Due Date:</span>
                    <span class="details-value">{$caseData['due_date']}</span>
                </div>
            </div>

            <div class="instructions">
                <strong>Instructions/Preferences:</strong><br>
                {$caseData['instructions']}
                <br><br>
                {$caseData['preferences']}
            </div>

            <p style="text-align: center;">
                <a href="{$driveFolderUrl}" class="button">View Design Files</a>
                <a href="{$caseUrl}" class="button button-secondary">View Case Details</a>
            </p>

            <p>Please reply to this email with one of the following:</p>
            <ul>
                <li><strong>APPROVED</strong> - To proceed with manufacturing</li>
                <li><strong>CHANGES REQUESTED</strong> - With specific changes needed</li>
            </ul>

            <p>Thank you for your prompt response.</p>

            <p>
                Best regards,<br>
                CAD/CAM Workflow System
            </p>
        </div>

        <div class="footer">
            <p>This is an automated message from the CAD/CAM Workflow System.</p>
            <p>Case #: {$caseData['case_number']} | Site: {$caseData['site']}</p>
        </div>
    </div>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * Build MIME message
     */
    private function buildMimeMessage(string $to, ?string $toName, string $subject, string $htmlBody): string
    {
        $fromEmail = 'noreply@cadcam-workflow.com'; // Should be configured
        $boundary = uniqid('boundary');

        $toHeader = $toName ? "{$toName} <{$to}>" : $to;

        $message = "From: CAD/CAM Workflow <{$fromEmail}>\r\n";
        $message .= "To: {$toHeader}\r\n";
        $message .= "Subject: {$subject}\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n\r\n";

        // Plain text version
        $plainText = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $htmlBody));
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $message .= $plainText . "\r\n\r\n";

        // HTML version
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $message .= $htmlBody . "\r\n\r\n";

        $message .= "--{$boundary}--";

        return $message;
    }

    /**
     * Base64 URL encode
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Update design confirm status based on reply
     */
    public function updateDesignConfirmStatus(int $emailId, string $status, ?string $notes = null): bool
    {
        $email = $this->db->fetchOne("SELECT * FROM design_confirm_emails WHERE id = ?", [$emailId]);

        if (!$email) {
            return false;
        }

        $this->db->update('design_confirm_emails', [
            'status' => $status,
            'response_received_at' => date('Y-m-d H:i:s'),
            'response_notes' => $notes
        ], ['id' => $emailId]);

        // Update case status
        $this->db->update('cases', [
            'design_confirm_status' => $status,
            'design_confirm_response_at' => date('Y-m-d H:i:s')
        ], ['id' => $email['case_id']]);

        return true;
    }

    /**
     * Get email history for a case
     */
    public function getEmailHistory(int $caseId): array
    {
        return $this->db->fetchAll(
            "SELECT e.*, u.full_name as sent_by_name
             FROM design_confirm_emails e
             LEFT JOIN users u ON e.sent_by = u.id
             WHERE e.case_id = ?
             ORDER BY e.created_at DESC",
            [$caseId]
        );
    }

    /**
     * Get pending design confirms
     */
    public function getPendingDesignConfirms(): array
    {
        return $this->db->fetchAll(
            "SELECT c.*, e.id as email_id, e.sent_at, e.recipient_email,
                    DATEDIFF(NOW(), e.sent_at) as days_waiting
             FROM cases c
             INNER JOIN design_confirm_emails e ON c.id = e.case_id
             WHERE c.design_confirm_status = 'SENT'
             ORDER BY e.sent_at ASC"
        );
    }

    /**
     * HTTP request helper
     */
    private function httpRequest(string $url, string $method = 'GET', $data = null, bool $useAuth = true): ?array
    {
        $ch = curl_init($url);

        $headers = [];

        if ($useAuth && $this->accessToken) {
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }

        $headers[] = 'Content-Type: application/json';

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            if ($data) {
                $options[CURLOPT_POSTFIELDS] = is_string($data) ? $data : json_encode($data);
            }
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return $decoded;
        }

        error_log("Gmail API error: HTTP $httpCode - $response");
        return $decoded; // Return error response for handling
    }

    private function __clone() {}
    public function __wakeup() { throw new Exception('Cannot unserialize singleton'); }
}
