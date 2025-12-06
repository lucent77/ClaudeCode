<?php
/**
 * Creodent Dashboard - Customer Service
 */

namespace Creodent\Services;

use Creodent\Core\Database;

class CustomerService
{
    /**
     * Get all customers with metrics
     */
    public static function getCustomers(string $branch, string $startDate, string $endDate, ?int $groupId = null, array $pagination = []): array
    {
        $offset = $pagination['offset'] ?? 0;
        $limit = $pagination['per_page'] ?? 50;

        $groupFilter = '';
        $params = [$startDate, $endDate . ' 23:59:59'];

        if ($groupId) {
            $members = self::getGroupMembers($groupId, $branch);
            if (empty($members)) {
                return ['data' => [], 'total' => 0];
            }
            $placeholders = implode(',', array_fill(0, count($members), '?'));
            $groupFilter = " AND customer_name IN ($placeholders)";
            $params = array_merge($params, $members);
        }

        $sql = "SELECT
                    customer_name,
                    SUM(net_price) as total_sales,
                    SUM(units) as total_units,
                    COUNT(DISTINCT invoice_number) as invoice_count,
                    COUNT(DISTINCT product_description) as product_count,
                    MAX(invoice_date) as last_purchase,
                    SUM(remake_amount) as remake_amount,
                    SUM(discount_amount) as discount_amount
                FROM invoices
                WHERE invoice_date BETWEEN ? AND ?
                    AND customer_name IS NOT NULL
                    AND customer_name != ''
                    $groupFilter
                GROUP BY customer_name
                ORDER BY total_sales DESC";

        if ($branch === 'ALL') {
            $nycResults = Database::query($sql, $params, 'nyc');
            $hvResults = Database::query($sql, $params, 'hv');
            $merged = self::mergeCustomerResults($nycResults, $hvResults);
            $total = count($merged);
            $data = array_slice($merged, $offset, $limit);
        } else {
            // Get total count first
            $countSql = "SELECT COUNT(DISTINCT customer_name) as total
                        FROM invoices
                        WHERE invoice_date BETWEEN ? AND ?
                            AND customer_name IS NOT NULL
                            AND customer_name != ''
                            $groupFilter";
            $countResult = Database::query($countSql, $params, strtolower($branch));
            $total = (int)($countResult[0]['total'] ?? 0);

            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $data = Database::query($sql, $params, strtolower($branch));
        }

        return ['data' => $data, 'total' => $total];
    }

    /**
     * Get single customer details
     */
    public static function getCustomerDetail(string $customerName, string $branch, string $startDate, string $endDate): array
    {
        $params = [$customerName, $startDate, $endDate . ' 23:59:59'];

        // Summary
        $summarySql = "SELECT
                        SUM(net_price) as total_sales,
                        SUM(units) as total_units,
                        COUNT(DISTINCT invoice_number) as invoice_count,
                        COUNT(DISTINCT product_description) as product_count,
                        MIN(invoice_date) as first_purchase,
                        MAX(invoice_date) as last_purchase,
                        SUM(remake_amount) as remake_amount,
                        SUM(discount_amount) as discount_amount
                    FROM invoices
                    WHERE customer_name = ?
                        AND invoice_date BETWEEN ? AND ?";

        // Products purchased
        $productsSql = "SELECT
                        product_description,
                        SUM(net_price) as total_sales,
                        SUM(units) as total_units,
                        COUNT(DISTINCT invoice_number) as invoice_count
                    FROM invoices
                    WHERE customer_name = ?
                        AND invoice_date BETWEEN ? AND ?
                        AND product_description IS NOT NULL
                    GROUP BY product_description
                    ORDER BY total_sales DESC
                    LIMIT 20";

        // Monthly trend
        $trendSql = "SELECT
                        DATE_FORMAT(invoice_date, '%Y-%m') as month,
                        SUM(net_price) as sales
                    FROM invoices
                    WHERE customer_name = ?
                        AND invoice_date BETWEEN ? AND ?
                    GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
                    ORDER BY month";

        if ($branch === 'ALL') {
            $nycSummary = Database::query($summarySql, $params, 'nyc');
            $hvSummary = Database::query($summarySql, $params, 'hv');
            $summary = self::mergeSummaries($nycSummary[0] ?? [], $hvSummary[0] ?? []);

            $nycProducts = Database::query($productsSql, $params, 'nyc');
            $hvProducts = Database::query($productsSql, $params, 'hv');
            $products = self::mergeProductResults($nycProducts, $hvProducts);

            $nycTrend = Database::query($trendSql, $params, 'nyc');
            $hvTrend = Database::query($trendSql, $params, 'hv');
            $trend = self::mergeTrendResults($nycTrend, $hvTrend);
        } else {
            $branchKey = strtolower($branch);
            $summaryResult = Database::query($summarySql, $params, $branchKey);
            $summary = $summaryResult[0] ?? [];
            $products = Database::query($productsSql, $params, $branchKey);
            $trend = Database::query($trendSql, $params, $branchKey);
        }

        return [
            'customer_name' => $customerName,
            'summary' => $summary,
            'products' => $products,
            'trend' => $trend
        ];
    }

