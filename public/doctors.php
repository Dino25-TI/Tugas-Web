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

// Ambil daftar dokter
$stmt = $pdo->query("SELECT * FROM doctors ORDER BY rating DESC");
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Psikolog - RASA</title>
<link rel="stylesheet" href="<?php echo $base; ?>/assets/doctor_style.css">
</head>
<body>

<div class="header-bar">
  <h1>Psikolog Kami</h1>
  <p>Pilih psikolog profesional sesuai kebutuhanmu</p>
</div>

<div class="container">

 <div class="doctor-grid">
<?php foreach($doctors as $d): ?>
    <div class="doctor-card">
        <div class="doc-top">
            <div class="doc-photo">
                <img src="<?=$base?>/assets/images/doctors/<?=htmlspecialchars($d['photo'])?>" 
                     alt="<?=htmlspecialchars($d['display_name'])?>">
            </div>
            <div class="doc-info">
                <h2><?= htmlspecialchars($d['display_name']); ?></h2>
                <span class="doc-title"><?= htmlspecialchars($d['title']); ?></span>
                <div class="doc-rating">⭐ <?= $d['rating']; ?>/5</div>
            </div>
        </div>

        <div class="doc-status">
            <?php if($d['is_online']): ?>
                <span class="status-badge online">● Online</span>
            <?php else: ?>
                <span class="status-badge offline">● Offline</span>
            <?php endif; ?>
        </div>

        <div class="doc-actions">
            <a class="btn-profile" href="<?= $base; ?>/doctor.php?id=<?= $d['id']; ?>">Lihat Profil</a>
            <a class="btn-chat" href="<?= $base; ?>/chat.php?doctor_id=<?= $d['id']; ?>">Chat Dokter</a>
        </div>
    </div>
<?php endforeach; ?>
</div>


<div class="back-btn-container">
  <a href="<?= $base; ?>" class="btn-back">Kembali ke Beranda</a>
</div>

</div>
</body>
</html>
