<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Get employees with department and position information
$stmt = $pdo->query("
    SELECT e.*, d.name as department_name, p.title as position_title 
    FROM employees e 
    LEFT JOIN departments d ON e.department_id = d.id 
    LEFT JOIN positions p ON e.position_id = p.id 
    ORDER BY e.created_at DESC
");
$employees = $stmt->fetchAll();

// Get departments for filter
$stmt = $pdo->query("SELECT * FROM departments ORDER BY name");
$departments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Core HCM - HOSPITAL</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            <li><a href="../hcm/" class="active"><i class="fas fa-users"></i> Core HCM</a></li>
            <li><a href="../payroll/"><i class="fas fa-money-bill-wave"></i> Payroll Management</a></li>
            <li><a href="../compensation/"><i class="fas fa-chart-line"></i> Compensation Planning</a></li>
            <li><a href="../analytics/"><i class="fas fa-chart-bar"></i> HR Analytics</a></li>
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
            <div class="form-container">
                <div class="form-title">
                    <i class="fas fa-users"></i> Core Human Capital Management
                </div>
                
                <!-- Action Buttons -->
                <div class="mb-3">
                    <a href="add_employee.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add New Employee
                    </a>
                    <a href="add_department.php" class="btn btn-secondary">
                        <i class="fas fa-building"></i> Add Department
                    </a>
                    <a href="add_position.php" class="btn btn-secondary">
                        <i class="fas fa-briefcase"></i> Add Position
                    </a>
                    <button onclick="exportToCSV('employeesTable', 'employees.csv')" class="btn btn-success">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                </div>

                <!-- Search and Filter -->
                <div class="mb-3">
                    <div class="dashboard-grid">
                        <div>
                            <input type="text" id="searchInput" class="form-input" placeholder="Search employees...">
                        </div>
                        <div>
                            <select id="departmentFilter" class="form-select">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept['name']); ?>">
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <select id="statusFilter" class="form-select">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="terminated">Terminated</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Employees Table -->
                <div class="table-container">
                    <table class="table" id="employeesTable">
                        <thead>
                            <tr>
                                <th data-sortable="true">Employee ID</th>
                                <th data-sortable="true">Name</th>
                                <th data-sortable="true">Department</th>
                                <th data-sortable="true">Position</th>
                                <th data-sortable="true">Email</th>
                                <th data-sortable="true">Phone</th>
                                <th data-sortable="true">Hire Date</th>
                                <th data-sortable="true">Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $employee): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($employee['employee_id']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($employee['department_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($employee['position_title'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                <td><?php echo htmlspecialchars($employee['phone'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($employee['hire_date'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $employee['status'] === 'active' ? 'success' : ($employee['status'] === 'inactive' ? 'warning' : 'danger'); ?>">
                                        <?php echo ucfirst($employee['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view_employee.php?id=<?php echo $employee['id']; ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_employee.php?id=<?php echo $employee['id']; ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="deleteEmployee(<?php echo $employee['id']; ?>)" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#employeesTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Department filter
        document.getElementById('departmentFilter').addEventListener('change', function() {
            const filterValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#employeesTable tbody tr');
            
            rows.forEach(row => {
                const department = row.cells[2].textContent.toLowerCase();
                row.style.display = !filterValue || department === filterValue ? '' : 'none';
            });
        });

        // Status filter
        document.getElementById('statusFilter').addEventListener('change', function() {
            const filterValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#employeesTable tbody tr');
            
            rows.forEach(row => {
                const status = row.cells[7].textContent.toLowerCase();
                row.style.display = !filterValue || status === filterValue ? '' : 'none';
            });
        });

        function deleteEmployee(employeeId) {
            if (confirm('Are you sure you want to delete this employee? This action cannot be undone.')) {
                // Send delete request
                fetch(`delete_employee.php?id=${employeeId}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting employee: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting employee');
                });
            }
        }
    </script>
</body>
</html>
