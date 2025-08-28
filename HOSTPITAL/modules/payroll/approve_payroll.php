<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit(); }

$period = $_GET['period'] ?? '';
if (!$period) { header('Location: index.php'); exit(); }

try {
    $pdo->beginTransaction();
    $today = date('Y-m-d');

    // Approve by period or month/year
[$year, $monthStr] = explode('-', $period);
$year = (int)$year; $month = (int)$monthStr;
$stmt = $pdo->prepare("UPDATE payroll SET status='paid', payment_date=?, updated_at=CURRENT_TIMESTAMP 
        WHERE status IN ('pending','processed') AND year = ? AND month = ?");
$stmt->execute([$today, $year, $month]);
    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
}

header('Location: payroll_details.php?period=' . urlencode($period));
exit();
