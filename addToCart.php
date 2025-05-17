<?php
// addToCart.php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

validate_csrf();

$userId    = $_SESSION['user_id'] ?? null;
$productId = (int)($_POST['product_id'] ?? 0);

if (!$userId || !$productId) {
    http_response_code(400);
    exit;
}

$pdo = getPDO();

// Insert or increment quantity
$stmt = $pdo->prepare(<<<SQL
  INSERT INTO consumer_cart (user_id, product_id, quantity)
  VALUES (?, ?, 1)
  ON DUPLICATE KEY UPDATE quantity = quantity + 1
SQL
);
$stmt->execute([$userId, $productId]);

// Flash success message
$_SESSION['flash'] = [
    'msg'   => 'Product added to cart!',
    'error' => false
];

// Redirect back to cart view
header('Location: viewCart.php');
exit;
