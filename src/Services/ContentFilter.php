<?php
/**
 * Content Filter Service
 *
 * Analyzes content for profanity, PII, and other policy violations.
 */

declare(strict_types=1);

namespace App\Services;

class ContentFilter
{
    // Profanity patterns (basic list - extend as needed)
    private array $profanityPatterns = [
        '/\bf+u+c+k+\w*/i',
        '/\bs+h+i+t+\w*/i',
        '/\ba+s+s+h+o+l+e*/i',
        '/\bb+i+t+c+h+\w*/i',
        '/\bd+a+m+n+\w*/i',
        '/\bc+r+a+p+\w*/i',
    ];

    // PII patterns
    private array $piiPatterns = [
        'pii_email' => '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/i',
        'pii_phone' => '/(\+?1[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/i',
        'pii_ssn' => '/\b\d{3}[-\s]?\d{2}[-\s]?\d{4}\b/',
        'pii_credit_card' => '/\b(?:\d{4}[-\s]?){3}\d{4}\b/',
    ];

    // Threat/harassment patterns
    private array $threatPatterns = [
        'threat_violence' => '/\b(kill|murder|hurt|attack|beat up|destroy)\s+(you|him|her|them|everyone)\b/i',
        'threat_harm' => '/\b(going to|gonna|will)\s+(hurt|harm|get|destroy)\b/i',
    ];

    // Doxxing patterns (name + identifying info)
    private array $doxxingPatterns = [
        'doxxing_address' => '/\b\d+\s+\w+\s+(street|st|avenue|ave|road|rd|drive|dr|lane|ln|way|boulevard|blvd)\b/i',
    ];

    /**
     * Analyze content and return analysis result
     */
    public function analyze(string $content): array
    {
        $flags = [];
        $score = 0;
        $redactions = [];

        // Check profanity
        foreach ($this->profanityPatterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[0] as $match) {
                    $flags[] = [
                        'rule_code' => 'profanity',
                        'matched_snippet' => $this->truncateSnippet($match),
                    ];
                    $score += 10;
                }
            }
        }

        // Check PII
        foreach ($this->piiPatterns as $code => $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[0] as $match) {
                    $flags[] = [
                        'rule_code' => $code,
                        'matched_snippet' => $this->maskSnippet($match),
                    ];
                    $redactions[] = [
                        'original' => $match,
                        'replacement' => '[REDACTED]',
                    ];
                    $score += 20;
                }
            }
        }

        // Check threats
        foreach ($this->threatPatterns as $code => $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[0] as $match) {
                    $flags[] = [
                        'rule_code' => $code,
                        'matched_snippet' => $this->truncateSnippet($match),
                    ];
                    $score += 50;
                }
            }
        }

        // Check doxxing
        foreach ($this->doxxingPatterns as $code => $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[0] as $match) {
                    $flags[] = [
                        'rule_code' => $code,
                        'matched_snippet' => $this->truncateSnippet($match),
                    ];
                    $score += 30;
                }
            }
        }

        // Check for personal name mentions (basic heuristic)
        if (preg_match('/\b([A-Z][a-z]+\s+[A-Z][a-z]+)\b/', $content, $matches)) {
            // Potential full name - flag for review
            $flags[] = [
                'rule_code' => 'potential_name',
                'matched_snippet' => $matches[1],
            ];
            $score += 15;
        }

        // Determine if content should be blocked
        $blocked = $score >= 50;

        return [
            'score' => $score,
            'blocked' => $blocked,
            'flags' => $flags,
            'redactions' => $redactions,
            'requires_review' => $score >= 20 && !$blocked,
        ];
    }

    /**
     * Apply redactions to content
     */
    public function redact(string $content, array $redactions): string
    {
        foreach ($redactions as $redaction) {
            $content = str_replace($redaction['original'], $redaction['replacement'], $content);
        }
        return $content;
    }

    /**
     * Truncate snippet for storage
     */
    private function truncateSnippet(string $snippet): string
    {
        if (strlen($snippet) > 50) {
            return substr($snippet, 0, 47) . '...';
        }
        return $snippet;
    }

    /**
     * Mask sensitive snippet
     */
    private function maskSnippet(string $snippet): string
    {
        $length = strlen($snippet);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }
        return substr($snippet, 0, 2) . str_repeat('*', $length - 4) . substr($snippet, -2);
    }

    /**
     * Get content suggestions for better feedback
     */
    public function getSuggestions(string $content): array
    {
        $suggestions = [];

        // Check if content is too short
        if (strlen($content) < 30) {
            $suggestions[] = 'Please provide more detail to help us understand the issue.';
        }

        // Check if content mentions specific people
        if (preg_match('/\b(he|she|they|this person|that guy|that woman)\s+(is|was|did|said)\b/i', $content)) {
            $suggestions[] = 'Instead of describing individuals, try focusing on the behavior or process you want to address.';
        }

        // Check if content is mostly negative without suggestion
        $negativeWords = preg_match_all('/\b(bad|terrible|awful|horrible|hate|worst|never|nothing)\b/i', $content);
        $suggestionWords = preg_match_all('/\b(suggest|improve|could|should|would|better|idea|solution)\b/i', $content);

        if ($negativeWords > 2 && $suggestionWords === 0) {
            $suggestions[] = 'Consider adding a suggestion for how this could be improved.';
        }

        // Check for all caps
        if (preg_match('/[A-Z]{10,}/', $content)) {
            $suggestions[] = 'Using all capitals can come across as shouting. Consider using normal capitalization.';
        }

        return $suggestions;
    }
}
