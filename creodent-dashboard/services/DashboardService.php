<?php
/**
 * Creodent Dashboard - Dashboard Service
 */

namespace Creodent\Services;

use Creodent\Core\Database;

class DashboardService
{
    /**
     * Get summary statistics
     */
    public static function getSummary(string $branch, string $startDate, string $endDate): array
    {
        $sql = "SELECT
                    COALESCE(SUM(net_price), 0) as total_sales,
                    COUNT(DISTINCT customer_name) as customer_count,
                    COUNT(DISTINCT product_description) as product_count,
                    COUNT(DISTINCT invoice_number) as invoice_count,
                    COALESCE(SUM(units), 0) as total_units,
                    COALESCE(SUM(remake_amount), 0) as remake_amount,
                    COALESCE(SUM(discount_amount), 0) as discount_amount
                FROM invoices
                WHERE invoice_date BETWEEN ? AND ?";

        $params = [$startDate, $endDate . ' 23:59:59'];

        if ($branch === 'ALL') {
            $results = Database::queryAllBranches($sql, $params);
            return self::aggregateSummary($results);
        }

        $branchKey = strtolower($branch);
        $result = Database::query($sql, $params, $branchKey);
        return $result[0] ?? self::emptySummary();
    }

    /**
     * Get previous period summary for comparison
     */
    public static function getPreviousPeriodSummary(string $branch, string $startDate, string $endDate): array
    {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $diff = $start->diff($end);

        $prevEnd = (clone $start)->modify('-1 day');
        $prevStart = (clone $prevEnd)->modify("-{$diff->days} days");

        return self::getSummary($branch, $prevStart->format('Y-m-d'), $prevEnd->format('Y-m-d'));
    }

    /**
     * Get monthly sales trend
     */
    public static function getMonthlySalesTrend(string $branch, int $year): array
    {
        $sql = "SELECT
                    MONTH(invoice_date) as month,
                    COALESCE(SUM(net_price), 0) as sales
                FROM invoices
                WHERE YEAR(invoice_date) = ?
                GROUP BY MONTH(invoice_date)
                ORDER BY month";

        $params = [$year];

        if ($branch === 'ALL') {
            $results = Database::queryAllBranches($sql, $params);
            return self::aggregateMonthlySales($results);
        }

        $branchKey = strtolower($branch);
        $results = Database::query($sql, $params, $branchKey);

        $monthly = array_fill(1, 12, 0);
        foreach ($results as $row) {
            $monthly[(int)$row['month']] = (float)$row['sales'];
        }

        return $monthly;
    }

    /**
     * Get top customers
     */
    public static function getTopCustomers(string $branch, string $startDate, string $endDate, int $limit = 10): array
    {
        $sql = "SELECT
                    customer_name,
                    SUM(net_price) as total_sales,
                    COUNT(DISTINCT invoice_number) as invoice_count,
                    SUM(units) as total_units
                FROM invoices
                WHERE invoice_date BETWEEN ? AND ?
                    AND customer_name IS NOT NULL
                    AND customer_name != ''
                GROUP BY customer_name
                ORDER BY total_sales DESC
                LIMIT ?";

        $params = [$startDate, $endDate . ' 23:59:59', $limit];

        if ($branch === 'ALL') {
            // Query both branches and merge
            $nycResults = Database::query($sql, $params, 'nyc');
            $hvResults = Database::query($sql, $params, 'hv');

            $merged = [];
            foreach (array_merge($nycResults, $hvResults) as $row) {
                $name = $row['customer_name'];
                if (!isset($merged[$name])) {
                    $merged[$name] = ['customer_name' => $name, 'total_sales' => 0, 'invoice_count' => 0, 'total_units' => 0];
                }
                $merged[$name]['total_sales'] += (float)$row['total_sales'];
                $merged[$name]['invoice_count'] += (int)$row['invoice_count'];
                $merged[$name]['total_units'] += (int)$row['total_units'];
            }

            usort($merged, fn($a, $b) => $b['total_sales'] <=> $a['total_sales']);
            return array_slice($merged, 0, $limit);
        }

        return Database::query($sql, $params, strtolower($branch));
    }

