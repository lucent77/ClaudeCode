<?php
/**
 * Machine 모델
 */
class Machine {
    private $conn;
    private $table = "machines";

    public $id;
    public $name;
    public $status;
    public $current_job;
    public $runtime;
    public $downtime;
    public $oee;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * 모든 장비 조회
     */
    public function readAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * 특정 장비 조회
     */
    public function readOne() {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->name = $row['name'];
            $this->status = $row['status'];
            $this->current_job = $row['current_job'];
            $this->runtime = $row['runtime'];
            $this->downtime = $row['downtime'];
            $this->oee = $row['oee'];
            return true;
        }

        return false;
    }

    /**
     * 장비 업데이트
     */
    public function update() {
        $query = "UPDATE " . $this->table . "
                  SET status = :status,
                      current_job = :current_job,
                      runtime = :runtime,
                      downtime = :downtime,
                      oee = :oee
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // 바인딩
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":current_job", $this->current_job);
        $stmt->bindParam(":runtime", $this->runtime);
        $stmt->bindParam(":downtime", $this->downtime);
        $stmt->bindParam(":oee", $this->oee);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    /**
     * 장비에 장착된 공구 목록 조회
     */
    public function getTools() {
        $query = "SELECT * FROM tools WHERE machine_id = :machine_id AND status = 'in-use'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":machine_id", $this->id);
        $stmt->execute();
        return $stmt;
    }

    /**
     * OEE 계산
     */
    public function calculateOEE() {
        $total_time = $this->runtime + $this->downtime;
        if ($total_time == 0) {
            return 0;
        }

        $availability = $this->runtime / $total_time;

        // 간단한 OEE 계산 (실제로는 성능과 품질도 고려해야 함)
        // OEE = Availability × Performance × Quality
        // 여기서는 Availability만 사용 (Performance와 Quality는 100%로 가정)
        $oee = $availability * 100;

        return round($oee, 2);
    }
}
?>
