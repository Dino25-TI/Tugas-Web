<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/db.php';
$config = require __DIR__ . '/../includes/config.php';
$base   = rtrim($config['base_url'], '/');

session_start();

// Kalau sudah login sebagai admin, langsung ke admin
if (($_SESSION['role'] ?? '') === 'admin') {
    header("Location: {$base}/admin/index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($u && password_verify($pass, $u['password_hash'])) {
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['role']    = $u['role'];

        // Arahkan sesuai role
        if ($u['role'] === 'admin') {
            header("Location: {$base}/admin/index.php");
            exit;

        } elseif ($u['role'] === 'psychologist') {
            // mapping users -> doctors
            $stmtDoc = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
            $stmtDoc->execute([$u['id']]);
            $doc = $stmtDoc->fetch(PDO::FETCH_ASSOC);

            $_SESSION['doctor_id'] = $doc['id'] ?? null;

            header("Location: {$base}/doctor/index.php");
            exit;

        } else {
            // user biasa
            header("Location: {$base}/index.php");
            exit;
        }
    } else {
        $error = "Email atau password salah.";
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Login - RASA</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $base; ?>/assets/login.css">
</head>
<body>
  <div class="container auth">
    <h3>Masuk ke RASA</h3>

    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post">
      <label>Email</label>
      <input type="email" name="email" placeholder="email@contoh.com" required>

      <label>Password</label>
      <input type="password" name="password" placeholder="Masukkan password" required>

      <button class="btn" type="submit">Masuk</button>
    </form>

    <p>Belum punya akun? <a href="<?= $base; ?>/register.php">Daftar</a></p>
  </div>
</body>
</html>
