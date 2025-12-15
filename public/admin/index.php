<?php
// Mulai session & include file
session_start();
require_once __DIR__ . '/../../includes/db.php';
$config = require __DIR__ . '/../../includes/config.php';
$base   = $config['base_url'];

// Cek login & role admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: {$base}/login.php");
    exit;
}

$adminName = $_SESSION['full_name'] ?? 'Nonoya';

// Pagination
$perPage = 10;
$page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset  = ($page - 1) * $perPage;

// Filter search & sort
$search = trim($_GET['q'] ?? '');
$sort   = $_GET['sort'] ?? 'newest';

$where  = '';
$params = [];

if ($search !== '') {
    $where = "WHERE u.full_name LIKE :search OR b.id LIKE :search";
    $params[':search'] = "%{$search}%";
}

$orderBy = "ORDER BY b.created_at DESC"; // default newest
if ($sort === 'oldest') {
    $orderBy = "ORDER BY b.created_at ASC";
}

// Hitung total data (ikut filter search)
$totalSql = "
    SELECT COUNT(*) 
    FROM bookings b
    LEFT JOIN users u ON u.id = b.user_id
    $where
";
$totalStmt = $pdo->prepare($totalSql);
foreach ($params as $k => $v) {
    $totalStmt->bindValue($k, $v, PDO::PARAM_STR);
}
$totalStmt->execute();
$totalRows  = (int)$totalStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

// Hitung total paid & aktif dari SEMUA bookings
$globalStmt = $pdo->query("
    SELECT
        SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) AS total_paid,
        SUM(
            CASE 
                WHEN status IN ('awaiting_confirmation','pending','approved','confirmed','in_session')
                THEN 1 ELSE 0 
            END
        ) AS total_active
    FROM bookings
");
$global      = $globalStmt->fetch(PDO::FETCH_ASSOC);
$totalPaid   = (int)($global['total_paid'] ?? 0);
$totalActive = (int)($global['total_active'] ?? 0);

// Ambil daftar booking
$sql = "
    SELECT 
        b.*,
        u.full_name    AS user_name,
        d.display_name AS doctor_name
    FROM bookings b
    LEFT JOIN users   u ON u.id = b.user_id
    LEFT JOIN doctors d ON d.id = b.doctor_id
    $where
    $orderBy
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, PDO::PARAM_STR);
}
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$bks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - RASA</title>
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/admin.css">
</head>
<body>

<!-- TOPBAR PUTIH -->
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
            <div class="topbar-sub">Hello <?= htmlspecialchars($adminName); ?> 👋</div>
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

