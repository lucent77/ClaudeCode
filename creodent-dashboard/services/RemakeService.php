<?php
/**
 * Creodent Dashboard - Remake Service
 */

namespace Creodent\Services;

use Creodent\Core\Database;

class RemakeService
{
    /**
     * Get remake overview summary
     */
    public static function getSummary(string $branch, string $startDate, string $endDate): array
    {
        $sql = "SELECT
                    COALESCE(SUM(remake_amount), 0) as total_remake,
                    COALESCE(SUM(net_price), 0) as total_sales,
                    COUNT(DISTINCT CASE WHEN remake_amount > 0 THEN invoice_number END) as remake_invoices,
                    COUNT(DISTINCT invoice_number) as total_invoices,
                    COUNT(DISTINCT CASE WHEN remake_amount > 0 THEN customer_name END) as remake_customers,
                    COUNT(DISTINCT CASE WHEN remake_amount > 0 THEN product_description END) as remake_products
                FROM invoices
                WHERE invoice_date BETWEEN ? AND ?";

        $params = [$startDate, $endDate . ' 23:59:59'];

        if ($branch === 'ALL') {
            $nycResult = Database::query($sql, $params, 'nyc');
            $hvResult = Database::query($sql, $params, 'hv');
            return self::mergeSummaries($nycResult[0] ?? [], $hvResult[0] ?? []);
        }

        $result = Database::query($sql, $params, strtolower($branch));
        return $result[0] ?? [];
    }

    /**
     * Get remake trend over time
     */
    public static function getTrend(string $branch, string $startDate, string $endDate): array
    {
        $sql = "SELECT
                    DATE_FORMAT(invoice_date, '%Y-%m') as month,
                    SUM(remake_amount) as remake_amount,
                    SUM(net_price) as total_sales
                FROM invoices
                WHERE invoice_date BETWEEN ? AND ?
                GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
                ORDER BY month";

        $params = [$startDate, $endDate . ' 23:59:59'];

        if ($branch === 'ALL') {
            $nycResults = Database::query($sql, $params, 'nyc');
            $hvResults = Database::query($sql, $params, 'hv');
            return self::mergeTrendResults($nycResults, $hvResults);
        }

        return Database::query($sql, $params, strtolower($branch));
    }

    /**
     * Get remakes by customer
     */
    public static function getByCustomer(string $branch, string $startDate, string $endDate): array
    {
        $sql = "SELECT
                    customer_name,
                    SUM(remake_amount) as remake_amount,
                    SUM(net_price) as total_sales,
                    COUNT(DISTINCT CASE WHEN remake_amount > 0 THEN invoice_number END) as remake_count,
                    COUNT(DISTINCT invoice_number) as invoice_count
                FROM invoices
                WHERE invoice_date BETWEEN ? AND ?
                    AND customer_name IS NOT NULL
                    AND customer_name != ''
                GROUP BY customer_name
                HAVING SUM(remake_amount) > 0
                ORDER BY remake_amount DESC";

        $params = [$startDate, $endDate . ' 23:59:59'];

        if ($branch === 'ALL') {
            $nycResults = Database::query($sql, $params, 'nyc');
            $hvResults = Database::query($sql, $params, 'hv');
            return self::mergeCustomerResults($nycResults, $hvResults);
        }

        $results = Database::query($sql, $params, strtolower($branch));

        // Calculate ratio
        foreach ($results as &$row) {
            $row['remake_ratio'] = $row['total_sales'] > 0
                ? ($row['remake_amount'] / $row['total_sales']) * 100
                : 0;
        }

        return $results;
    }

    /**
     * Get remakes by product
     */
    public static function getByProduct(string $branch, string $startDate, string $endDate): array
    {
        $sql = "SELECT
                    product_description,
                    SUM(remake_amount) as remake_amount,
                    SUM(net_price) as total_sales,
                    SUM(units) as total_units,
                    COUNT(DISTINCT CASE WHEN remake_amount > 0 THEN invoice_number END) as remake_count
                FROM invoices
                WHERE invoice_date BETWEEN ? AND ?
                    AND product_description IS NOT NULL
                    AND product_description != ''
                GROUP BY product_description
                HAVING SUM(remake_amount) > 0
                ORDER BY remake_amount DESC";

        $params = [$startDate, $endDate . ' 23:59:59'];

        if ($branch === 'ALL') {
            $nycResults = Database::query($sql, $params, 'nyc');
            $hvResults = Database::query($sql, $params, 'hv');
            return self::mergeProductResults($nycResults, $hvResults);
        }

        $results = Database::query($sql, $params, strtolower($branch));

        foreach ($results as &$row) {
            $row['remake_ratio'] = $row['total_sales'] > 0
                ? ($row['remake_amount'] / $row['total_sales']) * 100
                : 0;
        }

        return $results;
    }

