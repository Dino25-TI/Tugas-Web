<?php
require_once __DIR__.'/../includes/db.php';
$config = require __DIR__.'/../includes/config.php';
$base = $config['base_url'];
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: {$base}/login.php"); exit; }

$booking_id = intval($_GET['booking_id'] ?? 0);
$stmt = $pdo->prepare("SELECT b.*, d.display_name, d.hourly_price FROM bookings b JOIN doctors d ON d.id=b.doctor_id WHERE b.id=?");
$stmt->execute([$booking_id]); $book = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$book) { echo 'Booking tidak ditemukan'; exit; }
$amount = ($book['package']==='2_hours')? ($book['hourly_price']*2) : $book['hourly_price'];

if($_SERVER['REQUEST_METHOD']==='POST'){
    $method = $_POST['method'];
    $tx = 'RASA'.time().rand(100,999);
    $ins = $pdo->prepare("INSERT INTO payments (booking_id,amount,method,status,transaction_ref,paid_at) VALUES (?,?,?,?,?,NOW())");
    $ins->execute([$booking_id,$amount,$method,'success',$tx]);
    $pdo->prepare("UPDATE bookings SET status='paid', updated_at=NOW() WHERE id=?")->execute([$booking_id]);
    header("Location: {$base}/waiting.php?booking_id={$booking_id}"); exit;
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Pembayaran - RASA</title><link rel="stylesheet" href="<?php echo $base;?>/assets/payment.css"></head><body>
<div class="container">
  <div class="card">
    <h3>Pembayaran</h3>
    <p>Booking untuk <?php echo htmlspecialchars($book['display_name']); ?> pada <?php echo htmlspecialchars($book['scheduled_at']); ?></p>
    <form method="post">
      <label>Metode</label>
      <select name="method">
        <option value="ewallet">E-Wallet</option>
        <option value="bank">Transfer Bank</option>
      </select>
      <p>Jumlah: Rp <?php echo number_format($amount,0,',','.'); ?></p>
      <div style="margin-top:12px"><button class="btn" type="submit">Bayar Sekarang </button></div>
    </form>
  </div>
</div>
</body></html>

