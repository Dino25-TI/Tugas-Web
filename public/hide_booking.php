<?php
require_once __DIR__ . '/../includes/db.php';
$config = require __DIR__ . '/../includes/config.php';
$base   = $config['base_url'];

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: {$base}/login.php");
    exit;
}

$user_id    = $_SESSION['user_id'];
$booking_id = intval($_POST['booking_id'] ?? 0);

if ($booking_id > 0) {
    $stmt = $pdo->prepare("UPDATE bookings SET user_hidden = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$booking_id, $user_id]);
}

header("Location: {$base}/dashboard.php");
exit;
