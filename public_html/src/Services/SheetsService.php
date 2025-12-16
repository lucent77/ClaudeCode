<?php
/**
 * Google Sheets Service
 *
 * Handles Google Sheets API operations for case tracking
 */

class SheetsService
{
    private $client;
    private $service;
    private $credentialsPath;

    // Color codes for status highlighting
    const COLORS = [
        'Confirmed' => [
            'red' => 0.56,
            'green' => 0.93,
            'blue' => 0.56,
        ], // Light green
        'Action Needed' => [
            'red' => 1.0,
            'green' => 0.6,
            'blue' => 0.6,
        ], // Light red
        'Confirmed with Action Needed' => [
            'red' => 1.0,
            'green' => 0.85,
            'blue' => 0.4,
        ], // Light orange
        'Pending' => [
            'red' => 1.0,
            'green' => 1.0,
            'blue' => 0.6,
        ], // Light yellow
        'Resolved' => [
            'red' => 0.8,
            'green' => 0.8,
            'blue' => 0.8,
        ], // Light gray
    ];

    public function __construct()
    {
        $this->credentialsPath = env('GOOGLE_SHEETS_CREDENTIALS');

        if (empty($this->credentialsPath) || !file_exists($this->credentialsPath)) {
            $this->log('Google Sheets credentials not configured or file not found', 'warning');
        }
    }

    /**
     * Initialize Google Sheets client
     */
    private function initClient()
    {
        if ($this->client) {
            return;
        }

        if (!class_exists('Google_Client')) {
            throw new Exception('Google API Client library not installed');
        }

        $this->client = new Google_Client();
        $this->client->setApplicationName('Design Confirm System');
        $this->client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
        $this->client->setAuthConfig($this->credentialsPath);

        $this->service = new Google_Service_Sheets($this->client);
    }

