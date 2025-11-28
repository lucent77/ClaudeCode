<?php
/**
 * Admin Export Controller
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class ExportController extends BaseController
{
    public function csv(): void
    {
        $this->requireRole(['moderator', 'exec']);

        if (!$this->db) {
            $this->flash('error', 'Database unavailable.');
            $this->redirect('/admin');
        }

        // Get all approved posts
        $stmt = $this->db->query("
            SELECT
                p.public_id,
                p.category,
                p.title,
                p.body,
                p.status,
                p.label,
                p.department_hint,
                p.created_at,
                p.updated_at,
                u.name as owner_name,
                (SELECT COUNT(*) FROM post_votes WHERE post_id = p.id) as vote_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
            FROM posts p
            LEFT JOIN users u ON p.owner_id = u.id
            WHERE p.status NOT IN ('queued', 'rejected')
            ORDER BY p.created_at DESC
        ");
        $posts = $stmt->fetchAll();

        // Set headers for CSV download
        $filename = 'feedback_export_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        // Open output stream
        $output = fopen('php://output', 'w');

        // Add BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // CSV headers
        fputcsv($output, [
            'ID',
            'Category',
            'Title',
            'Description',
            'Status',
            'Label',
            'Department',
            'Owner',
            'Votes',
            'Comments',
            'Created',
            'Updated',
        ]);

        // Write data rows
        foreach ($posts as $post) {
            fputcsv($output, [
                $post['public_id'],
                $post['category'],
                $post['title'],
                $post['body'],
                $post['status'],
                $post['label'] ?? '',
                $post['department_hint'] ?? '',
                $post['owner_name'] ?? 'Unassigned',
                $post['vote_count'],
                $post['comment_count'],
                $post['created_at'],
                $post['updated_at'],
            ]);
        }

        fclose($output);
        exit;
    }
}
