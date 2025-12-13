<?php

namespace App\Jobs;

use App\Models\GsyncQueue;
use App\Models\GsheetLink;
use App\Models\CaseModel;
use App\Services\GoogleSheetsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessGoogleSheetSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public GsyncQueue $queueItem;
    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(GsyncQueue $queueItem)
    {
        $this->queueItem = $queueItem;
    }

    public function handle(GoogleSheetsService $sheetsService): void
    {
        $this->queueItem->markProcessing();

        try {
            $link = $this->queueItem->gsheetLink;

            if (!$link) {
                throw new \Exception('Google Sheet link not found');
            }

            $payload = $this->queueItem->payload;

            if ($this->queueItem->direction === 'PULL') {
                $this->processPull($sheetsService, $link, $payload);
            } else {
                $this->processPush($sheetsService, $link, $payload);
            }

            // Update last sync time
            $link->update(['last_sync_at' => now()]);

            $this->queueItem->markCompleted();

        } catch (\Exception $e) {
            Log::error('Google Sheet sync failed', [
                'queue_id' => $this->queueItem->id,
                'error' => $e->getMessage(),
            ]);

            $this->queueItem->markFailed($e->getMessage());

            if ($this->queueItem->canRetry()) {
                $this->release($this->backoff);
            }
        }
    }

    protected function processPull(GoogleSheetsService $sheetsService, GsheetLink $link, array $payload): void
    {
        $rows = $sheetsService->getSheetData(
            $payload['sheet_id'],
            $payload['tab_name']
        );

        $mapping = $payload['mapping'];
        $keyColumn = $mapping['key_column'] ?? 'case_number';
        $columnMap = $mapping['columns'] ?? [];

        foreach ($rows as $row) {
            $keyValue = $row[$keyColumn] ?? null;

            if (!$keyValue) {
                continue;
            }

            // Find or create case
            $case = CaseModel::where('case_number', $keyValue)->first();

            if (!$case) {
                // Create new case from row data
                $caseData = $this->mapRowToCase($row, $columnMap);
                $caseData['case_number'] = $keyValue;
                $case = CaseModel::create($caseData);
            } else {
                // Update existing case (only if not recently edited)
                if ($case->updated_at && $case->updated_at->diffInMinutes(now()) < 2) {
                    continue; // Skip - DB wins if edited in last 2 minutes
                }

                $caseData = $this->mapRowToCase($row, $columnMap);
                $case->update($caseData);
            }
        }
    }

    protected function processPush(GoogleSheetsService $sheetsService, GsheetLink $link, array $payload): void
    {
        $mapping = $payload['mapping'];
        $columnMap = $mapping['columns'] ?? [];

        // Get cases to push
        $cases = CaseModel::where('updated_at', '>', $link->last_sync_at ?? now()->subDay())
            ->get();

        $rows = [];

        foreach ($cases as $case) {
            $row = [];
            foreach ($columnMap as $sheetColumn => $dbColumn) {
                $row[$sheetColumn] = $case->{$dbColumn} ?? '';
            }
            $rows[] = $row;
        }

        if (!empty($rows)) {
            $sheetsService->updateSheetData(
                $payload['sheet_id'],
                $payload['tab_name'],
                $rows
            );
        }
    }

    protected function mapRowToCase(array $row, array $columnMap): array
    {
        $caseData = [];

        foreach ($columnMap as $sheetColumn => $dbColumn) {
            if (isset($row[$sheetColumn])) {
                $caseData[$dbColumn] = $row[$sheetColumn];
            }
        }

        return $caseData;
    }
}