    /**
     * Find a case in a spreadsheet by case number
     *
     * @param string $spreadsheetId The spreadsheet ID
     * @param string $caseNumber The case number to find
     * @param string $caseColumn Column containing case numbers (e.g., 'A')
     * @param string $sheetName Sheet name (default: first sheet)
     * @return array|null Row data if found
     */
    public function findCase($spreadsheetId, $caseNumber, $caseColumn = 'A', $sheetName = '')
    {
        $this->initClient();

        try {
            // Get all values from the case column
            $range = !empty($sheetName) ? "{$sheetName}!{$caseColumn}:{$caseColumn}" : "{$caseColumn}:{$caseColumn}";
            $response = $this->service->spreadsheets_values->get($spreadsheetId, $range);
            $values = $response->getValues();

            if (empty($values)) {
                return null;
            }

            // Find the row with the matching case number
            foreach ($values as $rowIndex => $row) {
                if (!empty($row[0]) && $this->matchCaseNumber($row[0], $caseNumber)) {
                    $rowNumber = $rowIndex + 1; // Sheets are 1-indexed

                    // Get full row data
                    $fullRange = !empty($sheetName) ? "{$sheetName}!{$rowNumber}:{$rowNumber}" : "{$rowNumber}:{$rowNumber}";
                    $fullRow = $this->service->spreadsheets_values->get($spreadsheetId, $fullRange);

                    return [
                        'row_number' => $rowNumber,
                        'row_index' => $rowIndex,
                        'data' => $fullRow->getValues()[0] ?? [],
                    ];
                }
            }

            return null;

        } catch (Exception $e) {
            $this->log("Error finding case in sheet: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Update row color based on case status
     *
     * @param string $spreadsheetId The spreadsheet ID
     * @param int $rowNumber The row number to update
     * @param string $status The case status
     * @param string $sheetName Sheet name (optional)
     * @return bool
     */
    public function updateRowColor($spreadsheetId, $rowNumber, $status, $sheetName = '')
    {
        $this->initClient();

        try {
            // Get sheet ID
            $sheetId = $this->getSheetId($spreadsheetId, $sheetName);

            // Get color for status
            $color = self::COLORS[$status] ?? self::COLORS['Pending'];

            // Create the request
            $requests = [
                new Google_Service_Sheets_Request([
                    'repeatCell' => [
                        'range' => [
                            'sheetId' => $sheetId,
                            'startRowIndex' => $rowNumber - 1,
                            'endRowIndex' => $rowNumber,
                        ],
                        'cell' => [
                            'userEnteredFormat' => [
                                'backgroundColor' => $color,
                            ],
                        ],
                        'fields' => 'userEnteredFormat.backgroundColor',
                    ],
                ]),
            ];

            $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
                'requests' => $requests,
            ]);

            $this->service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);

            $this->log("Updated row {$rowNumber} color to {$status}");
            return true;

        } catch (Exception $e) {
            $this->log("Error updating row color: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Update a cell value
     *
     * @param string $spreadsheetId The spreadsheet ID
     * @param string $range Cell range (e.g., 'A1' or 'Sheet1!A1')
     * @param mixed $value The value to set
     * @return bool
     */
    public function updateCell($spreadsheetId, $range, $value)
    {
        $this->initClient();

        try {
            $body = new Google_Service_Sheets_ValueRange([
                'values' => [[$value]],
            ]);

            $params = ['valueInputOption' => 'USER_ENTERED'];

            $this->service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);

            return true;

        } catch (Exception $e) {
            $this->log("Error updating cell: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Add a note to a cell
     *
     * @param string $spreadsheetId The spreadsheet ID
     * @param int $rowNumber Row number
     * @param int $colNumber Column number (1-indexed)
     * @param string $note The note content
     * @param string $sheetName Sheet name (optional)
     * @return bool
     */
    public function addNote($spreadsheetId, $rowNumber, $colNumber, $note, $sheetName = '')
    {
        $this->initClient();

        try {
            $sheetId = $this->getSheetId($spreadsheetId, $sheetName);

            $requests = [
                new Google_Service_Sheets_Request([
                    'updateCells' => [
                        'range' => [
                            'sheetId' => $sheetId,
                            'startRowIndex' => $rowNumber - 1,
                            'endRowIndex' => $rowNumber,
                            'startColumnIndex' => $colNumber - 1,
                            'endColumnIndex' => $colNumber,
                        ],
                        'rows' => [
                            [
                                'values' => [
                                    [
                                        'note' => $note,
                                    ],
                                ],
                            ],
                        ],
                        'fields' => 'note',
                    ],
                ]),
            ];

            $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
                'requests' => $requests,
            ]);

            $this->service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);

            return true;

        } catch (Exception $e) {
            $this->log("Error adding note: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Get sheet ID by name
     */
    private function getSheetId($spreadsheetId, $sheetName = '')
    {
        $spreadsheet = $this->service->spreadsheets->get($spreadsheetId);
        $sheets = $spreadsheet->getSheets();

        if (empty($sheetName)) {
            // Return first sheet ID
            return $sheets[0]->getProperties()->getSheetId();
        }

        foreach ($sheets as $sheet) {
            if ($sheet->getProperties()->getTitle() === $sheetName) {
                return $sheet->getProperties()->getSheetId();
            }
        }

        // Default to first sheet if not found
        return $sheets[0]->getProperties()->getSheetId();
    }

    /**
     * Match case number (handles different formats)
     */
    private function matchCaseNumber($cellValue, $caseNumber)
    {
        // Normalize both values (remove spaces, special characters)
        $normalizedCell = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($cellValue));
        $normalizedCase = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($caseNumber));

        return $normalizedCell === $normalizedCase;
    }

    /**
     * Get all data from a range
     *
     * @param string $spreadsheetId The spreadsheet ID
     * @param string $range The range (e.g., 'A1:Z100')
     * @return array
     */
    public function getRange($spreadsheetId, $range)
    {
        $this->initClient();

        try {
            $response = $this->service->spreadsheets_values->get($spreadsheetId, $range);
            return $response->getValues() ?? [];

        } catch (Exception $e) {
            $this->log("Error getting range: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Append a row to a sheet
     *
     * @param string $spreadsheetId The spreadsheet ID
     * @param array $values Row values
     * @param string $range Range to append to (e.g., 'Sheet1!A:Z')
     * @return bool
     */
    public function appendRow($spreadsheetId, $values, $range = 'A:Z')
    {
        $this->initClient();

        try {
            $body = new Google_Service_Sheets_ValueRange([
                'values' => [$values],
            ]);

            $params = [
                'valueInputOption' => 'USER_ENTERED',
                'insertDataOption' => 'INSERT_ROWS',
            ];

            $this->service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);

            return true;

        } catch (Exception $e) {
            $this->log("Error appending row: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Log Sheets service activity
     */
    private function log($message, $level = 'info')
    {
        $logFile = dirname(dirname(__DIR__)) . '/logs/sheets.log';
        $timestamp = date('Y-m-d H:i:s');

        $logMessage = "[{$timestamp}] [{$level}] {$message}\n";

        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}
