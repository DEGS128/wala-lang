<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Handle notification preferences
        if (isset($_POST['notifications'])) {
            $notification_preferences = [
                'email_notifications' => isset($_POST['email_notifications']),
                'sms_notifications' => isset($_POST['sms_notifications']),
                'push_notifications' => isset($_POST['push_notifications']),
                'payroll_alerts' => isset($_POST['payroll_alerts']),
                'attendance_reminders' => isset($_POST['attendance_reminders']),
                'system_updates' => isset($_POST['system_updates'])
            ];
            
            // Save to session for now (in a real app, save to database)
            $_SESSION['notification_preferences'] = $notification_preferences;
            $message = "Notification preferences updated successfully!";
        }
        
        // Handle display preferences
        if (isset($_POST['display'])) {
            $display_preferences = [
                'theme' => $_POST['theme'] ?? 'light',
                'language' => $_POST['language'] ?? 'en',
                'timezone' => $_POST['timezone'] ?? 'Asia/Manila',
                'date_format' => $_POST['date_format'] ?? 'M j, Y',
                'time_format' => $_POST['time_format'] ?? '12h'
            ];
            
            $_SESSION['display_preferences'] = $display_preferences;
            $message = "Display preferences updated successfully!";
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get current preferences
$notification_preferences = $_SESSION['notification_preferences'] ?? [
    'email_notifications' => true,
    'sms_notifications' => false,
    'push_notifications' => true,
    'payroll_alerts' => true,
    'attendance_reminders' => true,
    'system_updates' => false
];

$display_preferences = $_SESSION['display_preferences'] ?? [
    'theme' => 'light',
    'language' => 'en',
    'timezone' => 'Asia/Manila',
    'date_format' => 'M j, Y',
    'time_format' => '12h'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - HOSPITAL</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-cog"></i> Settings</h1>
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

            <!-- Notification Preferences -->
            <div class="card">
                <div class="card-header">
                    <h2>Notification Preferences</h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="form-container">
                        <input type="hidden" name="notifications" value="1">
                        
                        <div class="form-group">
                            <h3>Notification Channels</h3>
                            <div class="checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="email_notifications" 
                                           <?php echo $notification_preferences['email_notifications'] ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Email Notifications
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="sms_notifications" 
                                           <?php echo $notification_preferences['sms_notifications'] ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    SMS Notifications
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="push_notifications" 
                                           <?php echo $notification_preferences['push_notifications'] ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Push Notifications
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <h3>Notification Types</h3>
                            <div class="checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="payroll_alerts" 
                                           <?php echo $notification_preferences['payroll_alerts'] ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Payroll Alerts
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="attendance_reminders" 
                                           <?php echo $notification_preferences['attendance_reminders'] ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Attendance Reminders
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="system_updates" 
                                           <?php echo $notification_preferences['system_updates'] ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    System Updates
                                </label>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Notification Preferences
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Display Preferences -->
            <div class="card">
                <div class="card-header">
                    <h2>Display Preferences</h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="form-container">
                        <input type="hidden" name="display" value="1">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="theme">Theme</label>
                                <select id="theme" name="theme">
                                    <option value="light" <?php echo $display_preferences['theme'] == 'light' ? 'selected' : ''; ?>>Light</option>
                                    <option value="dark" <?php echo $display_preferences['theme'] == 'dark' ? 'selected' : ''; ?>>Dark</option>
                                    <option value="auto" <?php echo $display_preferences['theme'] == 'auto' ? 'selected' : ''; ?>>Auto (System)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="language">Language</label>
                                <select id="language" name="language">
                                    <option value="en" <?php echo $display_preferences['language'] == 'en' ? 'selected' : ''; ?>>English</option>
                                    <option value="tl" <?php echo $display_preferences['language'] == 'tl' ? 'selected' : ''; ?>>Tagalog</option>
                                    <option value="es" <?php echo $display_preferences['language'] == 'es' ? 'selected' : ''; ?>>Spanish</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="timezone">Timezone</label>
                                <select id="timezone" name="timezone">
                                    <option value="Asia/Manila" <?php echo $display_preferences['timezone'] == 'Asia/Manila' ? 'selected' : ''; ?>>Philippines (GMT+8)</option>
                                    <option value="UTC" <?php echo $display_preferences['timezone'] == 'UTC' ? 'selected' : ''; ?>>UTC (GMT+0)</option>
                                    <option value="America/New_York" <?php echo $display_preferences['timezone'] == 'America/New_York' ? 'selected' : ''; ?>>Eastern Time (GMT-5)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="date_format">Date Format</label>
                                <select id="date_format" name="date_format">
                                    <option value="M j, Y" <?php echo $display_preferences['date_format'] == 'M j, Y' ? 'selected' : ''; ?>>Jan 15, 2024</option>
                                    <option value="j/m/Y" <?php echo $display_preferences['date_format'] == 'j/m/Y' ? 'selected' : ''; ?>>15/01/2024</option>
                                    <option value="Y-m-d" <?php echo $display_preferences['date_format'] == 'Y-m-d' ? 'selected' : ''; ?>>2024-01-15</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="time_format">Time Format</label>
                            <select id="time_format" name="time_format">
                                <option value="12h" <?php echo $display_preferences['time_format'] == '12h' ? 'selected' : ''; ?>>12-hour (1:30 PM)</option>
                                <option value="24h" <?php echo $display_preferences['time_format'] == '24h' ? 'selected' : ''; ?>>24-hour (13:30)</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Display Preferences
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- System Information -->
            <div class="card">
                <div class="card-header">
                    <h2>System Information</h2>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>System Version:</label>
                            <span>HOSPITAL v1.0.0</span>
                        </div>
                        <div class="info-item">
                            <label>PHP Version:</label>
                            <span><?php echo PHP_VERSION; ?></span>
                        </div>
                        <div class="info-item">
                            <label>Database:</label>
                            <span>MySQL 5.7+</span>
                        </div>
                        <div class="info-item">
                            <label>Last Updated:</label>
                            <span><?php echo date('F j, Y'); ?></span>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> Some settings may require a page refresh to take effect. 
                        Contact your system administrator for additional configuration options.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <style>
        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .checkbox-group .checkbox-label {
            margin-bottom: 0;
        }
    </style>
</body>
</html>