<!-- LAYOUT BAWAH: SIDEBAR + KONTEN -->
<div class="admin-shell">

    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-menu">
            <div class="admin-menu-title">Menu</div>

            <a href="<?php echo $base; ?>/admin/index.php"
               class="admin-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/index.php') !== false) ? 'active' : ''; ?>">
                <span class="label">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24">
                            <rect x="3" y="4" width="8" height="7" rx="2"></rect>
                            <rect x="13" y="4" width="8" height="5" rx="2"></rect>
                            <rect x="3" y="13" width="8" height="7" rx="2"></rect>
                            <rect x="13" y="11" width="8" height="9" rx="2"></rect>
                        </svg>
                    </span>
                    <span class="menu-text">Booking &amp; Pembayaran</span>
                </span>
            </a>

            <a href="<?php echo $base; ?>/admin/manage_user.php"
               class="admin-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/manage_user.php') !== false) ? 'active' : ''; ?>">
                <span class="label">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24">
                            <circle cx="12" cy="9" r="3"></circle>
                            <path d="M6 18c0-2.2 2.2-4 6-4s6 1.8 6 4"
                                  fill="none" stroke-width="1.6"></path>
                        </svg>
                    </span>
                    <span class="menu-text">Kelola User</span>
                </span>
            </a>

            <a href="<?php echo $base; ?>/admin/manage_psikolog.php"
               class="admin-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/manage_psikolog.php') !== false) ? 'active' : ''; ?>">
                <span class="label">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24">
                            <rect x="6" y="8" width="12" height="9" rx="2"></rect>
                            <path d="M9 8V6.5A2.5 2.5 0 0 1 11.5 4h1A2.5 2.5 0 0 1 15 6.5V8"
                                  fill="none" stroke-width="1.6"></path>
                        </svg>
                    </span>
                    <span class="menu-text">Kelola Psikolog</span>
                </span>
            </a>

            <a href="<?php echo $base; ?>/admin/komplain.php"
               class="admin-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/komplain.php') !== false) ? 'active' : ''; ?>">
                <span class="label">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M5 6h14v8a3 3 0 0 1-3 3H9l-4 3v-3a3 3 0 0 1-3-3V6z"
                                  fill="none" stroke-width="1.6"></path>
                            <circle cx="10" cy="11" r="0.9"></circle>
                            <circle cx="14" cy="11" r="0.9"></circle>
                        </svg>
                    </span>
                    <span class="menu-text">Kelola Komplain</span>
                </span>
            </a>

            <a href="<?php echo $base; ?>/admin/laporan.php"
               class="admin-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/laporan.php') !== false) ? 'active' : ''; ?>">
                <span class="label">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M5 19V9l3-2 4 3 4-6 3 2v13H5z"
                                  fill="none" stroke-width="1.6"></path>
                        </svg>
                    </span>
                    <span class="menu-text">Laporan</span>
                </span>
            </a>

            <a href="<?= $base; ?>/admin/review_artikel.php"
               class="admin-link <?= (strpos($_SERVER['REQUEST_URI'], '/admin/review_artikel.php') !== false) ? 'active' : ''; ?>">
                <span class="label">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M7 4h7l5 5v11H7z" fill="none" stroke-width="1.6"></path>
                            <line x1="9" y1="12" x2="15" y2="12" stroke-width="1.4"></line>
                            <line x1="9" y1="15" x2="13" y2="15" stroke-width="1.4"></line>
                        </svg>
                    </span>
                    <span class="menu-text">Review Artikel Dokter</span>
                </span>
            </a>

            <a href="<?php echo $base; ?>/admin/logout_admin.php" class="admin-link">
                <span class="label">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M10 5H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h4"
                                  fill="none" stroke-width="1.6"></path>
                            <path d="M14 9l3 3-3 3" fill="none" stroke-width="1.6"></path>
                            <line x1="11" y1="12" x2="17" y2="12" stroke-width="1.6"></line>
                        </svg>
                    </span>
                    <span class="menu-text">Logout</span>
                </span>
            </a>
        </div>
    </aside>

    <!-- Main content -->
    <main class="admin-main">
        <!-- Kartu statistik -->
        <section class="admin-stats">
            <div class="stat-card">
                <div class="stat-icon stat-icon-green">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <circle cx="12" cy="9" r="3.2" fill="#16a34a" />
                        <path d="M6 18c0-2.2 2.2-4 6-4s6 1.8 6 4"
                              fill="none" stroke="#16a34a" stroke-width="1.6" stroke-linecap="round" />
                    </svg>
                </div>
                <div class="stat-main">
                    <div class="stat-label">Total Booking</div>
                    <div class="stat-value"><?= $totalRows; ?></div>
                    <div class="stat-pill">Semua waktu</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon stat-icon-green">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="8" fill="#bbf7d0" />
                        <path d="M8.5 12.5 11 15l4.5-5.5"
                              fill="none" stroke="#16a34a" stroke-width="1.8"
                              stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div class="stat-main">
                    <div class="stat-label">Booking Berhasil Dibayar</div>
                    <div class="stat-value"><?= $totalPaid; ?></div>
                    <div class="stat-pill" style="color:#16a34a;background:#dcfce7;">
                        Pembayaran sukses
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon stat-icon-blue">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <rect x="5" y="7" width="14" height="9" rx="1.5"
                              fill="none" stroke="#2563eb" stroke-width="1.6" />
                        <line x1="10" y1="18.5" x2="14" y2="18.5"
                              stroke="#2563eb" stroke-width="1.6" stroke-linecap="round" />
                    </svg>
                </div>
                <div class="stat-main">
                    <div class="stat-label">Booking Aktif</div>
                    <div class="stat-value"><?= $totalActive; ?></div>
                    <div class="stat-pill" style="color:#2563eb;background:#dbeafe;">
                        Sedang berlangsung / menunggu
                    </div>
                </div>
            </div>
        </section>

        <?php if (!empty($_SESSION['flash_meet'])): ?>
            <div class="alert-success">
                <?= htmlspecialchars($_SESSION['flash_meet']); ?>
            </div>
            <?php unset($_SESSION['flash_meet']); ?>
        <?php endif; ?>

        <!-- Tabel booking -->
        <div class="table-card">
            <div class="table-header">
                <h3>Daftar Booking</h3>

                <div class="admin-search">
                    <form method="get" class="search-group">
                        <span class="search-icon" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="6" stroke="#9CA3AF" stroke-width="1.6" fill="none" />
                                <line x1="15" y1="15" x2="20" y2="20"
                                      stroke="#9CA3AF" stroke-width="1.6" stroke-linecap="round" />
                            </svg>
                        </span>
                        <input
                            type="text"
                            name="q"
                            class="search-input"
                            placeholder="Cari user..."
                            value="<?= htmlspecialchars($_GET['q'] ?? ''); ?>">
                        <input type="hidden" name="sort" value="<?= htmlspecialchars($_GET['sort'] ?? 'newest'); ?>">
                    </form>

                    <form method="get" class="sort-group">
                        <span class="sort-label">Sort by:</span>
                        <select name="sort" onchange="this.form.submit()">
                            <option value="newest" <?= ($_GET['sort'] ?? 'newest') === 'newest' ? 'selected' : ''; ?>>Newest</option>
                            <option value="oldest" <?= ($_GET['sort'] ?? 'newest') === 'oldest' ? 'selected' : ''; ?>>Oldest</option>
                        </select>
                        <input type="hidden" name="q" value="<?= htmlspecialchars($_GET['q'] ?? ''); ?>">
                    </form>
                </div>
            </div>

            <div class="table-wrapper">
                <table class="table-admin">
                    <thead>
                    <tr>
                        <th>User</th>
                        <th>Dokter</th>
                        <th>Paket</th>
                        <th>Tanggal Booking</th>
                        <th>Status Booking</th>
                        <th>Pembayaran</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($bks as $it): ?>
                        <tr>
                            <td class="cell-user">
                                <div class="user-name">
                                    <?= htmlspecialchars($it['user_name']); ?>
                                </div>
                            </td>

                            <td><?= htmlspecialchars($it['doctor_name']); ?></td>
                            <td><?= htmlspecialchars($it['package']); ?></td>
                            <td><?= htmlspecialchars($it['created_at']); ?></td>

                            <td>
                                <?php
                                $status      = $it['status'];
                                $statusLabel = ucfirst(str_replace('_', ' ', $status));
                                ?>
                                <span class="cell-status-main">
                                    <?= htmlspecialchars($statusLabel); ?>
                                </span>

                                <?php if ($status === 'pending' || $status === 'awaiting_confirmation'): ?>
                                    <form method="post" action="confirm_booking.php">
                                        <input type="hidden" name="booking_id" value="<?= $it['id']; ?>">
                                        <button class="btn btn-confirm" type="submit">
                                            Konfirmasi Booking
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php
                                $pay        = $it['payment_status'] ?? 'unpaid';
                                $payLabel   = ucfirst($pay);
                                $confirmedAt = '';

                                if (!empty($it['payment_confirmed_at'])) {
                                    $confirmedAt = date('Y-m-d H:i', strtotime($it['payment_confirmed_at']));
                                }
                                ?>

                                <?php if ($pay === 'paid'): ?>
                                    <div class="cell-status-main">Berhasil dibayar</div>
                                    <?php if ($confirmedAt): ?>
                                        <div class="cell-status-sub">
                                            <?= htmlspecialchars($confirmedAt); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="cell-status-main">
                                        <?= htmlspecialchars($payLabel); ?>
                                    </div>
                                    <form method="post" action="confirm_payment.php">
                                        <input type="hidden" name="booking_id" value="<?= $it['id']; ?>">
                                        <button class="btn btn-pay" type="submit">
                                            Konfirmasi Bayar
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>

                            <td class="cell-actions">
                                <a href="edit_booking.php?id=<?= $it['id']; ?>" class="btn btn-action">
                                    Ubah Jadwal
                                </a>

                                <form method="post"
                                      action="hapus_booking.php"
                                      onsubmit="return confirm('Hapus booking ini? Data akan hilang permanen.');">
                                    <input type="hidden" name="booking_id" value="<?= $it['id']; ?>">
                                    <button type="submit" class="btn btn-action-danger" title="Hapus riwayat ini">
                                        ✕
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <?php
                $from    = $totalRows ? $offset + 1 : 0;
                $to      = min($offset + $perPage, $totalRows);
                $baseUrl = $base . '/admin/index.php';

                $window = 2;
                $start  = max(1, $page - $window);
                $end    = min($totalPages, $page + $window);
                ?>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;font-size:11px;color:#9ca3af;">
                    <div>
                        Showing data <?= $from; ?> to <?= $to; ?> of <?= number_format($totalRows); ?> entries
                    </div>

                    <div style="display:inline-flex;gap:4px;align-items:center;">
                        <?php if ($page > 1): ?>
                            <a class="btn" style="background:#f3f4f6;color:#111827;padding:3px 8px;border-radius:6px;"
                               href="<?= $baseUrl; ?>?page=<?= $page-1; ?>">‹</a>
                        <?php endif; ?>

                        <a class="btn"
                           style="padding:3px 8px;border-radius:6px;
                                  background:<?= $page == 1 ? '#4b5bfd' : '#f3f4f6'; ?>;
                                  color:<?= $page == 1 ? '#ffffff' : '#111827'; ?>;"
                           href="<?= $baseUrl; ?>?page=1">1</a>

                        <?php if ($start > 2): ?>
                            <span style="padding:3px 6px;color:#6b7280;">…</span>
                        <?php endif; ?>

                        <?php for ($p = $start; $p <= $end; $p++): ?>
                            <?php if ($p != 1 && $p != $totalPages): ?>
                                <a class="btn"
                                   style="padding:3px 8px;border-radius:6px;
                                          background:<?= $p == $page ? '#4b5bfd' : '#f3f4f6'; ?>;
                                          color:<?= $p == $page ? '#ffffff' : '#111827'; ?>;"
                                   href="<?= $baseUrl; ?>?page=<?= $p; ?>">
                                    <?= $p; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($end < $totalPages - 1): ?>
                            <span style="padding:3px 6px;color:#6b7280;">…</span>
                        <?php endif; ?>

                        <?php if ($totalPages > 1): ?>
                            <a class="btn"
                               style="padding:3px 8px;border-radius:6px;
                                      background:<?= $page == $totalPages ? '#4b5bfd' : '#f3f4f6'; ?>;
                                      color:<?= $page == $totalPages ? '#ffffff' : '#111827'; ?>;"
                               href="<?= $baseUrl; ?>?page=<?= $totalPages; ?>">
                                <?= $totalPages; ?>
                            </a>
                        <?php endif; ?>

                        <?php if ($page < $totalPages): ?>
                            <a class="btn" style="background:#f3f4f6;color:#111827;padding:3px 8px;border-radius:6px;"
                               href="<?= $baseUrl; ?>?page=<?= $page+1; ?>">›</a>
                        <?php endif; ?>
                    </div>
                </div>

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
