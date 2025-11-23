<?php
require_once __DIR__.'/../../includes/db.php';
session_start();
$config = require __DIR__.'/../../includes/config.php';
$base = $config['base_url'];

if(!isset($_SESSION['user_id'])) { header("Location: {$base}/login.php"); exit; }
$stmt = $pdo->prepare("SELECT role FROM users WHERE id=?"); $stmt->execute([$_SESSION['user_id']]); $r = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$r || $r['role']!=='admin') { echo 'Hanya admin.'; exit; }

$bks = $pdo->query("SELECT b.*, u.full_name as user_name, d.display_name as doctor_name FROM bookings b LEFT JOIN users u ON u.id=b.user_id LEFT JOIN doctors d ON d.id=b.doctor_id WHERE b.status IN ('paid','pending','awaiting_confirmation') ORDER BY b.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html><head><meta charset="utf-8"><title>Admin - RASA</title><link rel="stylesheet" href="<?php echo $base;?>/assets/css/styles.css"></head><body>
<div class="container">
  <div class="card">
    <h3>Admin Dashboard</h3>
    <?php foreach($bks as $it): ?>
      <div style="padding:10px;border:1px solid #eee;margin-bottom:8px">
        <div><?php echo htmlspecialchars($it['user_name']); ?> → <?php echo htmlspecialchars($it['doctor_name']); ?> — <?php echo htmlspecialchars($it['package']); ?> — <?php echo htmlspecialchars($it['status']); ?></div>
        <form method="post" action="confirm.php">
          <input type="hidden" name="booking_id" value="<?php echo $it['id']; ?>">
          <button class="btn" type="submit">Konfirmasi</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
</div>
</body></html>
