<?php
require_once __DIR__ . '/../../includes/db.php';
$config = require __DIR__ . '/../../includes/config.php';
$base   = rtrim($config['base_url'], '/');

session_start();

// Hanya psikolog
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'psychologist') {
    header("Location: {$base}/login.php");
    exit;
}

$doctor_id  = (int)($_SESSION['doctor_id'] ?? 0);
$booking_id = (int)($_GET['booking_id'] ?? 0);

if ($doctor_id <= 0 || $booking_id <= 0) {
    die('Data tidak valid.');
}

// Ambil detail booking + pasien, pastikan milik dokter ini
$stmt = $pdo->prepare("
    SELECT b.id, b.scheduled_at, b.consultation_type, b.status,
           u.full_name
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    WHERE b.id = ? AND b.doctor_id = ?
");
$stmt->execute([$booking_id, $doctor_id]);
$bk = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bk) {
    die('Booking tidak ditemukan atau bukan milik Anda.');
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Detail Riwayat Pasien - RASA</title>
  <link rel="stylesheet" href="<?= $base; ?>/assets/doctor_dashboard.css">
  <style>
    .detail-card {
      max-width: 600px;
      margin: 40px auto;
      background: #ffffff;
      border-radius: 18px;
      padding: 24px 28px;
      box-shadow: 0 18px 40px rgba(15,23,42,0.08);
    }
    .detail-header { margin-bottom: 16px; }
    .detail-header h2 { margin: 0 0 4px; }
    .detail-label {
      font-size: 13px;
      color: #6b7280;
    }
    .detail-value {
      font-size: 14px;
      color: #111827;
      margin-bottom: 8px;
    }
    .btn-back {
      display: inline-block;
      margin-top: 20px;
      font-size: 14px;
      padding: 8px 14px;
      border-radius: 999px;
      border: 1px solid #d1d5db;
      color: #111827;
      text-decoration: none;
      background: #ffffff;
    }
    .status-pill {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 999px;
      font-size: 12px;
      background: #e5f3ff;
      color: #1d4ed8;
    }
  </style>
</head>
<body>

<div class="docdash-page">
  <header class="docdash-header">
    <div>
      <h1>Detail Riwayat Pasien</h1>
      <p>Ringkasan satu booking konsultasi.</p>
    </div>
    <a href="<?= $base; ?>/logout.php" class="btn-logout">Logout</a>
  </header>

  <main class="docdash-main">
    <div class="detail-card">
      <div class="detail-header">
        <h2><?= htmlspecialchars($bk['full_name']); ?></h2>
      </div>

      <div class="detail-label">Jadwal Konsultasi</div>
      <div class="detail-value">
        <?= date('d M Y H:i', strtotime($bk['scheduled_at'])); ?>
      </div>

      <div class="detail-label">Jenis Konsultasi</div>
      <div class="detail-value">
        <?= htmlspecialchars($bk['consultation_type']); ?>
      </div>

      <div class="detail-label">Status</div>
      <div class="detail-value">
        <span class="status-pill"><?= htmlspecialchars($bk['status']); ?></span>
      </div>

      <a class="btn-back" href="<?= $base; ?>/doctor/index.php">⬅ Kembali ke Dashboard</a>
    </div>
  </main>
</div>

</body>
</html>
