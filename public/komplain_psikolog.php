<?php
session_start();
require __DIR__.'/../includes/db.php';
$config = require __DIR__.'/../includes/config.php';
$base   = $config['base_url'];

if (!isset($_SESSION['user_id'])) {
    header("Location: {$base}/login.php");
    exit;
}

$userId   = (int)$_SESSION['user_id'];
$doctorId = (int)($_GET['doctor_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($subject !== '' && $message !== '') {
        $stmt = $pdo->prepare("
            INSERT INTO support_tickets (user_id, doctor_id, complaint_type, subject, message, status, created_at, updated_at)
            VALUES (:user_id, :doctor_id, 'psychologist', :subject, :message, 'open', NOW(), NOW())
        ");
        $stmt->execute([
            ':user_id'   => $userId,
            ':doctor_id' => $doctorId ?: null,
            ':subject'   => $subject,
            ':message'   => $message,
        ]);
        header("Location: {$base}/doctor.php?id={$doctorId}");
        exit;
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Komplain Psikolog</title>
    <link rel="stylesheet" href="<?= $base; ?>/assets/komplain.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h3>Laporkan Psikolog</h3>
        <p>Komplain ini akan dikirim ke admin RASA dan tidak tampil sebagai ulasan publik.</p>

        <form method="post">
            <div class="booking-item">
                <label>Subjek Komplain</label>
                <input type="text" name="subject" required>
            </div>

            <div class="booking-item">
                <label>Detail Komplain</label>
                <textarea name="message" rows="5" required></textarea>
            </div>

            <div style="margin-top:12px">
                <button class="btn" type="submit">Kirim Komplain</button>
                <a class="btn" href="<?= $base; ?>/doctor.php?id=<?= $doctorId; ?>">Batal</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
