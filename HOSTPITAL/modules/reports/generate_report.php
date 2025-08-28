<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$message = '';
$error = '';
$report_data = null;
$report_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $report_type = $_POST['report_type'] ?? '';
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $department_id = $_POST['department_id'] ?? '';
        $format = $_POST['format'] ?? 'html';

        if (empty($report_type)) {
            throw new Exception("Report type is required.");
        }

        if (empty($start_date) || empty($end_date)) {
            throw new Exception("Date range is required.");
        }

        if (strtotime($start_date) > strtotime($end_date)) {
            throw new Exception("Start date cannot be after end date.");
        }

        // Generate report based on type
        switch ($report_type) {
            case 'employee_list':
                $report_data = generateEmployeeReport($pdo, $start_date, $end_date, $department_id);
                break;
            case 'payroll_summary':
                $report_data = generatePayrollReport($pdo, $start_date, $end_date, $department_id);
                break;
            case 'attendance_report':
                $report_data = generateAttendanceReport($pdo, $start_date, $end_date, $department_id);
                break;
            case 'hmo_coverage':
                $report_data = generateHMOCoverageReport($pdo, $start_date, $end_date);
                break;
            case 'compensation_analysis':
                $report_data = generateCompensationReport($pdo, $start_date, $end_date, $department_id);
                break;
            default:
                throw new Exception("Invalid report type.");
        }

        // Export if requested
        if ($format === 'csv' && $report_data) {
            exportToCSV($report_data, $report_type, $start_date, $end_date);
            exit();
        }

        $message = "Report generated successfully!";

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch departments for filtering
$stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
$departments = $stmt->fetchAll();

