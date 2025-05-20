<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

<?php
// viewCart.php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

$pdo = getPDO();
$stmt = $pdo->prepare(<<<SQL
  SELECT cc.product_id, cc.quantity,
         p.title, p.discounted_price, p.stock, p.image
  FROM consumer_cart cc
  JOIN products p ON cc.product_id = p.product_id
  WHERE cc.user_id = ?
SQL
);
$stmt->execute([$userId]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <title>Sepetim</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <style>
    /* Toast CSS */
    #toast {
      position: fixed;
      top: 20px;
      right: 20px;
      background: #28a745;
      color: #fff;
      padding: 10px 20px;
      border-radius: 4px;
      opacity: 0;
      transition: opacity 0.3s;
    }
    #toast.show {
      opacity: 1;
    }
    /* Page CSS */
    body {
      font-family: Arial, sans-serif;
       background-image: url("img/Supermarkt.jpg");
      background-size: cover;          /* Resmi tamamen kapla */
      background-repeat: no-repeat;    /* Tekrarlamasın */
      background-position: center;     /* Ortala */
      margin: 0;
      padding: 20px;
    }
    .container {
      max-width: 800px;
      margin: 0 auto;
      background: #fff;
      padding: 20px;
      border-radius: 8px;
    }
    .actions {
      display: flex;
      justify-content: space-between;
      margin-bottom: 20px;
    }
    .actions a,
    .actions button {
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      color: #fff;
      text-decoration: none;
      cursor: pointer;
    }
    .actions a {
      background: #007bff;
    }
    .actions a:hover {
      background: #0056b3;
    }
    .actions .purchase {
      background: #28a745;
    }
    .actions .purchase:hover {
      background: #218838;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }
    th, td {
      padding: 10px;
      border-bottom: 1px solid #ddd;
      text-align: center;
    }
    img {
      width: 50px;
    }
    input.qty {
      width: 50px;
      text-align: center;
    }
    button.delete {
      background: #dc3545;
      color: #fff;
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    button.delete:hover {
      background: #c82333;
    }
    .total {
      text-align: right;
      font-size: 1.2rem;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>

  <!-- Toast container & flash-to-JS -->
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
    <h1>My Cart<i style="font-size:36px" class="fa">&#xf291;</i></h1>

    <div class="actions">
      <a href="index.php">Return to Products</a>
      <button class="purchase">Buy</button>
    </div>

    <?php if (empty($items)): ?>
      <p>Cart is empty.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Image</th>
            <th>Product</th>
            <th>Price</th>
            <th>Count</th>
            <th>Total</th>
            <th>Delete</th>
          </tr>
        </thead>
        <tbody id="cart-body">
          <?php foreach ($items as $it): ?>
            <tr data-id="<?= $it['product_id'] ?>">
              <td><img src="img/<?= htmlspecialchars($it['image']) ?>" alt=""></td>
              <td><?= htmlspecialchars($it['title']) ?></td>
              <td><?= htmlspecialchars($it['discounted_price']) ?>₺</td>
              <td>
                <input type="number" class="qty" min="1" max="<?= $it['stock'] ?>"
                       value="<?= $it['quantity'] ?>">
              </td>
              <td class="line-total"><?= $it['quantity'] * $it['discounted_price'] ?>₺</td>
              <td><button class="delete">X</button></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="total">Grand Total: <span id="grand-total">0₺</span></div>
    <?php endif; ?>
  </div>

  <script>
    const csrf = '<?= htmlspecialchars(csrf_token()) ?>';

    function recalc() {
      let grand = 0;
      document.querySelectorAll('#cart-body tr').forEach(row => {
        const qty = +row.querySelector('.qty').value;
        const price = parseFloat(row.querySelector('td:nth-child(3)').textContent);
        const line = qty * price;
        row.querySelector('.line-total').textContent = line + '₺';
        grand += line;
      });
      document.getElementById('grand-total').textContent = grand + '₺';
    }
    recalc();

    document.querySelectorAll('.qty').forEach(input => {
      input.addEventListener('change', () => {
        const row = input.closest('tr');
        const pid = row.dataset.id;
        const qty = input.value;
        fetch('updateCart.php', {
          method: 'POST',
          headers: {'Content-Type':'application/x-www-form-urlencoded'},
          body: `csrf_token=${csrf}&product_id=${pid}&quantity=${qty}`
        }).then(recalc);
      });
    });

    document.querySelectorAll('.delete').forEach(btn => {
      btn.addEventListener('click', () => {
        const row = btn.closest('tr');
        const pid = row.dataset.id;
        fetch('removeCartItem.php', {
          method: 'POST',
          headers: {'Content-Type':'application/x-www-form-urlencoded'},
          body: `csrf_token=${csrf}&product_id=${pid}`
        }).then(() => {
          row.remove();
          recalc();
        });
      });
    });

    document.querySelector('.purchase').addEventListener('click', () => {
      if (!confirm('Do you confirm purchase?')) return;
      fetch('purchaseCart.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `csrf_token=${csrf}`
      })
      .then(res => res.text())
      .then(msg => {
        showToast(msg, false);
        setTimeout(() => window.location.href = 'index.php', 1500);
      });
    });
  </script>
</body>
</html>
