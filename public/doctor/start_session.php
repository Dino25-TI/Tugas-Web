<?php
require_once __DIR__ . '/../../includes/db.php';
$config = require __DIR__ . '/../../includes/config.php';
$base   = rtrim($config['base_url'], '/');

session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'psychologist') {
    header("Location: {$base}/login.php");
    exit;
}

$doctor_id  = (int)($_SESSION['doctor_id'] ?? 0);
$booking_id = (int)($_POST['booking_id'] ?? 0);

if ($doctor_id <= 0 || $booking_id <= 0) {
    $_SESSION['flash_info'] = 'Data tidak valid untuk mulai sesi.';
    header("Location: {$base}/doctor/index.php");
    exit;
}

// pastikan booking milik dokter ini
$stmt = $pdo->prepare("
    SELECT session_started_at
    FROM bookings
    WHERE id = ? AND doctor_id = ?
");
$stmt->execute([$booking_id, $doctor_id]);
$bk = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bk) {
    $_SESSION['flash_info'] = 'Booking tidak ditemukan.';
    header("Location: {$base}/doctor/index.php");
    exit;
}

// kalau belum pernah mulai, set sekarang
// kalau belum pernah mulai, set sekarang
if (empty($bk['session_started_at'])) {
    $up = $pdo->prepare("
        UPDATE bookings
        SET session_started_at = NOW(),
            session_status      = 'ongoing'
        WHERE id = ?
    ");
    $up->execute([$booking_id]);
}

// setelah set waktu mulai, arahkan ke halaman timer dokter
header("Location: {$base}/doctor/session_meet.php?booking_id={$booking_id}");
exit;
