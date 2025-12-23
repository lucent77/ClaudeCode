<?php

namespace SwissTurn\Services;

/**
 * Gemini API Service for CNC Code Analysis
 */
class GeminiService
{
    private array $config;
    private string $apiKey;
    private string $apiUrl;
    private string $model;

    public function __construct()
    {
        $this->config = require BASE_PATH . '/config/gemini.php';
        $this->apiKey = $this->config['api_key'];
        $this->apiUrl = $this->config['api_url'];
        $this->model = $this->config['model'];
    }

    /**
     * Check if API key is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Analyze CNC program
     */
    public function analyzeProgram(string $code, string $machineType, string $controllerType): array
    {
        $prompt = $this->buildPrompt('analyze', [
            'code' => $code,
            'machine_type' => $machineType,
            'controller_type' => $controllerType,
        ]);

        return $this->generateContent($prompt);
    }

    /**
     * Explain a specific line
     */
    public function explainLine(string $line, string $context, string $machineType, string $controllerType): array
    {
        $prompt = $this->buildPrompt('explain_line', [
            'line' => $line,
            'context' => $context,
            'machine_type' => $machineType,
            'controller_type' => $controllerType,
        ]);

        return $this->generateContent($prompt);
    }

    /**
     * Suggest optimizations
     */
    public function optimizeProgram(string $code, string $machineType, string $controllerType): array
    {
        $prompt = $this->buildPrompt('optimize', [
            'code' => $code,
            'machine_type' => $machineType,
            'controller_type' => $controllerType,
        ]);

        return $this->generateContent($prompt);
    }

    /**
     * Detect potential errors
     */
    public function detectErrors(string $code, string $machineType, string $controllerType): array
    {
        $prompt = $this->buildPrompt('detect_errors', [
            'code' => $code,
            'machine_type' => $machineType,
            'controller_type' => $controllerType,
        ]);

        return $this->generateContent($prompt);
    }

    /**
     * Custom prompt for flexible analysis
     */
    public function customAnalysis(string $prompt, string $code, string $machineType, string $controllerType): array
    {
        $fullPrompt = "Machine: {$machineType} ({$controllerType})\n\n";
        $fullPrompt .= "CNC Program:\n```\n{$code}\n```\n\n";
        $fullPrompt .= "Request: {$prompt}";

        return $this->generateContent($fullPrompt);
    }

    /**
     * Build prompt from template
     */
    private function buildPrompt(string $type, array $variables): string
    {
        $template = $this->config['prompts'][$type] ?? '';

        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }

        return $template;
    }

    /**
     * Call Gemini API to generate content
     */
    private function generateContent(string $prompt): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Gemini API key not configured',
            ];
        }

        $url = "{$this->apiUrl}/{$this->model}:generateContent?key={$this->apiKey}";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => $this->config['generation_config'],
            'safetySettings' => $this->config['safety_settings'],
        ];

        try {
            $response = $this->httpPost($url, $payload);

            if (isset($response['error'])) {
                return [
                    'success' => false,
                    'error' => $response['error']['message'] ?? 'Unknown API error',
                ];
            }

            // Extract text from response
            $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';

            return [
                'success' => true,
                'content' => $text,
                'usage' => $response['usageMetadata'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * HTTP POST request using cURL
     */
    private function httpPost(string $url, array $data): array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config['timeout'],
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($error) {
            throw new \Exception("cURL error: {$error}");
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON response from API");
        }

        return $decoded;
    }

    /**
     * Format analysis result to HTML
     */
    public function formatToHtml(string $content): string
    {
        // Convert markdown-style formatting to HTML
        $html = htmlspecialchars($content);

        // Headers
        $html = preg_replace('/^### (.+)$/m', '<h4 class="text-lg font-semibold mt-4 mb-2 text-cnc-highlight">$1</h4>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h3 class="text-xl font-bold mt-6 mb-3 text-white">$1</h3>', $html);
        $html = preg_replace('/^# (.+)$/m', '<h2 class="text-2xl font-bold mt-6 mb-4 text-white">$1</h2>', $html);

        // Bold
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong class="text-white">$1</strong>', $html);

        // Code blocks
        $html = preg_replace('/```(\w+)?\n(.*?)```/s', '<pre class="bg-cnc-dark p-4 rounded-lg my-4 overflow-x-auto"><code>$2</code></pre>', $html);

        // Inline code
        $html = preg_replace('/`([^`]+)`/', '<code class="bg-cnc-dark px-2 py-1 rounded text-cnc-highlight">$1</code>', $html);

        // Lists
        $html = preg_replace('/^- (.+)$/m', '<li class="ml-4">$1</li>', $html);
        $html = preg_replace('/^(\d+)\. (.+)$/m', '<li class="ml-4"><span class="text-cnc-highlight">$1.</span> $2</li>', $html);

        // Paragraphs
        $html = preg_replace('/\n\n/', '</p><p class="my-3">', $html);

        // Line breaks
        $html = nl2br($html);

        return '<div class="ai-analysis-content">' . $html . '</div>';
    }
}
