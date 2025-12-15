<?php
require_once __DIR__.'/../../includes/db.php';
header('Content-Type: application/json');


$data = json_decode(file_get_contents('php://input'), true);
$room_id = $data['room_id'] ?? null;

if(!$room_id){
    echo json_encode(['success'=>false]);
    exit;
}

// Ambil pesan terakhir user
$stmt = $pdo->prepare("SELECT message FROM chat_messages WHERE room_id=? AND sender='user' ORDER BY id DESC LIMIT 1");
$stmt->execute([$room_id]);
$last = $stmt->fetchColumn();

if(!$last) {
    echo json_encode(['success'=>false]);
    exit;
}

$lastLower = strtolower($last);

// Daftar kata kunci & balasan (variasi)
$responses = [
    ['keywords'=>['halo','hi','hai'], 
     'reply'=>["Halo! Saya siap mendengarkan kamu 😊 Ceritakan apa yang sedang kamu rasakan.",
               "Hai! Senang bisa ngobrol denganmu. Apa yang ingin kamu ceritakan hari ini?"]],

    ['keywords'=>['stress','stres','tekanan'], 
     'reply'=>["Saya mengerti kamu sedang tertekan. Apa yang paling mengganggu pikiranmu saat ini?",
               "Terlihat kamu merasa stress. Bisa ceritakan apa yang membuatmu tertekan?"]],

    ['keywords'=>['cemas','anxiety','khawatir'], 
     'reply'=>["Rasa cemas itu wajar. Bisa ceritakan apa yang membuat kamu cemas belakangan ini?",
               "Aku memahami rasa cemasmu. Apa yang sedang menjadi kekhawatiran terbesar saat ini?"]],

    ['keywords'=>['sedih','down','kecewa','galau'], 
     'reply'=>["Kamu tidak sendiri. Aku ada di sini. Apa yang membuatmu merasa sedih?",
               "Terasa sedih ya? Boleh ceritakan lebih lanjut apa yang membuatmu down."]],

    ['keywords'=>['bingung','tidak tahu','ragu'], 
     'reply'=>["Tidak apa-apa merasa bingung. Bisa ceritakan lebih detail apa yang sedang kamu pikirkan?",
               "Aku mengerti kalau kamu merasa ragu. Apa yang paling membuatmu bingung saat ini?"]],

    ['keywords'=>['marah','kesal','frustrasi'], 
     'reply'=>["Saya mengerti perasaan marah atau kesalmu. Apa yang membuatmu merasa begitu?",
               "Terlihat kamu sedang kesal. Boleh ceritakan apa yang membuatmu frustrasi?"]],

    ['keywords'=>['lelah','capek','letih'], 
     'reply'=>["Kedengarannya kamu sangat lelah. Apa yang paling menguras energimu akhir-akhir ini?",
               "Aku mengerti, kamu merasa letih. Bisa ceritakan lebih lanjut apa yang membuatmu capek?"]],

    ['keywords'=>['takut','khawatir'], 
     'reply'=>["Rasa takut itu wajar. Bisa ceritakan apa yang membuatmu merasa takut?",
               "Aku mengerti rasa takutmu. Apa yang membuatmu merasa khawatir saat ini?"]],

    ['keywords'=>['bosan','jenuh'], 
     'reply'=>["Bosan atau jenuh itu wajar. Apa yang biasanya membuatmu merasa begitu?",
               "Aku paham kalau kamu jenuh. Bisa ceritakan apa yang membuatmu bosan?"]]
];

// Fungsi random untuk variasi jawaban
function pickReply($replies){
    return $replies[array_rand($replies)];
}

// Cek kata kunci
$reply = "Baik, saya mengerti. Bisa ceritakan lebih detail ya?";
foreach($responses as $r){
    foreach($r['keywords'] as $kw){
        if(str_contains($lastLower, $kw)){
            $reply = pickReply($r['reply']);
            break 2;
        }
    }
}

// Simpan balasan dokter
$stmt2 = $pdo->prepare("INSERT INTO chat_messages (room_id, sender, message) VALUES (?, 'doctor', ?)");
$stmt2->execute([$room_id, $reply]);

echo json_encode(['success'=>true, 'reply'=>$reply]);
