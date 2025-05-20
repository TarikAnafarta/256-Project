<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

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
             LEFT JOIN markets m ON p.market_id = m.market_id
             LEFT JOIN users u   ON m.user_id = u.user_id
             WHERE {$where}";
    $cstm = $pdo->prepare($countSql);
    $cstm->execute($params);
    $total = $cstm->fetchColumn();
    $pages = (int)ceil($total / $per);

    // Fetch page
    $sql = "SELECT p.*
        FROM products p
        LEFT JOIN markets m ON p.market_id = m.market_id
        LEFT JOIN users u   ON m.user_id    = u.user_id
        WHERE {$where}
        ORDER BY (u.district = ? OR u.district IS NULL) DESC, p.expiration_date ASC
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
  <title>Main Page</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <style>
    #toast {
      position: fixed; top:20px; right:20px;
      background:#28a745; color:#fff;
      padding:10px 20px; border-radius:4px;
      opacity:0; transition:opacity .3s;
    }
    #toast.show { opacity:1; }

    body {
      font-family: Arial, sans-serif;
      background: #a3d5ff;
      margin: 0; padding: 20px;
    }
    .wrapper {
      max-width: max-content;
      margin: 0 auto;
    }
    .header {
       border: 1.5px solid black;
      background-color: #fff;
      padding: 15px 50px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .market-name {
      font-size: 1.5rem;
      font-weight: bold;
      color: #333;
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
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .nav a.logout { background: #dc3545;border: 1.5px solid black; }
    .nav a.cart   { background: #28a745; border: 1.5px solid black;}
    .nav a.profile{ background: #ffc107; color:#333;border: 1.5px solid black; }

    .search {
       
      margin: 0 auto 30px auto;
      text-align: center;
    }
    .search input {
      border: 1.5px solid black;
  border-radius: 6px;     
      padding: 8px;
  width: 60%;
  max-width: 500px;
  box-sizing: border-box;
    }
    .search button {
      border: 1.5px solid black;
      padding: 8px 12px;
      border: none;
      background: #007bff;
      color: #fff;
      border-radius: 4px;
      cursor: pointer;
      font-size: 1rem;
    }

    .grid {
      
      display: flex;
      flex-wrap: wrap;
      gap: 80px;
      justify-content: center;
    }
    
    .card {
      border: 1.5px solid black;
      width: 220px;
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
body {
  margin: 0;
  height: 100vh;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: linear-gradient(to right, #f0f2f5, #e2e8f0);
  color: #333;
  background-image: url(./img/bg.jpg);
  background-size: cover;
  background-position: center;
  display: flex;
  align-items: center;
  justify-content: center;
}

.wrapper {
  background: rgba(255, 255, 255, 0.95);
  padding: 40px;
  border-radius: 20px;
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
}

.nav {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 25px;
}

.nav a {
  text-decoration: none;
  padding: 20px 40px;
  border-radius: 12px;
  font-weight: 700;
  font-size: 1.2rem;
  transition: all 0.3s ease;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
  width: 250px;
  text-align: center;
  letter-spacing: 0.5px;
}

.nav a.logout {
  background-color: #ff6b6b;
  color: white;
}

.nav a.logout:hover {
  background-color: #e63946;
  transform: scale(1.08);
}

.nav a.cart {
  background-color: #48cae4;
  color: white;
}

.nav a.cart:hover {
  background-color: #0096c7;
  transform: scale(1.08);
}

.nav a.profile {
  background-color: #90be6d;
  color: white;
}

.nav a.profile:hover {
  background-color: #588157;
  transform: scale(1.08);
}

.nav a:not(.logout):not(.cart):not(.profile) {
  background-color: #f4a261;
  color: white;
}

.nav a:not(.logout):not(.cart):not(.profile):hover {
  background-color: #e76f51;
  transform: scale(1.08);
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
      <a href="logout.php" class="logout">Logout</a>
      <?php if ($_SESSION['user_type'] === 'consumer'): ?>
        <a href="viewCart.php" class="cart">Go to Cart</a>
      <?php else: ?>
        <a href="market_products.php">Market Management</a>
      <?php endif; ?>
      <a href="updateProfile.php" class="profile">Edit My Profile</a>
    </div>

    <?php if ($_SESSION['user_type'] === 'consumer'): ?>
      <form class="search" method="get">
        <input name="q" placeholder="Search product…" value="<?= htmlspecialchars($q) ?>">
        <button type="submit">Search</button>
      </form>

      <?php if (empty($products)): ?>
        <p>Product not found.</p>
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
                  <button>Add To Cart</button>
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
    <?php endif; ?>
  </div>
</body>
</html>
