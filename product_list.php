<?php
// product_list.php

require_once 'config/db.php';
require_once 'includes/functions.php';
requireLogin();

$stmt = $pdo->prepare(
  'SELECT id,title,stock,normal_price,discounted_price,expiration_date,
    (expiration_date < CURDATE()) AS is_expired
   FROM products
   WHERE market_id = ?
   ORDER BY created_at DESC'
);
$stmt->execute([$_SESSION['market_id']]);
$products = $stmt->fetchAll();

include 'views/header.php';
?>
<h2>My Products</h2>
<p>
  <a class="btn btn-success" href="product_add.php">+ Add New</a>
  <a class="btn btn-secondary" href="logout.php">Logout</a>
</p>

<?php if (empty($products)): ?>
  <p>No products yet.</p>
<?php else: ?>
  <table class="table table-bordered">
    <thead><tr>
      <th>Title</th><th>Stock</th><th>Normal</th>
      <th>Discount</th><th>Exp. Date</th><th>Actions</th>
    </tr></thead>
    <tbody>
    <?php foreach ($products as $p): ?>
      <tr class="<?= $p['is_expired'] ? 'table-danger' : '' ?>">
        <td><?= e($p['title']) ?></td>
        <td><?= (int)$p['stock'] ?></td>
        <td><?= number_format($p['normal_price'],2) ?></td>
        <td><?= number_format($p['discounted_price'],2) ?></td>
        <td><?= e($p['expiration_date']) ?></td>
        <td>
          <a class="btn btn-sm btn-primary" 
             href="product_edit.php?id=<?= $p['id'] ?>">Edit</a>
          <a class="btn btn-sm btn-danger" 
             href="product_delete.php?id=<?= $p['id'] ?>"
             onclick="return confirm('Delete?')">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php include 'views/footer.php'; ?>
