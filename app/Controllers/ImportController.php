<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\EvolutionClient;
use App\Services\CaseService;

/**
 * Import Controller
 *
 * Handles bulk imports from Evolution Portal
 */
class ImportController extends Controller
{
    private EvolutionClient $evolutionClient;
    private CaseService $caseService;

    public function __construct()
    {
        parent::__construct();

        // Load configuration
        $config = require BASE_PATH . '/config/config.php';

        $this->evolutionClient = new EvolutionClient($config);
        $this->caseService = new CaseService();
    }

    /**
     * Show import form
     */
    public function index(): void
    {
        $this->requireAnyRole(['super_admin', 'admin', 'manager']);

        $this->view('imports/index', [
            'title' => 'Import Cases - CREODENT Work Manager'
        ]);
    }

    /**
     * Execute bulk import from Evolution Portal
     */
    public function execute(): void
    {
        $this->requireAnyRole(['super_admin', 'admin', 'manager']);
        $this->validateCsrf();

        try {
            // Get import parameters
            $fromDate = $this->input('from_date');
            $toDate = $this->input('to_date');
            $location = $this->input('location') ?? 'HV';
            $overwriteExisting = (bool) $this->input('overwrite_existing');

            // Validate inputs
            if (empty($fromDate) || empty($toDate)) {
                throw new \Exception('From date and To date are required');
            }

            if (!in_array($location, ['HV', 'NYC'])) {
                throw new \Exception('Invalid location. Must be HV or NYC');
            }

            // Convert dates to proper format
            $fromDate = date('Y-m-d', strtotime($fromDate));
            $toDate = date('Y-m-d', strtotime($toDate));

            // Validate date range
            $dateFrom = new \DateTime($fromDate);
            $dateTo = new \DateTime($toDate);
            $interval = $dateFrom->diff($dateTo);

            if ($interval->days > 60) {
                throw new \Exception('Date range cannot exceed 60 days');
            }

            if ($dateTo < $dateFrom) {
                throw new \Exception('To date must be after From date');
            }

            $this->log("Starting bulk import from {$fromDate} to {$toDate} for location {$location}");

            // Get case list from Evolution Portal
            $cases = $this->evolutionClient->getCaseList($fromDate, $toDate);

            $results = [
                'total' => count($cases),
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [],
                'details' => []
            ];

            $userId = Session::getUserId();

            // Process each case
            foreach ($cases as $caseData) {
                try {
                    $externalCaseNo = $caseData['external_case_no'];

                    // Check if case already exists
                    $existingCase = $this->db->queryOne(
                        'SELECT id, version FROM cases WHERE external_case_no = :case_no',
                        [':case_no' => $externalCaseNo]
                    );

                    if ($existingCase) {
                        if (!$overwriteExisting) {
                            $results['skipped']++;
                            $results['details'][] = [
                                'case_no' => $externalCaseNo,
                                'status' => 'skipped',
                                'reason' => 'Case already exists'
                            ];
                            continue;
                        }

                        // Update existing case
                        $updateData = [
                            'patient_name' => $caseData['patient_name'] ?? null,
                            'lab_name' => $caseData['lab_name'] ?? null,
                            'due_date' => $caseData['due_date'] ?? null,
                            'status' => $caseData['status'] ?? 'new',
                            'location' => $location,
                            'source' => 'evolution_web_portal',
                            'last_imported_at' => date('Y-m-d H:i:s')
                        ];

                        $this->caseService->updateCase(
                            $existingCase['id'],
                            $updateData,
                            (int) $existingCase['version'],
                            $userId
                        );

                        $results['updated']++;
                        $results['details'][] = [
                            'case_no' => $externalCaseNo,
                            'status' => 'updated',
                            'id' => $existingCase['id']
                        ];
                    } else {
                        // Create new case
                        $newCaseData = [
                            'external_case_no' => $externalCaseNo,
                            'patient_name' => $caseData['patient_name'] ?? null,
                            'lab_name' => $caseData['lab_name'] ?? null,
                            'due_date' => $caseData['due_date'] ?? null,
                            'status' => $caseData['status'] ?? 'new',
                            'priority' => 'normal',
                            'location' => $location,
                            'source' => 'evolution_web_portal',
                            'last_imported_at' => date('Y-m-d H:i:s')
                        ];

                        $case = $this->caseService->createCase($newCaseData, $userId);

                        $results['imported']++;
                        $results['details'][] = [
                            'case_no' => $externalCaseNo,
                            'status' => 'imported',
                            'id' => $case['id']
                        ];
                    }

                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'case_no' => $externalCaseNo ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                    $this->log("Error importing case {$externalCaseNo}: " . $e->getMessage(), 'error');
                }
            }

            $this->log("Import completed: {$results['imported']} imported, {$results['updated']} updated, {$results['skipped']} skipped, " . count($results['errors']) . " errors");

            // Check if AJAX request
            if ($this->isApiRequest()) {
                $this->success($results, 'Import completed successfully');
            }

            Session::flash('success', "Import completed: {$results['imported']} imported, {$results['updated']} updated, {$results['skipped']} skipped");
            $this->redirect('/imports/results?' . http_build_query(['results' => json_encode($results)]));

        } catch (\Exception $e) {
            $this->log('Error executing import: ' . $e->getMessage(), 'error');

            if ($this->isApiRequest()) {
                $this->error($e->getMessage(), 500);
            }

            Session::flash('error', $e->getMessage());
            $this->redirect('/imports');
        }
    }

