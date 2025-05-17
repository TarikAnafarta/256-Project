<?php
// login.php
session_start();
require_once 'db.php';
require_once 'csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();

    $email = trim($_POST['email'] ?? '');
    $pw    = $_POST['password'] ?? '';

    // include registration_status to block unverified consumers
    $stmt = getPDO()->prepare("
        SELECT user_id, user_type, password_hash, registration_status
          FROM users
         WHERE email = ?
    ");
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    if ($u && password_verify($pw, $u['password_hash'])) {
        // block unverified consumers
        if ($u['user_type'] === 'consumer' && $u['registration_status'] !== 'verified') {
            $error = "Please verify your email address first.";
        } else {
            $_SESSION['user_id']   = $u['user_id'];
            $_SESSION['user_type'] = $u['user_type'];
            header('Location: index.php');
            exit;
        }
    } else {
        $error = "Email or password is incorrect..";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Log in</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      margin: 0; padding: 0;
    }
    .container {
      width: 400px;
      margin: 80px auto;
      background-color: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
    }
    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ddd;
      border-radius: 5px;
      box-sizing: border-box;
    }
    .btn {
      background-color: #28a745;
      color: white;
      padding: 10px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
      width: 100%;
      margin-top: 10px;
    }
    .btn:hover {
      background-color: #218838;
    }
    .error {
      background-color: #f8d7da;
      color: #842029;
      padding: 10px;
      border-radius: 5px;
      margin-bottom: 15px;
      text-align: center;
    }
    .text-center {
      text-align: center;
      margin-top: 15px;
    }
    .text-center a {
      color: #28a745;
      text-decoration: none;
    }
    .text-center a:hover {
      text-decoration: underline;
    }
    #toast { 
      position: fixed; top: 20px; right: 20px; 
      background: #28a745; color: white; padding: 10px 20px; 
      border-radius: 4px; opacity: 0; transition: opacity 0.3s;
    }
    #toast.show { opacity: 1; }
  </style>
</head>
<body>
  <div id="toast"></div>
  <div class="container">
    <h2>Log in </h2>

    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

      <label for="email">E-mail</label>
      <input type="email" id="email" name="email"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>

      <button type="submit" class="btn">Log in</button>
    </form>

    <p class="text-center">
      Don't have an account? <a href="register.php">Sign up</a>
    </p>
  </div>
  <script>
    function showToast(msg, isError=false) {
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.style.background = isError ? '#dc3545' : '#28a745';
      t.classList.add('show');
      setTimeout(() => t.classList.remove('show'), 2500);
    }
  </script>
</body>
</html>
