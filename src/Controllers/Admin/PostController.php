<?php
/**
 * Admin Post Controller
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class PostController extends BaseController
{
    public function show(string $id): void
    {
        $this->requireRole(['moderator', 'owner', 'exec', 'viewer']);

        if (!$this->db) {
            $this->flash('error', 'Database unavailable.');
            $this->redirect('/admin');
        }

        $postId = (int) $id;

        // Get post with owner info
        $stmt = $this->db->prepare("
            SELECT p.*, u.name as owner_name
            FROM posts p
            LEFT JOIN users u ON p.owner_id = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();

        if (!$post) {
            $this->flash('error', 'Post not found.');
            $this->redirect('/admin/queue');
        }

        // Get content flags
        $stmt = $this->db->prepare("SELECT * FROM content_flags WHERE post_id = ?");
        $stmt->execute([$postId]);
        $flags = $stmt->fetchAll();

        // Get comments with user info
        $stmt = $this->db->prepare("
            SELECT c.*, u.name as user_name, u.role as user_role
            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$postId]);
        $comments = $stmt->fetchAll();

        // Get attachments
        $stmt = $this->db->prepare("SELECT * FROM attachments WHERE post_id = ?");
        $stmt->execute([$postId]);
        $attachments = $stmt->fetchAll();

        // Get moderation history
        $stmt = $this->db->prepare("
            SELECT ma.*, u.name as moderator_name
            FROM moderation_audit ma
            JOIN users u ON ma.moderator_id = u.id
            WHERE ma.post_id = ?
            ORDER BY ma.created_at DESC
        ");
        $stmt->execute([$postId]);
        $auditLog = $stmt->fetchAll();

        // Get vote count
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM post_votes WHERE post_id = ?");
        $stmt->execute([$postId]);
        $voteCount = (int) $stmt->fetchColumn();

        // Get all users for assignment
        $stmt = $this->db->query("SELECT id, name, role FROM users WHERE status = 'active' ORDER BY name");
        $users = $stmt->fetchAll();

        $this->render('admin/post', [
            'title' => 'Post Details - Creodent Voice',
            'post' => $post,
            'flags' => $flags,
            'comments' => $comments,
            'attachments' => $attachments,
            'auditLog' => $auditLog,
            'voteCount' => $voteCount,
            'users' => $users,
            'csrf' => $this->csrfToken(),
        ]);
    }

    public function assign(string $id): void
    {
        $this->requireRole(['moderator', 'owner']);

        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/admin/posts/' . $id);
        }

        if (!$this->db) {
            $this->flash('error', 'Database unavailable.');
            $this->redirect('/admin');
        }

        $postId = (int) $id;
        $ownerId = $this->input('owner_id') ? (int) $this->input('owner_id') : null;
        $dueDate = $this->input('due_date') ?: null;
        $label = $this->input('label') ?: null;

        // Validate label
        $validLabels = ['urgent', 'compliance', 'safety', 'culture', null];
        if (!in_array($label, $validLabels, true)) {
            $label = null;
        }

        // Update post
        $stmt = $this->db->prepare("
            UPDATE posts
            SET owner_id = ?, due_date = ?, label = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$ownerId, $dueDate, $label, $postId]);

        // Log assignment
        $user = $this->currentUser();
        $stmt = $this->db->prepare("
            INSERT INTO moderation_audit (post_id, moderator_id, action, notes)
            VALUES (?, ?, 'assign_owner', ?)
        ");
        $ownerName = 'Unassigned';
        if ($ownerId) {
            $stmt2 = $this->db->prepare("SELECT name FROM users WHERE id = ?");
            $stmt2->execute([$ownerId]);
            $ownerName = $stmt2->fetchColumn() ?: 'Unknown';
        }
        $stmt->execute([$postId, $user['id'], "Assigned to: {$ownerName}"]);

        $this->flash('success', 'Post assignment updated.');
        $this->redirect('/admin/posts/' . $id);
    }

    public function updateStatus(string $id): void
    {
        $this->requireRole(['moderator', 'owner']);

        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/admin/posts/' . $id);
        }

        if (!$this->db) {
            $this->flash('error', 'Database unavailable.');
            $this->redirect('/admin');
        }

        $postId = (int) $id;
        $newStatus = $this->input('status', '');

        // Validate status
        $validStatuses = ['open', 'in_progress', 'resolved', 'duplicate'];
        if (!in_array($newStatus, $validStatuses, true)) {
            $this->flash('error', 'Invalid status.');
            $this->redirect('/admin/posts/' . $id);
        }

        // Get current status
        $stmt = $this->db->prepare("SELECT status FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        $currentStatus = $stmt->fetchColumn();

        if (!$currentStatus) {
            $this->flash('error', 'Post not found.');
            $this->redirect('/admin/queue');
        }

        // Update status
        $stmt = $this->db->prepare("UPDATE posts SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $postId]);

        // Log status change
        $user = $this->currentUser();
        $stmt = $this->db->prepare("
            INSERT INTO moderation_audit (post_id, moderator_id, action, previous_status, new_status)
            VALUES (?, ?, 'change_status', ?, ?)
        ");
        $stmt->execute([$postId, $user['id'], $currentStatus, $newStatus]);

        $this->flash('success', 'Post status updated to ' . ucfirst(str_replace('_', ' ', $newStatus)) . '.');
        $this->redirect('/admin/posts/' . $id);
    }

    public function comment(string $id): void
    {
        $this->requireRole(['moderator', 'owner', 'exec']);

        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/admin/posts/' . $id);
        }

        if (!$this->db) {
            $this->flash('error', 'Database unavailable.');
            $this->redirect('/admin');
        }

        $postId = (int) $id;
        $body = trim($this->input('body', ''));
        $isOfficial = (bool) $this->input('is_official', false);

        if (empty($body)) {
            $this->flash('error', 'Comment cannot be empty.');
            $this->redirect('/admin/posts/' . $id);
        }

        if (strlen($body) > 5000) {
            $this->flash('error', 'Comment is too long (max 5000 characters).');
            $this->redirect('/admin/posts/' . $id);
        }

        $user = $this->currentUser();

        // Insert comment
        $stmt = $this->db->prepare("
            INSERT INTO comments (post_id, user_id, is_official, body)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$postId, $user['id'], $isOfficial ? 1 : 0, $body]);

        $this->flash('success', 'Comment added successfully.');
        $this->redirect('/admin/posts/' . $id);
    }
}
