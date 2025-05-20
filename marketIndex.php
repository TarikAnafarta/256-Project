<?php
session_start();

// Giriş yapmamış kullanıcıları market sayfasına yönlendirme
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php'); // Giriş yapmamışsa login sayfasına yönlendir
    exit;
}

require_once 'db.php'; // Veritabanı bağlantısı

// Ürünleri çekme
$productsQuery = "SELECT * FROM products";
$products = $pdo->query($productsQuery)->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <style>
  #toast { 
    position: fixed; top: 20px; right: 20px; 
    background: #28a745; color: white; padding: 10px 20px; 
    border-radius: 4px; opacity: 0; transition: opacity 0.3s;
  }
  #toast.show { opacity: 1; }
</style>

    <meta charset="UTF-8">
    <title>Market Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            padding: 20px;
        }
        .product {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: 200px;
            margin: 10px;
            padding: 10px;
            text-align: center;
        }
        .product img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        .product h2 {
            font-size: 18px;
            color: #333;
        }
        .product p {
            font-size: 16px;
            color: #777;
        }
        .add-to-cart {
            background-color: #28a745;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .add-to-cart:hover {
            background-color: #218838;
        }
        .message {
            text-align: center;
            padding: 10px;
            color: green;
            font-weight: bold;
        }
        .logout-btn {
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
            display: block;
            text-align: center;
            width: 200px;
            margin-left: auto;
            margin-right: auto;
        }
        .logout-btn:hover {
            background-color: #c82333;
        }
        .go-to-cart-btn {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
            display: block;
            text-align: center;
            width: 200px;
            margin-left: auto;
            margin-right: auto;
        }
        .go-to-cart-btn:hover {
            background-color: #0056b3;
        }
        .update-profile-btn {
            background-color: #ffc107;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
            display: block;
            text-align: center;
            width: 200px;
            margin-left: auto;
            margin-right: auto;
        }
        .update-profile-btn:hover {
            background-color: #e0a800;
        }
    </style>
</head>
<body>
 <div id="toast"></div>
<header>
    <h1>Market Products</h1>
</header>

<!-- Çıkış yap butonu -->
<form method="POST" action="logout.php">
    <button type="submit" class="logout-btn">Log out</button>
</form>

<!-- Sepete Git butonu -->
<form method="GET" action="viewCart.php">
    <button type="submit" class="go-to-cart-btn">Go to Cart</button>
</form>

<!-- Kullanıcı bilgilerini güncelle butonu -->
<form method="GET" action="updateProfile.php">
    <button type="submit" class="update-profile-btn">Update User Information</button>
</form>

<div class="container">
    <?php foreach ($products as $product): ?>
        <div class="product">
            <img src="img/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['title']) ?>">
            <h2><?= htmlspecialchars($product['title']) ?></h2>
            <p>Price: <?= $product['discounted_price'] ?>₺</p>
            <form method="POST" action="addToCart.php">
                <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                <button type="submit" class="add-to-cart">Add to Cart</button>
            </form>
        </div>
    <?php endforeach; ?>
</div>
<script>
  function showToast(msg, isError=false) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = isError ? '#dc3545' : '#28a745';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2500);
  }
</script>

</body>
</html>
