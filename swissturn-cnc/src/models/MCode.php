<?php

namespace SwissTurn\Models;

use SwissTurn\Core\Database;

/**
 * M-Code Model
 */
class MCode
{
    public ?int $id = null;
    public ?int $machine_id = null;
    public string $code = '';
    public string $name = '';
    public ?string $description = null;
    public ?array $parameters = null;
    public ?string $semantic_type = null;
    public ?string $explanation_template = null;
    public bool $is_active = true;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    /**
     * Find M-code by ID
     */
    public static function find(int $id): ?self
    {
        $row = Database::fetch("SELECT * FROM m_code WHERE id = ?", [$id]);
        return $row ? self::fromArray($row) : null;
    }

    /**
     * Get all M-codes, optionally filtered by machine
     */
    public static function all(?int $machineId = null): array
    {
        if ($machineId !== null) {
            $rows = Database::fetchAll(
                "SELECT * FROM m_code WHERE (machine_id = ? OR machine_id IS NULL) AND is_active = 1 ORDER BY code",
                [$machineId]
            );
        } else {
            $rows = Database::fetchAll("SELECT * FROM m_code WHERE is_active = 1 ORDER BY code");
        }
        return array_map([self::class, 'fromArray'], $rows);
    }

    /**
     * Find M-code by code string for specific machine
     */
    public static function findByCode(string $code, ?int $machineId = null): ?self
    {
        // First try machine-specific
        if ($machineId !== null) {
            $row = Database::fetch(
                "SELECT * FROM m_code WHERE code = ? AND machine_id = ? AND is_active = 1",
                [$code, $machineId]
            );
            if ($row) {
                return self::fromArray($row);
            }
        }

        // Fall back to universal (machine_id IS NULL)
        $row = Database::fetch(
            "SELECT * FROM m_code WHERE code = ? AND machine_id IS NULL AND is_active = 1",
            [$code]
        );

        return $row ? self::fromArray($row) : null;
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $mcode = new self();
        $mcode->id = $data['id'] ?? null;
        $mcode->machine_id = $data['machine_id'] ?? null;
        $mcode->code = $data['code'] ?? '';
        $mcode->name = $data['name'] ?? '';
        $mcode->description = $data['description'] ?? null;
        $mcode->parameters = isset($data['parameters']) && is_string($data['parameters'])
            ? json_decode($data['parameters'], true)
            : ($data['parameters'] ?? null);
        $mcode->semantic_type = $data['semantic_type'] ?? null;
        $mcode->explanation_template = $data['explanation_template'] ?? null;
        $mcode->is_active = (bool)($data['is_active'] ?? true);
        $mcode->created_at = $data['created_at'] ?? null;
        $mcode->updated_at = $data['updated_at'] ?? null;
        return $mcode;
    }

    /**
     * Save to database
     */
    public function save(): bool
    {
        $params = $this->parameters !== null ? json_encode($this->parameters) : null;

        if ($this->id) {
            Database::query(
                "UPDATE m_code SET machine_id = ?, code = ?, name = ?, description = ?,
                 parameters = ?, semantic_type = ?, explanation_template = ?, is_active = ? WHERE id = ?",
                [
                    $this->machine_id,
                    $this->code,
                    $this->name,
                    $this->description,
                    $params,
                    $this->semantic_type,
                    $this->explanation_template,
                    $this->is_active ? 1 : 0,
                    $this->id,
                ]
            );
        } else {
            Database::query(
                "INSERT INTO m_code (machine_id, code, name, description, parameters,
                 semantic_type, explanation_template, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $this->machine_id,
                    $this->code,
                    $this->name,
                    $this->description,
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
     * Delete M-code
     */
    public function delete(): bool
    {
        if (!$this->id) {
            return false;
        }
        Database::query("DELETE FROM m_code WHERE id = ?", [$this->id]);
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
