<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Log;

class GoogleSheetsService
{
    protected ?Client $client = null;
    protected ?Sheets $service = null;

    public function __construct()
    {
        $this->initializeClient();
    }

    protected function initializeClient(): void
    {
        $credentialsPath = config('services.google.credentials_path',
            storage_path('google-credentials.json')
        );

        if (!file_exists($credentialsPath)) {
            Log::warning('Google credentials file not found', ['path' => $credentialsPath]);
            return;
        }

        try {
            $this->client = new Client();
            $this->client->setAuthConfig($credentialsPath);
            $this->client->setScopes([Sheets::SPREADSHEETS]);
            $this->client->setAccessType('offline');

            $this->service = new Sheets($this->client);
        } catch (\Exception $e) {
            Log::error('Failed to initialize Google Sheets client', ['error' => $e->getMessage()]);
        }
    }

    public function isAvailable(): bool
    {
        return $this->service !== null;
    }

    public function getSheetData(string $spreadsheetId, string $range): array
    {
        if (!$this->isAvailable()) {
            throw new \Exception('Google Sheets service not available');
        }

        try {
            $response = $this->service->spreadsheets_values->get($spreadsheetId, $range);
            $values = $response->getValues();

            if (empty($values)) {
                return [];
            }

            // First row is headers
            $headers = array_shift($values);
            $rows = [];

            foreach ($values as $row) {
                $rowData = [];
                foreach ($headers as $index => $header) {
                    $rowData[$header] = $row[$index] ?? null;
                }
                $rows[] = $rowData;
            }

            return $rows;

        } catch (\Exception $e) {
            Log::error('Failed to get sheet data', [
                'spreadsheetId' => $spreadsheetId,
                'range' => $range,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function updateSheetData(string $spreadsheetId, string $range, array $rows): void
    {
        if (!$this->isAvailable()) {
            throw new \Exception('Google Sheets service not available');
        }

        try {
            $values = [];

            // Add headers from first row keys
            if (!empty($rows)) {
                $values[] = array_keys($rows[0]);
            }

            // Add data rows
            foreach ($rows as $row) {
                $values[] = array_values($row);
            }

            $body = new \Google\Service\Sheets\ValueRange([
                'values' => $values,
            ]);

            $params = [
                'valueInputOption' => 'USER_ENTERED',
            ];

            $this->service->spreadsheets_values->update(
                $spreadsheetId,
                $range,
                $body,
                $params
            );

        } catch (\Exception $e) {
            Log::error('Failed to update sheet data', [
                'spreadsheetId' => $spreadsheetId,
                'range' => $range,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function appendSheetData(string $spreadsheetId, string $range, array $rows): void
    {
        if (!$this->isAvailable()) {
            throw new \Exception('Google Sheets service not available');
        }

        try {
            $values = [];

            foreach ($rows as $row) {
                $values[] = array_values($row);
            }

            $body = new \Google\Service\Sheets\ValueRange([
                'values' => $values,
            ]);

            $params = [
                'valueInputOption' => 'USER_ENTERED',
                'insertDataOption' => 'INSERT_ROWS',
            ];

            $this->service->spreadsheets_values->append(
                $spreadsheetId,
                $range,
                $body,
                $params
            );

        } catch (\Exception $e) {
            Log::error('Failed to append sheet data', [
                'spreadsheetId' => $spreadsheetId,
                'range' => $range,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function getSpreadsheetInfo(string $spreadsheetId): array
    {
        if (!$this->isAvailable()) {
            throw new \Exception('Google Sheets service not available');
        }

        try {
            $spreadsheet = $this->service->spreadsheets->get($spreadsheetId);

            return [
                'title' => $spreadsheet->getProperties()->getTitle(),
                'sheets' => array_map(function ($sheet) {
                    return [
                        'id' => $sheet->getProperties()->getSheetId(),
                        'title' => $sheet->getProperties()->getTitle(),
                    ];
                }, $spreadsheet->getSheets()),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get spreadsheet info', [
                'spreadsheetId' => $spreadsheetId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
