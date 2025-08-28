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
            throw new Exception("Deduction name is required.");
        }

        if (empty($_POST['type'])) {
            throw new Exception("Deduction type is required.");
        }

        if (empty($_POST['amount']) && empty($_POST['percentage'])) {
            throw new Exception("Either amount or percentage is required.");
        }

        // Check if deduction name already exists
        $stmt = $pdo->prepare("SELECT id FROM deductions WHERE name = ?");
        $stmt->execute([trim($_POST['name'])]);
        if ($stmt->fetch()) {
            throw new Exception("Deduction name already exists.");
        }

        // Insert new deduction
        $stmt = $pdo->prepare("
            INSERT INTO deductions (
                name, type, amount, percentage, description, is_mandatory, 
                applies_to_all, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        $stmt->execute([
            trim($_POST['name']),
            $_POST['type'],
            $_POST['amount'] ?: null,
            $_POST['percentage'] ?: null,
            $_POST['description'] ?? '',
            isset($_POST['is_mandatory']) ? 1 : 0,
            isset($_POST['applies_to_all']) ? 1 : 0
        ]);

        $message = "Deduction added successfully!";
        
        // Clear form data
        $_POST = array();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch existing deductions
$stmt = $pdo->query("
    SELECT d.*, 
           COUNT(DISTINCT ed.employee_id) as employee_count,
           SUM(ed.amount) as total_collected
    FROM deductions d
    LEFT JOIN employee_deductions ed ON d.id = ed.deduction_id
    GROUP BY d.id
    ORDER BY d.name
");
$deductions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Deductions - HOSPITAL</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../../includes/header.php'; ?>
        
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-minus-circle"></i> Manage Deductions</h1>
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
                    <h2>Add New Deduction</h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="form-container" id="deductionForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Deduction Name *</label>
                                <input type="text" id="name" name="name" required 
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                       placeholder="e.g., Tax, Insurance, Loan">
                            </div>
                            <div class="form-group">
                                <label for="type">Deduction Type *</label>
                                <select id="type" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="tax" <?php echo ($_POST['type'] ?? '') == 'tax' ? 'selected' : ''; ?>>Tax</option>
                                    <option value="insurance" <?php echo ($_POST['type'] ?? '') == 'insurance' ? 'selected' : ''; ?>>Insurance</option>
                                    <option value="loan" <?php echo ($_POST['type'] ?? '') == 'loan' ? 'selected' : ''; ?>>Loan</option>
                                    <option value="benefits" <?php echo ($_POST['type'] ?? '') == 'benefits' ? 'selected' : ''; ?>>Benefits</option>
                                    <option value="other" <?php echo ($_POST['type'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="amount">Fixed Amount</label>
                                <input type="number" id="amount" name="amount" step="0.01" min="0" 
                                       value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>"
                                       placeholder="0.00">
                                <small class="form-help">Leave empty if using percentage</small>
                            </div>
                            <div class="form-group">
                                <label for="percentage">Percentage (%)</label>
                                <input type="number" id="percentage" name="percentage" step="0.1" min="0" max="100" 
                                       value="<?php echo htmlspecialchars($_POST['percentage'] ?? ''); ?>"
                                       placeholder="0.0">
                                <small class="form-help">Leave empty if using fixed amount</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3" 
                                      placeholder="Enter deduction description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="is_mandatory" name="is_mandatory" 
                                           <?php echo isset($_POST['is_mandatory']) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Mandatory Deduction
                                </label>
                                <small class="form-help">Cannot be waived or modified</small>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="applies_to_all" name="applies_to_all" 
                                           <?php echo isset($_POST['applies_to_all']) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Applies to All Employees
                                </label>
                                <small class="form-help">Automatically applied to all active employees</small>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Add Deduction
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Reset Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Existing Deductions -->
            <div class="card">
                <div class="card-header">
                    <h2>Current Deductions</h2>
                </div>
                <div class="card-body">
                    <?php if ($deductions): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Deduction Name</th>
                                        <th>Type</th>
                                        <th>Amount/Percentage</th>
                                        <th>Mandatory</th>
                                        <th>Applies To</th>
                                        <th>Employees</th>
                                        <th>Total Collected</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deductions as $deduction): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($deduction['name']); ?></strong>
                                                <?php if ($deduction['is_mandatory']): ?>
                                                    <span class="badge badge-danger">Mandatory</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary"><?php echo ucfirst($deduction['type']); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($deduction['amount']): ?>
                                                    ₱<?php echo number_format($deduction['amount'], 2); ?>
                                                <?php elseif ($deduction['percentage']): ?>
                                                    <?php echo $deduction['percentage']; ?>%
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($deduction['is_mandatory']): ?>
                                                    <i class="fas fa-check text-success"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-times text-muted"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($deduction['applies_to_all']): ?>
                                                    <span class="badge badge-info">All Employees</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Selected Only</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-primary"><?php echo $deduction['employee_count']; ?> employees</span>
                                            </td>
                                            <td>
                                                <?php if ($deduction['total_collected']): ?>
                                                    ₱<?php echo number_format($deduction['total_collected'], 2); ?>
                                                <?php else: ?>
                                                    ₱0.00
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="editDeduction(<?php echo htmlspecialchars(json_encode($deduction)); ?>)">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-info" onclick="assignEmployees(<?php echo $deduction['id']; ?>)">
                                                    <i class="fas fa-users"></i> Assign
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No deductions found.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Deduction Guidelines -->
            <div class="card">
                <div class="card-header">
                    <h2>Guidelines</h2>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Tax Deductions:</label>
                            <span>Mandatory government taxes (SSS, PhilHealth, etc.)</span>
                        </div>
                        <div class="info-item">
                            <label>Insurance:</label>
                            <span>Health, life, and other insurance premiums</span>
                        </div>
                        <div class="info-item">
                            <label>Loans:</label>
                            <span>Salary advances and company loans</span>
                        </div>
                        <div class="info-item">
                            <label>Benefits:</label>
                            <span>Optional benefits and contributions</span>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> Mandatory deductions are automatically applied to all employees 
                        and cannot be waived. Percentage-based deductions are calculated from the basic salary.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
    <script>
        function editDeduction(deduction) {
            // Populate form with existing data
            document.getElementById('name').value = deduction.name;
            document.getElementById('type').value = deduction.type;
            document.getElementById('amount').value = deduction.amount || '';
            document.getElementById('percentage').value = deduction.percentage || '';
            document.getElementById('description').value = deduction.description || '';
            document.getElementById('is_mandatory').checked = deduction.is_mandatory == 1;
            document.getElementById('applies_to_all').checked = deduction.applies_to_all == 1;
            
            // Scroll to form
            document.getElementById('deductionForm').scrollIntoView({ behavior: 'smooth' });
        }

        function assignEmployees(deductionId) {
            // Redirect to employee assignment page
            window.location.href = `assign_deductions.php?deduction_id=${deductionId}`;
        }

        // Auto-save form data
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('deductionForm');
            const formData = JSON.parse(localStorage.getItem('deductionFormData') || '{}');
            
            // Restore form data
            Object.keys(formData).forEach(key => {
                const field = form.querySelector(`[name="${key}"]`);
                if (field && !field.value && field.type !== 'checkbox') {
                    field.value = formData[key];
                }
            });

            // Auto-save on input
            form.addEventListener('input', function(e) {
                if (e.target.name) {
                    formData[e.target.name] = e.target.value;
                    localStorage.setItem('deductionFormData', JSON.stringify(formData));
                }
            });

            // Clear saved data on successful submission
            form.addEventListener('submit', function() {
                if (form.checkValidity()) {
                    localStorage.removeItem('deductionFormData');
                }
            });
        });
    </script>
</body>
</html>
