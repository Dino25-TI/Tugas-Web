<?php
session_start();
require dirname(__DIR__, 2) . '/includes/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$doctorId = (int)($_POST['doctor_id'] ?? 0);
$status   = $_POST['status'] ?? 'active';

if ($doctorId > 0 && in_array($status, ['active','inactive'], true)) {
    $stmt = $pdo->prepare("UPDATE doctors SET status = ? WHERE id = ?");
    $stmt->execute([$status, $doctorId]);
}

header('Location: manage_psikolog.php');
exit;
