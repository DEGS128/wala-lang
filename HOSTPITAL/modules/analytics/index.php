<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Get analytics data
$stats = [];

// Employee count by department
$stmt = $pdo->query("
    SELECT d.name, COUNT(e.id) as count 
    FROM departments d 
    LEFT JOIN employees e ON d.id = e.department_id AND e.status = 'active'
    GROUP BY d.id, d.name 
    ORDER BY count DESC
");
$dept_stats = $stmt->fetchAll();

// Payroll trends (last 6 months)
$stmt = $pdo->query("
    SELECT month, year, SUM(net_salary) as total 
    FROM payroll 
    WHERE year >= YEAR(CURDATE()) - 1 
    GROUP BY month, year 
    ORDER BY year DESC, month DESC 
    LIMIT 6
");
$payroll_trends = $stmt->fetchAll();

// Performance metrics
$stmt = $pdo->query("
    SELECT metric_name, AVG(value) as avg_value, AVG(target) as avg_target 
    FROM performance_metrics 
    GROUP BY metric_name
");
$performance_metrics = $stmt->fetchAll();

// Training statistics
$stmt = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM training_records 
    GROUP BY status
");
$training_stats = $stmt->fetchAll();

// HMO coverage
$stmt = $pdo->query("
    SELECT h.plan_name, COUNT(eb.id) as employee_count 
    FROM hmo_plans h 
    LEFT JOIN employee_benefits eb ON h.id = eb.hmo_plan_id AND eb.status = 'active'
    GROUP BY h.id, h.plan_name
");
$hmo_coverage = $stmt->fetchAll();

// Calculate totals
$total_employees = array_sum(array_column($dept_stats, 'count'));
$total_payroll = array_sum(array_column($payroll_trends, 'total'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Analytics - HOSPITAL</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
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
            <li><a href="../../index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="../hcm/"><i class="fas fa-users"></i> Core HCM</a></li>
            <li><a href="../payroll/"><i class="fas fa-money-bill-wave"></i> Payroll Management</a></li>
            <li><a href="../compensation/"><i class="fas fa-chart-line"></i> Compensation Planning</a></li>
            <li><a href="../analytics/" class="active"><i class="fas fa-chart-bar"></i> HR Analytics</a></li>
            <li><a href="../hmo/"><i class="fas fa-heartbeat"></i> HMO & Benefits</a></li>
            <li><a href="../reports/"><i class="fas fa-file-alt"></i> Reports</a></li>
            <li><a href="../settings/"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="logo">
                    HUMAN RESOURCE <span>4</span>
                </div>
                <nav>
                    <ul class="nav-menu">
                        <li><a href="../../index.php">Dashboard</a></li>
                        <li><a href="../hcm/">HCM</a></li>
                        <li><a href="../payroll/">Payroll</a></li>
                        <li><a href="../analytics/">Analytics</a></li>
                        <li><a href="../hmo/">HMO</a></li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Content -->
        <div class="container">
            <h1 class="form-title">HR Analytics Dashboard</h1>
            
            <!-- Key Metrics Cards -->
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
                    <div class="card-value"><?php echo number_format($total_employees); ?></div>
                    <div class="card-description">Active workforce</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div>
                            <div class="card-title">Total Payroll</div>
                        </div>
                    </div>
                    <div class="card-value">₱<?php echo number_format($total_payroll, 2); ?></div>
                    <div class="card-description">Last 6 months</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div>
                            <div class="card-title">Training Programs</div>
                        </div>
                    </div>
                    <div class="card-value"><?php echo array_sum(array_column($training_stats, 'count')); ?></div>
                    <div class="card-description">Total training records</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <div>
                            <div class="card-title">HMO Plans</div>
                        </div>
                    </div>
                    <div class="card-value"><?php echo count($hmo_coverage); ?></div>
                    <div class="card-description">Active HMO plans</div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="dashboard-grid">
                <div class="chart-container">
                    <h3 class="chart-title">Employee Distribution by Department</h3>
                    <canvas id="deptChart" height="300"></canvas>
                </div>

                <div class="chart-container">
                    <h3 class="chart-title">Payroll Trends (6 Months)</h3>
                    <canvas id="payrollTrendChart" height="300"></canvas>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="chart-container">
                    <h3 class="chart-title">Performance Metrics Overview</h3>
                    <canvas id="performanceChart" height="300"></canvas>
                </div>

                <div class="chart-container">
                    <h3 class="chart-title">Training Status Distribution</h3>
                    <canvas id="trainingChart" height="300"></canvas>
                </div>
            </div>

            <!-- Detailed Analytics Tables -->
            <div class="form-container">
                <div class="form-title">
                    <i class="fas fa-chart-bar"></i> Detailed Analytics
                </div>
                
                <!-- Department Statistics -->
                <div class="table-container mb-4">
                    <h4>Department Statistics</h4>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Employee Count</th>
                                <th>Percentage</th>
                                <th>Trend</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dept_stats as $dept): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($dept['name']); ?></td>
                                <td><?php echo number_format($dept['count']); ?></td>
                                <td><?php echo $total_employees > 0 ? round(($dept['count'] / $total_employees) * 100, 1) : 0; ?>%</td>
                                <td>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $total_employees > 0 ? ($dept['count'] / $total_employees) * 100 : 0; ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Performance Metrics -->
                <div class="table-container mb-4">
                    <h4>Performance Metrics</h4>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Metric</th>
                                <th>Average Value</th>
                                <th>Target</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($performance_metrics as $metric): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($metric['metric_name']); ?></td>
                                <td><?php echo round($metric['avg_value'], 2); ?></td>
                                <td><?php echo round($metric['avg_target'], 2); ?></td>
                                <td>
                                    <?php 
                                    $performance = $metric['avg_target'] > 0 ? ($metric['avg_value'] / $metric['avg_target']) * 100 : 0;
                                    $badge_class = $performance >= 90 ? 'success' : ($performance >= 75 ? 'warning' : 'danger');
                                    ?>
                                    <span class="badge badge-<?php echo $badge_class; ?>">
                                        <?php echo round($performance, 1); ?>%
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- HMO Coverage -->
                <div class="table-container">
                    <h4>HMO Coverage Analysis</h4>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>HMO Plan</th>
                                <th>Employee Count</th>
                                <th>Coverage Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hmo_coverage as $hmo): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($hmo['plan_name']); ?></td>
                                <td><?php echo number_format($hmo['employee_count']); ?></td>
                                <td>
                                    <?php 
                                    $coverage_rate = $total_employees > 0 ? ($hmo['employee_count'] / $total_employees) * 100 : 0;
                                    echo round($coverage_rate, 1) . '%';
                                    ?>
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
        // Initialize charts when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initAnalyticsCharts();
        });

        function initAnalyticsCharts() {
            // Department Distribution Chart
            const deptCtx = document.getElementById('deptChart');
            if (deptCtx) {
                new Chart(deptCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo json_encode(array_column($dept_stats, 'name')); ?>,
                        datasets: [{
                            data: <?php echo json_encode(array_column($dept_stats, 'count')); ?>,
                            backgroundColor: [
                                '#2E7D32',
                                '#4CAF50',
                                '#81C784',
                                '#66BB6A',
                                '#A5D6A7',
                                '#C8E6C9'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }

            // Payroll Trends Chart
            const payrollCtx = document.getElementById('payrollTrendChart');
            if (payrollCtx) {
                const labels = <?php echo json_encode(array_map(function($item) {
                    return date('M Y', mktime(0, 0, 0, $item['month'], 1, $item['year']));
                }, $payroll_trends)); ?>;
                
                new Chart(payrollCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Total Payroll',
                            data: <?php echo json_encode(array_column($payroll_trends, 'total')); ?>,
                            borderColor: '#2E7D32',
                            backgroundColor: 'rgba(46, 125, 50, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '₱' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Performance Metrics Chart
            const performanceCtx = document.getElementById('performanceChart');
            if (performanceCtx) {
                new Chart(performanceCtx, {
                    type: 'radar',
                    data: {
                        labels: <?php echo json_encode(array_column($performance_metrics, 'metric_name')); ?>,
                        datasets: [{
                            label: 'Current Performance',
                            data: <?php echo json_encode(array_map(function($item) {
                                return round($item['avg_value'], 2);
                            }, $performance_metrics)); ?>,
                            borderColor: '#2E7D32',
                            backgroundColor: 'rgba(46, 125, 50, 0.2)',
                            pointBackgroundColor: '#2E7D32'
                        }, {
                            label: 'Target',
                            data: <?php echo json_encode(array_map(function($item) {
                                return round($item['avg_target'], 2);
                            }, $performance_metrics)); ?>,
                            borderColor: '#4CAF50',
                            backgroundColor: 'rgba(76, 175, 80, 0.2)',
                            pointBackgroundColor: '#4CAF50'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        },
                        scales: {
                            r: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            }

            // Training Status Chart
            const trainingCtx = document.getElementById('trainingChart');
            if (trainingCtx) {
                new Chart(trainingCtx, {
                    type: 'pie',
                    data: {
                        labels: <?php echo json_encode(array_column($training_stats, 'status')); ?>,
                        datasets: [{
                            data: <?php echo json_encode(array_column($training_stats, 'count')); ?>,
                            backgroundColor: [
                                '#4CAF50',
                                '#FF9800',
                                '#2196F3',
                                '#F44336'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        }
    </script>

    <style>
        .progress-bar {
            width: 100%;
            height: 20px;
            background-color: #E0E0E0;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #2E7D32, #4CAF50);
            transition: width 0.3s ease;
        }
        
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
    </style>
</body>
</html>
