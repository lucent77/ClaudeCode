<?php

namespace SwissTurn\Parser;

use SwissTurn\Parser\IR\Line;
use SwissTurn\Parser\IR\Command;
use SwissTurn\Parser\IR\CommandType;
use SwissTurn\Models\GCode;
use SwissTurn\Models\MCode;

/**
 * Generates human-readable explanations for CNC code lines
 */
class ExplanationGenerator
{
    private int $machineId;

    public function __construct(int $machineId)
    {
        $this->machineId = $machineId;
    }

    /**
     * Generate explanation for a parsed line
     */
    public function generate(Line $line): string
    {
        if ($line->isCommentOnly) {
            return $line->explanation ?: 'Comment';
        }

        if (empty($line->commands)) {
            return $this->generateFromTokens($line);
        }

        $explanations = [];

        foreach ($line->commands as $command) {
            $explanation = $this->explainCommand($command, $line);
            if ($explanation) {
                $explanations[] = $explanation;
            }
        }

        // Add feed/speed info if present
        $feedToken = $line->getTokenByType(TokenType::FEED);
        if ($feedToken) {
            $feedMode = $line->modalStateAfter?->feedMode ?? 'G94';
            $unit = $feedMode === 'G95' ? 'mm/rev' : 'mm/min';
            $explanations[] = "Feed: {$feedToken->numericValue} {$unit}";
        }

        $speedToken = $line->getTokenByType(TokenType::SPINDLE);
        if ($speedToken) {
            $explanations[] = "Speed: {$speedToken->numericValue} RPM";
        }

        return implode('; ', $explanations) ?: 'No explanation available';
    }

    /**
     * Generate explanation from tokens when no commands parsed
     */
    private function generateFromTokens(Line $line): string
    {
        $parts = [];

        foreach ($line->tokens as $token) {
            switch ($token->type) {
                case TokenType::BLOCK_NUMBER:
                    $parts[] = "Block N{$token->numericValue}";
                    break;

                case TokenType::LABEL:
                    $parts[] = "Program {$token->value}";
                    break;
            }
        }

        return implode('; ', $parts) ?: 'Code line';
    }

    /**
     * Generate explanation for a single command
     */
    private function explainCommand(Command $command, Line $line): ?string
    {
        switch ($command->type) {
            case CommandType::MOTION:
                return $this->explainMotion($command, $line);

            case CommandType::SPINDLE_CONTROL:
                return $this->explainSpindle($command);

            case CommandType::COOLANT_CONTROL:
                return $this->explainCoolant($command);

            case CommandType::TOOL_CHANGE:
                return $this->explainToolChange($command);

            case CommandType::PROGRAM_CONTROL:
                return $this->explainProgramControl($command);

            case CommandType::CANNED_CYCLE:
                return $this->explainCannedCycle($command, $line);

            case CommandType::COMPENSATION:
                return $this->explainCompensation($command);

            case CommandType::COORDINATE_SYSTEM:
                return $this->explainCoordinateSystem($command);

            default:
                return $this->explainFromDatabase($command);
        }
    }

    /**
     * Explain motion command
     */
    private function explainMotion(Command $command, Line $line): string
    {
        $code = $command->code;
        $params = $command->parameters;

        $motionType = match ($code) {
            'G00' => 'Rapid move',
            'G01' => 'Linear cut',
            'G02' => 'Arc CW',
            'G03' => 'Arc CCW',
            default => 'Move',
        };

        $coordParts = [];
        foreach (['X', 'Z', 'Y', 'C', 'B', 'W', 'U', 'A'] as $axis) {
            if (isset($params[$axis])) {
                $coordParts[] = "{$axis}{$params[$axis]}";
            }
        }

        if (empty($coordParts)) {
            return $motionType;
        }

        $coords = implode(' ', $coordParts);

        // Add arc radius if present
        if (isset($params['R'])) {
            $coords .= " R{$params['R']}";
        }

        return "{$motionType} to {$coords}";
    }

    /**
     * Explain spindle command
     */
    private function explainSpindle(Command $command): string
    {
        return match ($command->code) {
            'M03' => 'Spindle ON clockwise (CW)',
            'M04' => 'Spindle ON counter-clockwise (CCW)',
            'M05' => 'Spindle STOP',
            default => "Spindle {$command->code}",
        };
    }

