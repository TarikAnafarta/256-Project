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
        $errors[] = 'Please enter a valid email.';
    }
    if (!preg_match('/^\d{6}$/', $code)) {
        $errors[] = 'Enter the 6 digit code.';
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
            $success = 'Email verified! You can now <a href="login.php">log in</a>.';
        } else {
            $errors[] = 'The code did not match or has already been verified.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Email Verification</title>
  <style>
    body {
  margin: 0;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: linear-gradient(to right, #11998e, #38ef7d);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

.container {
  width: 420px;
  background: #ffffff;
  padding: 35px;
  border-radius: 16px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
  animation: fadeSlideIn 0.6s ease;
}

@keyframes fadeSlideIn {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

h2 {
  text-align: center;
  margin-bottom: 24px;
  color: #11998e;
}

label {
  font-weight: 600;
  margin-top: 12px;
  display: block;
  font-size: 0.95rem;
}

input[type="email"],
input[type="text"] {
  width: 100%;
  padding: 10px 12px;
  margin-top: 6px;
  border: 1px solid #ccc;
  border-radius: 8px;
  font-size: 0.95rem;
  box-sizing: border-box;
  transition: border-color 0.3s ease;
}

input:focus {
  outline: none;
  border-color: #38ef7d;
}

button {
  margin-top: 20px;
  width: 100%;
  padding: 12px;
  background: linear-gradient(to right, #00b09b, #96c93d);
  color: white;
  font-weight: bold;
  font-size: 1rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.3s ease;
}

button:hover {
  transform: scale(1.03);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

.success,
.error {
  padding: 12px;
  border-radius: 8px;
  font-weight: bold;
  margin-bottom: 16px;
  text-align: center;
}

.success {
  background: #d1e7dd;
  color: #0f5132;
}

.error {
  background: #f8d7da;
  color: #842029;
}

p {
  text-align: center;
  margin-top: 16px;
}

a {
  color: #11998e;
  text-decoration: none;
}

a:hover {
  text-decoration: underline;
}

  </style>
</head>
<body>
  <div class="container">
    <h2>Email Verification</h2>

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

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

        <label for="code">6 Digit Code</label>
        <input type="text" id="code" name="code" pattern="\d{6}" required
               value="<?= htmlspecialchars($_POST['code'] ?? '') ?>">

        <button type="submit">Verify</button>
      </form>
    <?php endif; ?>

    <p style="text-align:center; margin-top:10px;">
      <a href="login.php">← Return to Home Page</a>
    </p>
  </div>
</body>
</html>
