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
    header("Location: {$base}/doctor/index.php");
    exit;
}

// pastikan booking milik dokter ini
$stmt = $pdo->prepare("SELECT id FROM bookings WHERE id = ? AND doctor_id = ?");
$stmt->execute([$booking_id, $doctor_id]);
if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
    header("Location: {$base}/doctor/index.php");
    exit;
}

// set status jadi done
$up = $pdo->prepare("UPDATE bookings SET status = 'done', updated_at = NOW() WHERE id = ?");
$up->execute([$booking_id]); // update status dengan PDO. [web:227]

$_SESSION['flash_success'] = 'Sesi konsultasi ditandai selesai.';
header("Location: {$base}/doctor/index.php");
exit;
