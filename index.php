<?php
// index.php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

if (!isset($_SESSION['user_id'], $_SESSION['user_type'])) {
    header('Location: login.php');
    exit;
}

$pdo = getPDO();

// Only for consumers
if ($_SESSION['user_type'] === 'consumer') {
    // Get location
    $stmt = $pdo->prepare("SELECT city, district FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    [$city, $district] = $stmt->fetch(PDO::FETCH_NUM);

    // Search and pagination
    $q      = trim($_GET['q'] ?? '');
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $per    = 4;
    $offset = ($page - 1) * $per;

    // Build WHERE clause
    $where  = "p.expiration_date > CURDATE() AND u.city = ?";
    $params = [$city];

    if ($q !== '') {
        $where    .= " AND p.title LIKE ?";
        $params[] = "%{$q}%";
    }

    // Count total
    $countSql = "SELECT COUNT(*) FROM products p
                 JOIN markets m ON p.market_id = m.market_id
                 JOIN users u   ON m.user_id    = u.user_id
                 WHERE {$where}";
    $cstm = $pdo->prepare($countSql);
    $cstm->execute($params);
    $total = $cstm->fetchColumn();
    $pages = (int)ceil($total / $per);

    // Fetch page
    $sql = "SELECT p.*
            FROM products p
            JOIN markets m ON p.market_id = m.market_id
            JOIN users u   ON m.user_id    = u.user_id
            WHERE {$where}
            ORDER BY (u.district = ?) DESC, p.expiration_date ASC
            LIMIT {$per} OFFSET {$offset}";
    $params[] = $district;
    $stm = $pdo->prepare($sql);
    $stm->execute($params);
    $products = $stm->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <title>Ana Sayfa</title>
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
    body {
      font-family: Arial, sans-serif;
      background: #f4f4f4;
      margin: 0; padding: 20px;
    }
    .wrapper {
      max-width: 1100px;
      margin: 0 auto;
    }
    .nav {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 20px;
    }
    .nav a {
      padding: 8px 16px;
      background: #007bff;
      color: #fff;
      text-decoration: none;
      border-radius: 4px;
    }
    .nav a.logout { background: #dc3545; }
    .nav a.cart   { background: #28a745; }
    .nav a.profile{ background: #ffc107; color:#333; }

    .search {
      margin-bottom: 20px;
    }
    .search input {
      padding: 8px;
      width: 80%;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .search button {
      padding: 8px 12px;
      border: none;
      background: #007bff;
      color: #fff;
      border-radius: 4px;
      cursor: pointer;
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px,1fr));
      gap: 20px;
    }
    .card {
      background: #fff;
      border-radius: 6px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    .card img {
      width: 100%;
      height: 140px;
      object-fit: cover;
    }
    .card-body {
      flex: 1;
      padding: 12px;
      display: flex;
      flex-direction: column;
    }
    .card-title {
      font-size: 1rem;
      margin-bottom: 8px;
    }
    .card-prices {
      margin-bottom: 12px;
    }
    .old {
      text-decoration: line-through;
      color: #888;
      margin-right: 6px;
    }
    .new {
      color: #e55353;
      font-weight: bold;
    }
    .card-action button {
      margin-top: auto;
      padding: 8px;
      border: none;
      background: #007bff;
      color: #fff;
      border-radius: 4px;
      cursor: pointer;
    }
    .pagination {
      display: flex;
      list-style: none;
      gap: 6px;
      margin-top: 20px;
      justify-content: center;
    }
    .pagination a {
      padding: 6px 12px;
      background: #fff;
      border: 1px solid #ccc;
      border-radius: 4px;
      text-decoration: none;
      color: #333;
    }
    .pagination a.current {
      background: #007bff;
      color: #fff;
      border-color: #007bff;
    }
    @media (max-width: 576px) {
      .grid {
        grid-template-columns: 1fr !important;
      }
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

  <div class="wrapper">
    <div class="nav">
      <a href="logout.php" class="logout">Çıkış Yap</a>
      <?php if ($_SESSION['user_type'] === 'consumer'): ?>
        <a href="viewCart.php" class="cart">Sepete Git</a>
      <?php else: ?>
        <a href="market_products.php">Market Yönetimi</a>
      <?php endif; ?>
      <a href="updateProfile.php" class="profile">Profilimi Düzenle</a>
    </div>

    <?php if ($_SESSION['user_type'] === 'consumer'): ?>
      <form class="search" method="get">
        <input name="q" placeholder="Ürün ara…" value="<?= htmlspecialchars($q) ?>">
        <button type="submit">Ara</button>
      </form>

      <?php if (empty($products)): ?>
        <p>Ürün bulunamadı.</p>
      <?php else: ?>
        <div class="grid">
          <?php foreach ($products as $p): ?>
            <div class="card">
              <img src="img/<?= htmlspecialchars($p['image']) ?>" alt="">
              <div class="card-body">
                <div class="card-title"><?= htmlspecialchars($p['title']) ?></div>
                <div class="card-prices">
                  <span class="old"><?= htmlspecialchars($p['normal_price']) ?>₺</span>
                  <span class="new"><?= htmlspecialchars($p['discounted_price']) ?>₺</span>
                </div>
                <form method="POST" action="addToCart.php" class="card-action">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                  <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
                  <button>Sepete Ekle</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if ($pages > 1): ?>
          <ul class="pagination">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
              <li>
                <a href="?q=<?= urlencode($q) ?>&page=<?= $i ?>"
                   class="<?= $i === $page ? 'current' : '' ?>">
                  <?= $i ?>
                </a>
              </li>
            <?php endfor; ?>
          </ul>
        <?php endif; ?>
      <?php endif; ?>

    <?php else: ?>
      <p>Hoş geldiniz, market yetkilisi! Ürünlerinizi yönetmek için üstteki düğmeye tıklayın.</p>
    <?php endif; ?>
  </div>
</body>
</html>
