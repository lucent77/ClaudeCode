<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Events\CaseUpdated;
use App\Exceptions\OptimisticLockException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CaseModel::with(['activeRoutes']);

        // Filter by department if specified
        if ($request->has('dept')) {
            $query->byDept($request->dept);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        // Filter by due date
        if ($request->has('due_before')) {
            $query->dueBefore($request->due_before);
        }

        // Filter overdue
        if ($request->boolean('overdue')) {
            $query->overdue();
        }

        // Filter due soon
        if ($request->boolean('due_soon')) {
            $query->dueSoon(24);
        }

        // Search
        if ($request->has('q')) {
            $query->search($request->q);
        }

        // Filter by NYC/HV
        if ($request->has('nychv')) {
            $query->where('nychv', $request->nychv);
        }

        // Sort
        $sortField = $request->get('sort', 'due_date');
        $sortDir = $request->get('dir', 'asc');
        $query->orderBy($sortField, $sortDir);

        $perPage = min($request->get('per_page', 25), 100);
        $cases = $query->paginate($perPage);

        return $this->paginatedResponse($cases);
    }

    public function show(int $id): JsonResponse
    {
        $case = CaseModel::with([
            'activeRoutes',
            'cocrMeta',
            'cocrStages.assignee',
            'solidexMeta',
            'solidexTeeth.stages.assignee',
            'printMeta',
            'printStages.assignee',
            'notes.creator',
        ])->findOrFail($id);

        return $this->successResponse([
            'case' => $case,
            'progress' => $case->progress,
        ]);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $case = CaseModel::findOrFail($id);

        $validated = $request->validate([
            'due_date' => 'sometimes|date',
            'status' => 'sometimes|in:OPEN,IN_PROGRESS,DONE,ON_HOLD',
            'instructions' => 'sometimes|string|nullable',
            'preferences' => 'sometimes|string|nullable',
        ]);

        $expectedVersion = $request->input('_expected_version');

        if ($expectedVersion !== null) {
            try {
                $case->updateWithLock($validated, $expectedVersion);
            } catch (OptimisticLockException $e) {
                return response()->json([
                    'error' => 'conflict',
                    'message' => $e->getMessage(),
                    'current_version' => $e->getCurrentVersion(),
                ], 409);
            }
        } else {
            $case->update($validated);
        }

        event(new CaseUpdated($case));

        return $this->successResponse([
            'case' => $case->fresh(),
            'version' => $case->version,
        ]);
    }
}
