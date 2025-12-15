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

$user_id    = (int)$_SESSION['user_id'];
$doctor_id  = (int)($_GET['doctor_id'] ?? 0);
$booking_id = (int)($_GET['booking_id'] ?? 0);

if ($doctor_id <= 0) {
    die("Dokter tidak ditemukan.");
}

// Ambil info dokter
$stmt = $pdo->prepare("SELECT * FROM doctors WHERE id = ?");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$doctor) {
    die("Dokter tidak ditemukan.");
}

/* ========== CEK BATAS 24 JAM UNTUK CHAT ========== */
if ($booking_id > 0) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM bookings
        WHERE id = ? AND user_id = ? AND doctor_id = ?
    ");
    $stmt->execute([$booking_id, $user_id, $doctor_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        die("Booking tidak valid.");
    }

    // booking sudah diakhiri dokter → tidak boleh chat lagi
    if ($booking['status'] === 'done') {
        die("Sesi chat untuk booking ini sudah selesai. Silakan buat booking baru jika ingin konsultasi lagi.");
    }

    // Batasi untuk paket chat atau chat+video (kalau mau only chat saja, ganti array jadi ['chat'])
    if (in_array($booking['consultation_type'], ['chat','both'], true)) {
        $start = new DateTime($booking['scheduled_at']);
        $end   = (clone $start)->add(new DateInterval('P1D')); // +24 jam
        $now   = new DateTime('now');

        if ($now > $end) {
            die("Masa aktif chat 24 jam untuk booking ini sudah berakhir.");
        }
    }
}


/* ========== CEK / BUAT CHAT ROOM ========== */
if ($booking_id > 0) {
    // prioritas: 1 booking = 1 room
    $stmt = $pdo->prepare("SELECT * FROM chat_rooms WHERE booking_id = ?");
    $stmt->execute([$booking_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        $ins = $pdo->prepare("
            INSERT INTO chat_rooms (user_id, doctor_id, booking_id)
            VALUES (?, ?, ?)
        ");
        $ins->execute([$user_id, $doctor_id, $booking_id]);
        $room_id = (int)$pdo->lastInsertId();
    } else {
        $room_id = (int)$room['id'];
    }
} else {
    // fallback lama: kombinasi user + dokter
    $stmt = $pdo->prepare("SELECT * FROM chat_rooms WHERE user_id = ? AND doctor_id = ?");
    $stmt->execute([$user_id, $doctor_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        $ins = $pdo->prepare("INSERT INTO chat_rooms (user_id, doctor_id) VALUES (?, ?)");
        $ins->execute([$user_id, $doctor_id]);
        $room_id = (int)$pdo->lastInsertId();
    } else {
        $room_id = (int)$room['id'];
    }
}

$doctor_photo_url = !empty($doctor['photo'])
    ? $base . '/assets/images/doctors/' . $doctor['photo']
    : $base . '/assets/images/default-doctor.png';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Chat dengan <?= htmlspecialchars($doctor['display_name']); ?></title>
<link rel="stylesheet" href="<?= $base; ?>/assets/chat.css">
</head>
<body>

<div class="container">
  <div class="card chat-card">
    <h3>Chat dengan <?= htmlspecialchars($doctor['display_name']); ?></h3>
    <div id="chatBox" class="chat-box"></div>

    <form id="chatForm">
      <input type="text" id="messageInput" placeholder="Ketik pesan..." required>
      <button type="submit">Kirim</button>
    </form>

    <p><a class="btn" href="<?= $base; ?>/doctors.php">🏠 Kembali ke Daftar Psikolog</a></p>
  </div>
</div>

<script>
const roomId      = <?= $room_id ?>;
const chatBox     = document.getElementById('chatBox');
const doctorPhoto = '<?= $doctor_photo_url ?>';

function renderMessage(msg){
      const row = document.createElement('div');

    // mapping: di HALAMAN USER
    // kalau pengirimnya 'user'  -> msg-me (hijau kanan)
    // kalau pengirimnya 'doctor'-> msg-other (putih kiri)
    const bubbleClass = (msg.sender === 'user') ? 'msg-me' : 'msg-other';

    row.className = 'chat-row ' + bubbleClass;

    const bubble = document.createElement('div');
    bubble.className = 'chat-message ' + bubbleClass;
    bubble.innerHTML = `
        <span class="msg-text">${msg.message}</span>
        <span class="msg-time">${
            new Date(msg.created_at || new Date())
              .toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})
        }</span>
    `;

    if (msg.sender === 'doctor') {
        const avatar = document.createElement('img');
        avatar.className = 'chat-avatar';
        avatar.src = doctorPhoto;
        row.appendChild(avatar);
        row.appendChild(bubble);
    } else {
        row.appendChild(bubble);
    }

    chatBox.appendChild(row);
    chatBox.scrollTop = chatBox.scrollHeight;
}

function fetchMessages(){
    fetch('<?= $base; ?>/api/fetch_messages.php?room_id=' + roomId + '&user=user')
        .then(res => res.json())
        .then(data => {
            chatBox.innerHTML = '';
            data.messages.forEach(msg => {
                renderMessage(msg);
            });
        });
}

setInterval(fetchMessages, 1000);
fetchMessages();

document.getElementById('chatForm').addEventListener('submit', e => {
    e.preventDefault();
    const msgInput = document.getElementById('messageInput');
    const message  = msgInput.value.trim();
    if (!message) return;

    fetch('<?= $base; ?>/api/send_messages.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({room_id: roomId, sender:'user', message})
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            renderMessage({
                sender: 'user',
                message: message,
                created_at: new Date().toISOString()
            });
            msgInput.value = '';
        }
    });
});
const bookingId = <?= (int)$booking_id; ?>;

function checkBookingStatus() {
    if (!bookingId) return;

    fetch('<?= $base; ?>/check_status.php?booking_id=' + bookingId)
        .then(res => res.json())
        .then(data => {
            console.log('status booking:', data);
            if (data.status === 'done') {
                alert('Sesi chat ini sudah diakhiri oleh psikolog.');
                window.location.href = '<?= $base; ?>/dashboard.php';
            }
        })
        .catch(err => console.error('Error cek status:', err));
}

setInterval(checkBookingStatus, 5000);
</script>
</body>
</html>