    /**
     * Get inactive customers
     */
    public static function getInactiveCustomers(string $branch, int $days = 90): array
    {
        $cutoffDate = date('Y-m-d', strtotime("-{$days} days"));
        $oneYearAgo = date('Y-m-d', strtotime('-1 year'));

        $sql = "SELECT
                    customer_name,
                    MAX(invoice_date) as last_purchase,
                    SUM(net_price) as total_sales,
                    COUNT(DISTINCT invoice_number) as invoice_count
                FROM invoices
                WHERE customer_name IS NOT NULL
                    AND customer_name != ''
                GROUP BY customer_name
                HAVING MAX(invoice_date) < ? AND MAX(invoice_date) >= ?
                ORDER BY total_sales DESC";

        $params = [$cutoffDate, $oneYearAgo];

        if ($branch === 'ALL') {
            $nycResults = Database::query($sql, $params, 'nyc');
            $hvResults = Database::query($sql, $params, 'hv');
            return self::mergeCustomerResults($nycResults, $hvResults);
        }

        return Database::query($sql, $params, strtolower($branch));
    }

    /**
     * Get new customers
     */
    public static function getNewCustomers(string $branch, string $startDate, string $endDate): array
    {
        $sql = "SELECT
                    customer_name,
                    MIN(invoice_date) as first_purchase,
                    SUM(net_price) as total_sales,
                    SUM(units) as total_units,
                    COUNT(DISTINCT invoice_number) as invoice_count
                FROM invoices
                WHERE customer_name IS NOT NULL
                    AND customer_name != ''
                GROUP BY customer_name
                HAVING MIN(invoice_date) BETWEEN ? AND ?
                ORDER BY first_purchase DESC";

        $params = [$startDate, $endDate . ' 23:59:59'];

        if ($branch === 'ALL') {
            $nycResults = Database::query($sql, $params, 'nyc');
            $hvResults = Database::query($sql, $params, 'hv');

            // For new customers, take earliest first_purchase across branches
            $merged = [];
            foreach (array_merge($nycResults, $hvResults) as $row) {
                $name = $row['customer_name'];
                if (!isset($merged[$name]) || $row['first_purchase'] < $merged[$name]['first_purchase']) {
                    if (!isset($merged[$name])) {
                        $merged[$name] = $row;
                    } else {
                        $merged[$name]['first_purchase'] = min($merged[$name]['first_purchase'], $row['first_purchase']);
                        $merged[$name]['total_sales'] += (float)$row['total_sales'];
                        $merged[$name]['total_units'] += (int)$row['total_units'];
                        $merged[$name]['invoice_count'] += (int)$row['invoice_count'];
                    }
                }
            }

            usort($merged, fn($a, $b) => strcmp($b['first_purchase'], $a['first_purchase']));
            return array_values($merged);
        }

        return Database::query($sql, $params, strtolower($branch));
    }

    /**
     * Compare customers across periods
     */
    public static function compareCustomers(string $branch, string $startDate, string $endDate): array
    {
        // Current period
        $current = self::getCustomerSales($branch, $startDate, $endDate);

        // Previous period of same length
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $diff = $start->diff($end);

        $prevEnd = (clone $start)->modify('-1 day');
        $prevStart = (clone $prevEnd)->modify("-{$diff->days} days");

        $previous = self::getCustomerSales($branch, $prevStart->format('Y-m-d'), $prevEnd->format('Y-m-d'));

        // Calculate changes
        $comparison = [];
        $allCustomers = array_unique(array_merge(array_keys($current), array_keys($previous)));

        foreach ($allCustomers as $name) {
            $currSales = $current[$name] ?? 0;
            $prevSales = $previous[$name] ?? 0;
            $change = $prevSales > 0 ? (($currSales - $prevSales) / $prevSales) * 100 : ($currSales > 0 ? 100 : 0);

            $comparison[] = [
                'customer_name' => $name,
                'current_sales' => $currSales,
                'previous_sales' => $prevSales,
                'change_amount' => $currSales - $prevSales,
                'change_percent' => $change
            ];
        }

        // Sort by absolute change
        usort($comparison, fn($a, $b) => abs($b['change_amount']) <=> abs($a['change_amount']));

        return $comparison;
    }