// Report generation functions
function generateEmployeeReport($pdo, $start_date, $end_date, $department_id) {
    $query = "
        SELECT e.*, d.name as department_name, p.title as position_title
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN positions p ON e.position_id = p.id
        WHERE e.created_at BETWEEN ? AND ?
    ";
    $params = [$start_date, $end_date . ' 23:59:59'];

    if ($department_id) {
        $query .= " AND e.department_id = ?";
        $params[] = $department_id;
    }

    $query .= " ORDER BY d.name, e.last_name, e.first_name";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function generatePayrollReport($pdo, $start_date, $end_date, $department_id) {
    $query = "
        SELECT p.*, e.first_name, e.last_name, d.name as department_name
        FROM payroll p
        LEFT JOIN employees e ON p.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        WHERE p.created_at BETWEEN ? AND ?
    ";
    $params = [$start_date, $end_date . ' 23:59:59'];

    if ($department_id) {
        $query .= " AND e.department_id = ?";
        $params[] = $department_id;
    }

    $query .= " ORDER BY p.period, d.name, e.last_name";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function generateAttendanceReport($pdo, $start_date, $end_date, $department_id) {
    $query = "
        SELECT a.date, a.time_in, a.time_out, a.status,
               e.first_name, e.last_name, d.name as department_name
        FROM attendance a
        LEFT JOIN employees e ON a.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        WHERE a.date BETWEEN ? AND ?
    ";
    $params = [$start_date, $end_date];

    if ($department_id) {
        $query .= " AND e.department_id = ?";
        $params[] = $department_id;
    }

    $query .= " ORDER BY a.date, d.name, e.last_name";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function generateHMOCoverageReport($pdo, $start_date, $end_date) {
    $query = "
        SELECT eb.start_date, eb.end_date, eb.status,
               e.first_name, e.last_name, d.name as department_name,
               hp.name as provider_name, hpl.plan_name, hpl.premium_amount
        FROM employee_benefits eb
        LEFT JOIN employees e ON eb.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN hmo_plans hpl ON eb.hmo_plan_id = hpl.id
        LEFT JOIN hmo_providers hp ON hpl.provider_id = hp.id
        WHERE eb.created_at BETWEEN ? AND ?
        ORDER BY d.name, e.last_name
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$start_date, $end_date . ' 23:59:59']);
    return $stmt->fetchAll();
}

function generateCompensationReport($pdo, $start_date, $end_date, $department_id) {
    $query = "
        SELECT e.first_name, e.last_name, d.name as department_name, p.title as position_title,
               e.salary, p.min_salary, p.max_salary,
               cp.name as compensation_plan, cp.type as plan_type
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN positions p ON e.position_id = p.id
        LEFT JOIN compensation_plans cp ON cp.effective_date <= ?
        WHERE e.created_at BETWEEN ? AND ?
    ";
    $params = [$end_date, $start_date, $end_date . ' 23:59:59'];

    if ($department_id) {
        $query .= " AND e.department_id = ?";
        $params[] = $department_id;
    }

    $query .= " ORDER BY d.name, e.last_name";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function exportToCSV($data, $report_type, $start_date, $end_date) {
    $filename = $report_type . '_' . $start_date . '_to_' . $end_date . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    if (!empty($data)) {
        // Write headers
        fputcsv($output, array_keys($data[0]));
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Reports - HOSPITAL</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../../includes/header.php'; ?>
        
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-chart-bar"></i> Generate Reports</h1>
                <a href="../index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2>Report Configuration</h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="form-container" id="reportForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="report_type">Report Type *</label>
                                <select id="report_type" name="report_type" required>
                                    <option value="">Select Report Type</option>
                                    <option value="employee_list" <?php echo $report_type == 'employee_list' ? 'selected' : ''; ?>>Employee List</option>
                                    <option value="payroll_summary" <?php echo $report_type == 'payroll_summary' ? 'selected' : ''; ?>>Payroll Summary</option>
                                    <option value="attendance_report" <?php echo $report_type == 'attendance_report' ? 'selected' : ''; ?>>Attendance Report</option>
                                    <option value="hmo_coverage" <?php echo $report_type == 'hmo_coverage' ? 'selected' : ''; ?>>HMO Coverage Report</option>
                                    <option value="compensation_analysis" <?php echo $report_type == 'compensation_analysis' ? 'selected' : ''; ?>>Compensation Analysis</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="start_date">Start Date *</label>
                                <input type="date" id="start_date" name="start_date" required 
                                       value="<?php echo htmlspecialchars($_POST['start_date'] ?? date('Y-m-01')); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="end_date">End Date *</label>
                                <input type="date" id="end_date" name="end_date" required 
                                       value="<?php echo htmlspecialchars($_POST['end_date'] ?? date('Y-m-d')); ?>">
                            </div>
                            <div class="form-group">
                                <label for="department_id">Department (Optional)</label>
                                <select id="department_id" name="department_id">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>" 
                                                <?php echo ($_POST['department_id'] ?? '') == $dept['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="format">Export Format</label>
                                <select id="format" name="format">
                                    <option value="html">View in Browser</option>
                                    <option value="csv">Download CSV</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-chart-bar"></i> Generate Report
                                    </button>
                                    <button type="reset" class="btn btn-secondary">
                                        <i class="fas fa-undo"></i> Reset Form
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Report Results -->
            <?php if ($report_data): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>
                            <?php echo ucwords(str_replace('_', ' ', $report_type)); ?> Report
                            <small class="text-muted">
                                (<?php echo date('M j, Y', strtotime($_POST['start_date'])); ?> - 
                                <?php echo date('M j, Y', strtotime($_POST['end_date'])); ?>)
                            </small>
                        </h2>
                        <div class="header-actions">
                            <button class="btn btn-success" onclick="window.print()">
                                <i class="fas fa-print"></i> Print Report
                            </button>
                            <a href="?<?php echo http_build_query($_POST); ?>&format=csv" class="btn btn-info">
                                <i class="fas fa-download"></i> Download CSV
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($report_type === 'employee_list'): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Employee ID</th>
                                            <th>Name</th>
                                            <th>Department</th>
                                            <th>Position</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Hire Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data as $employee): ?>
                                            <tr>
                                                <td>#<?php echo str_pad($employee['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                                <td><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($employee['department_name']); ?></td>
                                                <td><?php echo htmlspecialchars($employee['position_title']); ?></td>
                                                <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                                <td><?php echo htmlspecialchars($employee['phone']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($employee['hire_date'])); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $employee['status'] == 'active' ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($employee['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php elseif ($report_type === 'payroll_summary'): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Department</th>
                                            <th>Period</th>
                                            <th>Basic Salary</th>
                                            <th>Allowances</th>
                                            <th>Deductions</th>
                                            <th>Net Salary</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data as $payroll): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($payroll['first_name'] . ' ' . $payroll['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($payroll['department_name']); ?></td>
                                                <td><?php echo htmlspecialchars($payroll['period']); ?></td>
                                                <td>₱<?php echo number_format($payroll['basic_salary'], 2); ?></td>
                                                <td>₱<?php echo number_format($payroll['allowances'], 2); ?></td>
                                                <td>₱<?php echo number_format($payroll['deductions'], 2); ?></td>
                                                <td class="salary">₱<?php echo number_format($payroll['net_salary'], 2); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $payroll['status'] == 'processed' ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($payroll['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php elseif ($report_type === 'attendance_report'): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Department</th>
                                            <th>Date</th>
                                            <th>Check In</th>
                                            <th>Check Out</th>
                                            <th>Total Hours</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data as $attendance): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($attendance['first_name'] . ' ' . $attendance['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($attendance['department_name']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($attendance['date'])); ?></td>
                                                <td><?php echo $attendance['check_in'] ? date('g:i A', strtotime($attendance['check_in'])) : 'N/A'; ?></td>
                                                <td><?php echo $attendance['check_out'] ? date('g:i A', strtotime($attendance['check_out'])) : 'N/A'; ?></td>
                                                <td><?php echo $attendance['total_hours'] ?: 'N/A'; ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $attendance['status'] == 'present' ? 'success' : ($attendance['status'] == 'absent' ? 'danger' : 'warning'); ?>">
                                                        <?php echo ucfirst($attendance['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php elseif ($report_type === 'hmo_coverage'): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Department</th>
                                            <th>HMO Provider</th>
                                            <th>Plan Name</th>
                                            <th>Coverage Amount</th>
                                            <th>Start Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data as $benefit): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($benefit['first_name'] . ' ' . $benefit['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($benefit['department_name']); ?></td>
                                                <td><?php echo htmlspecialchars($benefit['provider_name']); ?></td>
                                                <td><?php echo htmlspecialchars($benefit['plan_name']); ?></td>
                                                <td>₱<?php echo number_format($benefit['coverage_amount'], 2); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($benefit['start_date'])); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $benefit['status'] == 'active' ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($benefit['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php elseif ($report_type === 'compensation_analysis'): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Department</th>
                                            <th>Position</th>
                                            <th>Current Salary</th>
                                            <th>Position Range</th>
                                            <th>Compensation Plan</th>
                                            <th>Plan Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data as $comp): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($comp['first_name'] . ' ' . $comp['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($comp['department_name']); ?></td>
                                                <td><?php echo htmlspecialchars($comp['position_title']); ?></td>
                                                <td class="salary">₱<?php echo number_format($comp['salary'], 2); ?></td>
                                                <td>
                                                    ₱<?php echo number_format($comp['min_salary'], 2); ?> - 
                                                    ₱<?php echo number_format($comp['max_salary'], 2); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($comp['compensation_plan'] ?: 'N/A'); ?></td>
                                                <td>
                                                    <?php if ($comp['plan_type']): ?>
                                                        <span class="badge badge-info"><?php echo ucfirst(str_replace('_', ' ', $comp['plan_type'])); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <!-- Report Summary -->
                        <div class="report-summary">
                            <h3>Report Summary</h3>
                            <div class="summary-stats">
                                <div class="stat-item">
                                    <label>Total Records:</label>
                                    <span class="stat-value"><?php echo count($report_data); ?></span>
                                </div>
                                <div class="stat-item">
                                    <label>Report Period:</label>
                                    <span class="stat-value">
                                        <?php echo date('M j, Y', strtotime($_POST['start_date'])); ?> - 
                                        <?php echo date('M j, Y', strtotime($_POST['end_date'])); ?>
                                    </span>
                                </div>
                                <div class="stat-item">
                                    <label>Generated On:</label>
                                    <span class="stat-value"><?php echo date('M j, Y g:i A'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Report Types Information -->
            <div class="card">
                <div class="card-header">
                    <h2>Available Reports</h2>
                </div>
                <div class="card-body">
                    <div class="reports-grid">
                        <div class="report-type">
                            <h3><i class="fas fa-users"></i> Employee List</h3>
                            <p>Comprehensive list of employees with their basic information, department, and position details.</p>
                        </div>
                        <div class="report-type">
                            <h3><i class="fas fa-money-bill-wave"></i> Payroll Summary</h3>
                            <p>Detailed payroll information including salaries, allowances, deductions, and net pay for all employees.</p>
                        </div>
                        <div class="report-type">
                            <h3><i class="fas fa-calendar-check"></i> Attendance Report</h3>
                            <p>Employee attendance records showing check-in/out times, total hours worked, and attendance status.</p>
                        </div>
                        <div class="report-type">
                            <h3><i class="fas fa-heartbeat"></i> HMO Coverage Report</h3>
                            <p>Overview of employee HMO benefits, coverage amounts, and provider information.</p>
                        </div>
                        <div class="report-type">
                            <h3><i class="fas fa-chart-line"></i> Compensation Analysis</h3>
                            <p>Analysis of employee compensation including salary ranges, compensation plans, and market positioning.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
    <script>
        // Auto-save form data
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('reportForm');
            const formData = JSON.parse(localStorage.getItem('reportFormData') || '{}');
            
            // Restore form data
            Object.keys(formData).forEach(key => {
                const field = form.querySelector(`[name="${key}"]`);
                if (field && !field.value) {
                    field.value = formData[key];
                }
            });

            // Auto-save on input
            form.addEventListener('input', function(e) {
                if (e.target.name) {
                    formData[e.target.name] = e.target.value;
                    localStorage.setItem('reportFormData', JSON.stringify(formData));
                }
            });

            // Clear saved data on successful submission
            form.addEventListener('submit', function() {
                if (form.checkValidity()) {
                    localStorage.removeItem('reportFormData');
                }
            });
        });
    </script>
</body>
</html>
