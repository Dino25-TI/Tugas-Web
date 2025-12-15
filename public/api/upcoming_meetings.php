<?php
require_once __DIR__ . '/../../includes/db.php';
$config = require __DIR__ . '/../../includes/config.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'meetings' => []]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT id, doctor_id, scheduled_at, consultation_type
    FROM bookings
    WHERE user_id = ?
      AND (consultation_type = 'video' OR consultation_type = 'both')
      AND status IN ('pending','approved')
");
$stmt->execute([$user_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'meetings' => $rows]);
