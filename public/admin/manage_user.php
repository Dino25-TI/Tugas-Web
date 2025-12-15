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
$adminName = $_SESSION['full_name'] ?? 'Admin';
// Ambil & cari user (tanpa kolom status_akun)
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
// setup pagination
$perPage = 10; // user per halaman
$page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset  = ($page - 1) * $perPage;

// hitung total baris
if ($keyword !== '') {
    $countSql  = "SELECT COUNT(*) FROM users
                  WHERE full_name LIKE :kw OR email LIKE :kw";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute([':kw' => "%{$keyword}%"]);
} else {
    $countSql  = "SELECT COUNT(*) FROM users";
    $countStmt = $pdo->query($countSql);
}
$totalRows  = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset  = ($page - 1) * $perPage;
if ($keyword !== '') {
    $sql = "SELECT id, full_name AS nama, email, created_at
            FROM users
            WHERE full_name LIKE :kw OR email LIKE :kw
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':kw', "%{$keyword}%", PDO::PARAM_STR);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $sql = "SELECT id, full_name AS nama, email, created_at
            FROM users
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
}
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$start = ($page - 1) * $perPage + 1;
$end = min($page * $perPage, $totalRows);

// untuk tandai menu aktif
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola User - RASA</title>
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/admin.css">
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
            <div class="topbar-title">Kelola User</div>
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
                    <!-- icon orang keluar pintu -->
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <!-- pintu -->
                        <rect x="4" y="3" width="9" height="18" rx="2"></rect>
                        <!-- handle -->
                        <circle cx="11" cy="12" r="0.8" fill="white"></circle>
                        <!-- panah keluar -->
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
    $activeMenu = 'user';      // tandai menu aktif
    include 'sidebar.php';     // panggil sidebar standar
    ?>

    <!-- Main content -->
    <main class="admin-main">
        <div class="table-card">
            <div class="table-header">
                    <h3>Daftar User</h3>
                <div class="table-header-right">
            <span class="table-meta">
            Menampilkan <?= $start; ?>–<?= $end; ?> dari <?= $totalRows; ?> user
            </span>
        <form method="get" class="admin-search">
            <input type="text" name="q" class="search-input"
                   placeholder="Cari nama / email"
                   value="<?= htmlspecialchars($keyword); ?>">
            <button type="submit" class="btn btn-confirm search-btn">Cari</button>
        </form>
    </div>
</div>


            <div class="table-wrapper table-scroll-user">
                <table class="table-admin">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Tanggal Daftar</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                        
<?php foreach ($users as $u): ?>
    <tr>
        <td><?= htmlspecialchars($u['id'] ?? ''); ?></td>
        <td><?= htmlspecialchars($u['nama'] ?? ''); ?></td>
        <td><?= htmlspecialchars($u['email'] ?? ''); ?></td>
        <td><?= htmlspecialchars($u['created_at'] ?? ''); ?></td>
        <td>
            <?php if (!empty($u['id'])): ?>
                <a href="riwayat_user.php?user_id=<?= (int)$u['id']; ?>" class="btn btn-view">
                    Riwayat
                </a>
                <a class="btn-edit" href="<?= $base; ?>/admin/user_edit.php?user_id=<?= (int)$u['id']; ?>">
    ✏️ <span>Edit</span>
</a>
            <?php else: ?>
                <span class="text-muted">Tidak ada riwayat</span>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>
</tbody>

                </table>
            </div>
            <?php if ($totalPages > 1): ?>
    <div class="admin-pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page-1; ?>&q=<?= urlencode($keyword); ?>" class="page-dot">&lsaquo;</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == $page): ?>
                <span class="page-dot active"><?= $i; ?></span>
            <?php else: ?>
                <a href="?page=<?= $i; ?>&q=<?= urlencode($keyword); ?>" class="page-dot"><?= $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page+1; ?>&q=<?= urlencode($keyword); ?>" class="page-dot">&rsaquo;</a>
        <?php endif; ?>
    </div>
    
    
<?php endif; ?>


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

