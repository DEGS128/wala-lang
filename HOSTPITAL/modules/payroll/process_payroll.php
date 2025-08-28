<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$message = '';
$error = '';
$processed_count = 0;

// Fetch departments for filtering
$stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
$departments = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $period = $_POST['period'] ?? '';
        $department_id = $_POST['department_id'] ?? '';
        
        if (empty($period)) {
            throw new Exception("Payroll period is required.");
        }

        // Parse month/year from input (YYYY-MM)
        [$year, $monthStr] = explode('-', $period);
        $year = (int)$year;
        $month = (int)$monthStr;
        if ($month < 1 || $month > 12 || $year < 1970) {
            throw new Exception('Invalid payroll period.');
        }

        // Check if payroll for this period already exists
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM payroll WHERE month = ? AND year = ?");
        $check_stmt->execute([$month, $year]);
        if ((int)$check_stmt->fetchColumn() > 0) {
            throw new Exception("Payroll for period {$period} has already been processed.");
        }

        // Get employees to process
        $employee_query = "SELECT e.*, p.min_salary, p.max_salary FROM employees e 
                          LEFT JOIN positions p ON e.position_id = p.id 
                          WHERE e.status = 'active'";
        $employee_params = [];

        if (!empty($department_id)) {
            $employee_query .= " AND e.department_id = ?";
            $employee_params[] = $department_id;
        }

        $employee_query .= " ORDER BY e.department_id, e.last_name";
        $stmt = $pdo->prepare($employee_query);
        $stmt->execute($employee_params);
        $employees = $stmt->fetchAll();

        if (empty($employees)) {
            throw new Exception("No active employees found for payroll processing.");
        }

        // Begin transaction
        $pdo->beginTransaction();

        foreach ($employees as $employee) {
            $basic_salary = $employee['salary'] ?? $employee['min_salary'] ?? 0;
            $allowances = $basic_salary * 0.10; // 10%
            $tax_deduction = $basic_salary * 0.05; // 5%
            $benefit_deduction = $basic_salary * 0.02; // 2%
            $total_deductions = $tax_deduction + $benefit_deduction;
            $net_salary = $basic_salary + $allowances - $total_deductions;

            $stmt = $pdo->prepare("
                INSERT INTO payroll (
                    employee_id, month, year, basic_salary, allowances, deductions, 
                    net_salary, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP)
            ");
            
            $stmt->execute([
                $employee['id'],
                $month,
                $year,
                $basic_salary,
                $allowances,
                $total_deductions,
                $net_salary
            ]);
            
            $processed_count++;
        }

        // Commit transaction
        $pdo->commit();
        
        $message = "Payroll processed successfully for {$processed_count} employees for period {$period}!";
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Get recent payroll periods
$stmt = $pdo->query("SELECT month, year, COUNT(*) as total FROM payroll GROUP BY year, month ORDER BY year DESC, month DESC LIMIT 12");
$recent_periods = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Payroll - HOSPITAL</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../../includes/header.php'; ?>
<div class="container">
  <div class="page-header">
    <h1><i class="fas fa-calculator"></i> Process Payroll</h1>
    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Payroll</a>
  </div>

  <?php if ($message): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

  <div class="card">
    <div class="card-header"><h2>Payroll Processing</h2></div>
    <div class="card-body">
      <form method="POST" class="form-container" id="payrollForm">
        <div class="form-row">
          <div class="form-group">
            <label for="period">Payroll Period *</label>
            <input type="month" id="period" name="period" required value="<?php echo date('Y-m'); ?>">
            <small class="form-help">Select the month for payroll processing</small>
          </div>
          <div class="form-group">
            <label for="department_id">Department (Optional)</label>
            <select id="department_id" name="department_id">
              <option value="">All Departments</option>
              <?php foreach ($departments as $dept): ?>
                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
              <?php endforeach; ?>
            </select>
            <small class="form-help">Leave empty to process all departments</small>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to process payroll for the selected period? This action cannot be undone.')"><i class="fas fa-calculator"></i> Process Payroll</button>
          <button type="reset" class="btn btn-secondary"><i class="fas fa-undo"></i> Reset Form</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h2>Recent Payroll Periods</h2></div>
    <div class="card-body">
      <?php if ($recent_periods): ?>
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>Period</th><th>Total Records</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach ($recent_periods as $rp): 
            $p = sprintf('%04d-%02d', $rp['year'], $rp['month']);
          ?>
            <tr>
              <td><strong><?php echo date('F Y', mktime(0,0,0,(int)$rp['month'],1,(int)$rp['year'])); ?></strong></td>
              <td><?php echo (int)$rp['total']; ?></td>
              <td>
                <a href="payroll_details.php?period=<?php echo $p; ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> View Details</a>
                <a href="approve_payroll.php?period=<?php echo $p; ?>" class="btn btn-sm btn-success"><i class="fas fa-check"></i> Approve</a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <p class="text-muted">No payroll periods found.</p>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h2>Processing Information</h2></div>
    <div class="card-body">
      <div class="info-grid">
        <div class="info-item"><label>Basic Salary:</label><span>Employee's current salary or position minimum</span></div>
        <div class="info-item"><label>Allowances:</label><span>10% of basic salary (transport, meal, etc.)</span></div>
        <div class="info-item"><label>Deductions:</label><span>5% tax + 2% benefits = 7% total</span></div>
        <div class="info-item"><label>Net Salary:</label><span>Basic + Allowances - Deductions</span></div>
      </div>
      <div class="alert alert-info"><i class="fas fa-info-circle"></i> Records are created as 'pending' and should be approved before payment.</div>
    </div>
  </div>
</div>
</div>
<script src="../../assets/js/main.js"></script>
</body>
</html>
