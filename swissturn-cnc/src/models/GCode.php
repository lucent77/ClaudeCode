<?php

namespace SwissTurn\Models;

use SwissTurn\Core\Database;

/**
 * G-Code Model
 */
class GCode
{
    public ?int $id = null;
    public ?int $machine_id = null;
    public string $code = '';
    public string $name = '';
    public ?string $description = null;
    public ?int $modal_group_id = null;
    public bool $is_modal = true;
    public ?array $parameters = null;
    public ?string $semantic_type = null;
    public ?string $explanation_template = null;
    public bool $is_active = true;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    /**
     * Find G-code by ID
     */
    public static function find(int $id): ?self
    {
        $row = Database::fetch("SELECT * FROM g_code WHERE id = ?", [$id]);
        return $row ? self::fromArray($row) : null;
    }

    /**
     * Get all G-codes, optionally filtered by machine
     */
    public static function all(?int $machineId = null): array
    {
        if ($machineId !== null) {
            $rows = Database::fetchAll(
                "SELECT * FROM g_code WHERE (machine_id = ? OR machine_id IS NULL) AND is_active = 1 ORDER BY code",
                [$machineId]
            );
        } else {
            $rows = Database::fetchAll("SELECT * FROM g_code WHERE is_active = 1 ORDER BY code");
        }
        return array_map([self::class, 'fromArray'], $rows);
    }

    /**
     * Find G-code by code string for specific machine
     */
    public static function findByCode(string $code, ?int $machineId = null): ?self
    {
        // First try machine-specific
        if ($machineId !== null) {
            $row = Database::fetch(
                "SELECT * FROM g_code WHERE code = ? AND machine_id = ? AND is_active = 1",
                [$code, $machineId]
            );
            if ($row) {
                return self::fromArray($row);
            }
        }

        // Fall back to universal (machine_id IS NULL)
        $row = Database::fetch(
            "SELECT * FROM g_code WHERE code = ? AND machine_id IS NULL AND is_active = 1",
            [$code]
        );

        return $row ? self::fromArray($row) : null;
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $gcode = new self();
        $gcode->id = $data['id'] ?? null;
        $gcode->machine_id = $data['machine_id'] ?? null;
        $gcode->code = $data['code'] ?? '';
        $gcode->name = $data['name'] ?? '';
        $gcode->description = $data['description'] ?? null;
        $gcode->modal_group_id = $data['modal_group_id'] ?? null;
        $gcode->is_modal = (bool)($data['is_modal'] ?? true);
        $gcode->parameters = isset($data['parameters']) && is_string($data['parameters'])
            ? json_decode($data['parameters'], true)
            : ($data['parameters'] ?? null);
        $gcode->semantic_type = $data['semantic_type'] ?? null;
        $gcode->explanation_template = $data['explanation_template'] ?? null;
        $gcode->is_active = (bool)($data['is_active'] ?? true);
        $gcode->created_at = $data['created_at'] ?? null;
        $gcode->updated_at = $data['updated_at'] ?? null;
        return $gcode;
    }

    /**
     * Save to database
     */
    public function save(): bool
    {
        $params = $this->parameters !== null ? json_encode($this->parameters) : null;

        if ($this->id) {
            Database::query(
                "UPDATE g_code SET machine_id = ?, code = ?, name = ?, description = ?,
                 modal_group_id = ?, is_modal = ?, parameters = ?, semantic_type = ?,
                 explanation_template = ?, is_active = ? WHERE id = ?",
                [
                    $this->machine_id,
                    $this->code,
                    $this->name,
                    $this->description,
                    $this->modal_group_id,
                    $this->is_modal ? 1 : 0,
                    $params,
                    $this->semantic_type,
                    $this->explanation_template,
                    $this->is_active ? 1 : 0,
                    $this->id,
                ]
            );
        } else {
            Database::query(
                "INSERT INTO g_code (machine_id, code, name, description, modal_group_id,
                 is_modal, parameters, semantic_type, explanation_template, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $this->machine_id,
                    $this->code,
                    $this->name,
                    $this->description,
                    $this->modal_group_id,
                    $this->is_modal ? 1 : 0,
                    $params,
                    $this->semantic_type,
                    $this->explanation_template,
                    $this->is_active ? 1 : 0,
                ]
            );
            $this->id = (int)Database::lastInsertId();
        }
        return true;
    }

    /**
     * Delete G-code
     */
    public function delete(): bool
    {
        if (!$this->id) {
            return false;
        }
        Database::query("DELETE FROM g_code WHERE id = ?", [$this->id]);
        return true;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'machine_id' => $this->machine_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'modal_group_id' => $this->modal_group_id,
            'is_modal' => $this->is_modal,
            'parameters' => $this->parameters,
            'semantic_type' => $this->semantic_type,
            'explanation_template' => $this->explanation_template,
            'is_active' => $this->is_active,
        ];
    }

    /**
     * Generate explanation from template
     */
    public function generateExplanation(array $values = []): string
    {
        if (!$this->explanation_template) {
            return $this->name;
        }

        $explanation = $this->explanation_template;
        foreach ($values as $key => $value) {
            $explanation = str_replace('{' . $key . '}', (string)$value, $explanation);
        }
        return $explanation;
    }
}
