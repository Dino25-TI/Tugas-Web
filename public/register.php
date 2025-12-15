<?php
require_once __DIR__.'/../includes/db.php';
$config = require __DIR__.'/../includes/config.php';
$base = $config['base_url'];
session_start();
if (($_SESSION['role'] ?? '') === 'admin') {
    header("Location: {$base}/admin/index.php");
    exit;
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full = trim($_POST['full_name']);
    $pass = $_POST['password'];
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (username,email,password_hash,full_name,role) VALUES (?,?,?,?,?)");
    $stmt->execute([$username,$email,$hash,$full,'user']);
    header("Location: {$base}/login.php"); exit;
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Daftar - RASA</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo $base;?>/assets/register.css">
</head>
<body>
  <div class="container auth">
    <h3>Buat Akun Baru</h3>
    <form method="post">
      <label>Nama Lengkap</label>
      <input type="text" name="full_name" placeholder="Masukkan nama lengkap" required>
      
      <label>Username</label>
      <input type="text" name="username" placeholder="Pilih username" required>
      
      <label>Email</label>
      <input type="email" name="email" placeholder="email@contoh.com" required>
      
      <label>Password</label>
      <input type="password" name="password" placeholder="Masukkan password" required>
      
      <button class="btn" type="submit">Daftar</button>
    </form>
    <p>Sudah punya akun? <a href="<?php echo $base;?>/login.php">Masuk</a></p>
  </div>
</body>
</html>
