<?php

namespace SwissTurn\Controllers;

use SwissTurn\Core\Response;
use SwissTurn\Models\GCode;
use SwissTurn\Models\Machine;

/**
 * G-Code Controller
 */
class GCodeController
{
    /**
     * List all G-codes
     */
    public function index(): void
    {
        $machineId = isset($_GET['machine_id']) ? (int)$_GET['machine_id'] : null;
        $gcodes = GCode::all($machineId);
        $machines = Machine::all(true);

        Response::render('gcodes/index', [
            'title' => 'G-Codes',
            'gcodes' => $gcodes,
            'machines' => $machines,
            'selectedMachine' => $machineId,
        ]);
    }

    /**
     * Show create form
     */
    public function create(): void
    {
        $machines = Machine::all(true);
        Response::render('gcodes/edit', [
            'title' => 'Add G-Code',
            'gcode' => null,
            'machines' => $machines,
        ]);
    }

    /**
     * Store new G-code
     */
    public function store(): void
    {
        $gcode = new GCode();
        $gcode->machine_id = !empty($_POST['machine_id']) ? (int)$_POST['machine_id'] : null;
        $gcode->code = strtoupper($_POST['code'] ?? '');
        $gcode->name = $_POST['name'] ?? '';
        $gcode->description = $_POST['description'] ?? null;
        $gcode->modal_group_id = !empty($_POST['modal_group_id']) ? (int)$_POST['modal_group_id'] : null;
        $gcode->is_modal = isset($_POST['is_modal']);
        $gcode->semantic_type = $_POST['semantic_type'] ?? null;
        $gcode->explanation_template = $_POST['explanation_template'] ?? null;
        $gcode->is_active = isset($_POST['is_active']);

        // Parse parameters JSON
        if (!empty($_POST['parameters'])) {
            $gcode->parameters = json_decode($_POST['parameters'], true);
        }

        if (empty($gcode->code) || empty($gcode->name)) {
            Response::error('Code and name are required', 400);
            return;
        }

        $gcode->save();
        Response::redirect('/gcodes');
    }

    /**
     * Show G-code details
     */
    public function show(string $id): void
    {
        $gcode = GCode::find((int)$id);
        if (!$gcode) {
            Response::notFound('G-Code not found');
            return;
        }

        Response::json($gcode->toArray());
    }

    /**
     * Show edit form
     */
    public function edit(string $id): void
    {
        $gcode = GCode::find((int)$id);
        if (!$gcode) {
            Response::notFound('G-Code not found');
            return;
        }

        $machines = Machine::all(true);
        Response::render('gcodes/edit', [
            'title' => 'Edit ' . $gcode->code,
            'gcode' => $gcode,
            'machines' => $machines,
        ]);
    }

    /**
     * Update G-code
     */
    public function update(string $id): void
    {
        $gcode = GCode::find((int)$id);
        if (!$gcode) {
            Response::notFound('G-Code not found');
            return;
        }

        $gcode->machine_id = !empty($_POST['machine_id']) ? (int)$_POST['machine_id'] : null;
        $gcode->code = strtoupper($_POST['code'] ?? $gcode->code);
        $gcode->name = $_POST['name'] ?? $gcode->name;
        $gcode->description = $_POST['description'] ?? $gcode->description;
        $gcode->modal_group_id = !empty($_POST['modal_group_id']) ? (int)$_POST['modal_group_id'] : null;
        $gcode->is_modal = isset($_POST['is_modal']);
        $gcode->semantic_type = $_POST['semantic_type'] ?? $gcode->semantic_type;
        $gcode->explanation_template = $_POST['explanation_template'] ?? $gcode->explanation_template;
        $gcode->is_active = isset($_POST['is_active']);

        if (!empty($_POST['parameters'])) {
            $gcode->parameters = json_decode($_POST['parameters'], true);
        }

        $gcode->save();
        Response::redirect('/gcodes');
    }

    /**
     * Delete G-code
     */
    public function destroy(string $id): void
    {
        $gcode = GCode::find((int)$id);
        if (!$gcode) {
            Response::notFound('G-Code not found');
            return;
        }

        $gcode->delete();
        Response::redirect('/gcodes');
    }
}
