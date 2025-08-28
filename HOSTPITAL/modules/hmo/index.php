<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Get HMO providers
$stmt = $pdo->query("SELECT * FROM hmo_providers ORDER BY name");
$hmo_providers = $stmt->fetchAll();

// Get HMO plans with provider information
$stmt = $pdo->query("
    SELECT h.*, hp.name as provider_name 
    FROM hmo_plans h 
    LEFT JOIN hmo_providers hp ON h.provider_id = hp.id 
    ORDER BY h.created_at DESC
");
$hmo_plans = $stmt->fetchAll();

// Get employee benefits with plan and employee information
$stmt = $pdo->query("
    SELECT eb.*, h.plan_name, hp.name as provider_name, e.first_name, e.last_name, e.employee_id 
    FROM employee_benefits eb 
    LEFT JOIN hmo_plans h ON eb.hmo_plan_id = h.id 
    LEFT JOIN hmo_providers hp ON h.provider_id = hp.id 
    LEFT JOIN employees e ON eb.employee_id = e.id 
    ORDER BY eb.created_at DESC
");
$employee_benefits = $stmt->fetchAll();

// Calculate statistics
$total_providers = count($hmo_providers);
$total_plans = count($hmo_plans);
$active_benefits = count(array_filter($employee_benefits, function($benefit) {
    return $benefit['status'] === 'active';
}));
$total_coverage = array_sum(array_column($hmo_plans, 'premium_amount'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HMO & Benefits - HOSPITAL</title>
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
            <li><a href="../compensation/"><i class="fas fa-chart-line"></i> Compensation Planning</a></li>
            <li><a href="../analytics/"><i class="fas fa-chart-bar"></i> HR Analytics</a></li>
            <li><a href="../hmo/" class="active"><i class="fas fa-heartbeat"></i> HMO & Benefits</a></li>
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
            <!-- HMO Overview Cards -->
            <div class="dashboard-grid">
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-hospital"></i>
                        </div>
                        <div>
                            <div class="card-title">HMO Providers</div>
                        </div>
                    </div>
                    <div class="card-value"><?php echo number_format($total_providers); ?></div>
                    <div class="card-description">Total HMO providers</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div>
                            <div class="card-title">HMO Plans</div>
                        </div>
                    </div>
                    <div class="card-value"><?php echo number_format($total_plans); ?></div>
                    <div class="card-description">Available HMO plans</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div>
                            <div class="card-title">Active Benefits</div>
                        </div>
                    </div>
                    <div class="card-value"><?php echo number_format($active_benefits); ?></div>
                    <div class="card-description">Employees with active benefits</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div>
                            <div class="card-title">Total Coverage</div>
                        </div>
                    </div>
                    <div class="card-value">₱<?php echo number_format($total_coverage, 2); ?></div>
                    <div class="card-description">Total premium coverage</div>
                </div>
            </div>

            <div class="form-container">
                <div class="form-title">
                    <i class="fas fa-heartbeat"></i> HMO & Benefits Administration
                </div>
                
                <!-- Action Buttons -->
                <div class="mb-3">
                    <a href="add_provider.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add HMO Provider
                    </a>
                    <a href="add_plan.php" class="btn btn-secondary">
                        <i class="fas fa-plus"></i> Add HMO Plan
                    </a>
                    <a href="assign_benefits.php" class="btn btn-success">
                        <i class="fas fa-user-plus"></i> Assign Benefits
                    </a>
                    <button onclick="exportToCSV('benefitsTable', 'hmo_benefits.csv')" class="btn btn-info">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                </div>

                <!-- Tabs -->
                <div class="tabs mb-3">
                    <button class="tab-btn active" onclick="showTab('providers')">HMO Providers</button>
                    <button class="tab-btn" onclick="showTab('plans')">HMO Plans</button>
                    <button class="tab-btn" onclick="showTab('benefits')">Employee Benefits</button>
                </div>

                <!-- HMO Providers Tab -->
                <div id="providers" class="tab-content">
                    <div class="table-container">
                        <h4>HMO Providers</h4>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Provider Name</th>
                                    <th>Contact Person</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($hmo_providers as $provider): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($provider['name']); ?></td>
                                    <td><?php echo htmlspecialchars($provider['contact_person']); ?></td>
                                    <td><?php echo htmlspecialchars($provider['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($provider['email']); ?></td>
                                    <td>
                                        <a href="edit_provider.php?id=<?php echo $provider['id']; ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="deleteProvider(<?php echo $provider['id']; ?>)" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- HMO Plans Tab -->
                <div id="plans" class="tab-content" style="display: none;">
                    <div class="table-container">
                        <h4>HMO Plans</h4>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Plan Name</th>
                                    <th>Provider</th>
                                    <th>Coverage Details</th>
                                    <th>Premium Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($hmo_plans as $plan): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($plan['plan_name']); ?></td>
                                    <td><?php echo htmlspecialchars($plan['provider_name']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($plan['coverage_details'], 0, 100)) . '...'; ?></td>
                                    <td>₱<?php echo number_format($plan['premium_amount'], 2); ?></td>
                                    <td>
                                        <a href="view_plan.php?id=<?php echo $plan['id']; ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_plan.php?id=<?php echo $plan['id']; ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
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

                <!-- Employee Benefits Tab -->
                <div id="benefits" class="tab-content" style="display: none;">
                    <div class="table-container">
                        <h4>Employee Benefits</h4>
                        <table class="table" id="benefitsTable">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>HMO Plan</th>
                                    <th>Provider</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employee_benefits as $benefit): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($benefit['first_name'] . ' ' . $benefit['last_name']); ?>
                                        <br><small><?php echo htmlspecialchars($benefit['employee_id']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($benefit['plan_name']); ?></td>
                                    <td><?php echo htmlspecialchars($benefit['provider_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($benefit['start_date'])); ?></td>
                                    <td><?php echo $benefit['end_date'] ? date('M d, Y', strtotime($benefit['end_date'])) : 'N/A'; ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $benefit['status'] === 'active' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($benefit['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_benefit.php?id=<?php echo $benefit['id']; ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_benefit.php?id=<?php echo $benefit['id']; ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="toggleBenefitStatus(<?php echo $benefit['id']; ?>)" class="btn btn-<?php echo $benefit['status'] === 'active' ? 'warning' : 'success'; ?> btn-sm">
                                            <i class="fas fa-<?php echo $benefit['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
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

        // Delete provider
        function deleteProvider(providerId) {
            if (confirm('Are you sure you want to delete this HMO provider? This action cannot be undone.')) {
                fetch(`delete_provider.php?id=${providerId}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting provider: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting provider');
                });
            }
        }

        // Delete plan
        function deletePlan(planId) {
            if (confirm('Are you sure you want to delete this HMO plan? This action cannot be undone.')) {
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

        // Toggle benefit status
        function toggleBenefitStatus(benefitId) {
            if (confirm('Are you sure you want to change the status of this benefit?')) {
                fetch(`toggle_benefit_status.php?id=${benefitId}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error updating benefit: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating benefit');
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
