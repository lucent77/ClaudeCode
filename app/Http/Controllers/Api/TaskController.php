<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CocrStage;
use App\Models\SolidexToothStage;
use App\Models\PrintStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function myTasks(Request $request): JsonResponse
    {
        $user = auth()->user();
        $dept = $request->get('dept', $user->dept);
        $stage = $request->get('stage');
        $overdue = $request->boolean('overdue');
        $limit = min($request->get('limit', 50), 100);

        $tasks = [];

        // Get tasks based on department access
        if ($user->canAccessDept('COCR') && (!$dept || $dept === 'COCR')) {
            $cocrTasks = $this->getCocrTasks($user->id, $stage, $overdue, $limit);
            $tasks = array_merge($tasks, $cocrTasks);
        }

        if ($user->canAccessDept('SOLIDEX') && (!$dept || $dept === 'SOLIDEX')) {
            $solidexTasks = $this->getSolidexTasks($user->id, $stage, $overdue, $limit);
            $tasks = array_merge($tasks, $solidexTasks);
        }

        if ($user->canAccessDept('PRINT') && (!$dept || $dept === 'PRINT')) {
            $printTasks = $this->getPrintTasks($user->id, $stage, $overdue, $limit);
            $tasks = array_merge($tasks, $printTasks);
        }

        // Sort by due date and priority
        usort($tasks, function ($a, $b) {
            // Overdue first
            if ($a['is_overdue'] !== $b['is_overdue']) {
                return $a['is_overdue'] ? -1 : 1;
            }
            // Due soon next
            if ($a['is_due_soon'] !== $b['is_due_soon']) {
                return $a['is_due_soon'] ? -1 : 1;
            }
            // Then by due date
            return strcmp($a['due_date'] ?? '', $b['due_date'] ?? '');
        });

        // Apply limit
        $tasks = array_slice($tasks, 0, $limit);

        return $this->successResponse([
            'tasks' => $tasks,
            'total' => count($tasks),
        ]);
    }

    protected function getCocrTasks(int $userId, ?string $stage, bool $overdue, int $limit): array
    {
        $query = CocrStage::with(['case', 'assignee'])
            ->whereHas('case', function ($q) {
                $q->whereIn('status', ['OPEN', 'IN_PROGRESS']);
            })
            ->where(function ($q) use ($userId) {
                $q->where('assignee_user_id', $userId)
                    ->orWhereNull('assignee_user_id');
            })
            ->readyToWork();

        if ($stage) {
            $query->byStage($stage);
        }

        if ($overdue) {
            $query->whereHas('case', function ($q) {
                $q->overdue();
            });
        }

        return $query->limit($limit)->get()->map(function ($s) {
            return $this->formatTask($s, 'COCR');
        })->toArray();
    }

    protected function getSolidexTasks(int $userId, ?string $stage, bool $overdue, int $limit): array
    {
        $query = SolidexToothStage::with(['tooth.case', 'assignee'])
            ->whereHas('tooth.case', function ($q) {
                $q->whereIn('status', ['OPEN', 'IN_PROGRESS']);
            })
            ->where(function ($q) use ($userId) {
                $q->where('assignee_user_id', $userId)
                    ->orWhereNull('assignee_user_id');
            })
            ->readyToWork();

        if ($stage) {
            $query->byStage($stage);
        }

        if ($overdue) {
            $query->whereHas('tooth.case', function ($q) {
                $q->overdue();
            });
        }

        return $query->limit($limit)->get()->map(function ($s) {
            return $this->formatTask($s, 'SOLIDEX');
        })->toArray();
    }

    protected function getPrintTasks(int $userId, ?string $stage, bool $overdue, int $limit): array
    {
        $query = PrintStage::with(['case', 'assignee'])
            ->whereHas('case', function ($q) {
                $q->whereIn('status', ['OPEN', 'IN_PROGRESS']);
            })
            ->where(function ($q) use ($userId) {
                $q->where('assignee_user_id', $userId)
                    ->orWhereNull('assignee_user_id');
            })
            ->readyToWork();

        if ($stage) {
            $query->byStage($stage);
        }

        if ($overdue) {
            $query->whereHas('case', function ($q) {
                $q->overdue();
            });
        }

        return $query->limit($limit)->get()->map(function ($s) {
            return $this->formatTask($s, 'PRINT');
        })->toArray();
    }

    protected function formatTask($stageModel, string $dept): array
    {
        $case = $dept === 'SOLIDEX'
            ? $stageModel->tooth->case
            : $stageModel->case;

        return [
            'id' => $stageModel->id,
            'dept' => $dept,
            'stage' => $stageModel->stage,
            'status' => $stageModel->status,
            'case_id' => $case->id,
            'case_number' => $case->case_number,
            'patient' => $case->patient,
            'lab' => $case->lab,
            'due_date' => $case->due_date?->format('Y-m-d'),
            'is_overdue' => $case->isOverdue(),
            'is_due_soon' => $case->isDueSoon(24),
            'nychv' => $case->nychv,
            'tooth_number' => $dept === 'SOLIDEX' ? $stageModel->tooth->tooth_number : null,
            'assignee' => $stageModel->assignee?->only(['id', 'name', 'initials']),
            'version' => $stageModel->version,
        ];
    }
}
