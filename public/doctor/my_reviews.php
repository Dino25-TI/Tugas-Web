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
if ($doctor_id <= 0) {
    die('Akun ini belum terhubung ke data psikolog.');
}

/* ==== Profil singkat untuk topbar ==== */
$docStmt = $pdo->prepare("SELECT display_name FROM doctors WHERE id = ?");
$docStmt->execute([$doctor_id]);
$doctorProfile = $docStmt->fetch(PDO::FETCH_ASSOC);

/* ==== Handle POST: balasan & hapus ulasan ==== */

// Simpan balasan dokter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'], $_POST['reply'])) {
    $reviewId = (int) $_POST['review_id'];
    $reply    = trim($_POST['reply']);

    if ($reviewId > 0 && $reply !== '') {
        $upd = $pdo->prepare("
            UPDATE reviews
            SET doctor_reply    = :reply,
                doctor_reply_at = NOW()
            WHERE id = :id AND doctor_id = :doctor_id
        ");
        $upd->execute([
            ':reply'     => $reply,
            ':id'        => $reviewId,
            ':doctor_id' => $doctor_id,
        ]);
    }

    header("Location: {$base}/doctor/my_reviews.php");
    exit;
}

// Hapus ulasan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review_id'])) {
    $deleteId = (int) $_POST['delete_review_id'];

    if ($deleteId > 0) {
        $del = $pdo->prepare("
            DELETE FROM reviews
            WHERE id = :id AND doctor_id = :doctor_id
        ");
        $del->execute([
            ':id'        => $deleteId,
            ':doctor_id' => $doctor_id,
        ]);
    }

    header("Location: {$base}/doctor/my_reviews.php");
    exit;
}

/* ==== Statistik rating ==== */

$avgStmt = $pdo->prepare("
    SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_reviews
    FROM reviews
    WHERE doctor_id = ?
");
$avgStmt->execute([$doctor_id]);
$stats = $avgStmt->fetch(PDO::FETCH_ASSOC) ?: [
    'avg_rating'    => null,
    'total_reviews' => 0,
];

$goodStmt = $pdo->prepare("
    SELECT SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) AS good_count
    FROM reviews
    WHERE doctor_id = ?
");
$goodStmt->execute([$doctor_id]);
$good = $goodStmt->fetch(PDO::FETCH_ASSOC) ?: ['good_count' => 0];

$goodCount   = (int) $good['good_count'];
$total       = (int) $stats['total_reviews'];
$goodPercent = $total > 0 ? round($goodCount / $total * 100) : 0;

/* ==== Daftar ulasan ==== */

$revStmt = $pdo->prepare("
    SELECT 
        r.id,
        r.rating,
        r.comment,
        r.show_profile,
        r.created_at,
        r.doctor_reply,
        r.doctor_reply_at,
        u.full_name
    FROM reviews r
    LEFT JOIN users u ON u.id = r.user_id
    WHERE r.doctor_id = ?
    ORDER BY r.created_at DESC
");
$revStmt->execute([$doctor_id]);
$reviews = $revStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Ulasan untuk Saya</title>

    <link rel="stylesheet" href="<?= $base; ?>/assets/doctor_dashboard.css">
    <link rel="stylesheet" href="<?= $base; ?>/assets/doctor_reviews.css">

    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400&display=swap">
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

            <!-- Header halaman -->
            <header class="docdash-header">
                <div>
                    <h1>Ulasan untuk Saya</h1>
                    <p>Lihat rating dan komentar dari klien.</p>
                </div>
            </header>

            <!-- Ringkasan rating -->
            <section class="card rating-summary-cards">
                <div class="rating-summary-grid">
                    <!-- Total ulasan -->
                    <div class="rating-card">
                        <div class="rating-card__icon rating-card__icon--blue">
                            <span class="material-symbols-outlined">group</span>
                        </div>
                        <div>
                            <div class="rating-card__label">Total Ulasan</div>
                            <div class="rating-card__value"><?= (int) $stats['total_reviews']; ?></div>
                            <div class="rating-card__tag rating-card__tag--blue">Semua klien</div>
                        </div>
                    </div>

                    <!-- Rata-rata rating -->
                    <div class="rating-card">
                        <div class="rating-card__icon rating-card__icon--yellow">
                            <span class="material-symbols-outlined">star</span>
                        </div>
                        <div>
                            <div class="rating-card__label">Rata-rata Rating</div>
                            <div class="rating-card__value">
                                <?= $stats['avg_rating'] !== null ? number_format((float) $stats['avg_rating'], 2) : '0.00'; ?>/5
                            </div>
                            <div class="rating-card__tag rating-card__tag--green">Dari semua ulasan</div>
                        </div>
                    </div>

                    <!-- Rating baik (>= 4) -->
                    <div class="rating-card">
                        <div class="rating-card__icon rating-card__icon--purple">
                            <span class="material-symbols-outlined">thumb_up</span>
                        </div>
                        <div>
                            <div class="rating-card__label">Rating Baik (≥ 4)</div>
                            <div class="rating-card__value"><?= $goodPercent; ?>%</div>
                            <div class="rating-card__tag rating-card__tag--purple">
                                <?= $goodCount; ?> ulasan puas
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Daftar ulasan -->
            <section class="card history-card">
                <h2>Daftar Ulasan</h2>

                <?php if ($reviews): ?>
                    <div class="review-table-wrap">
                        <table class="review-table">
                            <thead>
                            <tr>
                                <th>Klien</th>
                                <th>Rating</th>
                                <th>Tanggal</th>
                                <th>Cuplikan</th>
                                <th>Aksi</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($reviews as $r): ?>
                                <?php
                                $nama = (!empty($r['show_profile']) && (int) $r['show_profile'] === 1)
                                    ? ($r['full_name'] ?? 'Klien')
                                    : 'Anonim';
                                $preview = mb_strimwidth($r['comment'], 0, 40, '...');
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($nama); ?></td>
                                    <td><?= (int) $r['rating']; ?>/5</td>
                                    <td><?= date('d M Y H:i', strtotime($r['created_at'])); ?></td>
                                    <td><?= htmlspecialchars($preview); ?></td>
                                    <td class="review-table__actions">
                                        <a href="<?= $base; ?>/doctor/review_detail.php?id=<?= (int) $r['id']; ?>"
                                           class="btn btn--small">
                                            Lihat
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="margin-top: 8px; font-size: 14px; color: #64748b;">
                        Belum ada ulasan yang masuk.
                    </p>
                <?php endif; ?>
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
