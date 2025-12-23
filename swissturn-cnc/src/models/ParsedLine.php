<?php

namespace SwissTurn\Models;

use SwissTurn\Core\Database;

/**
 * Parsed Line Model - Stores IR for each program line
 */
class ParsedLine
{
    public ?int $id = null;
    public int $program_id;
    public int $line_number;
    public ?int $block_number = null;
    public string $raw_text = '';
    public ?string $normalized_text = null;
    public ?array $tokens = null;
    public ?array $commands = null;
    public ?array $modal_state_before = null;
    public ?array $modal_state_after = null;
    public ?string $explanation = null;
    public bool $is_comment_only = false;
    public bool $has_errors = false;
    public ?array $errors = null;
    public ?string $created_at = null;

    /**
     * Find by ID
     */
    public static function find(int $id): ?self
    {
        $row = Database::fetch("SELECT * FROM parsed_line WHERE id = ?", [$id]);
        return $row ? self::fromArray($row) : null;
    }

    /**
     * Find all lines for a program
     */
    public static function findByProgram(int $programId): array
    {
        $rows = Database::fetchAll(
            "SELECT * FROM parsed_line WHERE program_id = ? ORDER BY line_number",
            [$programId]
        );
        return array_map([self::class, 'fromArray'], $rows);
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $line = new self();
        $line->id = $data['id'] ?? null;
        $line->program_id = (int)($data['program_id'] ?? 0);
        $line->line_number = (int)($data['line_number'] ?? 0);
        $line->block_number = $data['block_number'] ?? null;
        $line->raw_text = $data['raw_text'] ?? '';
        $line->normalized_text = $data['normalized_text'] ?? null;

        // Parse JSON fields
        $line->tokens = self::parseJson($data['tokens_json'] ?? null);
        $line->commands = self::parseJson($data['commands_json'] ?? null);
        $line->modal_state_before = self::parseJson($data['modal_state_before'] ?? null);
        $line->modal_state_after = self::parseJson($data['modal_state_after'] ?? null);
        $line->errors = self::parseJson($data['errors_json'] ?? null);

        $line->explanation = $data['explanation'] ?? null;
        $line->is_comment_only = (bool)($data['is_comment_only'] ?? false);
        $line->has_errors = (bool)($data['has_errors'] ?? false);
        $line->created_at = $data['created_at'] ?? null;

        return $line;
    }

    /**
     * Parse JSON string to array
     */
    private static function parseJson($value): ?array
    {
        if ($value === null) {
            return null;
        }
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return null;
    }

    /**
     * Save to database
     */
    public function save(): bool
    {
        $tokensJson = $this->tokens !== null ? json_encode($this->tokens) : null;
        $commandsJson = $this->commands !== null ? json_encode($this->commands) : null;
        $modalBefore = $this->modal_state_before !== null ? json_encode($this->modal_state_before) : null;
        $modalAfter = $this->modal_state_after !== null ? json_encode($this->modal_state_after) : null;
        $errorsJson = $this->errors !== null ? json_encode($this->errors) : null;

        if ($this->id) {
            Database::query(
                "UPDATE parsed_line SET program_id = ?, line_number = ?, block_number = ?,
                 raw_text = ?, normalized_text = ?, tokens_json = ?, commands_json = ?,
                 modal_state_before = ?, modal_state_after = ?, explanation = ?,
                 is_comment_only = ?, has_errors = ?, errors_json = ? WHERE id = ?",
                [
                    $this->program_id,
                    $this->line_number,
                    $this->block_number,
                    $this->raw_text,
                    $this->normalized_text,
                    $tokensJson,
                    $commandsJson,
                    $modalBefore,
                    $modalAfter,
                    $this->explanation,
                    $this->is_comment_only ? 1 : 0,
                    $this->has_errors ? 1 : 0,
                    $errorsJson,
                    $this->id,
                ]
            );
        } else {
            Database::query(
                "INSERT INTO parsed_line (program_id, line_number, block_number, raw_text,
                 normalized_text, tokens_json, commands_json, modal_state_before,
                 modal_state_after, explanation, is_comment_only, has_errors, errors_json)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $this->program_id,
                    $this->line_number,
                    $this->block_number,
                    $this->raw_text,
                    $this->normalized_text,
                    $tokensJson,
                    $commandsJson,
                    $modalBefore,
                    $modalAfter,
                    $this->explanation,
                    $this->is_comment_only ? 1 : 0,
                    $this->has_errors ? 1 : 0,
                    $errorsJson,
                ]
            );
            $this->id = (int)Database::lastInsertId();
        }
        return true;
    }

    /**
     * Bulk insert parsed lines
     */
    public static function bulkInsert(array $lines): bool
    {
        if (empty($lines)) {
            return true;
        }

        Database::beginTransaction();
        try {
            foreach ($lines as $line) {
                $line->save();
            }
            Database::commit();
            return true;
        } catch (\Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Delete all lines for a program
     */
    public static function deleteByProgram(int $programId): bool
    {
        Database::query("DELETE FROM parsed_line WHERE program_id = ?", [$programId]);
        return true;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'program_id' => $this->program_id,
            'line_number' => $this->line_number,
            'block_number' => $this->block_number,
            'raw_text' => $this->raw_text,
            'normalized_text' => $this->normalized_text,
            'tokens' => $this->tokens,
            'commands' => $this->commands,
            'modal_state_before' => $this->modal_state_before,
            'modal_state_after' => $this->modal_state_after,
            'explanation' => $this->explanation,
            'is_comment_only' => $this->is_comment_only,
            'has_errors' => $this->has_errors,
            'errors' => $this->errors,
        ];
    }
}
