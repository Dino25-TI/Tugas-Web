<?php
// public/doctor/set_meet_link.php

require_once __DIR__ . '/../../includes/db.php';
$config = require __DIR__ . '/../../includes/config.php';
$base   = rtrim($config['base_url'], '/');

session_start();

/* =========================
   1. CEK LOGIN & ROLE
   ========================= */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'psychologist') {
    header("Location: {$base}/login.php");
    exit;
}

$doctor_id = (int)($_SESSION['doctor_id'] ?? 0);
if ($doctor_id <= 0) {
    header("Location: {$base}/login.php");
    exit;
}

/* =========================
   2. PROSES SIMPAN (REQUEST POST)
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $meet_link  = trim($_POST['meet_link'] ?? '');

    if ($booking_id <= 0 || $meet_link === '') {
        $_SESSION['flash_error'] = 'Data tidak valid.';
        header("Location: {$base}/doctor/index.php");
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE bookings
        SET meet_link = ?
        WHERE id = ? AND doctor_id = ?
    ");
    $stmt->execute([$meet_link, $booking_id, $doctor_id]);

    $_SESSION['flash_success'] = 'Link meeting berhasil disimpan.';
    header("Location: {$base}/doctor/index.php");
    exit;
}

/* =========================
   3. TAMPILKAN FORM (REQUEST GET)
   ========================= */
$booking_id = (int)($_GET['booking_id'] ?? 0);

if ($booking_id <= 0) {
    $_SESSION['flash_error'] = 'Booking tidak valid.';
    header("Location: {$base}/doctor/index.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT b.meet_link, b.id, u.full_name
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    WHERE b.id = ? AND b.doctor_id = ?
");
$stmt->execute([$booking_id, $doctor_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    $_SESSION['flash_error'] = 'Booking tidak ditemukan.';
    header("Location: {$base}/doctor/index.php");
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Atur Link Google Meet</title>
    <link rel="stylesheet" href="<?= $base; ?>/assets/doctor_dashboard.css">
    <style>
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
        }
        label { display:block; margin-top:12px; font-weight:600; }
        input[type="url"] {
            width:100%; padding:8px 10px; margin-top:6px;
            border:1px solid #ccc; border-radius:4px;
        }
        .actions { margin-top:18px; display:flex; gap:10px; }
        .btn {
            padding:8px 16px; border-radius:4px;
            border:none; cursor:pointer; text-decoration:none;
            display:inline-block;
        }
        .btn-primary { background:#2563eb; color:#fff; }
        .btn-secondary { background:#e5e7eb; color:#111827; }
    </style>
</head>
<body>
<div class="container">
    <h2>Atur Link Google Meet</h2>
    <p>Pasien: <strong><?= htmlspecialchars($booking['full_name']); ?></strong></p>

    <form method="post" action="<?= $base; ?>/doctor/set_meet_link.php">
        <input type="hidden" name="booking_id" value="<?= (int)$booking['id']; ?>">

        <label for="meet_link">URL Google Meet</label>
        <input
            type="url"
            id="meet_link"
            name="meet_link"
            value="<?= htmlspecialchars($booking['meet_link'] ?? ''); ?>"
            placeholder="https://meet.google.com/xxx-yyyy-zzz"
            required
        >

        <div class="actions">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="<?= $base; ?>/doctor/index.php" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
</body>
</html>
