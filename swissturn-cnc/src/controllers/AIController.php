<?php

namespace SwissTurn\Controllers;

use SwissTurn\Core\Response;
use SwissTurn\Models\Program;
use SwissTurn\Models\Machine;
use SwissTurn\Services\GeminiService;

/**
 * AI Analysis Controller using Gemini API
 */
class AIController
{
    private GeminiService $gemini;

    public function __construct()
    {
        $this->gemini = new GeminiService();
    }

    /**
     * Show AI analysis page for a program
     */
    public function analyze(string $id): void
    {
        $program = Program::find((int)$id);
        if (!$program) {
            Response::notFound('Program not found');
            return;
        }

        $machine = $program->getMachine();

        Response::render('programs/ai-analysis', [
            'title' => 'AI Analysis - ' . $program->name,
            'program' => $program,
            'machine' => $machine,
            'isConfigured' => $this->gemini->isConfigured(),
        ]);
    }

    /**
     * API: Analyze program (AJAX)
     */
    public function apiAnalyze(): void
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $programId = (int)($data['program_id'] ?? 0);
        $analysisType = $data['type'] ?? 'analyze';

        $program = Program::find($programId);
        if (!$program) {
            Response::error('Program not found', 404);
            return;
        }

        $machine = $program->getMachine();
        $machineType = $machine ? $machine->manufacturer . ' ' . $machine->name : 'Unknown';
        $controllerType = $machine ? $machine->controller_type : 'Unknown';

        $result = match ($analysisType) {
            'analyze' => $this->gemini->analyzeProgram(
                $program->source_code,
                $machineType,
                $controllerType
            ),
            'optimize' => $this->gemini->optimizeProgram(
                $program->source_code,
                $machineType,
                $controllerType
            ),
            'errors' => $this->gemini->detectErrors(
                $program->source_code,
                $machineType,
                $controllerType
            ),
            default => ['success' => false, 'error' => 'Invalid analysis type'],
        };

        if ($result['success']) {
            $result['html'] = $this->gemini->formatToHtml($result['content']);
        }

        Response::json($result);
    }

    /**
     * API: Explain a specific line (AJAX)
     */
    public function apiExplainLine(): void
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $programId = (int)($data['program_id'] ?? 0);
        $lineNumber = (int)($data['line_number'] ?? 0);

        $program = Program::find($programId);
        if (!$program) {
            Response::error('Program not found', 404);
            return;
        }

        // Get the line and context
        $lines = explode("\n", $program->source_code);
        if ($lineNumber < 1 || $lineNumber > count($lines)) {
            Response::error('Invalid line number', 400);
            return;
        }

        $targetLine = $lines[$lineNumber - 1];

        // Get 5 lines of context before
        $contextStart = max(0, $lineNumber - 6);
        $contextLines = array_slice($lines, $contextStart, 5);
        $context = implode("\n", $contextLines);

        $machine = $program->getMachine();
        $machineType = $machine ? $machine->manufacturer . ' ' . $machine->name : 'Unknown';
        $controllerType = $machine ? $machine->controller_type : 'Unknown';

        $result = $this->gemini->explainLine(
            $targetLine,
            $context,
            $machineType,
            $controllerType
        );

        if ($result['success']) {
            $result['html'] = $this->gemini->formatToHtml($result['content']);
        }

        Response::json($result);
    }

    /**
     * API: Custom analysis (AJAX)
     */
    public function apiCustom(): void
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $programId = (int)($data['program_id'] ?? 0);
        $prompt = $data['prompt'] ?? '';

        if (empty($prompt)) {
            Response::error('Prompt is required', 400);
            return;
        }

        $program = Program::find($programId);
        if (!$program) {
            Response::error('Program not found', 404);
            return;
        }

        $machine = $program->getMachine();
        $machineType = $machine ? $machine->manufacturer . ' ' . $machine->name : 'Unknown';
        $controllerType = $machine ? $machine->controller_type : 'Unknown';

        $result = $this->gemini->customAnalysis(
            $prompt,
            $program->source_code,
            $machineType,
            $controllerType
        );

        if ($result['success']) {
            $result['html'] = $this->gemini->formatToHtml($result['content']);
        }

        Response::json($result);
    }

    /**
     * API: Check if Gemini is configured
     */
    public function apiStatus(): void
    {
        Response::json([
            'configured' => $this->gemini->isConfigured(),
        ]);
    }
}
