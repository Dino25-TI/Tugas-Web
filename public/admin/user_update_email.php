<?php
session_start();
require dirname(__DIR__, 2) . '/includes/db.php';
$config = require dirname(__DIR__, 2) . '/includes/config.php';
$base   = $config['base_url'];

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: {$base}/login.php");
    exit;
}

$user_id = (int)($_POST['user_id'] ?? 0);
$email   = trim($_POST['email'] ?? '');

if ($user_id <= 0 || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: {$base}/admin/user_edit.php?user_id=".$user_id);
    exit;
}

// opsional: pastikan email unik
$cek = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
$cek->execute([$email, $user_id]);
if ($cek->fetch(PDO::FETCH_ASSOC)) {
    // email sudah dipakai user lain
    header("Location: {$base}/admin/user_edit.php?user_id=".$user_id);
    exit;
}

$upd = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
$upd->execute([$email, $user_id]);

header("Location: {$base}/admin/manage_user.php");
exit;
