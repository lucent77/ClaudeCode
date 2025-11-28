<?php
/**
 * Digest Controller - Public Weekly Summaries
 */

declare(strict_types=1);

namespace App\Controllers;

class DigestController extends BaseController
{
    public function index(): void
    {
        if (!$this->db) {
            $this->render('digests/index', [
                'title' => 'Weekly Updates - Creodent Voice',
                'digests' => [],
            ]);
            return;
        }

        $stmt = $this->db->query("
            SELECT *
            FROM digests
            ORDER BY week_end DESC
            LIMIT 52
        ");
        $digests = $stmt->fetchAll();

        $this->render('digests/index', [
            'title' => 'Weekly Updates - Creodent Voice',
            'digests' => $digests,
        ]);
    }

    public function show(string $id): void
    {
        if (!$this->db) {
            $this->flash('error', 'Service temporarily unavailable.');
            $this->redirect('/digests');
        }

        $stmt = $this->db->prepare("SELECT * FROM digests WHERE id = ?");
        $stmt->execute([(int) $id]);
        $digest = $stmt->fetch();

        if (!$digest) {
            http_response_code(404);
            $this->render('errors/404', ['title' => 'Digest Not Found']);
            return;
        }

        // Get adjacent digests for navigation
        $stmt = $this->db->prepare("SELECT id, week_start FROM digests WHERE week_start > ? ORDER BY week_start ASC LIMIT 1");
        $stmt->execute([$digest['week_start']]);
        $nextDigest = $stmt->fetch();

        $stmt = $this->db->prepare("SELECT id, week_start FROM digests WHERE week_start < ? ORDER BY week_start DESC LIMIT 1");
        $stmt->execute([$digest['week_start']]);
        $prevDigest = $stmt->fetch();

        $this->render('digests/show', [
            'title' => 'Week of ' . date('M j, Y', strtotime($digest['week_start'])) . ' - Creodent Voice',
            'digest' => $digest,
            'nextDigest' => $nextDigest,
            'prevDigest' => $prevDigest,
        ]);
    }
}
