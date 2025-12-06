<?php
/**
 * Creodent Dashboard - Customer Group Service
 */

namespace Creodent\Services;

use Creodent\Core\Database;

class GroupService
{
    /**
     * Get all groups
     */
    public static function getGroups(): array
    {
        $sql = "SELECT
                    g.id,
                    g.name,
                    g.description,
                    g.is_active,
                    g.created_at,
                    COUNT(m.id) as member_count
                FROM customer_groups g
                LEFT JOIN customer_group_members m ON g.id = m.group_id
                GROUP BY g.id
                ORDER BY g.name";

        return Database::query($sql, [], 'nyc');
    }

    /**
     * Get single group with members
     */
    public static function getGroup(int $id): ?array
    {
        $sql = "SELECT * FROM customer_groups WHERE id = ?";
        $result = Database::query($sql, [$id], 'nyc');

        if (empty($result)) {
            return null;
        }

        $group = $result[0];

        // Get members
        $membersSql = "SELECT * FROM customer_group_members WHERE group_id = ? ORDER BY customer_name";
        $group['members'] = Database::query($membersSql, [$id], 'nyc');

        return $group;
    }

    /**
     * Create new group
     */
    public static function createGroup(string $name, ?string $description = null): int
    {
        Database::execute(
            "INSERT INTO customer_groups (name, description) VALUES (?, ?)",
            [$name, $description],
            'nyc'
        );

        return (int) Database::lastInsertId('nyc');
    }

    /**
     * Update group
     */
    public static function updateGroup(int $id, string $name, ?string $description = null, bool $isActive = true): bool
    {
        $rows = Database::execute(
            "UPDATE customer_groups SET name = ?, description = ?, is_active = ? WHERE id = ?",
            [$name, $description, $isActive ? 1 : 0, $id],
            'nyc'
        );

        return $rows > 0;
    }

    /**
     * Delete group
     */
    public static function deleteGroup(int $id): bool
    {
        $rows = Database::execute(
            "DELETE FROM customer_groups WHERE id = ?",
            [$id],
            'nyc'
        );

        return $rows > 0;
    }

    /**
     * Add member to group
     */
    public static function addMember(int $groupId, string $customerName, string $branch = 'ALL'): bool
    {
        try {
            Database::execute(
                "INSERT INTO customer_group_members (group_id, customer_name, branch) VALUES (?, ?, ?)",
                [$groupId, $customerName, $branch],
                'nyc'
            );
            return true;
        } catch (\Exception $e) {
            // Likely duplicate entry
            return false;
        }
    }

    /**
     * Remove member from group
     */
    public static function removeMember(int $groupId, string $customerName): bool
    {
        $rows = Database::execute(
            "DELETE FROM customer_group_members WHERE group_id = ? AND customer_name = ?",
            [$groupId, $customerName],
            'nyc'
        );

        return $rows > 0;
    }

    /**
     * Get group members' names
     */
    public static function getGroupMemberNames(int $groupId, ?string $branch = null): array
    {
        if ($branch && $branch !== 'ALL') {
            $sql = "SELECT customer_name FROM customer_group_members
                    WHERE group_id = ? AND (branch = ? OR branch = 'ALL')";
            $results = Database::query($sql, [$groupId, $branch], 'nyc');
        } else {
            $sql = "SELECT customer_name FROM customer_group_members WHERE group_id = ?";
            $results = Database::query($sql, [$groupId], 'nyc');
        }

        return array_column($results, 'customer_name');
    }

    /**
     * Get all available customers (for adding to groups)
     */
    public static function getAvailableCustomers(string $branch = 'ALL'): array
    {
        $sql = "SELECT DISTINCT customer_name
                FROM invoices
                WHERE customer_name IS NOT NULL
                    AND customer_name != ''
                ORDER BY customer_name";

        if ($branch === 'ALL') {
            $results = Database::queryAllBranches($sql, []);
            $customers = array_unique(array_column($results, 'customer_name'));
            sort($customers);
            return $customers;
        }

        $results = Database::query($sql, [], strtolower($branch));
        return array_column($results, 'customer_name');
    }

    /**
     * Get group statistics
     */
    public static function getGroupStats(int $groupId, string $branch, string $startDate, string $endDate): array
    {
        $members = self::getGroupMemberNames($groupId, $branch);

        if (empty($members)) {
            return [
                'total_sales' => 0,
                'customer_count' => 0,
                'invoice_count' => 0,
                'avg_sales_per_customer' => 0
            ];
        }

        $placeholders = implode(',', array_fill(0, count($members), '?'));

        $sql = "SELECT
                    COALESCE(SUM(net_price), 0) as total_sales,
                    COUNT(DISTINCT customer_name) as customer_count,
                    COUNT(DISTINCT invoice_number) as invoice_count
                FROM invoices
                WHERE invoice_date BETWEEN ? AND ?
                    AND customer_name IN ($placeholders)";

        $params = array_merge([$startDate, $endDate . ' 23:59:59'], $members);

        if ($branch === 'ALL') {
            $nycResult = Database::query($sql, $params, 'nyc');
            $hvResult = Database::query($sql, $params, 'hv');

            $totalSales = (float)($nycResult[0]['total_sales'] ?? 0) + (float)($hvResult[0]['total_sales'] ?? 0);
            $customerCount = (int)($nycResult[0]['customer_count'] ?? 0) + (int)($hvResult[0]['customer_count'] ?? 0);
            $invoiceCount = (int)($nycResult[0]['invoice_count'] ?? 0) + (int)($hvResult[0]['invoice_count'] ?? 0);
        } else {
            $result = Database::query($sql, $params, strtolower($branch));
            $totalSales = (float)($result[0]['total_sales'] ?? 0);
            $customerCount = (int)($result[0]['customer_count'] ?? 0);
            $invoiceCount = (int)($result[0]['invoice_count'] ?? 0);
        }

        return [
            'total_sales' => $totalSales,
            'customer_count' => $customerCount,
            'invoice_count' => $invoiceCount,
            'avg_sales_per_customer' => $customerCount > 0 ? $totalSales / $customerCount : 0
        ];
    }
}
