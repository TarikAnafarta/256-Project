<?php
// edit_product.php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

if (!isset($_SESSION['user_id'], $_SESSION['user_type']) || $_SESSION['user_type']!=='market') {
    header('Location: login.php'); exit;
}

$pdo = getPDO();
// ensure market owns this product
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT p.*, m.user_id FROM products p JOIN markets m ON p.market_id = m.market_id WHERE p.product_id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row || $row['user_id'] != $_SESSION['user_id']) {
    die('Ürün bulunamadı veya yetkiniz yok.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    validate_csrf();

    $title            = trim($_POST['title']);
    $stock            = (int)($_POST['stock']);
    $normal_price     = (float)($_POST['normal_price']);
    $discounted_price = (float)($_POST['discounted_price']);
    $exp_date         = $_POST['expiration_date'];
    $img              = $_FILES['image'] ?? null;

    if ($title==='')          $errors[]='Başlık boş olamaz.';
    if ($stock<1)             $errors[]='Stok en az 1 olmalı.';
    if ($normal_price<=0)     $errors[]='Normal fiyat sıfırdan büyük olmalı.';
    if ($discounted_price<=0 || $discounted_price>$normal_price) {
        $errors[]='İndirimli fiyat hatalı.';
    }
    if (!$exp_date)           $errors[]='SKT gerekli.';

    // if new image uploaded
    if ($img && $img['error']===UPLOAD_ERR_OK) {
        $ext = pathinfo($img['name'], PATHINFO_EXTENSION);
        $filename = uniqid('prod_', true).'.'.$ext;
        move_uploaded_file($img['tmp_name'], __DIR__.'/img/'.$filename);
    } else {
        $filename = $row['image'];
    }

    if (empty($errors)) {
        $upd = $pdo->prepare(<<<SQL
          UPDATE products
             SET title=?,stock=?,normal_price=?,discounted_price=?,expiration_date=?,image=?
           WHERE product_id=?
SQL
        );
        $upd->execute([
            $title, $stock, $normal_price,
            $discounted_price, $exp_date,
            $filename, $id
        ]);
        $_SESSION['flash']=['msg'=>'Ürün güncellendi.','error'=>false];
        header('Location: market_products.php');
        exit;
    }
}

?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8"><title>Ürün Düzenle</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    /* same styles as add_product */
    body{font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:20px;}
    .container{max-width:500px;margin:0 auto;background:#fff;padding:20px;border-radius:8px;}
    label{display:block;margin-top:10px;}
    input,button{width:100%;padding:10px;margin-top:5px;border:1px solid #ddd;border-radius:4px;box-sizing:border-box;}
    button{background:#28a745;color:#fff;border:none;cursor:pointer;margin-top:15px;}
    button:hover{background:#218838;}
    .error{background:#f8d7da;color:#842029;padding:10px;border-radius:4px;}
    #toast{position:fixed;top:20px;right:20px;background:#28a745;color:#fff;padding:10px 20px;border-radius:4px;opacity:0;transition:opacity .3s;}
    #toast.show{opacity:1;}
  </style>
</head>
<body>
  <div id="toast"></div>
  <?php if (!empty($_SESSION['flash'])):
    $f=$_SESSION['flash']; unset($_SESSION['flash']);
  ?>
  <script>
    function showToast(msg,isErr){const t=document.getElementById('toast');t.textContent=msg;t.style.background=isErr?'#dc3545':'#28a745';t.classList.add('show');setTimeout(()=>t.classList.remove('show'),2500);}
    showToast(<?= json_encode($f['msg']) ?>,<?= $f['error']?'true':'false'?>);
  </script>
  <?php endif; ?>

  <div class="container">
    <h2>Ürün Düzenle</h2>
    <?php if($errors): ?>
      <div class="error"><?php foreach($errors as $e) echo htmlspecialchars($e).'<br>' ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

      <label>Başlık</label>
      <input name="title" type="text" value="<?= htmlspecialchars($row['title']) ?>" required>

      <label>Stok</label>
      <input name="stock" type="number" min="1" value="<?= htmlspecialchars($row['stock']) ?>" required>

      <label>Normal Fiyat (₺)</label>
      <input name="normal_price" type="number" step="0.01" value="<?= htmlspecialchars($row['normal_price']) ?>" required>

      <label>İndirimli Fiyat (₺)</label>
      <input name="discounted_price" type="number" step="0.01" value="<?= htmlspecialchars($row['discounted_price']) ?>" required>

      <label>Son Kullanma Tarihi</label>
      <input name="expiration_date" type="date" value="<?= htmlspecialchars($row['expiration_date']) ?>" required>

      <label>Resim (yenilemek için seçin)</label>
      <input name="image" type="file" accept="image/*">

      <button type="submit">Güncelle</button>
    </form>

    <p style="text-align:center;margin-top:10px;">
      <a href="market_products.php">← Ürün Listesine Dön</a>
    </p>
  </div>
</body>
</html>
