<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$message = '';
$error = '';

// Fetch departments and positions for dropdowns
$stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
$departments = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id, title FROM positions ORDER BY title");
$positions = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate required fields
        $required_fields = ['first_name', 'last_name', 'email', 'phone', 'department_id', 'position_id', 'hire_date', 'salary'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("All fields are required.");
            }
        }

        // Validate email format
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE email = ?");
        $stmt->execute([$_POST['email']]);
        if ($stmt->fetch()) {
            throw new Exception("Email already exists in the system.");
        }

        // Insert new employee
        $stmt = $pdo->prepare("
            INSERT INTO employees (
                first_name, last_name, email, phone, address, 
                department_id, position_id, hire_date, salary, 
                status, emergency_contact, emergency_phone
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['address'] ?? '',
            $_POST['department_id'],
            $_POST['position_id'],
            $_POST['hire_date'],
            $_POST['salary'],
            'active',
            $_POST['emergency_contact'] ?? '',
            $_POST['emergency_phone'] ?? ''
        ]);

        $message = "Employee added successfully!";
        
        // Clear form data
        $_POST = array();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee - HOSPITAL</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../../includes/header.php'; ?>
        
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-user-plus"></i> Add New Employee</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to HCM
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
                    <h2>Employee Information</h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="form-container" id="employeeForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name *</label>
                                <input type="text" id="first_name" name="first_name" required 
                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" required 
                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" required 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone *</label>
                                <input type="tel" id="phone" name="phone" required 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="department_id">Department *</label>
                                <select id="department_id" name="department_id" required>
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>" 
                                                <?php echo ($_POST['department_id'] ?? '') == $dept['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="position_id">Position *</label>
                                <select id="position_id" name="position_id" required>
                                    <option value="">Select Position</option>
                                    <?php foreach ($positions as $pos): ?>
                                        <option value="<?php echo $pos['id']; ?>" 
                                                <?php echo ($_POST['position_id'] ?? '') == $pos['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($pos['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="hire_date">Hire Date *</label>
                                <input type="date" id="hire_date" name="hire_date" required 
                                       value="<?php echo htmlspecialchars($_POST['hire_date'] ?? date('Y-m-d')); ?>">
                            </div>
                            <div class="form-group">
                                <label for="salary">Salary *</label>
                                <input type="number" id="salary" name="salary" step="0.01" min="0" required 
                                       value="<?php echo htmlspecialchars($_POST['salary'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="emergency_contact">Emergency Contact</label>
                                <input type="text" id="emergency_contact" name="emergency_contact" 
                                       value="<?php echo htmlspecialchars($_POST['emergency_contact'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="emergency_phone">Emergency Phone</label>
                                <input type="tel" id="emergency_phone" name="emergency_phone" 
                                       value="<?php echo htmlspecialchars($_POST['emergency_phone'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Add Employee
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Reset Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
    <script>
        // Auto-save form data
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('employeeForm');
            const formData = JSON.parse(localStorage.getItem('employeeFormData') || '{}');
            
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
                    localStorage.setItem('employeeFormData', JSON.stringify(formData));
                }
            });

            // Clear saved data on successful submission
            form.addEventListener('submit', function() {
                if (form.checkValidity()) {
                    localStorage.removeItem('employeeFormData');
                }
            });
        });
    </script>
</body>
</html>
