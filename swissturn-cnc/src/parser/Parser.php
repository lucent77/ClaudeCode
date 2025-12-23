<?php

namespace SwissTurn\Parser;

use SwissTurn\Parser\IR\Program;
use SwissTurn\Parser\IR\Line;
use SwissTurn\Parser\IR\Command;
use SwissTurn\Parser\IR\CommandType;
use SwissTurn\Models\GCode;
use SwissTurn\Models\MCode;

/**
 * CNC Program Parser
 *
 * Parses raw CNC code into a structured Intermediate Representation (IR)
 */
class Parser
{
    private Lexer $lexer;
    private int $machineId;
    private ModalState $currentState;
    private ExplanationGenerator $explanationGenerator;

    public function __construct(int $machineId)
    {
        $this->machineId = $machineId;
        $this->lexer = new Lexer();
        $this->currentState = new ModalState();
        $this->explanationGenerator = new ExplanationGenerator($machineId);
    }

    /**
     * Parse a complete CNC program
     *
     * @param string $sourceCode Raw CNC program text
     * @param string|null $filename Optional source filename
     * @return Program Parsed program IR
     */
    public function parse(string $sourceCode, ?string $filename = null): Program
    {
        $program = new Program($this->machineId);
        $program->sourceFilename = $filename;

        // Split into lines
        $lines = explode("\n", str_replace("\r\n", "\n", $sourceCode));

        // Reset modal state
        $this->currentState = new ModalState();

        // Parse each line
        foreach ($lines as $lineNumber => $lineText) {
            $irLine = $this->parseLine($lineNumber + 1, $lineText);
            $program->addLine($irLine);
        }

        return $program;
    }

    /**
     * Parse a single line of CNC code
     */
    public function parseLine(int $lineNumber, string $rawText): Line
    {
        $line = new Line($lineNumber, $rawText);

        // Skip empty lines
        $trimmed = trim($rawText);
        if ($trimmed === '') {
            $line->isCommentOnly = true;
            $line->explanation = 'Empty line';
            return $line;
        }

        // Store modal state before processing
        $line->modalStateBefore = $this->currentState->copy();

        // Tokenize
        $tokens = $this->lexer->tokenize($trimmed);
        foreach ($tokens as $token) {
            $line->addToken($token);
        }

        // Check if comment only
        if ($this->lexer->isCommentOnly($trimmed)) {
            $line->isCommentOnly = true;
            $comment = $this->lexer->extractComment($trimmed);
            $line->explanation = $comment ? "Comment: $comment" : 'Comment';
            $line->modalStateAfter = $this->currentState->copy();
            return $line;
        }

        // Normalize text
        $line->normalizedText = $this->lexer->normalize($trimmed);

        // Extract block number if present
        $blockToken = $line->getTokenByType(TokenType::BLOCK_NUMBER);
        if ($blockToken) {
            $line->blockNumber = (int)$blockToken->numericValue;
        }

        // Process tokens into commands and update modal state
        $this->processTokens($line);

        // Store modal state after processing
        $line->modalStateAfter = $this->currentState->copy();

        // Generate explanation
        $line->explanation = $this->explanationGenerator->generate($line);

        return $line;
    }

    /**
     * Process tokens into semantic commands
     */
    private function processTokens(Line $line): void
    {
        $axisValues = [];

        foreach ($line->tokens as $token) {
            switch ($token->type) {
                case TokenType::GCODE:
                    $this->processGCode($line, $token);
                    break;

                case TokenType::MCODE:
                    $this->processMCode($line, $token);
                    break;

                case TokenType::TOOL:
                    $this->processTool($line, $token);
                    break;

                case TokenType::FEED:
                    $this->currentState->updateFeed($token->numericValue);
                    break;

                case TokenType::SPINDLE:
                    $this->currentState->updateSpeed($token->numericValue);
                    break;

                case TokenType::AXIS_X:
                case TokenType::AXIS_Z:
                case TokenType::AXIS_Y:
                case TokenType::AXIS_C:
                case TokenType::AXIS_B:
                case TokenType::AXIS_W:
                case TokenType::AXIS_U:
                case TokenType::AXIS_A:
                    $axis = $token->getAxisLetter();
                    if ($axis) {
                        $axisValues[$axis] = $token->numericValue;
                        $this->currentState->updatePosition($axis, $token->numericValue);
                    }
                    break;
            }
        }

        // If there are axis values without explicit motion code, use current motion mode
        if (!empty($axisValues) && !$line->hasTokenType(TokenType::GCODE)) {
            if ($this->currentState->motionMode) {
                $command = new Command(
                    CommandType::MOTION,
                    $this->currentState->motionMode,
                    $axisValues,
                    $this->currentState->getMotionModeDescription()
                );
                $line->addCommand($command);
            }
        }
    }

