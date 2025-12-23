<?php

namespace SwissTurn\Parser\IR;

use SwissTurn\Parser\Token;
use SwissTurn\Parser\ModalState;

/**
 * Intermediate Representation for a single CNC program line
 */
class Line
{
    public int $lineNumber;
    public ?int $blockNumber = null;
    public string $rawText;
    public string $normalizedText;
    public array $tokens = [];
    public array $commands = [];
    public ?ModalState $modalStateBefore = null;
    public ?ModalState $modalStateAfter = null;
    public string $explanation = '';
    public bool $isCommentOnly = false;
    public bool $hasErrors = false;
    public array $errors = [];

    public function __construct(int $lineNumber, string $rawText)
    {
        $this->lineNumber = $lineNumber;
        $this->rawText = $rawText;
        $this->normalizedText = '';
    }

    /**
     * Add a token to this line
     */
    public function addToken(Token $token): void
    {
        $this->tokens[] = $token;
    }

    /**
     * Add a command to this line
     */
    public function addCommand(Command $command): void
    {
        $this->commands[] = $command;
    }

    /**
     * Add an error
     */
    public function addError(string $error): void
    {
        $this->errors[] = $error;
        $this->hasErrors = true;
    }

    /**
     * Get token by type
     */
    public function getTokenByType(string $type): ?Token
    {
        foreach ($this->tokens as $token) {
            if ($token->type === $type) {
                return $token;
            }
        }
        return null;
    }

    /**
     * Get all tokens of a specific type
     */
    public function getTokensByType(string $type): array
    {
        return array_filter($this->tokens, fn($t) => $t->type === $type);
    }

    /**
     * Check if line contains specific token type
     */
    public function hasTokenType(string $type): bool
    {
        return $this->getTokenByType($type) !== null;
    }

    /**
     * Check if this line has motion commands
     */
    public function hasMotion(): bool
    {
        foreach ($this->commands as $command) {
            if ($command->isMotion()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all axis values from tokens
     */
    public function getAxisValues(): array
    {
        $axes = [];
        foreach ($this->tokens as $token) {
            if ($token->isAxis()) {
                $letter = $token->getAxisLetter();
                if ($letter) {
                    $axes[$letter] = $token->numericValue;
                }
            }
        }
        return $axes;
    }

    /**
     * Convert to array for storage
     */
    public function toArray(): array
    {
        return [
            'line_number' => $this->lineNumber,
            'block_number' => $this->blockNumber,
            'raw_text' => $this->rawText,
            'normalized_text' => $this->normalizedText,
            'tokens' => array_map(fn($t) => $t->toArray(), $this->tokens),
            'commands' => array_map(fn($c) => $c->toArray(), $this->commands),
            'modal_state_before' => $this->modalStateBefore?->toArray(),
            'modal_state_after' => $this->modalStateAfter?->toArray(),
            'explanation' => $this->explanation,
            'is_comment_only' => $this->isCommentOnly,
            'has_errors' => $this->hasErrors,
            'errors' => $this->errors,
        ];
    }
}
