<?php
require_once __DIR__.'/../includes/db.php';
$config = require __DIR__.'/../includes/config.php';
$base   = $config['base_url'];

session_start();

// wajib login
if (!isset($_SESSION['user_id'])) {
    header("Location: {$base}/login.php");
    exit;
}

// blokir admin dari halaman user
if (($_SESSION['role'] ?? '') === 'admin') {
    header("Location: {$base}/admin/index.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Booking aktif
$stmt = $pdo->prepare("
    SELECT b.*, d.display_name 
    FROM bookings b 
    LEFT JOIN doctors d ON d.id = b.doctor_id 
    WHERE b.user_id = ? 
      AND b.user_hidden = 0
      AND b.status IN ('pending','awaiting_confirmation','approved')
    ORDER BY b.created_at DESC
");
$stmt->execute([$user_id]);
$activeBks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Riwayat booking
$stmt = $pdo->prepare("
    SELECT b.*, d.display_name 
    FROM bookings b 
    LEFT JOIN doctors d ON d.id = b.doctor_id 
    WHERE b.user_id = ? 
      AND b.user_hidden = 0
      AND b.status IN ('done','cancelled')
    ORDER BY b.created_at DESC
");
$stmt->execute([$user_id]);
$historyBks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Dashboard - RASA</title>
    <link rel="stylesheet" href="<?= $base; ?>/assets/dashboard.css">
</head>
<body>
<div class="container">
  <div class="card">
    <h3>Dashboard</h3>

    <p>
      <a class="btn" href="<?= $base; ?>/index.php">🏠 Beranda</a>
      <a class="btn" href="<?= $base; ?>/doctors.php">Book baru</a>
      <a class="btn alt" href="<?= $base; ?>/pengantar_test.php">Test Gratis</a>
    </p>

    <h4>Booking Aktif</h4>

    <div class="bk-container">
    <?php foreach ($activeBks as $bk): ?>
        <?php
        $statusLabel = ucfirst(str_replace('_', ' ', $bk['status']));
        $paid        = ($bk['payment_status'] ?? 'unpaid') === 'paid';

        $scheduledRaw = $bk['scheduled_at'];
        $scheduledAt  = $scheduledRaw
            ? date('d M Y H:i', strtotime($scheduledRaw))
            : '-';

        // durasi & jam selesai
        $duration = (int)($bk['duration_minutes'] ?? 0);
        $endTime  = '-';
        if ($scheduledRaw && $duration > 0) {
            $end = new DateTime($scheduledRaw);
            $end->modify("+{$duration} minutes"); // tambah menit sesuai paket[web:166][web:176]
            $endTime = $end->format('H:i');
        }

        $paidAt = !empty($bk['payment_confirmed_at'])
            ? date('d M Y H:i', strtotime($bk['payment_confirmed_at']))
            : null;

        $canJoin     = in_array($bk['status'], ['approved','in_session']) && $paid;
        $consultType = $bk['consultation_type'] ?? 'chat'; // chat, video, both
        ?>
        <div class="bk">
          <div class="bk-top">
            <strong><?= htmlspecialchars($bk['display_name']); ?></strong>

            <span class="<?= in_array($bk['status'], ['approved','done']) ? 'online-dot' : 'offline-dot'; ?>">
              <?= htmlspecialchars($statusLabel); ?>
            </span>

            <form method="post" action="<?= $base; ?>/hide_booking.php" class="bk-close-form">
              <input type="hidden" name="booking_id" value="<?= $bk['id']; ?>">
              <button type="submit" class="bk-close-btn">×</button>
            </form>
          </div>

          <div class="bk-info">
            Paket: <?= htmlspecialchars($bk['package']); ?><br>
            Jadwal konsultasi: <?= htmlspecialchars($scheduledAt); ?>
            <?php if ($endTime !== '-'): ?>
              (s/d <?= htmlspecialchars($endTime); ?>,
               <?= (int)$bk['duration_minutes']; ?> menit)
            <?php endif; ?>
            <br>
            <?php if ($paid): ?>
              Pembayaran: <strong>Berhasil</strong>
              <?php if ($paidAt): ?>
                (<?= htmlspecialchars($paidAt); ?>)
              <?php endif; ?>
            <?php else: ?>
              Pembayaran: <strong>Belum dibayar</strong>
            <?php endif; ?>
          </div>

          <div class="bk-action">
            <?php if ($canJoin): ?>
              <?php if ($consultType === 'chat'): ?>
                <a class="btn" href="<?= $base; ?>/chat.php?doctor_id=<?= $bk['doctor_id']; ?>&booking_id=<?= $bk['id']; ?>">
                  Chat Dokter
                </a>
              <?php elseif ($consultType === 'video'): ?>
    <?php if (!empty($bk['meet_link'])): ?>
      <a class="btn"
         href="<?= $base; ?>/session_meet.php?booking_id=<?= $bk['id']; ?>">
         Masuk Sesi Video
      </a>
    <?php else: ?>

                  <button class="btn" disabled>Link Meet belum tersedia</button>
                <?php endif; ?>
              <?php elseif ($consultType === 'both'): ?>
  <div class="bk-actions-row">
    <?php if (!empty($bk['meet_link'])): ?>
      <a class="btn"
         href="<?= $base; ?>/session_meet.php?booking_id=<?= $bk['id']; ?>">
         Masuk Sesi Video
      </a>
    <?php else: ?>
      <button class="btn" disabled>Link Meet belum tersedia</button>
    <?php endif; ?>

    <a class="btn alt"
       href="<?= $base; ?>/chat.php?doctor_id=<?= $bk['doctor_id']; ?>&booking_id=<?= $bk['id']; ?>">
       Chat Dokter
    </a>
  </div>

              <?php else: ?>
                <a class="btn" href="<?= $base; ?>/session.php?booking_id=<?= $bk['id']; ?>">
                  Masuk Ruang
                </a>
              <?php endif; ?>
            <?php else: ?>
              <span class="small-muted">Tunggu konfirmasi / pembayaran</span>
            <?php endif; ?>
          </div>
        </div>
    <?php endforeach; ?>
    </div>

    <h4>Riwayat Booking</h4>

    <div class="bk-container">
    <?php foreach ($historyBks as $bk): ?>
        <?php
        $statusLabel = ucfirst(str_replace('_', ' ', $bk['status']));

        $scheduledRaw = $bk['scheduled_at'];
        $scheduledAt  = $scheduledRaw
            ? date('d M Y H:i', strtotime($scheduledRaw))
            : '-';

        $duration = (int)($bk['duration_minutes'] ?? 0);
        $endTime  = '-';
        if ($scheduledRaw && $duration > 0) {
            $end = new DateTime($scheduledRaw);
            $end->modify("+{$duration} minutes");
            $endTime = $end->format('H:i');
        }
        ?>
        <div class="bk">
          <div class="bk-top">
            <strong><?= htmlspecialchars($bk['display_name']); ?></strong>
            <span class="offline-dot"><?= htmlspecialchars($statusLabel); ?></span>
          </div>
          <div class="bk-info">
            Paket: <?= htmlspecialchars($bk['package']); ?><br>
            Jadwal konsultasi: <?= htmlspecialchars($scheduledAt); ?>
            <?php if ($endTime !== '-'): ?>
              (s/d <?= htmlspecialchars($endTime); ?>,
               <?= (int)$bk['duration_minutes']; ?> menit)
            <?php endif; ?>
            <br>
          </div>
        </div>
    <?php endforeach; ?>
    </div>

  </div>
</div>

<!-- Toast notification container -->
<div id="toastContainer" class="toast-container"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const container = document.getElementById('toastContainer');
  const shownIds = new Set(); // mencegah toast duplikat

  function showToast(title, text, link) {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `
      <div class="toast-icon">⏰</div>
      <div class="toast-content">
        <div class="toast-title">${title}</div>
        <div class="toast-text">${text}</div>
      </div>
      <button class="toast-close">&times;</button>
    `;

    toast.querySelector('.toast-close').onclick = (e) => {
      e.stopPropagation();
      toast.remove();
    };

    if (link) {
      toast.style.cursor = 'pointer';
      toast.addEventListener('click', (e) => {
        if (!e.target.classList.contains('toast-close')) {
          window.location.href = link;
        }
      });
    }

    container.appendChild(toast);
    setTimeout(() => toast.remove(), 10000);
  }

  function checkUpcomingMeetings() {
    console.log('🔄 PING checkUpcomingMeetings');
    fetch('<?= $base; ?>/api/upcoming_meetings.php', {
      cache: 'no-store'
    })
      .then(r => r.json())
      .then(data => {
        if (!data.success) return;
        const nowMs = Date.now();

        data.meetings.forEach(m => {
          const start   = new Date(m.scheduled_at.replace(' ', 'T'));
          const diffMs  = start.getTime() - nowMs;
          const diffMin = diffMs / 60000;

          if (diffMin <= 5 && diffMin > 0 && !shownIds.has(m.id)) {
            shownIds.add(m.id);
            const minuteText = Math.round(diffMin);
            const link = m.meet_link || '';
            showToast(
              'Sesi video akan dimulai',
              `Anda punya sesi ${m.consultation_type === 'both' ? 'chat + video' : 'video'} dengan psikolog dalam ${minuteText} menit.`,
              link
            );
          }
        });
      })
      .catch(console.error);
  }

  checkUpcomingMeetings();
  setInterval(checkUpcomingMeetings, 10000); // 10 detik untuk testing
});
</script>

</body>
</html>
