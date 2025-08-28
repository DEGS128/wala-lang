<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (empty($_POST['name'])) {
            throw new Exception("Plan name is required.");
        }

        if (empty($_POST['type'])) {
            throw new Exception("Plan type is required.");
        }

        if (empty($_POST['effective_date'])) {
            throw new Exception("Effective date is required.");
        }

        // Check if plan name already exists
        $stmt = $pdo->prepare("SELECT id FROM compensation_plans WHERE name = ?");
        $stmt->execute([trim($_POST['name'])]);
        if ($stmt->fetch()) {
            throw new Exception("Plan name already exists.");
        }

        // Insert new compensation plan
        $stmt = $pdo->prepare("
            INSERT INTO compensation_plans (
                name, type, description, effective_date, status, 
                base_salary_increase, performance_bonus_percentage, 
                benefits_package, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        $stmt->execute([
            trim($_POST['name']),
            $_POST['type'],
            $_POST['description'] ?? '',
            $_POST['effective_date'],
            'active',
            $_POST['base_salary_increase'] ?: 0,
            $_POST['performance_bonus_percentage'] ?: 0,
            $_POST['benefits_package'] ?? ''
        ]);

        $plan_id = $pdo->lastInsertId();

        // Add compensation components if specified
        if (!empty($_POST['components'])) {
            foreach ($_POST['components'] as $component) {
                if (!empty($component['name']) && !empty($component['type'])) {
                    $stmt = $pdo->prepare("
                        INSERT INTO compensation_components (
                            plan_id, name, type, amount, percentage, description
                        ) VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $plan_id,
                        $component['name'],
                        $component['type'],
                        $component['amount'] ?: null,
                        $component['percentage'] ?: null,
                        $component['description'] ?? ''
                    ]);
                }
            }
        }

        $message = "Compensation plan created successfully!";
        
        // Clear form data
        $_POST = array();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch existing plans for reference
$stmt = $pdo->query("
    SELECT cp.*, COUNT(cc.id) as component_count
    FROM compensation_plans cp
    LEFT JOIN compensation_components cc ON cp.id = cc.plan_id
    GROUP BY cp.id
    ORDER BY cp.created_at DESC
");
$existing_plans = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Compensation Plan - HOSPITAL</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../../includes/header.php'; ?>
        
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-plus-circle"></i> Create Compensation Plan</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Compensation
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
                    <h2>Plan Information</h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="form-container" id="compensationPlanForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Plan Name *</label>
                                <input type="text" id="name" name="name" required 
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                       placeholder="e.g., Executive Compensation 2024">
                            </div>
                            <div class="form-group">
                                <label for="type">Plan Type *</label>
                                <select id="type" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="annual" <?php echo ($_POST['type'] ?? '') == 'annual' ? 'selected' : ''; ?>>Annual Review</option>
                                    <option value="promotion" <?php echo ($_POST['type'] ?? '') == 'promotion' ? 'selected' : ''; ?>>Promotion</option>
                                    <option value="market_adjustment" <?php echo ($_POST['type'] ?? '') == 'market_adjustment' ? 'selected' : ''; ?>>Market Adjustment</option>
                                    <option value="performance" <?php echo ($_POST['type'] ?? '') == 'performance' ? 'selected' : ''; ?>>Performance Based</option>
                                    <option value="retention" <?php echo ($_POST['type'] ?? '') == 'retention' ? 'selected' : ''; ?>>Retention</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3" 
                                      placeholder="Enter plan description and objectives"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="effective_date">Effective Date *</label>
                                <input type="date" id="effective_date" name="effective_date" required 
                                       value="<?php echo htmlspecialchars($_POST['effective_date'] ?? date('Y-m-d')); ?>">
                            </div>
                            <div class="form-group">
                                <label for="base_salary_increase">Base Salary Increase (%)</label>
                                <input type="number" id="base_salary_increase" name="base_salary_increase" 
                                       step="0.1" min="0" max="100" 
                                       value="<?php echo htmlspecialchars($_POST['base_salary_increase'] ?? ''); ?>"
                                       placeholder="0.0">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="performance_bonus_percentage">Performance Bonus (%)</label>
                                <input type="number" id="performance_bonus_percentage" name="performance_bonus_percentage" 
                                       step="0.1" min="0" max="100" 
                                       value="<?php echo htmlspecialchars($_POST['performance_bonus_percentage'] ?? ''); ?>"
                                       placeholder="0.0">
                            </div>
                            <div class="form-group">
                                <label for="benefits_package">Benefits Package</label>
                                <select id="benefits_package" name="benefits_package">
                                    <option value="">Select Package</option>
                                    <option value="basic" <?php echo ($_POST['benefits_package'] ?? '') == 'basic' ? 'selected' : ''; ?>>Basic</option>
                                    <option value="standard" <?php echo ($_POST['benefits_package'] ?? '') == 'standard' ? 'selected' : ''; ?>>Standard</option>
                                    <option value="premium" <?php echo ($_POST['benefits_package'] ?? '') == 'premium' ? 'selected' : ''; ?>>Premium</option>
                                    <option value="executive" <?php echo ($_POST['benefits_package'] ?? '') == 'executive' ? 'selected' : ''; ?>>Executive</option>
                                </select>
                            </div>
                        </div>

                        <!-- Compensation Components -->
                        <div class="form-group">
                            <label>Compensation Components</label>
                            <div id="componentsContainer">
                                <div class="component-row">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <input type="text" name="components[0][name]" placeholder="Component Name" 
                                                   class="component-name">
                                        </div>
                                        <div class="form-group">
                                            <select name="components[0][type]" class="component-type">
                                                <option value="">Type</option>
                                                <option value="allowance">Allowance</option>
                                                <option value="bonus">Bonus</option>
                                                <option value="incentive">Incentive</option>
                                                <option value="benefit">Benefit</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <input type="number" name="components[0][amount]" step="0.01" min="0" 
                                                   placeholder="Amount" class="component-amount">
                                        </div>
                                        <div class="form-group">
                                            <input type="number" name="components[0][percentage]" step="0.1" min="0" max="100" 
                                                   placeholder="%" class="component-percentage">
                                        </div>
                                        <div class="form-group">
                                            <button type="button" class="btn btn-danger btn-sm remove-component">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" id="addComponent">
                                <i class="fas fa-plus"></i> Add Component
                            </button>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Plan
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Reset Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Existing Compensation Plans -->
            <div class="card">
                <div class="card-header">
                    <h2>Existing Compensation Plans</h2>
                </div>
                <div class="card-body">
                    <?php if ($existing_plans): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Plan Name</th>
                                        <th>Type</th>
                                        <th>Effective Date</th>
                                        <th>Status</th>
                                        <th>Components</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($existing_plans as $plan): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($plan['name']); ?></strong></td>
                                            <td>
                                                <span class="badge badge-secondary"><?php echo ucfirst(str_replace('_', ' ', $plan['type'])); ?></span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($plan['effective_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $plan['status'] == 'active' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($plan['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-info"><?php echo $plan['component_count']; ?> components</span>
                                            </td>
                                            <td>
                                                <a href="view_plan.php?id=<?php echo $plan['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="edit_plan.php?id=<?php echo $plan['id']; ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No compensation plans found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
    <script>
        let componentCounter = 1;

        document.getElementById('addComponent').addEventListener('click', function() {
            const container = document.getElementById('componentsContainer');
            const newRow = document.createElement('div');
            newRow.className = 'component-row';
            newRow.innerHTML = `
                <div class="form-row">
                    <div class="form-group">
                        <input type="text" name="components[${componentCounter}][name]" placeholder="Component Name" 
                               class="component-name">
                    </div>
                    <div class="form-group">
                        <select name="components[${componentCounter}][type]" class="component-type">
                            <option value="">Type</option>
                            <option value="allowance">Allowance</option>
                            <option value="bonus">Bonus</option>
                            <option value="incentive">Incentive</option>
                            <option value="benefit">Benefit</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="number" name="components[${componentCounter}][amount]" step="0.01" min="0" 
                               placeholder="Amount" class="component-amount">
                    </div>
                    <div class="form-group">
                        <input type="number" name="components[${componentCounter}][percentage]" step="0.1" min="0" max="100" 
                               placeholder="%" class="component-percentage">
                    </div>
                    <div class="form-group">
                        <button type="button" class="btn btn-danger btn-sm remove-component">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newRow);
            componentCounter++;
        });

        // Remove component row
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-component')) {
                e.target.closest('.component-row').remove();
            }
        });

        // Auto-save form data
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('compensationPlanForm');
            const formData = JSON.parse(localStorage.getItem('compensationPlanFormData') || '{}');
            
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
                    localStorage.setItem('compensationPlanFormData', JSON.stringify(formData));
                }
            });

            // Clear saved data on successful submission
            form.addEventListener('submit', function() {
                if (form.checkValidity()) {
                    localStorage.removeItem('compensationPlanFormData');
                }
            });
        });
    </script>
</body>
</html>
