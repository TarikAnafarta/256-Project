<?php
// delete_product.php

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

// 1) Ensure this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// 2) Validate CSRF token
validate_csrf();

// 3) Check user is a market
if (!isset($_SESSION['user_id'], $_SESSION['user_type']) || $_SESSION['user_type'] !== 'market') {
    header('Location: login.php');
    exit;
}

// 4) Get product_id from POST
$productId = (int)($_POST['product_id'] ?? 0);
if ($productId < 1) {
    die('Geçersiz ürün ID.');
}

$pdo = getPDO();

// 5) Verify ownership: the logged-in market must own this product
$stmt = $pdo->prepare("
    SELECT 1
      FROM products p
      JOIN markets m ON p.market_id = m.market_id
     WHERE p.product_id = ? AND m.user_id = ?
");
$stmt->execute([$productId, $_SESSION['user_id']]);
if (!$stmt->fetchColumn()) {
    die('Ürün bulunamadı veya yetkiniz yok.');
}

// 6) Perform deletion
$del = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
$del->execute([$productId]);

// 7) Flash success and redirect back to CRUD page
$_SESSION['flash'] = [
    'msg'   => 'Ürün silindi.',
    'error' => false
];

header('Location: market_products.php');
exit;
