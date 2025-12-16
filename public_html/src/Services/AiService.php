<?php
/**
 * AI Service
 *
 * Handles AI-powered email analysis using Google Gemini API
 */

class AiService
{
    private $apiKey;
    private $model;
    private $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->model = env('GEMINI_MODEL', 'gemini-1.5-flash');

        if (empty($this->apiKey)) {
            throw new Exception('GEMINI_API_KEY is not configured');
        }
    }

    /**
     * Analyze email content to determine case status
     *
     * @param string $emailContent The email body content
     * @param array $context Additional context (case number, patient name, etc.)
     * @return array Analysis result with status and confidence
     */
    public function analyzeEmail($emailContent, $context = [])
    {
        $prompt = $this->buildAnalysisPrompt($emailContent, $context);

        try {
            $response = $this->generateContent($prompt);
            return $this->parseAnalysisResponse($response);
        } catch (Exception $e) {
            $this->log("Email analysis failed: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Build the prompt for email analysis
     */
    private function buildAnalysisPrompt($emailContent, $context = [])
    {
        $contextInfo = '';
        if (!empty($context)) {
            $contextInfo = "\nContext Information:\n";
            foreach ($context as $key => $value) {
                $contextInfo .= "- {$key}: {$value}\n";
            }
        }

        $prompt = <<<PROMPT
You are an AI assistant analyzing dental laboratory emails. Your task is to determine the status of a dental case based on the email content.

Analyze the following email and determine if the doctor/dentist:
1. CONFIRMED - Approved the design without any changes
2. ACTION_NEEDED - Requested modifications or changes to the design
3. CONFIRMED_WITH_ACTION_NEEDED - Approved the design but requested minor adjustments or noted small issues

Important Guidelines:
- Look for keywords like "approve", "looks good", "proceed", "confirmed" for CONFIRMED status
- Look for keywords like "change", "modify", "adjust", "redo", "not right" for ACTION_NEEDED status
- If they approve but mention minor things like "looks great but can you..." or "approved, just note that...", use CONFIRMED_WITH_ACTION_NEEDED
- If the email is unclear or doesn't relate to design confirmation, default to ACTION_NEEDED
{$contextInfo}
Email Content:
---
{$emailContent}
---

Respond in the following JSON format only:
{
    "status": "CONFIRMED" | "ACTION_NEEDED" | "CONFIRMED_WITH_ACTION_NEEDED",
    "confidence": 0.0-1.0,
    "reasoning": "Brief explanation of why this status was chosen",
    "key_phrases": ["relevant", "phrases", "from", "email"],
    "action_items": ["list of action items if any"]
}
PROMPT;

        return $prompt;
    }

    /**
     * Generate content using Gemini API
     */
    private function generateContent($prompt, $options = [])
    {
        $url = $this->baseUrl . $this->model . ':generateContent?key=' . $this->apiKey;

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $options['temperature'] ?? 0.3,
                'topK' => $options['topK'] ?? 40,
                'topP' => $options['topP'] ?? 0.95,
                'maxOutputTokens' => $options['maxTokens'] ?? 1024,
            ],
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_NONE'
                ],
                [
                    'category' => 'HARM_CATEGORY_HATE_SPEECH',
                    'threshold' => 'BLOCK_NONE'
                ],
                [
                    'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                    'threshold' => 'BLOCK_NONE'
                ],
                [
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_NONE'
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Gemini API request failed: {$error}");
        }

        if ($httpCode !== 200) {
            $responseData = json_decode($response, true);
            $errorMessage = $responseData['error']['message'] ?? 'Unknown error';
            throw new Exception("Gemini API error ({$httpCode}): {$errorMessage}");
        }

        $data = json_decode($response, true);

        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception('Invalid response from Gemini API');
        }

        return $data['candidates'][0]['content']['parts'][0]['text'];
    }

    /**
     * Parse the analysis response
     */
    private function parseAnalysisResponse($response)
    {
        // Try to extract JSON from the response
        $jsonMatch = [];
        if (preg_match('/\{[\s\S]*\}/', $response, $jsonMatch)) {
            $parsed = json_decode($jsonMatch[0], true);

            if (json_last_error() === JSON_ERROR_NONE && isset($parsed['status'])) {
                // Map status to database enum values
                $statusMap = [
                    'CONFIRMED' => 'Confirmed',
                    'ACTION_NEEDED' => 'Action Needed',
                    'CONFIRMED_WITH_ACTION_NEEDED' => 'Confirmed with Action Needed',
                ];

                $status = strtoupper($parsed['status']);
                $mappedStatus = $statusMap[$status] ?? 'Action Needed';

                return [
                    'status' => $mappedStatus,
                    'confidence' => floatval($parsed['confidence'] ?? 0.5),
                    'reasoning' => $parsed['reasoning'] ?? '',
                    'key_phrases' => $parsed['key_phrases'] ?? [],
                    'action_items' => $parsed['action_items'] ?? [],
                    'raw_response' => $response,
                ];
            }
        }

        // Fallback: try to determine status from text
        $response = strtolower($response);

        if (strpos($response, 'confirmed') !== false && strpos($response, 'action') !== false) {
            $status = 'Confirmed with Action Needed';
        } elseif (strpos($response, 'confirmed') !== false) {
            $status = 'Confirmed';
        } else {
            $status = 'Action Needed';
        }

        return [
            'status' => $status,
            'confidence' => 0.5,
            'reasoning' => 'Parsed from unstructured response',
            'key_phrases' => [],
            'action_items' => [],
            'raw_response' => $response,
        ];
    }

    /**
     * Extract case number from email content
     */
    public function extractCaseNumber($emailContent)
    {
        // Common case number patterns
        $patterns = [
            '/case\s*#?\s*(\d{4,}-\d+)/i',           // Case #2025-12345
            '/case\s*number[:\s]*(\d{4,}-\d+)/i',   // Case number: 2025-12345
            '/reference[:\s]*(\d{4,}-\d+)/i',       // Reference: 2025-12345
            '/ref[:\s#]*(\d{4,}-\d+)/i',            // Ref: 2025-12345 or Ref#2025-12345
            '/(\d{4}-\d{5,})/i',                     // 2025-12345 (year-number format)
            '/case[:\s]*([A-Z]{2,3}\d{4,})/i',      // Case: NY12345
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $emailContent, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Extract patient name from email content
     */
    public function extractPatientName($emailContent)
    {
        $patterns = [
            '/patient[:\s]+([A-Za-z]+[\s]+[A-Za-z]+)/i',
            '/pt[:\s]+([A-Za-z]+[\s]+[A-Za-z]+)/i',
            '/for\s+patient\s+([A-Za-z]+[\s]+[A-Za-z]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $emailContent, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    /**
     * General text generation
     */
    public function generateText($prompt, $options = [])
    {
        return $this->generateContent($prompt, $options);
    }

    /**
     * Log AI service activity
     */
    private function log($message, $level = 'info')
    {
        $logFile = dirname(dirname(__DIR__)) . '/logs/ai.log';
        $timestamp = date('Y-m-d H:i:s');

        $logMessage = "[{$timestamp}] [{$level}] {$message}\n";

        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}
