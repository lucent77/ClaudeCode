<?php
/**
 * Import Controller
 *
 * Handles importing data from Evolution Web Portal
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ImportService;
use App\Services\EvolutionClient;
use Exception;

class ImportController extends Controller
{
    private ImportService $importService;
    private EvolutionClient $evoClient;

    public function __construct()
    {
        parent::__construct();
        $this->importService = new ImportService();
        $this->evoClient = new EvolutionClient();
    }

    /**
     * Show import management page
     */
    public function index(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'super_admin']);

        $this->view('import.index', [
            'title' => 'Import Management - CREODENT Work Manager',
            'user' => $this->getUser(),
        ]);
    }

    /**
     * Import from Evolution Web Portal
     */
    public function importFromEvolution(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'super_admin']);

        try {
            $startDate = $this->input('start_date') ?? date('Y-m-d', strtotime('-1 day'));
            $endDate = $this->input('end_date') ?? date('Y-m-d');

            // Run import
            $result = $this->importService->importFromEvolution($startDate, $endDate);

            $this->success($result, $result['message']);

        } catch (Exception $e) {
            $this->error($e->getMessage(), null, 500);
        }
    }

    /**
     * Get import history
     */
    public function getHistory(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'super_admin']);

        try {
            $limit = (int)($this->input('limit') ?? 20);
            $history = $this->importService->getImportHistory($limit);

            $this->success($history);

        } catch (Exception $e) {
            $this->error($e->getMessage(), null, 500);
        }
    }

    /**
     * Test Evolution connection
     */
    public function testConnection(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'super_admin']);

        try {
            $result = $this->evoClient->testConnection();

            if ($result['success']) {
                $this->success($result['data'], $result['message']);
            } else {
                $this->error($result['message'], $result['error'] ?? null, 500);
            }

        } catch (Exception $e) {
            $this->error($e->getMessage(), null, 500);
        }
    }

    /**
     * Import single case by case number
     */
    public function importSingleCase(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'super_admin']);

        try {
            $caseNo = $this->input('case_no');

            if (!$caseNo) {
                $this->error('Case number is required', null, 400);
                return;
            }

            // Get case information from Evolution
            $caseData = $this->evoClient->getCaseInformation($caseNo);

            // Import to database
            $caseId = $this->importService->importSingleCase($caseData);

            $this->success([
                'case_id' => $caseId,
                'case_no' => $caseNo,
            ], 'Case imported successfully');

        } catch (Exception $e) {
            $this->error($e->getMessage(), null, 500);
        }
    }
}
