<?php
require_once __DIR__.'/../includes/db.php';
$config = require __DIR__.'/../includes/config.php';
$base = $config['base_url'];
session_start();

$doctor_id = intval($_GET['doctor_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT r.rating, r.comment, u.full_name
    FROM reviews r
    LEFT JOIN users u ON u.id = r.user_id
    WHERE r.doctor_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$doctor_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Ulasan Dokter</title>
<link rel="stylesheet" href="<?= $base ?>/assets/review.css">
</head>
<body>
<div class="container">
  <h2>Ulasan Dokter</h2>
  <?php if($reviews): ?>
    <?php foreach($reviews as $r): ?>
      <div class="review">
        <p><?= htmlspecialchars($r['comment']); ?></p>
        <div class="by">— <?= htmlspecialchars($r['full_name']); ?> (<?= $r['rating']; ?>/5)</div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>Belum ada ulasan untuk dokter ini.</p>
  <?php endif; ?>
</div>
</body>
</html>
