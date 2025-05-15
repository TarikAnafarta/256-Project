<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require_once 'db.php';

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'] ?? 0;

if ($product_id > 0) {
    // Ürünün sepette olup olmadığını kontrol et
    $stmt = $pdo->prepare("SELECT quantity FROM consumer_cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $cartItem = $stmt->fetch();

    if ($cartItem) {
        // Eğer ürün sepette var ise, mevcut miktarı 1 artır
        $newQuantity = $cartItem['quantity'] + 1; // Miktarı 1 artır

        // Miktarı güncelle
        $updateStmt = $pdo->prepare("UPDATE consumer_cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $updateStmt->execute([$newQuantity, $user_id, $product_id]);
    } else {
        // Eğer ürün sepette yoksa, sepete 1 adet ekle
        // (Bu işlem bir ürün sayfasında yapılacaktır ve sepete eklenecek)
        $insertStmt = $pdo->prepare("INSERT INTO consumer_cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
        $insertStmt->execute([$user_id, $product_id, 1]);
    }
}

header("Location: viewCart.php"); // Sepete geri yönlendir
exit;
?>
