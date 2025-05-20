<?php
  use PHPMailer\PHPMailer\PHPMailer;
  use PHPMailer\PHPMailer\Exception;
   require_once __DIR__ . '/vendor/autoload.php';
   require_once 'db.php';
  require_once 'csrf.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


$pdo = getPDO();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();

    $email     = trim($_POST['email']      ?? '');
    $password  = $_POST['password']        ?? '';
    $full_name = trim($_POST['full_name']  ?? '');
    $city      = $_POST['city']            ?? '';
    // if 'other', override with manual
    if ($city === 'other') {
        $city = trim($_POST['city_other'] ?? '');
    }
    $district  = trim($_POST['district']    ?? '');
    $user_type = $_POST['user_type']       ?? '';

    // Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($full_name === '') {
        $errors[] = 'Name Surname cannot be left blank.';
    }
    if ($city === '') {
        $errors[] = 'Please select or enter a city.';
    }
    if ($district === '') {
        $errors[] = 'District cannot be left blank.';
    }
    if (!in_array($user_type, ['consumer','market'], true)) {
        $errors[] = 'Please select your user type.';
    }

    // E-posta kontrolü
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'This email is already registered.';
        }
    }

    // Kayıt
    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $code = random_int(100000,999999);
        // auto-verify markets
        $status = 'unverified';

        $stmt = $pdo->prepare(<<<SQL
            INSERT INTO users
              (email, password_hash, full_name, city, district, user_type, registration_status, verification_code)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        SQL
        );
        $stmt->execute([
            $email, $hash, $full_name,
            $city, $district, $user_type, $status, $code
        ]);

        // if market, insert into markets table
        if ($user_type === 'market') {
            $newId = $pdo->lastInsertId();
            $stmt2 = $pdo->prepare(
                "INSERT INTO markets (user_id, market_name) VALUES (?, ?)"
            );
            $stmt2->execute([$newId, $full_name]);
        }
      
        try {         
          $mail = new PHPMailer(true);
          $mail->isSMTP();
          $mail->Host = 'smtp.gmail.com';
          $mail->SMTPAuth   = true;          
          $mail->SMTPSecure = 'tls';                         
          $mail->Username   =  '256marketsystem@gmail.com';                                       
          $mail->Password   =  '' ;                     
          $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
          $mail->Port       = 587;  
          $mail->setFrom("256marketsystem@gmail.com", "Market System");
          $mail->addAddress($email, $full_name);  
          $mail->isHTML(true);
          $mail->Subject = 'Verification Code';
          $mail->Body    = $mail->Body = "
          <html>
          <head>
              <style>
                  .container {
                      font-family: Arial, sans-serif;
                      background-color: #f4f7fc;
                      padding: 30px;
                      border-radius: 10px;
                      max-width: 600px;
                      margin: auto;
                      border: 1px solid #ddd;
                  }
                  .code-box {
                      background-color: #ffffff;
                      padding: 15px 25px;
                      border: 2px dashed #4CAF50;
                      font-size: 24px;
                      font-weight: bold;
                      color: #4CAF50;
                      text-align: center;
                      border-radius: 8px;
                      margin: 20px 0;
                  }
                  .footer {
                      font-size: 12px;
                      color: #999999;
                      margin-top: 30px;
                      text-align: center;
                  }
              </style>
          </head>
          <body>
              <div class='container'>
                  <h2>Hello, {$full_name}</h2>
                  <p>Please use the verification code below to complete your registration:</p>
                  <div class='code-box'>{$code}</div>
                  <p>This code is valid for <strong>10 minutes</strong>.</p>
              </div>
          </body>
          </html>";
           
          $mail->send();
        } catch (Exception $e) {
            // if sending fails, you can log $mail->ErrorInfo
        }
    

        $success = 'Registration successful! Verification code has been sent via email.';
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
  background: linear-gradient(to right, #00b4db, #0083b0);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}

.container {
  width: 420px;
  background: #ffffff;
  color: #333;
  padding: 35px;
  border-radius: 16px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
  animation: slideIn 0.6s ease;
}

@keyframes slideIn {
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
  color: #007bff;
}

label {
  font-weight: 600;
  margin-top: 12px;
  display: block;
  font-size: 0.95rem;
}

input,
select {
  width: 100%;
  padding: 10px 12px;
  margin-top: 6px;
  border: 1px solid #ccc;
  border-radius: 8px;
  font-size: 0.95rem;
  box-sizing: border-box;
  transition: border-color 0.3s ease;
}

input:focus,
select:focus {
  outline: none;
  border-color: #007bff;
}

.btn {
  margin-top: 20px;
  padding: 12px;
  width: 100%;
  border: none;
  background: linear-gradient(to right, #00c6ff, #0072ff);
  color: white;
  font-size: 1rem;
  font-weight: bold;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.25s ease;
}

.btn:hover {
  transform: scale(1.03);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

.message,
.error {
  margin-bottom: 16px;
  text-align: center;
  font-weight: bold;
  padding: 10px;
  border-radius: 8px;
}

.message {
  background: #d4edda;
  color: #155724;
}

.error {
  background: #f8d7da;
  color: #721c24;
}

#city_other_field {
  display: none;
}

p {
  text-align: center;
  margin-top: 16px;
  font-size: 0.9rem;
}

a {
  color: #007bff;
  text-decoration: none;
}

a:hover {
  text-decoration: underline;
}

#toast {
  position: fixed;
  top: 20px;
  right: 20px;
  background: #28a745;
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
  <h2>Sign Up</h2>

  <?php if ($success): ?>
    <div class="message"><?= htmlspecialchars($success) ?></div>
    <p style="text-align:center;"><a href="verify.php">Go to Verification Page</a></p>
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

    <label for="email">Email:</label>
    <input type="email" id="email" name="email"
           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required>

    <label for="full_name">Name Surname:</label>
    <input type="text" id="full_name" name="full_name"
           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>

    <label for="city">City:</label>
    <select id="city" name="city" required>
      <option value="">Select…</option>
      <option value="İstanbul" <?= (($_POST['city'] ?? '')==='İstanbul')?'selected':'' ?>>İstanbul</option>
      <option value="Ankara"    <?= (($_POST['city'] ?? '')==='Ankara')   ?'selected':'' ?>>Ankara</option>
      <option value="İzmir"     <?= (($_POST['city'] ?? '')==='İzmir')    ?'selected':'' ?>>İzmir</option>
      <option value="Bursa"     <?= (($_POST['city'] ?? '')==='Bursa')    ?'selected':'' ?>>Bursa</option>
      <option value="Antalya"   <?= (($_POST['city'] ?? '')==='Antalya')  ?'selected':'' ?>>Antalya</option>
      <option value="other"     <?= (($_POST['city'] ?? '')==='other')    ?'selected':'' ?>>Diğer</option>
    </select>

    <div id="city_other_field">
      <label for="city_other">Other City:</label>
      <input type="text" id="city_other" name="city_other" 
             value="<?= htmlspecialchars($_POST['city_other'] ?? '') ?>">
    </div>

    <label for="district">District:</label>
    <input type="text" id="district" name="district"
           value="<?= htmlspecialchars($_POST['district'] ?? '') ?>" required>

    <label for="user_type">User Type:</label>
    <select id="user_type" name="user_type" required>
      <option value="">Select…</option>
      <option value="consumer" <?= (($_POST['user_type'] ?? '')==='consumer')?'selected':'' ?>>Consumer</option>
      <option value="market"   <?= (($_POST['user_type'] ?? '')==='market')  ?'selected':'' ?>>Market</option>
    </select>

    <button type="submit" class="btn">Log in</button>
  </form>

  <p style="text-align:center; margin-top:10px;">
   Are you already registered? <a href="login.php">Login</a>
  </p>
</div>

<script>
// Show/hide the "Diğer Şehir" field
document.getElementById('city').addEventListener('change', function(){
  const otherField = document.getElementById('city_other_field');
  if (this.value === 'other') {
    otherField.style.display = 'block';
  } else {
    otherField.style.display = 'none';
  } 
});
// On page load, check if 'other' was previously selected
window.addEventListener('DOMContentLoaded', function(){
  const sel = document.getElementById('city');
  if (sel.value === 'other') {
    document.getElementById('city_other_field').style.display = 'block';
  }
});
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
