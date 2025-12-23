<?php

namespace SwissTurn\Models;

use SwissTurn\Core\Database;

/**
 * Machine Model
 */
class Machine
{
    public ?int $id = null;
    public string $name = '';
    public string $manufacturer = '';
    public string $controller_type = '';
    public ?string $description = null;
    public bool $is_active = true;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    /**
     * Find machine by ID
     */
    public static function find(int $id): ?self
    {
        $row = Database::fetch(
            "SELECT * FROM machine WHERE id = ?",
            [$id]
        );

        return $row ? self::fromArray($row) : null;
    }

    /**
     * Get all machines
     */
    public static function all(bool $activeOnly = false): array
    {
        $sql = "SELECT * FROM machine";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY manufacturer, name";

        $rows = Database::fetchAll($sql);
        return array_map([self::class, 'fromArray'], $rows);
    }

    /**
     * Find machine by name
     */
    public static function findByName(string $name): ?self
    {
        $row = Database::fetch(
            "SELECT * FROM machine WHERE name = ?",
            [$name]
        );

        return $row ? self::fromArray($row) : null;
    }

    /**
     * Create machine from array
     */
    public static function fromArray(array $data): self
    {
        $machine = new self();
        $machine->id = $data['id'] ?? null;
        $machine->name = $data['name'] ?? '';
        $machine->manufacturer = $data['manufacturer'] ?? '';
        $machine->controller_type = $data['controller_type'] ?? '';
        $machine->description = $data['description'] ?? null;
        $machine->is_active = (bool)($data['is_active'] ?? true);
        $machine->created_at = $data['created_at'] ?? null;
        $machine->updated_at = $data['updated_at'] ?? null;
        return $machine;
    }

    /**
     * Save machine to database
     */
    public function save(): bool
    {
        if ($this->id) {
            return $this->update();
        }
        return $this->insert();
    }

    /**
     * Insert new machine
     */
    private function insert(): bool
    {
        Database::query(
            "INSERT INTO machine (name, manufacturer, controller_type, description, is_active)
             VALUES (?, ?, ?, ?, ?)",
            [
                $this->name,
                $this->manufacturer,
                $this->controller_type,
                $this->description,
                $this->is_active ? 1 : 0,
            ]
        );

        $this->id = (int)Database::lastInsertId();
        return true;
    }

    /**
     * Update existing machine
     */
    private function update(): bool
    {
        Database::query(
            "UPDATE machine SET name = ?, manufacturer = ?, controller_type = ?,
             description = ?, is_active = ? WHERE id = ?",
            [
                $this->name,
                $this->manufacturer,
                $this->controller_type,
                $this->description,
                $this->is_active ? 1 : 0,
                $this->id,
            ]
        );

        return true;
    }

    /**
     * Delete machine
     */
    public function delete(): bool
    {
        if (!$this->id) {
            return false;
        }

        Database::query("DELETE FROM machine WHERE id = ?", [$this->id]);
        return true;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'manufacturer' => $this->manufacturer,
            'controller_type' => $this->controller_type,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
