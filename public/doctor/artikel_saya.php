<?php
session_start();
require __DIR__ . '/../../includes/db.php';
$config = require __DIR__ . '/../../includes/config.php';
$base   = rtrim($config['base_url'], '/');

// Cek login psikolog
if (!isset($_SESSION['doctor_id']) || ($_SESSION['role'] ?? '') !== 'psychologist') {
    header("Location: {$base}/login.php");
    exit;
}

$doctorId = (int) $_SESSION['doctor_id'];

// Ambil profil singkat untuk topbar
$docStmt = $pdo->prepare("SELECT display_name FROM doctors WHERE id = ?");
$docStmt->execute([$doctorId]);
$doctorProfile = $docStmt->fetch(PDO::FETCH_ASSOC);

// Flash message artikel
$flash_article = $_SESSION['flash_article'] ?? '';
unset($_SESSION['flash_article']);

$error = '';

// Handle submit artikel baru
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title'] ?? '');
    $slug     = trim($_POST['slug'] ?? '');
    $category = $_POST['category'] ?? 'artikel';
    $content  = trim($_POST['content'] ?? '');

    if ($slug === '' && $title !== '') {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
        $slug = trim($slug, '-');
    }

    if ($title !== '' && $content !== '') {
        $sql = "INSERT INTO articles
                (author_doctor_id, title, slug, category, content, is_published, status, published_at)
                VALUES
                (:author_doctor_id, :title, :slug, :category, :content, 0, 'pending_review', NULL)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':author_doctor_id' => $doctorId,
            ':title'            => $title,
            ':slug'             => $slug,
            ':category'         => $category,
            ':content'          => $content,
        ]);

        $_SESSION['flash_article'] = 'Artikel berhasil dikirim untuk direview admin.';
        header("Location: {$base}/doctor/artikel_saya.php");
        exit;
    } else {
        $error = 'Judul dan konten wajib diisi.';
    }
}

