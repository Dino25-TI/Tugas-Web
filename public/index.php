<?php
error_reporting(E_ALL);
ini_set('display_errors',1);

session_start();

require_once __DIR__.'/../includes/db.php';
$config = require __DIR__.'/../includes/config.php';
$base = $config['base_url'];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>RASA - Ruang Asa dan Sadar</title>
  <link rel="stylesheet" href="<?php echo $base;?>/assets/style.css">
</head>

<body>

<!-- HEADER -->
<header class="header">

  <div class="container header-inner">
    <div class="brand">

      <img src="<?php echo $base;?>/assets/images/icon-rasa.png" class="icon-left">

      <div class="brand-text">
        <div class="name">RASA</div>
        <div class="sub">Ruang Asa dan Sadar</div>
      </div>
    </div>

    <nav class="nav">
      <a href="<?php echo $base;?>/index.php">Beranda</a>
      <a href="<?php echo $base;?>/doctors.php">Psikolog</a>
      <a href="<?php echo $base;?>/test.php">Test Psikologi</a>

      <?php if(isset($_SESSION['user_id'])): ?>
        <a href="<?php echo $base;?>/dashboard.php">Dashboard</a>
        <a href="<?php echo $base;?>/logout.php" class="logout">Logout</a>
      <?php else: ?>
        <a href="<?php echo $base;?>/login.php">Login</a>
        <a href="<?php echo $base;?>/register.php" class="btn-nav">Daftar</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<!-- HERO -->
<section class="hero">
  <div class="hero-inner">

    <h1 class="hero-title">
      "Tarik napas, biarkan asa bertumbuh — ketenangan dimulai dari sadar."
    </h1>

    <p class="hero-sub">
      Kesehatan mental adalah penting. Merawatnya bukan tanda lemah, tapi
      langkah berani menuju keseimbangan.
    </p>

    <div class="hero-btns">
      <a href="<?php echo $base;?>/doctors.php" class="btn-primary">Book Session</a>
      <a href="<?php echo $base;?>/test.php" class="btn-secondary">Test Gratis</a>
    </div>

  </div>
</section>

<!-- FEATURES — Konsultasi -->
<section class="features container">
  <div class="card">
    <h3>Bagaimana Konsultasi Bekerja</h3>

    <div class="how-wrapper">
      <div class="how-grid">

        <div class="how-card">
          <div class="how-icon">🧠</div>
          <h4>Login & Pilih Psikolog</h4>
          <p>Masuk akun dan pilih psikolog sesuai kebutuhanmu.</p>
        </div>

        <div class="how-card">
          <div class="how-icon">💬</div>
          <h4>Pilih Paket & Jadwal</h4>
          <p>Tentukan jenis layanan dan waktu yang kamu inginkan.</p>
        </div>

        <div class="how-card">
          <div class="how-icon">💳</div>
          <h4>Bayar & Konfirmasi</h4>
          <p>Lakukan pembayaran aman dan tunggu konfirmasi otomatis.</p>
        </div>

        <div class="how-card">
          <div class="how-icon">🎧</div>
          <h4>Masuk Ruang Konsultasi</h4>
          <p>Mulai sesi online dengan psikolog pilihanmu.</p>
        </div>

      </div>
    </div>

  </div>
</section>


<!-- ULASAN (Dipisah) -->
<section class="reviews-section container">
  <h2>Ulasan Pengguna</h2>

  <div class="review-list">

    <?php
    $review = $pdo->query("
        SELECT r.rating, r.comment, u.full_name
        FROM reviews r
        LEFT JOIN users u ON u.id = r.user_id
        ORDER BY r.created_at DESC
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);

    if ($review) {
        echo "<div class='review'>
                <p>\"".htmlspecialchars($review['comment'])."\"</p>
                <div class='by'>— ".htmlspecialchars($review['full_name'])." ({$review['rating']}/5)</div>
              </div>";
    } else {
        echo "<p>Belum ada ulasan.</p>";
    }
    ?>

    <a href="<?php echo $base; ?>/reviews.php" class="btn-show-reviews">
      Lihat Semua Ulasan
    </a>

  </div>
</section>

<footer class="footer">
  © RASA - Ruang Asa dan Sadar
</footer>

</body>
</html>
