<?php
require_once __DIR__ . '/../../includes/db.php';
$config = require __DIR__ . '/../../includes/config.php';
$base   = rtrim($config['base_url'], '/');

session_start();

// hanya psikolog
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'psychologist') {
    header("Location: {$base}/login.php");
    exit;
}

$doctor_id  = (int)($_SESSION['doctor_id'] ?? 0);
$booking_id = (int)($_GET['booking_id'] ?? 0);

if ($doctor_id <= 0 || $booking_id <= 0) {
    die('Data tidak valid.');
}

// booking milik dokter
$stmt = $pdo->prepare("
    SELECT b.*, u.full_name AS patient_name
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    WHERE b.id = ? AND b.doctor_id = ?
");
$stmt->execute([$booking_id, $doctor_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    die('Booking tidak ditemukan atau bukan milik Anda.');
}

date_default_timezone_set('Asia/Makassar');

$now             = new DateTime();
$durationMinutes = (int)$booking['duration_minutes']; // 60 / 120

// dokter TIDAK mengubah session_started_at, hanya baca
if (empty($booking['session_started_at'])) {
    $notStarted       = true;
    $expired          = false;
    $remainingSeconds = 0;
} else {
    $notStarted = false;
    $startTime  = new DateTime($booking['session_started_at']);

    // hitung endTime dari startTime + durasi paket
    $endTime = (clone $startTime)->add(new DateInterval('PT' . $durationMinutes . 'M'));

    $expired          = ($now >= $endTime);
    $remainingSeconds = max(0, $endTime->getTimestamp() - $now->getTimestamp());
}

// roomName HARUS sama dengan user/session_meet.php
$jitsiRoomName = "RasaTelemedSession_" . $booking['id'];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Ruang Konsultasi (Dokter) - Jitsi Meet</title>
  <link rel="stylesheet" href="<?= $base; ?>/assets/sesi.css">
  <style>
    body, html {
      height: 100%;
      margin: 0;
      font-family: Arial, sans-serif;
    }
    #jitsi-container {
      height: 600px;
      width: 100%;
      margin-top: 20px;
      border: 1px solid #ccc;
    }
    .container {
      max-width: 900px;
      margin: auto;
      padding: 10px;
    }
    .btn {
      background-color: #2c7be5;
      color: white;
      padding: 10px 16px;
      border: none;
      border-radius: 4px;
      text-decoration: none;
      cursor: pointer;
    }
    .btn.alt {
      background-color: #6c757d;
    }
  </style>
</head>
<body>
<div class="container">
  <div class="card">
    <h3>Ruang Konsultasi (Dokter) dengan <?= htmlspecialchars($booking['patient_name']); ?></h3>

    <p>
      Durasi paket: <?= htmlspecialchars($durationMinutes); ?> menit
    </p>

    <?php if ($notStarted): ?>
      <p style="margin-top:12px; color:#b91c1c;">
        Sesi belum dimulai (session_started_at kosong). Silakan klik "Mulai Sesi" di dashboard dokter.
      </p>
    <?php elseif ($expired): ?>
      <p style="margin-top:12px; color:#b91c1c;">
        Sesi sudah melewati durasi. Silakan akhiri panggilan video.
      </p>
    <?php else: ?>
      <p id="docTimer" style="margin-top:8px; font-weight:bold;">
        Sisa waktu: menghitung...
      </p>

      <div id="jitsi-container"></div>
    <?php endif; ?>

    <p style="margin-top:24px;">
      <a class="btn alt" href="<?= $base; ?>/doctor/index.php">⬅ Kembali ke Dashboard Dokter</a>
    </p>
  </div>
</div>

<?php if (!$notStarted && !$expired): ?>
<script src="https://meet.jit.si/external_api.js"></script>
<script>
  let remainingDoc = <?= (int)$remainingSeconds ?>;
  const domain = "meet.jit.si";

  const options = {
    roomName: "<?= htmlspecialchars($jitsiRoomName); ?>",
    width: "100%",
    height: 600,
    parentNode: document.querySelector("#jitsi-container"),
    configOverwrite: {
      startWithAudioMuted: true,
      disableModeratorIndicator: true,
      enableWelcomePage: false,
      defaultLanguage: "id",
      prejoinPageEnabled: false
    },
    interfaceConfigOverwrite: {
      SHOW_JITSI_WATERMARK: false,
      SHOW_WATERMARK_FOR_GUESTS: false,
      SHOW_POWERED_BY: false,
      TOOLBAR_BUTTONS: [
        "microphone", "camera", "hangup",
        "chat", "fullscreen", "settings"
      ]
    }
  };

  const api = new JitsiMeetExternalAPI(domain, options);

  function formatTime(sec) {
    const m = String(Math.floor(sec / 60)).padStart(2, '0');
    const s = String(sec % 60).padStart(2, '0');
    return m + ':' + s;
  }

  function tickDoc() {
    const el = document.getElementById('docTimer');
    if (!el) return;

    if (remainingDoc >= 0) {
      el.textContent = 'Sisa waktu: ' + formatTime(remainingDoc);
    }

    if (remainingDoc > 0) {
      remainingDoc--;
      setTimeout(tickDoc, 1000);
    } else {
      el.textContent = 'Sesi sudah melewati durasi. Harap akhiri panggilan.';
      alert('Durasi sesi sudah habis. Silakan akhiri panggilan video.');
    }
  }

  tickDoc();
</script>
<?php endif; ?>
</body>
</html>