    /**
     * Explain coolant command
     */
    private function explainCoolant(Command $command): string
    {
        return match ($command->code) {
            'M07' => 'Mist coolant ON',
            'M08' => 'Flood coolant ON',
            'M09' => 'Coolant OFF',
            default => "Coolant {$command->code}",
        };
    }

    /**
     * Explain tool change
     */
    private function explainToolChange(Command $command): string
    {
        if ($command->code === 'M06') {
            return 'Tool change';
        }

        $toolNum = $command->getParameter('tool_number');
        if ($toolNum !== null) {
            // Parse tool number - first 2 digits = tool, last 2 = offset
            $tool = floor($toolNum / 100);
            $offset = $toolNum % 100;

            if ($offset > 0) {
                return "Select Tool {$tool} with Offset {$offset}";
            }
            return "Select Tool {$toolNum}";
        }

        return "Tool {$command->code}";
    }

    /**
     * Explain program control
     */
    private function explainProgramControl(Command $command): string
    {
        return match ($command->code) {
            'M00' => 'Program STOP (unconditional)',
            'M01' => 'Optional STOP',
            'M02' => 'Program END',
            'M30' => 'Program END and rewind',
            'M99' => 'Return from subroutine / Loop',
            default => "Program control {$command->code}",
        };
    }

    /**
     * Explain canned cycle
     */
    private function explainCannedCycle(Command $command, Line $line): string
    {
        $gcode = GCode::findByCode($command->code, $this->machineId);
        if ($gcode && $gcode->explanation_template) {
            return $this->fillTemplate($gcode->explanation_template, $command->parameters);
        }

        $cycleName = match ($command->code) {
            'G70' => 'Finishing cycle',
            'G71' => 'Rough turning cycle',
            'G72' => 'Rough facing cycle',
            'G73' => 'Pattern repeating cycle',
            'G74' => 'Peck drilling cycle (Z-axis)',
            'G75' => 'Grooving cycle (X-axis)',
            'G76' => 'Threading cycle',
            'G80' => 'Cancel canned cycle',
            'G81' => 'Drilling cycle',
            'G82' => 'Drilling cycle with dwell',
            'G83' => 'Peck drilling cycle',
            'G84' => 'Tapping cycle',
            default => "Canned cycle {$command->code}",
        };

        return $cycleName;
    }

    /**
     * Explain compensation codes
     */
    private function explainCompensation(Command $command): string
    {
        return match ($command->code) {
            'G40' => 'Cancel cutter compensation',
            'G41' => 'Cutter compensation LEFT',
            'G42' => 'Cutter compensation RIGHT',
            'G43' => 'Tool length compensation +',
            'G49' => 'Cancel tool length compensation',
            default => "Compensation {$command->code}",
        };
    }

    /**
     * Explain coordinate system codes
     */
    private function explainCoordinateSystem(Command $command): string
    {
        return match ($command->code) {
            'G54' => 'Work coordinate system 1',
            'G55' => 'Work coordinate system 2',
            'G56' => 'Work coordinate system 3',
            'G57' => 'Work coordinate system 4',
            'G58' => 'Work coordinate system 5',
            'G59' => 'Work coordinate system 6',
            'G90' => 'Absolute positioning mode',
            'G91' => 'Incremental positioning mode',
            default => "Coordinate {$command->code}",
        };
    }

    /**
     * Look up explanation from database
     */
    private function explainFromDatabase(Command $command): ?string
    {
        $code = $command->code;

        if (str_starts_with($code, 'G')) {
            $gcode = GCode::findByCode($code, $this->machineId);
            if ($gcode) {
                if ($gcode->explanation_template) {
                    return $this->fillTemplate($gcode->explanation_template, $command->parameters);
                }
                return $gcode->name;
            }
        }

        if (str_starts_with($code, 'M')) {
            $mcode = MCode::findByCode($code, $this->machineId);
            if ($mcode) {
                if ($mcode->explanation_template) {
                    return $this->fillTemplate($mcode->explanation_template, $command->parameters);
                }
                return $mcode->name;
            }
        }

        return $command->semanticMeaning ?: "Code {$code}";
    }

    /**
     * Fill explanation template with parameter values
     */
    private function fillTemplate(string $template, array $params): string
    {
        foreach ($params as $key => $value) {
            $template = str_replace('{' . $key . '}', (string)$value, $template);
        }
        return $template;
    }
}
