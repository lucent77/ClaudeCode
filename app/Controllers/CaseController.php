<?php
/**
 * Case Controller
 *
 * Handles case management operations
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Services\CaseService;
use App\Repositories\CaseRepository;
use Exception;

class CaseController extends Controller
{
    private CaseService $caseService;
    private CaseRepository $caseRepo;

    public function __construct()
    {
        parent::__construct();
        $this->caseService = new CaseService();
        $this->caseRepo = new CaseRepository();
    }

    /**
     * Show cases list page
     */
    public function index(): void
    {
        $this->requireAuth();

        $this->view('cases.index', [
            'title' => 'Cases - CREODENT Work Manager',
            'user' => $this->getUser(),
        ]);
    }

    /**
     * Get cases list (API)
     */
    public function getCases(): void
    {
        $this->requireAuth();

        try {
            $page = (int)($this->input('page') ?? 1);
            $perPage = (int)($this->input('per_page') ?? 50);

            $filters = [
                'search' => $this->input('search'),
                'status' => $this->input('status'),
                'location' => $this->input('location'),
                'source' => $this->input('source'),
                'due_date_from' => $this->input('due_date_from'),
                'due_date_to' => $this->input('due_date_to'),
                'include_archived' => $this->input('include_archived') === 'true',
            ];

            $result = $this->caseService->getCases($filters, $page, $perPage);

            $this->success($result);

        } catch (Exception $e) {
            $this->error($e->getMessage(), null, 500);
        }
    }

    /**
     * Show single case detail page
     */
    public function show(int $id): void
    {
        $this->requireAuth();

        try {
            $case = $this->caseService->getCaseDetails($id);

            $this->view('cases.show', [
                'title' => "Case {$case['external_case_no']} - CREODENT Work Manager",
                'case' => $case,
                'user' => $this->getUser(),
            ]);

        } catch (Exception $e) {
            $this->view('errors.404', [
                'title' => 'Case Not Found',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get single case (API)
     */
    public function getCase(int $id): void
    {
        $this->requireAuth();

        try {
            $case = $this->caseService->getCaseDetails($id);
            $this->success($case);

        } catch (Exception $e) {
            $this->error($e->getMessage(), null, 404);
        }
    }

    /**
     * Create new case
     */
    public function create(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'super_admin', 'manager']);

        try {
            $data = $this->getAllInput();

            // Validate
            $errors = $this->validate($data, [
                'external_case_no' => 'required',
                'lab_name' => 'required',
            ]);

            if (!empty($errors)) {
                $this->error('Validation failed', $errors, 422);
                return;
            }

            $case = $this->caseService->createCase($data, $this->getUserId());

            $this->success($case, 'Case created successfully', 201);

        } catch (Exception $e) {
            $this->error($e->getMessage(), null, 400);
        }
    }

    /**
     * Update case
     */
    public function update(int $id): void
    {
        $this->requireAuth();

        try {
            $data = $this->getAllInput();
            $version = (int)$this->input('version');

            if (!$version) {
                $this->error('Version is required for update', null, 400);
                return;
            }

            $case = $this->caseService->updateCase($id, $data, $version, $this->getUserId());

            $this->success($case, 'Case updated successfully');

        } catch (Exception $e) {
            // Check if it's a concurrent modification error
            if (strpos($e->getMessage(), 'Concurrent modification') !== false) {
                $this->error($e->getMessage(), null, 409); // 409 Conflict
            } else {
                $this->error($e->getMessage(), null, 400);
            }
        }
    }

    /**
     * Assign case item to user
     */
    public function assignItem(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'super_admin', 'manager']);

        try {
            $itemId = (int)$this->input('item_id');
            $assignedToUserId = (int)$this->input('assigned_to_user_id');

            if (!$itemId || !$assignedToUserId) {
                $this->error('Item ID and user ID are required', null, 400);
                return;
            }

            $item = $this->caseService->assignCaseItem($itemId, $assignedToUserId, $this->getUserId());

            $this->success($item, 'Case item assigned successfully');

        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Concurrent modification') !== false) {
                $this->error($e->getMessage(), null, 409);
            } else {
                $this->error($e->getMessage(), null, 400);
            }
        }
    }

    /**
     * Update case item status
     */
    public function updateItemStatus(): void
    {
        $this->requireAuth();

        try {
            $itemId = (int)$this->input('item_id');
            $status = $this->input('status');
            $version = (int)$this->input('version');

            if (!$itemId || !$status || !$version) {
                $this->error('Item ID, status, and version are required', null, 400);
                return;
            }

            // Validate status
            $validStatuses = ['pending', 'assigned', 'working', 'done', 'remake', 'rejected'];
            if (!in_array($status, $validStatuses)) {
                $this->error('Invalid status', null, 400);
                return;
            }

            $item = $this->caseService->updateItemStatus($itemId, $status, $version, $this->getUserId());

            $this->success($item, 'Item status updated successfully');

        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Concurrent modification') !== false) {
                $this->error($e->getMessage(), null, 409);
            } else {
                $this->error($e->getMessage(), null, 400);
            }
        }
    }

    /**
     * Get case statistics
     */
    public function getStatistics(): void
    {
        $this->requireAuth();

        try {
            $stats = $this->caseRepo->getStatistics();
            $this->success($stats);

        } catch (Exception $e) {
            $this->error($e->getMessage(), null, 500);
        }
    }

    /**
     * Search cases
     */
    public function search(): void
    {
        $this->requireAuth();

        try {
            $query = $this->input('q');

            if (!$query) {
                $this->error('Search query is required', null, 400);
                return;
            }

            $results = $this->caseRepo->search($query);

            $this->success($results);

        } catch (Exception $e) {
            $this->error($e->getMessage(), null, 500);
        }
    }

    /**
     * Get overdue cases
     */
    public function getOverdue(): void
    {
        $this->requireAuth();

        try {
            $cases = $this->caseRepo->getOverdue();
            $this->success($cases);

        } catch (Exception $e) {
            $this->error($e->getMessage(), null, 500);
        }
    }
}