// Ambil artikel milik dokter ini
$stmt = $pdo->prepare("
    SELECT id, title, category, status, rejected_reason, created_at
    FROM articles
    WHERE author_doctor_id = :doctor_id
    ORDER BY created_at DESC
");
$stmt->execute([':doctor_id' => $doctorId]);
$myArticles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Artikel Saya - RASA</title>

    <!-- CSS layout dashboard (sama dengan index.php dokter) -->
    <link rel="stylesheet" href="<?= $base; ?>/assets/doctor_dashboard.css">

    <!-- CSS khusus halaman artikel (opsional) -->
    <style>
        /* ===== AREA KONTEN KANAN (OFFSET SIDEBAR) ===== */
.page-wrapper{
    margin-left: 80px;          /* samakan dengan lebar sidebar */
    padding: 96px 24px 48px;
    background:#f4f5ff;
}

/* bungkus konten artikel di tengah */
.doctor-wrapper{
    max-width: 900px;
    margin: 0 auto 72px;
}

/* judul halaman */
.doctor-wrapper h1{
    margin:0 0 18px;
    font-size:22px;
    font-weight:600;
    color:#0f172a;
}

/* flash & error */
.flash{
    margin-bottom:16px;
    padding:10px 12px;
    border-radius:12px;
    background:#dcfce7;
    color:#166534;
    font-size:0.9rem;
}
.error{
    margin-bottom:16px;
    padding:10px 12px;
    border-radius:12px;
    background:#fee2e2;
    color:#991b1b;
    font-size:0.9rem;
}

/* ===== KARTU FORM ARTIKEL ===== */
.article-form{
    margin-bottom:32px;
    padding:24px 26px 28px;
    background:linear-gradient(135deg,#f8fafc,#eef2ff);
    border-radius:20px;
    box-shadow:
        0 24px 50px rgba(15,23,42,0.18),
        0 0 0 1px rgba(148,163,184,0.12);
}
.article-form h2{
    margin:0 0 6px;
    font-size:18px;
    font-weight:600;
}
.article-form .text-muted{
    margin:0 0 12px;
    font-size:0.9rem;
    color:#6b7280;
}
.article-form label{
    display:block;
    font-size:0.9rem;
    margin-top:10px;
    color:#4b5563;
}

/* input & textarea */
.article-form input[type=text],
.article-form select,
.article-form textarea{
    width:100%;
    padding:9px 12px;
    border-radius:12px;
    border:1px solid #e5e7eb;
    font-size:0.95rem;
    background:#ffffff;
    margin-top:4px;
}

/* tombol kirim artikel full pill */
.article-form button{
    margin-top:18px;
    width:100%;
    padding:10px 18px;
    border-radius:999px;
    border:none;
    background:linear-gradient(90deg,#059669,#14b8a6);
    color:#ffffff;
    cursor:pointer;
    font-size:0.95rem;
    font-weight:500;
    box-shadow:0 14px 32px rgba(5,150,105,0.45);
    transition:0.16s transform,0.16s box-shadow,0.16s filter;
}
.article-form button:hover{
    filter:brightness(1.05);
    transform:translateY(-1px);
    box-shadow:0 18px 40px rgba(5,150,105,0.55);
}

/* ===== TABEL DAFTAR ARTIKEL ===== */
.article-list-table{
    width:100%;
    border-collapse:collapse;
    margin-top:16px;
    background:#ffffff;
    border-radius:18px;
    overflow:hidden;
    box-shadow:
        0 18px 40px rgba(15,23,42,0.12),
        0 0 0 1px rgba(226,232,240,0.9);
}
.article-list-table th{
    background:#f9fafb;
    font-weight:600;
    font-size:0.85rem;
    color:#6b7280;
}
.article-list-table th,
.article-list-table td{
    padding:10px 14px;
    border-bottom:1px solid #f3f4f6;
    font-size:0.9rem;
}

/* badge status artikel */
.badge{
    display:inline-block;
    padding:2px 8px;
    border-radius:999px;
    font-size:0.75rem;
}
.badge-pending{  background:#fef3c7; color:#92400e; }
.badge-approved{ background:#dcfce7; color:#166534; }
.badge-rejected{ background:#fee2e2; color:#991b1b; }

.text-muted{
    color:#9ca3af;
    font-size:0.85rem;
}

    </style>
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
            <a href="<?= $base; ?>/doctor/my_reviews.php">
                <span class="icon">💬</span>
                <span class="label">Ulasan untuk Saya</span>
            </a>
            <a href="<?= $base; ?>/doctor/artikel_saya.php" class="active">
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
    <main class="page-wrapper">
        <div class="doctor-wrapper">
            <h1 style="margin-bottom: 16px;">Artikel Saya</h1>

            <?php if ($flash_article): ?>
                <div class="flash"><?= htmlspecialchars($flash_article); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="article-form">
                <h2>Tulis Artikel Baru</h2>
                <p class="text-muted">
                    Artikel akan dikirim ke admin untuk direview sebelum dipublish ke pengguna.
                </p>

                <form method="post">
                    <label>Judul</label>
                    <input type="text" name="title" required>

                    <label>Slug (URL)</label>
                    <input type="text" name="slug" placeholder="otomatis dari judul jika dikosongkan">

                    <label>Kategori</label>
                    <select name="category">
                        <option value="artikel">Artikel</option>
                        <option value="faq">FAQ</option>
                        <option value="self_help">Self-help</option>
                    </select>

                    <label>Konten</label>
                    <textarea name="content" rows="8" required></textarea>

                    <button type="submit">Kirim untuk Review</button>
                </form>
            </div>

            <h2>Daftar Artikel</h2>
            <table class="article-list-table">
                <thead>
                <tr>
                    <th>Judul</th>
                    <th>Kategori</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Alasan (jika ditolak)</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($myArticles)): ?>
                    <tr>
                        <td colspan="5" class="text-muted">Belum ada artikel.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($myArticles as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['title']); ?></td>
                            <td><?= htmlspecialchars(ucfirst(str_replace('_',' ', $row['category']))); ?></td>
                            <td>
                                <?php
                                $status = $row['status'];
                                $label  = ucfirst(str_replace('_',' ', $status));
                                $class  = 'badge-pending';
                                if ($status === 'approved') $class = 'badge-approved';
                                if ($status === 'rejected') $class = 'badge-rejected';
                                ?>
                                <span class="badge <?= $class; ?>"><?= htmlspecialchars($label); ?></span>
                            </td>
                            <td><?= htmlspecialchars($row['created_at'] ?? ''); ?></td>
                            <td class="text-muted">
                                <?php if ($status === 'rejected' && !empty($row['rejected_reason'])): ?>
                                    <?= nl2br(htmlspecialchars($row['rejected_reason'])); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
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
