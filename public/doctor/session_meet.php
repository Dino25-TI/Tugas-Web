<?php
session_start();
date_default_timezone_set('Asia/Makassar');
require_once __DIR__.'/../../includes/db.php';

$booking_id = $_GET['booking_id'] ?? 0;
if (!$booking_id /* || !isset($_SESSION['user_id']) */) {
    die('Akses ditolak');
}

// Ambil langsung dari bookings saja (tanpa JOIN dulu)
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking || !$booking['meet_link']) {
    die('Booking video call tidak ditemukan atau link Meet belum disiapkan');
}

$now           = new DateTime();
$scheduledAt   = new DateTime($booking['scheduled_at']);
$duration      = $booking['duration_minutes'] ?? 60;
$endTime       = (clone $scheduledAt)->add(new DateInterval("PT{$duration}M"));
$sessionStartedAt = $booking['session_started_at'] ? new DateTime($booking['session_started_at']) : null;
$tooEarly      = $now < $scheduledAt;
$expired       = $sessionStartedAt && $now > (clone $sessionStartedAt)->add(new DateInterval("PT{$duration}M"));

// Dokter manual start (kalau mau dipakai)
if (!$sessionStartedAt && !$tooEarly && isset($_GET['start_session'])) {
    $pdo->prepare("UPDATE bookings SET session_started_at=NOW() WHERE id=?")->execute([$booking_id]);
    header("Location: session_meet.php?booking_id=$booking_id");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Video Call Dokter - Booking #<?= (int)$booking_id ?></title>
    <meta charset="UTF-8">
    <style>
        body{font-family:Arial;max-width:500px;margin:50px auto;padding:20px;background:#f0f8ff;}
        .card{background:#fff;padding:30px;border-radius:15px;box-shadow:0 5px 15px rgba(0,0,0,0.1);text-align:center;}
        .btn{background:#4285f4;color:white;padding:15px 30px;text-decoration:none;border-radius:25px;
             font-size:16px;display:inline-block;box-shadow:0 3px 10px rgba(66,133,244,0.3);}
        .btn:hover{background:#3367d6;transform:translateY(-2px);}
        .btn-start{background:#34a853;color:white;}
        .timer{font-size:2em;color:#4285f4;font-weight:bold;margin:20px 0;}
        .status{color:#666;font-size:1.2em;}
    </style>
</head>
<body>
<div class="card">
    <h1>🏥 Sesi Video Call Dokter</h1>
    <h2>Pasien: Booking #<?= (int)$booking_id ?></h2>

    <?php if ($tooEarly): ?>
        <div class="status">⏳ Belum waktunya</div>
        <div class="timer">Mulai: <?= $scheduledAt->format('H:i:s') ?></div>
        <p>Silakan klik "Mulai Sesi" saat waktunya tiba</p>
        <a href="?booking_id=<?= $booking_id ?>&start_session=1" class="btn btn-start">🚀 Mulai Sesi</a>

    <?php elseif ($expired): ?>
        <div class="status">✅ Sesi Selesai</div>
        <div class="timer"><?= $duration ?> menit telah berlalu</div>
        <p>Sesi dengan pasien selesai. Terima kasih!</p>
        <a href="index.php" class="btn">← Dashboard Dokter</a>

    <?php else: ?>
        <div class="status">▶️ Sesi Berlangsung</div>
        <div class="timer" id="timer">--:--</div>
        <p style="margin:20px 0;color:#666;">
            Durasi: <?= $duration ?> menit |
            Mulai: <?= $sessionStartedAt ? $sessionStartedAt->format('H:i:s') : $scheduledAt->format('H:i:s') ?>
        </p>
        <a href="<?= htmlspecialchars($booking['meet_link']) ?>" target="_blank" class="btn" id="meetBtn">
            🖥️ Buka Google Meet (sebagai Dokter)
        </a>
        <p style="margin-top:20px;font-size:0.9em;color:#888;">
            * Buka di tab baru. Jangan tutup halaman ini untuk melihat timer
        </p>
    <?php endif; ?>
</div>

<?php if (!$expired && !$tooEarly): ?>
<script>
let remaining = <?= ($endTime->getTimestamp() - $now->getTimestamp()) ?>;
const timerEl = document.getElementById('timer');
const meetBtn = document.getElementById('meetBtn');

function updateTimer() {
    if (remaining <= 0) {
        if (timerEl) {
            timerEl.textContent = '00:00';
            timerEl.style.color = '#ea4335';
        }
        if (meetBtn) meetBtn.remove();
        const status = document.querySelector('.status');
        if (status) status.textContent = '✅ Sesi Selesai';
        setTimeout(()=>location.reload(), 2000);
        return;
    }
    if (timerEl) {
        let minutes = Math.floor(remaining / 60);
        let seconds = remaining % 60;
        timerEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }
    remaining--;
}
setInterval(updateTimer, 1000);
updateTimer();
</script>
<?php endif; ?>
</body>
</html>
