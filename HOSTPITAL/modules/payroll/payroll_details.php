<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit(); }

$period = $_GET['period'] ?? '';
if (!$period) { header('Location: index.php'); exit(); }

// Parse YYYY-MM to month/year
[$year, $monthStr] = explode('-', $period);
$year = (int)$year; $month = (int)$monthStr;

$stmt = $pdo->prepare("SELECT p.*, e.first_name, e.last_name, d.name AS department_name
FROM payroll p
LEFT JOIN employees e ON p.employee_id = e.id
LEFT JOIN departments d ON e.department_id = d.id
WHERE p.year = ? AND p.month = ?
ORDER BY e.last_name");
$stmt->execute([$year, $month]);
$records = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payroll Details - <?php echo htmlspecialchars($period); ?></title>
<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../../includes/header.php'; ?>
<div class="container">
  <div class="page-header">
    <h1><i class="fas fa-list"></i> Payroll Details (<?php echo htmlspecialchars($period); ?>)</h1>
    <div class="header-actions">
      <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
      <a href="approve_payroll.php?period=<?php echo urlencode($period); ?>" class="btn btn-success"><i class="fas fa-check"></i> Approve All Pending</a>
    </div>
  </div>

  <div class="table-container">
    <table class="table" id="detailsTable">
      <thead>
        <tr>
          <th>Employee</th>
          <th>Department</th>
          <th>Basic</th>
          <th>Allowances</th>
          <th>Deductions</th>
          <th>Net</th>
          <th>Status</th>
          <th>Payment Date</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($records as $r): ?>
        <tr>
          <td><?php echo htmlspecialchars($r['first_name'].' '.$r['last_name']); ?></td>
          <td><?php echo htmlspecialchars($r['department_name'] ?? ''); ?></td>
          <td>₱<?php echo number_format($r['basic_salary'],2); ?></td>
          <td>₱<?php echo number_format($r['allowances'],2); ?></td>
          <td>₱<?php echo number_format($r['deductions'],2); ?></td>
          <td class="salary">₱<?php echo number_format($r['net_salary'],2); ?></td>
          <td><span class="badge badge-<?php echo $r['status']==='paid'?'success':($r['status']==='processed'?'warning':'info'); ?>"><?php echo ucfirst($r['status']); ?></span></td>
          <td><?php echo $r['payment_date']? date('M d, Y', strtotime($r['payment_date'])):'N/A'; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</div>
<script src="../../assets/js/main.js"></script>
</body>
</html>
