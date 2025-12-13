<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\PrintMeta;
use App\Models\PrintStage;
use App\Events\StageCompleted;
use App\Exceptions\OptimisticLockException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrintController extends Controller
{
    public function getMeta(int $caseId): JsonResponse
    {
        $meta = PrintMeta::where('case_id', $caseId)->firstOrFail();

        return $this->successResponse([
            'meta' => $meta,
            'version' => $meta->version,
        ]);
    }

    public function updateMeta(int $caseId, Request $request): JsonResponse
    {
        $meta = PrintMeta::where('case_id', $caseId)->firstOrFail();

        $validated = $request->validate([
            'note' => 'sometimes|string|nullable',
            'type' => 'sometimes|string|nullable',
            'implant' => 'sometimes|string|nullable',
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
        $stages = PrintStage::where('case_id', $caseId)
            ->with('assignee')
            ->orderByRaw("FIELD(stage, 'TRANS', 'PREP', 'DESIGN', 'NESTING', 'PRINT', 'POST', 'QC')")
            ->get();

        return $this->successResponse([
            'stages' => $stages,
        ]);
    }

    public function completeStage(int $stageId, Request $request): JsonResponse
    {
        $stage = PrintStage::with('case')->findOrFail($stageId);
        $user = auth()->user();

        // Validate required fields based on stage
        $rules = [
            'note' => 'sometimes|string|nullable',
        ];

        if ($stage->requiresPrinter()) {
            $rules['printer'] = 'required|string';
        }

        $validated = $request->validate($rules);

        $expectedVersion = $request->input('_expected_version');

        $updateData = [
            'status' => 'DONE',
            'completed_by_initials' => $user->initials,
            'completed_at' => now(),
            'printer' => $validated['printer'] ?? $stage->printer,
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
        event(new StageCompleted($stage, 'PRINT'));

        return $this->successResponse([
            'stage' => $stage->fresh()->load('assignee'),
            'version' => $stage->version,
        ]);
    }

    public function assignStage(int $stageId, Request $request): JsonResponse
    {
        $stage = PrintStage::findOrFail($stageId);

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
        $stages = $case->printStages;

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
