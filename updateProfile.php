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
    die("Kullanıcı bulunamadı.");
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
        $errors[] = 'Ad Soyad boş bırakılamaz.';
    }
    if ($city === '') {
        $errors[] = 'Lütfen şehir seçin veya girin.';
    }
    if ($district === '') {
        $errors[] = 'İlçe boş bırakılamaz.';
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

        $success = 'Profiliniz güncellendi.';
        // Refresh current values
        $user['full_name'] = $full_name;
        $user['city']      = $city;
        $user['district']  = $district;
    }
}

// List of major cities
$cities = ['İstanbul','Ankara','İzmir','Bursa','Antalya'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Profilimi Düzenle</title>
  <style>
    body { font-family: Arial,sans-serif; background:#f4f4f4; margin:0; padding:0; }
    .container {
      width:400px; margin:50px auto; background:#fff; padding:20px;
      border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.1);
    }
    h2 { text-align:center; margin-bottom:20px; }
    input, select { width:100%; padding:10px; margin:10px 0;
      border:1px solid #ddd; border-radius:5px; box-sizing:border-box; }
    .btn { background:#28a745; color:#fff; padding:10px; border:none;
      border-radius:5px; cursor:pointer; font-size:16px; width:100%; }
    .btn:hover { background:#218838; }
    .error { background:#f8d7da; color:#842029; padding:10px; border-radius:5px; margin-bottom:15px; }
    .message { background:#d1e7dd; color:#0f5132; padding:10px; border-radius:5px; margin-bottom:15px; }
    #city_other_field { display:none; }
   
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
  <h2>Profilimi Düzenle</h2>

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

    <label>Email (değiştirilemez)</label>
    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>

    <label for="full_name">Ad Soyad</label>
    <input id="full_name" name="full_name" type="text"
           value="<?= htmlspecialchars($user['full_name']) ?>" required>

    <label for="city">Şehir</label>
    <select id="city" name="city" required>
      <option value="">Seçiniz…</option>
      <?php foreach ($cities as $c): ?>
        <option value="<?= $c ?>"
          <?= $user['city'] === $c ? 'selected' : '' ?>>
          <?= $c ?>
        </option>
      <?php endforeach; ?>
      <option value="other" <?= !in_array($user['city'], $cities) ? 'selected' : '' ?>>
        Diğer
      </option>
    </select>

    <div id="city_other_field">
      <label for="city_other">Diğer Şehir</label>
      <input id="city_other" name="city_other" type="text"
             value="<?= !in_array($user['city'], $cities) ? htmlspecialchars($user['city']) : '' ?>">
    </div>

    <label for="district">İlçe</label>
    <input id="district" name="district" type="text"
           value="<?= htmlspecialchars($user['district']) ?>" required>

    <button type="submit" class="btn">Güncelle</button>
        <!-- after your Güncelle button, before closing </form> or </div> -->
    </form>

    <!-- BACK BUTTON -->
    <p style="text-align:center; margin-top:15px;">
      <a href="index.php" class="btn" style="background:#6c757d;">
        ← Ana Sayfa
      </a>
    </p>
  </div>

  </form>
</div>

<script>
  // Show/hide the "Other city" field
  const cityEl = document.getElementById('city');
  const otherEl = document.getElementById('city_other_field');
  function toggleOther() {
    otherEl.style.display = cityEl.value === 'other' ? 'block' : 'none';
  }
  cityEl.addEventListener('change', toggleOther);
  window.addEventListener('DOMContentLoaded', toggleOther);
</script>
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
