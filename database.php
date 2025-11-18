<?php
/**
 * Database Connection and Helper Functions
 */

require_once 'config.php';

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    // Systems CRUD Operations
    public function getAllSystems() {
        $stmt = $this->connection->query("SELECT * FROM systems ORDER BY system_name, size");
        return $stmt->fetchAll();
    }

    public function getSystemById($id) {
        $stmt = $this->connection->prepare("SELECT * FROM systems WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createSystem($system_name, $size, $sku) {
        $stmt = $this->connection->prepare("INSERT INTO systems (system_name, size, sku) VALUES (?, ?, ?)");
        return $stmt->execute([$system_name, $size, $sku]);
    }

    public function updateSystem($id, $system_name, $size, $sku) {
        $stmt = $this->connection->prepare("UPDATE systems SET system_name = ?, size = ?, sku = ? WHERE id = ?");
        return $stmt->execute([$system_name, $size, $sku, $id]);
    }

    public function deleteSystem($id) {
        $stmt = $this->connection->prepare("DELETE FROM systems WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Manufacturers CRUD Operations
    public function getAllManufacturers() {
        $stmt = $this->connection->query("SELECT * FROM manufacturers ORDER BY name, type");
        return $stmt->fetchAll();
    }

    public function getManufacturerById($id) {
        $stmt = $this->connection->prepare("SELECT * FROM manufacturers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createManufacturer($name, $type) {
        $stmt = $this->connection->prepare("INSERT INTO manufacturers (name, type) VALUES (?, ?)");
        return $stmt->execute([$name, $type]);
    }

    public function updateManufacturer($id, $name, $type) {
        $stmt = $this->connection->prepare("UPDATE manufacturers SET name = ?, type = ? WHERE id = ?");
        return $stmt->execute([$name, $type, $id]);
    }

    public function deleteManufacturer($id) {
        $stmt = $this->connection->prepare("DELETE FROM manufacturers WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Scanbodies CRUD Operations
    public function getAllScanbodies() {
        $sql = "SELECT s.*, sys.system_name, sys.size, sys.sku, m.name as manufacturer_name, m.type as manufacturer_type
                FROM scanbodies s
                JOIN systems sys ON s.system_id = sys.id
                JOIN manufacturers m ON s.manufacturer_id = m.id
                ORDER BY sys.system_name, sys.size, m.name, m.type";
        $stmt = $this->connection->query($sql);
        return $stmt->fetchAll();
    }

    public function getScanbodyById($id) {
        $sql = "SELECT s.*, sys.system_name, sys.size, sys.sku, m.name as manufacturer_name, m.type as manufacturer_type
                FROM scanbodies s
                JOIN systems sys ON s.system_id = sys.id
                JOIN manufacturers m ON s.manufacturer_id = m.id
                WHERE s.id = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getScanbodyBySystemAndManufacturer($system_id, $manufacturer_id) {
        $stmt = $this->connection->prepare("SELECT * FROM scanbodies WHERE system_id = ? AND manufacturer_id = ?");
        $stmt->execute([$system_id, $manufacturer_id]);
        return $stmt->fetch();
    }

    public function createScanbody($system_id, $manufacturer_id, $id_code = null, $stl_file_path = null, $original_filename = null, $file_size = null) {
        $stmt = $this->connection->prepare("INSERT INTO scanbodies (system_id, manufacturer_id, id_code, stl_file_path, original_filename, file_size) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$system_id, $manufacturer_id, $id_code, $stl_file_path, $original_filename, $file_size]);
    }

    public function updateScanbody($id, $id_code = null, $stl_file_path = null, $original_filename = null, $file_size = null) {
        if ($stl_file_path !== null) {
            $stmt = $this->connection->prepare("UPDATE scanbodies SET id_code = ?, stl_file_path = ?, original_filename = ?, file_size = ? WHERE id = ?");
            return $stmt->execute([$id_code, $stl_file_path, $original_filename, $file_size, $id]);
        } else {
            $stmt = $this->connection->prepare("UPDATE scanbodies SET id_code = ? WHERE id = ?");
            return $stmt->execute([$id_code, $id]);
        }
    }

    public function deleteScanbody($id) {
        // Get file path before deleting
        $scanbody = $this->getScanbodyById($id);
        if ($scanbody && $scanbody['stl_file_path']) {
            $file_path = UPLOAD_DIR . $scanbody['stl_file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        $stmt = $this->connection->prepare("DELETE FROM scanbodies WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Search functionality
    public function searchScanbodies($search_term) {
        $search = '%' . $search_term . '%';
        $sql = "SELECT s.*, sys.system_name, sys.size, sys.sku, m.name as manufacturer_name, m.type as manufacturer_type
                FROM scanbodies s
                JOIN systems sys ON s.system_id = sys.id
                JOIN manufacturers m ON s.manufacturer_id = m.id
                WHERE sys.system_name LIKE ? OR sys.sku LIKE ? OR s.id_code LIKE ? OR m.name LIKE ?
                ORDER BY sys.system_name, sys.size, m.name, m.type";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$search, $search, $search, $search]);
        return $stmt->fetchAll();
    }

    // Get complete data matrix (for display)
    public function getDataMatrix() {
        $systems = $this->getAllSystems();
        $manufacturers = $this->getAllManufacturers();
        $scanbodies = $this->getAllScanbodies();

        // Build matrix
        $matrix = [];
        foreach ($systems as $system) {
            $row = [
                'system_id' => $system['id'],
                'system_name' => $system['system_name'],
                'size' => $system['size'],
                'sku' => $system['sku'],
                'scanbodies' => []
            ];

            foreach ($manufacturers as $manufacturer) {
                $scanbody = null;
                foreach ($scanbodies as $sb) {
                    if ($sb['system_id'] == $system['id'] && $sb['manufacturer_id'] == $manufacturer['id']) {
                        $scanbody = $sb;
                        break;
                    }
                }

                $row['scanbodies'][] = [
                    'manufacturer_id' => $manufacturer['id'],
                    'manufacturer_name' => $manufacturer['name'],
                    'manufacturer_type' => $manufacturer['type'],
                    'scanbody_id' => $scanbody ? $scanbody['id'] : null,
                    'id_code' => $scanbody ? $scanbody['id_code'] : null,
                    'stl_file_path' => $scanbody ? $scanbody['stl_file_path'] : null,
                    'original_filename' => $scanbody ? $scanbody['original_filename'] : null,
                ];
            }

            $matrix[] = $row;
        }

        return [
            'manufacturers' => $manufacturers,
            'matrix' => $matrix
        ];
    }
}
