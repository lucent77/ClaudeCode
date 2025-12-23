<?php
/**
 * URL Collector - HTML Parser
 *
 * Extracts metadata, OG tags, and clean text from HTML
 */

declare(strict_types=1);

class HtmlParser
{
    private int $maxTextLength;
    private array $categories;

    public function __construct(int $maxTextLength = 8000, array $categories = [])
    {
        $this->maxTextLength = $maxTextLength;
        $this->categories = $categories;
    }

    /**
     * Parse HTML and extract all metadata
     *
     * @return array{
     *     title: ?string,
     *     description: ?string,
     *     image_url: ?string,
     *     site_name: ?string,
     *     text: string,
     *     meta: array
     * }
     */
    public function parse(string $html, string $url): array
    {
        // Suppress DOM warnings for malformed HTML
        libxml_use_internal_errors(true);

        $doc = new DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        libxml_clear_errors();

        $result = [
            'title'       => null,
            'description' => null,
            'image_url'   => null,
            'site_name'   => $this->extractDomain($url),
            'text'        => '',
            'meta'        => [],
        ];

        // Extract meta tags
        $metas = $doc->getElementsByTagName('meta');
        foreach ($metas as $meta) {
            $property = $meta->getAttribute('property');
            $name = $meta->getAttribute('name');
            $content = $meta->getAttribute('content');

            if (empty($content)) {
                continue;
            }

            // OpenGraph tags
            if ($property === 'og:title') {
                $result['title'] = $this->cleanText($content);
            } elseif ($property === 'og:description') {
                $result['description'] = $this->cleanText($content);
            } elseif ($property === 'og:image') {
                $result['image_url'] = $this->normalizeUrl($content, $url);
            } elseif ($property === 'og:site_name') {
                $result['site_name'] = $this->cleanText($content);
            }

            // Twitter cards
            if ($name === 'twitter:title' && empty($result['title'])) {
                $result['title'] = $this->cleanText($content);
            } elseif ($name === 'twitter:description' && empty($result['description'])) {
                $result['description'] = $this->cleanText($content);
            } elseif ($name === 'twitter:image' && empty($result['image_url'])) {
                $result['image_url'] = $this->normalizeUrl($content, $url);
            }

            // Standard meta description
            if ($name === 'description' && empty($result['description'])) {
                $result['description'] = $this->cleanText($content);
            }

            // Store all meta for reference
            $key = $property ?: $name;
            if ($key) {
                $result['meta'][$key] = $content;
            }
        }

        // Fallback: Extract title from <title> tag
        if (empty($result['title'])) {
            $titleTags = $doc->getElementsByTagName('title');
            if ($titleTags->length > 0) {
                $result['title'] = $this->cleanText($titleTags->item(0)->textContent);
            }
        }

        // Extract clean text content
        $result['text'] = $this->extractText($doc);

        return $result;
    }

    /**
     * Extract clean text from HTML, removing scripts and styles
     */
    private function extractText(DOMDocument $doc): string
    {
        // Clone to avoid modifying original
        $clone = clone $doc;

        // Remove script, style, noscript, header, footer, nav tags
        $removeTagNames = ['script', 'style', 'noscript', 'header', 'footer', 'nav', 'aside', 'iframe'];

        foreach ($removeTagNames as $tagName) {
            $elements = $clone->getElementsByTagName($tagName);
            $toRemove = [];

            // Collect elements first (can't modify during iteration)
            for ($i = 0; $i < $elements->length; $i++) {
                $toRemove[] = $elements->item($i);
            }

            foreach ($toRemove as $element) {
                if ($element->parentNode) {
                    $element->parentNode->removeChild($element);
                }
            }
        }

        // Get text content
        $text = $clone->textContent;

        // Clean up whitespace
        $text = preg_replace('/[\r\n]+/', "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n +/', "\n", $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = trim($text);

        // Truncate if too long
        if (mb_strlen($text) > $this->maxTextLength) {
            $text = mb_substr($text, 0, $this->maxTextLength) . '...';
        }

        return $text;
    }

    /**
     * Extract domain from URL
     */
    public function extractDomain(string $url): string
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';

        // Remove www prefix
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return $host;
    }

    /**
     * Normalize relative URLs to absolute
     */
    private function normalizeUrl(string $relativeUrl, string $baseUrl): string
    {
        // Already absolute
        if (preg_match('/^https?:\/\//i', $relativeUrl)) {
            return $relativeUrl;
        }

        // Protocol-relative
        if (str_starts_with($relativeUrl, '//')) {
            $parsed = parse_url($baseUrl);
            return ($parsed['scheme'] ?? 'https') . ':' . $relativeUrl;
        }

        // Build absolute URL
        $parsed = parse_url($baseUrl);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';

        if (str_starts_with($relativeUrl, '/')) {
            // Root-relative
            return "{$scheme}://{$host}{$port}{$relativeUrl}";
        }

        // Path-relative
        $path = $parsed['path'] ?? '/';
        $dir = dirname($path);
        if ($dir === '.') {
            $dir = '/';
        }

        return "{$scheme}://{$host}{$port}{$dir}/{$relativeUrl}";
    }

