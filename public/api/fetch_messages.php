<?php
require_once __DIR__.'/../includes/db.php';
$room_id=intval($_GET['room_id']);
$user_type=$_GET['user'] ?? 'user'; // 'user' atau 'doctor'

$stmt=$pdo->prepare("SELECT id,sender,message,read_status,created_at FROM chat_messages WHERE room_id=? ORDER BY created_at ASC");
$stmt->execute([$room_id]);
$messages=$stmt->fetchAll(PDO::FETCH_ASSOC);

// Update last_seen
$col = $user_type=='user' ? 'user_last_seen' : 'doctor_last_seen';
$upd = $pdo->prepare("UPDATE chat_rooms SET $col=NOW() WHERE id=?");
$upd->execute([$room_id]);
header('Content-Type: application/json');

echo json_encode(['messages'=>$messages]);
