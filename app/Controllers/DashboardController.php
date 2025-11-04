<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;

/**
 * Dashboard Controller
 *
 * Displays main dashboard with statistics and overview
 */
class DashboardController extends Controller
{
    /**
     * Show main dashboard
     */
    public function index(): void
    {
        $this->requireAuth();

        $user = Session::getUser();

        // Get basic statistics
        $stats = $this->getStatistics();

        $this->view('dashboard', [
            'title' => 'Dashboard - CREODENT Work Manager',
            'stats' => $stats,
            'user' => $user
        ]);
    }

    /**
     * Get dashboard statistics
     */
    private function getStatistics(): array
    {
        try {
            // Total cases
            $totalCases = $this->db->queryOne('SELECT COUNT(*) as count FROM cases WHERE status != "archived"');

            // Cases by status
            $casesByStatus = $this->db->query('
                SELECT status, COUNT(*) as count
                FROM cases
                WHERE status != "archived"
                GROUP BY status
            ');

            // Recent cases
            $recentCases = $this->db->query('
                SELECT id, external_case_no, patient_name, lab_name, status, due_date, created_at
                FROM cases
                WHERE status != "archived"
                ORDER BY created_at DESC
                LIMIT 10
            ');

            // Overdue cases
            $overdueCases = $this->db->query('
                SELECT COUNT(*) as count
                FROM cases
                WHERE due_date < CURDATE()
                AND status NOT IN ("done", "canceled", "archived")
            ');

            // My assigned cases (for workers)
            $myAssignedCases = null;
            if (Session::hasRole('worker')) {
                $userId = Session::getUserId();
                $myAssignedCases = $this->db->query('
                    SELECT ci.*, c.external_case_no, c.patient_name, c.due_date
                    FROM case_items ci
                    JOIN cases c ON ci.case_id = c.id
                    WHERE ci.assigned_to_user_id = :user_id
                    AND ci.status NOT IN ("done", "rejected")
                    ORDER BY c.due_date ASC
                    LIMIT 10
                ', [':user_id' => $userId]);
            }

            return [
                'total_cases' => (int) $totalCases['count'],
                'cases_by_status' => $casesByStatus,
                'recent_cases' => $recentCases,
                'overdue_cases' => (int) $overdueCases[0]['count'],
                'my_assigned_cases' => $myAssignedCases
            ];
        } catch (\Exception $e) {
            $this->log('Error fetching dashboard statistics: ' . $e->getMessage(), 'error');
            return [
                'total_cases' => 0,
                'cases_by_status' => [],
                'recent_cases' => [],
                'overdue_cases' => 0,
                'my_assigned_cases' => null
            ];
        }
    }

    /**
     * API endpoint for dashboard stats (for AJAX refresh)
     */
    public function apiStats(): void
    {
        $this->requireAuth();

        $stats = $this->getStatistics();
        $this->success($stats);
    }
}
