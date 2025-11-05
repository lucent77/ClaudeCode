<?php
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Security.php';
require_once __DIR__ . '/includes/TaskManager.php';

$auth = new Auth();
$auth->requireLogin();

$departmentId = 2; // Solidex department ID
$departmentName = 'Solidex';
$departmentCode = 'SOLIDEX';
$departmentIcon = '<i class="fas fa-tooth mr-2"></i>';

// Check access
if (!$auth->canAccessDepartment($departmentId)) {
    header('Location: /index.php');
    exit;
}

$taskManager = new TaskManager();

// Get filters
$filters = ['department_id' => $departmentId];
if (isset($_GET['status'])) {
    $filters['status_id'] = $_GET['status'];
}
if (isset($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}
if (isset($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'];
}

$tasks = $taskManager->getTasks($filters);

// Define columns for Solidex
$columns = [
    ['label' => 'ID', 'field' => 'external_id', 'nowrap' => true],
    ['label' => 'Date', 'field' => 'date', 'nowrap' => true],
    ['label' => 'Due Date', 'field' => 'due_date', 'nowrap' => true],
    ['label' => 'Teeth', 'field' => 'teeth'],
    ['label' => 'Implant System', 'field' => 'implant_system'],
    ['label' => 'Design', 'field' => 'design'],
    ['label' => 'Lab', 'field' => 'lab'],
    ['label' => 'Notes', 'field' => 'notes']
];

// Define form fields for create modal
$formFields = [
    ['name' => 'external_id', 'label' => 'ID', 'type' => 'text'],
    ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'default' => date('Y-m-d')],
    ['name' => 'due_date', 'label' => 'Due Date', 'type' => 'date', 'required' => true],
    ['name' => 'teeth', 'label' => 'Teeth', 'type' => 'text'],
    ['name' => 'implant_system', 'label' => 'Implant System', 'type' => 'text'],
    ['name' => 'design', 'label' => 'Design', 'type' => 'text'],
    ['name' => 'lab', 'label' => 'Lab', 'type' => 'text', 'fullWidth' => true],
    ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea', 'fullWidth' => true]
];

$pageTitle = 'Solidex Department';
$showNav = true;
?>

<?php include __DIR__ . '/views/layouts/header.php'; ?>
<?php include __DIR__ . '/views/components/department_tasks.php'; ?>
<?php include __DIR__ . '/views/layouts/footer.php'; ?>
