<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$message = '';
$error = '';
$employee = null;

// Get employee ID from URL
$employee_id = $_GET['id'] ?? null;
if (!$employee_id) {
    header('Location: index.php');
    exit();
}

// Fetch departments and positions for dropdowns
$stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
$departments = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id, title FROM positions ORDER BY title");
$positions = $stmt->fetchAll();

// Fetch employee data
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
} catch (Exception $e) {
    $error = $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $employee) {
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

        // Check if email already exists for other employees
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE email = ? AND id != ?");
        $stmt->execute([$_POST['email'], $employee_id]);
        if ($stmt->fetch()) {
            throw new Exception("Email already exists in the system.");
        }

        // Update employee
        $stmt = $pdo->prepare("
            UPDATE employees SET
                first_name = ?, last_name = ?, email = ?, phone = ?, address = ?,
                department_id = ?, position_id = ?, hire_date = ?, salary = ?,
                status = ?, emergency_contact = ?, emergency_phone = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
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
            $_POST['status'],
            $_POST['emergency_contact'] ?? '',
            $_POST['emergency_phone'] ?? '',
            $employee_id
        ]);

        $message = "Employee updated successfully!";
        
        // Refresh employee data
        $stmt = $pdo->prepare("
            SELECT e.*, d.name as department_name, p.title as position_title
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            WHERE e.id = ?
        ");
        $stmt->execute([$employee_id]);
        $employee = $stmt->fetch();
        
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
    <title>Edit Employee - HOSPITAL</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../../includes/header.php'; ?>
        
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-user-edit"></i> Edit Employee</h1>
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

            <?php if ($employee): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>Edit Employee: <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="form-container" id="employeeForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name">First Name *</label>
                                    <input type="text" id="first_name" name="first_name" required 
                                           value="<?php echo htmlspecialchars($employee['first_name']); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="last_name">Last Name *</label>
                                    <input type="text" id="last_name" name="last_name" required 
                                           value="<?php echo htmlspecialchars($employee['last_name']); ?>">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">Email *</label>
                                    <input type="email" id="email" name="email" required 
                                           value="<?php echo htmlspecialchars($employee['email']); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone *</label>
                                    <input type="tel" id="phone" name="phone" required 
                                           value="<?php echo htmlspecialchars($employee['phone']); ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($employee['address'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="department_id">Department *</label>
                                    <select id="department_id" name="department_id" required>
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo $dept['id']; ?>" 
                                                    <?php echo $employee['department_id'] == $dept['id'] ? 'selected' : ''; ?>>
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
                                                    <?php echo $employee['position_id'] == $pos['id'] ? 'selected' : ''; ?>>
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
                                           value="<?php echo htmlspecialchars($employee['hire_date']); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="salary">Salary *</label>
                                    <input type="number" id="salary" name="salary" step="0.01" min="0" required 
                                           value="<?php echo htmlspecialchars($employee['salary']); ?>">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="status">Status *</label>
                                    <select id="status" name="status" required>
                                        <option value="active" <?php echo $employee['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $employee['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="terminated" <?php echo $employee['status'] == 'terminated' ? 'selected' : ''; ?>>Terminated</option>
                                        <option value="on_leave" <?php echo $employee['status'] == 'on_leave' ? 'selected' : ''; ?>>On Leave</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="emergency_contact">Emergency Contact</label>
                                    <input type="text" id="emergency_contact" name="emergency_contact" 
                                           value="<?php echo htmlspecialchars($employee['emergency_contact'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="emergency_phone">Emergency Phone</label>
                                <input type="tel" id="emergency_phone" name="emergency_phone" 
                                       value="<?php echo htmlspecialchars($employee['emergency_phone'] ?? ''); ?>">
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Employee
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> Employee not found or error occurred.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
</body>
</html>
