<?php
require_once __DIR__.'/../includes/db.php';

$booking_id = intval($_GET['booking_id'] ?? 0);

$stmt = $pdo->prepare("SELECT status FROM bookings WHERE id=?");
$stmt->execute([$booking_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

header("Content-Type: application/json");
echo json_encode(["status" => $row['status']]);
