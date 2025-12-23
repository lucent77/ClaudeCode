<?php
/**
 * URL Collector - Gemini API Client
 *
 * REST-based integration with Google's Gemini API
 * Returns JSON-only responses for parsing stability
 */

declare(strict_types=1);

class GeminiClient
{
    private string $apiKey;
    private string $model;
    private string $endpoint;
    private int $maxTokens;
    private float $temperature;
    private array $validCategories;
    private int $timeout;

    public function __construct(array $config, array $validCategories = [])
    {
        $this->apiKey = $config['api_key'] ?? '';
        $this->model = $config['model'] ?? 'gemini-1.5-flash';
        $this->endpoint = $config['endpoint'] ?? 'https://generativelanguage.googleapis.com/v1beta/models/';
        $this->maxTokens = $config['max_tokens'] ?? 1024;
        $this->temperature = $config['temperature'] ?? 0.3;
        $this->timeout = $config['timeout'] ?? 30;
        $this->validCategories = $validCategories;

        if (empty($this->apiKey)) {
            throw new InvalidArgumentException('Gemini API key is required');
        }
    }

    /**
     * Analyze content and return structured data
     *
     * @return array{success: bool, data?: array, error?: string}
     */
    public function analyze(string $url, string $domain, string $title, string $text): array
    {
        // Prepare the prompt
        $categoriesStr = implode(', ', $this->validCategories);

        $prompt = $this->buildPrompt($url, $domain, $title, $text, $categoriesStr);

        // Call API
        $response = $this->callApi($prompt);

        if (!$response['success']) {
            return $response;
        }

        // Parse JSON from response
        $parsed = $this->parseResponse($response['text']);

        if (!$parsed['success']) {
            return $parsed;
        }

        // Validate and normalize the data
        return $this->validateData($parsed['data']);
    }

    /**
     * Build the analysis prompt
     */
    private function buildPrompt(string $url, string $domain, string $title, string $text, string $categories): string
    {
        // Truncate text if too long (Gemini context limit consideration)
        $maxTextForPrompt = 6000;
        if (mb_strlen($text) > $maxTextForPrompt) {
            $text = mb_substr($text, 0, $maxTextForPrompt) . '...';
        }

        return <<<PROMPT
Analyze this web content and respond with ONLY valid JSON. No markdown, no explanations, just JSON.

URL: {$url}
Domain: {$domain}
Title: {$title}

Content:
{$text}

---

Instructions:
1. Choose exactly ONE category from: {$categories}
2. Write a concise summary in 2-3 sentences (in Korean if the content is Korean, otherwise in English)
3. Extract exactly 5 relevant keywords (in the content's language)
4. Estimate an auto_score from 0-100 based on:
   - Information value and educational worth
   - Practical usefulness for work or learning
   - Content quality and reliability
   - Uniqueness and depth of information

Required JSON format (output ONLY this, nothing else):
{
  "category": "one_of_the_categories",
  "summary": "2-3 sentence summary",
  "keywords": ["keyword1", "keyword2", "keyword3", "keyword4", "keyword5"],
  "auto_score": 75
}
PROMPT;
    }

    /**
     * Call Gemini API
     *
     * @return array{success: bool, text?: string, error?: string}
     */
    private function callApi(string $prompt): array
    {
        $url = $this->endpoint . $this->model . ':generateContent';

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $this->temperature,
                'maxOutputTokens' => $this->maxTokens,
                'responseMimeType' => 'application/json',
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
                ],
            ],
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $this->apiKey,
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($errno !== 0) {
            return [
                'success' => false,
                'error'   => "cURL error ({$errno}): {$error}",
            ];
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? "HTTP {$httpCode}";
            return [
                'success' => false,
                'error'   => "Gemini API error: {$errorMsg}",
            ];
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error'   => 'Failed to decode Gemini response',
            ];
        }

        // Extract text from response
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (empty($text)) {
            // Check for safety block
            $finishReason = $data['candidates'][0]['finishReason'] ?? '';
            if ($finishReason === 'SAFETY') {
                return [
                    'success' => false,
                    'error'   => 'Content blocked by safety filters',
                ];
            }

            return [
                'success' => false,
                'error'   => 'Empty response from Gemini',
            ];
        }

        return [
            'success' => true,
            'text'    => $text,
        ];
    }

    /**
     * Parse JSON from response text
     *
     * @return array{success: bool, data?: array, error?: string}
     */
    private function parseResponse(string $text): array
    {
        // Try direct JSON parse first
        $data = json_decode($text, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return ['success' => true, 'data' => $data];
        }

        // Try to extract JSON from markdown code block
        if (preg_match('/```(?:json)?\s*(\{[\s\S]*?\})\s*```/', $text, $matches)) {
            $data = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                return ['success' => true, 'data' => $data];
            }
        }

        // Try to find JSON object in text
        if (preg_match('/\{[\s\S]*"category"[\s\S]*"summary"[\s\S]*"keywords"[\s\S]*\}/', $text, $matches)) {
            $data = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                return ['success' => true, 'data' => $data];
            }
        }

        return [
            'success' => false,
            'error'   => 'Failed to parse JSON from response: ' . substr($text, 0, 200),
        ];
    }

    /**
     * Validate and normalize the parsed data
     *
     * @return array{success: bool, data?: array, error?: string}
     */
    private function validateData(array $data): array
    {
        $result = [
            'category'   => 'other',
            'summary'    => '',
            'keywords'   => [],
            'auto_score' => 50,
        ];

        // Validate category
        if (isset($data['category']) && is_string($data['category'])) {
            $category = strtolower(trim($data['category']));
            if (in_array($category, $this->validCategories, true)) {
                $result['category'] = $category;
            }
        }

        // Validate summary
        if (isset($data['summary']) && is_string($data['summary'])) {
            $result['summary'] = trim($data['summary']);

            // Limit length
            if (mb_strlen($result['summary']) > 1000) {
                $result['summary'] = mb_substr($result['summary'], 0, 1000);
            }
        }

        // Validate keywords
        if (isset($data['keywords']) && is_array($data['keywords'])) {
            $keywords = [];
            foreach ($data['keywords'] as $keyword) {
                if (is_string($keyword)) {
                    $keyword = trim($keyword);
                    if (!empty($keyword) && mb_strlen($keyword) <= 50) {
                        $keywords[] = $keyword;
                    }
                }
            }
            // Take max 5 keywords
            $result['keywords'] = array_slice($keywords, 0, 5);
        }

        // Validate auto_score
        if (isset($data['auto_score'])) {
            $score = (int) $data['auto_score'];
            $result['auto_score'] = max(0, min(100, $score));
        }

        return [
            'success' => true,
            'data'    => $result,
        ];
    }

    /**
     * Test API connection
     */
    public function testConnection(): array
    {
        $url = $this->endpoint . $this->model;

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                'x-goog-api-key: ' . $this->apiKey,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return ['success' => true, 'message' => 'Gemini API connection successful'];
        }

        return [
            'success' => false,
            'error'   => "API test failed with HTTP {$httpCode}",
        ];
    }
}
