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

// Update status tiket (jika ada aksi dari admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id'], $_POST['status'])) {
    $id     = (int) $_POST['ticket_id'];
    $status = $_POST['status'];

    if (in_array($status, ['open','in_progress','closed'], true)) {
        $sql = "UPDATE support_tickets
                SET status = :status, updated_at = NOW()
                WHERE id = :id";
        $st = $pdo->prepare($sql);
        $st->execute([
            ':status' => $status,
            ':id'     => $id,
        ]);
    }
    header("Location: {$base}/admin/komplain.php");
    exit;
}

// Ambil daftar tiket + join user & dokter (kalau ada)
$sql = "SELECT 
            t.id,
            t.subject,
            t.message,
            t.status,
            t.created_at,
            u.full_name AS user_name,
            d.display_name AS doctor_name
        FROM support_tickets t
        LEFT JOIN users   u ON u.id = t.user_id
        LEFT JOIN doctors d ON d.id = t.doctor_id
        ORDER BY 
            FIELD(t.status, 'open','in_progress','closed'),
            t.created_at DESC";

$tickets = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// tandai menu aktif
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Komplain - RASA</title>
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
            <div class="topbar-title">Kelola Komplain User</div>
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
    $activeMenu = 'komplain';      // tandai menu aktif
    include 'sidebar.php';     // panggil sidebar standar
    ?>
    <!-- Main content -->
    <main class="admin-main">
        <div class="table-card">
            <div class="table-header">
                <h3>Daftar Komplain</h3>
            </div>

            <div class="table-wrapper">
                <table class="table-admin">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pengirim</th>
                        <th>Psikolog Terkait</th>
                        <th>Subjek</th>
                        <th>Pesan</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td><?= htmlspecialchars($t['id'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($t['user_name'] ?? '-'); ?></td>
                            <td><?= htmlspecialchars($t['doctor_name'] ?? '-'); ?></td>
                            <td><?= htmlspecialchars($t['subject'] ?? ''); ?></td>
                            <td><?= nl2br(htmlspecialchars($t['message'] ?? '')); ?></td>
                            <td><?= htmlspecialchars($t['status'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($t['created_at'] ?? ''); ?></td>
                            <td class="complain-actions">
    <form method="post" action="">
        <input type="hidden" name="ticket_id" value="<?= (int)$t['id']; ?>">

        <select name="status" class="complain-select">
            <option value="open"        <?= $t['status']=='open'?'selected':''; ?>>Open</option>
            <option value="in_progress" <?= $t['status']=='in_progress'?'selected':''; ?>>Diproses</option>
            <option value="closed"      <?= $t['status']=='closed'?'selected':''; ?>>Selesai</option>
        </select>

        <button type="submit" class="complain-btn-main">
            <span class="icon">★</span>
            <span>Update</span>
        </button>
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
