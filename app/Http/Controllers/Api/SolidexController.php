<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\SolidexCaseMeta;
use App\Models\SolidexTooth;
use App\Models\SolidexToothStage;
use App\Events\StageCompleted;
use App\Exceptions\OptimisticLockException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SolidexController extends Controller
{
    public function getMeta(int $caseId): JsonResponse
    {
        $meta = SolidexCaseMeta::where('case_id', $caseId)->firstOrFail();

        return $this->successResponse([
            'meta' => $meta,
            'version' => $meta->version,
            'completion' => $meta->getCompletionPercentage(),
        ]);
    }

    public function updateMeta(int $caseId, Request $request): JsonResponse
    {
        $meta = SolidexCaseMeta::where('case_id', $caseId)->firstOrFail();

        $validated = $request->validate([
            'note' => 'sometimes|string|nullable',
            'implant_system' => 'sometimes|string|nullable',
            'lot' => 'sometimes|string|nullable',
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

    public function getTeeth(int $caseId): JsonResponse
    {
        $teeth = SolidexTooth::where('case_id', $caseId)
            ->with(['stages.assignee'])
            ->orderBy('tooth_number')
            ->get();

        return $this->successResponse([
            'teeth' => $teeth->map(function ($tooth) {
                return [
                    'id' => $tooth->id,
                    'tooth_number' => $tooth->tooth_number,
                    'status' => $tooth->status,
                    'progress' => $tooth->getProgressPercentage(),
                    'current_stage' => $tooth->currentStage()?->stage,
                    'stages' => $tooth->stages,
                ];
            }),
        ]);
    }

    public function createTeeth(int $caseId, Request $request): JsonResponse
    {
        $case = CaseModel::findOrFail($caseId);

        $validated = $request->validate([
            'teeth' => 'required|array|min:1',
            'teeth.*' => 'string',
        ]);

        $createdTeeth = [];

        foreach ($validated['teeth'] as $toothNumber) {
            // Check if tooth already exists
            $existing = SolidexTooth::where('case_id', $caseId)
                ->where('tooth_number', $toothNumber)
                ->first();

            if ($existing) {
                continue;
            }

            $tooth = SolidexTooth::create([
                'case_id' => $caseId,
                'tooth_number' => $toothNumber,
                'status' => 'OPEN',
            ]);

            // Create stages for the tooth
            foreach (SolidexToothStage::STAGES as $stage) {
                SolidexToothStage::create([
                    'tooth_id' => $tooth->id,
                    'stage' => $stage,
                    'status' => 'PENDING',
                ]);
            }

            $createdTeeth[] = $tooth->load('stages');
        }

        // Update meta count
        $meta = SolidexCaseMeta::where('case_id', $caseId)->first();
        if ($meta) {
            $meta->update([
                'count' => SolidexTooth::where('case_id', $caseId)->count(),
            ]);
        }

        return $this->successResponse([
            'teeth' => $createdTeeth,
        ], 'Teeth created successfully', 201);
    }

    public function getToothStages(int $toothId): JsonResponse
    {
        $stages = SolidexToothStage::where('tooth_id', $toothId)
            ->with('assignee')
            ->orderByRaw("FIELD(stage, 'TRANSCAN', 'PRECAD', 'CAD', 'PRECAM', 'CNC', 'QC')")
            ->get();

        return $this->successResponse([
            'stages' => $stages,
        ]);
    }

    public function completeStage(int $stageId, Request $request): JsonResponse
    {
        $stage = SolidexToothStage::with(['tooth.case'])->findOrFail($stageId);
        $user = auth()->user();

        // Validate required fields based on stage
        $rules = [
            'note' => 'sometimes|string|nullable',
        ];

        if ($stage->requiresMachine()) {
            $rules['machine'] = 'required|string';
        }

        if ($stage->hasPrecamSubtasks()) {
            $rules['precam_subtasks'] = 'sometimes|array';
        }

        $validated = $request->validate($rules);

        $expectedVersion = $request->input('_expected_version');

        $updateData = [
            'status' => 'DONE',
            'completed_by_initials' => $user->initials,
            'completed_at' => now(),
            'machine' => $validated['machine'] ?? $stage->machine,
            'precam_subtasks' => $validated['precam_subtasks'] ?? $stage->precam_subtasks,
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

        // Update tooth status
        $stage->tooth->updateStatusFromStages();

        // Update case status if all teeth are done
        $this->updateCaseStatus($stage->tooth->case);

        // Broadcast event
        event(new StageCompleted($stage, 'SOLIDEX'));

        return $this->successResponse([
            'stage' => $stage->fresh()->load('assignee'),
            'version' => $stage->version,
            'tooth_status' => $stage->tooth->fresh()->status,
        ]);
    }

    public function assignStage(int $stageId, Request $request): JsonResponse
    {
        $stage = SolidexToothStage::findOrFail($stageId);

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

    public function isComplete(int $caseId): JsonResponse
    {
        $meta = SolidexCaseMeta::where('case_id', $caseId)->firstOrFail();

        return $this->successResponse([
            'is_complete' => $meta->isComplete(),
            'completion_percentage' => $meta->getCompletionPercentage(),
        ]);
    }

    protected function updateCaseStatus(CaseModel $case): void
    {
        $teeth = $case->solidexTeeth;

        if ($teeth->every(fn ($t) => $t->status === 'DONE')) {
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
        } elseif ($teeth->contains(fn ($t) => $t->status === 'IN_PROGRESS' || $t->status === 'DONE')) {
            if ($case->status === 'OPEN') {
                $case->update(['status' => 'IN_PROGRESS']);
            }
        }
    }
}
