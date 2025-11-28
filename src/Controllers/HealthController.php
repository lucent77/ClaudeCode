<?php
/**
 * Health Check Controller
 */

declare(strict_types=1);

namespace App\Controllers;

class HealthController extends BaseController
{
    public function index(): void
    {
        $status = [
            'status' => 'OK',
            'timestamp' => date('c'),
            'version' => '1.0.0',
        ];

        // Check database connection
        if ($this->db) {
            try {
                $this->db->query('SELECT 1');
                $status['database'] = 'connected';
            } catch (\PDOException $e) {
                $status['database'] = 'error';
                $status['status'] = 'DEGRADED';
            }
        } else {
            $status['database'] = 'not configured';
        }

        // Return plain text for simple health checks
        if (isset($_GET['format']) && $_GET['format'] === 'json') {
            $this->json($status);
        } else {
            header('Content-Type: text/plain');
            echo $status['status'];
        }
    }
}
