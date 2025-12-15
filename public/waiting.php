<?php
require_once __DIR__ . '/../includes/db.php';
$config = require __DIR__ . '/../includes/config.php';
$base   = rtrim($config['base_url'], '/');

session_start();

$booking_id = (int)($_GET['booking_id'] ?? 0);

// Ambil data booking
$stmt = $pdo->prepare("
    SELECT b.*, d.display_name
    FROM bookings b
    LEFT JOIN doctors d ON d.id = b.doctor_id
    WHERE b.id = ?
");
$stmt->execute([$booking_id]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    die("Booking tidak ditemukan.");
}

$consultType = $book['consultation_type'] ?? 'chat'; // chat | video | both
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Waiting - RASA</title>
    <link rel="stylesheet" href="<?= $base; ?>/assets/waiting.css">
</head>
<body>
<div class="container">
  <div class="card">
    <h3>Menunggu Konfirmasi Dokter</h3>

    <!-- Loader -->
    <div id="loader" class="loader"></div>

    <p id="statusText">
        Booking untuk <?= htmlspecialchars($book['display_name']); ?> —
        Status: <b><?= htmlspecialchars($book['status']); ?></b>
    </p>

    <p>Setelah dokter konfirmasi, Anda akan diarahkan ke sesi konsultasi.</p>

    <p><a class="btn" href="<?= $base; ?>/dashboard.php">Kembali ke Dashboard</a></p>
  </div>
</div>

<script>
const baseUrl        = <?= json_encode($base); ?>;
const bookingId      = <?= json_encode($booking_id); ?>;
const doctorId       = <?= json_encode((int)$book['doctor_id']); ?>;
const consultType    = <?= json_encode($consultType); ?>; // chat | video | both

// Auto check status tiap 5 detik
setInterval(() => {
    fetch(baseUrl + "/api/check_status.php?booking_id=" + bookingId)
        .then(res => res.json())
        .then(data => {
            const statusText = document.getElementById("statusText");
            statusText.innerHTML =
                "Booking untuk <?= htmlspecialchars($book['display_name']); ?> — Status: <b>"
                + data.status + "</b>";

            statusText.classList.remove("status-pending","status-confirmed","status-rejected");

            if (data.status === "pending" || data.status === "awaiting_confirmation") {
                statusText.classList.add("status-pending");
            } else if (data.status === "confirmed" || data.status === "in_session" || data.status === "accepted") {
                statusText.classList.add("status-confirmed");
            } else if (data.status === "rejected") {
                statusText.classList.add("status-rejected");
            }

            // kalau sudah disetujui dokter → stop loading + redirect
            if (
                data.status === "accepted" ||
                data.status === "confirmed" ||
                data.status === "in_session"
            ) {
                document.getElementById("loader").style.display = "none";

                let targetUrl;

                if (consultType === 'chat') {
                    // paket chat saja
                    targetUrl = baseUrl + "/chat.php?doctor_id=" + doctorId + "&booking_id=" + bookingId;
                } else if (consultType === 'video') {
                    // paket video saja
                    targetUrl = baseUrl + "/session_meet.php?booking_id=" + bookingId;
                } else {
                    // paket video + chat: user pilih di halaman khusus
                    targetUrl = baseUrl + "/session_both.php?booking_id=" + bookingId;
                }

                window.location.href = targetUrl;
            }
        })
        .catch(err => console.error(err));
}, 5000);
</script>

</body>
</html>
