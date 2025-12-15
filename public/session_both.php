<?php
require_once __DIR__ . '/../includes/db.php';
$config = require __DIR__ . '/../includes/config.php';
$base   = rtrim($config['base_url'], '/');

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: {$base}/login.php");
    exit;
}
if (($_SESSION['role'] ?? '') === 'admin') {
    header("Location: {$base}/admin/index.php");
    exit;
}

$user_id    = (int)$_SESSION['user_id'];
$booking_id = (int)($_GET['booking_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT b.*, d.display_name
    FROM bookings b
    LEFT JOIN doctors d ON d.id = b.doctor_id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    die('Booking tidak ditemukan atau bukan milik Anda.');
}

if ($booking['consultation_type'] !== 'both') {
    die('Halaman ini hanya untuk paket chat + video.');
}

$meet_link = $booking['meet_link'] ?? '';
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pilih Mode Konsultasi</title>
    <link rel="stylesheet" href="<?= $base; ?>/assets/sesi.css">
</head>
<body>
<div class="container">
  <div class="card">
    <h3>Konsultasi dengan <?= htmlspecialchars($booking['display_name']); ?></h3>

    <p>
      Jadwal:
      <strong><?= htmlspecialchars($booking['scheduled_at']); ?></strong>
    </p>

    <p style="margin-top:12px;">
      Silakan pilih mau mulai lewat chat atau video call terlebih dahulu.
    </p>

    <p style="margin-top:12px;">
      <a class="btn" href="<?= $base; ?>/chat.php?booking_id=<?= $booking_id; ?>">
        Masuk Chat
      </a>
    </p>

    <p style="margin-top:8px;">
      <?php if (!empty($meet_link)): ?>
        <a class="btn alt" href="<?= htmlspecialchars($meet_link); ?>" target="_blank" rel="noopener">
          Buka Video Call
        </a>
      <?php else: ?>
        <span style="color:#b91c1c;">
          Link video call belum diatur oleh psikolog. Silakan mulai dari chat atau cek lagi nanti.
        </span>
      <?php endif; ?>
    </p>

    <p class="end-session" style="margin-top:24px;">
      <a class="btn alt" href="<?= $base; ?>/dashboard.php">⬅ Kembali ke Dashboard</a>
    </p>
  </div>
</div>
</body>
</html>
