<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\CaseAttachment;
use App\Models\CaseActivityLog;
use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    protected $fileStorage;

    public function __construct(FileStorageService $fileStorage)
    {
        $this->fileStorage = $fileStorage;
    }

    /**
     * Get attachments for a case
     */
    public function index($caseId, Request $request)
    {
        $case = CaseModel::findOrFail($caseId);

        $query = $case->attachments();

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $attachments = $query->get();

        return response()->json($attachments);
    }

    /**
     * Upload attachment
     */
    public function store($caseId, Request $request)
    {
        $case = CaseModel::findOrFail($caseId);

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:51200', // 50MB
            'type' => 'required|in:photo,stl,cbct,scan,other',
            'label' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $file = $request->file('file');

            $localPath = $this->fileStorage->uploadFile(
                $file,
                $caseId,
                $request->type
            );

            $attachment = CaseAttachment::create([
                'case_id' => $caseId,
                'type' => $request->type,
                'label' => $request->label,
                'local_path' => $localPath,
                'filename' => $file->getClientOriginalName(),
                'mimetype' => $file->getMimeType(),
                'filesize' => $file->getSize(),
            ]);

            CaseActivityLog::logActivity(
                $caseId,
                'attachment_added',
                "Attachment '{$attachment->filename}' uploaded",
                null,
                auth()->id()
            );

            return response()->json($attachment, 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to upload file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a single attachment
     */
    public function show($caseId, $id)
    {
        $case = CaseModel::findOrFail($caseId);
        $attachment = $case->attachments()->findOrFail($id);

        return response()->json($attachment);
    }

    /**
     * Update attachment metadata
     */
    public function update($caseId, $id, Request $request)
    {
        $case = CaseModel::findOrFail($caseId);
        $attachment = $case->attachments()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'label' => 'nullable|string|max:100',
            'type' => 'nullable|in:photo,stl,cbct,scan,other',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $attachment->update($request->only(['label', 'type']));

        return response()->json($attachment);
    }

    /**
     * Delete attachment
     */
    public function destroy($caseId, $id)
    {
        $case = CaseModel::findOrFail($caseId);
        $attachment = $case->attachments()->findOrFail($id);

        // Delete file from storage
        if ($attachment->local_path) {
            $this->fileStorage->deleteFile($attachment->local_path);
        }

        $filename = $attachment->filename;
        $attachment->delete();

        CaseActivityLog::logActivity(
            $caseId,
            'attachment_deleted',
            "Attachment '{$filename}' deleted",
            null,
            auth()->id()
        );

        return response()->json(['message' => 'Attachment deleted successfully']);
    }

    /**
     * Download attachment
     */
    public function download($caseId, $id)
    {
        $case = CaseModel::findOrFail($caseId);
        $attachment = $case->attachments()->findOrFail($id);

        if ($attachment->local_path && $this->fileStorage->fileExists($attachment->local_path)) {
            return Storage::disk('public')->download(
                $attachment->local_path,
                $attachment->filename
            );
        }

        if ($attachment->file_url) {
            // Redirect to Slack URL or proxy it
            return redirect($attachment->file_url);
        }

        return response()->json(['error' => 'File not found'], 404);
    }

    /**
     * Proxy Slack file (for private URLs)
     */
    public function proxy($id)
    {
        $attachment = CaseAttachment::findOrFail($id);

        if (!$attachment->file_url) {
            return response()->json(['error' => 'No file URL available'], 404);
        }

        try {
            $client = new \GuzzleHttp\Client([
                'headers' => [
                    'Authorization' => 'Bearer ' . config('services.slack.bot_token'),
                ],
            ]);

            $response = $client->get($attachment->file_url);

            return response($response->getBody())
                ->header('Content-Type', $attachment->mimetype ?? 'application/octet-stream')
                ->header('Content-Disposition', 'inline; filename="' . $attachment->filename . '"');

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch file'], 500);
        }
    }
}
