<?php
/**
 * Multi-Store Price Scraper
 * Supports 11+ major retailers
 */

class StoreScraper {
    private $userAgent;
    private $timeout;

    public function __construct() {
        $this->userAgent = USER_AGENT;
        $this->timeout = SCRAPE_TIMEOUT;
    }

    /**
     * Main scraping function - routes to appropriate store scraper
     */
    public function scrapePrice($url, $store) {
        $store = strtolower(trim($store));

        try {
            switch ($store) {
                case 'amazon':
                    return $this->scrapeAmazon($url);
                case 'bestbuy':
                    return $this->scrapeBestBuy($url);
                case 'target':
                    return $this->scrapeTarget($url);
                case 'walmart':
                    return $this->scrapeWalmart($url);
                case 'sony':
                    return $this->scrapeSony($url);
                case 'b&h':
                case 'bh':
                case 'bhphotovideo':
                    return $this->scrapeBH($url);
                case 'newegg':
                    return $this->scrapeNewegg($url);
                case 'costco':
                    return $this->scrapeCostco($url);
                case 'adorama':
                    return $this->scrapeAdorama($url);
                case 'microcenter':
                    return $this->scrapeMicroCenter($url);
                case 'ebay':
                    return $this->scrapeEbay($url);
                default:
                    return $this->scrapeGeneric($url);
            }
        } catch (Exception $e) {
            error_log("Scraping error for $store: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch HTML content with cURL
     */
    private function fetchHTML($url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1'
            ]
        ]);

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$html) {
            throw new Exception("Failed to fetch URL (HTTP $httpCode)");
        }

