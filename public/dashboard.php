<?php
require_once __DIR__.'/../includes/db.php';
$config = require __DIR__.'/../includes/config.php';
$base = $config['base_url'];
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: {$base}/login.php"); exit; }

$user_id = $_SESSION['user_id'];
// Booking aktif
$stmt = $pdo->prepare("SELECT b.*, d.display_name 
                       FROM bookings b 
                       LEFT JOIN doctors d ON d.id=b.doctor_id 
                       WHERE b.user_id=? AND b.status IN ('pending','accepted','paid')
                       ORDER BY b.created_at DESC");
$stmt->execute([$user_id]); 
$activeBks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Riwayat booking
$stmt = $pdo->prepare("SELECT b.*, d.display_name 
                       FROM bookings b 
                       LEFT JOIN doctors d ON d.id=b.doctor_id 
                       WHERE b.user_id=? AND b.status IN ('done','rejected')
                       ORDER BY b.created_at DESC");
$stmt->execute([$user_id]); 
$historyBks = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!doctype html><html><head><meta charset="utf-8"><title>Dashboard - RASA</title><link rel="stylesheet" href="<?php echo $base;?>/assets/dashboard.css"></head><body>
<div class="container">
  <div class="card">
    <h3>Dashboard</h3>
<p>
  <a class="btn" href="<?php echo $base;?>/index.php">🏠 Beranda</a>
  <a class="btn" href="<?php echo $base;?>/doctors.php">Book baru</a>
  <a class="btn alt" href="<?php echo $base;?>/test.php">Test Gratis</a>
</p>

    <h4>Riwayat Booking</h4>
<div class="bk-container">
<?php foreach($activeBks as $bk): ?>
<div class="bk">
      <div class="bk-top">
        <strong><?php echo htmlspecialchars($bk['display_name']); ?></strong>
        <span class="<?php echo in_array($bk['status'], ['confirmed','paid'])?'online-dot':'offline-dot'; ?>">
          <?php echo ucfirst($bk['status']); ?>
        </span>
      </div>
      <div class="bk-info">
        Paket: <?php echo htmlspecialchars($bk['package']); ?><br>
        Jadwal: <?php echo htmlspecialchars($bk['scheduled_at']); ?>
      </div>
      <div class="bk-action">
    <?php if(in_array($bk['status'], ['confirmed','paid'])): ?>
        <a class="btn" href="<?php echo $base;?>/session.php?booking_id=<?php echo $bk['id']; ?>">Masuk Ruang</a>
        <a class="btn alt" href="<?php echo $base;?>/chat.php?booking_id=<?php echo $bk['id']; ?>">Chat Dokter</a>
    <?php else: ?>
        <span class="small-muted">Tunggu konfirmasi</span>
    <?php endif; ?>
</div>
    </div>
  <?php endforeach; ?>
</div>
</div>
</div>
</body></html>

