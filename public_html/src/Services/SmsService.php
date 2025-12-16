<?php
/**
 * SMS Service
 *
 * Handles SMS sending via Twilio
 */

class SmsService
{
    private $accountSid;
    private $authToken;
    private $fromNumber;
    private $apiUrl = 'https://api.twilio.com/2010-04-01';

    public function __construct()
    {
        $this->accountSid = env('TWILIO_SID');
        $this->authToken = env('TWILIO_TOKEN');
        $this->fromNumber = env('TWILIO_FROM_NUMBER');

        if (empty($this->accountSid) || empty($this->authToken)) {
            $this->log('Twilio credentials not configured', 'warning');
        }
    }

    /**
     * Send an SMS message
     *
     * @param string $to Recipient phone number (E.164 format preferred)
     * @param string $message The message content
     * @return array Result with success status and message SID
     */
    public function send($to, $message)
    {
        if (empty($this->accountSid) || empty($this->authToken)) {
            throw new Exception('Twilio is not configured');
        }

        // Format phone number
        $to = $this->formatPhoneNumber($to);

        // Validate message length
        if (strlen($message) > 1600) {
            throw new Exception('Message exceeds maximum length of 1600 characters');
        }

        $url = "{$this->apiUrl}/Accounts/{$this->accountSid}/Messages.json";

        $data = [
            'From' => $this->fromNumber,
            'To' => $to,
            'Body' => $message,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_USERPWD => "{$this->accountSid}:{$this->authToken}",
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->log("Curl error: {$error}", 'error');
            throw new Exception("Failed to send SMS: {$error}");
        }

        $result = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMessage = $result['message'] ?? 'Unknown error';
            $this->log("Twilio error ({$httpCode}): {$errorMessage}", 'error');
            throw new Exception("SMS failed: {$errorMessage}");
        }

        $this->log("SMS sent to {$to}, SID: {$result['sid']}");

        return [
            'success' => true,
            'sid' => $result['sid'],
            'status' => $result['status'],
            'to' => $result['to'],
        ];
    }

    /**
     * Send a case notification SMS
     *
     * @param string $to Recipient phone number
     * @param array $caseData Case information
     * @param string $status The new status
     * @return array
     */
    public function sendCaseNotification($to, $caseData, $status)
    {
        $statusMessages = [
            'Confirmed' => 'has been CONFIRMED',
            'Action Needed' => 'requires ACTION',
            'Confirmed with Action Needed' => 'is CONFIRMED with minor notes',
            'Pending' => 'is pending review',
            'Resolved' => 'has been RESOLVED',
        ];

        $statusText = $statusMessages[$status] ?? 'has been updated';

        $message = "Design Confirm System\n\n" .
                   "Case {$caseData['case_number']} {$statusText}.\n" .
                   "Patient: {$caseData['patient_name']}\n" .
                   "Please check your email for details.";

        return $this->send($to, $message);
    }

    /**
     * Format phone number to E.164 format
     *
     * @param string $phoneNumber The phone number
     * @return string Formatted phone number
     */
    private function formatPhoneNumber($phoneNumber)
    {
        // Remove all non-numeric characters except +
        $cleaned = preg_replace('/[^0-9+]/', '', $phoneNumber);

        // If doesn't start with +, assume US number
        if (strpos($cleaned, '+') !== 0) {
            // Remove leading 1 if present
            if (strlen($cleaned) === 11 && $cleaned[0] === '1') {
                $cleaned = substr($cleaned, 1);
            }

            // Add US country code
            if (strlen($cleaned) === 10) {
                $cleaned = '+1' . $cleaned;
            }
        }

        return $cleaned;
    }

    /**
     * Check if SMS service is available
     *
     * @return bool
     */
    public function isAvailable()
    {
        return !empty($this->accountSid) &&
               !empty($this->authToken) &&
               !empty($this->fromNumber);
    }

    /**
     * Get account balance
     *
     * @return array|null
     */
    public function getBalance()
    {
        if (!$this->isAvailable()) {
            return null;
        }

        $url = "{$this->apiUrl}/Accounts/{$this->accountSid}/Balance.json";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => "{$this->accountSid}:{$this->authToken}",
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Get message status
     *
     * @param string $messageSid The message SID
     * @return array|null
     */
    public function getMessageStatus($messageSid)
    {
        if (!$this->isAvailable()) {
            return null;
        }

        $url = "{$this->apiUrl}/Accounts/{$this->accountSid}/Messages/{$messageSid}.json";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => "{$this->accountSid}:{$this->authToken}",
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Log SMS service activity
     */
    private function log($message, $level = 'info')
    {
        $logFile = dirname(dirname(__DIR__)) . '/logs/sms.log';
        $timestamp = date('Y-m-d H:i:s');

        $logMessage = "[{$timestamp}] [{$level}] {$message}\n";

        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}