    /**
     * Get top products
     */
    public static function getTopProducts(string $branch, string $startDate, string $endDate, int $limit = 10): array
    {
        $sql = "SELECT
                    product_description,
                    SUM(net_price) as total_sales,
                    SUM(units) as total_units,
                    COUNT(DISTINCT customer_name) as customer_count
                FROM invoices
                WHERE invoice_date BETWEEN ? AND ?
                    AND product_description IS NOT NULL
                    AND product_description != ''
                GROUP BY product_description
                ORDER BY total_sales DESC
                LIMIT ?";

        $params = [$startDate, $endDate . ' 23:59:59', $limit];

        if ($branch === 'ALL') {
            $nycResults = Database::query($sql, $params, 'nyc');
            $hvResults = Database::query($sql, $params, 'hv');

            $merged = [];
            foreach (array_merge($nycResults, $hvResults) as $row) {
                $name = $row['product_description'];
                if (!isset($merged[$name])) {
                    $merged[$name] = ['product_description' => $name, 'total_sales' => 0, 'total_units' => 0, 'customer_count' => 0];
                }
                $merged[$name]['total_sales'] += (float)$row['total_sales'];
                $merged[$name]['total_units'] += (int)$row['total_units'];
                $merged[$name]['customer_count'] += (int)$row['customer_count'];
            }

            usort($merged, fn($a, $b) => $b['total_sales'] <=> $a['total_sales']);
            return array_slice($merged, 0, $limit);
        }

        return Database::query($sql, $params, strtolower($branch));
    }

    /**
     * Get customer distribution by sales range
     */
    public static function getCustomerDistribution(string $branch, string $startDate, string $endDate): array
    {
        $sql = "SELECT
                    customer_name,
                    SUM(net_price) as total_sales
                FROM invoices
                WHERE invoice_date BETWEEN ? AND ?
                    AND customer_name IS NOT NULL
                    AND customer_name != ''
                GROUP BY customer_name";

        $params = [$startDate, $endDate . ' 23:59:59'];

        if ($branch === 'ALL') {
            $results = Database::queryAllBranches($sql, $params);
            $customerSales = [];
            foreach ($results as $row) {
                $name = $row['customer_name'];
                if (!isset($customerSales[$name])) {
                    $customerSales[$name] = 0;
                }
                $customerSales[$name] += (float)$row['total_sales'];
            }
            $sales = array_values($customerSales);
        } else {
            $results = Database::query($sql, $params, strtolower($branch));
            $sales = array_column($results, 'total_sales');
        }

        $ranges = [
            '< $1K' => 0,
            '$1K - $5K' => 0,
            '$5K - $10K' => 0,
            '$10K - $25K' => 0,
            '$25K - $50K' => 0,
            '$50K+' => 0
        ];

        foreach ($sales as $amount) {
            $amount = (float)$amount;
            if ($amount < 1000) {
                $ranges['< $1K']++;
            } elseif ($amount < 5000) {
                $ranges['$1K - $5K']++;
            } elseif ($amount < 10000) {
                $ranges['$5K - $10K']++;
            } elseif ($amount < 25000) {
                $ranges['$10K - $25K']++;
            } elseif ($amount < 50000) {
                $ranges['$25K - $50K']++;
            } else {
                $ranges['$50K+']++;
            }
        }

        return $ranges;
    }

    private static function aggregateSummary(array $results): array
    {
        $summary = self::emptySummary();

        foreach ($results as $row) {
            $summary['total_sales'] += (float)($row['total_sales'] ?? 0);
            $summary['invoice_count'] += (int)($row['invoice_count'] ?? 0);
            $summary['total_units'] += (int)($row['total_units'] ?? 0);
            $summary['remake_amount'] += (float)($row['remake_amount'] ?? 0);
            $summary['discount_amount'] += (float)($row['discount_amount'] ?? 0);
        }

        // Count distinct needs special handling
        $summary['customer_count'] = (int)($results[0]['customer_count'] ?? 0) + (int)($results[1]['customer_count'] ?? 0);
        $summary['product_count'] = (int)($results[0]['product_count'] ?? 0) + (int)($results[1]['product_count'] ?? 0);

        return $summary;
    }

    private static function aggregateMonthlySales(array $results): array
    {
        $monthly = array_fill(1, 12, 0);

        foreach ($results as $row) {
            $month = (int)$row['month'];
            $monthly[$month] += (float)$row['sales'];
        }

        return $monthly;
    }

    private static function emptySummary(): array
    {
        return [
            'total_sales' => 0,
            'customer_count' => 0,
            'product_count' => 0,
            'invoice_count' => 0,
            'total_units' => 0,
            'remake_amount' => 0,
            'discount_amount' => 0
        ];
    }
}
