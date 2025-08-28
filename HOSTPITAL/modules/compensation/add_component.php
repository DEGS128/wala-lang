<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit(); }

$message='';$error='';
$plans = $pdo->query('SELECT id, name FROM compensation_plans ORDER BY created_at DESC')->fetchAll();

if ($_SERVER['REQUEST_METHOD']==='POST'){
  try{
    if (empty($_POST['plan_id']) || empty($_POST['name']) || empty($_POST['type'])){
      throw new Exception('Please fill all required fields');
    }
    $stmt=$pdo->prepare('INSERT INTO compensation_components (plan_id, name, type, amount, criteria) VALUES (?,?,?,?,?)');
    $stmt->execute([
      $_POST['plan_id'],
      $_POST['name'],
      $_POST['type'],
      $_POST['amount'] ?: 0,
      $_POST['criteria'] ?? ''
    ]);
    $message='Component added successfully!';
    $_POST=[];
  }catch(Exception $e){$error=$e->getMessage();}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Compensation Component</title>
<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../../includes/header.php'; ?>
<div class="container">
  <div class="page-header">
    <h1><i class="fas fa-puzzle-piece"></i> Add Component</h1>
    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Compensation</a>
  </div>
  <?php if($message): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?></div><?php endif; ?>
  <?php if($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

  <div class="card">
    <div class="card-header"><h2>Component Information</h2></div>
    <div class="card-body">
      <form method="POST" class="form-container">
        <div class="form-row">
          <div class="form-group">
            <label for="plan_id">Plan *</label>
            <select id="plan_id" name="plan_id" required>
              <option value="">Select Plan</option>
              <?php foreach($plans as $p): ?>
              <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="name">Component Name *</label>
            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="type">Type *</label>
            <select id="type" name="type" required>
              <option value="">Select Type</option>
              <option value="allowance">Allowance</option>
              <option value="bonus">Bonus</option>
              <option value="incentive">Incentive</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label for="amount">Amount</label>
            <input type="number" id="amount" name="amount" step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-group">
          <label for="criteria">Criteria</label>
          <textarea id="criteria" name="criteria" rows="4"><?php echo htmlspecialchars($_POST['criteria'] ?? ''); ?></textarea>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Component</button>
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
