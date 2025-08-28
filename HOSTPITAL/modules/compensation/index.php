<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Get compensation plans
$stmt = $pdo->query("SELECT * FROM compensation_plans ORDER BY created_at DESC");
$compensation_plans = $stmt->fetchAll();

// Get compensation components with plan information
$stmt = $pdo->query("
    SELECT cc.*, cp.name as plan_name 
    FROM compensation_components cc 
    LEFT JOIN compensation_plans cp ON cc.plan_id = cp.id 
    ORDER BY cc.id DESC
");
$compensation_components = $stmt->fetchAll();

// Get salary structures
$stmt = $pdo->query("
    SELECT ss.*, p.title as position_title, d.name as department_name 
    FROM salary_structures ss 
    LEFT JOIN positions p ON ss.position_id = p.id 
    LEFT JOIN departments d ON p.department_id = d.id 
    ORDER BY ss.id DESC
");
$salary_structures = $stmt->fetchAll();

// Calculate statistics
$total_plans = count($compensation_plans);
$active_plans = count(array_filter($compensation_plans, function($plan) {
    return $plan['status'] === 'active';
}));
$total_components = count($compensation_components);
$total_salary_structures = count($salary_structures);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compensation Planning - HOSPITAL</title>
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
            <li><a href="../hcm/"><i class="fas fa-users"></i> Core HCM</a></li>
            <li><a href="../payroll/"><i class="fas fa-money-bill-wave"></i> Payroll Management</a></li>
            <li><a href="../compensation/" class="active"><i class="fas fa-chart-line"></i> Compensation Planning</a></li>
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
            <!-- Compensation Overview Cards -->
            <div class="dashboard-grid">
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div>
                            <div class="card-title">Total Plans</div>
                        </div>
                    </div>
                    <div class="card-value"><?php echo number_format($total_plans); ?></div>
                    <div class="card-description">Compensation plans created</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <div class="card-title">Active Plans</div>
                        </div>
                    </div>
                    <div class="card-value"><?php echo number_format($active_plans); ?></div>
                    <div class="card-description">Currently active plans</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-puzzle-piece"></i>
                        </div>
                        <div>
                            <div class="card-title">Components</div>
                        </div>
                    </div>
                    <div class="card-value"><?php echo number_format($total_components); ?></div>
                    <div class="card-description">Compensation components</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <div>
                            <div class="card-title">Salary Structures</div>
                        </div>
                    </div>
                    <div class="card-value"><?php echo number_format($total_salary_structures); ?></div>
                    <div class="card-description">Position salary structures</div>
                </div>
            </div>

            <div class="form-container">
                <div class="form-title">
                    <i class="fas fa-chart-line"></i> Compensation Planning
                </div>
                
                <!-- Action Buttons -->
                <div class="mb-3">
                    <a href="add_plan.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Compensation Plan
                    </a>
                    <a href="add_component.php" class="btn btn-secondary">
                        <i class="fas fa-plus"></i> Add Component
                    </a>
                    <a href="salary_structure.php" class="btn btn-success">
                        <i class="fas fa-cogs"></i> Manage Salary Structure
                    </a>
                    <button onclick="exportToCSV('compensationTable', 'compensation.csv')" class="btn btn-info">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                </div>

                <!-- Tabs -->
                <div class="tabs mb-3">
                    <button class="tab-btn active" onclick="showTab('plans')">Compensation Plans</button>
                    <button class="tab-btn" onclick="showTab('components')">Components</button>
                    <button class="tab-btn" onclick="showTab('structures')">Salary Structures</button>
                </div>

                <!-- Compensation Plans Tab -->
                <div id="plans" class="tab-content">
                    <div class="table-container">
                        <h4>Compensation Plans</h4>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Plan Name</th>
                                    <th>Description</th>
                                    <th>Effective Date</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($compensation_plans as $plan): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($plan['name']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($plan['description'], 0, 100)) . '...'; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($plan['effective_date'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $plan['status'] === 'active' ? 'success' : ($plan['status'] === 'draft' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($plan['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($plan['created_at'])); ?></td>
                                    <td>
                                        <a href="view_plan.php?id=<?php echo $plan['id']; ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_plan.php?id=<?php echo $plan['id']; ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="togglePlanStatus(<?php echo $plan['id']; ?>)" class="btn btn-<?php echo $plan['status'] === 'active' ? 'warning' : 'success'; ?> btn-sm">
                                            <i class="fas fa-<?php echo $plan['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                        </button>
                                        <button onclick="deletePlan(<?php echo $plan['id']; ?>)" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Components Tab -->
                <div id="components" class="tab-content" style="display: none;">
                    <div class="table-container">
                        <h4>Compensation Components</h4>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Component Name</th>
                                    <th>Plan</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Criteria</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($compensation_components as $component): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($component['name']); ?></td>
                                    <td><?php echo htmlspecialchars($component['plan_name']); ?></td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo ucfirst($component['type']); ?>
                                        </span>
                                    </td>
                                    <td>₱<?php echo number_format($component['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars(substr($component['criteria'], 0, 80)) . '...'; ?></td>
                                    <td>
                                        <a href="edit_component.php?id=<?php echo $component['id']; ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="deleteComponent(<?php echo $component['id']; ?>)" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Salary Structures Tab -->
                <div id="structures" class="tab-content" style="display: none;">
                    <div class="table-container">
                        <h4>Salary Structures</h4>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Basic Salary</th>
                                    <th>Allowances</th>
                                    <th>Total</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($salary_structures as $structure): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($structure['position_title']); ?></td>
                                    <td><?php echo htmlspecialchars($structure['department_name']); ?></td>
                                    <td>₱<?php echo number_format($structure['basic_salary'], 2); ?></td>
                                    <td>₱<?php echo number_format($structure['allowances'], 2); ?></td>
                                    <td>₱<?php echo number_format($structure['basic_salary'] + $structure['allowances'], 2); ?></td>
                                    <td>
                                        <a href="edit_structure.php?id=<?php echo $structure['id']; ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="deleteStructure(<?php echo $structure['id']; ?>)" class="btn btn-danger btn-sm">
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
    </div>

    <script src="../../assets/js/main.js"></script>
    <script>
        // Tab functionality
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.style.display = 'none';
            });
            
            // Remove active class from all tab buttons
            const tabBtns = document.querySelectorAll('.tab-btn');
            tabBtns.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).style.display = 'block';
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }

        // Toggle plan status
        function togglePlanStatus(planId) {
            if (confirm('Are you sure you want to change the status of this compensation plan?')) {
                fetch(`toggle_plan_status.php?id=${planId}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error updating plan: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating plan');
                });
            }
        }

        // Delete plan
        function deletePlan(planId) {
            if (confirm('Are you sure you want to delete this compensation plan? This action cannot be undone.')) {
                fetch(`delete_plan.php?id=${planId}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting plan: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting plan');
                });
            }
        }

        // Delete component
        function deleteComponent(componentId) {
            if (confirm('Are you sure you want to delete this compensation component? This action cannot be undone.')) {
                fetch(`delete_component.php?id=${componentId}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting component: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting component');
                });
            }
        }

        // Delete structure
        function deleteStructure(structureId) {
            if (confirm('Are you sure you want to delete this salary structure? This action cannot be undone.')) {
                fetch(`delete_structure.php?id=${structureId}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting structure: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting structure');
                });
            }
        }
    </script>

    <style>
        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .tab-btn {
            padding: 0.75rem 1.5rem;
            border: 2px solid var(--medium-gray);
            background: var(--white);
            color: var(--text-dark);
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .tab-btn:hover,
        .tab-btn.active {
            background: var(--primary-green);
            color: var(--white);
            border-color: var(--primary-green);
        }
        
        .tab-content {
            display: block;
        }
    </style>
</body>
</html>
