<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit(); }

$message = '';$error='';

$provider_id = $_GET['provider_id'] ?? null;

// Load providers for select
$providers = $pdo->query('SELECT id, name FROM hmo_providers ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD']==='POST') {
  try {
    if (empty($_POST['provider_id']) || empty($_POST['plan_name']) || empty($_POST['premium_amount'])) {
      throw new Exception('Please fill required fields');
    }
    $stmt = $pdo->prepare('INSERT INTO hmo_plans (provider_id, plan_name, coverage_details, premium_amount, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)');
    $stmt->execute([
      $_POST['provider_id'],
      $_POST['plan_name'],
      $_POST['coverage_details'] ?? '',
      $_POST['premium_amount']
    ]);
    $message = 'HMO plan added successfully!';
    $_POST = [];
  } catch (Exception $e) { $error = $e->getMessage(); }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add HMO Plan</title>
<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../../includes/header.php'; ?>
<div class="container">
  <div class="page-header">
    <h1><i class="fas fa-plus"></i> Add HMO Plan</h1>
    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to HMO</a>
  </div>

  <?php if($message): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?></div><?php endif; ?>
  <?php if($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

  <div class="card">
    <div class="card-header"><h2>Plan Information</h2></div>
    <div class="card-body">
      <form method="POST" class="form-container">
        <div class="form-row">
          <div class="form-group">
            <label for="provider_id">Provider *</label>
            <select id="provider_id" name="provider_id" required>
              <option value="">Select Provider</option>
              <?php foreach ($providers as $p): ?>
                <option value="<?php echo $p['id']; ?>" <?php echo ($provider_id==$p['id'])?'selected':''; ?>><?php echo htmlspecialchars($p['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="plan_name">Plan Name *</label>
            <input type="text" id="plan_name" name="plan_name" required value="<?php echo htmlspecialchars($_POST['plan_name'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-group">
          <label for="coverage_details">Coverage Details</label>
          <textarea id="coverage_details" name="coverage_details" rows="4"><?php echo htmlspecialchars($_POST['coverage_details'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
          <label for="premium_amount">Premium Amount (₱) *</label>
          <input type="number" id="premium_amount" name="premium_amount" step="0.01" min="0" required value="<?php echo htmlspecialchars($_POST['premium_amount'] ?? ''); ?>">
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Plan</button>
          <button type="reset" class="btn btn-secondary"><i class="fas fa-undo"></i> Reset</button>
        </div>
      </form>
    </div>
  </div>
</div>
</div>
<script src="../../assets/js/main.js"></script>
</body>
</html>
