<?php
/**
 * 데이터베이스 연결 설정
 * Hostinger에서 제공하는 MySQL 정보로 업데이트하세요
 */

class Database {
    // Hostinger 데이터베이스 설정 (실제 값으로 변경 필요)
    private $host = "localhost";
    private $db_name = "swissturn_db";
    private $username = "your_db_username";
    private $password = "your_db_password";
    private $charset = "utf8mb4";
    public $conn;

    /**
     * 데이터베이스 연결
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Database connection failed'
            ]);
            exit();
        }

        return $this->conn;
    }
}

/**
 * 데이터베이스 연결 설정 파일 (config.php)
 * Hostinger phpMyAdmin에서 확인한 정보로 업데이트하세요
 */
class Config {
    // Hostinger에서 확인할 정보
    public static $DB_HOST = "localhost";
    public static $DB_NAME = "swissturn_db";
    public static $DB_USER = "your_db_username";
    public static $DB_PASS = "your_db_password";

    // JWT 비밀키 (랜덤한 긴 문자열로 변경하세요)
    public static $JWT_SECRET = "your-secret-key-change-this-in-production";

    // API 설정
    public static $API_URL = "/api";

    // CORS 설정
    public static $ALLOWED_ORIGINS = [
        "http://localhost:3000",
        "https://yourdomain.com"
    ];
}
?>
