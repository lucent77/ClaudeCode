<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\CaseActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CaseController extends Controller
{
    /**
     * Get all cases with filters
     */
    public function index(Request $request)
    {
        $query = CaseModel::with(['assignee', 'attachments']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by assignee
        if ($request->has('assignee_id')) {
            $query->where('assignee_user_id', $request->assignee_id);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('surgery_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('surgery_date', '<=', $request->date_to);
        }

        // Filter overdue
        if ($request->boolean('overdue')) {
            $query->overdue();
        }

        // Filter upcoming
        if ($request->boolean('upcoming')) {
            $query->upcoming();
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('patient_name', 'like', "%{$search}%")
                  ->orWhere('assignee_name', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $request->get('per_page', 20);
        $cases = $query->paginate($perPage);

        return response()->json($cases);
    }

    /**
     * Get a single case
     */
    public function show($id)
    {
        $case = CaseModel::with([
            'assignee',
            'attachments',
            'steps.assignedUser',
            'activityLogs.user'
        ])->findOrFail($id);

        return response()->json($case);
    }

    /**
     * Create a new case
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_name' => 'required|string|max:255',
            'assignee_name' => 'nullable|string|max:255',
            'assignee_user_id' => 'nullable|exists:users,id',
            'surgery_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'preop_scan_date' => 'nullable|date',
            'status' => 'nullable|in:open,in_progress,completed,cancelled',
            'priority' => 'nullable|in:low,normal,high,critical',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $case = CaseModel::create($request->all());

        CaseActivityLog::logActivity(
            $case->id,
            'created',
            'Case created manually',
            null,
            auth()->id()
        );

        return response()->json($case, 201);
    }

    /**
     * Update a case
     */
    public function update(Request $request, $id)
    {
        $case = CaseModel::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'patient_name' => 'sometimes|required|string|max:255',
            'assignee_name' => 'nullable|string|max:255',
            'assignee_user_id' => 'nullable|exists:users,id',
            'surgery_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'preop_scan_date' => 'nullable|date',
            'status' => 'nullable|in:open,in_progress,completed,cancelled',
            'priority' => 'nullable|in:low,normal,high,critical',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldData = $case->toArray();
        $case->update($request->all());

        CaseActivityLog::logActivity(
            $case->id,
            'updated',
            'Case updated',
            [
                'before' => $oldData,
                'after' => $case->fresh()->toArray(),
            ],
            auth()->id()
        );

        return response()->json($case);
    }

    /**
     * Delete a case
     */
    public function destroy($id)
    {
        $case = CaseModel::findOrFail($id);
        $case->delete();

        CaseActivityLog::logActivity(
            $case->id,
            'deleted',
            'Case deleted',
            null,
            auth()->id()
        );

        return response()->json(['message' => 'Case deleted successfully']);
    }

    /**
     * Get case statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => CaseModel::count(),
            'open' => CaseModel::open()->count(),
            'in_progress' => CaseModel::inProgress()->count(),
            'completed' => CaseModel::completed()->count(),
            'overdue' => CaseModel::overdue()->count(),
            'upcoming' => CaseModel::upcoming()->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Get case activity logs
     */
    public function activityLogs($id)
    {
        $case = CaseModel::findOrFail($id);
        $logs = $case->activityLogs()->with('user')->paginate(20);

        return response()->json($logs);
    }
}
