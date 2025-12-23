<?php

namespace SwissTurn\Parser\IR;

use SwissTurn\Parser\ModalState;

/**
 * Intermediate Representation for a complete CNC program
 */
class Program
{
    public ?int $id = null;
    public int $machineId;
    public string $machineName = '';
    public ?string $sourceFilename = null;
    public string $createdAt;

    /** @var Line[] */
    public array $lines = [];

    public ?ModalState $initialModalState = null;
    public array $metadata = [];

    // Statistics
    public int $totalLines = 0;
    public int $codeLines = 0;
    public int $commentLines = 0;
    public int $errorLines = 0;

    public function __construct(int $machineId)
    {
        $this->machineId = $machineId;
        $this->createdAt = date('Y-m-d H:i:s');
        $this->initialModalState = new ModalState();
    }

    /**
     * Add a parsed line
     */
    public function addLine(Line $line): void
    {
        $this->lines[] = $line;
        $this->totalLines++;

        if ($line->isCommentOnly) {
            $this->commentLines++;
        } else {
            $this->codeLines++;
        }

        if ($line->hasErrors) {
            $this->errorLines++;
        }
    }

    /**
     * Get line by line number
     */
    public function getLine(int $lineNumber): ?Line
    {
        foreach ($this->lines as $line) {
            if ($line->lineNumber === $lineNumber) {
                return $line;
            }
        }
        return null;
    }

    /**
     * Get all lines with errors
     */
    public function getErrorLines(): array
    {
        return array_filter($this->lines, fn($l) => $l->hasErrors);
    }

    /**
     * Check if program has any errors
     */
    public function hasErrors(): bool
    {
        return $this->errorLines > 0;
    }

    /**
     * Get all unique G-codes used
     */
    public function getUsedGCodes(): array
    {
        $codes = [];
        foreach ($this->lines as $line) {
            foreach ($line->commands as $command) {
                if (str_starts_with($command->code, 'G')) {
                    $codes[$command->code] = true;
                }
            }
        }
        return array_keys($codes);
    }

    /**
     * Get all unique M-codes used
     */
    public function getUsedMCodes(): array
    {
        $codes = [];
        foreach ($this->lines as $line) {
            foreach ($line->commands as $command) {
                if (str_starts_with($command->code, 'M')) {
                    $codes[$command->code] = true;
                }
            }
        }
        return array_keys($codes);
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_lines' => $this->totalLines,
            'code_lines' => $this->codeLines,
            'comment_lines' => $this->commentLines,
            'error_lines' => $this->errorLines,
            'g_codes_used' => $this->getUsedGCodes(),
            'm_codes_used' => $this->getUsedMCodes(),
        ];
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'machine_id' => $this->machineId,
            'machine_name' => $this->machineName,
            'source_filename' => $this->sourceFilename,
            'created_at' => $this->createdAt,
            'lines' => array_map(fn($l) => $l->toArray(), $this->lines),
            'initial_modal_state' => $this->initialModalState?->toArray(),
            'metadata' => $this->metadata,
            'statistics' => $this->getStatistics(),
        ];
    }
}
