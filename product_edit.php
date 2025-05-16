<?php
// product_edit.php

require_once 'config/db.php';
require_once 'includes/functions.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND market_id = ?');
$stmt->execute([$id, $_SESSION['market_id']]);
$p = $stmt->fetch();
if (!$p) {
    exit('Product not found');
}

$errors = [];
$title            = $p['title'];
$stock            = $p['stock'];
$normal_price     = $p['normal_price'];
$discounted_price = $p['discounted_price'];
$expiration_date  = $p['expiration_date'];

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

    $imgPath = $p['image_path'];
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
          'UPDATE products SET
             title=?,stock=?,normal_price=?,discounted_price=?,expiration_date=?,image_path=?
           WHERE id=? AND market_id=?'
        );
        $stmt->execute([
          $title, $stock, $normal_price,
          $discounted_price, $expiration_date,
          $imgPath, $id, $_SESSION['market_id']
        ]);
        header('Location: product_list.php');
        exit;
    }
}

include 'views/header.php';
?>
<h2>Edit Product</h2>
<form method="post" enctype="multipart/form-data" novalidate>
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <?php foreach (['title','stock','normal_price','discounted_price','expiration_date'] as $field): ?>
    <div class="mb-3">
      <label class="form-label"><?= ucfirst(str_replace('_',' ',$field)) ?></label>
      <input
        type="<?= $field==='stock'?'number': ($field==='expiration_date'?'date':'text') ?>"
        name="<?= $field ?>"
        class="form-control"
        value="<?= e($$field) ?>"
      >
      <div class="text-danger"><?= $errors[$field] ?? '' ?></div>
    </div>
  <?php endforeach; ?>

  <div class="mb-3">
    <label class="form-label">Current Image</label><br>
    <?php if ($p['image_path']): ?>
      <img src="<?= e($p['image_path']) ?>" style="max-width:200px"><br>
    <?php else: ?>
      <em>None</em><br>
    <?php endif; ?>
    <label class="form-label mt-2">Replace Image (optional)</label>
    <input type="file" name="image" class="form-control">
    <div class="text-danger"><?= $errors['image'] ?? '' ?></div>
  </div>

  <button class="btn btn-primary">Save</button>
  <a href="product_list.php" class="btn btn-secondary">Cancel</a>
</form>
<?php include 'views/footer.php'; ?>
