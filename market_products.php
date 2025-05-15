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
<html lang="tr">
<head>
  <meta charset="utf-8">
  <title>Market Yönetimi</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <style>
    /* Toast CSS */
    #toast {
      position: fixed; top:20px; right:20px;
      background:#28a745; color:#fff;
      padding:10px 20px; border-radius:4px;
      opacity:0; transition:opacity .3s;
    }
    #toast.show { opacity:1; }
    /* Page CSS */
    body { font-family: Arial, sans-serif; background: #f4f4f4; margin:0; padding:20px; }
    .container { max-width:900px; margin:0 auto; background:#fff; padding:20px; border-radius:8px; }
    .actions { margin-bottom:20px; }
    .actions a {
      margin-right:10px; padding:8px 16px;
      background:#007bff; color:#fff; text-decoration:none; border-radius:4px;
    }
    table { width:100%; border-collapse:collapse; }
    th, td {
      padding:10px; border:1px solid #ddd; text-align:center;
    }
    .table-danger { background:#f8d7da; }
    .btn-sm {
      padding:4px 8px; border:none; color:#fff; border-radius:4px; cursor:pointer;
    }
    .btn-primary { background:#007bff; }
    .btn-danger  { background:#dc3545; }
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
    <h1>Benim Ürünlerim</h1>

    <div class="actions">
      <a href="add_product.php">Yeni Ürün Ekle</a>
      <a href="index.php" style="background:#6c757d;">Ana Sayfa</a>
    </div>

    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Başlık</th>
          <th>Stok</th>
          <th>Fiyat</th>
          <th>İndirim</th>
          <th>Son Kullanma</th>
          <th>İşlemler</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($products)): ?>
          <tr><td colspan="7" class="text-center">Henüz ürün eklenmemiş.</td></tr>
        <?php else: foreach ($products as $p): ?>
          <tr class="<?= $p['is_expired'] ? 'table-danger' : '' ?>">
            <td><?= htmlspecialchars($p['product_id']) ?></td>
            <td><?= htmlspecialchars($p['title']) ?></td>
            <td><?= htmlspecialchars($p['stock']) ?></td>
            <td><?= htmlspecialchars($p['normal_price']) ?>₺</td>
            <td><?= htmlspecialchars($p['discounted_price']) ?>₺</td>
            <td><?= htmlspecialchars($p['expiration_date']) ?></td>
            <td>
              <a href="edit_product.php?id=<?= $p['product_id'] ?>" class="btn-sm btn-primary">Düzenle</a>
              <a href="delete_product.php?id=<?= $p['product_id'] ?>&csrf_token=<?= htmlspecialchars(csrf_token()) ?>"
                 class="btn-sm btn-danger"
                 onclick="return confirm('Bu ürünü silmek istediğinize emin misiniz?')">
                Sil
              </a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

</body>
</html>
