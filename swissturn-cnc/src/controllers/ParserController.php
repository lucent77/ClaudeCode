<?php

namespace SwissTurn\Controllers;

use SwissTurn\Core\Response;
use SwissTurn\Models\Program;
use SwissTurn\Models\ParsedLine;
use SwissTurn\Parser\Parser;

/**
 * Parser API Controller
 */
class ParserController
{
    /**
     * Parse code on-the-fly (AJAX)
     */
    public function parse(): void
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $machineId = (int)($data['machine_id'] ?? 0);
        $code = $data['code'] ?? '';

        if (!$machineId || empty($code)) {
            Response::error('Machine ID and code are required', 400);
            return;
        }

        try {
            $parser = new Parser($machineId);
            $ir = $parser->parse($code);

            Response::success([
                'lines' => array_map(fn($l) => $l->toArray(), $ir->lines),
                'statistics' => $ir->getStatistics(),
            ]);
        } catch (\Exception $e) {
            Response::error('Parse error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get parsed lines for a program (AJAX)
     */
    public function getLines(string $id): void
    {
        $program = Program::find((int)$id);
        if (!$program) {
            Response::notFound('Program not found');
            return;
        }

        $offset = (int)($_GET['offset'] ?? 0);
        $limit = (int)($_GET['limit'] ?? 100);

        $lines = ParsedLine::findByProgram($program->id);

        // Apply pagination
        $pagedLines = array_slice($lines, $offset, $limit);

        Response::success([
            'total' => count($lines),
            'offset' => $offset,
            'limit' => $limit,
            'lines' => array_map(fn($l) => $l->toArray(), $pagedLines),
        ]);
    }
}
