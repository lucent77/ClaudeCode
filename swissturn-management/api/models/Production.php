<?php
/**
 * Production 모델
 */
class Production {
    private $conn;
    private $table = "production_records";

    public $id;
    public $month;
    public $week;
    public $date;
    public $model_name;
    public $sku;
    public $lot_no;
    public $part_length;
    public $worker;
    public $cnc;
    public $diameter;
    public $length;
    public $lot;
    public $unit;
    public $exped_count;
    public $exped_count_24h;
    public $plan;
    public $achievement_1day;
    public $achievement_24h;
    public $achievement_total;
    public $tool_broken_fail_qty;
    public $dent_failed_qty;
    public $dimension_fail_qty;
    public $overnight_fail_qty;
    public $note;
    public $description;
    public $inspected_by;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * 모든 생산 기록 조회
     */
    public function readAll($limit = 100, $offset = 0) {
        $query = "SELECT * FROM " . $this->table . "
                  ORDER BY date DESC, id DESC
                  LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    /**
     * 특정 생산 기록 조회
     */
    public function readOne() {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            foreach ($row as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * 생산 기록 생성
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                  (month, week, date, model_name, sku, lot_no, part_length, worker, cnc,
                   diameter, length, lot, unit, exped_count, exped_count_24h, plan,
                   achievement_1day, achievement_24h, achievement_total,
                   tool_broken_fail_qty, dent_failed_qty, dimension_fail_qty, overnight_fail_qty,
                   note, description, inspected_by)
                  VALUES
                  (:month, :week, :date, :model_name, :sku, :lot_no, :part_length, :worker, :cnc,
                   :diameter, :length, :lot, :unit, :exped_count, :exped_count_24h, :plan,
                   :achievement_1day, :achievement_24h, :achievement_total,
                   :tool_broken_fail_qty, :dent_failed_qty, :dimension_fail_qty, :overnight_fail_qty,
                   :note, :description, :inspected_by)";

        $stmt = $this->conn->prepare($query);

        // 바인딩
        $stmt->bindParam(":month", $this->month);
        $stmt->bindParam(":week", $this->week);
        $stmt->bindParam(":date", $this->date);
        $stmt->bindParam(":model_name", $this->model_name);
        $stmt->bindParam(":sku", $this->sku);
        $stmt->bindParam(":lot_no", $this->lot_no);
        $stmt->bindParam(":part_length", $this->part_length);
        $stmt->bindParam(":worker", $this->worker);
        $stmt->bindParam(":cnc", $this->cnc);
        $stmt->bindParam(":diameter", $this->diameter);
        $stmt->bindParam(":length", $this->length);
        $stmt->bindParam(":lot", $this->lot);
        $stmt->bindParam(":unit", $this->unit);
        $stmt->bindParam(":exped_count", $this->exped_count);
        $stmt->bindParam(":exped_count_24h", $this->exped_count_24h);
        $stmt->bindParam(":plan", $this->plan);
        $stmt->bindParam(":achievement_1day", $this->achievement_1day);
        $stmt->bindParam(":achievement_24h", $this->achievement_24h);
        $stmt->bindParam(":achievement_total", $this->achievement_total);
        $stmt->bindParam(":tool_broken_fail_qty", $this->tool_broken_fail_qty);
        $stmt->bindParam(":dent_failed_qty", $this->dent_failed_qty);
        $stmt->bindParam(":dimension_fail_qty", $this->dimension_fail_qty);
        $stmt->bindParam(":overnight_fail_qty", $this->overnight_fail_qty);
        $stmt->bindParam(":note", $this->note);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":inspected_by", $this->inspected_by);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    /**
     * CSV 데이터 일괄 삽입
     */
    public function bulkInsert($records) {
        try {
            $this->conn->beginTransaction();

            $query = "INSERT INTO " . $this->table . "
                      (month, week, date, model_name, sku, lot_no, part_length, worker, cnc,
                       diameter, length, lot, unit, exped_count, exped_count_24h, plan,
                       achievement_1day, achievement_24h, achievement_total,
                       tool_broken_fail_qty, dent_failed_qty, dimension_fail_qty, overnight_fail_qty,
                       note, description, inspected_by)
                      VALUES
                      (:month, :week, :date, :model_name, :sku, :lot_no, :part_length, :worker, :cnc,
                       :diameter, :length, :lot, :unit, :exped_count, :exped_count_24h, :plan,
                       :achievement_1day, :achievement_24h, :achievement_total,
                       :tool_broken_fail_qty, :dent_failed_qty, :dimension_fail_qty, :overnight_fail_qty,
                       :note, :description, :inspected_by)";

            $stmt = $this->conn->prepare($query);

            foreach ($records as $record) {
                foreach ($record as $key => $value) {
                    $stmt->bindValue(":" . $key, $value);
                }
                $stmt->execute();
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Bulk insert error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 날짜별 생산 기록 조회
     */
    public function getByDate($date) {
        $query = "SELECT * FROM " . $this->table . " WHERE date = :date ORDER BY id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":date", $date);
        $stmt->execute();
        return $stmt;
    }

    /**
     * 장비별 생산 기록 조회
     */
    public function getByCNC($cnc) {
        $query = "SELECT * FROM " . $this->table . " WHERE cnc = :cnc ORDER BY date DESC LIMIT 50";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":cnc", $cnc);
        $stmt->execute();
        return $stmt;
    }
}
?>
