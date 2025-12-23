<?php
/**
 * URL Collector - HTTP Client with SSRF Protection
 *
 * Safe cURL wrapper that prevents SSRF attacks
 * Blocks private IPs, localhost, and dangerous protocols
 */

declare(strict_types=1);

class HttpClient
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Fetch URL content safely
     *
     * @return array{success: bool, html?: string, final_url?: string, http_code?: int, error?: string, headers?: array}
     */
    public function fetch(string $url): array
    {
        // Validate URL first
        $validation = $this->validateUrl($url);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'error'   => $validation['error'],
            ];
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => $this->config['max_redirects'] ?? 5,
            CURLOPT_TIMEOUT        => $this->config['timeout'] ?? 8,
            CURLOPT_CONNECTTIMEOUT => $this->config['connect_timeout'] ?? 5,
            CURLOPT_USERAGENT      => $this->config['user_agent'] ?? 'URLCollector/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HEADER         => true,
            CURLOPT_ENCODING       => '', // Accept all encodings
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7',
            ],
            // Security: Restrict protocols
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);

        if ($errno !== 0) {
            curl_close($ch);
            return [
                'success' => false,
                'error'   => "cURL error ({$errno}): {$error}",
            ];
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        curl_close($ch);

        // Validate final URL after redirects
        $finalValidation = $this->validateUrl($finalUrl);
        if (!$finalValidation['valid']) {
            return [
                'success' => false,
                'error'   => 'Redirect to blocked URL: ' . $finalValidation['error'],
            ];
        }

        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        if ($httpCode >= 400) {
            return [
                'success'   => false,
                'error'     => "HTTP error: {$httpCode}",
                'http_code' => $httpCode,
            ];
        }

        return [
            'success'   => true,
            'html'      => $body,
            'final_url' => $finalUrl,
            'http_code' => $httpCode,
            'headers'   => $this->parseHeaders($headers),
        ];
    }

    /**
     * Validate URL for safety (SSRF prevention)
     *
     * @return array{valid: bool, error?: string}
     */
    public function validateUrl(string $url): array
    {
        // Check URL format
        if (empty($url)) {
            return ['valid' => false, 'error' => 'Empty URL'];
        }

        // Check URL length
        if (strlen($url) > 2048) {
            return ['valid' => false, 'error' => 'URL too long'];
        }

        // Parse URL
        $parsed = parse_url($url);
        if ($parsed === false) {
            return ['valid' => false, 'error' => 'Invalid URL format'];
        }

        // Check scheme (only http and https allowed)
        $scheme = strtolower($parsed['scheme'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true)) {
            return ['valid' => false, 'error' => 'Invalid protocol. Only HTTP/HTTPS allowed'];
        }

        // Check host exists
        if (empty($parsed['host'])) {
            return ['valid' => false, 'error' => 'No host specified'];
        }

        $host = strtolower($parsed['host']);

        // Block localhost variations
        $localhostPatterns = [
            'localhost',
            'localhost.localdomain',
            '127.0.0.1',
            '0.0.0.0',
            '::1',
            '[::1]',
            '0000:0000:0000:0000:0000:0000:0000:0001',
        ];

        foreach ($localhostPatterns as $pattern) {
            if ($host === $pattern) {
                return ['valid' => false, 'error' => 'Localhost access blocked'];
            }
        }

        // Check for IP address
        $ip = filter_var($host, FILTER_VALIDATE_IP);
        if ($ip !== false) {
            if ($this->isPrivateIp($ip)) {
                return ['valid' => false, 'error' => 'Private IP access blocked'];
            }
        } else {
            // It's a hostname - resolve and check
            $resolvedIps = gethostbynamel($host);
            if ($resolvedIps === false) {
                // DNS resolution failed - allow but may fail on fetch
                // This is acceptable as the URL might be valid but temporarily unresolvable
            } else {
                foreach ($resolvedIps as $resolvedIp) {
                    if ($this->isPrivateIp($resolvedIp)) {
                        return ['valid' => false, 'error' => 'Domain resolves to private IP'];
                    }
                }
            }
        }

        // Block dangerous ports
        $port = $parsed['port'] ?? null;
        if ($port !== null) {
            $dangerousPorts = [21, 22, 23, 25, 53, 110, 143, 445, 3306, 5432, 6379, 27017];
            if (in_array((int)$port, $dangerousPorts, true)) {
                return ['valid' => false, 'error' => 'Dangerous port blocked'];
            }
        }

        // Block userinfo (user:pass@host)
        if (!empty($parsed['user']) || !empty($parsed['pass'])) {
            return ['valid' => false, 'error' => 'URL credentials not allowed'];
        }

        return ['valid' => true];
    }

    /**
     * Check if IP is private/reserved
     */
    private function isPrivateIp(string $ip): bool
    {
        // Use PHP's filter for common private ranges
        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

        if (filter_var($ip, FILTER_VALIDATE_IP, $flags) === false) {
            return true;
        }

        // Additional checks for edge cases
        $privateRanges = [
            // Loopback
            ['127.0.0.0', '127.255.255.255'],
            // Private Class A
            ['10.0.0.0', '10.255.255.255'],
            // Private Class B
            ['172.16.0.0', '172.31.255.255'],
            // Private Class C
            ['192.168.0.0', '192.168.255.255'],
            // Link-local
            ['169.254.0.0', '169.254.255.255'],
            // CGNAT
            ['100.64.0.0', '100.127.255.255'],
            // Localhost
            ['0.0.0.0', '0.255.255.255'],
        ];

        $ipLong = ip2long($ip);
        if ($ipLong === false) {
            // IPv6 - be conservative and check common private prefixes
            $ipLower = strtolower($ip);
            $ipv6Private = [
                'fe80:', // Link-local
                'fc00:', // Unique local
                'fd00:', // Unique local
                '::1',   // Loopback
                '::ffff:127.', // IPv4-mapped loopback
                '::ffff:10.',  // IPv4-mapped private
                '::ffff:192.168.',
                '::ffff:172.16.',
            ];

            foreach ($ipv6Private as $prefix) {
                if (str_starts_with($ipLower, $prefix)) {
                    return true;
                }
            }

            return false;
        }

        foreach ($privateRanges as [$start, $end]) {
            $startLong = ip2long($start);
            $endLong = ip2long($end);
            if ($ipLong >= $startLong && $ipLong <= $endLong) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse HTTP headers into array
     */
    private function parseHeaders(string $headerString): array
    {
        $headers = [];
        $lines = explode("\r\n", $headerString);

        foreach ($lines as $line) {
            if (str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($key))] = trim($value);
            }
        }

        return $headers;
    }

    /**
     * Make a POST request (for API calls)
     *
     * @return array{success: bool, body?: string, http_code?: int, error?: string}
     */
    public function post(string $url, array $data, array $headers = []): array
    {
        $ch = curl_init();

        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

        $defaultHeaders = [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData),
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonData,
            CURLOPT_TIMEOUT        => $this->config['timeout'] ?? 30,
            CURLOPT_CONNECTTIMEOUT => $this->config['connect_timeout'] ?? 10,
            CURLOPT_HTTPHEADER     => array_merge($defaultHeaders, $headers),
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

        return [
            'success'   => true,
            'body'      => $response,
            'http_code' => $httpCode,
        ];
    }
}
