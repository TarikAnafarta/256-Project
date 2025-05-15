<?php
// updateCart.php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }
validate_csrf();

$userId    = $_SESSION['user_id'];
$productId = (int)($_POST['product_id'] ?? 0);
$qty       = max(1, (int)($_POST['quantity'] ?? 1));

$pdo = getPDO();
$stmt = $pdo->prepare("
  UPDATE consumer_cart
     SET quantity = ?
   WHERE user_id = ? AND product_id = ?
");
$stmt->execute([$qty, $userId, $productId]);
