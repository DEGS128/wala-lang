<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

// Fetch user profile
try {
    $stmt = $pdo->prepare("
        SELECT u.*, e.first_name, e.last_name, e.email, e.phone, e.department_id, e.position_id,
               d.name as department_name, p.title as position_title
        FROM users u
        LEFT JOIN employees e ON u.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN positions p ON e.position_id = p.id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (Exception $e) {
    $error = $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (empty($_POST['current_password'])) {
            throw new Exception("Current password is required.");
        }

        if (empty($_POST['new_password'])) {
            throw new Exception("New password is required.");
        }

        if (empty($_POST['confirm_password'])) {
            throw new Exception("Password confirmation is required.");
        }

        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            throw new Exception("New passwords do not match.");
        }

        if (strlen($_POST['new_password']) < 6) {
            throw new Exception("New password must be at least 6 characters long.");
        }

        // Verify current password
        if (!password_verify($_POST['current_password'], $user['password'])) {
            throw new Exception("Current password is incorrect.");
        }

        // Update password
        $new_password_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$new_password_hash, $_SESSION['user_id']]);

        $message = "Password updated successfully!";
        
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
    <title>Profile - HOSPITAL</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-user-circle"></i> My Profile</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
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

            <?php if ($user): ?>
                <!-- Profile Information -->
                <div class="card">
                    <div class="card-header">
                        <h2>Profile Information</h2>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Username:</label>
                                <span><?php echo htmlspecialchars($user['username']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Role:</label>
                                <span class="badge badge-primary"><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></span>
                            </div>
                            <?php if ($user['first_name']): ?>
                                <div class="info-item">
                                    <label>Full Name:</label>
                                    <span><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Email:</label>
                                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Phone:</label>
                                    <span><?php echo htmlspecialchars($user['phone']); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Department:</label>
                                    <span><?php echo htmlspecialchars($user['department_name']); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Position:</label>
                                    <span><?php echo htmlspecialchars($user['position_title']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <label>Account Created:</label>
                                <span><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="card">
                    <div class="card-header">
                        <h2>Change Password</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="form-container">
                            <div class="form-group">
                                <label for="current_password">Current Password *</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="new_password">New Password *</label>
                                    <input type="password" id="new_password" name="new_password" required 
                                           minlength="6">
                                    <small class="form-help">Minimum 6 characters</small>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password *</label>
                                    <input type="password" id="confirm_password" name="confirm_password" required 
                                           minlength="6">
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Update Password
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-undo"></i> Reset Form
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Account Security -->
                <div class="card">
                    <div class="card-header">
                        <h2>Account Security</h2>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Last Login:</label>
                                <span><?php echo date('F j, Y g:i A'); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Session Status:</label>
                                <span class="badge badge-success">Active</span>
                            </div>
                            <div class="info-item">
                                <label>Password Last Changed:</label>
                                <span><?php echo date('F j, Y'); ?></span>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Security Tips:</strong>
                            <ul style="margin: 0.5rem 0 0 1.5rem;">
                                <li>Use a strong, unique password</li>
                                <li>Never share your login credentials</li>
                                <li>Log out when using shared computers</li>
                                <li>Contact IT support if you suspect unauthorized access</li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
