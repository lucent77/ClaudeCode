<?php
/**
 * User 모델
 */
class User {
    private $conn;
    private $table = "users";

    public $id;
    public $username;
    public $password;
    public $name;
    public $role;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * 사용자 인증
     */
    public function login() {
        $query = "SELECT id, username, password, name, role
                  FROM " . $this->table . "
                  WHERE username = :username
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $this->username);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && password_verify($this->password, $row['password'])) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->role = $row['role'];
            return true;
        }

        return false;
    }

    /**
     * 사용자 생성
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                  (username, password, name, role)
                  VALUES
                  (:username, :password, :name, :role)";

        $stmt = $this->conn->prepare($query);

        // 비밀번호 해시
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);

        // 바인딩
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":role", $this->role);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    /**
     * 사용자 조회
     */
    public function readOne() {
        $query = "SELECT id, username, name, role
                  FROM " . $this->table . "
                  WHERE id = :id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->username = $row['username'];
            $this->name = $row['name'];
            $this->role = $row['role'];
            return true;
        }

        return false;
    }
}
?>
