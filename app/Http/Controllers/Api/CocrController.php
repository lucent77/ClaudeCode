<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\CocrMeta;
use App\Models\CocrStage;
use App\Events\StageCompleted;
use App\Exceptions\OptimisticLockException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CocrController extends Controller
{
    public function getMeta(int $caseId): JsonResponse
    {
        $meta = CocrMeta::where('case_id', $caseId)->firstOrFail();

        return $this->successResponse([
            'meta' => $meta,
            'version' => $meta->version,
        ]);
    }

    public function updateMeta(int $caseId, Request $request): JsonResponse
    {
        $meta = CocrMeta::where('case_id', $caseId)->firstOrFail();

        $validated = $request->validate([
            'note' => 'sometimes|string|nullable',
            'type' => 'sometimes|string|nullable',
            'disk_material' => 'sometimes|string|nullable',
            'mi' => 'sometimes|in:MODEL,IO,FILE',
            'shade' => 'sometimes|string|nullable',
            'milling_shade' => 'sometimes|string|nullable',
            'fc' => 'sometimes|boolean',
            'ah' => 'sometimes|boolean',
            'contact' => 'sometimes|string|nullable',
            'occ' => 'sometimes|string|nullable',
            'implant_type' => 'sometimes|string|nullable',
        ]);

        $expectedVersion = $request->input('_expected_version');

        if ($expectedVersion !== null) {
            try {
                $meta->updateWithLock($validated, $expectedVersion);
            } catch (OptimisticLockException $e) {
                return response()->json([
                    'error' => 'conflict',
                    'message' => $e->getMessage(),
                    'current_version' => $e->getCurrentVersion(),
                ], 409);
            }
        } else {
            $meta->update($validated);
        }

        return $this->successResponse([
            'meta' => $meta->fresh(),
            'version' => $meta->version,
        ]);
    }

    public function getStages(int $caseId): JsonResponse
    {
        $stages = CocrStage::where('case_id', $caseId)
            ->with('assignee')
            ->orderByRaw("FIELD(stage, 'TRANS', 'DESIGN', 'CAM', 'CNC', 'OVENS', 'QC')")
            ->get();

        return $this->successResponse([
            'stages' => $stages,
        ]);
    }

    public function completeStage(int $stageId, Request $request): JsonResponse
    {
        $stage = CocrStage::with('case')->findOrFail($stageId);
        $user = auth()->user();

        // Validate required fields based on stage
        $rules = [
            'note' => 'sometimes|string|nullable',
        ];

        if ($stage->requiresMachine()) {
            $rules['machine'] = 'required|string';
        }

        if ($stage->requiresOven()) {
            $rules['oven'] = 'required|string';
        }

        $validated = $request->validate($rules);

        $expectedVersion = $request->input('_expected_version');

        $updateData = [
            'status' => 'DONE',
            'completed_by_initials' => $user->initials,
            'completed_at' => now(),
            'machine' => $validated['machine'] ?? $stage->machine,
            'oven' => $validated['oven'] ?? $stage->oven,
            'note' => $validated['note'] ?? $stage->note,
        ];

        if ($expectedVersion !== null) {
            try {
                $stage->updateWithLock($updateData, $expectedVersion);
            } catch (OptimisticLockException $e) {
                return response()->json([
                    'error' => 'conflict',
                    'message' => $e->getMessage(),
                    'current_version' => $e->getCurrentVersion(),
                ], 409);
            }
        } else {
            $stage->update($updateData);
        }

        // Update case status if needed
        $this->updateCaseStatus($stage->case);

        // Broadcast event
        event(new StageCompleted($stage, 'COCR'));

        return $this->successResponse([
            'stage' => $stage->fresh()->load('assignee'),
            'version' => $stage->version,
        ]);
    }

    public function assignStage(int $stageId, Request $request): JsonResponse
    {
        $stage = CocrStage::findOrFail($stageId);

        $validated = $request->validate([
            'assignee_user_id' => 'required|exists:users,id',
        ]);

        $stage->update([
            'assignee_user_id' => $validated['assignee_user_id'],
            'status' => $stage->status === 'PENDING' ? 'IN_PROGRESS' : $stage->status,
        ]);

        return $this->successResponse([
            'stage' => $stage->fresh()->load('assignee'),
        ]);
    }

    protected function updateCaseStatus(CaseModel $case): void
    {
        $stages = $case->cocrStages;

        if ($stages->every(fn ($s) => $s->status === 'DONE')) {
            // Check if all department paths are complete
            $allComplete = true;

            foreach ($case->activeRoutes as $route) {
                switch ($route->target_dept) {
                    case 'COCR':
                        $allComplete = $allComplete && $case->cocrStages->every(fn ($s) => $s->status === 'DONE');
                        break;
                    case 'SOLIDEX':
                        $allComplete = $allComplete && $case->solidexTeeth->every(fn ($t) => $t->status === 'DONE');
                        break;
                    case 'PRINT':
                        $allComplete = $allComplete && $case->printStages->every(fn ($s) => $s->status === 'DONE');
                        break;
                }
            }

            if ($allComplete) {
                $case->update(['status' => 'DONE']);
            }
        } elseif ($stages->contains(fn ($s) => $s->status === 'IN_PROGRESS' || $s->status === 'DONE')) {
            if ($case->status === 'OPEN') {
                $case->update(['status' => 'IN_PROGRESS']);
            }
        }
    }
}
