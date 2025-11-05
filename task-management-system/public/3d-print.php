<?php
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../includes/TaskManager.php';

$auth = new Auth();
$auth->requireLogin();

$departmentId = 4; // 3D Print department ID
$departmentName = '3D Print';
$departmentCode = '3D_PRINT';
$departmentIcon = '<i class="fas fa-cube mr-2"></i>';

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

// Define columns for 3D Print
$columns = [
    ['label' => 'Case #', 'field' => 'case_number', 'nowrap' => true],
    ['label' => 'Date', 'field' => 'date', 'nowrap' => true],
    ['label' => 'Type', 'field' => 'type'],
    ['label' => 'Patient #', 'field' => 'patient_number'],
    ['label' => 'Tooth #', 'field' => 'tooth'],
    ['label' => 'Design', 'field' => 'design'],
    ['label' => 'Lab #', 'field' => 'lab_number'],
    ['label' => 'Notes', 'field' => 'notes']
];

// Define form fields for create modal
$formFields = [
    ['name' => 'case_number', 'label' => 'Case #', 'type' => 'text', 'required' => true],
    ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'default' => date('Y-m-d')],
    ['name' => 'type', 'label' => 'Type (MODEL, GUIDE, etc)', 'type' => 'text'],
    ['name' => 'patient_number', 'label' => 'Patient #', 'type' => 'text'],
    ['name' => 'tooth', 'label' => 'Tooth #', 'type' => 'text'],
    ['name' => 'design', 'label' => 'Design', 'type' => 'text'],
    ['name' => 'lab_number', 'label' => 'Lab #', 'type' => 'text'],
    ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea', 'fullWidth' => true]
];

$pageTitle = '3D Print Department';
$showNav = true;
?>

<?php include __DIR__ . '/../views/layouts/header.php'; ?>
<?php include __DIR__ . '/../views/components/department_tasks.php'; ?>
<?php include __DIR__ . '/../views/layouts/footer.php'; ?>