    private static function getCustomerSales(string $branch, string $startDate, string $endDate): array
    {
        $sql = "SELECT customer_name, SUM(net_price) as total_sales
                FROM invoices
                WHERE invoice_date BETWEEN ? AND ?
                    AND customer_name IS NOT NULL
                    AND customer_name != ''
                GROUP BY customer_name";

        $params = [$startDate, $endDate . ' 23:59:59'];

        if ($branch === 'ALL') {
            $results = Database::queryAllBranches($sql, $params);
        } else {
            $results = Database::query($sql, $params, strtolower($branch));
        }

        $sales = [];
        foreach ($results as $row) {
            $name = $row['customer_name'];
            if (!isset($sales[$name])) {
                $sales[$name] = 0;
            }
            $sales[$name] += (float)$row['total_sales'];
        }

        return $sales;
    }

    private static function getGroupMembers(int $groupId, string $branch): array
    {
        $sql = "SELECT customer_name FROM customer_group_members
                WHERE group_id = ? AND (branch = ? OR branch = 'ALL')";

        $results = Database::query($sql, [$groupId, $branch], 'nyc');
        return array_column($results, 'customer_name');
    }

    private static function mergeCustomerResults(array $nyc, array $hv): array
    {
        $merged = [];

        foreach (array_merge($nyc, $hv) as $row) {
            $name = $row['customer_name'];
            if (!isset($merged[$name])) {
                $merged[$name] = $row;
            } else {
                $merged[$name]['total_sales'] = (float)$merged[$name]['total_sales'] + (float)$row['total_sales'];
                $merged[$name]['total_units'] = (int)$merged[$name]['total_units'] + (int)$row['total_units'];
                $merged[$name]['invoice_count'] = (int)$merged[$name]['invoice_count'] + (int)$row['invoice_count'];
                $merged[$name]['product_count'] = (int)$merged[$name]['product_count'] + (int)$row['product_count'];
                $merged[$name]['remake_amount'] = (float)($merged[$name]['remake_amount'] ?? 0) + (float)($row['remake_amount'] ?? 0);
                $merged[$name]['discount_amount'] = (float)($merged[$name]['discount_amount'] ?? 0) + (float)($row['discount_amount'] ?? 0);
                if (isset($row['last_purchase']) && $row['last_purchase'] > ($merged[$name]['last_purchase'] ?? '')) {
                    $merged[$name]['last_purchase'] = $row['last_purchase'];
                }
            }
        }

        usort($merged, fn($a, $b) => (float)$b['total_sales'] <=> (float)$a['total_sales']);
        return array_values($merged);
    }

    private static function mergeSummaries(array $nyc, array $hv): array
    {
        return [
            'total_sales' => (float)($nyc['total_sales'] ?? 0) + (float)($hv['total_sales'] ?? 0),
            'total_units' => (int)($nyc['total_units'] ?? 0) + (int)($hv['total_units'] ?? 0),
            'invoice_count' => (int)($nyc['invoice_count'] ?? 0) + (int)($hv['invoice_count'] ?? 0),
            'product_count' => (int)($nyc['product_count'] ?? 0) + (int)($hv['product_count'] ?? 0),
            'first_purchase' => min($nyc['first_purchase'] ?? '9999-12-31', $hv['first_purchase'] ?? '9999-12-31'),
            'last_purchase' => max($nyc['last_purchase'] ?? '', $hv['last_purchase'] ?? ''),
            'remake_amount' => (float)($nyc['remake_amount'] ?? 0) + (float)($hv['remake_amount'] ?? 0),
            'discount_amount' => (float)($nyc['discount_amount'] ?? 0) + (float)($hv['discount_amount'] ?? 0)
        ];
    }

    private static function mergeProductResults(array $nyc, array $hv): array
    {
        $merged = [];

        foreach (array_merge($nyc, $hv) as $row) {
            $name = $row['product_description'];
            if (!isset($merged[$name])) {
                $merged[$name] = $row;
            } else {
                $merged[$name]['total_sales'] = (float)$merged[$name]['total_sales'] + (float)$row['total_sales'];
                $merged[$name]['total_units'] = (int)$merged[$name]['total_units'] + (int)$row['total_units'];
                $merged[$name]['invoice_count'] = (int)$merged[$name]['invoice_count'] + (int)$row['invoice_count'];
            }
        }

        usort($merged, fn($a, $b) => (float)$b['total_sales'] <=> (float)$a['total_sales']);
        return array_slice(array_values($merged), 0, 20);
    }

    private static function mergeTrendResults(array $nyc, array $hv): array
    {
        $merged = [];

        foreach (array_merge($nyc, $hv) as $row) {
            $month = $row['month'];
            if (!isset($merged[$month])) {
                $merged[$month] = $row;
            } else {
                $merged[$month]['sales'] = (float)$merged[$month]['sales'] + (float)$row['sales'];
            }
        }

        ksort($merged);
        return array_values($merged);
    }
}
