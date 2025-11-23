<?php
require_once __DIR__.'/../includes/db.php';
$config = require __DIR__.'/../includes/config.php';
$base = $config['base_url'];
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: {$base}/login.php"); exit; }

$doctor_id = intval($_GET['doctor_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM doctors WHERE id=?"); $stmt->execute([$doctor_id]); $doc = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$doc) { echo "Dokter tidak ditemukan"; exit; }

/* If user chooses direct-online booking (show online doctors) we handled at list view.
   Here handle form submission to create booking and redirect to payment. */
if($_SERVER['REQUEST_METHOD']==='POST'){
    $package = $_POST['package'];
    $scheduled_at = $_POST['scheduled_at'];
    $duration = ($package==='2_hours')?120:60;
    $stmt = $pdo->prepare("INSERT INTO bookings (user_id,doctor_id,package,scheduled_at,duration_minutes,status) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$_SESSION['user_id'],$doctor_id,$package,$scheduled_at,$duration,'pending']);
    $bid = $pdo->lastInsertId();
    header("Location: {$base}/payment.php?booking_id={$bid}"); exit;
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Book - RASA</title><link rel="stylesheet" href="<?php echo $base;?>/assets/book.css"></head><body>
<div class="container">
  <div class="card">
    <h3>Booking: <?php echo htmlspecialchars($doc['display_name']); ?></h3>
    <form method="post">
<div class="booking-row">
  <div class="booking-item">
    <label>Paket</label>
    <select name="package">
      <option value="1_hour">1 Jam - Rp <?php echo number_format($doc['hourly_price'],0,',','.'); ?></option>
      <option value="2_hours">2 Jam - Rp <?php echo number_format($doc['hourly_price']*2,0,',','.'); ?></option>
    </select>
  </div>

  <div class="booking-item">
    <label>Pilih Tanggal</label>
    <input type="date" id="date_pick" required>
  </div>

  <div class="booking-item">
    <label>Pilih Waktu</label>
    <input type="time" id="time_pick" required>
  </div>
</div>


<input type="hidden" name="scheduled_at" id="scheduled_at">

<div style="margin-top:12px">
    <button class="btn" type="submit">Lanjut ke Pembayaran</button>
</div>

<script>
document.querySelector("form").addEventListener("submit", function(e){
    const d = document.getElementById("date_pick").value;
    const t = document.getElementById("time_pick").value;

    if(!d || !t){
        alert("Mohon isi tanggal dan waktu dengan lengkap.");
        e.preventDefault();
        return;
    }

    // gabungkan ke format final
    document.getElementById("scheduled_at").value = d + " " + t;
});
</script>


    </form>
  </div>
</div>
</body></html>
