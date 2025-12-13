<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\CocrStage;
use App\Models\SolidexToothStage;
use App\Models\PrintStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $user = auth()->user();

        $stats = [
            'today_intake' => CaseModel::whereDate('created_at', today())->count(),
            'due_today' => CaseModel::dueToday()->whereNotIn('status', ['DONE'])->count(),
            'overdue' => CaseModel::overdue()->count(),
            'in_progress' => CaseModel::inProgress()->count(),
            'open' => CaseModel::open()->count(),
            'done_today' => CaseModel::whereDate('updated_at', today())
                ->where('status', 'DONE')
                ->count(),
        ];

        // WIP by department
        $stats['wip_by_dept'] = [
            'COCR' => CaseModel::byDept('COCR')->inProgress()->count(),
            'SOLIDEX' => CaseModel::byDept('SOLIDEX')->inProgress()->count(),
            'PRINT' => CaseModel::byDept('PRINT')->inProgress()->count(),
        ];

        // Stage distribution for each department
        if ($user->isAdmin() || $user->canAccessDept('COCR')) {
            $stats['cocr_stages'] = CocrStage::select('stage', DB::raw('count(*) as count'))
                ->whereHas('case', fn ($q) => $q->whereNotIn('status', ['DONE']))
                ->where('status', '!=', 'DONE')
                ->groupBy('stage')
                ->pluck('count', 'stage')
                ->toArray();
        }

        if ($user->isAdmin() || $user->canAccessDept('SOLIDEX')) {
            $stats['solidex_stages'] = SolidexToothStage::select('stage', DB::raw('count(*) as count'))
                ->whereHas('tooth.case', fn ($q) => $q->whereNotIn('status', ['DONE']))
                ->where('status', '!=', 'DONE')
                ->groupBy('stage')
                ->pluck('count', 'stage')
                ->toArray();
        }

        if ($user->isAdmin() || $user->canAccessDept('PRINT')) {
            $stats['print_stages'] = PrintStage::select('stage', DB::raw('count(*) as count'))
                ->whereHas('case', fn ($q) => $q->whereNotIn('status', ['DONE']))
                ->where('status', '!=', 'DONE')
                ->groupBy('stage')
                ->pluck('count', 'stage')
                ->toArray();
        }

        return $this->successResponse($stats);
    }

    public function dueRisk(Request $request): JsonResponse
    {
        // Cases due within 24 hours that are not done
        $dueSoon = CaseModel::with(['activeRoutes'])
            ->dueSoon(24)
            ->orderBy('due_date')
            ->limit(50)
            ->get()
            ->map(function ($case) {
                return [
                    'id' => $case->id,
                    'case_number' => $case->case_number,
                    'patient' => $case->patient,
                    'lab' => $case->lab,
                    'due_date' => $case->due_date->format('Y-m-d'),
                    'status' => $case->status,
                    'is_overdue' => $case->isOverdue(),
                    'hours_until_due' => $case->due_date->diffInHours(now(), false),
                    'routes' => $case->activeRoutes->pluck('target_dept'),
                    'progress' => $case->progress,
                ];
            });

        // Heatmap data: Due date vs Stage
        $heatmapData = [];

        // Get cases grouped by due date
        $casesByDate = CaseModel::whereNotIn('status', ['DONE', 'ON_HOLD'])
            ->whereNotNull('due_date')
            ->where('due_date', '>=', now()->subDays(7))
            ->where('due_date', '<=', now()->addDays(14))
            ->with(['cocrStages', 'printStages', 'solidexTeeth.stages'])
            ->get()
            ->groupBy(fn ($c) => $c->due_date->format('Y-m-d'));

        foreach ($casesByDate as $date => $cases) {
            $heatmapData[$date] = [
                'total' => $cases->count(),
                'cocr' => [],
                'solidex' => [],
                'print' => [],
            ];

            foreach ($cases as $case) {
                // COCR stages
                foreach ($case->cocrStages->where('status', '!=', 'DONE') as $stage) {
                    $heatmapData[$date]['cocr'][$stage->stage] =
                        ($heatmapData[$date]['cocr'][$stage->stage] ?? 0) + 1;
                }

                // Print stages
                foreach ($case->printStages->where('status', '!=', 'DONE') as $stage) {
                    $heatmapData[$date]['print'][$stage->stage] =
                        ($heatmapData[$date]['print'][$stage->stage] ?? 0) + 1;
                }

                // SOLIDEX stages
                foreach ($case->solidexTeeth as $tooth) {
                    foreach ($tooth->stages->where('status', '!=', 'DONE') as $stage) {
                        $heatmapData[$date]['solidex'][$stage->stage] =
                            ($heatmapData[$date]['solidex'][$stage->stage] ?? 0) + 1;
                    }
                }
            }
        }

        return $this->successResponse([
            'due_soon' => $dueSoon,
            'heatmap' => $heatmapData,
        ]);
    }
}
