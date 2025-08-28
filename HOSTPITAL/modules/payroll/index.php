<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Get payroll records with employee information
$stmt = $pdo->query("
    SELECT p.*, e.first_name, e.last_name, e.employee_id, d.name AS department_name
    FROM payroll p 
    LEFT JOIN employees e ON p.employee_id = e.id 
    LEFT JOIN departments d ON e.department_id = d.id
    ORDER BY p.year DESC, p.month DESC, p.created_at DESC
");
$payroll_records = $stmt->fetchAll();

// Get current month and year
$currentMonth = (int)date('n');
$currentYear = (int)date('Y');

// Calculate total payroll for current month
$stmt = $pdo->prepare('SELECT SUM(net_salary) as total FROM payroll WHERE month = ? AND year = ?');
$stmt->execute([$currentMonth, $currentYear]);
$currentMonthTotal = $stmt->fetch()['total'] ?? 0;

// Get pending payroll count
$stmt = $pdo->query("SELECT COUNT(*) as count FROM payroll WHERE status = 'pending'");
$pendingCount = (int)$stmt->fetch()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Management - HOSPITAL</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../../includes/header.php'; ?>
        <div class="container">
            <div class="dashboard-grid">
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon"><i class="fas fa-calendar"></i></div>
                        <div><div class="card-title">Current Month</div></div>
                    </div>
                    <div class="card-value"><?php echo date('F Y'); ?></div>
                    <div class="card-description"><?php echo date('n/Y'); ?></div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon"><i class="fas fa-money-bill-wave"></i></div>
                        <div><div class="card-title">Monthly Total</div></div>
                    </div>
                    <div class="card-value">₱<?php echo number_format($currentMonthTotal, 2); ?></div>
                    <div class="card-description">Total payroll for current month</div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon"><i class="fas fa-clock"></i></div>
                        <div><div class="card-title">Pending</div></div>
                    </div>
                    <div class="card-value"><?php echo number_format($pendingCount); ?></div>
                    <div class="card-description">Pending payroll records</div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon"><i class="fas fa-check-circle"></i></div>
                        <div><div class="card-title">Processed</div></div>
                    </div>
                    <div class="card-value"><?php echo number_format(count($payroll_records) - $pendingCount); ?></div>
                    <div class="card-description">Processed payroll records</div>
                </div>
            </div>

            <div class="form-container">
                <div class="form-title"><i class="fas fa-money-bill-wave"></i> Payroll Management</div>
                <div class="mb-3">
                    <a href="process_payroll.php" class="btn btn-primary"><i class="fas fa-calculator"></i> Process Payroll</a>
                    <a href="salary_structure.php" class="btn btn-secondary"><i class="fas fa-cogs"></i> Salary Structure</a>
                    <a href="manage_deductions.php" class="btn btn-secondary"><i class="fas fa-minus-circle"></i> Manage Deductions</a>
                    <button onclick="exportToCSV('payrollTable', 'payroll.csv')" class="btn btn-success"><i class="fas fa-download"></i> Export CSV</button>
                </div>

                <div class="mb-3">
                    <div class="dashboard-grid">
                        <div><input type="text" id="searchInput" class="form-input" placeholder="Search payroll records..."></div>
                        <div>
                            <select id="monthFilter" class="form-select">
                                <option value="">All Months</option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $i == $currentMonth ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <select id="yearFilter" class="form-select">
                                <option value="">All Years</option>
                                <?php for ($year = date('Y'); $year >= date('Y') - 5; $year--): ?>
                                    <option value="<?php echo $year; ?>" <?php echo $year == $currentYear ? 'selected' : ''; ?>>
                                        <?php echo $year; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <select id="statusFilter" class="form-select">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="processed">Processed</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-container">
                    <table class="table" id="payrollTable">
                        <thead>
                            <tr>
                                <th data-sortable="true">Employee</th>
                                <th data-sortable="true">Department</th>
                                <th data-sortable="true">Period</th>
                                <th data-sortable="true">Basic Salary</th>
                                <th data-sortable="true">Allowances</th>
                                <th data-sortable="true">Deductions</th>
                                <th data-sortable="true">Net Salary</th>
                                <th data-sortable="true">Status</th>
                                <th data-sortable="true">Payment Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payroll_records as $payroll): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payroll['first_name'] . ' ' . $payroll['last_name']); ?><br><small><?php echo htmlspecialchars($payroll['employee_id']); ?></small></td>
                                <td><?php echo htmlspecialchars($payroll['department_name'] ?? ''); ?></td>
                                <td>
                                    <?php
                                    if (!empty($payroll['month']) && !empty($payroll['year'])) {
                                        echo date('F Y', mktime(0, 0, 0, (int)$payroll['month'], 1, (int)$payroll['year']));
                                    } else {
                                        echo htmlspecialchars($payroll['period'] ?? '');
                                    }
                                    ?>
                                </td>
                                <td>₱<?php echo number_format($payroll['basic_salary'], 2); ?></td>
                                <td>₱<?php echo number_format($payroll['allowances'], 2); ?></td>
                                <td>₱<?php echo number_format($payroll['deductions'], 2); ?></td>
                                <td>₱<?php echo number_format($payroll['net_salary'], 2); ?></td>
                                <td><span class="badge badge-<?php echo $payroll['status'] === 'paid' ? 'success' : ($payroll['status'] === 'processed' ? 'warning' : 'info'); ?>"><?php echo ucfirst($payroll['status']); ?></span></td>
                                <td><?php echo $payroll['payment_date'] ? date('M d, Y', strtotime($payroll['payment_date'])) : 'N/A'; ?></td>
                                <td>
                                    <a href="view_payroll.php?id=<?php echo $payroll['id']; ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                                    <?php if ($payroll['status'] === 'pending'): ?>
                                        <a href="process_payroll_record.php?id=<?php echo $payroll['id']; ?>" class="btn btn-success btn-sm"><i class="fas fa-check"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<script src="../../assets/js/main.js"></script>
<script>
// Search
const searchInput = document.getElementById('searchInput');
if (searchInput) {
  searchInput.addEventListener('input', function(){
    const term = this.value.toLowerCase();
    const rows = document.querySelectorAll('#payrollTable tbody tr');
    rows.forEach(r => {
      r.style.display = r.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
  });
}

function applyFilters(){
  const m = document.getElementById('monthFilter').value;
  const y = document.getElementById('yearFilter').value;
  const s = document.getElementById('statusFilter').value.toLowerCase();
  const rows = document.querySelectorAll('#payrollTable tbody tr');
  rows.forEach(row => {
    const period = row.cells[2].textContent.trim();
    const status = row.cells[7].textContent.trim().toLowerCase();
    let monthMatch = true, yearMatch = true, statusMatch = true;
    if (m) monthMatch = period.toLowerCase().includes(new Date(2000, m-1, 1).toLocaleString('en', {month:'long'}).toLowerCase());
    if (y) yearMatch = period.includes(y);
    if (s) statusMatch = status === s;
    row.style.display = (monthMatch && yearMatch && statusMatch) ? '' : 'none';
  });
}
['monthFilter','yearFilter','statusFilter'].forEach(id=>{
  const el = document.getElementById(id);
  if (el) el.addEventListener('change', applyFilters);
});
</script>
</body>
</html>
