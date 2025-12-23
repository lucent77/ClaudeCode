<?php

namespace SwissTurn\Models;

use SwissTurn\Core\Database;

/**
 * CNC Program Model
 */
class Program
{
    public ?int $id = null;
    public int $machine_id;
    public string $name = '';
    public string $source_code = '';
    public ?string $source_filename = null;
    public string $status = 'pending';
    public ?string $error_message = null;
    public int $line_count = 0;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    /**
     * Find program by ID
     */
    public static function find(int $id): ?self
    {
        $row = Database::fetch("SELECT * FROM program WHERE id = ?", [$id]);
        return $row ? self::fromArray($row) : null;
    }

    /**
     * Get all programs
     */
    public static function all(?int $machineId = null): array
    {
        if ($machineId !== null) {
            $rows = Database::fetchAll(
                "SELECT * FROM program WHERE machine_id = ? ORDER BY created_at DESC",
                [$machineId]
            );
        } else {
            $rows = Database::fetchAll("SELECT * FROM program ORDER BY created_at DESC");
        }
        return array_map([self::class, 'fromArray'], $rows);
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $program = new self();
        $program->id = $data['id'] ?? null;
        $program->machine_id = (int)($data['machine_id'] ?? 0);
        $program->name = $data['name'] ?? '';
        $program->source_code = $data['source_code'] ?? '';
        $program->source_filename = $data['source_filename'] ?? null;
        $program->status = $data['status'] ?? 'pending';
        $program->error_message = $data['error_message'] ?? null;
        $program->line_count = (int)($data['line_count'] ?? 0);
        $program->created_at = $data['created_at'] ?? null;
        $program->updated_at = $data['updated_at'] ?? null;
        return $program;
    }

    /**
     * Save to database
     */
    public function save(): bool
    {
        if ($this->id) {
            Database::query(
                "UPDATE program SET machine_id = ?, name = ?, source_code = ?,
                 source_filename = ?, status = ?, error_message = ?, line_count = ? WHERE id = ?",
                [
                    $this->machine_id,
                    $this->name,
                    $this->source_code,
                    $this->source_filename,
                    $this->status,
                    $this->error_message,
                    $this->line_count,
                    $this->id,
                ]
            );
        } else {
            Database::query(
                "INSERT INTO program (machine_id, name, source_code, source_filename, status, line_count)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $this->machine_id,
                    $this->name,
                    $this->source_code,
                    $this->source_filename,
                    $this->status,
                    $this->line_count,
                ]
            );
            $this->id = (int)Database::lastInsertId();
        }
        return true;
    }

    /**
     * Delete program and its parsed lines
     */
    public function delete(): bool
    {
        if (!$this->id) {
            return false;
        }
        // Parsed lines will be deleted via CASCADE
        Database::query("DELETE FROM program WHERE id = ?", [$this->id]);
        return true;
    }

    /**
     * Get parsed lines for this program
     */
    public function getParsedLines(): array
    {
        if (!$this->id) {
            return [];
        }
        return ParsedLine::findByProgram($this->id);
    }

    /**
     * Get the machine for this program
     */
    public function getMachine(): ?Machine
    {
        return Machine::find($this->machine_id);
    }

    /**
     * Update status
     */
    public function updateStatus(string $status, ?string $errorMessage = null): void
    {
        $this->status = $status;
        $this->error_message = $errorMessage;
        Database::query(
            "UPDATE program SET status = ?, error_message = ? WHERE id = ?",
            [$status, $errorMessage, $this->id]
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'machine_id' => $this->machine_id,
            'name' => $this->name,
            'source_code' => $this->source_code,
            'source_filename' => $this->source_filename,
            'status' => $this->status,
            'error_message' => $this->error_message,
            'line_count' => $this->line_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
