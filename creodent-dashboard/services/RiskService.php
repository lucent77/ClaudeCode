<?php
/**
 * Creodent Dashboard - Risk Insights Service
 */

namespace Creodent\Services;

use Creodent\Core\Database;

class RiskService
{
    /**
     * Get at-risk customers (declining sales)
     */
    public static function getAtRiskCustomers(string $branch, string $startDate, string $endDate, int $threshold = 20): array
    {
        // Compare current period with previous period of same length
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $diff = $start->diff($end);

        $prevEnd = (clone $start)->modify('-1 day');
        $prevStart = (clone $prevEnd)->modify("-{$diff->days} days");

        $currentSales = self::getCustomerSales($branch, $startDate, $endDate);
        $previousSales = self::getCustomerSales($branch, $prevStart->format('Y-m-d'), $prevEnd->format('Y-m-d'));

        $atRisk = [];

        foreach ($previousSales as $name => $prevSale) {
            $currSale = $currentSales[$name] ?? 0;

            if ($prevSale > 0) {
                $changePercent = (($currSale - $prevSale) / $prevSale) * 100;

                if ($changePercent <= -$threshold) {
                    $atRisk[] = [
                        'customer_name' => $name,
                        'current_sales' => $currSale,
                        'previous_sales' => $prevSale,
                        'change_amount' => $currSale - $prevSale,
                        'change_percent' => $changePercent,
                        'risk_score' => self::calculateRiskScore($changePercent, $prevSale)
                    ];
                }
            }
        }

        // Sort by risk score descending
        usort($atRisk, fn($a, $b) => $b['risk_score'] <=> $a['risk_score']);

        return $atRisk;
    }

    /**
     * Get at-risk products (declining sales)
     */
    public static function getAtRiskProducts(string $branch, string $startDate, string $endDate, int $threshold = 20): array
    {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $diff = $start->diff($end);

        $prevEnd = (clone $start)->modify('-1 day');
        $prevStart = (clone $prevEnd)->modify("-{$diff->days} days");

        $currentSales = self::getProductSales($branch, $startDate, $endDate);
        $previousSales = self::getProductSales($branch, $prevStart->format('Y-m-d'), $prevEnd->format('Y-m-d'));

        $atRisk = [];

        foreach ($previousSales as $name => $prevSale) {
            $currSale = $currentSales[$name] ?? 0;

            if ($prevSale > 0) {
                $changePercent = (($currSale - $prevSale) / $prevSale) * 100;

                if ($changePercent <= -$threshold) {
                    $atRisk[] = [
                        'product_description' => $name,
                        'current_sales' => $currSale,
                        'previous_sales' => $prevSale,
                        'change_amount' => $currSale - $prevSale,
                        'change_percent' => $changePercent,
                        'risk_score' => self::calculateRiskScore($changePercent, $prevSale)
                    ];
                }
            }
        }

        usort($atRisk, fn($a, $b) => $b['risk_score'] <=> $a['risk_score']);

        return $atRisk;
    }

    /**
     * Get risk summary
     */
    public static function getSummary(string $branch, string $startDate, string $endDate): array
    {
        $atRiskCustomers = self::getAtRiskCustomers($branch, $startDate, $endDate);
        $atRiskProducts = self::getAtRiskProducts($branch, $startDate, $endDate);

        $potentialLossCustomers = array_sum(array_column($atRiskCustomers, 'previous_sales'));
        $potentialLossProducts = 0;

        foreach ($atRiskProducts as $product) {
            $potentialLossProducts += abs($product['change_amount']);
        }

        return [
            'at_risk_customers' => count($atRiskCustomers),
            'at_risk_products' => count($atRiskProducts),
            'potential_loss_customers' => $potentialLossCustomers,
            'avg_decline_customers' => count($atRiskCustomers) > 0
                ? array_sum(array_column($atRiskCustomers, 'change_percent')) / count($atRiskCustomers)
                : 0,
            'avg_decline_products' => count($atRiskProducts) > 0
                ? array_sum(array_column($atRiskProducts, 'change_percent')) / count($atRiskProducts)
                : 0
        ];
    }

    /**
     * Get risk distribution
     */
    public static function getDistribution(string $branch, string $startDate, string $endDate): array
    {
        $atRiskCustomers = self::getAtRiskCustomers($branch, $startDate, $endDate, 10);

        $distribution = [
            'Low (10-20% decline)' => 0,
            'Medium (20-40% decline)' => 0,
            'High (40-60% decline)' => 0,
            'Critical (60%+ decline)' => 0
        ];

        foreach ($atRiskCustomers as $customer) {
            $decline = abs($customer['change_percent']);

            if ($decline >= 60) {
                $distribution['Critical (60%+ decline)']++;
            } elseif ($decline >= 40) {
                $distribution['High (40-60% decline)']++;
            } elseif ($decline >= 20) {
                $distribution['Medium (20-40% decline)']++;
            } else {
                $distribution['Low (10-20% decline)']++;
            }
        }

        return $distribution;
    }

    /**
     * Calculate risk score (higher = more risky)
     */
    private static function calculateRiskScore(float $changePercent, float $previousSales): float
    {
        // Normalize decline (0-100 scale)
        $declineScore = min(100, abs($changePercent));

        // Normalize revenue impact (log scale)
        $revenueScore = min(100, log10(max(1, $previousSales)) * 20);

        // Weighted combination
        return ($declineScore * 0.6) + ($revenueScore * 0.4);
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
}
