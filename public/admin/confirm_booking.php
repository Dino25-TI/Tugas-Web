<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
$config = require __DIR__ . '/../../includes/config.php';
$base = $config['base_url'];

// pastikan hanya admin
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

// ambil booking_id dari POST
if (!isset($_POST['booking_id'])) {
    header("Location: {$base}/admin/index.php");
    exit;
}

$bookingId = (int) $_POST['booking_id'];

// update status booking -> in_session (atau confirmed, terserah kamu)
$upd = $pdo->prepare("UPDATE bookings SET status = 'approved' WHERE id = ?");
$upd->execute([$bookingId]);

// balikin ke halaman dashboard
header("Location: {$base}/admin/index.php");
exit;
