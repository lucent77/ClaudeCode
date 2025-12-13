<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GsheetLink;
use App\Models\GsyncQueue;
use App\Jobs\ProcessGoogleSheetSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GsyncController extends Controller
{
    public function run(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'link_id' => 'sometimes|exists:gsheet_links,id',
            'dept' => 'sometimes|in:COCR,SOLIDEX,PRINT,CASES',
        ]);

        $query = GsheetLink::active();

        if (isset($validated['link_id'])) {
            $query->where('id', $validated['link_id']);
        }

        if (isset($validated['dept'])) {
            $query->byDept($validated['dept']);
        }

        $links = $query->pullable()->get();

        if ($links->isEmpty()) {
            return $this->errorResponse('No active sync links found', 404);
        }

        $queued = [];

        foreach ($links as $link) {
            $queueItem = GsyncQueue::create([
                'gsheet_link_id' => $link->id,
                'payload' => [
                    'sheet_id' => $link->sheet_id,
                    'tab_name' => $link->tab_name,
                    'mapping' => $link->mapping,
                ],
                'direction' => 'PULL',
                'status' => 'PENDING',
            ]);

            ProcessGoogleSheetSync::dispatch($queueItem);

            $queued[] = [
                'queue_id' => $queueItem->id,
                'link_id' => $link->id,
                'dept' => $link->dept,
            ];
        }

        return $this->successResponse([
            'message' => 'Sync jobs queued',
            'queued' => $queued,
        ]);
    }

    public function status(): JsonResponse
    {
        $links = GsheetLink::active()
            ->with(['syncQueue' => fn ($q) => $q->latest()->limit(1)])
            ->get()
            ->map(function ($link) {
                $lastSync = $link->syncQueue->first();
                return [
                    'id' => $link->id,
                    'dept' => $link->dept,
                    'sheet_id' => $link->sheet_id,
                    'tab_name' => $link->tab_name,
                    'direction' => $link->direction,
                    'last_sync_at' => $link->last_sync_at?->format('Y-m-d H:i:s'),
                    'last_sync_status' => $lastSync?->status,
                    'last_sync_error' => $lastSync?->error_text,
                ];
            });

        return $this->successResponse([
            'links' => $links,
        ]);
    }

    public function queue(Request $request): JsonResponse
    {
        $query = GsyncQueue::with('gsheetLink');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('link_id')) {
            $query->where('gsheet_link_id', $request->link_id);
        }

        $queue = $query->latest()
            ->limit($request->get('limit', 50))
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'link_id' => $item->gsheet_link_id,
                    'dept' => $item->gsheetLink?->dept,
                    'direction' => $item->direction,
                    'status' => $item->status,
                    'error_text' => $item->error_text,
                    'retry_count' => $item->retry_count,
                    'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                    'processed_at' => $item->processed_at?->format('Y-m-d H:i:s'),
                ];
            });

        return $this->successResponse([
            'queue' => $queue,
        ]);
    }
}
