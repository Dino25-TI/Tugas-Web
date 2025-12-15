<?php
require_once __DIR__.'/../includes/db.php';
$config = require __DIR__.'/../includes/config.php';
$base   = $config['base_url'];

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: {$base}/login.php");
    exit;
}
if (($_SESSION['role'] ?? '') === 'admin') {
    header("Location: {$base}/admin/index.php");
    exit;
}

/* ========== DATA DOKTER ========== */
$doctor_id = intval($_GET['doctor_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM doctors WHERE id = ?");
$stmt->execute([$doctor_id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    echo "Dokter tidak ditemukan";
    exit;
}

/* ========== JADWAL PAKEM DARI URL ========== */
$scheduled_at = $_GET['scheduled_at'] ?? null;
if (!$scheduled_at) {
    // fallback kalau lupa kirim, pakai sekarang
    $scheduled_at = date('Y-m-d H:i:s');
}

/* ========== SUBMIT BOOKING ========== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $package           = $_POST['package'];              // 1_hour | 2_hours
    $consultation_type = $_POST['consultation_type'] ?? 'chat';

    $duration     = 60;   // default
    $price        = 0;
    $packageLabel = '';

    if ($consultation_type === 'chat') {
        // Selalu: Chat 24 jam, Rp 100.000, satu paket saja
        $duration     = 24 * 60;                 // 24 jam
        $price        = 100000;
        $packageLabel = 'Chat 24 Jam - Paket 1';
        // $package diabaikan

    } elseif ($consultation_type === 'video') {
        // Video call saja
        if ($package === '1_hour') {
            $duration     = 60;
            $price        = 200000;
            $packageLabel = 'Video 1 Jam - Paket 1';
        } else { // 2_hours
            $duration     = 120;
            $price        = 300000;
            $packageLabel = 'Video 2 Jam - Paket 2';
        }

    } else { // both: chat 24 jam + video
        if ($package === '1_hour') {
            $duration     = 60;      // video 1 jam
            $price        = 400000;
            $packageLabel = 'Chat 24 Jam + Video 1 Jam (Paket 1)';
        } else { // 2_hours
            $duration     = 120;     // video 2 jam
            $price        = 500000;
            $packageLabel = 'Chat 24 Jam + Video 2 Jam (Paket 2)';
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO bookings
            (user_id, doctor_id, package, scheduled_at, duration_minutes, status, consultation_type, price)
        VALUES (?,?,?,?,?,?,?,?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $doctor_id,
        $packageLabel,
        $scheduled_at,   // dari URL jadwal pakem
        $duration,
        'pending',
        $consultation_type,
        $price
    ]);

    $bid = $pdo->lastInsertId();
    header("Location: {$base}/payment.php?booking_id={$bid}");
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Book - RASA</title>
    <link rel="stylesheet" href="<?= $base; ?>/assets/book.css">
</head>
<body>

<div class="container">
    <div class="card">
        <h3>Selamat Datang di Ruang Book</h3>
        <h4>Psikolog: <?= htmlspecialchars($doc['display_name']); ?></h4>

        <!-- Info jadwal pakem yang sudah dipilih -->
        <p><strong>Jadwal:</strong> <?= htmlspecialchars($scheduled_at); ?></p>

        <form method="post">
            <div class="booking-row">

                <div class="booking-item">
                    <label>Paket</label>
                    <select name="package" id="package_select">
                        <option value="1_hour">Paket 1</option>
                        <option value="2_hours">Paket 2</option>
                    </select>
                </div>

                <div class="booking-item">
                    <label>Jenis Konsultasi</label>
                    <select name="consultation_type" id="type_select" required>
                        <option value="chat">Chat saja (24 jam)</option>
                        <option value="video">Video call saja</option>
                        <option value="both">Chat + Video call</option>
                    </select>
                </div>

            </div>

            <div style="margin-top:12px">
                <button class="btn" type="submit">Lanjut ke Pembayaran</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const pkgSelect  = document.getElementById('package_select');
    const typeSelect = document.getElementById('type_select');

    function updatePackageLabels() {
        const t    = typeSelect.value;
        const opt1 = pkgSelect.options[0]; // 1_hour
        const opt2 = pkgSelect.options[1]; // 2_hours

        if (t === 'chat') {
            opt1.textContent = 'Paket 1 - Chat 24 Jam - Rp 100.000';
            opt2.textContent = '';
            opt2.disabled    = true;
            opt2.hidden      = true;
            pkgSelect.value  = '1_hour';
        } else if (t === 'video') {
            opt1.textContent = 'Paket 1 - Video 1 Jam - Rp 200.000';
            opt2.textContent = 'Paket 2 - Video 2 Jam - Rp 300.000';
            opt2.disabled    = false;
            opt2.hidden      = false;
        } else { // both
            opt1.textContent = 'Paket 1 - Chat 24 Jam + Video 1 Jam - Rp 400.000';
            opt2.textContent = 'Paket 2 - Chat 24 Jam + Video 2 Jam - Rp 500.000';
            opt2.disabled    = false;
            opt2.hidden      = false;
        }
    }

    typeSelect.addEventListener('change', updatePackageLabels);
    updatePackageLabels(); // set awal
});
</script>

</body>
</html>
