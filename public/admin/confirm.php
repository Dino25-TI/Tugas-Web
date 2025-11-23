<?php
require_once __DIR__.'/../../includes/db.php';
session_start();
$config = require __DIR__.'/../../includes/config.php';
$base = $config['base_url'];

if(!isset($_SESSION['user_id'])) { header("Location: {$base}/login.php"); exit; }
$stmt = $pdo->prepare("SELECT role FROM users WHERE id=?"); $stmt->execute([$_SESSION['user_id']]); $r = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$r || $r['role']!=='admin') { echo 'Hanya admin.'; exit; }

$booking_id = intval($_POST['booking_id'] ?? 0);
if($booking_id){
    $pdo->prepare("UPDATE bookings SET status='confirmed', updated_at=NOW() WHERE id=?")->execute([$booking_id]);
    $room = 'rasa-room-' . $booking_id;
    $pdo->prepare("INSERT INTO sessions (booking_id,room_id,start_time) VALUES (?,?,NOW())")->execute([$booking_id,$room]);
}
header("Location: index.php");

