<?php
session_start();
require dirname(__DIR__, 2) . '/includes/db.php';
$config = require dirname(__DIR__, 2) . '/includes/config.php';
$base   = $config['base_url'];

// Cek login & role admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: {$base}/login.php");
    exit;
}

// Ambil user_id dari URL
$user_id = (int)($_GET['user_id'] ?? 0);
if ($user_id <= 0) {
    die('User tidak ditemukan.');
}

// Ambil data user
$stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    die('User tidak ditemukan.');
}

// nama admin (kalau mau dipakai di topbar)
$adminName = $_SESSION['full_name'] ?? 'Admin';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Edit User - RASA</title>

    <!-- CSS global admin + CSS khusus form -->
    <link rel="stylesheet" href="<?= $base; ?>/assets/admin.css">
    <link rel="stylesheet" href="<?= $base; ?>/assets/admin_form.css">
</head>
<body>
<header class="admin-topbar-main">
    <div class="topbar-left">
        <div class="topbar-logo">
            <button class="sidebar-toggle" type="button"
                    onclick="document.body.classList.toggle('sidebar-collapsed')">
                ☰
            </button>
            <span class="logo-mark">R</span>
            <span class="logo-text">RASA Admin</span>
        </div>

        <div class="topbar-page">
            <div class="topbar-title">Edit User</div>
            <div class="topbar-subtitle">Perbarui email login user dengan aman</div>
        </div>
    </div>

    <div class="topbar-right">
        <button class="user-toggle" type="button" id="userToggle">
            <div class="user-toggle-avatar">
                <span><?= strtoupper(substr($adminName, 0, 1)); ?></span>
            </div>
            <span class="user-toggle-name"><?= htmlspecialchars($adminName); ?></span>
            <span class="user-toggle-caret">▾</span>
        </button>

        <div class="user-dropdown" id="userDropdown">
            <a href="<?= $base; ?>/admin/logout_admin.php" class="user-dropdown-item">
                <span class="user-dropdown-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="4" y="3" width="9" height="18" rx="2"></rect>
                        <circle cx="11" cy="12" r="0.8" fill="white"></circle>
                        <path d="M14 12h6"></path>
                        <path d="M18 9l3 3-3 3"></path>
                    </svg>
                </span>
                <span>Sign out</span>
            </a>
        </div>
    </div>
</header>

<div class="admin-shell">
    <?php
    $activeMenu = 'users'; // atau apa pun key untuk menu Kelola User
    include 'sidebar.php';
    ?>

    <main class="admin-main">
        <div class="admin-form-wrapper">
            <h2>Edit User</h2>
            <p>
                <span class="admin-form-label">Nama user</span><br>
                <span class="admin-form-value">
                    <?= htmlspecialchars($user['full_name']); ?>
                </span>
            </p>

            <form method="post"
                  action="<?= $base; ?>/admin/user_update_email.php"
                  class="admin-form">
                <input type="hidden" name="user_id" value="<?= (int)$user['id']; ?>">

                <div class="admin-form-group">
                    <label for="email" class="admin-form-label">Email login</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="admin-form-input"
                        required
                        value="<?= htmlspecialchars($user['email']); ?>"
                        placeholder="masukkan email login baru">
                </div>

                <button type="submit" class="btn btn-confirm">
                    Simpan Perubahan
                </button>
            </form>
            <a href="<?= $base; ?>/admin/manage_user.php" class="admin-form-back">
                Kembali ke beranda
            </a>
        </div>
    </main>
</div>

<script>
  const userToggle   = document.getElementById('userToggle');
  const userDropdown = document.getElementById('userDropdown');

  if (userToggle && userDropdown) {
    userToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      userDropdown.classList.toggle('open');
    });

    document.addEventListener('click', function () {
      userDropdown.classList.remove('open');
    });
  }
</script>
</body>
</html>
