<?php
session_start();
require __DIR__.'/../includes/db.php';
$config = require __DIR__.'/../includes/config.php';
$base   = $config['base_url'];

if (!isset($_SESSION['user_id'])) {
    header("Location: {$base}/login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $pageUrl = trim($_POST['page_url'] ?? '');

    if ($subject !== '' && $message !== '') {
        $stmt = $pdo->prepare("
            INSERT INTO support_tickets (user_id, complaint_type, subject, message, status, created_at, updated_at)
            VALUES (:user_id, 'system', :subject, :message, 'open', NOW(), NOW())
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':subject' => $subject . ($pageUrl ? ' (Halaman: '.$pageUrl.')' : ''),
            ':message' => $message,
        ]);
        header("Location: {$base}/");
        exit;
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporkan Masalah Web</title>
    <link rel="stylesheet" href="<?= $base; ?>/assets/book.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h3>Laporkan Masalah Web / Aplikasi</h3>
        <p>Gunakan form ini untuk melaporkan error, tampilan bermasalah, atau gangguan teknis di RASA.</p>

        <form method="post">
            <div class="booking-item">
                <label>Judul singkat masalah</label>
                <input type="text" name="subject" required
                       placeholder="Contoh: Tidak bisa bayar pakai QRIS">
            </div>

            <div class="booking-item">
                <label>Halaman yang bermasalah (opsional)</label>
                <input type="text" name="page_url"
                       placeholder="Contoh: /payment.php atau URL lengkap">
            </div>

            <div class="booking-item">
                <label>Detail masalah</label>
                <textarea name="message" rows="5" required
                          placeholder="Jelaskan apa yang terjadi, langkah sebelum error, dsb."></textarea>
            </div>

            <div style="margin-top:12px">
                <button class="btn" type="submit">Kirim Laporan</button>
                <a class="btn" href="<?= $base; ?>/">Batal</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
