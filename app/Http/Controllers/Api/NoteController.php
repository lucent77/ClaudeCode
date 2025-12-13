<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Note::with('creator');

        if ($request->has('scope')) {
            $query->byScope($request->scope);
        }

        if ($request->has('scope_id')) {
            $query->byScopeId($request->scope_id);
        }

        if ($request->has('tag')) {
            $query->withTag($request->tag);
        }

        if ($request->has('tags')) {
            $query->withAnyTags($request->tags);
        }

        if ($request->has('q')) {
            $query->search($request->q);
        }

        $query->orderByDesc('created_at');

        $perPage = min($request->get('per_page', 25), 100);
        $notes = $query->paginate($perPage);

        return $this->paginatedResponse($notes);
    }

    public function show(int $id): JsonResponse
    {
        $note = Note::with('creator')->findOrFail($id);

        return $this->successResponse([
            'note' => $note,
        ]);
    }

    public function store(int $caseId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scope' => 'sometimes|in:CASE,COCR,SOLIDEX,PRINT,TOOTH',
            'scope_id' => 'sometimes|integer',
            'tags' => 'sometimes|array',
            'tags.*' => 'string',
            'body' => 'required|string|min:1',
        ]);

        $note = Note::create([
            'scope' => $validated['scope'] ?? 'CASE',
            'scope_id' => $validated['scope_id'] ?? $caseId,
            'tags' => $validated['tags'] ?? [],
            'body' => $validated['body'],
            'created_by' => auth()->id(),
        ]);

        return $this->successResponse([
            'note' => $note->load('creator'),
        ], 'Note created successfully', 201);
    }

    public function availableTags(): JsonResponse
    {
        return $this->successResponse([
            'tags' => Note::getAvailableTags(),
        ]);
    }
}
