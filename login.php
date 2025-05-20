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
  margin: 0;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: linear-gradient(to right, #667eea, #764ba2);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background-image: url('img/login-bg.jpg');
  background-size: cover;
  background-position: center;
  backdrop-filter: blur(3px);
}

.container {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 16px;
  padding: 40px;
  width: 350px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.2);
  backdrop-filter: blur(12px);
  color: black;
  animation: fadeIn 0.7s ease forwards;
  opacity: 0;
}

@keyframes fadeIn {
  to { opacity: 1; transform: translateY(0); }
  from { opacity: 0; transform: translateY(20px); }
}

h2 {
  text-align: center;
  margin-bottom: 24px;
  font-size: 1.8rem;
  font-weight: bold;
}

label {
  font-size: 0.9rem;
  display: block;
  margin-top: 10px;
  margin-bottom: 4px;
  color: black;
}

input[type="email"],
input[type="password"] {
  width: 100%;
  padding: 10px 12px;
  border: none;
  border-radius: 8px;
  margin-bottom: 16px;
  background-color: rgba(255,255,255,0.15);
  color: #fff;
  transition: background 0.3s ease;
}

input[type="email"]:focus,
input[type="password"]:focus {
  outline: none;
  background-color: rgba(255,255,255,0.25);
}

::placeholder {
  color: #ddd;
}

.btn {
  width: 100%;
  padding: 10px;
  border: none;
  border-radius: 8px;
  background: linear-gradient(to right, #43e97b, #38f9d7);
  color: #000;
  font-weight: bold;
  cursor: pointer;
  font-size: 1rem;
  transition: transform 0.2s ease, box-shadow 0.3s ease;
}

.btn:hover {
  transform: scale(1.03);
  box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.error {
  background-color: rgba(255, 0, 0, 0.25);
  color: red;
  padding: 10px;
  border-radius: 8px;
  text-align: center;
  margin-bottom: 15px;
}

.text-center {
  text-align: center;
  margin-top: 20px;
  font-size: 0.9rem;
}

.text-center a {
  color: #00eaff;
  text-decoration: none;
}

.text-center a:hover {
  text-decoration: underline;
}

#toast {
  position: fixed;
  top: 20px;
  right: 20px;
  background: #00c851;
  color: white;
  padding: 10px 20px;
  border-radius: 6px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.2);
  opacity: 0;
  transition: opacity 0.3s ease;
}
#toast.show {
  opacity: 1;
}

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
