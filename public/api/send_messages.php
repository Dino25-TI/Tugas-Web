<?php
require_once __DIR__.'/../../includes/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if(!$data){
    echo json_encode(['success'=>false, 'error'=>'No input data']);
    exit;
}

$room_id = $data['room_id'] ?? null;
$sender = $data['sender'] ?? null;
$message = $data['message'] ?? null;

// Validasi
if(!$room_id || !$sender || !$message){
    echo json_encode(['success'=>false, 'error'=>'Data tidak valid']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO chat_messages (room_id, sender, message) VALUES (?, ?, ?)");
    $stmt->execute([$room_id, $sender, $message]);

    echo json_encode(['success'=>true]);
} catch(Exception $e){
    echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
}
