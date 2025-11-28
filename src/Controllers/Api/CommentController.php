<?php
/**
 * Comment API Controller - Anonymous Comments
 */

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\ContentFilter;
use App\Services\RateLimiter;

class CommentController extends BaseController
{
    public function store(string $public_id): void
    {
        if (!$this->db) {
            $this->json(['error' => 'Service unavailable'], 503);
        }

        // Get post
        $stmt = $this->db->prepare("SELECT id FROM posts WHERE public_id = ? AND status NOT IN ('queued', 'rejected')");
        $stmt->execute([$public_id]);
        $post = $stmt->fetch();

        if (!$post) {
            $this->json(['error' => 'Post not found'], 404);
        }

        $postId = (int) $post['id'];
        $clientIp = $this->getClientIp();

        // Rate limiting
        $rateLimiter = new RateLimiter($this->db, 300, 5); // 5 comments per 5 minutes
        if ($rateLimiter->isLimited($clientIp, 'comment')) {
            $this->json(['error' => 'Too many comments. Please wait a few minutes.'], 429);
        }

        // Get and validate comment body
        $body = trim($this->input('body', ''));

        if (empty($body)) {
            $this->json(['error' => 'Comment cannot be empty'], 400);
        }

        if (strlen($body) > 2000) {
            $this->json(['error' => 'Comment is too long (max 2000 characters)'], 400);
        }

        if (strlen($body) < 10) {
            $this->json(['error' => 'Comment is too short (min 10 characters)'], 400);
        }

        // Content filter
        $filter = new ContentFilter();
        $analysis = $filter->analyze($body);

        if ($analysis['blocked']) {
            $this->json(['error' => 'Your comment contains content that violates our guidelines'], 400);
        }

        // Insert comment (anonymous - no user_id)
        $stmt = $this->db->prepare("
            INSERT INTO comments (post_id, user_id, is_official, body)
            VALUES (?, NULL, 0, ?)
        ");
        $stmt->execute([$postId, $body]);

        $commentId = (int) $this->db->lastInsertId();

        // Record rate limit hit
        $rateLimiter->hit($clientIp, 'comment');

        $this->json([
            'success' => true,
            'comment' => [
                'id' => $commentId,
                'body' => htmlspecialchars($body),
                'created_at' => date('c'),
                'is_anonymous' => true,
            ],
        ]);
    }
}
