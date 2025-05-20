<?php
// add_product.php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

// only markets may add
if (!isset($_SESSION['user_id'], $_SESSION['user_type']) || $_SESSION['user_type']!=='market') {
    header('Location: login.php');
    exit;
}

$pdo = getPDO();
// find market_id
$stmt = $pdo->prepare("SELECT market_id FROM markets WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$marketId = $stmt->fetchColumn();
if (!$marketId) die('Market profile not found.');

$errors = [];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    validate_csrf();

    // collect & validate
    $title            = trim($_POST['title'] ?? '');
    $stock            = (int)($_POST['stock'] ?? 0);
    $normal_price     = (float)($_POST['normal_price'] ?? 0);
    $discounted_price = (float)($_POST['discounted_price'] ?? 0);
    $exp_date         = $_POST['expiration_date'] ?? '';
    $img              = $_FILES['image'] ?? null;

    if ($title==='')          $errors[] = 'Title cannot be empty.';
    if ($stock < 1)           $errors[] = 'Stock must be at least 1.';
    if ($normal_price <= 0)   $errors[] = 'Normal price must be greater than zero.';
    if ($discounted_price <= 0 || $discounted_price > $normal_price) {
        $errors[] = 'Discounted price must not exceed the normal price and must be greater than zero.';
    }
    if (!$exp_date)           $errors[] = 'Expiration date required.';
    if (!$img || $img['error']!==UPLOAD_ERR_OK) {
        $errors[] = 'Upload an image.';
    }

    if (empty($errors)) {
        // handle upload
        $ext = pathinfo($img['name'], PATHINFO_EXTENSION);
        $filename = uniqid('prod_', true) . '.' . $ext;
        move_uploaded_file($img['tmp_name'], __DIR__ . '/img/' . $filename);

        // insert
        $ins = $pdo->prepare(<<<SQL
          INSERT INTO products
            (market_id, title, stock, normal_price, discounted_price, expiration_date, image)
          VALUES (?, ?, ?, ?, ?, ?, ?)
SQL
        );
        $ins->execute([
            $marketId,
            $title,
            $stock,
            $normal_price,
            $discounted_price,
            $exp_date,
            $filename
        ]);

        $_SESSION['flash'] = ['msg'=>'Product added.','error'=>false];
        header('Location: market_products.php');
        exit;
    }
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><title>Add New Product</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(to right, #e0f7fa, #fce4ec);
      margin: 0;
      padding: 20px;
    }

    .container {
      max-width: 550px;
      margin: 40px auto;
      background: #fff;
      padding: 35px;
      border-radius: 12px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }

    h2 {
      text-align: center;
      margin-bottom: 25px;
      color: #333;
      font-size: 1.8rem;
    }

    label {
      display: block;
      margin-top: 15px;
      font-weight: 600;
      color: #444;
    }

    input,
    button {
      width: 100%;
      padding: 12px;
      margin-top: 6px;
      border: 1px solid #ccc;
      border-radius: 6px;
      box-sizing: border-box;
      font-size: 15px;
    }

    input:focus {
      border-color: #007bff;
      outline: none;
      box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
    }

    button {
      background: linear-gradient(to right, #28a745, #3ddc84);
      color: #fff;
      font-weight: bold;
      border: none;
      cursor: pointer;
      margin-top: 20px;
      transition: background 0.3s, transform 0.2s;
    }

    button:hover {
      background: #218838;
      transform: scale(1.03);
    }

    .error {
      background: #f8d7da;
      color: #842029;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 20px;
      border-left: 5px solid #dc3545;
    }

    #toast {
      position: fixed;
      top: 20px;
      right: 20px;
      background: #28a745;
      color: #fff;
      padding: 12px 24px;
      border-radius: 6px;
      opacity: 0;
      transition: opacity 0.4s ease;
      font-weight: bold;
      font-size: 15px;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
      z-index: 1000;
    }

    #toast.show {
      opacity: 1;
    }

    p {
      text-align: center;
      margin-top: 20px;
    }

    p a {
      text-decoration: none;
      color: #007bff;
      font-weight: bold;
      transition: color 0.3s;
    }

    p a:hover {
      color: #0056b3;
    }
  </style>
</head>
<body>

  <div id="toast"></div>
  <?php if (!empty($_SESSION['flash'])):
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
  ?>
  <script>
    function showToast(msg, isErr) {
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.style.background = isErr ? '#dc3545' : '#28a745';
      t.classList.add('show');
      setTimeout(() => t.classList.remove('show'), 2500);
    }
    showToast(<?= json_encode($f['msg']) ?>, <?= $f['error'] ? 'true' : 'false' ?>);
  </script>
  <?php endif; ?>

  <div class="container">
    <h2>Add new product</h2>
    <?php if($errors): ?>
      <div class="error"><?php foreach($errors as $e) echo htmlspecialchars($e).'<br>' ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

      <label>Title</label>
      <input name="title" type="text" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>

      <label>Stock</label>
      <input name="stock" type="number" min="1" value="<?= htmlspecialchars($_POST['stock'] ?? '') ?>" required>

      <label>Normal Price (₺)</label>
      <input name="normal_price" type="number" step="0.01" value="<?= htmlspecialchars($_POST['normal_price'] ?? '') ?>" required>

      <label>Discounted Price (₺)</label>
      <input name="discounted_price" type="number" step="0.01" value="<?= htmlspecialchars($_POST['discounted_price'] ?? '') ?>" required>

      <label>Expiration Date</label>
      <input name="expiration_date" type="date" value="<?= htmlspecialchars($_POST['expiration_date'] ?? '') ?>" required>

      <label>Image</label>
      <input name="image" type="file" accept="image/*" required>

      <button type="submit">Submit</button>
    </form>

    <p style="text-align:center;margin-top:10px;">
      <a href="market_products.php">← Return to Products</a>
    </p>
  </div>
</body>
</html>
