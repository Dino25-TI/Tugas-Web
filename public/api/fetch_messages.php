<?php
require_once __DIR__.'/../../includes/db.php';

header('Content-Type: application/json');

$room_id   = (int)($_GET['room_id'] ?? 0);
$user_type = $_GET['user'] ?? 'user'; // 'user' atau 'doctor'

if ($room_id <= 0) {
    echo json_encode(['messages' => []]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, room_id, sender, message, created_at
    FROM chat_messages
    WHERE room_id = ?
    ORDER BY id ASC
");
$stmt->execute([$room_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// update last_seen (opsional)
$col = ($user_type === 'doctor') ? 'doctor_last_seen' : 'user_last_seen';
$upd = $pdo->prepare("UPDATE chat_rooms SET $col = NOW() WHERE id = ?");
$upd->execute([$room_id]);

echo json_encode(['messages' => $messages]);
