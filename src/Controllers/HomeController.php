<?php
/**
 * Home Controller
 */

declare(strict_types=1);

namespace App\Controllers;

class HomeController extends BaseController
{
    public function index(): void
    {
        // Get statistics if database is available
        $stats = [
            'total_posts' => 0,
            'resolved_posts' => 0,
            'open_posts' => 0,
            'recent_posts' => [],
        ];

        if ($this->db) {
            try {
                // Total approved posts
                $stmt = $this->db->query("SELECT COUNT(*) FROM posts WHERE status NOT IN ('queued', 'rejected')");
                $stats['total_posts'] = (int) $stmt->fetchColumn();

                // Resolved posts
                $stmt = $this->db->query("SELECT COUNT(*) FROM posts WHERE status = 'resolved'");
                $stats['resolved_posts'] = (int) $stmt->fetchColumn();

                // Open posts
                $stmt = $this->db->query("SELECT COUNT(*) FROM posts WHERE status IN ('open', 'in_progress')");
                $stats['open_posts'] = (int) $stmt->fetchColumn();

                // Recent posts
                $stmt = $this->db->query("
                    SELECT public_id, title, category, status, created_at,
                           (SELECT COUNT(*) FROM post_votes WHERE post_id = posts.id) as vote_count
                    FROM posts
                    WHERE status NOT IN ('queued', 'rejected')
                    ORDER BY created_at DESC
                    LIMIT 5
                ");
                $stats['recent_posts'] = $stmt->fetchAll();
            } catch (\PDOException $e) {
                // Database not ready yet, use defaults
            }
        }

        $this->render('home', [
            'title' => 'Creodent Voice - Anonymous Feedback',
            'stats' => $stats,
        ]);
    }
}
