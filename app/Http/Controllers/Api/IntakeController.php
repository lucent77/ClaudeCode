<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\CaseRoute;
use App\Models\CocrMeta;
use App\Models\CocrStage;
use App\Models\SolidexCaseMeta;
use App\Models\SolidexTooth;
use App\Models\SolidexToothStage;
use App\Models\PrintMeta;
use App\Models\PrintStage;
use App\Events\CaseCreated;
use App\Services\RoutingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IntakeController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'case_number' => 'required|string|unique:cases,case_number',
            'nychv' => 'required|in:NYC,HV',
            'time_stamp' => 'sometimes|date',
            'combo' => 'sometimes|boolean',
            'due_date' => 'sometimes|date',
            'ld' => 'sometimes|in:LAB,DOC',
            'pan' => 'sometimes|string|max:100',
            'lab' => 'sometimes|string|max:255',
            'patient' => 'sometimes|string|max:255',
            'tooth_count' => 'sometimes|integer|min:0',
            'tooth_map' => 'sometimes|array',
            'instructions' => 'sometimes|string|nullable',
            'preferences' => 'sometimes|string|nullable',

            // Department routing
            'routes' => 'sometimes|array',
            'routes.*' => 'in:COCR,SOLIDEX,PRINT',

            // COCR meta
            'cocr' => 'sometimes|array',
            'cocr.type' => 'sometimes|string',
            'cocr.disk_material' => 'sometimes|string',
            'cocr.mi' => 'sometimes|in:MODEL,IO,FILE',
            'cocr.shade' => 'sometimes|string',
            'cocr.milling_shade' => 'sometimes|string',
            'cocr.fc' => 'sometimes|boolean',
            'cocr.ah' => 'sometimes|boolean',
            'cocr.contact' => 'sometimes|string',
            'cocr.occ' => 'sometimes|string',
            'cocr.implant_type' => 'sometimes|string',
            'cocr.note' => 'sometimes|string',

            // SOLIDEX meta
            'solidex' => 'sometimes|array',
            'solidex.note' => 'sometimes|string',
            'solidex.implant_system' => 'sometimes|string',
            'solidex.lot' => 'sometimes|string',
            'solidex.teeth' => 'sometimes|array',
            'solidex.teeth.*' => 'string',

            // 3D Print meta
            'print' => 'sometimes|array',
            'print.type' => 'sometimes|string',
            'print.implant' => 'sometimes|string',
            'print.note' => 'sometimes|string',
        ]);

        try {
            DB::beginTransaction();

            // Create the case
            $case = CaseModel::create([
                'case_number' => $validated['case_number'],
                'nychv' => $validated['nychv'],
                'time_stamp' => $validated['time_stamp'] ?? now(),
                'combo' => $validated['combo'] ?? false,
                'due_date' => $validated['due_date'] ?? null,
                'ld' => $validated['ld'] ?? null,
                'pan' => $validated['pan'] ?? null,
                'lab' => $validated['lab'] ?? null,
                'patient' => $validated['patient'] ?? null,
                'tooth_count' => $validated['tooth_count'] ?? 0,
                'tooth_map' => $validated['tooth_map'] ?? null,
                'instructions' => $validated['instructions'] ?? null,
                'preferences' => $validated['preferences'] ?? null,
                'status' => 'OPEN',
            ]);

            // Determine routes
            $routes = $validated['routes'] ?? $this->determineRoutes($validated);

            foreach ($routes as $dept) {
                CaseRoute::create([
                    'case_id' => $case->id,
                    'target_dept' => $dept,
                    'active' => true,
                ]);

                $this->initializeDepartmentData($case, $dept, $validated);
            }

            DB::commit();

            event(new CaseCreated($case));

            return $this->successResponse([
                'case' => $case->load(['activeRoutes', 'cocrMeta', 'solidexMeta', 'printMeta']),
            ], 'Case created successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create case: ' . $e->getMessage(), 500);
        }
    }

    protected function determineRoutes(array $data): array
    {
        $routes = [];

        // Route based on what data is provided
        if (!empty($data['cocr'])) {
            $routes[] = 'COCR';
        }
        if (!empty($data['solidex'])) {
            $routes[] = 'SOLIDEX';
        }
        if (!empty($data['print'])) {
            $routes[] = 'PRINT';
        }

        // Default to COCR if no specific routing
        if (empty($routes)) {
            $routes[] = 'COCR';
        }

        return $routes;
    }

    protected function initializeDepartmentData(CaseModel $case, string $dept, array $data): void
    {
        switch ($dept) {
            case 'COCR':
                $this->initializeCocrData($case, $data['cocr'] ?? []);
                break;
            case 'SOLIDEX':
                $this->initializeSolidexData($case, $data['solidex'] ?? []);
                break;
            case 'PRINT':
                $this->initializePrintData($case, $data['print'] ?? []);
                break;
        }
    }

    protected function initializeCocrData(CaseModel $case, array $data): void
    {
        // Create meta
        CocrMeta::create([
            'case_id' => $case->id,
            'note' => $data['note'] ?? null,
            'type' => $data['type'] ?? null,
            'disk_material' => $data['disk_material'] ?? null,
            'mi' => $data['mi'] ?? null,
            'shade' => $data['shade'] ?? null,
            'milling_shade' => $data['milling_shade'] ?? null,
            'fc' => $data['fc'] ?? false,
            'ah' => $data['ah'] ?? false,
            'contact' => $data['contact'] ?? null,
            'occ' => $data['occ'] ?? null,
            'implant_type' => $data['implant_type'] ?? null,
        ]);

        // Create stage rows
        $stages = CocrStage::STAGES;
        foreach ($stages as $stage) {
            CocrStage::create([
                'case_id' => $case->id,
                'stage' => $stage,
                'status' => 'PENDING',
            ]);
        }
    }

    protected function initializeSolidexData(CaseModel $case, array $data): void
    {
        $teeth = $data['teeth'] ?? [];

        // Create meta
        SolidexCaseMeta::create([
            'case_id' => $case->id,
            'note' => $data['note'] ?? null,
            'count' => count($teeth),
            'implant_system' => $data['implant_system'] ?? null,
            'lot' => $data['lot'] ?? null,
        ]);

        // Create tooth rows and their stages
        foreach ($teeth as $toothNumber) {
            $tooth = SolidexTooth::create([
                'case_id' => $case->id,
                'tooth_number' => $toothNumber,
                'status' => 'OPEN',
            ]);

            foreach (SolidexToothStage::STAGES as $stage) {
                SolidexToothStage::create([
                    'tooth_id' => $tooth->id,
                    'stage' => $stage,
                    'status' => 'PENDING',
                ]);
            }
        }
    }

    protected function initializePrintData(CaseModel $case, array $data): void
    {
        // Create meta
        PrintMeta::create([
            'case_id' => $case->id,
            'note' => $data['note'] ?? null,
            'type' => $data['type'] ?? null,
            'implant' => $data['implant'] ?? null,
        ]);

        // Create stage rows
        foreach (PrintStage::STAGES as $stage) {
            PrintStage::create([
                'case_id' => $case->id,
                'stage' => $stage,
                'status' => 'PENDING',
            ]);
        }
    }
}
