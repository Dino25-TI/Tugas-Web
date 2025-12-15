<?php
session_start();
require dirname(__DIR__, 2) . '/includes/db.php';
$config = require dirname(__DIR__, 2) . '/includes/config.php';
$base   = $config['base_url'];

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: {$base}/login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

// ambil data jika edit
$article = [
    'title'        => '',
    'slug'         => '',
    'category'     => 'artikel',
    'content'      => '',
    'is_published' => 1,
];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $article = $row;
    } else {
        header("Location: {$base}/admin/artikel.php");
        exit;
    }
}

// handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title        = trim($_POST['title'] ?? '');
    $slug         = trim($_POST['slug'] ?? '');
    $category     = $_POST['category'] ?? 'artikel';
    $content      = trim($_POST['content'] ?? '');
    $is_published = isset($_POST['is_published']) ? 1 : 0;

    if ($slug === '' && $title !== '') {
        // auto slug sederhana
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
        $slug = trim($slug, '-');
    }

    if ($isEdit) {
        $sql = "UPDATE articles
                SET title = :title,
                    slug  = :slug,
                    category = :category,
                    content = :content,
                    is_published = :is_published,
                    published_at = CASE 
                                      WHEN :is_published = 1 AND published_at IS NULL 
                                      THEN NOW() 
                                      ELSE published_at 
                                   END,
                    updated_at = NOW()
                WHERE id = :id";
    } else {
        $sql = "INSERT INTO articles (title, slug, category, content, is_published, published_at)
                VALUES (:title, :slug, :category, :content, :is_published,
                        CASE WHEN :is_published = 1 THEN NOW() ELSE NULL END)";
    }

    $stmt = $pdo->prepare($sql);
    $params = [
        ':title'        => $title,
        ':slug'         => $slug,
        ':category'     => $category,
        ':content'      => $content,
        ':is_published' => $is_published,
    ];
    if ($isEdit) {
        $params[':id'] = $id;
    }
    $stmt->execute($params);

    header("Location: {$base}/admin/artikel.php");
    exit;
}

$currentPath = $_SERVER['REQUEST_URI'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $isEdit ? 'Edit' : 'Tambah'; ?> Artikel - RASA</title>
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
               class="admin-link">
                <span class="label">Kelola User</span>
            </a>

            <a href="<?php echo $base; ?>/admin/manage_psikolog.php"
               class="admin-link">
                <span class="label">Kelola Psikolog</span>
            </a>

            <a href="<?php echo $base; ?>/admin/laporan.php"
               class="admin-link">
                <span class="label">Laporan</span>
            </a>

            <a href="<?php echo $base; ?>/admin/artikel.php"
               class="admin-link <?php echo (strpos($currentPath, '/admin/edit_artikel.php') !== false || strpos($currentPath, '/admin/artikel.php') !== false) ? 'active' : ''; ?>">
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
                <div class="admin-title"><?= $isEdit ? 'Edit Konten' : 'Tambah Konten'; ?></div>
                <div class="admin-subtitle">Artikel, FAQ, atau materi self-help</div>
            </div>
            <div class="admin-user-pill">Admin</div>
        </div>

        <div class="table-card">
            <div class="table-header">
                <h3>Form Konten</h3>
            </div>
            <div class="table-wrapper">
                <form method="post" class="admin-form">
                    <label>Judul</label>
                    <input type="text" name="title" required
                           value="<?= htmlspecialchars($article['title'] ?? ''); ?>">

                    <label>Slug (URL)</label>
                    <input type="text" name="slug"
                           placeholder="otomatis dari judul jika dikosongkan"
                           value="<?= htmlspecialchars($article['slug'] ?? ''); ?>">

                    <label>Kategori</label>
                    <select name="category">
                        <option value="artikel"   <?= ($article['category'] ?? '') === 'artikel'   ? 'selected' : ''; ?>>Artikel</option>
                        <option value="faq"       <?= ($article['category'] ?? '') === 'faq'       ? 'selected' : ''; ?>>FAQ</option>
                        <option value="self_help" <?= ($article['category'] ?? '') === 'self_help' ? 'selected' : ''; ?>>Self-help</option>
                    </select>

                    <label>Konten</label>
                    <textarea name="content" rows="10"><?= htmlspecialchars($article['content'] ?? ''); ?></textarea>

                    <label>
                        <input type="checkbox" name="is_published"
                               <?= !empty($article['is_published']) ? 'checked' : ''; ?>>
                        Publish
                    </label>

                    <button type="submit" class="btn btn-confirm">
                        Simpan
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
