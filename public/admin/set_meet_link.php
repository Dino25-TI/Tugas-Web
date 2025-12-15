<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
$config = require __DIR__ . '/../../includes/config.php';
$base   = $config['base_url'];

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: {$base}/login.php");
    exit;
}

$booking_id = intval($_POST['booking_id'] ?? 0);
$meet_link  = trim($_POST['meet_link'] ?? '');

if ($booking_id > 0) {
    $stmt = $pdo->prepare("UPDATE bookings SET meet_link = ? WHERE id = ?");
    $stmt->execute([$meet_link, $booking_id]);

    $_SESSION['flash_meet']    = 'Link Meet berhasil disimpan.';
    $_SESSION['flash_meet_id'] = $booking_id;
}

header("Location: {$base}/admin/index.php");
exit;
