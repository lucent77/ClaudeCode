<?php
/**
 * Dashboard Controller
 *
 * Main dashboard and home page
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Services\CaseService;
use Exception;

class DashboardController extends Controller
{
    private CaseService $caseService;

    public function __construct()
    {
        parent::__construct();
        $this->caseService = new CaseService();
    }

    /**
     * Show dashboard
     */
    public function index(): void
    {
        $this->requireAuth();

        $user = $this->getUser();

        $this->view('dashboard', [
            'title' => 'Dashboard - CREODENT Work Manager',
            'user' => $user,
        ]);
    }

    /**
     * Get dashboard statistics (API)
     */
    public function getStats(): void
    {
        $this->requireAuth();

        try {
            $user = $this->getUser();

            // Get department-specific stats if user is not admin
            $departmentId = null;
            if (!in_array($user['role'], ['admin', 'super_admin'])) {
                $departmentId = $user['department_id'];
            }

            $stats = $this->caseService->getDashboardStats($departmentId);

            $this->success($stats);

        } catch (Exception $e) {
            $this->error($e->getMessage(), null, 500);
        }
    }
}
