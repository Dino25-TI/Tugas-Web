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
$booking_id = (int)($_POST['booking_id'] ?? 0);
$action     = $_POST['action'] ?? '';

if ($doctor_id <= 0 || $booking_id <= 0 || !in_array($action, ['accept','reject'], true)) {
    $_SESSION['flash_info'] = 'Data tidak lengkap.';
    header("Location: {$base}/doctor/index.php");
    exit;
}

// Ambil data booking
$stmt = $pdo->prepare("SELECT consultation_type, status FROM bookings WHERE id = ? AND doctor_id = ?");
$stmt->execute([$booking_id, $doctor_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    $_SESSION['flash_info'] = 'Booking tidak valid.';
    header("Location: {$base}/doctor/index.php");
    exit;
}

if ($action === 'accept') {
    // ✅ HANYA UPDATE STATUS → BALIK DASHBOARD
    $newStatus = 'approved';
    $msg       = 'Booking berhasil disetujui.';
    
    $up = $pdo->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?");
    $up->execute([$newStatus, $booking_id]);
    
    $_SESSION['flash_success'] = $msg;
    header("Location: {$base}/doctor/index.php");
    exit;
} else {
    // Tolak
    $newStatus = 'cancelled';
    $msg       = 'Booking ditolak.';
    
    $up = $pdo->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?");
    $up->execute([$newStatus, $booking_id]);
    
    $_SESSION['flash_info'] = $msg;
    header("Location: {$base}/doctor/index.php");
    exit;
}
?>
