<?php
// purchaseCart.php

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

validate_csrf();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "You must log in first.";
    exit;
}

$userId = $_SESSION['user_id'];
$pdo    = getPDO();

try {
    // 1) Start transaction
    $pdo->beginTransaction();

    // 2) Fetch cart items + stock
    $stmt = $pdo->prepare(<<<SQL
        SELECT cc.product_id, cc.quantity AS want, p.stock, p.title
          FROM consumer_cart cc
          JOIN products p ON cc.product_id = p.product_id
         WHERE cc.user_id = ?
         FOR UPDATE
    SQL
    );
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        throw new Exception("Your cart is empty.");
    }

    // 3) Validate stock
    foreach ($items as $it) {
        if ($it['want'] > $it['stock']) {
            throw new Exception("Insufficient stock: Only {$it['stock']} left for {$it['title']}.");
        }
    }

    // 4) Subtract stock or delete product
    $updStmt = $pdo->prepare("UPDATE products SET stock = ? WHERE product_id = ?");
    $delStmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
    foreach ($items as $it) {
        $newStock = $it['stock'] - $it['want'];
        if ($newStock > 0) {
            $updStmt->execute([$newStock, $it['product_id']]);
        } else {
            // exactly zero or negative (we know want ≤ stock)
            $delStmt->execute([$it['product_id']]);
        }
    }

    // 5) Clear the cart
    $pdo->prepare("DELETE FROM consumer_cart WHERE user_id = ?")
        ->execute([$userId]);

    // 6) Commit
    $pdo->commit();

    // 7) Respond
    echo "Purchase successful!";

} catch (Exception $e) {
    // Roll back on any error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo $e->getMessage();
}
