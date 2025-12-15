<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
$config = require __DIR__ . '/../../includes/config.php';
$base = $config['base_url'];

if (!isset($_SESSION['user_id'])) {
    header("Location: {$base}/login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id=?");
$stmt->execute([$_SESSION['user_id']]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$r || $r['role'] !== 'admin') {
    echo 'Hanya admin.';
    exit;
}

if (!isset($_POST['booking_id'])) {
    header("Location: {$base}/admin/dashboard_booking.php");
    exit;
}

$bookingId = (int) $_POST['booking_id'];

// update payment_status -> paid
$upd = $pdo->prepare("
    UPDATE bookings
    SET payment_status = 'paid',
        payment_confirmed_at = NOW()
    WHERE id = ?
");
$upd->execute([$bookingId]);

header("Location: {$base}/admin/index.php");
exit;

