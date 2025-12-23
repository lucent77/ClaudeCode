<?php

namespace SwissTurn\Controllers;

use SwissTurn\Core\Response;
use SwissTurn\Models\MCode;
use SwissTurn\Models\Machine;

/**
 * M-Code Controller
 */
class MCodeController
{
    /**
     * List all M-codes
     */
    public function index(): void
    {
        $machineId = isset($_GET['machine_id']) ? (int)$_GET['machine_id'] : null;
        $mcodes = MCode::all($machineId);
        $machines = Machine::all(true);

        Response::render('mcodes/index', [
            'title' => 'M-Codes',
            'mcodes' => $mcodes,
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
        Response::render('mcodes/edit', [
            'title' => 'Add M-Code',
            'mcode' => null,
            'machines' => $machines,
        ]);
    }

    /**
     * Store new M-code
     */
    public function store(): void
    {
        $mcode = new MCode();
        $mcode->machine_id = !empty($_POST['machine_id']) ? (int)$_POST['machine_id'] : null;
        $mcode->code = strtoupper($_POST['code'] ?? '');
        $mcode->name = $_POST['name'] ?? '';
        $mcode->description = $_POST['description'] ?? null;
        $mcode->semantic_type = $_POST['semantic_type'] ?? null;
        $mcode->explanation_template = $_POST['explanation_template'] ?? null;
        $mcode->is_active = isset($_POST['is_active']);

        if (!empty($_POST['parameters'])) {
            $mcode->parameters = json_decode($_POST['parameters'], true);
        }

        if (empty($mcode->code) || empty($mcode->name)) {
            Response::error('Code and name are required', 400);
            return;
        }

        $mcode->save();
        Response::redirect('/mcodes');
    }

    /**
     * Show M-code details
     */
    public function show(string $id): void
    {
        $mcode = MCode::find((int)$id);
        if (!$mcode) {
            Response::notFound('M-Code not found');
            return;
        }

        Response::json($mcode->toArray());
    }

    /**
     * Show edit form
     */
    public function edit(string $id): void
    {
        $mcode = MCode::find((int)$id);
        if (!$mcode) {
            Response::notFound('M-Code not found');
            return;
        }

        $machines = Machine::all(true);
        Response::render('mcodes/edit', [
            'title' => 'Edit ' . $mcode->code,
            'mcode' => $mcode,
            'machines' => $machines,
        ]);
    }

    /**
     * Update M-code
     */
    public function update(string $id): void
    {
        $mcode = MCode::find((int)$id);
        if (!$mcode) {
            Response::notFound('M-Code not found');
            return;
        }

        $mcode->machine_id = !empty($_POST['machine_id']) ? (int)$_POST['machine_id'] : null;
        $mcode->code = strtoupper($_POST['code'] ?? $mcode->code);
        $mcode->name = $_POST['name'] ?? $mcode->name;
        $mcode->description = $_POST['description'] ?? $mcode->description;
        $mcode->semantic_type = $_POST['semantic_type'] ?? $mcode->semantic_type;
        $mcode->explanation_template = $_POST['explanation_template'] ?? $mcode->explanation_template;
        $mcode->is_active = isset($_POST['is_active']);

        if (!empty($_POST['parameters'])) {
            $mcode->parameters = json_decode($_POST['parameters'], true);
        }

        $mcode->save();
        Response::redirect('/mcodes');
    }

    /**
     * Delete M-code
     */
    public function destroy(string $id): void
    {
        $mcode = MCode::find((int)$id);
        if (!$mcode) {
            Response::notFound('M-Code not found');
            return;
        }

        $mcode->delete();
        Response::redirect('/mcodes');
    }
}
