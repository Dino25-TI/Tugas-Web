<?php
require_once __DIR__.'/../includes/db.php';
$config = require __DIR__.'/../includes/config.php';
$base = $config['base_url'];
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: {$base}/login.php"); exit; }

$booking_id = intval($_GET['booking_id'] ?? 0);

/* get or create session room */
$stmt = $pdo->prepare("SELECT room_id FROM sessions WHERE booking_id=? ORDER BY id DESC LIMIT 1");
$stmt->execute([$booking_id]); $row = $stmt->fetch(PDO::FETCH_ASSOC);
$room = $row['room_id'] ?? 'rasa-room-' . $booking_id;

/* if not exists, create a session record */
if(!$row){
    $ins = $pdo->prepare("INSERT INTO sessions (booking_id,room_id,start_time) VALUES (?,?,NOW())");
    $ins->execute([$booking_id,$room]);
    $pdo->prepare("UPDATE bookings SET status='in_session' WHERE id=?")->execute([$booking_id]);
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Ruang Konsultasi - RASA</title><link rel="stylesheet" href="<?php echo $base;?>/assets/sesi.css"></head><body>
<div class="container">
  <div class="card">
    <h3>Ruang Konsultasi</h3>
    <p>Ruang: <?php echo htmlspecialchars($room); ?></p>
    <div style="height:640px">
      <!-- embed Jitsi Meet public (alternatif WebRTC) -->
      <iframe src="https://meet.jit.si/<?php echo urlencode($room); ?>" style="width:100%;height:100%;border:0"></iframe>
    </div>
        <p class="end-session">
            <a class="btn" href="<?php echo $base;?>/dashboard.php">Selesai</a>
        </p>

  </div>
</div>
</body></html>

