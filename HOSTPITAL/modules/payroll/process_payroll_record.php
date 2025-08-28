<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing payroll id']);
    exit();
}

try {
    $stmt = $pdo->prepare('SELECT * FROM payroll WHERE id = ?');
    $stmt->execute([$id]);
    $record = $stmt->fetch();
    if (!$record) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Payroll record not found']);
        exit();
    }
    if ($record['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Only pending records can be processed']);
        exit();
    }

    $stmt = $pdo->prepare("UPDATE payroll SET status='processed' WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Payroll marked as processed']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
