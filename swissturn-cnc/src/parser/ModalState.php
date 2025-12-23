<?php

namespace SwissTurn\Parser;

/**
 * Modal State Tracker
 *
 * Tracks active modal codes and machine state during parsing
 */
class ModalState
{
    // Motion mode (Group 1)
    public ?string $motionMode = null;          // G00, G01, G02, G03

    // Distance mode (Group 3)
    public ?string $distanceMode = 'G90';       // G90 (absolute), G91 (incremental)

    // Feed mode (Group 5)
    public ?string $feedMode = 'G94';           // G94 (per minute), G95 (per revolution)

    // Units (Group 6)
    public ?string $units = null;               // G20 (inch), G21 (metric)

    // Plane selection (Group 2)
    public ?string $plane = 'G18';              // G17 (XY), G18 (ZX), G19 (YZ)

    // Work offset (Group 12)
    public ?string $workOffset = 'G54';         // G54-G59

    // Cutter compensation (Group 7)
    public ?string $cutterComp = 'G40';         // G40, G41, G42

    // Spindle state
    public ?string $spindleState = null;        // M03, M04, M05

    // Coolant state
    public ?string $coolantState = null;        // M08, M09

    // Current tool
    public ?string $currentTool = null;

    // Current feed rate
    public ?float $currentFeed = null;

    // Current spindle speed
    public ?float $currentSpeed = null;

    // Current position
    public array $position = [
        'X' => 0.0,
        'Z' => 0.0,
        'Y' => 0.0,
        'C' => 0.0,
        'B' => 0.0,
        'W' => 0.0,
    ];

    /**
     * Create a copy of current state
     */
    public function copy(): self
    {
        $copy = new self();
        $copy->motionMode = $this->motionMode;
        $copy->distanceMode = $this->distanceMode;
        $copy->feedMode = $this->feedMode;
        $copy->units = $this->units;
        $copy->plane = $this->plane;
        $copy->workOffset = $this->workOffset;
        $copy->cutterComp = $this->cutterComp;
        $copy->spindleState = $this->spindleState;
        $copy->coolantState = $this->coolantState;
        $copy->currentTool = $this->currentTool;
        $copy->currentFeed = $this->currentFeed;
        $copy->currentSpeed = $this->currentSpeed;
        $copy->position = $this->position;
        return $copy;
    }

    /**
     * Update state from a G-code
     */
    public function updateFromGCode(string $code): void
    {
        $code = strtoupper($code);

        // Normalize code format (G1 -> G01)
        if (preg_match('/^G(\d)$/', $code, $m)) {
            $code = 'G0' . $m[1];
        }

        // Motion mode (Group 1)
        if (in_array($code, ['G00', 'G01', 'G02', 'G03'])) {
            $this->motionMode = $code;
            return;
        }

        // Plane selection (Group 2)
        if (in_array($code, ['G17', 'G18', 'G19'])) {
            $this->plane = $code;
            return;
        }

        // Distance mode (Group 3)
        if (in_array($code, ['G90', 'G91'])) {
            $this->distanceMode = $code;
            return;
        }

        // Feed mode (Group 5)
        if (in_array($code, ['G94', 'G95'])) {
            $this->feedMode = $code;
            return;
        }

        // Units (Group 6)
        if (in_array($code, ['G20', 'G21'])) {
            $this->units = $code;
            return;
        }

        // Cutter compensation (Group 7)
        if (in_array($code, ['G40', 'G41', 'G42'])) {
            $this->cutterComp = $code;
            return;
        }

        // Work offset (Group 12)
        if (in_array($code, ['G54', 'G55', 'G56', 'G57', 'G58', 'G59'])) {
            $this->workOffset = $code;
            return;
        }
    }

    /**
     * Update state from an M-code
     */
    public function updateFromMCode(string $code): void
    {
        $code = strtoupper($code);

        // Normalize code format (M3 -> M03)
        if (preg_match('/^M(\d)$/', $code, $m)) {
            $code = 'M0' . $m[1];
        }

        // Spindle control
        if (in_array($code, ['M03', 'M04', 'M05'])) {
            $this->spindleState = $code;
            return;
        }

        // Coolant control
        if (in_array($code, ['M07', 'M08', 'M09'])) {
            $this->coolantState = $code;
            return;
        }
    }

    /**
     * Update position from axis token
     */
    public function updatePosition(string $axis, float $value): void
    {
        $axis = strtoupper($axis);
        if (isset($this->position[$axis])) {
            if ($this->distanceMode === 'G91') {
                // Incremental mode
                $this->position[$axis] += $value;
            } else {
                // Absolute mode
                $this->position[$axis] = $value;
            }
        }
    }

    /**
     * Update tool
     */
    public function updateTool(string $tool): void
    {
        $this->currentTool = $tool;
    }

    /**
     * Update feed rate
     */
    public function updateFeed(float $feed): void
    {
        $this->currentFeed = $feed;
    }

    /**
     * Update spindle speed
     */
    public function updateSpeed(float $speed): void
    {
        $this->currentSpeed = $speed;
    }

    /**
     * Get motion mode description
     */
    public function getMotionModeDescription(): string
    {
        return match ($this->motionMode) {
            'G00' => 'Rapid traverse',
            'G01' => 'Linear interpolation',
            'G02' => 'Circular interpolation CW',
            'G03' => 'Circular interpolation CCW',
            default => 'Unknown motion',
        };
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'motion_mode' => $this->motionMode,
            'distance_mode' => $this->distanceMode,
            'feed_mode' => $this->feedMode,
            'units' => $this->units,
            'plane' => $this->plane,
            'work_offset' => $this->workOffset,
            'cutter_comp' => $this->cutterComp,
            'spindle_state' => $this->spindleState,
            'coolant_state' => $this->coolantState,
            'current_tool' => $this->currentTool,
            'current_feed' => $this->currentFeed,
            'current_speed' => $this->currentSpeed,
            'position' => $this->position,
        ];
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $state = new self();
        $state->motionMode = $data['motion_mode'] ?? null;
        $state->distanceMode = $data['distance_mode'] ?? 'G90';
        $state->feedMode = $data['feed_mode'] ?? 'G94';
        $state->units = $data['units'] ?? null;
        $state->plane = $data['plane'] ?? 'G18';
        $state->workOffset = $data['work_offset'] ?? 'G54';
        $state->cutterComp = $data['cutter_comp'] ?? 'G40';
        $state->spindleState = $data['spindle_state'] ?? null;
        $state->coolantState = $data['coolant_state'] ?? null;
        $state->currentTool = $data['current_tool'] ?? null;
        $state->currentFeed = $data['current_feed'] ?? null;
        $state->currentSpeed = $data['current_speed'] ?? null;
        $state->position = $data['position'] ?? [
            'X' => 0.0, 'Z' => 0.0, 'Y' => 0.0,
            'C' => 0.0, 'B' => 0.0, 'W' => 0.0,
        ];
        return $state;
    }
}
