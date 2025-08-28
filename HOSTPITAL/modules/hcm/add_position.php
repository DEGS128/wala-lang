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
        // Validate required fields
        if (empty($_POST['title'])) {
            throw new Exception("Position title is required.");
        }

        if (empty($_POST['department_id'])) {
            throw new Exception("Department is required.");
        }

        if (empty($_POST['min_salary']) || empty($_POST['max_salary'])) {
            throw new Exception("Salary range is required.");
        }

        if ($_POST['min_salary'] > $_POST['max_salary']) {
            throw new Exception("Minimum salary cannot be greater than maximum salary.");
        }

        // Check if position title already exists in the same department
        $stmt = $pdo->prepare("SELECT id FROM positions WHERE title = ? AND department_id = ?");
        $stmt->execute([trim($_POST['title']), $_POST['department_id']]);
        if ($stmt->fetch()) {
            throw new Exception("Position title already exists in this department.");
        }

        // Insert new position
        $stmt = $pdo->prepare("
            INSERT INTO positions (title, department_id, description, min_salary, max_salary, requirements) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            trim($_POST['title']),
            $_POST['department_id'],
            $_POST['description'] ?? '',
            $_POST['min_salary'],
            $_POST['max_salary'],
            $_POST['requirements'] ?? ''
        ]);

        $message = "Position added successfully!";
        
        // Clear form data
        $_POST = array();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch departments for dropdown
$stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
$departments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Position - HOSPITAL</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../../includes/header.php'; ?>
        
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-user-tie"></i> Add New Position</h1>
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
                    <h2>Position Information</h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="form-container" id="positionForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="title">Position Title *</label>
                                <input type="text" id="title" name="title" required 
                                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                       placeholder="Enter position title">
                            </div>
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
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3" 
                                      placeholder="Enter position description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="min_salary">Minimum Salary *</label>
                                <input type="number" id="min_salary" name="min_salary" step="0.01" min="0" required 
                                       value="<?php echo htmlspecialchars($_POST['min_salary'] ?? ''); ?>"
                                       placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label for="max_salary">Maximum Salary *</label>
                                <input type="number" id="max_salary" name="max_salary" step="0.01" min="0" required 
                                       value="<?php echo htmlspecialchars($_POST['max_salary'] ?? ''); ?>"
                                       placeholder="0.00">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="requirements">Requirements</label>
                            <textarea id="requirements" name="requirements" rows="4" 
                                      placeholder="Enter position requirements (education, experience, skills)"><?php echo htmlspecialchars($_POST['requirements'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Add Position
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Reset Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Existing Positions -->
            <div class="card">
                <div class="card-header">
                    <h2>Existing Positions</h2>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->query("
                        SELECT p.*, d.name as department_name, COUNT(e.id) as employee_count 
                        FROM positions p 
                        LEFT JOIN departments d ON p.department_id = d.id 
                        LEFT JOIN employees e ON p.id = e.position_id 
                        GROUP BY p.id 
                        ORDER BY d.name, p.title
                    ");
                    $positions = $stmt->fetchAll();
                    ?>
                    
                    <?php if ($positions): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Position Title</th>
                                        <th>Department</th>
                                        <th>Salary Range</th>
                                        <th>Employees</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($positions as $pos): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($pos['title']); ?></strong></td>
                                            <td>
                                                <span class="badge badge-primary"><?php echo htmlspecialchars($pos['department_name']); ?></span>
                                            </td>
                                            <td>
                                                ₱<?php echo number_format($pos['min_salary'], 2); ?> - 
                                                ₱<?php echo number_format($pos['max_salary'], 2); ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-info"><?php echo $pos['employee_count']; ?> employees</span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($pos['created_at'])); ?></td>
                                            <td>
                                                <a href="edit_position.php?id=<?php echo $pos['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No positions found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
    <script>
        // Auto-save form data
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('positionForm');
            const formData = JSON.parse(localStorage.getItem('positionFormData') || '{}');
            
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
                    localStorage.setItem('positionFormData', JSON.stringify(formData));
                }
            });

            // Clear saved data on successful submission
            form.addEventListener('submit', function() {
                if (form.checkValidity()) {
                    localStorage.removeItem('positionFormData');
                }
            });
        });
    </script>
</body>
</html>
