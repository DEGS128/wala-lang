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
        if (empty($_POST['position_id'])) {
            throw new Exception("Position is required.");
        }

        if (empty($_POST['min_salary']) || empty($_POST['max_salary'])) {
            throw new Exception("Salary range is required.");
        }

        if ($_POST['min_salary'] > $_POST['max_salary']) {
            throw new Exception("Minimum salary cannot be greater than maximum salary.");
        }

        // Check if salary structure already exists for this position
        $stmt = $pdo->prepare("SELECT id FROM salary_structures WHERE position_id = ?");
        $stmt->execute([$_POST['position_id']]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update existing structure
            $stmt = $pdo->prepare("
                UPDATE salary_structures SET
                    min_salary = ?, max_salary = ?, allowances_percentage = ?, 
                    deductions_percentage = ?, updated_at = CURRENT_TIMESTAMP
                WHERE position_id = ?
            ");
            $stmt->execute([
                $_POST['min_salary'],
                $_POST['max_salary'],
                $_POST['allowances_percentage'] ?? 10,
                $_POST['deductions_percentage'] ?? 7,
                $_POST['position_id']
            ]);
            $message = "Salary structure updated successfully!";
        } else {
            // Insert new structure
            $stmt = $pdo->prepare("
                INSERT INTO salary_structures (
                    position_id, min_salary, max_salary, allowances_percentage, 
                    deductions_percentage, created_at
                ) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([
                $_POST['position_id'],
                $_POST['min_salary'],
                $_POST['max_salary'],
                $_POST['allowances_percentage'] ?? 10,
                $_POST['deductions_percentage'] ?? 7
            ]);
            $message = "Salary structure added successfully!";
        }

        // Also update the position table
        $stmt = $pdo->prepare("
            UPDATE positions SET
                min_salary = ?, max_salary = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['min_salary'],
            $_POST['max_salary'],
            $_POST['position_id']
        ]);
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch positions with departments
$stmt = $pdo->query("
    SELECT p.*, d.name as department_name 
    FROM positions p 
    LEFT JOIN departments d ON p.department_id = d.id 
    ORDER BY d.name, p.title
");
$positions = $stmt->fetchAll();

// Fetch existing salary structures
$stmt = $pdo->query("
    SELECT ss.*, p.title as position_title, d.name as department_name
    FROM salary_structures ss
    LEFT JOIN positions p ON ss.position_id = p.id
    LEFT JOIN departments d ON p.department_id = d.id
    ORDER BY d.name, p.title
");
$salary_structures = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Structure - HOSPITAL</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../../includes/header.php'; ?>
        
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-money-bill-wave"></i> Salary Structure Management</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Payroll
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
                    <h2>Add/Edit Salary Structure</h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="form-container" id="salaryStructureForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="position_id">Position *</label>
                                <select id="position_id" name="position_id" required>
                                    <option value="">Select Position</option>
                                    <?php foreach ($positions as $pos): ?>
                                        <option value="<?php echo $pos['id']; ?>">
                                            <?php echo htmlspecialchars($pos['department_name'] . ' - ' . $pos['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="min_salary">Minimum Salary *</label>
                                <input type="number" id="min_salary" name="min_salary" step="0.01" min="0" required 
                                       placeholder="0.00">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="max_salary">Maximum Salary *</label>
                                <input type="number" id="max_salary" name="max_salary" step="0.01" min="0" required 
                                       placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label for="allowances_percentage">Allowances (%)</label>
                                <input type="number" id="allowances_percentage" name="allowances_percentage" 
                                       step="0.1" min="0" max="100" value="10"
                                       placeholder="10.0">
                                <small class="form-help">Percentage of basic salary for allowances</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="deductions_percentage">Deductions (%)</label>
                            <input type="number" id="deductions_percentage" name="deductions_percentage" 
                                   step="0.1" min="0" max="100" value="7"
                                   placeholder="7.0">
                            <small class="form-help">Percentage of basic salary for taxes and benefits</small>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Structure
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Reset Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Existing Salary Structures -->
            <div class="card">
                <div class="card-header">
                    <h2>Current Salary Structures</h2>
                </div>
                <div class="card-body">
                    <?php if ($salary_structures): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Position</th>
                                        <th>Department</th>
                                        <th>Salary Range</th>
                                        <th>Allowances</th>
                                        <th>Deductions</th>
                                        <th>Last Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($salary_structures as $structure): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($structure['position_title']); ?></strong></td>
                                            <td>
                                                <span class="badge badge-primary"><?php echo htmlspecialchars($structure['department_name']); ?></span>
                                            </td>
                                            <td>
                                                ₱<?php echo number_format($structure['min_salary'], 2); ?> - 
                                                ₱<?php echo number_format($structure['max_salary'], 2); ?>
                                            </td>
                                            <td><?php echo $structure['allowances_percentage']; ?>%</td>
                                            <td><?php echo $structure['deductions_percentage']; ?>%</td>
                                            <td><?php echo date('M j, Y', strtotime($structure['updated_at'] ?? $structure['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="editStructure(<?php echo htmlspecialchars(json_encode($structure)); ?>)">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No salary structures found.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Salary Structure Guidelines -->
            <div class="card">
                <div class="card-header">
                    <h2>Guidelines</h2>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Minimum Salary:</label>
                            <span>Starting salary for entry-level positions</span>
                        </div>
                        <div class="info-item">
                            <label>Maximum Salary:</label>
                            <span>Highest salary for experienced employees</span>
                        </div>
                        <div class="info-item">
                            <label>Allowances:</label>
                            <span>Additional benefits (transport, meal, housing)</span>
                        </div>
                        <div class="info-item">
                            <label>Deductions:</label>
                            <span>Taxes, insurance, and other mandatory deductions</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
    <script>
        function editStructure(structure) {
            // Populate form with existing data
            document.getElementById('position_id').value = structure.position_id;
            document.getElementById('min_salary').value = structure.min_salary;
            document.getElementById('max_salary').value = structure.max_salary;
            document.getElementById('allowances_percentage').value = structure.allowances_percentage;
            document.getElementById('deductions_percentage').value = structure.deductions_percentage;
            
            // Scroll to form
            document.getElementById('salaryStructureForm').scrollIntoView({ behavior: 'smooth' });
        }

        // Auto-save form data
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('salaryStructureForm');
            const formData = JSON.parse(localStorage.getItem('salaryStructureFormData') || '{}');
            
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
                    localStorage.setItem('salaryStructureFormData', JSON.stringify(formData));
                }
            });

            // Clear saved data on successful submission
            form.addEventListener('submit', function() {
                if (form.checkValidity()) {
                    localStorage.removeItem('salaryStructureFormData');
                }
            });
        });
    </script>
</body>
</html>
