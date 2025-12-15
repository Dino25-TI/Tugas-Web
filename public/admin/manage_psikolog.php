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

// Ambil & cari psikolog
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($keyword !== '') {
    $sql = "SELECT id, display_name AS nama, email, specialties, hourly_price
            FROM doctors
            WHERE display_name LIKE :kw OR email LIKE :kw
            ORDER BY display_name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':kw' => "%{$keyword}%"]);
} else {
    $sql = "SELECT id, display_name AS nama, email, specialties, hourly_price, status
            FROM doctors
            ORDER BY display_name ASC";
    $stmt = $pdo->query($sql);
}
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
// pagination setup
$perPage = 10;
$page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset  = ($page - 1) * $perPage;

// hitung total data (untuk semua / hasil cari)
if ($keyword !== '') {
    $countSql  = "SELECT COUNT(*) FROM doctors
                  WHERE display_name LIKE :kw OR email LIKE :kw";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute([':kw' => "%{$keyword}%"]);
} else {
    $countSql  = "SELECT COUNT(*) FROM doctors";
    $countStmt = $pdo->query($countSql);
}
$totalRows  = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

// koreksi page kalau kelewatan
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// ambil data dokter dengan LIMIT/OFFSET
if ($keyword !== '') {
    $sql = "SELECT id, display_name AS nama, email, specialties, hourly_price, status
            FROM doctors
            WHERE display_name LIKE :kw OR email LIKE :kw
            ORDER BY display_name ASC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':kw', "%{$keyword}%", PDO::PARAM_STR);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $sql = "SELECT id, display_name AS nama, email, specialties, hourly_price, status
            FROM doctors
            ORDER BY display_name ASC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
}
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// hitung range tampilan
$start = $totalRows ? $offset + 1 : 0;
$end   = min($offset + $perPage, $totalRows);

// tandai menu aktif
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Psikolog - RASA</title>
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
            <div class="topbar-title">Kelola Profil Psikolog</div>
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
    <!-- Sidebar -->        
    <?php
    $activeMenu = 'psikolog';      // tandai menu aktif
    include 'sidebar.php';     // panggil sidebar standar
    ?>
    <!-- Main content -->
    <main class="admin-main">
          <div class="table-card">
            <div class="table-header">
        <h3>Daftar Psikolog</h3>

        <div class="table-header-right">
            <span class="table-meta">
                Menampilkan <?= $start; ?>–<?= $end; ?> dari <?= $totalRows; ?> psikolog
            </span>

            <form method="get" class="admin-search">
    <div class="search-group">
        <span class="search-icon" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="6"
                        stroke="#9CA3AF" stroke-width="1.6" fill="none" />
                <line x1="15" y1="15" x2="20" y2="20"
                      stroke="#9CA3AF" stroke-width="1.6" stroke-linecap="round" />
            </svg>
        </span>

        <input type="text"
               name="q"
               class="search-input"
               placeholder="Cari nama / email"
               value="<?= htmlspecialchars($keyword); ?>">
    </div>
    <button type="submit" class="btn btn-confirm search-btn">Cari</button>
</form>

        </div>
    </div>
            <div class="table-wrapper">
                <table class="table-admin">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Spesialisasi</th>
                        <th>Tarif / Jam</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($doctors as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['id'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($d['nama'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($d['email'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($d['specialties'] ?? ''); ?></td>
                            <td>
                                <?php if (!is_null($d['hourly_price'])): ?>
                                    Rp<?= number_format((float)$d['hourly_price'], 0, ',', '.'); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
    <a href="edit_psikolog.php?id=<?= $d['id']; ?>" class="btn btn-warning">
        Edit
    </a>

    <form method="post"
          action="toggle_psikolog_status.php"
          style="display:inline-block; margin-left:6px;"
          onsubmit="return confirm('Ubah status psikolog ini?');">
        <input type="hidden" name="doctor_id" value="<?= $d['id']; ?>">
        <?php if (($d['status'] ?? 'active') === 'active'): ?>
            <input type="hidden" name="status" value="inactive">
            <button type="submit" class="btn btn-danger">
                Non-aktifkan
            </button>
        <?php else: ?>
            <input type="hidden" name="status" value="active">
            <button type="submit" class="btn btn-confirm">
                Aktifkan
            </button>
        <?php endif; ?>
    </form>
</td>

                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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
