<?php
require_once __DIR__ . '/../includes/db.php';
$config = require __DIR__ . '/../includes/config.php';
$base   = rtrim($config['base_url'], '/');

session_start();

$id   = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM doctors WHERE id = ?");
$stmt->execute([$id]);
$d = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$d) {
    echo "Dokter tidak ditemukan";
    exit;
}

// Sertifikat
$certsStmt = $pdo->prepare("SELECT * FROM doctor_certificates WHERE doctor_id = ?");
$certsStmt->execute([$id]);
$certs = $certsStmt->fetchAll(PDO::FETCH_ASSOC);

// Ulasan
$revStmt = $pdo->prepare("
    SELECT r.rating,
           r.comment,
           r.show_profile,
           u.full_name
    FROM reviews r
    LEFT JOIN users u ON u.id = r.user_id
    WHERE r.doctor_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
");
$revStmt->execute([$id]);
$reviews = $revStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($d['display_name']); ?> - Detail Psikolog</title>
    <link rel="stylesheet" href="<?= $base; ?>/assets/doctorr_style.css">
</head>
<body>

<div class="doctor-detail-page">
    <div class="doctor-detail-main">

        <!-- KIRI: CARD PROFIL KECIL -->
        <aside class="doctor-side-card">
            <div class="side-photo">
                <img src="<?= $base; ?>/assets/images/doctors/<?= htmlspecialchars($d['photo']); ?>"
                     alt="<?= htmlspecialchars($d['display_name']); ?>">
            </div>

            <h3 class="side-name"><?= htmlspecialchars($d['display_name']); ?></h3>
            <p class="side-title"><?= htmlspecialchars($d['title']); ?></p>
            <p class="side-meta">⭐ <?= $d['rating']; ?>/5</p>

            <?php if (!empty($d['location'])): ?>
                <p class="side-location"><?= htmlspecialchars($d['location']); ?></p>
            <?php endif; ?>

            <div class="side-actions">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a class="btn-primary" href="<?= $base; ?>/login.php">Login untuk Booking</a>
                <?php else: ?>
                    <a class="btn-primary" href="<?= $base; ?>/booking.php?doctor_id=<?= $d['id']; ?>">Booking Sesi</a>
                    <a class="btn-outline" href="<?= $base; ?>/review.php?doctor_id=<?= $d['id']; ?>">Beri Ulasan</a>
                    <a class="btn-outline" href="<?= $base; ?>/komplain_psikolog.php?doctor_id=<?= $d['id']; ?>">Laporkan Psikolog</a>
                <?php endif; ?>
            </div>
        </aside>

        <!-- KANAN: KONTEN UTAMA -->
        <section class="doctor-detail-content">

            <!-- Hero: foto / video -->
            <div class="doctor-hero">
                <img src="<?= $base; ?>/assets/images/doctors/<?= htmlspecialchars($d['photo']); ?>"
                     alt="<?= htmlspecialchars($d['display_name']); ?>">
            </div>

            <!-- Detail Psikolog (bio panjang) -->
            <div class="doctor-section doctor-bio-long">
                <h3>Detail Psikolog</h3>
                <?php if (!empty($d['bio'])): ?>
                    <p><?= nl2br(htmlspecialchars($d['bio'])); ?></p>
                <?php else: ?>
                    <p>Deskripsi psikolog belum tersedia.</p>
                <?php endif; ?>
            </div>

            <!-- Pendidikan & Topik Keahlian (dua kotak) -->
            <?php if (!empty($d['education']) || !empty($d['specialties'])): ?>
                <div class="doctor-info-grid">

                    <?php if (!empty($d['education'])): ?>
                        <div class="info-card">
                            <h3>Pendidikan</h3>
                            <p><?= nl2br(htmlspecialchars($d['education'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($d['specialties'])): ?>
                        <div class="info-card">
                            <h3>Topik Keahlian</h3>
                            <div class="topic-badges">
                                <?php foreach (explode(',', $d['specialties']) as $spec): ?>
                                    <span class="badge-topic"><?= htmlspecialchars(trim($spec)); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endif; ?>

            <!-- Sertifikat -->
            <?php if ($certs): ?>
                <div class="doctor-section">
                    <h3>Sertifikat</h3>
                    <?php foreach ($certs as $c): ?>
                        <p><?= htmlspecialchars($c['title']); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Jadwal & Layanan -->
            <div class="doctor-section">
                <h3>Jadwal dan Layanan Tersedia</h3>
                <p>Integrasikan dengan halaman jadwal yang sudah kamu buat (filter hari &amp; waktu).</p>
            </div>

            <!-- Ulasan -->
            <?php if ($reviews): ?>
                <div class="doctor-reviews">
                    <h3>Ulasan</h3>

                    <?php foreach ($reviews as $r): ?>
                        <?php
                        $nama = (!empty($r['show_profile']) && $r['show_profile'] == 1)
                            ? htmlspecialchars($r['full_name'])
                            : 'Anonim';
                        ?>
                        <div class="review-item">
                            <div class="review-avatar">
                                <span><?= strtoupper(substr($nama, 0, 2)); ?></span>
                            </div>

                            <div class="review-body">
                                <div class="review-header">
                                    <strong><?= $nama; ?></strong>
                                    <span class="review-meta"><?= (int)$r['rating']; ?>/5</span>
                                </div>
                                <p class="review-text">
                                    <?= nl2br(htmlspecialchars($r['comment'])); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="reviews-more-wrap">
                        <a href="<?= $base; ?>/review_doctor.php?doctor_id=<?= $d['id']; ?>" class="btn-more-reviews">
                            Tampilkan lebih banyak
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="doctor-reviews">
                    <h3>Ulasan</h3>
                    <p class="review-empty">Belum ada ulasan untuk psikolog ini.</p>
                </div>
            <?php endif; ?>

        </section>

    </div>
</div>

</body>
</html>
