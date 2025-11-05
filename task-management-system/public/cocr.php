<?php
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../includes/TaskManager.php';

$auth = new Auth();
$auth->requireLogin();

$departmentId = 3; // COCR department ID
$departmentName = 'COCR';
$departmentCode = 'COCR';
$departmentIcon = '<i class="fas fa-crown mr-2"></i>';

// Check access
if (!$auth->canAccessDepartment($departmentId)) {
    header('Location: /public/index.php');
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

// Define columns for COCR
$columns = [
    ['label' => 'ID', 'field' => 'external_id', 'nowrap' => true],
    ['label' => 'Date', 'field' => 'date', 'nowrap' => true],
    ['label' => 'Type', 'field' => 'type'],
    ['label' => 'Tooth #', 'field' => 'tooth'],
    ['label' => 'Implant Type', 'field' => 'implant_type'],
    ['label' => 'Design', 'field' => 'design'],
    ['label' => 'Lab', 'field' => 'lab'],
    ['label' => 'Notes', 'field' => 'notes']
];

// Define form fields for create modal
$formFields = [
    ['name' => 'external_id', 'label' => 'ID', 'type' => 'text'],
    ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'default' => date('Y-m-d')],
    ['name' => 'type', 'label' => 'Type (CR, BRIDGE, etc)', 'type' => 'text'],
    ['name' => 'tooth', 'label' => 'Tooth #', 'type' => 'text'],
    ['name' => 'implant_type', 'label' => 'Implant Type', 'type' => 'text', 'fullWidth' => true],
    ['name' => 'design', 'label' => 'Design', 'type' => 'text'],
    ['name' => 'lab', 'label' => 'Lab', 'type' => 'text'],
    ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea', 'fullWidth' => true]
];

$pageTitle = 'COCR Department';
$showNav = true;
?>

<?php include __DIR__ . '/../views/layouts/header.php'; ?>
<?php include __DIR__ . '/../views/components/department_tasks.php'; ?>
<?php include __DIR__ . '/../views/layouts/footer.php'; ?>
