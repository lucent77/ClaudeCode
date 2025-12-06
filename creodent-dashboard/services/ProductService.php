<?php
/**
 * Creodent Dashboard - Product Service
 */

namespace Creodent\Services;

use Creodent\Core\Database;

class ProductService
{
    /**
     * Get all products with metrics
     */
    public static function getProducts(string $branch, string $startDate, string $endDate, array $pagination = []): array
    {
        $offset = $pagination['offset'] ?? 0;
        $limit = $pagination['per_page'] ?? 50;

        $sql = "SELECT
                    product_description,
                    product_number,
                    SUM(net_price) as total_sales,
                    SUM(units) as total_units,
                    COUNT(DISTINCT invoice_number) as invoice_count,
                    COUNT(DISTINCT customer_name) as customer_count,
                    SUM(remake_amount) as remake_amount
                FROM invoices
                WHERE invoice_date BETWEEN ? AND ?
                    AND product_description IS NOT NULL
                    AND product_description != ''
                GROUP BY product_description, product_number
                ORDER BY total_sales DESC";

        $params = [$startDate, $endDate . ' 23:59:59'];

        if ($branch === 'ALL') {
            $nycResults = Database::query($sql, $params, 'nyc');
            $hvResults = Database::query($sql, $params, 'hv');
            $merged = self::mergeProductResults($nycResults, $hvResults);
            $total = count($merged);
            $data = array_slice($merged, $offset, $limit);
        } else {
            $countSql = "SELECT COUNT(DISTINCT product_description) as total
                        FROM invoices
                        WHERE invoice_date BETWEEN ? AND ?
                            AND product_description IS NOT NULL
                            AND product_description != ''";
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
     * Get single product details
     */
    public static function getProductDetail(string $productName, string $branch, string $startDate, string $endDate): array
    {
        $params = [$productName, $startDate, $endDate . ' 23:59:59'];

        $summarySql = "SELECT
                        SUM(net_price) as total_sales,
                        SUM(units) as total_units,
                        COUNT(DISTINCT invoice_number) as invoice_count,
                        COUNT(DISTINCT customer_name) as customer_count,
                        SUM(remake_amount) as remake_amount,
                        AVG(net_price / NULLIF(units, 0)) as avg_unit_price
                    FROM invoices
                    WHERE product_description = ?
                        AND invoice_date BETWEEN ? AND ?";

        $customersSql = "SELECT
                        customer_name,
                        SUM(net_price) as total_sales,
                        SUM(units) as total_units,
                        COUNT(DISTINCT invoice_number) as invoice_count
                    FROM invoices
                    WHERE product_description = ?
                        AND invoice_date BETWEEN ? AND ?
                        AND customer_name IS NOT NULL
                    GROUP BY customer_name
                    ORDER BY total_sales DESC
                    LIMIT 20";

        $trendSql = "SELECT
                        DATE_FORMAT(invoice_date, '%Y-%m') as month,
                        SUM(net_price) as sales,
                        SUM(units) as units
                    FROM invoices
                    WHERE product_description = ?
                        AND invoice_date BETWEEN ? AND ?
                    GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
                    ORDER BY month";

        if ($branch === 'ALL') {
            $nycSummary = Database::query($summarySql, $params, 'nyc');
            $hvSummary = Database::query($summarySql, $params, 'hv');
            $summary = self::mergeSummaries($nycSummary[0] ?? [], $hvSummary[0] ?? []);

            $nycCustomers = Database::query($customersSql, $params, 'nyc');
            $hvCustomers = Database::query($customersSql, $params, 'hv');
            $customers = self::mergeCustomerResults($nycCustomers, $hvCustomers);

            $nycTrend = Database::query($trendSql, $params, 'nyc');
            $hvTrend = Database::query($trendSql, $params, 'hv');
            $trend = self::mergeTrendResults($nycTrend, $hvTrend);
        } else {
            $branchKey = strtolower($branch);
            $summaryResult = Database::query($summarySql, $params, $branchKey);
            $summary = $summaryResult[0] ?? [];
            $customers = Database::query($customersSql, $params, $branchKey);
            $trend = Database::query($trendSql, $params, $branchKey);
        }

        return [
            'product_description' => $productName,
            'summary' => $summary,
            'customers' => $customers,
            'trend' => $trend
        ];
    }

    /**
     * Get parts (REF products)
     */
    public static function getParts(string $branch, string $startDate, string $endDate): array
    {
        $sql = "SELECT
                    product_description,
                    product_number,
                    SUM(net_price) as total_sales,
                    SUM(units) as total_units,
                    COUNT(DISTINCT customer_name) as customer_count
                FROM invoices
                WHERE invoice_date BETWEEN ? AND ?
                    AND (product_description LIKE '%REF%' OR product_number LIKE 'REF%')
                GROUP BY product_description, product_number
                ORDER BY total_sales DESC";

        $params = [$startDate, $endDate . ' 23:59:59'];

        if ($branch === 'ALL') {
            $nycResults = Database::query($sql, $params, 'nyc');
            $hvResults = Database::query($sql, $params, 'hv');
            return self::mergeProductResults($nycResults, $hvResults);
        }

        return Database::query($sql, $params, strtolower($branch));
    }

    /**
     * Compare products across periods
     */
    public static function compareProducts(string $branch, string $startDate, string $endDate): array
    {
        $current = self::getProductSales($branch, $startDate, $endDate);

        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $diff = $start->diff($end);

        $prevEnd = (clone $start)->modify('-1 day');
        $prevStart = (clone $prevEnd)->modify("-{$diff->days} days");

        $previous = self::getProductSales($branch, $prevStart->format('Y-m-d'), $prevEnd->format('Y-m-d'));

        $comparison = [];
        $allProducts = array_unique(array_merge(array_keys($current), array_keys($previous)));

        foreach ($allProducts as $name) {
            $currSales = $current[$name] ?? 0;
            $prevSales = $previous[$name] ?? 0;
            $change = $prevSales > 0 ? (($currSales - $prevSales) / $prevSales) * 100 : ($currSales > 0 ? 100 : 0);

            $comparison[] = [
                'product_description' => $name,
                'current_sales' => $currSales,
                'previous_sales' => $prevSales,
                'change_amount' => $currSales - $prevSales,
                'change_percent' => $change
            ];
        }

        usort($comparison, fn($a, $b) => abs($b['change_amount']) <=> abs($a['change_amount']));

        return $comparison;
    }

    private static function getProductSales(string $branch, string $startDate, string $endDate): array
    {
        $sql = "SELECT product_description, SUM(net_price) as total_sales
                FROM invoices
                WHERE invoice_date BETWEEN ? AND ?
                    AND product_description IS NOT NULL
                    AND product_description != ''
                GROUP BY product_description";

        $params = [$startDate, $endDate . ' 23:59:59'];

        if ($branch === 'ALL') {
            $results = Database::queryAllBranches($sql, $params);
        } else {
            $results = Database::query($sql, $params, strtolower($branch));
        }

        $sales = [];
        foreach ($results as $row) {
            $name = $row['product_description'];
            if (!isset($sales[$name])) {
                $sales[$name] = 0;
            }
            $sales[$name] += (float)$row['total_sales'];
        }

        return $sales;
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
                $merged[$name]['invoice_count'] = (int)($merged[$name]['invoice_count'] ?? 0) + (int)($row['invoice_count'] ?? 0);
                $merged[$name]['customer_count'] = (int)($merged[$name]['customer_count'] ?? 0) + (int)($row['customer_count'] ?? 0);
                $merged[$name]['remake_amount'] = (float)($merged[$name]['remake_amount'] ?? 0) + (float)($row['remake_amount'] ?? 0);
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
            'customer_count' => (int)($nyc['customer_count'] ?? 0) + (int)($hv['customer_count'] ?? 0),
            'remake_amount' => (float)($nyc['remake_amount'] ?? 0) + (float)($hv['remake_amount'] ?? 0),
            'avg_unit_price' => ((float)($nyc['avg_unit_price'] ?? 0) + (float)($hv['avg_unit_price'] ?? 0)) / 2
        ];
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
                $merged[$month]['units'] = (int)$merged[$month]['units'] + (int)$row['units'];
            }
        }

        ksort($merged);
        return array_values($merged);
    }
}
