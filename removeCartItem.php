<?php
// removeCartItem.php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }
validate_csrf();

$userId    = $_SESSION['user_id'];
$productId = (int)($_POST['product_id'] ?? 0);

$pdo = getPDO();
$stmt = $pdo->prepare("
  DELETE FROM consumer_cart
  WHERE user_id = ? AND product_id = ?
");
$stmt->execute([$userId, $productId]);
