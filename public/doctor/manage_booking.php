<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';  // 🔥 Bukan '/../includes/db.php'


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../login.php');
    exit;
}

$doctor_id = $_SESSION['user_id'];
$booking_id = $_GET['booking_id'] ?? 0;

if (!$booking_id) {
    die('Booking ID tidak ditemukan');
}

// Simpan meet_link
if (isset($_POST['save_meet_link'])) {
    $meet_link = filter_var($_POST['meet_link'], FILTER_SANITIZE_URL);
    if (filter_var($meet_link, FILTER_VALIDATE_URL)) {
        $stmt = $pdo->prepare("UPDATE bookings SET meet_link=? WHERE id=? AND doctor_id=?");
        $stmt->execute([$meet_link, $booking_id, $doctor_id]);
        $success = "✅ Link Google Meet disimpan!";
    } else {
        $error = "❌ URL tidak valid!";
    }
}

// Ambil data booking
$stmt = $pdo->prepare("
    SELECT b.*, u.nama as patient_name, u.email as patient_email 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    WHERE b.id=? AND b.doctor_id=?
");
$stmt->execute([$booking_id, $doctor_id]);
$booking = $stmt->fetch();

if (!$booking) {
    die('Booking tidak ditemukan atau bukan milik dokter ini');
}

// Cek apakah sudah ada session_meet
$session_started_at = $booking['session_started_at'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Booking - <?= $booking['patient_name'] ?></title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 20px auto; padding: 20px; }
        .card { background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 10px 0; }
        .btn { background: #4285f4; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
        .btn:hover { background: #3367d6; }
        .btn-danger { background: #ea4335; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        input[type="url"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
    </style>
</head>
<body>
    <h1>🏥 Kelola Booking Video Call</h1>
    
    <?php if (isset($success)): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <div class="card">
        <h3>📋 Detail Booking</h3>
        <p><strong>Pasien:</strong> <?= htmlspecialchars($booking['patient_name']) ?> (<?= $booking['patient_email'] ?>)</p>
        <p><strong>Jadwal:</strong> <?= date('d/m/Y H:i', strtotime($booking['scheduled_at'])) ?> (<?= $booking['duration_minutes'] ?> menit)</p>
        <p><strong>Tipe:</strong> <?= $booking['consultation_type'] == 'video' ? 'Video Call' : 'Video + Chat' ?></p>
        <p><strong>Status:</strong> 
            <span style="color: <?= $booking['status'] == 'confirmed' ? 'green' : 'orange' ?>">
                <?= ucfirst($booking['status']) ?>
            </span>
        </p>
    </div>

    <div class="card">
        <h3>🔗 Google Meet Link</h3>
        <?php if ($booking['meet_link']): ?>
            <p><strong>✅ Link sudah ada:</strong></p>
            <input type="url" value="<?= htmlspecialchars($booking['meet_link']) ?>" readonly style="background: #e8f0fe;">
            <br><br>
            <a href="<?= htmlspecialchars($booking['meet_link']) ?>" target="_blank" class="btn">🖥️ Buka Google Meet (sebagai Dokter)</a>
        <?php else: ?>
            <p><strong>📝 Belum ada link Meet. Buat dulu di Google Calendar:</strong></p>
            <ol>
                <li>Buka <a href="https://calendar.google.com" target="_blank">Google Calendar</a></li>
                <li>Buat event baru → tambah "Google Meet"</li>
                <li>Copy link Meet → paste di bawah</li>
            </ol>
        <?php endif; ?>

        <form method="POST" style="margin-top: 15px;">
            <input type="url" name="meet_link" 
                   value="<?= htmlspecialchars($booking['meet_link'] ?? '') ?>" 
                   placeholder="https://meet.google.com/abc-defg-hij"
                   <?= $booking['meet_link'] ? '' : 'required' ?>>
            <br><br>
            <button type="submit" name="save_meet_link" class="btn">💾 Simpan Link Meet</button>
        </form>
    </div>

    <div class="card">
        <h3>⏰ Status Sesi</h3>
        <?php if ($session_started_at): ?>
            <p><strong>✅ Sesi dimulai:</strong> <?= date('H:i:s', strtotime($session_started_at)) ?></p>
            <a href="../session_meet.php?booking_id=<?= $booking_id ?>" class="btn">📱 Lihat Timer Sesi</a>
        <?php else: ?>
            <p><strong>⏳ Sesi belum dimulai</strong>. Klik "Mulai Sesi" saat waktunya tiba.</p>
            <a href="../session_meet.php?booking_id=<?= $booking_id ?>" class="btn">🚀 Mulai Sesi</a>
        <?php endif; ?>
    </div>

    <div class="card">
        <a href="index.php" class="btn">← Kembali ke Dashboard</a>
        <a href="../logout.php" class="btn btn-danger">Logout</a>
    </div>
</body>
</html>
