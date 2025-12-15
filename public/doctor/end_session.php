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

$stmt = $pdo->prepare("
    SELECT id FROM bookings
    WHERE id = ? AND doctor_id = ?
");
$stmt->execute([$booking_id, $doctor_id]);
if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
    header("Location: {$base}/doctor/index.php");
    exit;
}

$up = $pdo->prepare("
    UPDATE bookings
    SET session_status = 'ended'
    WHERE id = ?
");
$up->execute([$booking_id]);

$_SESSION['flash_info'] = 'Sesi konsultasi telah diakhiri oleh dokter.';
header("Location: {$base}/doctor/index.php");
exit;
