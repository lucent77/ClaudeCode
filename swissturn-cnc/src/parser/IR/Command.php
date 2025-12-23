<?php

namespace SwissTurn\Parser\IR;

/**
 * Semantic command types
 */
class CommandType
{
    public const MOTION = 'MOTION';
    public const SPINDLE_CONTROL = 'SPINDLE_CONTROL';
    public const COOLANT_CONTROL = 'COOLANT_CONTROL';
    public const TOOL_CHANGE = 'TOOL_CHANGE';
    public const PROGRAM_CONTROL = 'PROGRAM_CONTROL';
    public const CANNED_CYCLE = 'CANNED_CYCLE';
    public const COMPENSATION = 'COMPENSATION';
    public const COORDINATE_SYSTEM = 'COORDINATE_SYSTEM';
    public const FEED_SPEED = 'FEED_SPEED';
    public const MACRO = 'MACRO';
    public const UNKNOWN = 'UNKNOWN';
}

/**
 * Represents a semantic command extracted from tokens
 */
class Command
{
    public string $type;
    public string $code;
    public array $parameters;
    public ?string $semanticMeaning = null;

    public function __construct(
        string $type,
        string $code,
        array $parameters = [],
        ?string $semanticMeaning = null
    ) {
        $this->type = $type;
        $this->code = $code;
        $this->parameters = $parameters;
        $this->semanticMeaning = $semanticMeaning;
    }

    /**
     * Check if command is a motion command
     */
    public function isMotion(): bool
    {
        return $this->type === CommandType::MOTION;
    }

    /**
     * Get parameter value by key
     */
    public function getParameter(string $key): mixed
    {
        return $this->parameters[$key] ?? null;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'code' => $this->code,
            'parameters' => $this->parameters,
            'semantic_meaning' => $this->semanticMeaning,
        ];
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['type'] ?? CommandType::UNKNOWN,
            $data['code'] ?? '',
            $data['parameters'] ?? [],
            $data['semantic_meaning'] ?? null
        );
    }
}
