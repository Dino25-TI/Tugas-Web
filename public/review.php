<?php
require_once __DIR__.'/../includes/db.php';
$config = require __DIR__.'/../includes/config.php';
$base = $config['base_url'];
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: {$base}/login.php"); exit; }

if($_SERVER['REQUEST_METHOD']==='POST'){
    $doctor_id = intval($_POST['doctor_id']);
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    $ins = $pdo->prepare("INSERT INTO reviews (user_id,doctor_id,rating,comment) VALUES (?,?,?,?)");
    $ins->execute([$_SESSION['user_id'],$doctor_id,$rating,$comment]);
    header("Location: {$base}/dashboard.php"); exit;
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Review</title><link rel="stylesheet" href="<?php echo $base;?>/assets/review.css"></head><body>
<div class="container">
  <div class="card">
    <h3>Berikan Penilaian</h3>
    <form method="post">
<?php
// ambil daftar psikolog
$doctor_id = intval($_GET['doctor_id'] ?? 0);
$stmt = $pdo->prepare("SELECT display_name FROM doctors WHERE id=?");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$doctor) { echo "Dokter tidak ditemukan"; exit; }
?>
<label>Psikolog</label>
<input type="text" value="<?= htmlspecialchars($doctor['display_name']) ?>" readonly>
<input type="hidden" name="doctor_id" value="<?= $doctor_id ?>"> <!-- ini untuk database -->

    <label>Rating (1-5)</label><input name="rating" type="number" min="1" max="5" required>
      <label>Komentar</label><textarea name="comment"></textarea>
      <div style="margin-top:12px"><button class="btn" type="submit">Kirim Review</button></div>
    </form>
  </div>
</div>
</body></html>
