<?php
// purchaseCart.php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }
validate_csrf();

$userId = $_SESSION['user_id'];
$pdo = getPDO();
$pdo->beginTransaction();

// 1) Get cart items
$stmt = $pdo->prepare("SELECT product_id, quantity FROM consumer_cart WHERE user_id = ?");
$stmt->execute([$userId]);
$items = $stmt->fetchAll();

// 2) Deduct stock
$upd = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");
foreach ($items as $it) {
    $upd->execute([$it['quantity'], $it['product_id']]);
}

// 3) Clear cart
$pdo->prepare("DELETE FROM consumer_cart WHERE user_id = ?")->execute([$userId]);

$pdo->commit();
echo 'Satın alma başarılı! Sepetiniz temizlendi.';
