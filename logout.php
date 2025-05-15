<?php
session_start();
session_unset(); // Tüm oturum verilerini siler
session_destroy(); // Oturumu sonlandırır

header('Location: index.php'); // Kullanıcıyı giriş sayfasına yönlendir
exit;
?>
