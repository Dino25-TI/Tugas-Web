<?php
session_start();
date_default_timezone_set('Asia/Makassar');
require_once __DIR__ . '/../includes/db.php';


$booking_id = $_GET['booking_id'] ?? 0;
if (!$booking_id || !isset($_SESSION['user_id'])) {
    die('Akses ditolak');
}

// Ambil booking + info dokter (sesuaikan kolom/nama tabel doktermu)
$stmt = $pdo->prepare("
    SELECT b.*, d.display_name AS doctor_name
    FROM bookings b
    JOIN doctors d ON d.id = b.doctor_id
    WHERE b.id = ? 
      AND b.user_id = ?
      AND b.consultation_type IN ('video','both')
");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$booking || !$booking['meet_link']) {
    die('Booking video call tidak ditemukan atau link Meet belum disiapkan dokter');
}

$now          = new DateTime();
$scheduledAt  = new DateTime($booking['scheduled_at']);
$duration     = $booking['duration_minutes'] ?? 60;

// Waktu mulai/selesai resmi sesi (berdasarkan session_started_at, bukan sekarang)
$sessionStartedAt = $booking['session_started_at'] ? new DateTime($booking['session_started_at']) : null;
$tooEarly   = $now < $scheduledAt;
$expired    = $sessionStartedAt && $now > (clone $sessionStartedAt)->add(new DateInterval("PT{$duration}M"));
$remainingToStart = max(0, $scheduledAt->getTimestamp() - $now->getTimestamp()); // detik ke jam mulai

// 🔥 CEK JAM KERJA DOKTER (doctor_schedules)
$hari        = (int)$now->format('N');         // 1=Senin ... 7=Minggu
$currentTime = $now->format('H:i:s');

$stmtJam = $pdo->prepare("
    SELECT start_time, end_time 
    FROM doctor_schedules
    WHERE doctor_id   = ?
      AND is_available = 1
      AND ? BETWEEN start_time AND end_time
    LIMIT 1
");
$stmtJam->execute([$booking['doctor_id'], $currentTime]);
$withinWorkHours = (bool)$stmtJam->fetch(PDO::FETCH_ASSOC);


?>
<!DOCTYPE html>
<html>
<head>
    <title>Video Call - <?= htmlspecialchars($booking['doctor_name']) ?></title>
    <meta charset="UTF-8">
    <style>
        body{font-family:Arial;max-width:500px;margin:50px auto;padding:20px;background:#f0f8ff;}
        .card{background:#fff;padding:30px;border-radius:15px;box-shadow:0 5px 15px rgba(0,0,0,0.1);text-align:center;}
        .btn{background:#4285f4;color:white;padding:15px 30px;text-decoration:none;border-radius:25px;
             font-size:16px;display:inline-block;box-shadow:0 3px 10px rgba(66,133,244,0.3);}
        .btn:hover{background:#3367d6;transform:translateY(-2px);}
        .timer{font-size:2em;color:#4285f4;font-weight:bold;margin:20px 0;}
        .status{color:#666;font-size:1.2em;}
    </style>
</head>
<body>
<div class="card">
    <h1>🏥 Sesi <?= $booking['consultation_type'] == 'video' ? 'Video Call' : 'Video + Chat' ?></h1>
    <h2>Dr. <?= htmlspecialchars($booking['doctor_name']) ?></h2>

    <?php if (!$withinWorkHours): ?>
        <!-- Di luar jam kerja dokter -->
        <div class="status">⛔ Di luar jam kerja</div>
        <p>Sesi video call hanya dapat diakses pada jam kerja dokter hari ini.</p>

    <?php elseif ($tooEarly || !$sessionStartedAt): ?>
        <!-- Belum waktunya / dokter belum mulai sesi -->
        <div class="status">⏳ Belum waktunya</div>
        <div class="timer" id="countdown">Mulai: <?= $scheduledAt->format('H:i:s') ?></div>
        <p>Silakan tunggu dokter memulai sesi pada jadwal yang ditentukan.</p>

    <?php elseif ($expired): ?>
        <!-- Sesi sudah selesai -->
        <div class="status">✅ Sesi Selesai</div>
        <div class="timer"><?= (int)$duration ?> menit telah berlalu</div>
        <p>Terima kasih telah menggunakan Rasa!</p>
        <a href="index.php" class="btn">← Kembali ke Dashboard</a>

    <?php else: ?>
        <!-- Sesi sedang berlangsung -->
        <?php
        $endTime = (clone $sessionStartedAt)->add(new DateInterval("PT{$duration}M"));
        $remainingSeconds = max(0, $endTime->getTimestamp() - $now->getTimestamp());
        ?>
        <div class="status">▶️ Sesi Berlangsung</div>
        <div class="timer" id="timer">--:--</div>
        <p style="margin:20px 0;color:#666;">
            Durasi: <?= (int)$duration ?> menit |
            Mulai: <?= $sessionStartedAt->format('H:i:s') ?>
        </p>
        <a href="<?= htmlspecialchars($booking['meet_link']) ?>" target="_blank" class="btn" id="meetBtn">
            🖥️ Buka Google Meet
        </a>
        <p style="margin-top:20px;font-size:0.9em;color:#888;">
            * Buka di tab baru. Jangan tutup halaman ini untuk melihat timer.
        </p>
    <?php endif; ?>
</div>


<?php if ($tooEarly || !$sessionStartedAt): ?>
<script>
let remainStart = <?= (int)$remainingToStart ?>; // detik sampai scheduled_at
const cdEl = document.getElementById('countdown');

function fmtStart(sec) {
    let m = Math.floor(sec / 60);
    let s = sec % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
}

function tickStart() {
    if (!cdEl) return;
    if (remainStart <= 0) {
        cdEl.textContent = 'Mulai sekarang';
        // Beri waktu 2 detik lalu reload supaya masuk ke cabang "Sesi Berlangsung"
        setTimeout(() => location.reload(), 2000);
        return;
    }
    cdEl.textContent = 'Mulai dalam: ' + fmtStart(remainStart);
    remainStart--;
}

tickStart();
setInterval(tickStart, 1000);
</script>
<?php endif; ?>

</body>
</html>
