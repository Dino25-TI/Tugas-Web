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

// Ambil ID booking dari URL
$bookingId = (int)($_GET['id'] ?? 0);
if ($bookingId <= 0) {
    die('Booking tidak ditemukan.');
}

// Ambil data booking
$sql = "SELECT b.id, b.user_id, b.doctor_id, b.scheduled_at
        FROM bookings b
        WHERE b.id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $bookingId]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    die('Booking tidak ditemukan.');
}

// Format jadwal saat ini untuk ditampilkan (read-only)
$currentTimeDisplay = date('d/m/Y H:i', strtotime($booking['scheduled_at']));

// Format untuk input datetime-local (YYYY-MM-DDTHH:ii)
$currentTimeForInput = date('Y-m-d\TH:i', strtotime($booking['scheduled_at']));

// Nama admin (opsional untuk topbar)
$adminName = $_SESSION['full_name'] ?? 'Admin';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Ubah Jadwal Booking - RASA</title>

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
            <div class="topbar-title">Ubah Jadwal Booking</div>
            <div class="topbar-subtitle">Sesuaikan jadwal konsultasi user dengan dokter</div>
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
    $activeMenu = 'booking'; // sesuaikan dengan key menu-mu
    include 'sidebar.php';
    ?>

    <main class="admin-main">
        <div class="admin-form-wrapper">
            <h2>Ubah Jadwal Booking</h2>
            <p class="admin-form-caption">
                User ID: <?= (int)$booking['user_id']; ?> &nbsp;•&nbsp;
                Dokter ID: <?= (int)$booking['doctor_id']; ?>
            </p>

            <form method="post"
                  action="<?= $base; ?>/admin/update_booking.php"
                  class="admin-form">

                <input type="hidden" name="id" value="<?= (int)$booking['id']; ?>">

                <div class="admin-form-group">
                    <label class="admin-form-label">Jadwal saat ini</label>
                    <input type="text"
                           class="admin-form-input"
                           value="<?= htmlspecialchars($currentTimeDisplay); ?>"
                           disabled>
                </div>

                <div class="admin-form-group">
                    <label for="new_time" class="admin-form-label">Jadwal baru</label>
                    <input type="datetime-local"
                           id="new_time"
                           name="new_time"
                           class="admin-form-input"
                           required
                           value="<?= htmlspecialchars($currentTimeForInput); ?>">
                </div>

                <div class="admin-form-actions">
                    <button type="submit" class="btn btn-confirm">
                        Simpan Perubahan
                    </button>

                    <a href="<?= $base; ?>/admin/index.php" class="admin-form-back strong">
                        Kembali ke beranda
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