    private static function mergeSummaries(array $nyc, array $hv): array
    {
        return [
            'total_remake' => (float)($nyc['total_remake'] ?? 0) + (float)($hv['total_remake'] ?? 0),
            'total_sales' => (float)($nyc['total_sales'] ?? 0) + (float)($hv['total_sales'] ?? 0),
            'remake_invoices' => (int)($nyc['remake_invoices'] ?? 0) + (int)($hv['remake_invoices'] ?? 0),
            'total_invoices' => (int)($nyc['total_invoices'] ?? 0) + (int)($hv['total_invoices'] ?? 0),
            'remake_customers' => (int)($nyc['remake_customers'] ?? 0) + (int)($hv['remake_customers'] ?? 0),
            'remake_products' => (int)($nyc['remake_products'] ?? 0) + (int)($hv['remake_products'] ?? 0)
        ];
    }

    private static function mergeTrendResults(array $nyc, array $hv): array
    {
        $merged = [];

        foreach (array_merge($nyc, $hv) as $row) {
            $month = $row['month'];
            if (!isset($merged[$month])) {
                $merged[$month] = $row;
            } else {
                $merged[$month]['remake_amount'] = (float)$merged[$month]['remake_amount'] + (float)$row['remake_amount'];
                $merged[$month]['total_sales'] = (float)$merged[$month]['total_sales'] + (float)$row['total_sales'];
            }
        }

        ksort($merged);
        return array_values($merged);
    }

    private static function mergeCustomerResults(array $nyc, array $hv): array
    {
        $merged = [];

        foreach (array_merge($nyc, $hv) as $row) {
            $name = $row['customer_name'];
            if (!isset($merged[$name])) {
                $merged[$name] = $row;
            } else {
                $merged[$name]['remake_amount'] = (float)$merged[$name]['remake_amount'] + (float)$row['remake_amount'];
                $merged[$name]['total_sales'] = (float)$merged[$name]['total_sales'] + (float)$row['total_sales'];
                $merged[$name]['remake_count'] = (int)$merged[$name]['remake_count'] + (int)$row['remake_count'];
                $merged[$name]['invoice_count'] = (int)$merged[$name]['invoice_count'] + (int)$row['invoice_count'];
            }
        }

        foreach ($merged as &$row) {
            $row['remake_ratio'] = $row['total_sales'] > 0
                ? ($row['remake_amount'] / $row['total_sales']) * 100
                : 0;
        }

        usort($merged, fn($a, $b) => (float)$b['remake_amount'] <=> (float)$a['remake_amount']);
        return array_values($merged);
    }

    private static function mergeProductResults(array $nyc, array $hv): array
    {
        $merged = [];

        foreach (array_merge($nyc, $hv) as $row) {
            $name = $row['product_description'];
            if (!isset($merged[$name])) {
                $merged[$name] = $row;
            } else {
                $merged[$name]['remake_amount'] = (float)$merged[$name]['remake_amount'] + (float)$row['remake_amount'];
                $merged[$name]['total_sales'] = (float)$merged[$name]['total_sales'] + (float)$row['total_sales'];
                $merged[$name]['total_units'] = (int)$merged[$name]['total_units'] + (int)$row['total_units'];
                $merged[$name]['remake_count'] = (int)$merged[$name]['remake_count'] + (int)$row['remake_count'];
            }
        }

        foreach ($merged as &$row) {
            $row['remake_ratio'] = $row['total_sales'] > 0
                ? ($row['remake_amount'] / $row['total_sales']) * 100
                : 0;
        }

        usort($merged, fn($a, $b) => (float)$b['remake_amount'] <=> (float)$a['remake_amount']);
        return array_values($merged);
    }
}
