<?php
// updateProfile.php
session_start();
require_once 'db.php';
require_once 'csrf.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getPDO();
$uid = $_SESSION['user_id'];

// Fetch current data
$stmt = $pdo->prepare("SELECT email, full_name, city, district, user_type FROM users WHERE user_id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();

    $full_name = trim($_POST['full_name'] ?? '');
    $city_sel  = $_POST['city'] ?? '';
    $city      = $city_sel === 'other' 
                 ? trim($_POST['city_other'] ?? '') 
                 : $city_sel;
    $district  = trim($_POST['district'] ?? '');

    // Validation
    if ($full_name === '') {
        $errors[] = 'Name Surname cannot be left blank.';
    }
    if ($city === '') {
        $errors[] = 'Please select or enter a city.';
    }
    if ($district === '') {
        $errors[] = 'District cannot be left blank..';
    }

    if (!$errors) {
        // Update users table
        $upd = $pdo->prepare(<<<SQL
          UPDATE users 
             SET full_name = ?, city = ?, district = ?
           WHERE user_id = ?
        SQL
        );
        $upd->execute([$full_name, $city, $district, $uid]);

        // If market, also update markets.market_name
        if ($user['user_type'] === 'market') {
            $upd2 = $pdo->prepare(
                "UPDATE markets SET market_name = ? WHERE user_id = ?"
            );
            $upd2->execute([$full_name, $uid]);
        }

        $success = 'Your profile has been updated.';
        // Refresh current values
        $user['full_name'] = $full_name;
        $user['city']      = $city;
        $user['district']  = $district;
    }
}

// List of major cities
$cities = ['İstanbul','Ankara','İzmir','Bursa','Antalya'];
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit My Profile</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(to right, #e0f7fa, #fce4ec);
      margin: 0;
      padding: 0;
    }

    .container {
      width: 420px;
      margin: 60px auto;
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }

    h2 {
      text-align: center;
      margin-bottom: 25px;
      color: #333;
      font-size: 1.8rem;
    }

    input, select {
      width: 100%;
      padding: 12px;
      margin: 12px 0;
      border: 1px solid #ccc;
      border-radius: 6px;
      box-sizing: border-box;
      font-size: 15px;
    }

    input:focus, select:focus {
      border-color: #007bff;
      outline: none;
      box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
    }

    .btn {
      background: linear-gradient(to right, #28a745, #3ddc84);
      color: #fff;
      padding: 12px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 16px;
      font-weight: bold;
      margin-top: 15px;
      transition: background 0.3s, transform 0.2s;
      width: 400px;
    }

    .btn:hover {
      background: #218838;
      transform: scale(1.03);
    }

    .btn.back {
      background: #6c757d;
    }

    .btn.back:hover {
      background: #495057;
    }

    .error, .message {
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 15px;
      font-size: 14px;
    }

    .error {
      background: #f8d7da;
      color: #842029;
      border-left: 5px solid #dc3545;
    }

    .message {
      background: #d1e7dd;
      color: #0f5132;
      border-left: 5px solid #198754;
    }

    #city_other_field {
      display: none;
    }

    #toast {
      position: fixed;
      top: 20px;
      right: 20px;
      background: #28a745;
      color: white;
      padding: 12px 24px;
      border-radius: 6px;
      opacity: 0;
      transition: opacity 0.3s ease;
      font-weight: bold;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
      z-index: 9999;
    }

    #toast.show {
      opacity: 1;
    }

    p {
      text-align: center;
      margin-top: 20px;
    }

    p a {
      display: inline-block;
      padding: 10px 20px;
      background: #6c757d;
      color: white;
      border-radius: 6px;
      text-decoration: none;
      font-weight: bold;
      transition: background 0.3s;
    }

    p a:hover {
      background: #495057;
    }

    form{
      width: 400px;
    }

  </style>
</head>
<body>

<div id="toast"></div>

<div class="container">
  <h2>Edit My Profile</h2>

  <?php if ($success): ?>
    <div class="message"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="error">
      <?php foreach ($errors as $e): ?>
        <div><?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

    <label>Email (cannot be changed)</label>
    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>

    <label for="full_name">Name Surname</label>
    <input id="full_name" name="full_name" type="text"
           value="<?= htmlspecialchars($user['full_name']) ?>" required>

    <label for="city">City</label>
    <select id="city" name="city" required>
      <option value="">Select…</option>
      <?php foreach ($cities as $c): ?>
        <option value="<?= $c ?>" <?= $user['city'] === $c ? 'selected' : '' ?>>
          <?= $c ?>
        </option>
      <?php endforeach; ?>
      <option value="other" <?= !in_array($user['city'], $cities) ? 'selected' : '' ?>>
        Other
      </option>
    </select>

    <div id="city_other_field">
      <label for="city_other">Other City</label>
      <input id="city_other" name="city_other" type="text"
             value="<?= !in_array($user['city'], $cities) ? htmlspecialchars($user['city']) : '' ?>">
    </div>

    <label for="district">District</label>
    <input id="district" name="district" type="text"
           value="<?= htmlspecialchars($user['district']) ?>" required>

    <button type="submit" class="btn">Update</button>
    </form>
    <p style="text-align:center; margin-top:15px;">
      <a href="index.php" class="btn" style="background:#6c757d;">
        ← Home Page
      </a>
    </p>
  </div>

  </form>
</div>

<script>
  const cityEl = document.getElementById('city');
  const otherEl = document.getElementById('city_other_field');

  function toggleOther() {
    otherEl.style.display = cityEl.value === 'other' ? 'block' : 'none';
  }

  cityEl.addEventListener('change', toggleOther);
  window.addEventListener('DOMContentLoaded', toggleOther);

  function showToast(msg, isError = false) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = isError ? '#dc3545' : '#28a745';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2500);
  }
</script>

</body>
</html>