        return $html;
    }

    /**
     * Extract price using regex patterns
     */
    private function extractPrice($html, $patterns) {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $price = $matches[1];
                // Clean price: remove currency symbols, commas
                $price = preg_replace('/[^0-9.]/', '', $price);
                $price = floatval($price);

                if ($price > 0) {
                    return $price;
                }
            }
        }
        return null;
    }

    /**
     * Amazon Scraper
     */
    private function scrapeAmazon($url) {
        $html = $this->fetchHTML($url);
        $patterns = [
            '/<span class="a-price-whole">([0-9,]+\.?[0-9]*)<\/span>/',
            '/<span id="priceblock_ourprice"[^>]*>.*?\$([0-9,]+\.?[0-9]*)<\/span>/',
            '/<span id="priceblock_dealprice"[^>]*>.*?\$([0-9,]+\.?[0-9]*)<\/span>/',
            '/data-a-color="price">.*?\$([0-9,]+\.?[0-9]*)</',
        ];
        return $this->extractPrice($html, $patterns);
    }

    /**
     * Best Buy Scraper
     */
    private function scrapeBestBuy($url) {
        $html = $this->fetchHTML($url);
        $patterns = [
            '/<div class="priceView-hero-price[^"]*"[^>]*>.*?\$([0-9,]+\.?[0-9]*)<\/div>/',
            '/<span aria-hidden="true">([0-9,]+\.?[0-9]*)<\/span>/',
            '/data-customer-price="([0-9,]+\.?[0-9]*)"/',
            '/"price":\s*"([0-9,]+\.?[0-9]*)"/',
        ];
        return $this->extractPrice($html, $patterns);
    }

    /**
     * Target Scraper
     */
    private function scrapeTarget($url) {
        $html = $this->fetchHTML($url);
        $patterns = [
            '/<span data-test="product-price"[^>]*>\$([0-9,]+\.?[0-9]*)<\/span>/',
            '/"current_retail":\s*([0-9,]+\.?[0-9]*)/',
            '/data-test="product-price-value"[^>]*>([0-9,]+\.?[0-9]*)</',
        ];
        return $this->extractPrice($html, $patterns);
    }

    /**
     * Walmart Scraper
     */
    private function scrapeWalmart($url) {
        $html = $this->fetchHTML($url);
        $patterns = [
            '/<span itemprop="price"[^>]*>([0-9,]+\.?[0-9]*)<\/span>/',
            '/"currentPrice":\s*"?([0-9,]+\.?[0-9]*)"?/',
            '/class="price-characteristic"[^>]*>([0-9,]+)</',
            '/<span class="price display-inline-block arrange-fit"[^>]*>\$([0-9,]+\.?[0-9]*)<\/span>/',
        ];
        return $this->extractPrice($html, $patterns);
    }

    /**
     * Sony Scraper
     */
    private function scrapeSony($url) {
        $html = $this->fetchHTML($url);
        $patterns = [
            '/<span class="price"[^>]*>\$([0-9,]+\.?[0-9]*)<\/span>/',
            '/"price":\s*"?([0-9,]+\.?[0-9]*)"?/',
            '/data-price="([0-9,]+\.?[0-9]*)"/',
        ];
        return $this->extractPrice($html, $patterns);
    }

    /**
     * B&H Photo Video Scraper
     */
    private function scrapeBH($url) {
        $html = $this->fetchHTML($url);
        $patterns = [
            '/<div[^>]*data-selenium="pricingPrice"[^>]*>\$([0-9,]+\.?[0-9]*)<\/div>/',
            '/"price":\s*"?([0-9,]+\.?[0-9]*)"?/',
            '/data-price-amount="([0-9,]+\.?[0-9]*)"/',
        ];
        return $this->extractPrice($html, $patterns);
    }

    /**
     * Newegg Scraper
     */
    private function scrapeNewegg($url) {
        $html = $this->fetchHTML($url);
        $patterns = [
            '/<li class="price-current"[^>]*>\$([0-9,]+\.?[0-9]*)<\/li>/',
            '/<strong class="price-current-label"[^>]*>.*?\$([0-9,]+\.?[0-9]*)</',
            '/"price":\s*"?([0-9,]+\.?[0-9]*)"?/',
        ];
        return $this->extractPrice($html, $patterns);
    }

    /**
     * Costco Scraper
     */
    private function scrapeCostco($url) {
        $html = $this->fetchHTML($url);
        $patterns = [
            '/<span class="value"[^>]*>\$([0-9,]+\.?[0-9]*)<\/span>/',
            '/data-price="([0-9,]+\.?[0-9]*)"/',
            '/"price":\s*"?([0-9,]+\.?[0-9]*)"?/',
        ];
        return $this->extractPrice($html, $patterns);
    }

    /**
     * Adorama Scraper
     */
    private function scrapeAdorama($url) {
        $html = $this->fetchHTML($url);
        $patterns = [
            '/<span class="your-price"[^>]*>\$([0-9,]+\.?[0-9]*)<\/span>/',
            '/data-price="([0-9,]+\.?[0-9]*)"/',
            '/"price":\s*"?([0-9,]+\.?[0-9]*)"?/',
        ];
        return $this->extractPrice($html, $patterns);
    }

    /**
     * Micro Center Scraper
     */
    private function scrapeMicroCenter($url) {
        $html = $this->fetchHTML($url);
        $patterns = [
            '/<span id="pricing"[^>]*>\$([0-9,]+\.?[0-9]*)<\/span>/',
            '/<div class="price"[^>]*>\$([0-9,]+\.?[0-9]*)<\/div>/',
            '/data-price="([0-9,]+\.?[0-9]*)"/',
        ];
        return $this->extractPrice($html, $patterns);
    }

    /**
     * eBay Scraper
     */
    private function scrapeEbay($url) {
        $html = $this->fetchHTML($url);
        $patterns = [
            '/<span class="notranslate"[^>]*>US \$([0-9,]+\.?[0-9]*)<\/span>/',
            '/<span itemprop="price"[^>]*>([0-9,]+\.?[0-9]*)<\/span>/',
            '/data-testid="x-price-primary"[^>]*>.*?\$([0-9,]+\.?[0-9]*)</',
        ];
        return $this->extractPrice($html, $patterns);
    }

    /**
     * Generic Scraper (fallback)
     * Tries common price patterns
     */
    private function scrapeGeneric($url) {
        $html = $this->fetchHTML($url);
        $patterns = [
            '/<span[^>]*class="[^"]*price[^"]*"[^>]*>\$?([0-9,]+\.?[0-9]*)<\/span>/i',
            '/<div[^>]*class="[^"]*price[^"]*"[^>]*>\$?([0-9,]+\.?[0-9]*)<\/div>/i',
            '/data-price="([0-9,]+\.?[0-9]*)"/i',
            '/"price":\s*"?([0-9,]+\.?[0-9]*)"?/i',
            '/\$([0-9,]+\.?[0-9]{2})\b/',
        ];
        return $this->extractPrice($html, $patterns);
    }
}