    /**
     * Clean and normalize text
     */
    private function cleanText(string $text): string
    {
        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Remove control characters
        $text = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $text);

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Detect category based on URL/domain (rule-based)
     */
    public function detectCategory(string $url, string $domain): string
    {
        $domainLower = strtolower($domain);
        $urlLower = strtolower($url);

        // Video platforms
        $videoSites = ['youtube.com', 'youtu.be', 'vimeo.com', 'dailymotion.com', 'twitch.tv', 'tiktok.com'];
        foreach ($videoSites as $site) {
            if (str_contains($domainLower, $site)) {
                return 'video';
            }
        }

        // Social media
        $socialSites = ['twitter.com', 'x.com', 'facebook.com', 'instagram.com', 'linkedin.com', 'threads.net', 'mastodon.social'];
        foreach ($socialSites as $site) {
            if (str_contains($domainLower, $site)) {
                return 'social';
            }
        }

        // Shopping
        $shoppingSites = ['amazon.', 'ebay.com', 'aliexpress.com', 'coupang.com', '11st.co.kr', 'gmarket.co.kr'];
        foreach ($shoppingSites as $site) {
            if (str_contains($domainLower, $site)) {
                return 'shopping';
            }
        }

        // Tech
        $techSites = ['github.com', 'gitlab.com', 'stackoverflow.com', 'hackernews.com', 'news.ycombinator.com', 'dev.to', 'medium.com'];
        foreach ($techSites as $site) {
            if (str_contains($domainLower, $site)) {
                return 'tech';
            }
        }

        // News
        $newsSites = ['news.', 'bbc.com', 'cnn.com', 'nytimes.com', 'reuters.com', 'naver.com/news', 'daum.net/news'];
        foreach ($newsSites as $site) {
            if (str_contains($domainLower, $site) || str_contains($urlLower, '/news')) {
                return 'news';
            }
        }

        // Music
        $musicSites = ['spotify.com', 'soundcloud.com', 'apple.com/music', 'music.youtube.com', 'melon.com'];
        foreach ($musicSites as $site) {
            if (str_contains($domainLower, $site)) {
                return 'music';
            }
        }

        // Design
        $designSites = ['dribbble.com', 'behance.net', 'figma.com', 'pinterest.com'];
        foreach ($designSites as $site) {
            if (str_contains($domainLower, $site)) {
                return 'design';
            }
        }

        // Education
        $educationSites = ['coursera.org', 'udemy.com', 'edx.org', 'khan', 'edu.'];
        foreach ($educationSites as $site) {
            if (str_contains($domainLower, $site)) {
                return 'education';
            }
        }

        // Business
        if (str_contains($urlLower, '/business') || str_contains($urlLower, '/finance')) {
            return 'business';
        }

        // Default
        return '';
    }

    /**
     * Extract keywords from text (simple extraction)
     */
    public function extractKeywords(string $text, int $count = 10): array
    {
        // Remove common stop words (English + Korean)
        $stopWords = [
            'the', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
            'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should',
            'may', 'might', 'must', 'shall', 'can', 'need', 'dare', 'ought', 'used',
            'to', 'of', 'in', 'for', 'on', 'with', 'at', 'by', 'from', 'as', 'into',
            'through', 'during', 'before', 'after', 'above', 'below', 'between',
            'and', 'but', 'or', 'nor', 'so', 'yet', 'both', 'either', 'neither',
            'not', 'only', 'own', 'same', 'than', 'too', 'very', 'just',
            'this', 'that', 'these', 'those', 'it', 'its', 'they', 'them', 'their',
            'we', 'us', 'our', 'you', 'your', 'he', 'him', 'his', 'she', 'her',
            '이', '그', '저', '것', '수', '등', '및', '더', '또', '또는',
            '의', '가', '이', '은', '는', '을', '를', '에', '에서', '로', '으로',
            '와', '과', '도', '만', '뿐', '부터', '까지', '처럼', '같이',
        ];

        // Tokenize
        $words = preg_split('/[\s\p{P}]+/u', mb_strtolower($text));

        // Count word frequency
        $wordCounts = [];
        foreach ($words as $word) {
            $word = trim($word);

            // Skip short words, numbers, and stop words
            if (mb_strlen($word) < 2 || is_numeric($word) || in_array($word, $stopWords, true)) {
                continue;
            }

            $wordCounts[$word] = ($wordCounts[$word] ?? 0) + 1;
        }

        // Sort by frequency
        arsort($wordCounts);

        // Return top N
        return array_slice(array_keys($wordCounts), 0, $count);
    }
}
