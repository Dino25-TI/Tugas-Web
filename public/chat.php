<?php
require_once __DIR__.'/../includes/db.php';
$config = require __DIR__.'/../includes/config.php';
$base = $config['base_url'];
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: {$base}/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$doctor_id = intval($_GET['doctor_id'] ?? 0);

if(!$doctor_id) die("Dokter tidak ditemukan.");

// Ambil info dokter
$stmt = $pdo->prepare("SELECT * FROM doctors WHERE id=?");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$doctor) die("Dokter tidak ditemukan.");

// Cek atau buat chat room
$stmt = $pdo->prepare("SELECT * FROM chat_rooms WHERE user_id=? AND doctor_id=?");
$stmt->execute([$user_id, $doctor_id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$room){
    $ins = $pdo->prepare("INSERT INTO chat_rooms (user_id, doctor_id) VALUES (?, ?)");
    $ins->execute([$user_id, $doctor_id]);
    $room_id = $pdo->lastInsertId();
} else {
    $room_id = $room['id'];
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
const roomId = <?= $room_id ?>;
const chatBox = document.getElementById('chatBox');
let lastMessageId = 0;
const doctorPhoto = '<?= $doctor_photo_url ?>';

function renderMessage(msg){
    const row = document.createElement('div');
    row.className = 'chat-row ' + msg.sender;

    const bubble = document.createElement('div');
    bubble.className = 'chat-message ' + msg.sender;
    bubble.innerHTML = `
        <span class="msg-text">${msg.message}</span>
        <span class="msg-time">${new Date(msg.created_at || new Date()).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}</span>
    `;

    if(msg.sender === 'doctor'){
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
    .then(res=>res.json())
    .then(data=>{
        data.messages.forEach(msg=>{
            if(msg.id > lastMessageId){
                renderMessage(msg);
                lastMessageId = msg.id;
            }
        });
    });
}

setInterval(fetchMessages, 1000);
fetchMessages();

document.getElementById('chatForm').addEventListener('submit', e=>{
    e.preventDefault();
    const msgInput = document.getElementById('messageInput');
    const message = msgInput.value.trim();
    if(!message) return;

    fetch('<?= $base; ?>/api/send_messages.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({room_id: roomId, sender:'user', message})
    }).then(res=>res.json()).then(data=>{
        if(data.success){
            renderMessage({sender:'user', message:message, created_at:new Date().toISOString()});
            msgInput.value = '';

            // Auto-reply dokter
            setTimeout(()=> {
                fetch('<?= $base; ?>/api/auto_reply.php', {
                    method:'POST',
                    headers:{'Content-Type':'application/json'},
                    body: JSON.stringify({room_id: roomId})
                }).then(res=>res.json()).then(data=>{
                    if(data.success){
                        renderMessage({sender:'doctor', message:data.reply, created_at:new Date().toISOString()});
                    }
                });
            }, 500);
        }
    });
});
</script>
</body>
</html>
