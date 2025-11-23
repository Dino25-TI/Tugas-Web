<?php
require_once __DIR__.'/../includes/db.php';
$config = require __DIR__.'/../includes/config.php';
$base = $config['base_url'];
session_start();

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
          <h2><?php echo htmlspecialchars($d['display_name']); ?></h2>
          <span class="doc-title"><?php echo htmlspecialchars($d['title']); ?></span>
          <div class="doc-rating">⭐ <?php echo $d['rating']; ?>/5</div>
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
        <div class="doc-actions">
        <a class="btn-profile" href="<?php echo $base;?>/doctor.php?id=<?php echo $d['id']; ?>">Lihat Profil</a>
        <a class="btn-review" href="<?php echo $base;?>/review.php?doctor_id=<?php echo $d['id']; ?>">Beri Review</a>
</div>

      </div>

    </div>
  <?php endforeach; ?>
</div>

 <!-- Tombol Kembali ke Beranda di bawah -->
  <div class="back-btn-container">
      <a href="<?php echo $base; ?>" class="btn-back">Kembali ke Beranda</a>
  </div>

</div>

</body>
</html>
