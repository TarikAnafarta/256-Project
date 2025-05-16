<?php
// market_profile.php

require_once 'config/db.php';
require_once 'includes/functions.php';
requireLogin();

$errors = [];
$mId = $_SESSION['market_id'];

// Fetch current data
$stmt = $pdo->prepare('SELECT email,name,city,district FROM markets WHERE id = ?');
$stmt->execute([$mId]);
$market = $stmt->fetch();

// Initialize form vars
$name     = $market['name'];
$city     = $market['city'];
$district = $market['district'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name     = trim($_POST['name'] ?? '');
    $city     = trim($_POST['city'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '')     $errors['name']     = 'Name required';
    if ($city === '')     $errors['city']     = 'City required';
    if ($district === '') $errors['district'] = 'District required';
    if ($password && strlen($password) < 6) {
        $errors['password'] = 'If changing, password ≥6 chars';
    }

    if (empty($errors)) {
        // Build query
        $fields = ['name = ?', 'city = ?', 'district = ?'];
        $params = [$name, $city, $district];

        if ($password) {
            $fields[] = 'password_hash = ?';
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }

        $params[] = $mId;
        $sql = 'UPDATE markets SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $pdo->prepare($sql)->execute($params);

        header('Location: product_list.php');
        exit;
    }
}

include 'views/header.php';
?>
<h2>My Profile</h2>
<form method="post" novalidate>
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <div class="mb-3">
    <label class="form-label">Email (cannot change)</label>
    <input type="email" class="form-control" value="<?= e($market['email']) ?>" disabled>
  </div>
  <?php foreach (['name','city','district'] as $field): ?>
    <div class="mb-3">
      <label class="form-label"><?= ucfirst($field) ?></label>
      <input type="text" name="<?= $field ?>" class="form-control" 
             value="<?= e($$field) ?>">
      <div class="text-danger"><?= $errors[$field] ?? '' ?></div>
    </div>
  <?php endforeach; ?>
  <div class="mb-3">
    <label class="form-label">New Password (optional)</label>
    <input type="password" name="password" class="form-control">
    <div class="text-danger"><?= $errors['password'] ?? '' ?></div>
  </div>
  <button class="btn btn-primary">Save Changes</button>
</form>
<?php include 'views/footer.php'; ?>