    /**
     * Show import results
     */
    public function results(): void
    {
        $this->requireAnyRole(['super_admin', 'admin', 'manager']);

        $resultsJson = $_GET['results'] ?? '{}';
        $results = json_decode($resultsJson, true) ?: [];

        $this->view('imports/results', [
            'title' => 'Import Results - CREODENT Work Manager',
            'results' => $results
        ]);
    }

    /**
     * API: Get import history
     */
    public function apiHistory(): void
    {
        $this->requireAnyRole(['super_admin', 'admin', 'manager']);

        try {
            $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20;
            $offset = ($page - 1) * $perPage;

            // Get import history from audit logs
            $history = $this->db->query('
                SELECT
                    u.username,
                    u.name as user_name,
                    COUNT(*) as cases_count,
                    DATE(c.last_imported_at) as import_date,
                    c.location,
                    c.source
                FROM cases c
                LEFT JOIN users u ON c.created_by = u.id
                WHERE c.source = "evolution_web_portal"
                AND c.last_imported_at IS NOT NULL
                GROUP BY DATE(c.last_imported_at), c.location, c.created_by
                ORDER BY c.last_imported_at DESC
                LIMIT :limit OFFSET :offset
            ', [
                ':limit' => $perPage,
                ':offset' => $offset
            ]);

            $total = $this->db->queryOne('
                SELECT COUNT(DISTINCT DATE(last_imported_at)) as count
                FROM cases
                WHERE source = "evolution_web_portal"
                AND last_imported_at IS NOT NULL
            ');

            $this->success([
                'history' => $history,
                'total' => (int) $total['count'],
                'page' => $page,
                'per_page' => $perPage
            ]);

        } catch (\Exception $e) {
            $this->log('Error fetching import history: ' . $e->getMessage(), 'error');
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Test Evolution Portal connection
     */
    public function testConnection(): void
    {
        $this->requireAnyRole(['super_admin', 'admin']);
        $this->validateCsrf();

        try {
            $location = $this->input('location') ?? 'HV';

            // Test XML API connection
            $apiResult = $this->evolutionClient->testConnection();

            // Test SQL Server connection
            $sqlResult = ['success' => false, 'message' => 'Not tested'];
            try {
                // Try a simple query to test connection
                $testCase = $this->evolutionClient->getCaseFromSQLServer('TEST-00000', $location);
                $sqlResult = [
                    'success' => true,
                    'message' => 'SQL Server connection successful (or case not found, which means connection works)'
                ];
            } catch (\Exception $e) {
                $sqlResult = [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }

            $results = [
                'xml_api' => $apiResult,
                'sql_server' => $sqlResult,
                'location' => $location
            ];

            if ($this->isApiRequest()) {
                $this->success($results, 'Connection test completed');
            }

            Session::flash(
                $apiResult['success'] || $sqlResult['success'] ? 'success' : 'error',
                'Test completed. Check results below.'
            );
            $this->redirect('/imports?' . http_build_query(['test_results' => json_encode($results)]));

        } catch (\Exception $e) {
            $this->log('Error testing connection: ' . $e->getMessage(), 'error');

            if ($this->isApiRequest()) {
                $this->error($e->getMessage(), 500);
            }

            Session::flash('error', $e->getMessage());
            $this->redirect('/imports');
        }
    }
}
