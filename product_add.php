<?php
// product_add.php

require_once 'config/db.php';
require_once 'includes/functions.php';
requireLogin();

$errors = [];
$title            = '';
$stock            = '';
$normal_price     = '';
$discounted_price = '';
$expiration_date  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $title            = trim($_POST['title'] ?? '');
    $stock            = (int)($_POST['stock'] ?? 0);
    $normal_price     = trim($_POST['normal_price'] ?? '');
    $discounted_price = trim($_POST['discounted_price'] ?? '');
    $expiration_date  = $_POST['expiration_date'] ?? '';

    if ($title === '')            $errors['title'] = 'Title required';
    if ($stock < 1)               $errors['stock'] = 'Stock must be ≥1';
    if (!is_numeric($normal_price) || $normal_price <= 0) {
        $errors['normal_price'] = 'Valid normal price';
    }
    if (!is_numeric($discounted_price) || $discounted_price <= 0) {
        $errors['discounted_price'] = 'Valid discounted price';
    }
    if (!$expiration_date)        $errors['expiration_date'] = 'Expiration date required';

    // Handle image upload
    $imgPath = null;
    if (!empty($_FILES['image']['name'])) {
        $f = $_FILES['image'];
        if ($f['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
            $new = 'uploads/' . bin2hex(random_bytes(8)) . '.' . $ext;
            if (move_uploaded_file($f['tmp_name'], $new)) {
                $imgPath = $new;
            } else {
                $errors['image'] = 'Upload failed';
            }
        } else {
            $errors['image'] = 'Upload error';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
          'INSERT INTO products
           (market_id,title,stock,normal_price,discounted_price,expiration_date,image_path)
           VALUES (?,?,?,?,?,?,?)'
        );
        $stmt->execute([
          $_SESSION['market_id'],
          $title,
          $stock,
          $normal_price,
          $discounted_price,
          $expiration_date,
          $imgPath
        ]);
        header('Location: product_list.php');
        exit;
    }
}

include 'views/header.php';
?>
<h2>Add Product</h2>
<form method="post" enctype="multipart/form-data" novalidate>
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

  <div class="mb-3">
    <label class="form-label">Title</label>
    <input type="text" name="title" class="form-control" value="<?= e($title) ?>">
    <div class="text-danger"><?= $errors['title'] ?? '' ?></div>
  </div>

  <div class="mb-3">
    <label class="form-label">Stock</label>
    <input type="number" name="stock" class="form-control" value="<?= e($stock) ?>">
    <div class="text-danger"><?= $errors['stock'] ?? '' ?></div>
  </div>

  <div class="mb-3">
    <label class="form-label">Normal Price</label>
    <input type="text" name="normal_price" class="form-control" value="<?= e($normal_price) ?>">
    <div class="text-danger"><?= $errors['normal_price'] ?? '' ?></div>
  </div>

  <div class="mb-3">
    <label class="form-label">Discounted Price</label>
    <input type="text" name="discounted_price" class="form-control" value="<?= e($discounted_price) ?>">
    <div class="text-danger"><?= $errors['discounted_price'] ?? '' ?></div>
  </div>

  <div class="mb-3">
    <label class="form-label">Expiration Date</label>
    <input type="date" name="expiration_date" class="form-control" value="<?= e($expiration_date) ?>">
    <div class="text-danger"><?= $errors['expiration_date'] ?? '' ?></div>
  </div>

  <div class="mb-3">
    <label class="form-label">Image (optional)</label>
    <input type="file" name="image" class="form-control">
    <div class="text-danger"><?= $errors['image'] ?? '' ?></div>
  </div>

  <button class="btn btn-success">Add Product</button>
  <a href="product_list.php" class="btn btn-secondary">Cancel</a>
</form>
<?php include 'views/footer.php'; ?>
