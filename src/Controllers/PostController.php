<?php
/**
 * Post Controller - Public Post Listing and Details
 */

declare(strict_types=1);

namespace App\Controllers;

class PostController extends BaseController
{
    public function index(): void
    {
        if (!$this->db) {
            $this->render('posts/index', [
                'title' => 'Feedback - Creodent Voice',
                'posts' => [],
                'totalPosts' => 0,
            ]);
            return;
        }

        $sort = $this->query('sort', 'trending');
        $category = $this->query('category', '');
        $status = $this->query('status', '');
        $page = max(1, (int) $this->query('page', '1'));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        // Build query
        $where = ["p.status NOT IN ('queued', 'rejected', 'duplicate')"];
        $params = [];

        if ($category && in_array($category, ['process', 'tools', 'communication', 'leadership', 'benefits', 'other'], true)) {
            $where[] = "p.category = ?";
            $params[] = $category;
        }

        if ($status && in_array($status, ['open', 'in_progress', 'resolved'], true)) {
            $where[] = "p.status = ?";
            $params[] = $status;
        }

        $whereClause = implode(' AND ', $where);

        // Determine sort order
        $orderBy = match ($sort) {
            'new' => 'p.created_at DESC',
            'unresolved' => "CASE WHEN p.status IN ('open', 'in_progress') THEN 0 ELSE 1 END, p.created_at DESC",
            'trending' => 'vote_count DESC, p.created_at DESC',
            default => 'vote_count DESC, p.created_at DESC',
        };

        // Get posts
        $sql = "
            SELECT p.*,
                   u.name as owner_name,
                   (SELECT COUNT(*) FROM post_votes WHERE post_id = p.id) as vote_count,
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
            FROM posts p
            LEFT JOIN users u ON p.owner_id = u.id
            WHERE {$whereClause}
            ORDER BY {$orderBy}
            LIMIT ? OFFSET ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([...$params, $perPage, $offset]);
        $posts = $stmt->fetchAll();

        // Get total count
        $countSql = "SELECT COUNT(*) FROM posts p WHERE {$whereClause}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $totalPosts = (int) $stmt->fetchColumn();
        $totalPages = ceil($totalPosts / $perPage);

        // Get category counts
        $categoryCounts = [];
        $stmt = $this->db->query("
            SELECT category, COUNT(*) as count
            FROM posts
            WHERE status NOT IN ('queued', 'rejected', 'duplicate')
            GROUP BY category
        ");
        foreach ($stmt->fetchAll() as $row) {
            $categoryCounts[$row['category']] = (int) $row['count'];
        }

        // Get status counts
        $statusCounts = [];
        $stmt = $this->db->query("
            SELECT status, COUNT(*) as count
            FROM posts
            WHERE status NOT IN ('queued', 'rejected', 'duplicate')
            GROUP BY status
        ");
        foreach ($stmt->fetchAll() as $row) {
            $statusCounts[$row['status']] = (int) $row['count'];
        }

        $this->render('posts/index', [
            'title' => 'Browse Feedback - Creodent Voice',
            'posts' => $posts,
            'currentSort' => $sort,
            'currentCategory' => $category,
            'currentStatus' => $status,
            'categoryCounts' => $categoryCounts,
            'statusCounts' => $statusCounts,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalPosts' => $totalPosts,
        ]);
    }

    public function show(string $public_id): void
    {
        if (!$this->db) {
            $this->flash('error', 'Service temporarily unavailable.');
            $this->redirect('/posts');
        }

        // Get post
        $stmt = $this->db->prepare("
            SELECT p.*, u.name as owner_name
            FROM posts p
            LEFT JOIN users u ON p.owner_id = u.id
            WHERE p.public_id = ? AND p.status NOT IN ('queued', 'rejected')
        ");
        $stmt->execute([$public_id]);
        $post = $stmt->fetch();

        if (!$post) {
            http_response_code(404);
            $this->render('errors/404', ['title' => 'Post Not Found']);
            return;
        }

        // Get vote count
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM post_votes WHERE post_id = ?");
        $stmt->execute([$post['id']]);
        $voteCount = (int) $stmt->fetchColumn();

        // Check if user has voted (using cookie)
        $hasVoted = $this->hasUserVoted($post['id']);

        // Get comments (only show approved/official for public)
        $stmt = $this->db->prepare("
            SELECT c.*, u.name as user_name, u.role as user_role
            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.is_official DESC, c.created_at ASC
        ");
        $stmt->execute([$post['id']]);
        $comments = $stmt->fetchAll();

        // Get attachments
        $stmt = $this->db->prepare("SELECT * FROM attachments WHERE post_id = ?");
        $stmt->execute([$post['id']]);
        $attachments = $stmt->fetchAll();

        // Get related posts (same category)
        $stmt = $this->db->prepare("
            SELECT p.public_id, p.title, p.status,
                   (SELECT COUNT(*) FROM post_votes WHERE post_id = p.id) as vote_count
            FROM posts p
            WHERE p.category = ? AND p.id != ? AND p.status NOT IN ('queued', 'rejected', 'duplicate')
            ORDER BY vote_count DESC
            LIMIT 5
        ");
        $stmt->execute([$post['category'], $post['id']]);
        $relatedPosts = $stmt->fetchAll();

        $this->render('posts/show', [
            'title' => $post['title'] . ' - Creodent Voice',
            'post' => $post,
            'voteCount' => $voteCount,
            'hasVoted' => $hasVoted,
            'comments' => $comments,
            'attachments' => $attachments,
            'relatedPosts' => $relatedPosts,
            'csrf' => $this->csrfToken(),
        ]);
    }

    private function hasUserVoted(int $postId): bool
    {
        $voteKey = $this->generateVoteKey();

        $stmt = $this->db->prepare("SELECT 1 FROM post_votes WHERE post_id = ? AND vote_key = ?");
        $stmt->execute([$postId, $voteKey]);

        return (bool) $stmt->fetch();
    }

    private function generateVoteKey(): string
    {
        // Get or create a persistent cookie for voting
        if (empty($_COOKIE['vote_token'])) {
            $token = bin2hex(random_bytes(32));
            setcookie('vote_token', $token, [
                'expires' => time() + 86400 * 365,
                'path' => '/',
                'secure' => ($_ENV['APP_ENV'] ?? 'development') === 'production',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            $_COOKIE['vote_token'] = $token;
        }

        // Hash token with daily salt to prevent cross-day vote farming
        $daySalt = date('Y-m-d');
        return hash('sha256', $_COOKIE['vote_token'] . $daySalt);
    }
}
