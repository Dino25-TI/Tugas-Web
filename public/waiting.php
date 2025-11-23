<?php
require_once __DIR__.'/../includes/db.php';
$config = require __DIR__.'/../includes/config.php';
$base = $config['base_url'];
session_start();

$booking_id = intval($_GET['booking_id'] ?? 0);

// Ambil data booking
$stmt = $pdo->prepare("SELECT b.*, d.display_name 
                       FROM bookings b 
                       LEFT JOIN doctors d ON d.id=b.doctor_id 
                       WHERE b.id=?");
$stmt->execute([$booking_id]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    die("Booking tidak ditemukan.");
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Waiting - RASA</title>
<link rel="stylesheet" href="<?php echo $base;?>/assets/waiting.css">
</head>

<body>
<div class="container">
  <div class="card">
    <h3>Menunggu Konfirmasi Dokter</h3>

    <!-- Loader -->
    <div id="loader" class="loader"></div>

    <p id="statusText">
        Booking untuk <?php echo htmlspecialchars($book['display_name']); ?> —
        Status: <b><?php echo htmlspecialchars($book['status']); ?></b>
    </p>

    <p>Setelah dokter konfirmasi, Anda akan diarahkan ke sesi konsultasi.</p>

    <p><a class="btn" href="<?php echo $base;?>/dashboard.php">Kembali ke Dashboard</a></p>
  </div>
</div>

<script>
// Auto check status tiap 5 detik
setInterval(() => {
    fetch("<?php echo $base; ?>/api/check_status.php?booking_id=<?php echo $booking_id; ?>")
        .then(res => res.json())
        .then(data => {
            const statusText = document.getElementById("statusText");
            statusText.innerHTML = "Booking untuk <?php echo htmlspecialchars($book['display_name']); ?> — Status: <b>" + data.status + "</b>";
            
            // hapus semua class status dulu
            statusText.classList.remove("status-pending","status-confirmed","status-rejected");
            
            if (data.status === "pending") statusText.classList.add("status-pending");
            else if (data.status === "confirmed") statusText.classList.add("status-confirmed");
            else if (data.status === "rejected") statusText.classList.add("status-rejected");

            // jika sudah accepted → stop loading + redirect
            if (data.status === "accepted" || data.status === "confirmed") {
                document.getElementById("loader").style.display = "none";
                window.location.href = "<?php echo $base; ?>/session.php?booking_id=<?php echo $booking_id; ?>";
            }

        });
}, 5000);

</script>

</body>
</html>
