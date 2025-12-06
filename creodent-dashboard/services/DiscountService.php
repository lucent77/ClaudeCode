<?php
/**
 * Creodent Dashboard - Discount Service
 */

namespace Creodent\Services;

use Creodent\Core\Database;

class DiscountService
{
    /**
     * Get discount overview summary
     */
    public static function getSummary(string $branch, string $startDate, string $endDate): array
    {
        $sql = "SELECT
                    COALESCE(SUM(discount_amount), 0) as total_discount,
                    COALESCE(SUM(net_price), 0) as total_sales,
                    COALESCE(SUM(net_price + discount_amount), 0) as gross_sales,
                    COUNT(DISTINCT CASE WHEN discount_amount > 0 THEN invoice_number END) as discount_invoices,
                    COUNT(DISTINCT invoice_number) as total_invoices,
                    COUNT(DISTINCT CASE WHEN discount_amount > 0 THEN customer_name END) as discount_customers
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
     * Get discount trend over time
     */
    public static function getTrend(string $branch, string $startDate, string $endDate): array
    {
        $sql = "SELECT
                    DATE_FORMAT(invoice_date, '%Y-%m') as month,
                    SUM(discount_amount) as discount_amount,
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
     * Get top discount customers
     */
    public static function getTopCustomers(string $branch, string $startDate, string $endDate, int $limit = 20): array
    {
        $sql = "SELECT
                    customer_name,
                    SUM(discount_amount) as discount_amount,
                    SUM(net_price) as total_sales,
                    SUM(net_price + discount_amount) as gross_sales,
                    COUNT(DISTINCT invoice_number) as invoice_count
                FROM invoices
                WHERE invoice_date BETWEEN ? AND ?
                    AND customer_name IS NOT NULL
                    AND customer_name != ''
                GROUP BY customer_name
                HAVING SUM(discount_amount) > 0
                ORDER BY discount_amount DESC
                LIMIT ?";

        $params = [$startDate, $endDate . ' 23:59:59', $limit];

        if ($branch === 'ALL') {
            $nycResults = Database::query($sql, $params, 'nyc');
            $hvResults = Database::query($sql, $params, 'hv');
            return self::mergeCustomerResults($nycResults, $hvResults, $limit);
        }

        $results = Database::query($sql, $params, strtolower($branch));

        foreach ($results as &$row) {
            $row['discount_rate'] = $row['gross_sales'] > 0
                ? ($row['discount_amount'] / $row['gross_sales']) * 100
                : 0;
        }

        return $results;
    }

    /**
     * Get top discount products
     */
    public static function getTopProducts(string $branch, string $startDate, string $endDate, int $limit = 20): array
    {
        $sql = "SELECT
                    product_description,
                    SUM(discount_amount) as discount_amount,
                    SUM(net_price) as total_sales,
                    SUM(units) as total_units,
                    COUNT(DISTINCT invoice_number) as invoice_count
                FROM invoices
                WHERE invoice_date BETWEEN ? AND ?
                    AND product_description IS NOT NULL
                    AND product_description != ''
                GROUP BY product_description
                HAVING SUM(discount_amount) > 0
                ORDER BY discount_amount DESC
                LIMIT ?";

        $params = [$startDate, $endDate . ' 23:59:59', $limit];

        if ($branch === 'ALL') {
            $nycResults = Database::query($sql, $params, 'nyc');
            $hvResults = Database::query($sql, $params, 'hv');
            return self::mergeProductResults($nycResults, $hvResults, $limit);
        }

        return Database::query($sql, $params, strtolower($branch));
    }

    /**
     * Get discount distribution by amount
     */
    public static function getDistribution(string $branch, string $startDate, string $endDate): array
    {
        $sql = "SELECT
                    customer_name,
                    SUM(discount_amount) as discount_amount
                FROM invoices
                WHERE invoice_date BETWEEN ? AND ?
                    AND discount_amount > 0
                    AND customer_name IS NOT NULL
                GROUP BY customer_name";

        $params = [$startDate, $endDate . ' 23:59:59'];

        if ($branch === 'ALL') {
            $results = Database::queryAllBranches($sql, $params);
            $discounts = [];
            foreach ($results as $row) {
                $name = $row['customer_name'];
                if (!isset($discounts[$name])) {
                    $discounts[$name] = 0;
                }
                $discounts[$name] += (float)$row['discount_amount'];
            }
            $amounts = array_values($discounts);
        } else {
            $results = Database::query($sql, $params, strtolower($branch));
            $amounts = array_column($results, 'discount_amount');
        }

        $ranges = [
            '< $100' => 0,
            '$100 - $500' => 0,
            '$500 - $1K' => 0,
            '$1K - $5K' => 0,
            '$5K+' => 0
        ];

        foreach ($amounts as $amount) {
            $amount = (float)$amount;
            if ($amount < 100) {
                $ranges['< $100']++;
            } elseif ($amount < 500) {
                $ranges['$100 - $500']++;
            } elseif ($amount < 1000) {
                $ranges['$500 - $1K']++;
            } elseif ($amount < 5000) {
                $ranges['$1K - $5K']++;
            } else {
                $ranges['$5K+']++;
            }
        }

        return $ranges;
    }

    private static function mergeSummaries(array $nyc, array $hv): array
    {
        return [
            'total_discount' => (float)($nyc['total_discount'] ?? 0) + (float)($hv['total_discount'] ?? 0),
            'total_sales' => (float)($nyc['total_sales'] ?? 0) + (float)($hv['total_sales'] ?? 0),
            'gross_sales' => (float)($nyc['gross_sales'] ?? 0) + (float)($hv['gross_sales'] ?? 0),
            'discount_invoices' => (int)($nyc['discount_invoices'] ?? 0) + (int)($hv['discount_invoices'] ?? 0),
            'total_invoices' => (int)($nyc['total_invoices'] ?? 0) + (int)($hv['total_invoices'] ?? 0),
            'discount_customers' => (int)($nyc['discount_customers'] ?? 0) + (int)($hv['discount_customers'] ?? 0)
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
                $merged[$month]['discount_amount'] = (float)$merged[$month]['discount_amount'] + (float)$row['discount_amount'];
                $merged[$month]['total_sales'] = (float)$merged[$month]['total_sales'] + (float)$row['total_sales'];
            }
        }

        ksort($merged);
        return array_values($merged);
    }

    private static function mergeCustomerResults(array $nyc, array $hv, int $limit): array
    {
        $merged = [];

        foreach (array_merge($nyc, $hv) as $row) {
            $name = $row['customer_name'];
            if (!isset($merged[$name])) {
                $merged[$name] = $row;
            } else {
                $merged[$name]['discount_amount'] = (float)$merged[$name]['discount_amount'] + (float)$row['discount_amount'];
                $merged[$name]['total_sales'] = (float)$merged[$name]['total_sales'] + (float)$row['total_sales'];
                $merged[$name]['gross_sales'] = (float)$merged[$name]['gross_sales'] + (float)$row['gross_sales'];
                $merged[$name]['invoice_count'] = (int)$merged[$name]['invoice_count'] + (int)$row['invoice_count'];
            }
        }

        foreach ($merged as &$row) {
            $row['discount_rate'] = $row['gross_sales'] > 0
                ? ($row['discount_amount'] / $row['gross_sales']) * 100
                : 0;
        }

        usort($merged, fn($a, $b) => (float)$b['discount_amount'] <=> (float)$a['discount_amount']);
        return array_slice(array_values($merged), 0, $limit);
    }

    private static function mergeProductResults(array $nyc, array $hv, int $limit): array
    {
        $merged = [];

        foreach (array_merge($nyc, $hv) as $row) {
            $name = $row['product_description'];
            if (!isset($merged[$name])) {
                $merged[$name] = $row;
            } else {
                $merged[$name]['discount_amount'] = (float)$merged[$name]['discount_amount'] + (float)$row['discount_amount'];
                $merged[$name]['total_sales'] = (float)$merged[$name]['total_sales'] + (float)$row['total_sales'];
                $merged[$name]['total_units'] = (int)$merged[$name]['total_units'] + (int)$row['total_units'];
                $merged[$name]['invoice_count'] = (int)$merged[$name]['invoice_count'] + (int)$row['invoice_count'];
            }
        }

        usort($merged, fn($a, $b) => (float)$b['discount_amount'] <=> (float)$a['discount_amount']);
        return array_slice(array_values($merged), 0, $limit);
    }
}
