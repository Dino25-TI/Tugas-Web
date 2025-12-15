<?php
session_start();
require dirname(__DIR__, 2) . '/includes/db.php';
$config = require dirname(__DIR__, 2) . '/includes/config.php';
$base   = $config['base_url'];

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: {$base}/login.php");
    exit;
}

// ambil semua artikel
$sql = "SELECT id, title, slug, category, is_published, published_at
        FROM articles
        ORDER BY created_at DESC";
$articles = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$currentPath = $_SERVER['REQUEST_URI'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Artikel - RASA</title>
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/admin.css">
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-logo">
            <span>R</span> RASA Admin
        </div>
        <div class="admin-menu">
            <div class="admin-menu-title">Menu</div>

            <a href="<?php echo $base; ?>/admin/index.php"
               class="admin-link <?php echo (strpos($currentPath, '/admin/index.php') !== false) ? 'active' : ''; ?>">
                <span class="label"><span class="dot"></span> Booking & Pembayaran</span>
            </a>

            <a href="<?php echo $base; ?>/admin/manage_user.php"
               class="admin-link <?php echo (strpos($currentPath, '/admin/manage_user.php') !== false) ? 'active' : ''; ?>">
                <span class="label">Kelola User</span>
            </a>

            <a href="<?php echo $base; ?>/admin/manage_psikolog.php"
               class="admin-link <?php echo (strpos($currentPath, '/admin/manage_psikolog.php') !== false) ? 'active' : ''; ?>">
                <span class="label">Kelola Psikolog</span>
            </a>

            <a href="<?php echo $base; ?>/admin/laporan.php"
               class="admin-link <?php echo (strpos($currentPath, '/admin/laporan.php') !== false) ? 'active' : ''; ?>">
                <span class="label">Laporan</span>
            </a>

            <a href="<?php echo $base; ?>/admin/artikel.php"
               class="admin-link <?php echo (strpos($currentPath, '/admin/artikel.php') !== false) ? 'active' : ''; ?>">
                <span class="label">Artikel & FAQ</span>
            </a>

            <a href="<?php echo $base; ?>/admin/logout_admin.php" class="admin-link">
                <span class="label">Logout</span>
            </a>
        </div>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <div class="admin-title">Artikel & FAQ</div>
                <div class="admin-subtitle">Kelola konten edukasi yang tampil di RASA</div>
            </div>
            <div class="admin-user-pill">Admin</div>
        </div>

        <div class="table-card">
            <div class="table-header">
                <h3>Daftar Konten</h3>
                <a href="<?php echo $base; ?>/admin/edit_artikel.php" class="btn btn-confirm">
                    + Tambah Konten
                </a>
            </div>
            <div class="table-wrapper">
                <table class="table-admin">
                    <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th>Dipublish</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($articles as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['title'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($a['category'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($a['slug'] ?? ''); ?></td>
                            <td><?= !empty($a['is_published']) ? 'Published' : 'Draft'; ?></td>
                            <td><?= htmlspecialchars($a['published_at'] ?? '-'); ?></td>
                            <td>
                                <a href="edit_artikel.php?id=<?= (int)$a['id']; ?>" class="btn btn-view">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
