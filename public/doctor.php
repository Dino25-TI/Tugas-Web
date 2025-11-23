<?php
require_once __DIR__.'/../includes/db.php';
$config = require __DIR__.'/../includes/config.php';
$base = $config['base_url'];
session_start();

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM doctors WHERE id=?");
$stmt->execute([$id]); $d = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$d) { echo "Dokter tidak ditemukan"; exit; }

$certs = $pdo->prepare("SELECT * FROM doctor_certificates WHERE doctor_id=?");
$certs->execute([$id]);
$certs = $certs->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html><head><meta charset="utf-8"><title><?php echo htmlspecialchars($d['display_name']); ?></title><link rel="stylesheet" href="<?php echo rtrim($base,'/'); ?>/assets/doctor_style.css"></head><body>
<div class="container">
  <div class="card">
    <h3><?php echo htmlspecialchars($d['display_name']); ?> <small><?php echo htmlspecialchars($d['title']); ?></small></h3>
    <p><?php echo nl2br(htmlspecialchars($d['bio'])); ?></p>
    <p><strong>Spesialis:</strong> <?php echo htmlspecialchars($d['specialties']); ?></p>
    <p><strong>Pendidikan:</strong> <?php echo nl2br(htmlspecialchars($d['education'])); ?></p>
    <p><strong>Sertifikat:</strong> <?php echo nl2br(htmlspecialchars($d['certifications'])); ?></p>
  <div style="margin-top:12px">
  <?php if(!isset($_SESSION['user_id'])): ?>
    <a class="btn" href="<?= $base ?>login.php">Login untuk Booking</a>
  <?php else: ?>
    <a class="btn" href="<?= $base ?>book.php?doctor_id=<?= $d['id'] ?>">Book Sekarang</a>
    <a class="btn-review" href="<?= $base ?>review_doctor.php?doctor_id=<?= $d['id'] ?>">Lihat Ulasan</a>
  <?php endif; ?>
</div>

  </div>
</div>
</body></html>

