<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Repositories\CaseRepository;
use App\Services\CaseService;

/**
 * Case Controller
 *
 * Handles all case-related operations
 */
class CaseController extends Controller
{
    private CaseRepository $caseRepo;
    private CaseService $caseService;

    public function __construct()
    {
        parent::__construct();
        $this->caseRepo = new CaseRepository();
        $this->caseService = new CaseService();
    }

    /**
     * Display list of cases
     */
    public function index(): void
    {
        $this->requireAuth();

        // Get filters from query string
        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'location' => $_GET['location'] ?? '',
            'source' => $_GET['source'] ?? '',
            'due_date_from' => $_GET['due_date_from'] ?? '',
            'due_date_to' => $_GET['due_date_to'] ?? '',
            'include_archived' => isset($_GET['include_archived'])
        ];

        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 50;

        $result = $this->caseRepo->getAll($page, $perPage, $filters);

        $this->view('cases/index', [
            'title' => 'Cases - CREODENT Work Manager',
            'cases' => $result['cases'],
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
            'total_pages' => $result['total_pages'],
            'filters' => $filters
        ]);
    }

    /**
     * Show create case form
     */
    public function create(): void
    {
        $this->requireAnyRole(['super_admin', 'admin', 'manager']);

        $this->view('cases/create', [
            'title' => 'Create New Case - CREODENT Work Manager'
        ]);
    }

    /**
     * Store new case
     */
    public function store(): void
    {
        $this->requireAnyRole(['super_admin', 'admin', 'manager']);
        $this->validateCsrf();

        try {
            $data = $this->input();

            // Create case
            $case = $this->caseService->createCase($data, Session::getUserId());

            // Check if AJAX request
            if ($this->isApiRequest()) {
                $this->success($case, 'Case created successfully');
            }

            Session::flash('success', 'Case created successfully');
            $this->redirect('/cases/' . $case['id']);

        } catch (\Exception $e) {
            $this->log('Error creating case: ' . $e->getMessage(), 'error');

            if ($this->isApiRequest()) {
                $this->error($e->getMessage(), 400);
            }

            Session::flash('error', $e->getMessage());
            $this->redirect('/cases/create');
        }
    }

    /**
     * Show case details
     */
    public function show(string $id): void
    {
        $this->requireAuth();

        $caseId = (int) $id;
        $case = $this->caseService->getCaseWithItems($caseId);

        if (!$case) {
            $this->notFound('Case not found');
        }

        // Check permissions - workers can only see their assigned cases
        if (Session::hasRole('worker')) {
            $userId = Session::getUserId();
            $hasAccess = false;

            foreach ($case['items'] as $item) {
                if ((int) $item['assigned_to_user_id'] === $userId) {
                    $hasAccess = true;
                    break;
                }
            }

            if (!$hasAccess) {
                $this->forbidden('You do not have access to this case');
            }
        }

        // Get audit logs
        $auditLogs = $this->caseService->getAuditLogs($caseId, 50);

        $this->view('cases/show', [
            'title' => 'Case ' . htmlspecialchars($case['external_case_no']) . ' - CREODENT Work Manager',
            'case' => $case,
            'audit_logs' => $auditLogs
        ]);
    }

    /**
     * Update case
     */
    public function update(string $id): void
    {
        $this->requireAnyRole(['super_admin', 'admin', 'manager']);
        $this->validateCsrf();

        try {
            $caseId = (int) $id;
            $data = $this->input();

            // Get expected version
            $expectedVersion = (int) ($data['version'] ?? 0);
            unset($data['version']);

            // Remove fields that shouldn't be updated directly
            unset($data['id'], $data['created_at'], $data['created_by']);

            $case = $this->caseService->updateCase($caseId, $data, $expectedVersion, Session::getUserId());

            if ($this->isApiRequest()) {
                $this->success($case, 'Case updated successfully');
            }

            Session::flash('success', 'Case updated successfully');
            $this->redirect('/cases/' . $caseId);

        } catch (\Exception $e) {
            $this->log('Error updating case: ' . $e->getMessage(), 'error');

            if ($this->isApiRequest()) {
                $this->error($e->getMessage(), $e->getMessage() === 'Concurrent modification detected. Please refresh and try again.' ? 409 : 400);
            }

            Session::flash('error', $e->getMessage());
            $this->redirect('/cases/' . $id);
        }
    }

    /**
     * Delete case
     */
    public function delete(string $id): void
    {
        $this->requireAnyRole(['super_admin', 'admin']);
        $this->validateCsrf();

        try {
            $caseId = (int) $id;
            $this->caseService->deleteCase($caseId, Session::getUserId());

            if ($this->isApiRequest()) {
                $this->success(null, 'Case deleted successfully');
            }

            Session::flash('success', 'Case deleted successfully');
            $this->redirect('/cases');

        } catch (\Exception $e) {
            $this->log('Error deleting case: ' . $e->getMessage(), 'error');

            if ($this->isApiRequest()) {
                $this->error($e->getMessage(), 400);
            }

            Session::flash('error', $e->getMessage());
            $this->redirect('/cases/' . $id);
        }
    }

    /**
     * Archive case
     */
    public function archive(string $id): void
    {
        $this->requireAnyRole(['super_admin', 'admin', 'manager']);
        $this->validateCsrf();

        try {
            $caseId = (int) $id;
            $expectedVersion = (int) ($this->input('version') ?? 0);

            $case = $this->caseService->archiveCase($caseId, $expectedVersion, Session::getUserId());

            if ($this->isApiRequest()) {
                $this->success($case, 'Case archived successfully');
            }

            Session::flash('success', 'Case archived successfully');
            $this->redirect('/cases');

        } catch (\Exception $e) {
            $this->log('Error archiving case: ' . $e->getMessage(), 'error');

            if ($this->isApiRequest()) {
                $this->error($e->getMessage(), $e->getMessage() === 'Concurrent modification detected. Please refresh and try again.' ? 409 : 400);
            }

            Session::flash('error', $e->getMessage());
            $this->redirect('/cases/' . $id);
        }
    }

    /**
     * Assign case item to user
     */
    public function assign(string $id): void
    {
        $this->requireAnyRole(['super_admin', 'admin', 'manager']);
        $this->validateCsrf();

        try {
            $caseId = (int) $id;
            $itemId = (int) $this->input('item_id');
            $userId = (int) $this->input('user_id');

            $item = $this->caseService->assignCaseItem($caseId, $itemId, $userId, Session::getUserId());

            if ($this->isApiRequest()) {
                $this->success($item, 'Case item assigned successfully');
            }

            Session::flash('success', 'Case item assigned successfully');
            $this->redirect('/cases/' . $caseId);

        } catch (\Exception $e) {
            $this->log('Error assigning case: ' . $e->getMessage(), 'error');

            if ($this->isApiRequest()) {
                $this->error($e->getMessage(), 400);
            }

            Session::flash('error', $e->getMessage());
            $this->redirect('/cases/' . $id);
        }
    }

    /**
     * Add item to case
     */
    public function addItem(string $id): void
    {
        $this->requireAnyRole(['super_admin', 'admin', 'manager']);
        $this->validateCsrf();

        try {
            $caseId = (int) $id;
            $itemData = $this->input();

            $item = $this->caseService->addCaseItem($caseId, $itemData, Session::getUserId());

            if ($this->isApiRequest()) {
                $this->success($item, 'Item added successfully');
            }

            Session::flash('success', 'Item added successfully');
            $this->redirect('/cases/' . $caseId);

        } catch (\Exception $e) {
            $this->log('Error adding item: ' . $e->getMessage(), 'error');

            if ($this->isApiRequest()) {
                $this->error($e->getMessage(), 400);
            }

            Session::flash('error', $e->getMessage());
            $this->redirect('/cases/' . $id);
        }
    }

    /**
     * Update case item
     */
    public function updateItem(string $caseId, string $itemId): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        try {
            $caseIdInt = (int) $caseId;
            $itemIdInt = (int) $itemId;

            $status = $this->input('status');
            $expectedVersion = (int) ($this->input('version') ?? 0);

            $item = $this->caseService->updateItemStatus($caseIdInt, $itemIdInt, $status, $expectedVersion, Session::getUserId());

            if ($this->isApiRequest()) {
                $this->success($item, 'Item updated successfully');
            }

            Session::flash('success', 'Item updated successfully');
            $this->redirect('/cases/' . $caseId);

        } catch (\Exception $e) {
            $this->log('Error updating item: ' . $e->getMessage(), 'error');

            if ($this->isApiRequest()) {
                $this->error($e->getMessage(), $e->getMessage() === 'Concurrent modification detected. Please refresh and try again.' ? 409 : 400);
            }

            Session::flash('error', $e->getMessage());
            $this->redirect('/cases/' . $caseId);
        }
    }

    /**
     * Delete case item
     */
    public function deleteItem(string $caseId, string $itemId): void
    {
        $this->requireAnyRole(['super_admin', 'admin', 'manager']);
        $this->validateCsrf();

        try {
            $caseIdInt = (int) $caseId;
            $itemIdInt = (int) $itemId;

            $this->caseService->deleteCaseItem($caseIdInt, $itemIdInt, Session::getUserId());

            if ($this->isApiRequest()) {
                $this->success(null, 'Item deleted successfully');
            }

            Session::flash('success', 'Item deleted successfully');
            $this->redirect('/cases/' . $caseId);

        } catch (\Exception $e) {
            $this->log('Error deleting item: ' . $e->getMessage(), 'error');

            if ($this->isApiRequest()) {
                $this->error($e->getMessage(), 400);
            }

            Session::flash('error', $e->getMessage());
            $this->redirect('/cases/' . $caseId);
        }
    }

    /**
     * API: Get cases list (for AJAX)
     */
    public function apiList(): void
    {
        $this->requireAuth();

        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'location' => $_GET['location'] ?? '',
            'source' => $_GET['source'] ?? '',
            'due_date_from' => $_GET['due_date_from'] ?? '',
            'due_date_to' => $_GET['due_date_to'] ?? '',
            'include_archived' => isset($_GET['include_archived'])
        ];

        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 50;

        $result = $this->caseRepo->getAll($page, $perPage, $filters);

        $this->success($result);
    }

    /**
     * API: Fetch case from Evolution Portal
     */
    public function fetchEvolutionCase(): void
    {
        $this->requireAnyRole(['super_admin', 'admin', 'manager']);

        // This will be implemented in Phase 3 with Evolution integration
        $this->error('Evolution Portal integration not yet implemented', 501);
    }
}
