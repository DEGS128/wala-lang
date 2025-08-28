<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$employee = null;
$error = '';

// Get employee ID from URL
$employee_id = $_GET['id'] ?? null;
if (!$employee_id) {
    header('Location: index.php');
    exit();
}

// Fetch employee data with related information
try {
    $stmt = $pdo->prepare("
        SELECT e.*, d.name as department_name, p.title as position_title
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN positions p ON e.position_id = p.id
        WHERE e.id = ?
    ");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch();

    if (!$employee) {
        throw new Exception("Employee not found.");
    }

    // Fetch attendance records for the last 30 days
    $stmt = $pdo->prepare("
        SELECT date, time_in, time_out, status
        FROM attendance 
        WHERE employee_id = ? 
        ORDER BY date DESC 
        LIMIT 30
    ");
    $stmt->execute([$employee_id]);
    $attendance_records = $stmt->fetchAll();

    // Fetch salary history
    $stmt = $pdo->prepare("
        SELECT month, year, basic_salary, allowances, deductions, net_salary, status, payment_date
        FROM payroll 
        WHERE employee_id = ? 
        ORDER BY year DESC, month DESC 
        LIMIT 12
    ");
    $stmt->execute([$employee_id]);
    $salary_history = $stmt->fetchAll();

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Employee - HOSPITAL</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../../includes/header.php'; ?>
        
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-user"></i> Employee Details</h1>
                <div class="header-actions">
                    <a href="edit_employee.php?id=<?php echo $employee_id; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Employee
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to HCM
                    </a>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($employee): ?>
                <!-- Employee Basic Information -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-info-circle"></i> Basic Information</h2>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Full Name:</label>
                                <span><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Employee ID:</label>
                                <span>#<?php echo str_pad($employee['id'], 4, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Email:</label>
                                <span><?php echo htmlspecialchars($employee['email']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Phone:</label>
                                <span><?php echo htmlspecialchars($employee['phone']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Department:</label>
                                <span class="badge badge-primary"><?php echo htmlspecialchars($employee['department_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Position:</label>
                                <span class="badge badge-secondary"><?php echo htmlspecialchars($employee['position_title']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Hire Date:</label>
                                <span><?php echo date('F j, Y', strtotime($employee['hire_date'])); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Status:</label>
                                <span class="badge badge-<?php echo $employee['status'] == 'active' ? 'success' : ($employee['status'] == 'inactive' ? 'warning' : 'danger'); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $employee['status'])); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <label>Salary:</label>
                                <span class="salary">₱<?php echo number_format($employee['salary'], 2); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Address:</label>
                                <span><?php echo htmlspecialchars($employee['address'] ?: 'Not provided'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Emergency Contact Information -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-phone-alt"></i> Emergency Contact</h2>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Emergency Contact:</label>
                                <span><?php echo htmlspecialchars($employee['emergency_contact'] ?: 'Not provided'); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Emergency Phone:</label>
                                <span><?php echo htmlspecialchars($employee['emergency_phone'] ?: 'Not provided'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Records -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-calendar-check"></i> Recent Attendance (Last 30 Days)</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($attendance_records): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Check In</th>
                                            <th>Check Out</th>
                                            <th>Total Hours</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($attendance_records as $record): ?>
                                            <tr>
                                                <td><?php echo date('M j, Y', strtotime($record['date'])); ?></td>
                                                <td><?php echo !empty($record['time_in']) ? date('g:i A', strtotime($record['time_in'])) : 'N/A'; ?></td>
                                                <td><?php echo !empty($record['time_out']) ? date('g:i A', strtotime($record['time_out'])) : 'N/A'; ?></td>
                                                <td>
                                                    <?php
                                                    if (!empty($record['time_in']) && !empty($record['time_out'])) {
                                                        $diff = strtotime($record['time_out']) - strtotime($record['time_in']);
                                                        echo number_format(max($diff,0)/3600, 2) . ' h';
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo $record['status'] == 'present' ? 'success' : ($record['status'] == 'absent' ? 'danger' : 'warning'); ?>">
                                                        <?php echo ucfirst($record['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No attendance records found for the last 30 days.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Salary History -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-money-bill-wave"></i> Salary History (Last 12 Months)</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($salary_history): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Period</th>
                                            <th>Basic Salary</th>
                                            <th>Allowances</th>
                                            <th>Deductions</th>
                                            <th>Net Salary</th>
                                            <th>Status</th>
                                            <th>Payment Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($salary_history as $record): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                      if (!empty($record['month']) && !empty($record['year'])) {
                                                          echo date('F Y', mktime(0,0,0,(int)$record['month'],1,(int)$record['year']));
                                                      } else { echo '—'; }
                                                    ?>
                                                </td>
                                                <td>₱<?php echo number_format($record['basic_salary'], 2); ?></td>
                                                <td>₱<?php echo number_format($record['allowances'], 2); ?></td>
                                                <td>₱<?php echo number_format($record['deductions'], 2); ?></td>
                                                <td class="salary">₱<?php echo number_format($record['net_salary'], 2); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $record['status'] == 'processed' ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($record['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $record['payment_date'] ? date('M j, Y', strtotime($record['payment_date'])) : 'N/A'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No salary history found.</p>
                        <?php endif; ?>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
</body>
</html>
