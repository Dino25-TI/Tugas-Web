<?php
session_start();
require __DIR__ . '/../includes/db.php';
$config = require __DIR__ . '/../includes/config.php';
$base   = $config['base_url'];

$slug = $_GET['slug'] ?? '';

$stmt = $pdo->prepare("
    SELECT title, content, category, published_at
    FROM articles
    WHERE slug = :slug AND is_published = 1
    LIMIT 1
");
$stmt->execute([':slug' => $slug]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    http_response_code(404);
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $article ? htmlspecialchars($article['title']) . ' - RASA' : 'Artikel tidak ditemukan - RASA'; ?></title>

    <link rel="stylesheet" href="<?= $base; ?>/assets/style.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          crossorigin="anonymous"
          referrerpolicy="no-referrer">

    <style>
        .page-wrapper {
            min-height: 60vh;
        }
        .section-article-detail {
            padding-top: 120px;
            padding-bottom: 80px;
        }
        .article-meta {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 8px;
            margin-bottom: 24px;
        }
        .article-content {
    line-height: 1.7;
    color: #0f172a;
    margin-bottom: 24px;
    font-family: "Times New Roman", Times, serif;
    text-align: justify;
    font-size: 1rem;
}
        .article-back{
    margin-top: 24px;
}

.article-back a{
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 10px;
    color: #2563eb;
    font-size: .9rem;
    font-weight: 500;
    background: rgba(37,99,235,0.06);
    text-decoration: none;
    transition: background .2s ease, transform .18s ease, box-shadow .18s ease;
}

.article-back a::before{
    font-size: 1rem;
}

.article-back a:hover{
    background: rgba(37,99,235,0.12);
    transform: translateX(-2px);
    box-shadow: 0 4px 10px rgba(15,23,42,0.12);
}

.article-back a:active{
    transform: translateX(-1px) scale(.98);
    box-shadow: 0 2px 6px rgba(15,23,42,0.18);
}

    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const logo = document.getElementById('logoRasa');
        if (!logo) return;

        logo.addEventListener('click', function (e) {
            e.preventDefault();
            logo.classList.toggle('is-active');
        });
    });
    </script>
</head>
<body>

<header class="header">
    <div class="container header-inner">
        <div class="brand">
            <img src="<?= $base; ?>/assets/images/icon-rasa.png"
                 class="icon-left"
                 id="logoRasa"
                 alt="RASA">
            <div class="brand-text">
                <div class="name">RASA</div>
                <div class="sub">Ruang Asa dan Sadar</div>
            </div>
        </div>

        <nav class="nav">
            <a href="<?= $base; ?>/index.php">Beranda</a>
            <a href="<?= $base; ?>/doctors.php">Psikolog</a>
            <a href="<?= $base; ?>/pengantar_test.php">Tes Psikogi</a>
            <a href="<?= $base; ?>/artikel.php">Artikel</a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?= $base; ?>/dashboard.php">Dashboard</a>
                <a href="<?= $base; ?>/logout.php" class="logout">Logout</a>
            <?php else: ?>
                <a href="<?= $base; ?>/login.php">Login</a>
                <a href="<?= $base; ?>/register.php" class="btn-nav">Daftar</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="page-wrapper">
    <section class="section section-article-detail">
        <div class="container">
            <?php if (!$article): ?>
                <h1>Artikel tidak ditemukan</h1>
                <p>Konten yang kamu cari tidak tersedia atau sudah di-unpublish.</p>
                <p class="article-back">
                    <a href="<?= $base; ?>/artikel.php">← Kembali ke daftar artikel</a>
                </p>
            <?php else: ?>
                <h1><?= htmlspecialchars($article['title']); ?></h1>

                <div class="article-meta">
                    <?php if (!empty($article['published_at'])): ?>
                        <?= htmlspecialchars(date('d M Y', strtotime($article['published_at']))); ?> •
                    <?php endif; ?>
                    <?= htmlspecialchars(ucfirst(str_replace('_',' ', $article['category']))); ?>
                </div>

                <article class="article-content">
                    <?= nl2br($article['content']); ?>
                </article>

                <p class="article-back">
                    <a href="<?= $base; ?>/artikel.php">← Kembali ke daftar artikel</a>
                </p>
            <?php endif; ?>
        </div>
    </section>
</main>

<footer class="footer">
  <div class="footer-inner">
        <div class="footer-col brand">
  <div class="footer-header">
    <div class="footer-logo-wrap">
      <img src="<?= $base; ?>/assets/images/icon-rasa.png" alt="RASA" class="footer-logo-img">
    </div>
    <div class="footer-title">
      <div class="brand-name">RASA</div>
      <div class="brand-sub">Ruang Asa dan Sadar</div>
    </div>
  </div>

  <p class="footer-desc">
    Memberikan ruang aman untuk berbagi cerita, memahami diri,
    dan merawat kesehatan mental dengan profesional.
  </p>

  <div class="footer-social">
    <a href="#"><i class="fa-brands fa-instagram"></i></a>
    <a href="#"><i class="fa-brands fa-whatsapp"></i></a>
    <a href="#"><i class="fa-brands fa-tiktok"></i></a>
   </div>

</div>

    <div class="footer-col">
      <h4>Layanan</h4>
      <a href="<?= $base; ?>/doctors.php">Konsultasi Individual</a>
      <a href="<?= $base; ?>/doctors.php">Konsultasi Keluarga &amp; Pasangan</a>
      <a href="<?= $base; ?>/pengantar_test.php">Tes Psikologi</a>
    </div>

    <div class="footer-col">
      <h4>Navigasi</h4>
      <a href="<?= $base; ?>/index.php">Beranda</a>
      <a href="<?= $base; ?>/doctors.php">Psikolog</a>
      <a href="<?= $base; ?>/reviews.php">Ulasan</a>
      <a href="<?= $base; ?>/artikel.php">Artikel</a>
      <a href="<?= $base; ?>/login.php">Login</a>
      <a href="<?= $base; ?>/register.php">Daftar</a>
    </div>

    <div class="footer-col contact">
      <h4>Kontak</h4>
      <p><i class="fas fa-map-marker-alt"></i> Jimbaran, Bali</p>
      <p><i class="fas fa-envelope"></i> nonoya@rasa.id</p>
      <p><i class="fas fa-phone"></i> +62 812‑0000‑0000</p>
    </div>
  </div>

  <div class="footer-bottom">
    <span>© <?= date('Y'); ?> RASA - Ruang Asa dan Sadar.</span>
    <div class="footer-links">
      <a href="#">Kebijakan Privasi</a>
      <a href="#">Syarat &amp; Ketentuan</a>
      <a href="#">Bantuan</a>
    </div>
  </div>
</footer>

</body>
</html>
