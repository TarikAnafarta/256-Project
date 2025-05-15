<?php
// verify.php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();

    $email = trim($_POST['email'] ?? '');
    $code  = trim($_POST['code']  ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta girin.';
    }
    if (!preg_match('/^\d{6}$/', $code)) {
        $errors[] = '6 haneli kod girin.';
    }

    if (empty($errors)) {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            SELECT user_id FROM users
             WHERE email = ? AND verification_code = ? AND registration_status = 'unverified'
        ");
        $stmt->execute([$email, $code]);
        $uid = $stmt->fetchColumn();

        if ($uid) {
            // mark verified
            $upd = $pdo->prepare("
                UPDATE users
                   SET registration_status = 'verified',
                       verification_code = ''
                 WHERE user_id = ?
            ");
            $upd->execute([$uid]);
            $success = 'E-posta doğrulandı! Şimdi <a href="login.php">giriş yapabilirsiniz</a>.';
        } else {
            $errors[] = 'Kod eşleşmedi veya zaten doğrulanmış.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>E-posta Doğrulama</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f4f4; }
    .container {
      width: 400px; margin: 50px auto; background: #fff; padding: 20px;
      border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    input, button {
      width: 100%; padding: 10px; margin: 10px 0;
      border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;
    }
    button {
      background: #28a745; color: #fff; border: none; cursor: pointer;
    }
    button:hover { background: #218838; }
    .error { color: #842029; background: #f8d7da; padding: 10px; border-radius: 5px; }
    .success { color: #0f5132; background: #d1e7dd; padding: 10px; border-radius: 5px; }
  </style>
</head>
<body>
  <div class="container">
    <h2>E-posta Doğrulama</h2>

    <?php if ($errors): ?>
      <div class="error">
        <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="success"><?= $success ?></div>
    <?php else: ?>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <label for="email">E-posta</label>
        <input type="email" id="email" name="email" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

        <label for="code">6 Haneli Kod</label>
        <input type="text" id="code" name="code" pattern="\d{6}" required
               value="<?= htmlspecialchars($_POST['code'] ?? '') ?>">

        <button type="submit">Doğrula</button>
      </form>
    <?php endif; ?>

    <p style="text-align:center; margin-top:10px;">
      <a href="login.php">← Giriş Sayfasına Dön</a>
    </p>
  </div>
</body>
</html>
