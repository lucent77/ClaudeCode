<?php
/**
 * Admin Queue Controller - Moderation Queue
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class QueueController extends BaseController
{
    public function index(): void
    {
        $this->requireRole(['moderator', 'owner', 'exec']);

        if (!$this->db) {
            $this->flash('error', 'Database unavailable.');
            $this->redirect('/admin');
        }

        $status = $this->query('status', 'queued');
        $page = max(1, (int) $this->query('page', '1'));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        // Get posts in queue
        $validStatuses = ['queued', 'open', 'in_progress', 'resolved', 'rejected', 'duplicate'];
        if (!in_array($status, $validStatuses, true)) {
            $status = 'queued';
        }

        $stmt = $this->db->prepare("
            SELECT p.*, u.name as owner_name,
                   (SELECT COUNT(*) FROM content_flags WHERE post_id = p.id) as flag_count,
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
                   (SELECT COUNT(*) FROM post_votes WHERE post_id = p.id) as vote_count
            FROM posts p
            LEFT JOIN users u ON p.owner_id = u.id
            WHERE p.status = ?
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$status, $perPage, $offset]);
        $posts = $stmt->fetchAll();

        // Get flags for these posts
        $postIds = array_column($posts, 'id');
        $flags = [];
        if (!empty($postIds)) {
            $placeholders = str_repeat('?,', count($postIds) - 1) . '?';
            $stmt = $this->db->prepare("
                SELECT * FROM content_flags
                WHERE post_id IN ({$placeholders})
            ");
            $stmt->execute($postIds);
            foreach ($stmt->fetchAll() as $flag) {
                $flags[$flag['post_id']][] = $flag;
            }
        }

        // Get total count
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM posts WHERE status = ?");
        $stmt->execute([$status]);
        $totalPosts = (int) $stmt->fetchColumn();
        $totalPages = ceil($totalPosts / $perPage);

        // Get all users for assignment dropdown
        $stmt = $this->db->query("SELECT id, name, role FROM users WHERE status = 'active' ORDER BY name");
        $users = $stmt->fetchAll();

        // Get status counts for tabs
        $statusCounts = [];
        $stmt = $this->db->query("SELECT status, COUNT(*) as count FROM posts GROUP BY status");
        foreach ($stmt->fetchAll() as $row) {
            $statusCounts[$row['status']] = (int) $row['count'];
        }

        $this->render('admin/queue', [
            'title' => 'Moderation Queue - Creodent Voice',
            'posts' => $posts,
            'flags' => $flags,
            'users' => $users,
            'currentStatus' => $status,
            'statusCounts' => $statusCounts,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalPosts' => $totalPosts,
            'csrf' => $this->csrfToken(),
        ]);
    }

    public function moderate(string $id): void
    {
        $this->requireRole(['moderator']);

        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        if (!$this->db) {
            $this->json(['error' => 'Database unavailable'], 500);
        }

        $postId = (int) $id;
        $action = $this->input('action', '');
        $notes = trim($this->input('notes', ''));

        // Validate action
        $validActions = ['approve', 'reject', 'ask_revision', 'merge', 'unmerge', 'redact'];
        if (!in_array($action, $validActions, true)) {
            $this->json(['error' => 'Invalid action'], 400);
        }

        // Get post
        $stmt = $this->db->prepare("SELECT * FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();

        if (!$post) {
            $this->json(['error' => 'Post not found'], 404);
        }

        $previousStatus = $post['status'];
        $newStatus = $previousStatus;
        $user = $this->currentUser();

        // Process action
        switch ($action) {
            case 'approve':
                $newStatus = 'open';
                break;

            case 'reject':
                $newStatus = 'rejected';
                break;

            case 'ask_revision':
                // Keep queued status but log the action
                $newStatus = 'queued';
                break;

            case 'merge':
                $mergeIntoId = (int) $this->input('merge_into', 0);
                if ($mergeIntoId) {
                    $newStatus = 'duplicate';
                    $stmt = $this->db->prepare("UPDATE posts SET merged_into_post_id = ? WHERE id = ?");
                    $stmt->execute([$mergeIntoId, $postId]);
                }
                break;

            case 'unmerge':
                $newStatus = 'open';
                $stmt = $this->db->prepare("UPDATE posts SET merged_into_post_id = NULL WHERE id = ?");
                $stmt->execute([$postId]);
                break;

            case 'redact':
                // Redact sensitive content
                $redactedBody = $this->input('redacted_body', $post['body']);
                $stmt = $this->db->prepare("UPDATE posts SET body = ? WHERE id = ?");
                $stmt->execute([$redactedBody, $postId]);
                break;
        }

        // Update post status
        if ($newStatus !== $previousStatus) {
            $stmt = $this->db->prepare("UPDATE posts SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $postId]);
        }

        // Log moderation action
        $stmt = $this->db->prepare("
            INSERT INTO moderation_audit (post_id, moderator_id, action, notes, previous_status, new_status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $postId,
            $user['id'],
            $action,
            $notes ?: null,
            $previousStatus,
            $newStatus,
        ]);

        $this->flash('success', 'Post moderated successfully.');

        // Check if this is an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->json(['success' => true, 'new_status' => $newStatus]);
        }

        $this->redirect('/admin/queue');
    }
}
