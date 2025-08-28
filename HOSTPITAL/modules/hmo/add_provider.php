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
            throw new Exception("Provider name is required.");
        }

        if (empty($_POST['contact_person'])) {
            throw new Exception("Contact person is required.");
        }

        if (empty($_POST['phone']) && empty($_POST['email'])) {
            throw new Exception("Either phone or email is required.");
        }

        // Check if provider name already exists
        $stmt = $pdo->prepare("SELECT id FROM hmo_providers WHERE name = ?");
        $stmt->execute([trim($_POST['name'])]);
        if ($stmt->fetch()) {
            throw new Exception("Provider name already exists.");
        }

        // Insert new HMO provider
        $stmt = $pdo->prepare("
            INSERT INTO hmo_providers (
                name, contact_person, phone, email, address, 
                website, coverage_details, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', CURRENT_TIMESTAMP)
        ");
        
        $stmt->execute([
            trim($_POST['name']),
            trim($_POST['contact_person']),
            $_POST['phone'] ?? '',
            $_POST['email'] ?? '',
            $_POST['address'] ?? '',
            $_POST['website'] ?? '',
            $_POST['coverage_details'] ?? ''
        ]);

        $message = "HMO provider added successfully!";
        
        // Clear form data
        $_POST = array();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch existing providers for reference
$stmt = $pdo->query("
    SELECT hp.*, COUNT(hp2.id) as plan_count
    FROM hmo_providers hp
    LEFT JOIN hmo_plans hp2 ON hp.id = hp2.provider_id
    GROUP BY hp.id
    ORDER BY hp.name
");
$existing_providers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add HMO Provider - HOSPITAL</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../../includes/header.php'; ?>
        
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-hospital"></i> Add HMO Provider</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to HMO & Benefits
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
                    <h2>Provider Information</h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="form-container" id="providerForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Provider Name *</label>
                                <input type="text" id="name" name="name" required 
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                       placeholder="e.g., Maxicare, Medicard, Intellicare">
                            </div>
                            <div class="form-group">
                                <label for="contact_person">Contact Person *</label>
                                <input type="text" id="contact_person" name="contact_person" required 
                                       value="<?php echo htmlspecialchars($_POST['contact_person'] ?? ''); ?>"
                                       placeholder="Full name of contact person">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                       placeholder="+63 XXX XXX XXXX">
                                <small class="form-help">Required if email is not provided</small>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       placeholder="contact@provider.com">
                                <small class="form-help">Required if phone is not provided</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" rows="3" 
                                      placeholder="Provider's business address"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="website">Website</label>
                                <input type="url" id="website" name="website" 
                                       value="<?php echo htmlspecialchars($_POST['website'] ?? ''); ?>"
                                       placeholder="https://www.provider.com">
                            </div>
                            <div class="form-group">
                                <label for="coverage_details">Coverage Details</label>
                                <select id="coverage_details" name="coverage_details">
                                    <option value="">Select Coverage Type</option>
                                    <option value="comprehensive" <?php echo ($_POST['coverage_details'] ?? '') == 'comprehensive' ? 'selected' : ''; ?>>Comprehensive</option>
                                    <option value="basic" <?php echo ($_POST['coverage_details'] ?? '') == 'basic' ? 'selected' : ''; ?>>Basic</option>
                                    <option value="premium" <?php echo ($_POST['coverage_details'] ?? '') == 'premium' ? 'selected' : ''; ?>>Premium</option>
                                    <option value="specialized" <?php echo ($_POST['coverage_details'] ?? '') == 'specialized' ? 'selected' : ''; ?>>Specialized</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Add Provider
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Reset Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Existing HMO Providers -->
            <div class="card">
                <div class="card-header">
                    <h2>Current HMO Providers</h2>
                </div>
                <div class="card-body">
                    <?php if ($existing_providers): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Provider Name</th>
                                        <th>Contact Person</th>
                                        <th>Contact Info</th>
                                        <th>Coverage</th>
                                        <th>Plans</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($existing_providers as $provider): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($provider['name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($provider['contact_person']); ?></td>
                                            <td>
                                                <?php if ($provider['phone']): ?>
                                                    <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($provider['phone']); ?></div>
                                                <?php endif; ?>
                                                <?php if ($provider['email']): ?>
                                                    <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($provider['email']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($provider['coverage_details']): ?>
                                                    <span class="badge badge-info"><?php echo ucfirst($provider['coverage_details']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">Not specified</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-primary"><?php echo $provider['plan_count']; ?> plans</span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $provider['status'] == 'active' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($provider['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="edit_provider.php?id=<?php echo $provider['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="add_plan.php?provider_id=<?php echo $provider['id']; ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-plus"></i> Add Plan
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No HMO providers found.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Provider Guidelines -->
            <div class="card">
                <div class="card-header">
                    <h2>Guidelines</h2>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Provider Name:</label>
                            <span>Official business name of the HMO provider</span>
                        </div>
                        <div class="info-item">
                            <label>Contact Person:</label>
                            <span>Primary contact for business inquiries</span>
                        </div>
                        <div class="info-item">
                            <label>Coverage Type:</label>
                            <span>Level of medical coverage provided</span>
                        </div>
                        <div class="info-item">
                            <label>Status:</label>
                            <span>Active providers can offer plans to employees</span>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> After adding a provider, you can create HMO plans and assign them to employees. 
                        Make sure to provide accurate contact information for smooth communication.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
    <script>
        // Auto-save form data
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('providerForm');
            const formData = JSON.parse(localStorage.getItem('providerFormData') || '{}');
            
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
                    localStorage.setItem('providerFormData', JSON.stringify(formData));
                }
            });

            // Clear saved data on successful submission
            form.addEventListener('submit', function() {
                if (form.checkValidity()) {
                    localStorage.removeItem('providerFormData');
                }
            });
        });
    </script>
</body>
</html>
