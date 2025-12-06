<?php
/**
 * Creodent Dashboard - API Endpoint Router
 *
 * Single entry point for all API requests
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Request.php';
require_once dirname(__DIR__) . '/core/Response.php';
require_once dirname(__DIR__) . '/services/DashboardService.php';
require_once dirname(__DIR__) . '/services/CustomerService.php';
require_once dirname(__DIR__) . '/services/ProductService.php';
require_once dirname(__DIR__) . '/services/RemakeService.php';
require_once dirname(__DIR__) . '/services/DiscountService.php';
require_once dirname(__DIR__) . '/services/RiskService.php';
require_once dirname(__DIR__) . '/services/GroupService.php';

use Creodent\Core\{Auth, Request, Response};
use Creodent\Services\{
    DashboardService,
    CustomerService,
    ProductService,
    RemakeService,
    DiscountService,
    RiskService,
    GroupService
};

date_default_timezone_set(TIMEZONE);
Response::securityHeaders();
Response::cacheHeaders(0);

// Require authentication
if (!Auth::check()) {
    Response::unauthorized('Please log in to access the API');
}

// Get action and common parameters
$action = Request::input('action');
$branch = Request::branch();
$dateRange = Request::dateRange();
$pagination = Request::pagination();

if (!$action) {
    Response::error('Action parameter is required', 'MISSING_ACTION');
}

// Build common meta
$meta = [
    'branch' => $branch,
    'period' => [
        'type' => $dateRange['type'],
        'start' => $dateRange['start_date'],
        'end' => $dateRange['end_date']
    ]
];

try {
    switch ($action) {
        // Dashboard
        case 'dashboard':
            $summary = DashboardService::getSummary($branch, $dateRange['start_date'], $dateRange['end_date']);
            $prevSummary = DashboardService::getPreviousPeriodSummary($branch, $dateRange['start_date'], $dateRange['end_date']);
            $trend = DashboardService::getMonthlySalesTrend($branch, $dateRange['year']);
            $topCustomers = DashboardService::getTopCustomers($branch, $dateRange['start_date'], $dateRange['end_date']);
            $topProducts = DashboardService::getTopProducts($branch, $dateRange['start_date'], $dateRange['end_date']);
            $distribution = DashboardService::getCustomerDistribution($branch, $dateRange['start_date'], $dateRange['end_date']);

            Response::success([
                'summary' => $summary,
                'previous_summary' => $prevSummary,
                'trend' => $trend,
                'top_customers' => $topCustomers,
                'top_products' => $topProducts,
                'distribution' => $distribution
            ], $meta);

        // Customers
        case 'customers':
            $groupId = Request::int('group_id');
            $result = CustomerService::getCustomers($branch, $dateRange['start_date'], $dateRange['end_date'], $groupId, $pagination);
            Response::success($result['data'], array_merge($meta, ['total' => $result['total'], 'pagination' => $pagination]));

        case 'customer_detail':
            $name = Request::input('name');
            if (!$name) {
                Response::error('Customer name is required', 'MISSING_NAME');
            }
            $data = CustomerService::getCustomerDetail($name, $branch, $dateRange['start_date'], $dateRange['end_date']);
            Response::success($data, $meta);

        case 'customer_compare':
            $data = CustomerService::compareCustomers($branch, $dateRange['start_date'], $dateRange['end_date']);
            Response::success($data, $meta);

        case 'inactive_customers':
            $days = Request::int('days', 90);
            $data = CustomerService::getInactiveCustomers($branch, $days);
            Response::success($data, $meta);

        case 'new_customers':
            $data = CustomerService::getNewCustomers($branch, $dateRange['start_date'], $dateRange['end_date']);
            Response::success($data, $meta);

        // Products
        case 'products':
            $result = ProductService::getProducts($branch, $dateRange['start_date'], $dateRange['end_date'], $pagination);
            Response::success($result['data'], array_merge($meta, ['total' => $result['total'], 'pagination' => $pagination]));

        case 'product_detail':
            $name = Request::input('name');
            if (!$name) {
                Response::error('Product name is required', 'MISSING_NAME');
            }
            $data = ProductService::getProductDetail($name, $branch, $dateRange['start_date'], $dateRange['end_date']);
            Response::success($data, $meta);

        case 'product_compare':
            $data = ProductService::compareProducts($branch, $dateRange['start_date'], $dateRange['end_date']);
            Response::success($data, $meta);

        case 'parts':
            $data = ProductService::getParts($branch, $dateRange['start_date'], $dateRange['end_date']);
            Response::success($data, $meta);

        // Remakes
        case 'remakes':
            $summary = RemakeService::getSummary($branch, $dateRange['start_date'], $dateRange['end_date']);
            $trend = RemakeService::getTrend($branch, $dateRange['start_date'], $dateRange['end_date']);
            Response::success(['summary' => $summary, 'trend' => $trend], $meta);

        case 'remake_customers':
            $data = RemakeService::getByCustomer($branch, $dateRange['start_date'], $dateRange['end_date']);
            Response::success($data, $meta);

        case 'remake_products':
            $data = RemakeService::getByProduct($branch, $dateRange['start_date'], $dateRange['end_date']);
            Response::success($data, $meta);

        // Discounts
        case 'discounts':
            $summary = DiscountService::getSummary($branch, $dateRange['start_date'], $dateRange['end_date']);
            $trend = DiscountService::getTrend($branch, $dateRange['start_date'], $dateRange['end_date']);
            $distribution = DiscountService::getDistribution($branch, $dateRange['start_date'], $dateRange['end_date']);
            Response::success(['summary' => $summary, 'trend' => $trend, 'distribution' => $distribution], $meta);

        case 'discount_customers':
            $data = DiscountService::getTopCustomers($branch, $dateRange['start_date'], $dateRange['end_date']);
            Response::success($data, $meta);

        case 'discount_products':
            $data = DiscountService::getTopProducts($branch, $dateRange['start_date'], $dateRange['end_date']);
            Response::success($data, $meta);

        // Risk Insights
        case 'risk_insights':
            $summary = RiskService::getSummary($branch, $dateRange['start_date'], $dateRange['end_date']);
            $atRiskCustomers = RiskService::getAtRiskCustomers($branch, $dateRange['start_date'], $dateRange['end_date']);
            $atRiskProducts = RiskService::getAtRiskProducts($branch, $dateRange['start_date'], $dateRange['end_date']);
            $distribution = RiskService::getDistribution($branch, $dateRange['start_date'], $dateRange['end_date']);
            Response::success([
                'summary' => $summary,
                'at_risk_customers' => array_slice($atRiskCustomers, 0, 20),
                'at_risk_products' => array_slice($atRiskProducts, 0, 20),
                'distribution' => $distribution
            ], $meta);

        // Customer Groups
        case 'groups':
            $data = GroupService::getGroups();
            Response::success($data, $meta);

        case 'group_detail':
            $id = Request::int('id');
            if (!$id) {
                Response::error('Group ID is required', 'MISSING_ID');
            }
            $data = GroupService::getGroup($id);
            if (!$data) {
                Response::notFound('Group not found');
            }
            $stats = GroupService::getGroupStats($id, $branch, $dateRange['start_date'], $dateRange['end_date']);
            $data['stats'] = $stats;
            Response::success($data, $meta);

        case 'create_group':
            if (!Auth::isAdmin()) {
                Response::forbidden('Admin access required');
            }
            if (!Request::isPost() || !Request::validateCsrf()) {
                Response::error('Invalid request', 'INVALID_REQUEST');
            }
            $name = Request::input('name');
            if (!$name) {
                Response::error('Group name is required', 'MISSING_NAME');
            }
            $description = Request::input('description');
            $id = GroupService::createGroup($name, $description);
            Response::success(['id' => $id], $meta);

        case 'update_group':
            if (!Auth::isAdmin()) {
                Response::forbidden('Admin access required');
            }
            if (!Request::isPost() || !Request::validateCsrf()) {
                Response::error('Invalid request', 'INVALID_REQUEST');
            }
            $id = Request::int('id');
            $name = Request::input('name');
            if (!$id || !$name) {
                Response::error('Group ID and name are required', 'MISSING_PARAMS');
            }
            $description = Request::input('description');
            $isActive = Request::input('is_active', true);
            $success = GroupService::updateGroup($id, $name, $description, $isActive);
            Response::success(['success' => $success], $meta);

        case 'delete_group':
            if (!Auth::isAdmin()) {
                Response::forbidden('Admin access required');
            }
            if (!Request::isPost() || !Request::validateCsrf()) {
                Response::error('Invalid request', 'INVALID_REQUEST');
            }
            $id = Request::int('id');
            if (!$id) {
                Response::error('Group ID is required', 'MISSING_ID');
            }
            $success = GroupService::deleteGroup($id);
            Response::success(['success' => $success], $meta);

        case 'add_group_member':
            if (!Auth::isAdmin()) {
                Response::forbidden('Admin access required');
            }
            if (!Request::isPost() || !Request::validateCsrf()) {
                Response::error('Invalid request', 'INVALID_REQUEST');
            }
            $groupId = Request::int('group_id');
            $customerName = Request::input('customer_name');
            $memberBranch = Request::input('branch', 'ALL');
            if (!$groupId || !$customerName) {
                Response::error('Group ID and customer name are required', 'MISSING_PARAMS');
            }
            $success = GroupService::addMember($groupId, $customerName, $memberBranch);
            Response::success(['success' => $success], $meta);

        case 'remove_group_member':
            if (!Auth::isAdmin()) {
                Response::forbidden('Admin access required');
            }
            if (!Request::isPost() || !Request::validateCsrf()) {
                Response::error('Invalid request', 'INVALID_REQUEST');
            }
            $groupId = Request::int('group_id');
            $customerName = Request::input('customer_name');
            if (!$groupId || !$customerName) {
                Response::error('Group ID and customer name are required', 'MISSING_PARAMS');
            }
            $success = GroupService::removeMember($groupId, $customerName);
            Response::success(['success' => $success], $meta);

        case 'available_customers':
            $data = GroupService::getAvailableCustomers($branch);
            Response::success($data, $meta);

        default:
            Response::error("Unknown action: {$action}", 'UNKNOWN_ACTION');
    }
} catch (\Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    Response::error('An error occurred processing your request', 'SERVER_ERROR', 500);
}
