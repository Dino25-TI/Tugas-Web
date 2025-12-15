<?php
require_once 'includes/db.php'; // sesuaikan path db kamu

$newPassword = 'passwordBaru123'; // password baru yang ingin kamu pakai
$hashed = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
$stmt->execute([$hashed, 'Nonoya']);

echo "Password berhasil diubah!";
?>
