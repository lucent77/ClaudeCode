<?php

namespace SwissTurn\Parser;

/**
 * Token types for CNC code lexer
 */
class TokenType
{
    public const BLOCK_NUMBER = 'BLOCK_NUMBER';    // N100
    public const GCODE = 'GCODE';                  // G01
    public const MCODE = 'MCODE';                  // M03
    public const AXIS_X = 'AXIS_X';                // X10.0
    public const AXIS_Z = 'AXIS_Z';                // Z-5.0
    public const AXIS_Y = 'AXIS_Y';                // Y0.5
    public const AXIS_C = 'AXIS_C';                // C90.0
    public const AXIS_B = 'AXIS_B';                // B45.0
    public const AXIS_W = 'AXIS_W';                // W-10.0
    public const AXIS_U = 'AXIS_U';                // U1.0
    public const AXIS_A = 'AXIS_A';                // A45.0
    public const FEED = 'FEED';                    // F0.05
    public const SPINDLE = 'SPINDLE';              // S2000
    public const TOOL = 'TOOL';                    // T0101
    public const DWELL = 'DWELL';                  // P500
    public const RADIUS = 'RADIUS';                // R0.5
    public const RADIUS_I = 'RADIUS_I';            // I1.0
    public const RADIUS_J = 'RADIUS_J';            // J1.0
    public const RADIUS_K = 'RADIUS_K';            // K1.0
    public const PARAMETER_Q = 'PARAMETER_Q';      // Q1.0
    public const PARAMETER_D = 'PARAMETER_D';      // D1
    public const PARAMETER_H = 'PARAMETER_H';      // H1
    public const PARAMETER_L = 'PARAMETER_L';      // L1
    public const COMMENT = 'COMMENT';              // (text) or ;text
    public const LABEL = 'LABEL';                  // O0001
    public const VARIABLE = 'VARIABLE';            // #100
    public const EXPRESSION = 'EXPRESSION';        // [#100+1]
    public const EOB = 'EOB';                      // End of block (implicit)
    public const UNKNOWN = 'UNKNOWN';
}

/**
 * Token class representing a single parsed token
 */
class Token
{
    public string $type;
    public string $value;
    public ?float $numericValue;
    public int $startPosition;
    public int $endPosition;

    public function __construct(
        string $type,
        string $value,
        ?float $numericValue = null,
        int $startPosition = 0,
        int $endPosition = 0
    ) {
        $this->type = $type;
        $this->value = $value;
        $this->numericValue = $numericValue;
        $this->startPosition = $startPosition;
        $this->endPosition = $endPosition;
    }

    /**
     * Check if token is an axis word
     */
    public function isAxis(): bool
    {
        return in_array($this->type, [
            TokenType::AXIS_X,
            TokenType::AXIS_Z,
            TokenType::AXIS_Y,
            TokenType::AXIS_C,
            TokenType::AXIS_B,
            TokenType::AXIS_W,
            TokenType::AXIS_U,
            TokenType::AXIS_A,
        ]);
    }

    /**
     * Check if token is a motion code
     */
    public function isMotionCode(): bool
    {
        if ($this->type !== TokenType::GCODE) {
            return false;
        }
        return in_array($this->value, ['G00', 'G0', 'G01', 'G1', 'G02', 'G2', 'G03', 'G3']);
    }

    /**
     * Get axis letter from token type
     */
    public function getAxisLetter(): ?string
    {
        $map = [
            TokenType::AXIS_X => 'X',
            TokenType::AXIS_Z => 'Z',
            TokenType::AXIS_Y => 'Y',
            TokenType::AXIS_C => 'C',
            TokenType::AXIS_B => 'B',
            TokenType::AXIS_W => 'W',
            TokenType::AXIS_U => 'U',
            TokenType::AXIS_A => 'A',
        ];
        return $map[$this->type] ?? null;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'value' => $this->value,
            'numeric_value' => $this->numericValue,
            'position' => [
                'start' => $this->startPosition,
                'end' => $this->endPosition,
            ],
        ];
    }
}
