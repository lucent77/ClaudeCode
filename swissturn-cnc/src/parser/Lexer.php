<?php

namespace SwissTurn\Parser;

/**
 * CNC Code Lexer - Tokenizes raw CNC program lines
 */
class Lexer
{
    private string $input;
    private int $position = 0;
    private int $length = 0;

    /**
     * Token patterns (order matters - more specific first)
     */
    private array $patterns = [
        // Comments
        ['pattern' => '/^\([^)]*\)/', 'type' => TokenType::COMMENT],
        ['pattern' => '/^;.*$/', 'type' => TokenType::COMMENT],

        // Program/Subroutine labels
        ['pattern' => '/^O\d{1,5}/i', 'type' => TokenType::LABEL],

        // Block numbers
        ['pattern' => '/^N\d+/i', 'type' => TokenType::BLOCK_NUMBER],

        // G-codes (G0-G999)
        ['pattern' => '/^G\d{1,3}(\.\d)?/i', 'type' => TokenType::GCODE],

        // M-codes (M0-M999)
        ['pattern' => '/^M\d{1,3}/i', 'type' => TokenType::MCODE],

        // Tool
        ['pattern' => '/^T\d{1,6}/i', 'type' => TokenType::TOOL],

        // Axis words
        ['pattern' => '/^X[+-]?\d*\.?\d+/i', 'type' => TokenType::AXIS_X],
        ['pattern' => '/^Z[+-]?\d*\.?\d+/i', 'type' => TokenType::AXIS_Z],
        ['pattern' => '/^Y[+-]?\d*\.?\d+/i', 'type' => TokenType::AXIS_Y],
        ['pattern' => '/^C[+-]?\d*\.?\d+/i', 'type' => TokenType::AXIS_C],
        ['pattern' => '/^B[+-]?\d*\.?\d+/i', 'type' => TokenType::AXIS_B],
        ['pattern' => '/^W[+-]?\d*\.?\d+/i', 'type' => TokenType::AXIS_W],
        ['pattern' => '/^U[+-]?\d*\.?\d+/i', 'type' => TokenType::AXIS_U],
        ['pattern' => '/^A[+-]?\d*\.?\d+/i', 'type' => TokenType::AXIS_A],

        // Feed rate
        ['pattern' => '/^F\d*\.?\d+/i', 'type' => TokenType::FEED],

        // Spindle speed
        ['pattern' => '/^S\d+/i', 'type' => TokenType::SPINDLE],

        // Arc parameters
        ['pattern' => '/^R[+-]?\d*\.?\d+/i', 'type' => TokenType::RADIUS],
        ['pattern' => '/^I[+-]?\d*\.?\d+/i', 'type' => TokenType::RADIUS_I],
        ['pattern' => '/^J[+-]?\d*\.?\d+/i', 'type' => TokenType::RADIUS_J],
        ['pattern' => '/^K[+-]?\d*\.?\d+/i', 'type' => TokenType::RADIUS_K],

        // Other parameters
        ['pattern' => '/^P\d*\.?\d+/i', 'type' => TokenType::DWELL],
        ['pattern' => '/^Q\d*\.?\d+/i', 'type' => TokenType::PARAMETER_Q],
        ['pattern' => '/^D\d+/i', 'type' => TokenType::PARAMETER_D],
        ['pattern' => '/^H\d+/i', 'type' => TokenType::PARAMETER_H],
        ['pattern' => '/^L\d+/i', 'type' => TokenType::PARAMETER_L],

        // Variables and expressions
        ['pattern' => '/^#\d+/', 'type' => TokenType::VARIABLE],
        ['pattern' => '/^\[[^\]]+\]/', 'type' => TokenType::EXPRESSION],
    ];

    /**
     * Tokenize a single line of CNC code
     *
     * @param string $line Raw CNC code line
     * @return Token[] Array of tokens
     */
    public function tokenize(string $line): array
    {
        $this->input = trim($line);
        $this->position = 0;
        $this->length = strlen($this->input);

        $tokens = [];

        while ($this->position < $this->length) {
            // Skip whitespace
            if ($this->skipWhitespace()) {
                continue;
            }

            $token = $this->matchToken();
            if ($token) {
                $tokens[] = $token;
            } else {
                // Unknown character - skip it
                $this->position++;
            }
        }

        return $tokens;
    }

    /**
     * Skip whitespace characters
     */
    private function skipWhitespace(): bool
    {
        $skipped = false;
        while ($this->position < $this->length && ctype_space($this->input[$this->position])) {
            $this->position++;
            $skipped = true;
        }
        return $skipped;
    }

    /**
     * Try to match a token at current position
     */
    private function matchToken(): ?Token
    {
        $remaining = substr($this->input, $this->position);

        foreach ($this->patterns as $patternDef) {
            if (preg_match($patternDef['pattern'], $remaining, $matches)) {
                $value = $matches[0];
                $startPos = $this->position;
                $endPos = $this->position + strlen($value);
                $this->position = $endPos;

                $numericValue = $this->extractNumericValue($patternDef['type'], $value);

                return new Token(
                    $patternDef['type'],
                    strtoupper($value),
                    $numericValue,
                    $startPos,
                    $endPos
                );
            }
        }

        return null;
    }

    /**
     * Extract numeric value from token
     */
    private function extractNumericValue(string $type, string $value): ?float
    {
        // Types that have numeric values
        $numericTypes = [
            TokenType::BLOCK_NUMBER,
            TokenType::GCODE,
            TokenType::MCODE,
            TokenType::AXIS_X,
            TokenType::AXIS_Z,
            TokenType::AXIS_Y,
            TokenType::AXIS_C,
            TokenType::AXIS_B,
            TokenType::AXIS_W,
            TokenType::AXIS_U,
            TokenType::AXIS_A,
            TokenType::FEED,
            TokenType::SPINDLE,
            TokenType::TOOL,
            TokenType::DWELL,
            TokenType::RADIUS,
            TokenType::RADIUS_I,
            TokenType::RADIUS_J,
            TokenType::RADIUS_K,
            TokenType::PARAMETER_Q,
            TokenType::PARAMETER_D,
            TokenType::PARAMETER_H,
            TokenType::PARAMETER_L,
        ];

        if (!in_array($type, $numericTypes)) {
            return null;
        }

        // Extract number from value (strip letter prefix)
        if (preg_match('/[+-]?\d*\.?\d+/', substr($value, 1), $matches)) {
            return (float)$matches[0];
        }

        return null;
    }

    /**
     * Check if a line is empty or comment-only
     */
    public function isCommentOnly(string $line): bool
    {
        $tokens = $this->tokenize($line);
        if (empty($tokens)) {
            return true;
        }

        foreach ($tokens as $token) {
            if ($token->type !== TokenType::COMMENT) {
                return false;
            }
        }
        return true;
    }

    /**
     * Extract comment text from line
     */
    public function extractComment(string $line): ?string
    {
        $tokens = $this->tokenize($line);
        foreach ($tokens as $token) {
            if ($token->type === TokenType::COMMENT) {
                // Remove parentheses or semicolon
                $text = $token->value;
                if (str_starts_with($text, '(') && str_ends_with($text, ')')) {
                    return trim(substr($text, 1, -1));
                }
                if (str_starts_with($text, ';')) {
                    return trim(substr($text, 1));
                }
                return $text;
            }
        }
        return null;
    }

    /**
     * Normalize a line (consistent spacing, uppercase)
     */
    public function normalize(string $line): string
    {
        $tokens = $this->tokenize($line);
        $parts = [];

        foreach ($tokens as $token) {
            $parts[] = $token->value;
        }

        return implode(' ', $parts);
    }
}
