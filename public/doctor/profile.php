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

// Ambil doctor_id dari session
$doctor_id = (int) ($_SESSION['doctor_id'] ?? 0);
if ($doctor_id <= 0) {
    die('Akun ini belum terhubung ke data psikolog.');
}

// Ambil data dokter + email user (tanpa hourly_price)
$stmt = $pdo->prepare("
    SELECT 
        d.display_name,
        d.title,
        d.bio,
        d.education,
        d.specialties,
        d.location,
        d.photo,
        u.email
    FROM doctors d
    JOIN users u ON u.id = d.user_id
    WHERE d.id = ?
");
$stmt->execute([$doctor_id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    die('Data psikolog tidak ditemukan.');
}

// Flash message
$flash = $_SESSION['flash_profile'] ?? '';
unset($_SESSION['flash_profile']);

// Handle submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $display_name = trim($_POST['display_name'] ?? '');
    $title        = trim($_POST['title'] ?? '');
    $bio          = trim($_POST['bio'] ?? '');
    $education    = trim($_POST['education'] ?? '');
    $specialties  = trim($_POST['specialties'] ?? '');
    $location     = trim($_POST['location'] ?? '');
    $email        = trim($_POST['email'] ?? '');

    // TODO: handle upload foto kalau mau

    // Update tabel doctors (tanpa hourly_price)
    $up = $pdo->prepare("
        UPDATE doctors
        SET display_name = :display_name,
            title        = :title,
            bio          = :bio,
            education    = :education,
            specialties  = :specialties,
            location     = :location
        WHERE id = :id
    ");

    $up->execute([
        ':display_name' => $display_name,
        ':title'        => $title,
        ':bio'          => $bio,
        ':education'    => $education,
        ':specialties'  => $specialties,
        ':location'     => $location,
        ':id'           => $doctor_id,
    ]);

    // Update email login (users) + optional di doctors
    $user_id = (int) $_SESSION['user_id'];

    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $upUser = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
        $upUser->execute([$email, $user_id]);

        $upDocEmail = $pdo->prepare("UPDATE doctors SET email = ? WHERE id = ?");
        $upDocEmail->execute([$email, $doctor_id]);
    }

    $_SESSION['flash_profile'] = 'Profil berhasil diperbarui.';
    header("Location: {$base}/doctor/profile.php");
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Profil Psikolog</title>
    <link rel="stylesheet" href="<?= $base; ?>/assets/doctor_dashboard.css">
    <link rel="stylesheet" href="<?= $base; ?>/assets/doctor_profile.css">
</head>
<body>
<div class="doclayout">
    <!-- TOPBAR -->
    <header class="topbar-main">
        <div class="topbar-left">
            <button class="topbar-menu" type="button" onclick="toggleSidebar()">☰</button>

            <div class="topbar-brand">
                <span class="brand-dot">
                    <?= strtoupper(substr($doc['display_name'] ?? 'P', 0, 1)); ?>
                </span>
                <span class="brand-text">RASA Psikolog</span>
            </div>
        </div>

        <div class="topbar-spacer"></div>

        <div class="topbar-user">
            <span class="topbar-user-avatar">
                <?= strtoupper(substr($doc['display_name'] ?? 'P', 0, 1)); ?>
            </span>
            <span class="topbar-user-name">
                <?= htmlspecialchars($doc['display_name'] ?? 'Psikolog'); ?>
            </span>
        </div>
    </header>

    <!-- SIDEBAR KIRI -->
    <aside class="doc-sidebar">
        <nav class="doc-menu">
            <a href="<?= $base; ?>/doctor/index.php">
                <span class="icon">🏠</span>
                <span class="label">Dashboard</span>
            </a>
            <a href="<?= $base; ?>/doctor/profile.php" class="active">
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

    <!-- KONTEN PROFIL -->
    <main class="doc-main">
        <div class="docdash-main--profile">
            <?php if ($flash): ?>
                <div class="toast toast--success">
                    <?= htmlspecialchars($flash); ?>
                </div>
            <?php endif; ?>

            <section class="card--profile">
                <form method="post" class="form-grid">
                    <div class="form-section">
                        <h2>Informasi Utama</h2>

                        <div class="form-row">
                            <label for="display_name">Nama Tampilan</label>
                            <input
                                id="display_name"
                                type="text"
                                name="display_name"
                                value="<?= htmlspecialchars($doc['display_name']); ?>"
                                required
                            >
                        </div>

                        <div class="form-row">
                            <label for="title">Gelar / Title</label>
                            <input
                                id="title"
                                type="text"
                                name="title"
                                value="<?= htmlspecialchars($doc['title']); ?>"
                            >
                        </div>

                        <div class="form-row">
                            <label for="location">Lokasi</label>
                            <input
                                id="location"
                                type="text"
                                name="location"
                                value="<?= htmlspecialchars($doc['location']); ?>"
                            >
                        </div>

                        <div class="form-row">
                            <label for="email">Email Login / Praktik</label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="<?= htmlspecialchars($doc['email'] ?? ''); ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-section">
                        <h2>Detail Profil</h2>

                        <div class="form-row">
                            <label for="bio">Bio Singkat</label>
                            <textarea
                                id="bio"
                                name="bio"
                                rows="4"
                            ><?= htmlspecialchars($doc['bio']); ?></textarea>
                        </div>

                        <div class="form-row">
                            <label for="education">Pendidikan</label>
                            <textarea
                                id="education"
                                name="education"
                                rows="3"
                            ><?= htmlspecialchars($doc['education']); ?></textarea>
                        </div>

                        <div class="form-row">
                            <label for="specialties">Topik Keahlian (pisahkan dengan koma)</label>
                            <input
                                id="specialties"
                                type="text"
                                name="specialties"
                                value="<?= htmlspecialchars($doc['specialties']); ?>"
                            >
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            Simpan Profil
                        </button>
                    </div>
                </form>
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
