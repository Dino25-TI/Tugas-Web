<?php
require_once __DIR__ . '/../includes/db.php';
$config = require __DIR__ . '/../includes/config.php';
$base   = rtrim($config['base_url'], '/');

session_start();

// Hanya psikolog yang boleh akses
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'psychologist') {
    header("Location: {$base}/login.php");
    exit;
}

$doctor_id  = (int)($_SESSION['doctor_id'] ?? 0);
$booking_id = (int)($_GET['booking_id'] ?? 0);

if ($doctor_id <= 0) {
    die("Data psikolog tidak ditemukan.");
}
if ($booking_id <= 0) {
    die("Booking tidak ditemukan.");
}

// Ambil info dokter
$stmt = $pdo->prepare("SELECT * FROM doctors WHERE id = ?");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$doctor) {
    die("Dokter tidak ditemukan.");
}

// Cari / buat chat room berdasarkan booking_id
$stmt = $pdo->prepare("SELECT * FROM chat_rooms WHERE booking_id = ?");
$stmt->execute([$booking_id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    // kalau belum ada room, buat baru (ISI doctor_id JUGA)
    $ins = $pdo->prepare("
        INSERT INTO chat_rooms (booking_id, doctor_id, created_at)
        VALUES (?, ?, NOW())
    ");
    $ins->execute([$booking_id, $doctor_id]);

    $room_id = (int)$pdo->lastInsertId();
} else {
    $room_id = (int)$room['id'];
}
// ambil data user dari booking ini
$stmt = $pdo->prepare("
    SELECT u.*
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    WHERE b.id = ?
");
$stmt->execute([$booking_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$user_photo_url = !empty($user['photo'])
    ? $base . '/assets/images/users/' . $user['photo']
    : $base . '/assets/images/Alok.jpg';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Chat Pasien - <?= htmlspecialchars($doctor['display_name']); ?></title>
    <link rel="stylesheet" href="<?= $base; ?>/assets/chat.css">
</head>
<body>

<div class="container">
  <div class="card chat-card">
    <h3>Chat Konsultasi</h3>
    <div id="chatBox" class="chat-box"></div>

    <form id="chatForm">
      <input type="text" id="messageInput" placeholder="Ketik pesan ke pasien..." required>
      <button type="submit">Kirim</button>
    </form>

    <form method="post" action="<?= $base; ?>/doctor/finish_booking.php" style="margin-top:12px;" class="end-form">
        <input type="hidden" name="booking_id" value="<?= $booking_id; ?>">
        <button type="submit" class="btn-end">Akhiri Sesi</button>
    </form>

    <p><a class="btn" href="<?= $base; ?>/doctor/index.php">⬅ Kembali ke Dashboard</a></p>
  </div>
</div>

<script>
const roomId      = <?= (int)$room_id ?>;
const chatBox     = document.getElementById('chatBox');
const userPhoto = '<?= $user_photo_url ?>';

function renderMessage(msg) {
    const row = document.createElement('div');

    // di halaman dokter: doctor -> msg-me (hijau kanan), user -> msg-other (putih kiri)
    const bubbleClass = (msg.sender === 'doctor') ? 'msg-me' : 'msg-other';

    row.className = 'chat-row ' + bubbleClass;

    const bubble = document.createElement('div');
    bubble.className = 'chat-message ' + bubbleClass;
    bubble.innerHTML = `
        <span class="msg-text">${msg.message}</span>
        <span class="msg-time">${
            new Date(msg.created_at || new Date())
              .toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})
        }</span>
    `;

    // avatar hanya untuk USER dan pakai foto user
    if (msg.sender === 'user') {
        const avatar = document.createElement('img');
        avatar.className = 'chat-avatar';
        avatar.src = userPhoto;
        row.appendChild(avatar);
        row.appendChild(bubble);
    } else {
        // dokter: tanpa foto
        row.appendChild(bubble);
    }

    chatBox.appendChild(row);
    chatBox.scrollTop = chatBox.scrollHeight;
}


function fetchMessages() {
    fetch('<?= $base; ?>/api/fetch_messages.php?room_id=' + roomId + '&user=doctor')
        .then(res => res.json())
        .then(data => {
            chatBox.innerHTML = '';
            (data.messages || []).forEach(msg => {
                renderMessage(msg);
            });
        })
        .catch(err => console.error(err));
}

// polling pesan tiap 1 detik
setInterval(fetchMessages, 1000);
fetchMessages();

document.getElementById('chatForm').addEventListener('submit', e => {
    e.preventDefault();
    const msgInput = document.getElementById('messageInput');
    const message  = msgInput.value.trim();
    if (!message) return;

    fetch('<?= $base; ?>/api/send_messages.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({room_id: roomId, sender: 'doctor', message})
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            renderMessage({
                sender: 'doctor',
                message: message,
                created_at: new Date().toISOString()
            });
            msgInput.value = '';
        }
    })
    .catch(err => console.error(err));
});
</script>
</body>
</html>
