<?php
require_once __DIR__ . '/../../includes/db.php';
$config = require __DIR__ . '/../../includes/config.php';
$base   = rtrim($config['base_url'], '/');

session_start();

// Pastikan login sebagai psikolog
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'psychologist') {
    header("Location: {$base}/login.php");
    exit;
}

$doctor_id = (int)($_SESSION['doctor_id'] ?? 0);
if ($doctor_id <= 0) {
    die('Akun ini belum terhubung ke data psikolog.');
}

// Profil dokter
$docStmt = $pdo->prepare("SELECT display_name FROM doctors WHERE id = ?");
$docStmt->execute([$doctor_id]);
$doctorProfile = $docStmt->fetch(PDO::FETCH_ASSOC);

// Riwayat penuh (tanpa LIMIT)
$hist = $pdo->prepare("
    SELECT b.id, b.scheduled_at, b.consultation_type,
           u.full_name
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    WHERE b.doctor_id = ?
      AND b.status = 'done'
    ORDER BY b.scheduled_at DESC
");
$hist->execute([$doctor_id]);
$history = $hist->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Riwayat Pasien - RASA</title>
    <link rel="stylesheet" href="<?= $base; ?>/assets/doctor_dashboard.css">
</head>
<body>
<div class="doclayout">

    <!-- TOPBAR ATAS -->
    <header class="topbar-main">
        <div class="topbar-left">
            <button class="topbar-menu" type="button" onclick="toggleSidebar()">☰</button>

            <div class="topbar-brand">
                <span class="brand-dot">
                    <?= strtoupper(substr($doctorProfile['display_name'] ?? 'P', 0, 1)); ?>
                </span>
                <span class="brand-text">RASA Psikolog</span>
            </div>
        </div>

        <div class="topbar-spacer"></div>

        <div class="topbar-user">
            <span class="topbar-user-avatar">
                <?= strtoupper(substr($doctorProfile['display_name'] ?? 'P', 0, 1)); ?>
            </span>
            <span class="topbar-user-name">
                <?= htmlspecialchars($doctorProfile['display_name'] ?? 'Psikolog'); ?>
            </span>
        </div>
    </header>

    <!-- SIDEBAR (menu Riwayat aktif) -->
    <aside class="doc-sidebar">
        <nav class="doc-menu">
            <a href="<?= $base; ?>/doctor/index.php">
                <span class="icon">🏠</span>
                <span class="label">Dashboard</span>
            </a>
            <a href="<?= $base; ?>/doctor/profile.php">
                <span class="icon">👤</span>
                <span class="label">Profil Saya</span>
            </a>
            <a href="<?= $base; ?>/doctor/schedule.php">
                <span class="icon">📅</span>
                <span class="label">Jadwal Saya</span>
            </a>
            <a href="<?= $base; ?>/doctor/history.php" class="active">
                <span class="icon">📜</span>
                <span class="label">Riwayat Pasien</span>
            </a>
            <a href="<?= $base; ?>/doctor/my_reviews.php">
                <span class="icon">💬</span>
                <span class="label">Ulasan untuk Saya</span>
            </a>
            <a href="<?= $base; ?>/doctor/artikel_saya.php">
                <span class="icon">📄</span>
                <span class="label">Artikel Saya</span>
            </a>
            <a href="<?= $base; ?>/logout.php">
                <span class="icon">⏏</span>
                <span class="label">Logout</span>
            </a>
        </nav>
    </aside>

    <!-- KONTEN RIWAYAT -->
    <div class="doc-main">
        <header class="docdash-header">
            <div>
                <h1>Riwayat Pasien</h1>
                <p>Daftar lengkap riwayat konsultasi yang sudah selesai.</p>
            </div>
            <a href="<?= $base; ?>/doctor/index.php" class="btn-logout" style="background:rgba(15,23,42,0.15);">
                Kembali ke Dashboard
            </a>
        </header>

        <main class="doc-grid">
            <section class="doc-panel" style="grid-column: 1 / -1;">
                <h2>Semua Riwayat Konsultasi</h2>

                <?php if ($history): ?>
                    <ul class="history-list">
                        <?php foreach ($history as $h): ?>
                            <li>
                                <strong><?= htmlspecialchars($h['full_name']); ?></strong>
                                <span><?= date('d M Y H:i', strtotime($h['scheduled_at'])); ?></span>
                                <span><?= htmlspecialchars($h['consultation_type']); ?></span>
                                <a href="<?= $base; ?>/doctor/doctor_patient_detail.php?booking_id=<?= $h['id']; ?>">
                                    Lihat detail
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p style="margin-top:8px; font-size:14px; color:#64748b;">
                        Belum ada riwayat konsultasi.
                    </p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

<script>
function toggleSidebar() {
  const layout  = document.querySelector('.doclayout');
  const sidebar = document.querySelector('.doc-sidebar');

  layout.classList.toggle('sidebar-open');
  sidebar.classList.toggle('expand');
}
</script>
</body>
</html>
