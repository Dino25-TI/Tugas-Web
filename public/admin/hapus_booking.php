<?php
session_start();
require __DIR__ . '/../../includes/db.php';
$config = require __DIR__ . '/../../includes/config.php';
$base   = rtrim($config['base_url'], '/');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: {$base}/login.php");
    exit;
}

$bookingId = (int) ($_POST['booking_id'] ?? 0);

if ($bookingId > 0) {
    $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = :id");
    $stmt->execute([':id' => $bookingId]);
}

header("Location: {$base}/admin/index.php");
exit;
