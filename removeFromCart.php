<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'db.php';

// Kullanıcı bilgisi
$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'] ?? 0;

if ($product_id > 0) {
    // Sepetteki ürünün miktarını kontrol et
    $stmt = $pdo->prepare("SELECT quantity FROM consumer_cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $cartItem = $stmt->fetch();

    if ($cartItem) {
        // Eğer miktar 1'den fazla ise, miktarı 1 azalt
        if ($cartItem['quantity'] > 1) {
            $newQuantity = $cartItem['quantity'] - 1;

            // Yeni miktarı güncelle
            $updateStmt = $pdo->prepare("UPDATE consumer_cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $updateStmt->execute([$newQuantity, $user_id, $product_id]);
        } else {
            // Eğer miktar 1 ise, ürünü tamamen sepetten sil
            $deleteStmt = $pdo->prepare("DELETE FROM consumer_cart WHERE user_id = ? AND product_id = ?");
            $deleteStmt->execute([$user_id, $product_id]);
        }
    }
}

header("Location: viewCart.php"); // Sepete geri yönlendir
exit;
?>