    /**
     * Process a G-code token
     */
    private function processGCode(Line $line, Token $token): void
    {
        $code = $this->normalizeCode($token->value);

        // Update modal state
        $this->currentState->updateFromGCode($code);

        // Determine command type
        $type = $this->getGCodeCommandType($code);

        // Get axis values for motion commands
        $params = [];
        if ($type === CommandType::MOTION) {
            $params = $line->getAxisValues();

            // Add arc parameters if present
            foreach ([TokenType::RADIUS, TokenType::RADIUS_I, TokenType::RADIUS_J, TokenType::RADIUS_K] as $paramType) {
                $paramToken = $line->getTokenByType($paramType);
                if ($paramToken) {
                    $params[$this->getParamLetter($paramType)] = $paramToken->numericValue;
                }
            }
        }

        // Look up G-code definition for semantic meaning
        $gcode = GCode::findByCode($code, $this->machineId);
        $meaning = $gcode ? $gcode->name : null;

        $command = new Command($type, $code, $params, $meaning);
        $line->addCommand($command);
    }

    /**
     * Process an M-code token
     */
    private function processMCode(Line $line, Token $token): void
    {
        $code = $this->normalizeCode($token->value);

        // Update modal state
        $this->currentState->updateFromMCode($code);

        // Determine command type
        $type = $this->getMCodeCommandType($code);

        // Look up M-code definition for semantic meaning
        $mcode = MCode::findByCode($code, $this->machineId);
        $meaning = $mcode ? $mcode->name : null;

        $command = new Command($type, $code, [], $meaning);
        $line->addCommand($command);
    }

    /**
     * Process a tool token
     */
    private function processTool(Line $line, Token $token): void
    {
        $this->currentState->updateTool($token->value);

        $command = new Command(
            CommandType::TOOL_CHANGE,
            $token->value,
            ['tool_number' => $token->numericValue],
            'Tool change'
        );
        $line->addCommand($command);
    }

    /**
     * Normalize code format (G1 -> G01)
     */
    private function normalizeCode(string $code): string
    {
        if (preg_match('/^([GM])(\d)$/i', $code, $m)) {
            return strtoupper($m[1]) . '0' . $m[2];
        }
        return strtoupper($code);
    }

    /**
     * Determine command type for G-code
     */
    private function getGCodeCommandType(string $code): string
    {
        // Motion codes
        if (in_array($code, ['G00', 'G01', 'G02', 'G03'])) {
            return CommandType::MOTION;
        }

        // Canned cycles
        if (preg_match('/^G7[0-9]|G8[0-9]/', $code)) {
            return CommandType::CANNED_CYCLE;
        }

        // Compensation
        if (in_array($code, ['G40', 'G41', 'G42', 'G43', 'G49'])) {
            return CommandType::COMPENSATION;
        }

        // Coordinate system
        if (in_array($code, ['G54', 'G55', 'G56', 'G57', 'G58', 'G59', 'G90', 'G91'])) {
            return CommandType::COORDINATE_SYSTEM;
        }

        return CommandType::UNKNOWN;
    }

    /**
     * Determine command type for M-code
     */
    private function getMCodeCommandType(string $code): string
    {
        // Spindle control
        if (in_array($code, ['M03', 'M04', 'M05'])) {
            return CommandType::SPINDLE_CONTROL;
        }

        // Coolant control
        if (in_array($code, ['M07', 'M08', 'M09'])) {
            return CommandType::COOLANT_CONTROL;
        }

        // Program control
        if (in_array($code, ['M00', 'M01', 'M02', 'M30', 'M99'])) {
            return CommandType::PROGRAM_CONTROL;
        }

        // Tool change
        if ($code === 'M06') {
            return CommandType::TOOL_CHANGE;
        }

        return CommandType::UNKNOWN;
    }

    /**
     * Get parameter letter from token type
     */
    private function getParamLetter(string $tokenType): string
    {
        return match ($tokenType) {
            TokenType::RADIUS => 'R',
            TokenType::RADIUS_I => 'I',
            TokenType::RADIUS_J => 'J',
            TokenType::RADIUS_K => 'K',
            default => '',
        };
    }

    /**
     * Get current modal state
     */
    public function getModalState(): ModalState
    {
        return $this->currentState;
    }
}
