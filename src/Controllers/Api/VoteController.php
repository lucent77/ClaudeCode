<?php
/**
 * Vote API Controller
 */

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\RateLimiter;

class VoteController extends BaseController
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
        $rateLimiter = new RateLimiter($this->db, 60, (int) ($_ENV['RATE_LIMIT_VOTES'] ?? 10));
        if ($rateLimiter->isLimited($clientIp, 'vote')) {
            $this->json(['error' => 'Too many votes. Please slow down.'], 429);
        }

        // Generate vote key
        $voteKey = $this->generateVoteKey();

        // Check if already voted
        $stmt = $this->db->prepare("SELECT 1 FROM post_votes WHERE post_id = ? AND vote_key = ?");
        $stmt->execute([$postId, $voteKey]);

        if ($stmt->fetch()) {
            // Remove vote (toggle)
            $stmt = $this->db->prepare("DELETE FROM post_votes WHERE post_id = ? AND vote_key = ?");
            $stmt->execute([$postId, $voteKey]);
            $voted = false;
        } else {
            // Add vote
            $stmt = $this->db->prepare("INSERT INTO post_votes (post_id, vote_key) VALUES (?, ?)");
            $stmt->execute([$postId, $voteKey]);
            $voted = true;
        }

        // Record rate limit hit
        $rateLimiter->hit($clientIp, 'vote');

        // Get new vote count
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM post_votes WHERE post_id = ?");
        $stmt->execute([$postId]);
        $voteCount = (int) $stmt->fetchColumn();

        $this->json([
            'success' => true,
            'voted' => $voted,
            'vote_count' => $voteCount,
        ]);
    }

    private function generateVoteKey(): string
    {
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

        $daySalt = date('Y-m-d');
        return hash('sha256', $_COOKIE['vote_token'] . $daySalt);
    }
}
