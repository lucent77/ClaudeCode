<?php

namespace App\Http\Controllers;

use PDO;

class AuthController
{
    private $db;

    public function __construct()
    {
        $config = include(__DIR__ . '/../../../config/database.php');
        $dbConfig = $config['connections']['mysql'];

        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
        $this->db = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Login user
     */
    public function login()
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['email']) || !isset($input['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password are required']);
            return;
        }

        $email = $input['email'];
        $password = $input['password'];

        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid credentials']);
                return;
            }

            // Generate JWT token
            $token = $this->generateJWT($user);

            // Remove password from response
            unset($user['password']);
            unset($user['remember_token']);

            echo json_encode([
                'success' => true,
                'token' => $token,
                'user' => $user,
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Login failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Get current user
     */
    public function me()
    {
        header('Content-Type: application/json');

        $user = $this->getAuthenticatedUser();

        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        unset($user['password']);
        unset($user['remember_token']);

        echo json_encode([
            'success' => true,
            'user' => $user,
        ]);
    }

    /**
     * Logout user
     */
    public function logout()
    {
        header('Content-Type: application/json');

        // In a real JWT implementation, you'd add the token to a blacklist
        echo json_encode([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Generate JWT token
     */
    private function generateJWT($user)
    {
        $jwtConfig = include(__DIR__ . '/../../../config/jwt.php');
        $secret = $jwtConfig['secret'];
        $ttl = $jwtConfig['ttl'] * 60; // Convert minutes to seconds

        $issuedAt = time();
        $expire = $issuedAt + $ttl;

        $header = json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ]);

        $payload = json_encode([
            'iss' => 'creodent-aox-dashboard',
            'sub' => $user['id'],
            'iat' => $issuedAt,
            'exp' => $expire,
            'nbf' => $issuedAt,
            'jti' => bin2hex(random_bytes(16)),
        ]);

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode($payload);

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * Verify JWT token
     */
    private function verifyJWT($token)
    {
        $jwtConfig = include(__DIR__ . '/../../../config/jwt.php');
        $secret = $jwtConfig['secret'];

        $tokenParts = explode('.', $token);

        if (count($tokenParts) !== 3) {
            return null;
        }

        list($header, $payload, $signature) = $tokenParts;

        $signatureCheck = $this->base64UrlEncode(
            hash_hmac('sha256', $header . "." . $payload, $secret, true)
        );

        if ($signature !== $signatureCheck) {
            return null;
        }

        $payloadData = json_decode($this->base64UrlDecode($payload), true);

        if (!$payloadData || $payloadData['exp'] < time()) {
            return null;
        }

        return $payloadData;
    }

    /**
     * Get authenticated user from token
     */
    private function getAuthenticatedUser()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return null;
        }

        $token = $matches[1];
        $payload = $this->verifyJWT($token);

        if (!$payload) {
            return null;
        }

        $userId = $payload['sub'];

        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * Base64 URL encode
     */
    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     */
    private function base64UrlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
