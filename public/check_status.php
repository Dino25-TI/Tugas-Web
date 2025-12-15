<?php
require_once __DIR__ . '/../includes/db.php';

$booking_id = (int)($_GET['booking_id'] ?? 0);

header('Content-Type: application/json');

if ($booking_id <= 0) {
    echo json_encode(['status' => 'unknown']);
    exit;
}

$stmt = $pdo->prepare("SELECT status FROM bookings WHERE id = ?");
$stmt->execute([$booking_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['status' => 'unknown']);
    exit;
}

echo json_encode(['status' => $row['status']]);
