<?php
session_start();
require_once '../../config/database.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if it's a DELETE request
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get employee ID from JSON body or query string
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
$employee_id = $input['id'] ?? ($_GET['id'] ?? null);

if (!$employee_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
    exit();
}

try {
    // Check if employee exists
    $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch();

    if (!$employee) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit();
    }

    // Check if employee has related records (payroll, attendance, etc.)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM payroll WHERE employee_id = ?");
    $stmt->execute([$employee_id]);
    $payroll_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE employee_id = ?");
    $stmt->execute([$employee_id]);
    $attendance_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM employee_benefits WHERE employee_id = ?");
    $stmt->execute([$employee_id]);
    $benefits_count = $stmt->fetchColumn();

    // If employee has related records, soft delete instead of hard delete
    if ($payroll_count > 0 || $attendance_count > 0 || $benefits_count > 0) {
        // Soft delete - update status to terminated
        $stmt = $pdo->prepare("UPDATE employees SET status = 'terminated', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$employee_id]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Employee status updated to terminated due to existing records',
            'action' => 'soft_delete'
        ]);
    } else {
        // Hard delete - remove employee completely
        $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->execute([$employee_id]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Employee deleted successfully',
            'action' => 'hard_delete'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error deleting employee: ' . $e->getMessage()]);
}
?>
