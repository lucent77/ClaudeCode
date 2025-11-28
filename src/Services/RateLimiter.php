<?php
/**
 * Rate Limiter Service
 *
 * Implements sliding window rate limiting using database storage.
 */

declare(strict_types=1);

namespace App\Services;

use PDO;

class RateLimiter
{
    private PDO $db;
    private int $windowSeconds;
    private int $maxAttempts;

    public function __construct(PDO $db, int $windowSeconds = 60, int $maxAttempts = 10)
    {
        $this->db = $db;
        $this->windowSeconds = $windowSeconds;
        $this->maxAttempts = $maxAttempts;
    }

    /**
     * Check if an action is rate limited
     */
    public function isLimited(string $identifier, string $action): bool
    {
        $ipHash = hash('sha256', $identifier);

        // Clean old entries
        $this->cleanup();

        // Get current attempts
        $stmt = $this->db->prepare("
            SELECT attempts, window_start
            FROM rate_limits
            WHERE ip_hash = ? AND action = ?
        ");
        $stmt->execute([$ipHash, $action]);
        $record = $stmt->fetch();

        if (!$record) {
            return false;
        }

        $windowStart = strtotime($record['window_start']);
        $now = time();

        // Check if window has expired
        if ($now - $windowStart > $this->windowSeconds) {
            return false;
        }

        return $record['attempts'] >= $this->maxAttempts;
    }

    /**
     * Record an action attempt
     */
    public function hit(string $identifier, string $action): int
    {
        $ipHash = hash('sha256', $identifier);

        // Try to update existing record
        $stmt = $this->db->prepare("
            INSERT INTO rate_limits (ip_hash, action, attempts, window_start)
            VALUES (?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE
                attempts = IF(
                    TIMESTAMPDIFF(SECOND, window_start, NOW()) > ?,
                    1,
                    attempts + 1
                ),
                window_start = IF(
                    TIMESTAMPDIFF(SECOND, window_start, NOW()) > ?,
                    NOW(),
                    window_start
                )
        ");
        $stmt->execute([$ipHash, $action, $this->windowSeconds, $this->windowSeconds]);

        // Get current attempts
        $stmt = $this->db->prepare("
            SELECT attempts FROM rate_limits
            WHERE ip_hash = ? AND action = ?
        ");
        $stmt->execute([$ipHash, $action]);
        $result = $stmt->fetch();

        return $result ? (int) $result['attempts'] : 1;
    }

    /**
     * Get remaining attempts
     */
    public function remaining(string $identifier, string $action): int
    {
        $ipHash = hash('sha256', $identifier);

        $stmt = $this->db->prepare("
            SELECT attempts, window_start
            FROM rate_limits
            WHERE ip_hash = ? AND action = ?
        ");
        $stmt->execute([$ipHash, $action]);
        $record = $stmt->fetch();

        if (!$record) {
            return $this->maxAttempts;
        }

        $windowStart = strtotime($record['window_start']);
        $now = time();

        if ($now - $windowStart > $this->windowSeconds) {
            return $this->maxAttempts;
        }

        return max(0, $this->maxAttempts - $record['attempts']);
    }

    /**
     * Reset rate limit for identifier/action
     */
    public function reset(string $identifier, string $action): void
    {
        $ipHash = hash('sha256', $identifier);

        $stmt = $this->db->prepare("
            DELETE FROM rate_limits
            WHERE ip_hash = ? AND action = ?
        ");
        $stmt->execute([$ipHash, $action]);
    }

    /**
     * Clean up expired entries
     */
    private function cleanup(): void
    {
        // Run cleanup 1% of the time to avoid overhead
        if (random_int(1, 100) !== 1) {
            return;
        }

        $stmt = $this->db->prepare("
            DELETE FROM rate_limits
            WHERE window_start < DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$this->windowSeconds * 2]);
    }

    /**
     * Set max attempts
     */
    public function setMaxAttempts(int $max): self
    {
        $this->maxAttempts = $max;
        return $this;
    }

    /**
     * Set window in seconds
     */
    public function setWindow(int $seconds): self
    {
        $this->windowSeconds = $seconds;
        return $this;
    }
}
