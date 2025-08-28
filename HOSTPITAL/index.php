<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get dashboard statistics
$stats = [];

// Total employees
$stmt = $pdo->query("SELECT COUNT(*) as total FROM employees WHERE status = 'active'");
$stats['total_employees'] = $stmt->fetch()['total'];

// Total departments
$stmt = $pdo->query("SELECT COUNT(*) as total FROM departments");
$stats['total_departments'] = $stmt->fetch()['total'];

// Total payroll this month
$currentMonth = date('n');
$currentYear = date('Y');
$stmt = $pdo->prepare("SELECT SUM(net_salary) as total FROM payroll WHERE month = ? AND year = ?");
$stmt->execute([$currentMonth, $currentYear]);
$stats['monthly_payroll'] = $stmt->fetch()['total'] ?? 0;

// Active HMO plans
$stmt = $pdo->query("SELECT COUNT(*) as total FROM employee_benefits WHERE status = 'active'");
$stats['active_hmo'] = $stmt->fetch()['total'];

// Recent activities
$stmt = $pdo->query("SELECT e.first_name, e.last_name, e.hire_date FROM employees e ORDER BY e.created_at DESC LIMIT 5");
$recent_employees = $stmt->fetchAll();

// Department distribution
$stmt = $pdo->query("SELECT d.name, COUNT(e.id) as count FROM departments d LEFT JOIN employees e ON d.id = e.department_id WHERE e.status = 'active' GROUP BY d.id");
$department_distribution = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOSPITAL - Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>HOSPITAL</h3>
            <p>Compensation & HR Intelligence</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="modules/hcm/"><i class="fas fa-users"></i> Core HCM</a></li>
            <li><a href="modules/payroll/"><i class="fas fa-money-bill-wave"></i> Payroll Management</a></li>
            <li><a href="modules/compensation/"><i class="fas fa-chart-line"></i> Compensation Planning</a></li>
            <li><a href="modules/analytics/"><i class="fas fa-chart-bar"></i> HR Analytics</a></li>
            <li><a href="modules/hmo/"><i class="fas fa-heartbeat"></i> HMO & Benefits</a></li>
            <li><a href="modules/reports/"><i class="fas fa-file-alt"></i> Reports</a></li>
            <li><a href="modules/settings/"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="logo">HOSPITAL</div>
                <nav>
                    <ul class="nav-menu">
                        <li><a href="index.php">Dashboard</a></li>
                        <li><a href="modules/hcm/">HCM</a></li>
                        <li><a href="modules/payroll/">Payroll</a></li>
                        <li><a href="modules/analytics/">Analytics</a></li>
                        <li><a href="modules/hmo/">HMO</a></li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="container">
            <h1 class="form-title">Dashboard Overview</h1>
            
            <!-- Statistics Cards -->
            <div class="dashboard-grid">
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <div class="card-title">Total Employees</div>
                        </div>
                    </div>
                    <div class="card-value"><?php echo number_format($stats['total_employees']); ?></div>
                    <div class="card-description">Active employees in the system</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div>
                            <div class="card-title">Departments</div>
                        </div>
                    </div>
                    <div class="card-value"><?php echo number_format($stats['total_departments']); ?></div>
                    <div class="card-description">Total organizational departments</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div>
                            <div class="card-title">Monthly Payroll</div>
                        </div>
                    </div>
                    <div class="card-value">₱<?php echo number_format($stats['monthly_payroll'], 2); ?></div>
                    <div class="card-description">Total payroll for <?php echo date('F Y'); ?></div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <div>
                            <div class="card-title">Active HMO</div>
                        </div>
                    </div>
                    <div class="card-value"><?php echo number_format($stats['active_hmo']); ?></div>
                    <div class="card-description">Employees with active HMO plans</div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="dashboard-grid">
                <div class="chart-container">
                    <h3 class="chart-title">Employee Distribution by Department</h3>
                    <canvas id="employeeChart" height="300"></canvas>
                </div>

                <div class="chart-container">
                    <h3 class="chart-title">Payroll Trends (6 Months)</h3>
                    <canvas id="payrollChart" height="300"></canvas>
                </div>
            </div>

            <!-- Recent Activities and Quick Actions -->
            <div class="dashboard-grid">
                <div class="card">
                    <h3 class="card-title">Recent Employee Additions</h3>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Employee Name</th>
                                    <th>Hire Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_employees as $employee): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($employee['hire_date'])); ?></td>
                                    <td><span class="badge badge-success">Active</span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <h3 class="card-title">Quick Actions</h3>
                    <div class="quick-actions">
                        <a href="modules/hcm/add_employee.php" class="btn btn-primary btn-block mb-2">
                            <i class="fas fa-user-plus"></i> Add New Employee
                        </a>
                        <a href="modules/payroll/process_payroll.php" class="btn btn-success btn-block mb-2">
                            <i class="fas fa-calculator"></i> Process Payroll
                        </a>
                        <a href="modules/hmo/manage_plans.php" class="btn btn-info btn-block mb-2">
                            <i class="fas fa-heartbeat"></i> Manage HMO Plans
                        </a>
                        <a href="modules/reports/generate_report.php" class="btn btn-warning btn-block mb-2">
                            <i class="fas fa-file-alt"></i> Generate Report
                        </a>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div class="card">
                <h3 class="card-title">System Status</h3>
                <div class="dashboard-grid">
                    <div class="status-item">
                        <i class="fas fa-database text-success"></i>
                        <span>Database: Connected</span>
                    </div>
                    <div class="status-item">
                        <i class="fas fa-server text-success"></i>
                        <span>Server: Running</span>
                    </div>
                    <div class="status-item">
                        <i class="fas fa-clock text-success"></i>
                        <span>Last Backup: <?php echo date('M d, Y H:i'); ?></span>
                    </div>
                    <div class="status-item">
                        <i class="fas fa-users text-success"></i>
                        <span>Active Sessions: <?php echo rand(5, 15); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
