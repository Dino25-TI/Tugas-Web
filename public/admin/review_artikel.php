<?php
session_start();
require __DIR__ . '/../../includes/db.php';
$config = require __DIR__ . '/../../includes/config.php';
$base   = rtrim($config['base_url'], '/');

// cek admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: {$base}/login.php");
    exit;
}
$adminName = $_SESSION['full_name'] ?? 'Admin';
// handle approve / reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int) ($_POST['article_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $reason = trim($_POST['rejected_reason'] ?? '');

    if ($id > 0 && ($action === 'approve' || $action === 'reject')) {

        if ($action === 'approve') {
            $sql = "UPDATE articles
                    SET status = 'approved',
                        is_published = 1,
                        published_at = CASE WHEN published_at IS NULL THEN NOW() ELSE published_at END,
                        rejected_reason = NULL
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);

            $_SESSION['flash_review'] = 'Artikel berhasil disetujui dan dipublish.';
        } else {
            $sql = "UPDATE articles
                    SET status = 'rejected',
                        is_published = 0,
                        rejected_reason = :reason
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id'     => $id,
                ':reason' => $reason,
            ]);

            $_SESSION['flash_review'] = 'Artikel ditolak dan alasan tersimpan untuk dokter.';
        }

        header("Location: {$base}/admin/review_artikel.php");
        exit;
    }
}

// ambil artikel pending
$stmt = $pdo->query("
    SELECT a.id, a.title, a.category, a.created_at, a.status,
           a.rejected_reason,
           d.display_name AS doctor_name
    FROM articles a
    LEFT JOIN doctors d ON d.id = a.author_doctor_id
    WHERE a.status = 'pending_review'
    ORDER BY a.created_at DESC
");
$pending = $stmt->fetchAll(PDO::FETCH_ASSOC);

$flash = $_SESSION['flash_review'] ?? '';
unset($_SESSION['flash_review']);

$currentPath = $_SERVER['REQUEST_URI'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Review Artikel Dokter - RASA Admin</title>
    <link rel="stylesheet" href="<?= $base; ?>/assets/admin.css">
    <style>
        .review-form textarea {
            width: 100%;
            min-height: 70px;
            padding: 6px 8px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            font-size: 0.85rem;
            resize: vertical;
        }
        .btn-approve {
            background: #22c55e;
            color: #fff;
        }
        .btn-approve:hover {
            background: #16a34a;
        }
        .btn-reject {
            background: #fee2e2;
            color: #991b1b;
        }
        .btn-reject:hover {
            background: #fecaca;
        }
        .flash-box {
            margin-bottom: 16px;
            padding: 10px 14px;
            border-radius: 12px;
            background: #dcfce7;
            color: #166534;
            font-size: 0.9rem;
        }
    </style>
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
            <div class="topbar-title">Review Artikel Dokter</div>
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
    $activeMenu = 'artikel';      // tandai menu aktif
    include 'sidebar.php';     // panggil sidebar standar
    ?>
    <main class="admin-main">
        
        <?php if ($flash): ?>
            <div class="flash-box"><?= htmlspecialchars($flash); ?></div>
        <?php endif; ?>

        <div class="table-card">
            <div class="table-header">
                <h3>Artikel Pending Review</h3>
            </div>

            <div class="table-wrapper">
                <table class="table-admin">
                    <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Psikolog</th>
                        <th>Kategori</th>
                        <th>Dikirim</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($pending)): ?>
                        <tr>
                            <td colspan="5">Belum ada artikel yang menunggu review.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pending as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['title']); ?></td>
                                <td><?= htmlspecialchars($row['doctor_name'] ?? '-'); ?></td>
                                <td><?= htmlspecialchars(ucfirst(str_replace('_',' ', $row['category']))); ?></td>
                                <td><?= htmlspecialchars($row['created_at'] ?? ''); ?></td>
                                <td>
                                    <form method="post" class="review-form">
                                        <input type="hidden" name="article_id" value="<?= (int)$row['id']; ?>">
                                        <textarea name="rejected_reason"
                                                  placeholder="Alasan penolakan (isi jika akan ditolak)"></textarea>
                                        <div style="margin-top:6px; display:flex; gap:6px;">
                                            <button type="submit" name="action" value="approve"
                                                    class="btn btn-confirm btn-approve">
                                                Approve & Publish
                                            </button>
                                            <button type="submit" name="action" value="reject"
                                                    class="btn btn-reject">
                                                Reject
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
