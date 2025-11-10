<?php
/**
 * Tool 모델
 */
class Tool {
    private $conn;
    private $table = "tools";

    public $id;
    public $code;
    public $name;
    public $category;
    public $size;
    public $supplier;
    public $supplier_model;
    public $current_stock;
    public $min_stock;
    public $lifespan_type;
    public $lifespan_limit;
    public $current_usage;
    public $status;
    public $machine_id;
    public $description;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * 모든 공구 조회
     */
    public function readAll() {
        $query = "SELECT * FROM " . $this->table . " WHERE status != 'used' ORDER BY code";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * 특정 공구 조회
     */
    public function readOne() {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->code = $row['code'];
            $this->name = $row['name'];
            $this->category = $row['category'];
            $this->size = $row['size'];
            $this->supplier = $row['supplier'];
            $this->supplier_model = $row['supplier_model'];
            $this->current_stock = $row['current_stock'];
            $this->min_stock = $row['min_stock'];
            $this->lifespan_type = $row['lifespan_type'];
            $this->lifespan_limit = $row['lifespan_limit'];
            $this->current_usage = $row['current_usage'];
            $this->status = $row['status'];
            $this->machine_id = $row['machine_id'];
            $this->description = $row['description'];
            return true;
        }

        return false;
    }

    /**
     * 공구 생성
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                  (code, name, category, size, supplier, supplier_model, current_stock,
                   min_stock, lifespan_type, lifespan_limit, current_usage, status,
                   machine_id, description)
                  VALUES
                  (:code, :name, :category, :size, :supplier, :supplier_model, :current_stock,
                   :min_stock, :lifespan_type, :lifespan_limit, :current_usage, :status,
                   :machine_id, :description)";

        $stmt = $this->conn->prepare($query);

        // 바인딩
        $stmt->bindParam(":code", $this->code);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":size", $this->size);
        $stmt->bindParam(":supplier", $this->supplier);
        $stmt->bindParam(":supplier_model", $this->supplier_model);
        $stmt->bindParam(":current_stock", $this->current_stock);
        $stmt->bindParam(":min_stock", $this->min_stock);
        $stmt->bindParam(":lifespan_type", $this->lifespan_type);
        $stmt->bindParam(":lifespan_limit", $this->lifespan_limit);
        $stmt->bindParam(":current_usage", $this->current_usage);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":machine_id", $this->machine_id);
        $stmt->bindParam(":description", $this->description);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    /**
     * 공구 업데이트
     */
    public function update() {
        $query = "UPDATE " . $this->table . "
                  SET code = :code,
                      name = :name,
                      category = :category,
                      size = :size,
                      supplier = :supplier,
                      supplier_model = :supplier_model,
                      current_stock = :current_stock,
                      min_stock = :min_stock,
                      lifespan_type = :lifespan_type,
                      lifespan_limit = :lifespan_limit,
                      current_usage = :current_usage,
                      status = :status,
                      machine_id = :machine_id,
                      description = :description
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // 바인딩
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":code", $this->code);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":size", $this->size);
        $stmt->bindParam(":supplier", $this->supplier);
        $stmt->bindParam(":supplier_model", $this->supplier_model);
        $stmt->bindParam(":current_stock", $this->current_stock);
        $stmt->bindParam(":min_stock", $this->min_stock);
        $stmt->bindParam(":lifespan_type", $this->lifespan_type);
        $stmt->bindParam(":lifespan_limit", $this->lifespan_limit);
        $stmt->bindParam(":current_usage", $this->current_usage);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":machine_id", $this->machine_id);
        $stmt->bindParam(":description", $this->description);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    /**
     * 공구 삭제
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    /**
     * 공구 교체
     */
    public function replace($new_tool_data) {
        try {
            $this->conn->beginTransaction();

            // 기존 공구를 used_tools에 저장
            $query = "INSERT INTO used_tools (tool_id, code, name, category, final_usage, lifespan_limit, machine_id)
                      VALUES (:tool_id, :code, :name, :category, :final_usage, :lifespan_limit, :machine_id)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":tool_id", $this->id);
            $stmt->bindParam(":code", $this->code);
            $stmt->bindParam(":name", $this->name);
            $stmt->bindParam(":category", $this->category);
            $stmt->bindParam(":final_usage", $this->current_usage);
            $stmt->bindParam(":lifespan_limit", $this->lifespan_limit);
            $stmt->bindParam(":machine_id", $this->machine_id);
            $stmt->execute();

            // 기존 공구 상태를 'used'로 변경
            $query = "UPDATE " . $this->table . " SET status = 'used', machine_id = NULL WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);
            $stmt->execute();

            // 새 공구 생성
            $newTool = new Tool($this->conn);
            foreach ($new_tool_data as $key => $value) {
                $newTool->$key = $value;
            }
            $newTool->current_usage = 0;
            $newTool->status = 'available';
            $newTool->create();

            $this->conn->commit();
            return $newTool->id;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    /**
     * 사용 완료 공구 목록 조회
     */
    public function getUsedTools() {
        $query = "SELECT * FROM used_tools ORDER BY replaced_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * 공구 수명 잔여율 계산
     */
    public function getLifespanRemaining() {
        if ($this->lifespan_limit == 0) {
            return 100;
        }

        $remaining = (($this->lifespan_limit - $this->current_usage) / $this->lifespan_limit) * 100;
        return max(0, min(100, $remaining));
    }

    /**
     * 공구 상태 판단 (good/warning/critical)
     */
    public function getToolStatus() {
        $remaining = $this->getLifespanRemaining();

        if ($remaining > 50) return 'good';
        if ($remaining > 20) return 'warning';
        return 'critical';
    }
}
?>
