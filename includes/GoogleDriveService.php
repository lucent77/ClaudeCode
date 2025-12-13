<?php
/**
 * Google Drive Service
 * Handles Google Drive API integration for file management
 */

class GoogleDriveService
{
    private Database $db;
    private ?string $accessToken = null;
    private static ?GoogleDriveService $instance = null;

    private const API_BASE = 'https://www.googleapis.com/drive/v3';
    private const UPLOAD_BASE = 'https://www.googleapis.com/upload/drive/v3';

    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->loadToken();
    }

    public static function getInstance(): GoogleDriveService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Check if Google Drive is configured and enabled
     */
    public function isEnabled(): bool
    {
        return GOOGLE_DRIVE_ENABLED && !empty($this->accessToken);
    }

    /**
     * Load access token from database
     */
    private function loadToken(): void
    {
        if (!GOOGLE_DRIVE_ENABLED) {
            return;
        }

        $token = $this->db->fetchOne(
            "SELECT * FROM google_tokens WHERE token_type = 'DRIVE' AND user_id IS NULL ORDER BY id DESC LIMIT 1"
        );

        if ($token) {
            // Check if token is expired
            if (strtotime($token['expires_at']) <= time()) {
                // Try to refresh
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
            'scope' => 'https://www.googleapis.com/auth/drive.file',
            'access_type' => 'offline',
            'prompt' => 'consent'
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
                'token_type' => 'DRIVE',
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
     * List files in a folder
     */
    public function listFiles(string $folderId, int $maxResults = 100): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $params = [
            'q' => "'$folderId' in parents and trashed = false",
            'fields' => 'files(id,name,mimeType,size,createdTime,modifiedTime,webViewLink,thumbnailLink)',
            'pageSize' => $maxResults,
            'orderBy' => 'modifiedTime desc'
        ];

        $response = $this->httpRequest(self::API_BASE . '/files?' . http_build_query($params));

        return $response['files'] ?? [];
    }

    /**
     * Get file metadata
     */
    public function getFile(string $fileId): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $params = [
            'fields' => 'id,name,mimeType,size,createdTime,modifiedTime,webViewLink,thumbnailLink,parents'
        ];

        return $this->httpRequest(self::API_BASE . '/files/' . $fileId . '?' . http_build_query($params));
    }

    /**
     * Create a folder
     */
    public function createFolder(string $name, ?string $parentId = null): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $metadata = [
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder'
        ];

        if ($parentId) {
            $metadata['parents'] = [$parentId];
        }

        return $this->httpRequest(self::API_BASE . '/files', 'POST', $metadata);
    }

    /**
     * Upload a file
     */
    public function uploadFile(string $filePath, string $fileName, ?string $folderId = null, ?string $mimeType = null): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $mimeType = $mimeType ?? mime_content_type($filePath);
        $fileContent = file_get_contents($filePath);

        $metadata = [
            'name' => $fileName
        ];

        if ($folderId) {
            $metadata['parents'] = [$folderId];
        }

        // Use multipart upload
        $boundary = '-------' . uniqid();

        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $body .= json_encode($metadata) . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: {$mimeType}\r\n\r\n";
        $body .= $fileContent . "\r\n";
        $body .= "--{$boundary}--";

        $response = $this->httpRequest(
            self::UPLOAD_BASE . '/files?uploadType=multipart&fields=id,name,webViewLink,thumbnailLink',
            'POST',
            $body,
            true,
            "multipart/related; boundary={$boundary}"
        );

        return $response;
    }

    /**
     * Download a file
     */
    public function downloadFile(string $fileId): ?string
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $ch = curl_init(self::API_BASE . '/files/' . $fileId . '?alt=media');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200 ? $response : null;
    }

    /**
     * Delete a file
     */
    public function deleteFile(string $fileId): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $ch = curl_init(self::API_BASE . '/files/' . $fileId);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken
            ]
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 204;
    }

    /**
     * Create case folder structure
     */
    public function createCaseFolderStructure(string $caseNumber, array $departments): ?array
    {
        if (!$this->isEnabled() || empty(DRIVE_BASE_FOLDER)) {
            return null;
        }

        // Create main case folder
        $caseFolder = $this->createFolder($caseNumber, DRIVE_BASE_FOLDER);

        if (!$caseFolder) {
            return null;
        }

        $folders = ['main' => $caseFolder];

        // Create department subfolders
        foreach ($departments as $dept) {
            $deptFolder = $this->createFolder($dept, $caseFolder['id']);
            if ($deptFolder) {
                $folders[$dept] = $deptFolder;
            }
        }

        return $folders;
    }

    /**
     * Get folder URL
     */
    public function getFolderUrl(string $folderId): string
    {
        return "https://drive.google.com/drive/folders/{$folderId}";
    }

    /**
     * Save file record to database
     */
    public function saveFileRecord(int $caseId, string $department, array $fileData, ?string $stepCode = null): int
    {
        return $this->db->insert('case_files', [
            'case_id' => $caseId,
            'department' => $department,
            'step_code' => $stepCode,
            'file_name' => $fileData['name'],
            'file_type' => $fileData['mimeType'] ?? null,
            'file_size' => $fileData['size'] ?? null,
            'drive_file_id' => $fileData['id'],
            'drive_file_url' => $fileData['webViewLink'] ?? '',
            'drive_folder_id' => $fileData['parents'][0] ?? null,
            'thumbnail_url' => $fileData['thumbnailLink'] ?? null,
            'uploaded_by' => Auth::getInstance()->userId()
        ]);
    }

    /**
     * Get case files from database
     */
    public function getCaseFiles(int $caseId, ?string $department = null): array
    {
        $where = 'case_id = ?';
        $params = [$caseId];

        if ($department) {
            $where .= ' AND department = ?';
            $params[] = $department;
        }

        return $this->db->fetchAll(
            "SELECT cf.*, u.full_name as uploaded_by_name
             FROM case_files cf
             LEFT JOIN users u ON cf.uploaded_by = u.id
             WHERE $where
             ORDER BY cf.uploaded_at DESC",
            $params
        );
    }

    /**
     * HTTP request helper
     */
    private function httpRequest(string $url, string $method = 'GET', $data = null, bool $useAuth = true, ?string $contentType = null): ?array
    {
        $ch = curl_init($url);

        $headers = [];

        if ($useAuth && $this->accessToken) {
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }

        if ($contentType) {
            $headers[] = 'Content-Type: ' . $contentType;
        } elseif ($data && !is_string($data)) {
            $headers[] = 'Content-Type: application/json';
        }

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

        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        }

        error_log("Google Drive API error: HTTP $httpCode - $response");
        return null;
    }

    private function __clone() {}
    public function __wakeup() { throw new Exception('Cannot unserialize singleton'); }
}
