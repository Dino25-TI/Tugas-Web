<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
$config = require __DIR__ . '/../../includes/config.php';

$base = rtrim($config['base_url'], '/');

// Cek login & role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'psychologist') {
    header("Location: {$base}/login.php");
    exit;
}

$doctor_id = (int) ($_SESSION['doctor_id'] ?? 0);
$review_id = (int) ($_GET['id'] ?? 0);

if ($doctor_id <= 0 || $review_id <= 0) {
    die('Data tidak ditemukan.');
}

/* ==== Profil singkat untuk topbar ==== */

$docStmt = $pdo->prepare("SELECT display_name FROM doctors WHERE id = ?");
$docStmt->execute([$doctor_id]);
$doctorProfile = $docStmt->fetch(PDO::FETCH_ASSOC);

/* ==== Simpan balasan ==== */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $reply = trim($_POST['reply']);

    if ($reply !== '') {
        $upd = $pdo->prepare("
            UPDATE reviews
            SET doctor_reply = :reply,
                doctor_reply_at = NOW()
            WHERE id = :id AND doctor_id = :doctor_id
        ");
        $upd->execute([
            ':reply'     => $reply,
            ':id'        => $review_id,
            ':doctor_id' => $doctor_id,
        ]);
    }

    header("Location: {$base}/doctor/review_detail.php?id={$review_id}");
    exit;
}

/* ==== Ambil detail ulasan ==== */

$stmt = $pdo->prepare("
    SELECT r.*, u.full_name
    FROM reviews r
    LEFT JOIN users u ON u.id = r.user_id
    WHERE r.id = ? AND r.doctor_id = ?
");
$stmt->execute([$review_id, $doctor_id]);
$review = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$review) {
    die('Ulasan tidak ditemukan.');
}

$nama = (!empty($review['show_profile']) && (int) $review['show_profile'] === 1)
    ? ($review['full_name'] ?? 'Klien')
    : 'Anonim';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Detail Ulasan</title>

    <!-- layout dashboard utama -->
    <link rel="stylesheet" href="<?= $base; ?>/assets/doctor_dashboard.css">

    <!-- CSS khusus ulasan -->
    <link rel="stylesheet" href="<?= $base; ?>/assets/doctor_reviews.css">
</head>
<body>
<div class="doclayout">

    <!-- TOPBAR -->
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

    <!-- SIDEBAR -->
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
            <a href="<?= $base; ?>/doctor/history.php">
                <span class="icon">📜</span>
                <span class="label">Riwayat Pasien</span>
            </a>
            <a href="<?= $base; ?>/doctor/my_reviews.php" class="active">
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

    <!-- KONTEN KANAN -->
    <main class="doc-main">
        <div class="dash-reviews-wrapper"> 
        <header class="docdash-header">
            <div>
                <h1>Detail Ulasan</h1>
                <p>Lihat dan balas ulasan klien.</p>
            </div>
            <a href="<?= $base; ?>/doctor/my_reviews.php" class="btn-logout">Kembali ke Daftar</a>
        </header>

        <section class="card history-card">
            <h2>Ulasan</h2>

            <ul class="history-list">
                <li>
                    <div class="history-header-main">
                        <strong><?= htmlspecialchars($nama); ?></strong>
                        <span><?= date('d M Y H:i', strtotime($review['created_at'])); ?></span>
                        <span><?= (int) $review['rating']; ?>/5</span>
                    </div>

                    <p><?= nl2br(htmlspecialchars($review['comment'])); ?></p>

                    <?php if (!empty($review['doctor_reply'])): ?>
                        <div class="doctor-reply">
                            <strong>Tanggapan Anda:</strong>
                            <p><?= nl2br(htmlspecialchars($review['doctor_reply'])); ?></p>
                            <?php if (!empty($review['doctor_reply_at'])): ?>
                                <small><?= date('d M Y H:i', strtotime($review['doctor_reply_at'])); ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="reply-form">
                        <label>Balasan singkat</label>
                        <textarea name="reply" rows="3" required></textarea>
                        <button type="submit" class="btn">Kirim Balasan</button>
                    </form>
                </li>
            </ul>
        </section>
        </div>
    </main>
</div>

<script>
function toggleSidebar() {
    const layout  = document.querySelector('.doclayout');
    const sidebar = document.querySelector('.doc-sidebar');
    if (!layout || !sidebar) return;

    layout.classList.toggle('sidebar-open');
    sidebar.classList.toggle('expand');
}
</script>
</body>
</html>
