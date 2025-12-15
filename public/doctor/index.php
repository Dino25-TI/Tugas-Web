<?php
require_once __DIR__ . '/../../includes/db.php';
$config = require __DIR__ . '/../../includes/config.php';
$base   = rtrim($config['base_url'], '/');

session_start();

// FLASH MESSAGE
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_info    = $_SESSION['flash_info'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_info']);

// Pastikan login sebagai psikolog
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'psychologist') {
    header("Location: {$base}/login.php");
    exit;
}

// Ambil ID dokter dari session
$doctor_id = (int)($_SESSION['doctor_id'] ?? 0);
if ($doctor_id <= 0) {
    die('Akun ini belum terhubung ke data psikolog.');
}

// Update aktivitas dokter
$updSeen = $pdo->prepare("UPDATE doctors SET last_seen_at = NOW() WHERE id = ?");
$updSeen->execute([$doctor_id]);

// Data profil dokter
$docStmt = $pdo->prepare("SELECT display_name FROM doctors WHERE id = ?");
$docStmt->execute([$doctor_id]);
$doctorProfile = $docStmt->fetch(PDO::FETCH_ASSOC);

// Booking aktif / mendatang
$today = date('Y-m-d 00:00:00');
$stmt  = $pdo->prepare("
    SELECT b.id, b.scheduled_at, b.consultation_type, b.status,
           b.meet_link, b.duration_minutes, u.full_name
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    WHERE b.doctor_id = ?
      AND b.scheduled_at >= ?
      AND b.status IN ('awaiting_confirmation','pending','approved','confirmed','in_session')
    ORDER BY b.scheduled_at ASC
");

$stmt->execute([$doctor_id, $today]);
$upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Riwayat pasien (ringkas: 5 terakhir)
$hist = $pdo->prepare("
    SELECT b.id, b.scheduled_at, b.consultation_type,
           u.full_name
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    WHERE b.doctor_id = ?
      AND b.status = 'done'
    ORDER BY b.scheduled_at DESC
    LIMIT 5
");
$hist->execute([$doctor_id]);
$history = $hist->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Dashboard Psikolog</title>
    <link rel="stylesheet" href="<?= $base; ?>/assets/doctor_dashboard.css">
</head>
<body>
<div class="doclayout">

    <!-- TOPBAR ATAS -->
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

    <!-- SIDEBAR KIRI -->
    <aside class="doc-sidebar">
        <nav class="doc-menu">
            <a href="<?= $base; ?>/doctor/index.php" class="active">
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

    <!-- AREA KANAN -->
    <div class="doc-main">

        <!-- HERO: sapaan + 4 kartu -->
        <div class="doc-hero">
            <header class="docdash-header">
                <div>
                    <h1>Halo, <?= htmlspecialchars($doctorProfile['display_name'] ?? 'Psikolog'); ?></h1>
                    <p>Ini jadwal konsultasi dan riwayat pasienmu.</p>
                </div>
                <a href="<?= $base; ?>/logout.php" class="btn-logout">Logout</a>
            </header>

            <section class="doc-stats">
                <div class="stat-card purple">
                    <div class="stat-icon-wrap stat-icon-green">
                        <!-- icon orang -->
                        <svg width="20" height="20" viewBox="0 0 24 24">
                            <circle cx="12" cy="9" r="3.2" fill="#16a34a" />
                            <path d="M6 18c0-2.2 2.2-4 6-4s6 1.8 6 4"
                                  fill="none" stroke="#16a34a" stroke-width="1.6" stroke-linecap="round" />
                        </svg>
                    </div>
                    <div class="stat-main">
                        <div class="stat-label">Sesi mendatang</div>
                        <div class="stat-value"><?= count($upcoming); ?></div>
                        <div class="stat-pill">Semua jadwal yang akan datang</div>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon-wrap stat-icon-green">
                        <!-- icon checklist -->
                        <svg width="20" height="20" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="8" fill="#bbf7d0" />
                            <path d="M8.5 12.5 11 15l4.5-5.5"
                                  fill="none" stroke="#16a34a" stroke-width="1.8"
                                  stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <div class="stat-main">
                        <div class="stat-label">Riwayat selesai</div>
                        <div class="stat-value"><?= count($history); ?></div>
                        <div class="stat-pill">Konsultasi sudah selesai</div>
                    </div>
                </div>

                <div class="stat-card blue">
                    <div class="stat-icon-wrap stat-icon-blue">
                        <!-- icon monitor/chat -->
                        <svg width="20" height="20" viewBox="0 0 24 24">
                            <rect x="5" y="7" width="14" height="9" rx="1.5"
                                  fill="none" stroke="#2563eb" stroke-width="1.6" />
                            <line x1="10" y1="18.5" x2="14" y2="18.5"
                                  stroke="#2563eb" stroke-width="1.6" stroke-linecap="round" />
                        </svg>
                    </div>
                    <div class="stat-main">
                        <div class="stat-label">Chat / Video aktif</div>
                        <div class="stat-value">
                            <?= count(array_filter($upcoming, fn($b) => in_array($b['consultation_type'], ['chat','video','both'], true))); ?>
                        </div>
                        <div class="stat-pill" style="color:#2563eb;background:#dbeafe;">
                            Sedang berlangsung / menunggu
                        </div>
                    </div>
                </div>

                <div class="stat-card orange">
                    <div class="stat-icon-wrap stat-icon-orange">
                        <!-- icon status -->
                        <svg width="20" height="20" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="8" fill="#ffedd5" />
                            <circle cx="12" cy="12" r="4" fill="#f97316" />
                        </svg>
                    </div>
                    <div class="stat-main">
                        <div class="stat-label">Status</div>
                        <div class="stat-value">Online</div>
                        <div class="stat-pill" style="color:#f97316;background:#ffedd5;">
                            Bisa menerima konsultasi
                        </div>
                    </div>
                </div>
            </section>

        </div>

        <!-- FLASH TOAST -->
        <?php if ($flash_success): ?>
            <div class="toast-success"><?= htmlspecialchars($flash_success); ?></div>
        <?php endif; ?>

        <?php if ($flash_info): ?>
            <div class="toast-info"><?= htmlspecialchars($flash_info); ?></div>
        <?php endif; ?>

        <!-- GRID PANEL KONTEN -->
        <main class="doc-grid">

            <!-- PANEL JADWAL -->
            <section class="doc-panel">
                <h2>Jadwal Konsultasi Mendatang</h2>

                <?php if ($upcoming): ?>
                    <div class="schedule-header">
                        <span>Pasien</span>
                        <span>Tanggal</span>
                        <span>Tipe</span>
                        <span>Status</span>
                        <span>Aksi</span>
                    </div>

                    <div class="schedule-list">
                        <?php foreach ($upcoming as $bk): ?>
                            <?php
    // hitung end time untuk timer
    $scheduledRaw = $bk['scheduled_at'];
    $duration     = (int)($bk['duration_minutes'] ?? 0);
    $endIso       = '';
    if ($scheduledRaw && $duration > 0) {
        $endObj = new DateTime($scheduledRaw);
        $endObj->modify("+{$duration} minutes");
        $endIso = $endObj->format('Y-m-d H:i:s');
    }
    ?>
                            <div class="schedule-row">
                                <div class="col-patient">
                                    <div class="bk-name"><?= htmlspecialchars($bk['full_name']); ?></div>
                                </div>

                                <div class="col-date">
                                    <?= date('d M Y H:i', strtotime($bk['scheduled_at'])); ?>
                                </div>

                                <div class="col-type">
                                    <?= htmlspecialchars($bk['consultation_type']); ?>
                                </div>

                                <div class="col-status">
                                    <?= htmlspecialchars($bk['status']); ?>
                                </div>

                                <div class="col-actions">
    <?php if (in_array($bk['consultation_type'], ['video', 'both'], true)): ?>
        <?php if (!empty($bk['meet_link'])): ?>
            <a class="tag-link"
               href="<?= htmlspecialchars($bk['meet_link']); ?>"
               target="_blank" rel="noopener">
               Buka Google Meet
            </a>
        <?php else: ?>
            <span class="tag-link disabled">Link Meet belum diisi</span>
        <?php endif; ?>

        <?php if ($endIso): ?>
            <div class="timer-small">
                Sisa: <span class="countdown"
                            data-end="<?= htmlspecialchars($endIso); ?>"></span>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (in_array($bk['consultation_type'], ['chat', 'both'], true)
        && in_array($bk['status'], ['approved', 'in_session'], true)): ?>
        <a class="tag-link" href="<?= $base; ?>/doctor_chat.php?booking_id=<?= $bk['id']; ?>">
            Mulai Chat
        </a>
    <?php endif; ?>
</div>

                            </div>

                            <div class="bk-actions bk-actions-full">
                                <?php if (in_array($bk['status'], ['awaiting_confirmation', 'pending'], true)): ?>
                                    <form method="post" action="<?= $base; ?>/doctor/confirm_booking.php">
                                        <input type="hidden" name="booking_id" value="<?= $bk['id']; ?>">
                                        <button class="btn" type="submit" name="action" value="accept">Setujui</button>
                                        <button class="btn-outline" type="submit" name="action" value="reject" style="margin-left:4px;">
                                            Tolak
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if (in_array($bk['consultation_type'], ['video', 'both'], true)): ?>
                                    <form method="post"
                                        action="<?= $base; ?>/doctor/start_session.php"
                                        style="margin-top:6px;">
                                        <input type="hidden" name="booking_id" value="<?= $bk['id']; ?>">
                                        <button class="btn" type="submit">
                                            Mulai Sesi &amp; Lihat Timer
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if (in_array($bk['status'], ['approved', 'in_session'], true)): ?>
                                    <form method="post"
                                        action="<?= $base; ?>/doctor/finish_booking.php"
                                        style="margin-top:6px;">
                                        <input type="hidden" name="booking_id" value="<?= $bk['id']; ?>">
                                        <button class="btn" type="submit" style="background:#ef4444;color:white;">
                                            Akhiri Sesi
                                        </button>
                                    </form>
                                <?php endif; ?>

                                    <?php if (in_array($bk['consultation_type'], ['video', 'both'], true)): ?>
        <a class="btn-outline"
           href="<?= $base; ?>/doctor/set_meet_link.php?booking_id=<?= $bk['id']; ?>"
           style="margin-top:6px;display:inline-block;">
           Atur Link Meet
        </a>
    <?php endif; ?>

                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>Belum ada jadwal konsultasi mendatang.</p>
                <?php endif; ?>
            </section>

        </main>
    </div>
</div>

<script>
function toggleSidebar() {
  const layout  = document.querySelector('.doclayout');
  const sidebar = document.querySelector('.doc-sidebar');

  layout.classList.toggle('sidebar-open');
  sidebar.classList.toggle('expand');
}
document.addEventListener('DOMContentLoaded', () => {
  const els = document.querySelectorAll('.countdown');

  function tick() {
    const now = Date.now();
    els.forEach(el => {
      const endStr = el.dataset.end;
      if (!endStr) return;
      const end = new Date(endStr.replace(' ', 'T')).getTime();
      const diff = end - now;

      if (diff <= 0) {
        el.textContent = '00:00';
        return;
      }

      const totalSec = Math.floor(diff / 1000);
      const minutes  = Math.floor(totalSec / 60);
      const seconds  = totalSec % 60;

      el.textContent =
        minutes.toString().padStart(2, '0') + ':' +
        seconds.toString().padStart(2, '0');
    });
  }

  tick();
  setInterval(tick, 1000);
});
</script>
</body>
</html>
