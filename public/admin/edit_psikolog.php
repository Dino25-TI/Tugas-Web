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

// Ambil ID psikolog dari URL
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header("Location: {$base}/admin/manage_psikolog.php");
    exit;
}

// Ambil data dokter/psikolog
$stmt = $pdo->prepare("
    SELECT id, display_name, email, specialties, hourly_price, bio
    FROM doctors
    WHERE id = :id
");
$stmt->execute([':id' => $id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    header("Location: {$base}/admin/manage_psikolog.php");
    exit;
}

// Proses submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama        = trim($_POST['display_name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $specialties = trim($_POST['specialties'] ?? '');
    $hourly      = (float) ($_POST['hourly_price'] ?? 0);

    $sql = "UPDATE doctors
            SET display_name = :nama,
                email        = :email,
                specialties  = :specialties,
                hourly_price = :hourly
            WHERE id = :id";
    $st = $pdo->prepare($sql);
    $st->execute([
        ':nama'        => $nama,
        ':email'       => $email,
        ':specialties' => $specialties,
        ':hourly'      => $hourly,
        ':id'          => $id,
    ]);

    header("Location: {$base}/admin/manage_psikolog.php");
    exit;
}

// Nama admin untuk topbar
$adminName = $_SESSION['full_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Psikolog - RASA</title>
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
            <div class="topbar-title">Edit Psikolog</div>
            <div class="topbar-subtitle">Perbarui profil dan tarif psikolog</div>
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
    $activeMenu = 'psikolog'; // sesuaikan dengan sidebar
    include 'sidebar.php';
    ?>

    <main class="admin-main">
        <div class="admin-form-wrapper psikolog">
            <h2>Edit Psikolog</h2>
            <p class="admin-form-caption">
                ID: <?= (int) $doc['id']; ?>
            </p>

            <form method="post" class="admin-form">
                <div class="psikolog-grid-2">
                    <div class="admin-form-group">
                        <label for="display_name" class="admin-form-label">Nama lengkap</label>
                        <input
                            type="text"
                            id="display_name"
                            name="display_name"
                            class="admin-form-input"
                            required
                            value="<?= htmlspecialchars($doc['display_name'] ?? ''); ?>">
                    </div>

                    <div class="admin-form-group">
                        <label for="email" class="admin-form-label">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="admin-form-input"
                            required
                            value="<?= htmlspecialchars($doc['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="admin-form-group">
                    <label for="specialties" class="admin-form-label">Spesialisasi</label>
                    <input
                        type="text"
                        id="specialties"
                        name="specialties"
                        class="admin-form-input"
                        placeholder="Contoh: Pernikahan, Keluarga, Kecemasan"
                        value="<?= htmlspecialchars($doc['specialties'] ?? ''); ?>">
                </div>

                <div class="admin-form-group">
                    <label for="hourly_price" class="admin-form-label">Tarif per jam (Rp)</label>
                    <input
                        type="number"
                        id="hourly_price"
                        name="hourly_price"
                        class="admin-form-input"
                        min="0"
                        step="10000"
                        value="<?= htmlspecialchars($doc['hourly_price'] ?? ''); ?>">
                    <div class="admin-hint">Contoh: 400000 untuk Rp400.000 / jam</div>
                </div>

                <div class="admin-form-actions">
                    <button type="submit" class="btn btn-confirm">
                        Simpan Perubahan
                    </button>

                    <a href="<?= $base; ?>/admin/manage_psikolog.php" class="admin-form-back strong">
                        Kembali ke daftar psikolog
                    </a>
                </div>
            </form>
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
