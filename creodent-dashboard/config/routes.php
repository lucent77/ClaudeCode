<?php
/**
 * Creodent Dashboard - Route Definitions
 */

return [
    // Main pages
    'dashboard' => [
        'file' => 'pages/dashboard.php',
        'title' => 'Dashboard',
        'icon' => 'home',
        'nav' => true,
        'roles' => ['admin', 'viewer']
    ],

    // Customer pages
    'customers' => [
        'file' => 'pages/customers.php',
        'title' => 'Customers',
        'icon' => 'users',
        'nav' => true,
        'roles' => ['admin', 'viewer']
    ],
    'customer-detail' => [
        'file' => 'pages/customer-detail.php',
        'title' => 'Customer Details',
        'icon' => 'user',
        'nav' => false,
        'roles' => ['admin', 'viewer']
    ],
    'customer-compare' => [
        'file' => 'pages/customer-compare.php',
        'title' => 'Customer Comparison',
        'icon' => 'scale',
        'nav' => true,
        'roles' => ['admin', 'viewer']
    ],
    'inactive-customers' => [
        'file' => 'pages/inactive-customers.php',
        'title' => 'Inactive Customers',
        'icon' => 'user-minus',
        'nav' => true,
        'roles' => ['admin', 'viewer']
    ],
    'new-customers' => [
        'file' => 'pages/new-customers.php',
        'title' => 'New Customers',
        'icon' => 'user-plus',
        'nav' => true,
        'roles' => ['admin', 'viewer']
    ],
    'customer-groups' => [
        'file' => 'pages/customer-groups.php',
        'title' => 'Customer Groups',
        'icon' => 'folder',
        'nav' => true,
        'roles' => ['admin']
    ],

    // Product pages
    'products' => [
        'file' => 'pages/products.php',
        'title' => 'Products',
        'icon' => 'cube',
        'nav' => true,
        'roles' => ['admin', 'viewer']
    ],
    'product-detail' => [
        'file' => 'pages/product-detail.php',
        'title' => 'Product Details',
        'icon' => 'cube',
        'nav' => false,
        'roles' => ['admin', 'viewer']
    ],
    'product-compare' => [
        'file' => 'pages/product-compare.php',
        'title' => 'Product Comparison',
        'icon' => 'chart-bar',
        'nav' => true,
        'roles' => ['admin', 'viewer']
    ],
    'parts' => [
        'file' => 'pages/parts.php',
        'title' => 'Parts (REF)',
        'icon' => 'wrench',
        'nav' => true,
        'roles' => ['admin', 'viewer']
    ],

    // Remake pages
    'remakes' => [
        'file' => 'pages/remakes.php',
        'title' => 'Remakes Overview',
        'icon' => 'refresh',
        'nav' => true,
        'roles' => ['admin', 'viewer']
    ],
    'remake-customers' => [
        'file' => 'pages/remake-customers.php',
        'title' => 'Remake by Customer',
        'icon' => 'users',
        'nav' => true,
        'roles' => ['admin', 'viewer']
    ],
    'remake-products' => [
        'file' => 'pages/remake-products.php',
        'title' => 'Remake by Product',
        'icon' => 'cube',
        'nav' => true,
        'roles' => ['admin', 'viewer']
    ],

    // Analysis pages
    'discounts' => [
        'file' => 'pages/discounts.php',
        'title' => 'Discount Analysis',
        'icon' => 'tag',
        'nav' => true,
        'roles' => ['admin', 'viewer']
    ],
    'risk-insights' => [
        'file' => 'pages/risk-insights.php',
        'title' => 'Risk Insights',
        'icon' => 'exclamation-triangle',
        'nav' => true,
        'roles' => ['admin', 'viewer']
    ]
];
