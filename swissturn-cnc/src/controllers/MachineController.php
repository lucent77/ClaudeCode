<?php

namespace SwissTurn\Controllers;

use SwissTurn\Core\Response;
use SwissTurn\Models\Machine;

/**
 * Machine Controller
 */
class MachineController
{
    /**
     * List all machines
     */
    public function index(): void
    {
        $machines = Machine::all();
        Response::render('machines/index', [
            'title' => 'Machines',
            'machines' => $machines,
        ]);
    }

    /**
     * Show create form
     */
    public function create(): void
    {
        Response::render('machines/edit', [
            'title' => 'Add Machine',
            'machine' => null,
        ]);
    }

    /**
     * Store new machine
     */
    public function store(): void
    {
        $machine = new Machine();
        $machine->name = $_POST['name'] ?? '';
        $machine->manufacturer = $_POST['manufacturer'] ?? '';
        $machine->controller_type = $_POST['controller_type'] ?? '';
        $machine->description = $_POST['description'] ?? null;
        $machine->is_active = isset($_POST['is_active']);

        if (empty($machine->name)) {
            Response::error('Machine name is required', 400);
            return;
        }

        $machine->save();
        Response::redirect('/machines');
    }

    /**
     * Show machine details
     */
    public function show(string $id): void
    {
        $machine = Machine::find((int)$id);
        if (!$machine) {
            Response::notFound('Machine not found');
            return;
        }

        Response::render('machines/show', [
            'title' => $machine->name,
            'machine' => $machine,
        ]);
    }

    /**
     * Show edit form
     */
    public function edit(string $id): void
    {
        $machine = Machine::find((int)$id);
        if (!$machine) {
            Response::notFound('Machine not found');
            return;
        }

        Response::render('machines/edit', [
            'title' => 'Edit ' . $machine->name,
            'machine' => $machine,
        ]);
    }

    /**
     * Update machine
     */
    public function update(string $id): void
    {
        $machine = Machine::find((int)$id);
        if (!$machine) {
            Response::notFound('Machine not found');
            return;
        }

        $machine->name = $_POST['name'] ?? $machine->name;
        $machine->manufacturer = $_POST['manufacturer'] ?? $machine->manufacturer;
        $machine->controller_type = $_POST['controller_type'] ?? $machine->controller_type;
        $machine->description = $_POST['description'] ?? $machine->description;
        $machine->is_active = isset($_POST['is_active']);

        $machine->save();
        Response::redirect('/machines');
    }

    /**
     * Delete machine
     */
    public function destroy(string $id): void
    {
        $machine = Machine::find((int)$id);
        if (!$machine) {
            Response::notFound('Machine not found');
            return;
        }

        $machine->delete();
        Response::redirect('/machines');
    }
}
