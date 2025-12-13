<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\Oven;
use App\Models\Material;
use App\Models\Shade;
use App\Models\StageConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    protected array $models = [
        'machines' => Machine::class,
        'ovens' => Oven::class,
        'materials' => Material::class,
        'shades' => Shade::class,
    ];

    public function index(string $type, Request $request): JsonResponse
    {
        if (!isset($this->models[$type])) {
            return $this->errorResponse('Invalid lookup type', 400);
        }

        $model = $this->models[$type];
        $query = $model::query();

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        if ($request->has('dept') && $type === 'machines') {
            $query->byDept($request->dept);
        }

        $items = $query->orderBy('name')->get();

        return $this->successResponse([
            'items' => $items,
        ]);
    }

    public function store(string $type, Request $request): JsonResponse
    {
        if (!isset($this->models[$type])) {
            return $this->errorResponse('Invalid lookup type', 400);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'active' => 'sometimes|boolean',
        ];

        if ($type === 'machines') {
            $rules['dept'] = 'required|in:COCR,SOLIDEX,PRINT';
        }

        $validated = $request->validate($rules);

        $model = $this->models[$type];
        $item = $model::create($validated);

        return $this->successResponse([
            'item' => $item,
        ], 'Created successfully', 201);
    }

    public function update(string $type, int $id, Request $request): JsonResponse
    {
        if (!isset($this->models[$type])) {
            return $this->errorResponse('Invalid lookup type', 400);
        }

        $model = $this->models[$type];
        $item = $model::findOrFail($id);

        $rules = [
            'name' => 'sometimes|string|max:255',
            'active' => 'sometimes|boolean',
        ];

        if ($type === 'machines') {
            $rules['dept'] = 'sometimes|in:COCR,SOLIDEX,PRINT';
        }

        $validated = $request->validate($rules);
        $item->update($validated);

        return $this->successResponse([
            'item' => $item->fresh(),
        ]);
    }

    public function destroy(string $type, int $id): JsonResponse
    {
        if (!isset($this->models[$type])) {
            return $this->errorResponse('Invalid lookup type', 400);
        }

        $model = $this->models[$type];
        $item = $model::findOrFail($id);
        $item->delete();

        return $this->successResponse(null, 'Deleted successfully');
    }

    public function stageConfigs(Request $request): JsonResponse
    {
        $query = StageConfig::query();

        if ($request->has('dept')) {
            $query->byDept($request->dept);
        }

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        $configs = $query->ordered()->get();

        return $this->successResponse([
            'configs' => $configs,
        ]);
    }

    public function createStageConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'dept' => 'required|in:COCR,SOLIDEX,PRINT',
            'stage' => 'required|string|max:50',
            'order_index' => 'required|integer|min:0',
            'enable_subtasks' => 'sometimes|boolean',
            'allowed_fields' => 'sometimes|array',
            'required_fields' => 'sometimes|array',
            'active' => 'sometimes|boolean',
        ]);

        $config = StageConfig::create($validated);

        return $this->successResponse([
            'config' => $config,
        ], 'Stage config created', 201);
    }

    public function updateStageConfig(int $id, Request $request): JsonResponse
    {
        $config = StageConfig::findOrFail($id);

        $validated = $request->validate([
            'stage' => 'sometimes|string|max:50',
            'order_index' => 'sometimes|integer|min:0',
            'enable_subtasks' => 'sometimes|boolean',
            'allowed_fields' => 'sometimes|array',
            'required_fields' => 'sometimes|array',
            'active' => 'sometimes|boolean',
        ]);

        $config->update($validated);

        return $this->successResponse([
            'config' => $config->fresh(),
        ]);
    }
}
