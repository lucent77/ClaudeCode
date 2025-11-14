<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SlackSyncService;
use App\Models\SyncLog;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    protected $syncService;

    public function __construct(SlackSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Trigger manual sync from Slack Canvas
     */
    public function sync(Request $request)
    {
        if (!config('services.sync.enabled')) {
            return response()->json([
                'error' => 'Sync is disabled in configuration'
            ], 403);
        }

        $result = $this->syncService->syncFromCanvas('manual', auth()->id());

        if ($result['success']) {
            return response()->json([
                'message' => 'Sync completed successfully',
                'data' => $result,
            ]);
        }

        return response()->json([
            'message' => 'Sync failed',
            'error' => $result['error'] ?? 'Unknown error',
        ], 500);
    }

    /**
     * Get sync logs
     */
    public function logs(Request $request)
    {
        $query = SyncLog::with('triggeredBy')
            ->orderBy('started_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by sync type
        if ($request->has('sync_type')) {
            $query->where('sync_type', $request->sync_type);
        }

        $logs = $query->paginate(20);

        return response()->json($logs);
    }

    /**
     * Get a single sync log
     */
    public function showLog($id)
    {
        $log = SyncLog::with('triggeredBy')->findOrFail($id);

        return response()->json($log);
    }

    /**
     * Test Slack API connection
     */
    public function testConnection()
    {
        $connected = $this->syncService->testConnection();

        if ($connected) {
            return response()->json([
                'message' => 'Slack API connection successful',
                'connected' => true,
            ]);
        }

        return response()->json([
            'message' => 'Failed to connect to Slack API',
            'connected' => false,
        ], 500);
    }

    /**
     * Get sync statistics
     */
    public function statistics()
    {
        $stats = [
            'total_syncs' => SyncLog::count(),
            'successful_syncs' => SyncLog::where('status', 'success')->count(),
            'failed_syncs' => SyncLog::where('status', 'failed')->count(),
            'last_sync' => SyncLog::orderBy('started_at', 'desc')->first(),
            'total_cases_synced' => SyncLog::sum('cases_synced'),
            'total_attachments_synced' => SyncLog::sum('attachments_synced'),
        ];

        return response()->json($stats);
    }
}
