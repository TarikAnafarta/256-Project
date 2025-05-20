<?php
// market_products.php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

if (!isset($_SESSION['user_id'], $_SESSION['user_type']) || $_SESSION['user_type'] !== 'market') {
    header('Location: login.php');
    exit;
}

$pdo = getPDO();

// Find this market’s ID
$stmt = $pdo->prepare('SELECT market_id FROM markets WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$mid = $stmt->fetchColumn();

if (!$mid) {
    die('Market profile not found.');
}

// Fetch products, marking expired
$stmt = $pdo->prepare(<<<SQL
  SELECT *, (expiration_date < CURDATE()) AS is_expired
  FROM products
  WHERE market_id = ?
  ORDER BY expiration_date ASC
SQL
);
$stmt->execute([$mid]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Market Management</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <style>
    #toast {
      position: fixed;
      top: 20px;
      right: 20px;
      background: #28a745;
      color: #fff;
      padding: 12px 24px;
      border-radius: 6px;
      opacity: 0;
      transition: opacity 0.3s ease-in-out;
      font-weight: bold;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    #toast.show {
      opacity: 1;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(to right, #e3f2fd, #fce4ec);
      margin: 0;
      padding: 20px;
    }

    .container {
      max-width: 1000px;
      margin: 0 auto;
      background: #ffffff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
    }

    h1 {
      text-align: center;
      color: #333;
      margin-bottom: 30px;
    }

    .actions {
      display: flex;
      justify-content: center;
      gap: 15px;
      margin-bottom: 25px;
    }

    .actions a {
      background: #007bff;
      color: white;
      padding: 12px 24px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: bold;
      transition: background 0.3s, transform 0.2s;
    }

    .actions a:hover {
      background: #0056b3;
      transform: scale(1.05);
    }

    .actions a.back {
      background: #6c757d;
    }

    .actions a.back:hover {
      background: #495057;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    th, td {
      padding: 12px;
      border: 1px solid #ddd;
      text-align: center;
      font-size: 15px;
    }

    th {
      background-color: #f1f1f1;
      font-weight: bold;
      color: #444;
    }

    .table-danger {
      background-color: #ffe6e6;
    }

    .btn-sm {
      padding: 6px 12px;
      border: none;
      color: #fff;
      border-radius: 6px;
      cursor: pointer;
      text-decoration: none;
      font-size: 14px;
    }

    .btn-primary {
      background-color: #007bff;
    }

    .btn-primary:hover {
      background-color: #0056b3;
    }

    .btn-danger {
      background-color: #dc3545;
    }

    .btn-danger:hover {
      background-color: #b02a37;
    }

    .text-center {
      text-align: center;
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
    function showToast(msg, isError) {
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.style.background = isError ? '#dc3545' : '#28a745';
      t.classList.add('show');
      setTimeout(() => t.classList.remove('show'), 2500);
    }
    showToast(<?= json_encode($f['msg']) ?>, <?= $f['error'] ? 'true' : 'false' ?>);
  </script>
  <?php endif; ?>

  <div class="container">
    <h1>My Products</h1>

    <div class="actions">
      <a href="add_product.php">Add New Product</a>
      <a href="index.php" style="background:#6c757d;">Home Page</a>
    </div>

    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Title</th>
          <th>Stock</th>
          <th>Price</th>
          <th>Discount</th>
          <th>Expiration Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($products)): ?>
          <tr><td colspan="7" class="text-center">No products added yet.</td></tr>
        <?php else: foreach ($products as $p): ?>
          <tr class="<?= $p['is_expired'] ? 'table-danger' : '' ?>">
            <td><?= htmlspecialchars($p['product_id']) ?></td>
            <td><?= htmlspecialchars($p['title']) ?></td>
            <td><?= htmlspecialchars($p['stock']) ?></td>
            <td><?= htmlspecialchars($p['normal_price']) ?>₺</td>
            <td><?= htmlspecialchars($p['discounted_price']) ?>₺</td>
            <td><?= htmlspecialchars($p['expiration_date']) ?></td>
            <td>
              <a href="edit_product.php?id=<?= $p['product_id'] ?>" class="btn-sm btn-primary">Edit</a>
              <form method="POST"
                  action="delete_product.php"
                  style="display:inline"
                  onsubmit="return confirm('Are you sure you want to delete this product?')">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
              <input type="hidden" name="product_id"   value="<?= (int)$p['product_id'] ?>">
              <button type="submit" class="btn-sm btn-danger">Delete</button>
            </form>

            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

</body>
</html>
