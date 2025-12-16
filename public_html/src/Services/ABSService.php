<?php
/**
 * ABS Service
 *
 * Handles ABS (Dental Lab Management) API integration
 */

class ABSService
{
    private $username;
    private $password;
    private $apiUrlNYC;
    private $apiUrlHV;
    private $noteTypeId;

    public function __construct()
    {
        $this->username = env('ABS_USERNAME');
        $this->password = env('ABS_PASSWORD');
        $this->apiUrlNYC = env('ABS_API_URL_NYC');
        $this->apiUrlHV = env('ABS_API_URL_HV');
        $this->noteTypeId = env('ABS_NOTE_TYPE_ID', 1);

        if (empty($this->username) || empty($this->password)) {
            $this->log('ABS credentials not configured', 'warning');
        }
    }

    /**
     * Add a note to a case in ABS
     *
     * @param string $caseNumber The case number
     * @param string $noteText The note content
     * @param string $location Location ('NYC' or 'HV')
     * @return bool
     */
    public function addCaseNote($caseNumber, $noteText, $location = 'NYC')
    {
        if (!$this->isAvailable()) {
            throw new Exception('ABS service is not configured');
        }

        $apiUrl = $this->getApiUrl($location);

        if (empty($apiUrl)) {
            throw new Exception("API URL not configured for location: {$location}");
        }

        $xmlPayload = $this->buildNoteXml($caseNumber, $noteText);

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xmlPayload,
            CURLOPT_USERPWD => "{$this->username}:{$this->password}",
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/xml',
                'Accept: application/xml',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->log("ABS API error: {$error}", 'error');
            throw new Exception("ABS API request failed: {$error}");
        }

        if ($httpCode >= 400) {
            $this->log("ABS API returned {$httpCode}: {$response}", 'error');
            return false;
        }

        $this->log("Note added to case {$caseNumber} in {$location}");
        return true;
    }

    /**
     * Add a status update note
     *
     * @param string $caseNumber The case number
     * @param string $status The new status
     * @param string $details Additional details
     * @param string $location Location ('NYC' or 'HV')
     * @return bool
     */
    public function addStatusNote($caseNumber, $status, $details = '', $location = 'NYC')
    {
        $noteText = "Design Status Update: {$status}";

        if (!empty($details)) {
            $noteText .= "\n\nDetails: {$details}";
        }

        $noteText .= "\n\nUpdated via Design Confirm System at " . date('Y-m-d H:i:s');

        return $this->addCaseNote($caseNumber, $noteText, $location);
    }

    /**
     * Build XML payload for adding a note
     */
    private function buildNoteXml($caseNumber, $noteText)
    {
        // Escape special characters for XML
        $noteText = htmlspecialchars($noteText, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $caseNumber = htmlspecialchars($caseNumber, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $timestamp = date('Y-m-d\TH:i:s');

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<CaseNote>
    <CaseNumber>{$caseNumber}</CaseNumber>
    <NoteTypeId>{$this->noteTypeId}</NoteTypeId>
    <NoteText>{$noteText}</NoteText>
    <CreatedAt>{$timestamp}</CreatedAt>
    <Source>Design Confirm System</Source>
</CaseNote>
XML;

        return $xml;
    }

    /**
     * Get API URL for location
     */
    private function getApiUrl($location)
    {
        $location = strtoupper($location);

        switch ($location) {
            case 'NYC':
                return $this->apiUrlNYC;
            case 'HV':
                return $this->apiUrlHV;
            default:
                return $this->apiUrlNYC; // Default to NYC
        }
    }

    /**
     * Get case information from ABS
     *
     * @param string $caseNumber The case number
     * @param string $location Location ('NYC' or 'HV')
     * @return array|null
     */
    public function getCaseInfo($caseNumber, $location = 'NYC')
    {
        if (!$this->isAvailable()) {
            return null;
        }

        $apiUrl = $this->getApiUrl($location);

        if (empty($apiUrl)) {
            return null;
        }

        // Build case lookup URL (adjust based on actual ABS API structure)
        $url = rtrim($apiUrl, '/') . '/cases/' . urlencode($caseNumber);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => "{$this->username}:{$this->password}",
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
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
     * Update case status in ABS
     *
     * @param string $caseNumber The case number
     * @param string $status The new status
     * @param string $location Location ('NYC' or 'HV')
     * @return bool
     */
    public function updateCaseStatus($caseNumber, $status, $location = 'NYC')
    {
        if (!$this->isAvailable()) {
            throw new Exception('ABS service is not configured');
        }

        $apiUrl = $this->getApiUrl($location);

        if (empty($apiUrl)) {
            throw new Exception("API URL not configured for location: {$location}");
        }

        // Map status to ABS status codes (adjust based on actual ABS API)
        $statusMap = [
            'Confirmed' => 'DESIGN_CONFIRMED',
            'Action Needed' => 'DESIGN_REVISION',
            'Confirmed with Action Needed' => 'DESIGN_CONFIRMED_WITH_NOTES',
            'Pending' => 'DESIGN_PENDING',
            'Resolved' => 'COMPLETED',
        ];

        $absStatus = $statusMap[$status] ?? 'UNKNOWN';

        $xmlPayload = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<CaseStatusUpdate>
    <CaseNumber>{$caseNumber}</CaseNumber>
    <Status>{$absStatus}</Status>
    <UpdatedAt>{date('Y-m-d\TH:i:s')}</UpdatedAt>
    <Source>Design Confirm System</Source>
</CaseStatusUpdate>
XML;

        $url = rtrim($apiUrl, '/') . '/cases/' . urlencode($caseNumber) . '/status';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $xmlPayload,
            CURLOPT_USERPWD => "{$this->username}:{$this->password}",
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/xml',
                'Accept: application/xml',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->log("ABS status update error: {$error}", 'error');
            return false;
        }

        if ($httpCode >= 400) {
            $this->log("ABS status update failed ({$httpCode}): {$response}", 'error');
            return false;
        }

        $this->log("Status updated for case {$caseNumber} to {$status}");
        return true;
    }

    /**
     * Check if ABS service is available
     *
     * @return bool
     */
    public function isAvailable()
    {
        return !empty($this->username) &&
               !empty($this->password) &&
               (!empty($this->apiUrlNYC) || !empty($this->apiUrlHV));
    }

    /**
     * Test ABS connection
     *
     * @param string $location Location to test
     * @return bool
     */
    public function testConnection($location = 'NYC')
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $apiUrl = $this->getApiUrl($location);

        if (empty($apiUrl)) {
            return false;
        }

        $url = rtrim($apiUrl, '/') . '/health';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => "{$this->username}:{$this->password}",
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    /**
     * Log ABS service activity
     */
    private function log($message, $level = 'info')
    {
        $logFile = dirname(dirname(__DIR__)) . '/logs/abs.log';
        $timestamp = date('Y-m-d H:i:s');

        $logMessage = "[{$timestamp}] [{$level}] {$message}\n";

        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}
