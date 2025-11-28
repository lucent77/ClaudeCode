<?php
/**
 * Admin Dashboard Controller
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class DashboardController extends BaseController
{
    public function index(): void
    {
        $this->requireRole(['moderator', 'owner', 'exec']);

        if (!$this->db) {
            $this->flash('error', 'Database unavailable.');
            $this->redirect('/');
        }

        // Get counts by status
        $statusCounts = [];
        $stmt = $this->db->query("
            SELECT status, COUNT(*) as count
            FROM posts
            GROUP BY status
        ");
        foreach ($stmt->fetchAll() as $row) {
            $statusCounts[$row['status']] = (int) $row['count'];
        }

        // Get counts by category
        $categoryCounts = [];
        $stmt = $this->db->query("
            SELECT category, COUNT(*) as count
            FROM posts
            WHERE status NOT IN ('queued', 'rejected')
            GROUP BY category
        ");
        foreach ($stmt->fetchAll() as $row) {
            $categoryCounts[$row['category']] = (int) $row['count'];
        }

        // Get aging buckets
        $agingBuckets = [
            '0-7' => 0,
            '8-14' => 0,
            '15-30' => 0,
            '31+' => 0,
        ];
        $stmt = $this->db->query("
            SELECT
                CASE
                    WHEN DATEDIFF(NOW(), created_at) <= 7 THEN '0-7'
                    WHEN DATEDIFF(NOW(), created_at) <= 14 THEN '8-14'
                    WHEN DATEDIFF(NOW(), created_at) <= 30 THEN '15-30'
                    ELSE '31+'
                END as bucket,
                COUNT(*) as count
            FROM posts
            WHERE status IN ('open', 'in_progress')
            GROUP BY bucket
        ");
        foreach ($stmt->fetchAll() as $row) {
            $agingBuckets[$row['bucket']] = (int) $row['count'];
        }

        // Get SLA breaches (no response in 5 days)
        $stmt = $this->db->query("
            SELECT COUNT(*) FROM posts
            WHERE status IN ('open', 'queued')
            AND created_at < DATE_SUB(NOW(), INTERVAL 5 DAY)
        ");
        $slaBreaches = (int) $stmt->fetchColumn();

        // Get owner workload
        $ownerWorkload = [];
        $stmt = $this->db->query("
            SELECT u.name, COUNT(p.id) as count
            FROM users u
            LEFT JOIN posts p ON u.id = p.owner_id AND p.status IN ('open', 'in_progress')
            WHERE u.role IN ('owner', 'moderator')
            GROUP BY u.id
            ORDER BY count DESC
            LIMIT 10
        ");
        $ownerWorkload = $stmt->fetchAll();

        // Get recent activity
        $recentActivity = [];
        $stmt = $this->db->query("
            SELECT ma.*, p.title as post_title, p.public_id, u.name as moderator_name
            FROM moderation_audit ma
            JOIN posts p ON ma.post_id = p.id
            JOIN users u ON ma.moderator_id = u.id
            ORDER BY ma.created_at DESC
            LIMIT 10
        ");
        $recentActivity = $stmt->fetchAll();

        // Get weekly trends
        $weeklyTrends = [];
        $stmt = $this->db->query("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM posts
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 4 WEEK)
            AND status NOT IN ('rejected')
            GROUP BY DATE(created_at)
            ORDER BY date
        ");
        $weeklyTrends = $stmt->fetchAll();

        $this->render('admin/dashboard', [
            'title' => 'Admin Dashboard - Creodent Voice',
            'statusCounts' => $statusCounts,
            'categoryCounts' => $categoryCounts,
            'agingBuckets' => $agingBuckets,
            'slaBreaches' => $slaBreaches,
            'ownerWorkload' => $ownerWorkload,
            'recentActivity' => $recentActivity,
            'weeklyTrends' => $weeklyTrends,
        ]);
    }
}
