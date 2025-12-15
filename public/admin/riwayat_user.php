<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
$config = require __DIR__ . '/../../includes/config.php';
$base = $config['base_url'];

if (!isset($_SESSION['user_id'])) {
    header("Location: {$base}/login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id=?");
$stmt->execute([$_SESSION['user_id']]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$r || $r['role'] !== 'admin') {
    echo 'Hanya admin.';
    exit;
}

if (!isset($_GET['user_id'])) {
    echo 'User tidak ditemukan.';
    exit;
}

$userId = (int) $_GET['user_id'];

// info user
$uStmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
$uStmt->execute([$userId]);
$user = $uStmt->fetch(PDO::FETCH_ASSOC);

// daftar booking user ini
$bStmt = $pdo->prepare("
    SELECT b.*, d.display_name AS doctor_name
    FROM bookings b
    LEFT JOIN doctors d ON d.id = b.doctor_id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$bStmt->execute([$userId]);
$bookings = $bStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat User - RASA</title>
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/admin.css">
</head>
<body>
<div class="admin-main">
    <div class="table-card">
        <h3>Riwayat Booking - <?= htmlspecialchars($user['full_name'] ?? 'User'); ?></h3>

        <div class="table-wrapper">
            <table class="table-admin">
                <thead>
                    <tr>
                        <th>Dokter</th>
                        <th>Paket</th>
                        <th>Tanggal</th>
                        <th>Status Booking</th>
                        <th>Pembayaran</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td><?= htmlspecialchars($b['doctor_name']); ?></td>
                        <td><?= htmlspecialchars($b['package']); ?></td>
                        <td><?= htmlspecialchars($b['created_at']); ?></td>
                        <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $b['status']))); ?></td>
                        <td><?= htmlspecialchars(ucfirst($b['payment_status'] ?? 'unpaid')); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="5">Belum ada booking.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <a href="<?php echo $base; ?>/admin/manage_user.php" class="btn btn-ghost">
            Kembali ke Dashboard
        </a>
    </div>
</div>
</body>
</html>
