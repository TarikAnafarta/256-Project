<?php
// product_delete.php

require_once 'config/db.php';
require_once 'includes/functions.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);

// Delete product
$stmt = $pdo->prepare('DELETE FROM products WHERE id = ? AND market_id = ?');
$stmt->execute([$id, $_SESSION['market_id']]);

header('Location: product_list.php');
exit;
