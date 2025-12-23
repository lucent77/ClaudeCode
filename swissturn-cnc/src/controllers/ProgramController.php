<?php

namespace SwissTurn\Controllers;

use SwissTurn\Core\Response;
use SwissTurn\Models\Program;
use SwissTurn\Models\Machine;
use SwissTurn\Models\ParsedLine;
use SwissTurn\Parser\Parser;

/**
 * Program Controller
 */
class ProgramController
{
    /**
     * List all programs
     */
    public function index(): void
    {
        $machineId = isset($_GET['machine_id']) ? (int)$_GET['machine_id'] : null;
        $programs = Program::all($machineId);
        $machines = Machine::all(true);

        Response::render('programs/index', [
            'title' => 'Programs',
            'programs' => $programs,
            'machines' => $machines,
            'selectedMachine' => $machineId,
        ]);
    }

    /**
     * Show upload form
     */
    public function upload(): void
    {
        $machines = Machine::all(true);
        Response::render('programs/upload', [
            'title' => 'Upload Program',
            'machines' => $machines,
        ]);
    }

    /**
     * Store uploaded program
     */
    public function store(): void
    {
        $machineId = (int)($_POST['machine_id'] ?? 0);
        if (!$machineId) {
            Response::error('Machine selection is required', 400);
            return;
        }

        $machine = Machine::find($machineId);
        if (!$machine) {
            Response::error('Invalid machine selected', 400);
            return;
        }

        // Handle file upload or direct paste
        $sourceCode = '';
        $filename = null;

        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $filename = $_FILES['file']['name'];
            $sourceCode = file_get_contents($_FILES['file']['tmp_name']);
        } elseif (!empty($_POST['source_code'])) {
            $sourceCode = $_POST['source_code'];
            $filename = $_POST['name'] ?? 'Pasted program';
        } else {
            Response::error('No program code provided', 400);
            return;
        }

        // Create program record
        $program = new Program();
        $program->machine_id = $machineId;
        $program->name = $_POST['name'] ?? $filename ?? 'Untitled';
        $program->source_code = $sourceCode;
        $program->source_filename = $filename;
        $program->status = 'parsing';
        $program->line_count = substr_count($sourceCode, "\n") + 1;
        $program->save();

        // Parse the program
        try {
            $parser = new Parser($machineId);
            $ir = $parser->parse($sourceCode, $filename);

            // Store parsed lines
            foreach ($ir->lines as $irLine) {
                $parsedLine = new ParsedLine();
                $parsedLine->program_id = $program->id;
                $parsedLine->line_number = $irLine->lineNumber;
                $parsedLine->block_number = $irLine->blockNumber;
                $parsedLine->raw_text = $irLine->rawText;
                $parsedLine->normalized_text = $irLine->normalizedText;
                $parsedLine->tokens = array_map(fn($t) => $t->toArray(), $irLine->tokens);
                $parsedLine->commands = array_map(fn($c) => $c->toArray(), $irLine->commands);
                $parsedLine->modal_state_before = $irLine->modalStateBefore?->toArray();
                $parsedLine->modal_state_after = $irLine->modalStateAfter?->toArray();
                $parsedLine->explanation = $irLine->explanation;
                $parsedLine->is_comment_only = $irLine->isCommentOnly;
                $parsedLine->has_errors = $irLine->hasErrors;
                $parsedLine->errors = $irLine->errors;
                $parsedLine->save();
            }

            $program->updateStatus('parsed');
        } catch (\Exception $e) {
            $program->updateStatus('error', $e->getMessage());
        }

        Response::redirect('/programs/' . $program->id);
    }

    /**
     * Show program details
     */
    public function show(string $id): void
    {
        $program = Program::find((int)$id);
        if (!$program) {
            Response::notFound('Program not found');
            return;
        }

        $machine = $program->getMachine();
        $lines = $program->getParsedLines();

        Response::render('programs/view', [
            'title' => $program->name,
            'program' => $program,
            'machine' => $machine,
            'lines' => $lines,
        ]);
    }

    /**
     * Show annotated program view
     */
    public function annotated(string $id): void
    {
        $program = Program::find((int)$id);
        if (!$program) {
            Response::notFound('Program not found');
            return;
        }

        $machine = $program->getMachine();
        $lines = $program->getParsedLines();

        Response::render('programs/annotated', [
            'title' => $program->name . ' - Annotated',
            'program' => $program,
            'machine' => $machine,
            'lines' => $lines,
        ]);
    }

    /**
     * Delete program
     */
    public function destroy(string $id): void
    {
        $program = Program::find((int)$id);
        if (!$program) {
            Response::notFound('Program not found');
            return;
        }

        $program->delete();
        Response::redirect('/programs');
    }

    /**
     * Re-parse program
     */
    public function reparse(string $id): void
    {
        $program = Program::find((int)$id);
        if (!$program) {
            Response::notFound('Program not found');
            return;
        }

        // Delete existing parsed lines
        ParsedLine::deleteByProgram($program->id);

        // Re-parse
        $program->updateStatus('parsing');

        try {
            $parser = new Parser($program->machine_id);
            $ir = $parser->parse($program->source_code, $program->source_filename);

            foreach ($ir->lines as $irLine) {
                $parsedLine = new ParsedLine();
                $parsedLine->program_id = $program->id;
                $parsedLine->line_number = $irLine->lineNumber;
                $parsedLine->block_number = $irLine->blockNumber;
                $parsedLine->raw_text = $irLine->rawText;
                $parsedLine->normalized_text = $irLine->normalizedText;
                $parsedLine->tokens = array_map(fn($t) => $t->toArray(), $irLine->tokens);
                $parsedLine->commands = array_map(fn($c) => $c->toArray(), $irLine->commands);
                $parsedLine->modal_state_before = $irLine->modalStateBefore?->toArray();
                $parsedLine->modal_state_after = $irLine->modalStateAfter?->toArray();
                $parsedLine->explanation = $irLine->explanation;
                $parsedLine->is_comment_only = $irLine->isCommentOnly;
                $parsedLine->has_errors = $irLine->hasErrors;
                $parsedLine->errors = $irLine->errors;
                $parsedLine->save();
            }

            $program->updateStatus('parsed');
        } catch (\Exception $e) {
            $program->updateStatus('error', $e->getMessage());
        }

        Response::redirect('/programs/' . $program->id);
    }
}
