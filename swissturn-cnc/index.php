<?php
/**
 * SwissTurn CNC Parser - Application Entry Point
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path (root directory for Hostinger compatibility)
define('BASE_PATH', __DIR__);

// Simple autoloader
spl_autoload_register(function ($class) {
    $prefix = 'SwissTurn\\';
    $baseDir = BASE_PATH . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use SwissTurn\Core\Router;
use SwissTurn\Core\Response;
use SwissTurn\Controllers\MachineController;
use SwissTurn\Controllers\GCodeController;
use SwissTurn\Controllers\MCodeController;
use SwissTurn\Controllers\ProgramController;
use SwissTurn\Controllers\ParserController;
use SwissTurn\Controllers\AIController;

// Create router
$router = new Router();

// Home page
$router->get('/', function () {
    Response::render('home', ['title' => 'SwissTurn CNC Parser']);
});

// Machine routes
$router->get('/machines', [MachineController::class, 'index']);
$router->get('/machines/create', [MachineController::class, 'create']);
$router->post('/machines', [MachineController::class, 'store']);
$router->get('/machines/{id}', [MachineController::class, 'show']);
$router->get('/machines/{id}/edit', [MachineController::class, 'edit']);
$router->post('/machines/{id}', [MachineController::class, 'update']);
$router->post('/machines/{id}/delete', [MachineController::class, 'destroy']);

// G-Code routes
$router->get('/gcodes', [GCodeController::class, 'index']);
$router->get('/gcodes/create', [GCodeController::class, 'create']);
$router->post('/gcodes', [GCodeController::class, 'store']);
$router->get('/gcodes/{id}', [GCodeController::class, 'show']);
$router->get('/gcodes/{id}/edit', [GCodeController::class, 'edit']);
$router->post('/gcodes/{id}', [GCodeController::class, 'update']);
$router->post('/gcodes/{id}/delete', [GCodeController::class, 'destroy']);

// M-Code routes
$router->get('/mcodes', [MCodeController::class, 'index']);
$router->get('/mcodes/create', [MCodeController::class, 'create']);
$router->post('/mcodes', [MCodeController::class, 'store']);
$router->get('/mcodes/{id}', [MCodeController::class, 'show']);
$router->get('/mcodes/{id}/edit', [MCodeController::class, 'edit']);
$router->post('/mcodes/{id}', [MCodeController::class, 'update']);
$router->post('/mcodes/{id}/delete', [MCodeController::class, 'destroy']);

// Program routes
$router->get('/programs', [ProgramController::class, 'index']);
$router->get('/programs/upload', [ProgramController::class, 'upload']);
$router->post('/programs', [ProgramController::class, 'store']);
$router->get('/programs/{id}', [ProgramController::class, 'show']);
$router->get('/programs/{id}/annotated', [ProgramController::class, 'annotated']);
$router->post('/programs/{id}/delete', [ProgramController::class, 'destroy']);
$router->post('/programs/{id}/reparse', [ProgramController::class, 'reparse']);

// AI Analysis routes
$router->get('/programs/{id}/ai', [AIController::class, 'analyze']);

// API routes for AJAX
$router->post('/api/parse', [ParserController::class, 'parse']);
$router->get('/api/programs/{id}/lines', [ParserController::class, 'getLines']);

// AI API routes
$router->get('/api/ai/status', [AIController::class, 'apiStatus']);
$router->post('/api/ai/analyze', [AIController::class, 'apiAnalyze']);
$router->post('/api/ai/explain-line', [AIController::class, 'apiExplainLine']);
$router->post('/api/ai/custom', [AIController::class, 'apiCustom']);

// Dispatch request
try {
    $router->dispatch();
} catch (\Exception $e) {
    if (getenv('APP_DEBUG')) {
        Response::error($e->getMessage(), 500, ['trace' => $e->getTraceAsString()]);
    } else {
        Response::error('An error occurred', 500);
    }
}
