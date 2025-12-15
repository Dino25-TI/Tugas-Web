<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once __DIR__ . '/../includes/db.php';
$config = require __DIR__ . '/../includes/config.php';
$base   = $config['base_url'];

if (($_SESSION['role'] ?? '') === 'admin') {
    header("Location: {$base}/admin/index.php");
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>RASA - Ruang Asa dan Sadar</title>

    <link rel="stylesheet" href="<?= $base; ?>/assets/style.css">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          crossorigin="anonymous"
          referrerpolicy="no-referrer">

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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap">
</head>
<body>

<!-- HEADER -->
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

<!-- HERO -->
<section class="hero">
    <div class="hero-overlay">
        <div class="hero-content container">
            <h1 class="hero-title">
                "Tarik napas, biarkan asa bertumbuh — ketenangan dimulai dari sadar."
            </h1>
            <p class="hero-sub">
                Kesehatan mental adalah penting. Merawatnya bukan tanda lemah, <br>
                tapi langkah berani menuju keseimbangan.
            </p>
            <div class="hero-btns">
                <a href="<?= $base; ?>/doctors.php" class="btn-primary">Book Session</a>
                <a href="<?= $base; ?>/pengantar_test.php" class="btn-secondary">Test Gratis</a>
            </div>
        </div>
    </div>
</section>

<!-- CARA KONSULTASI -->
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

<?php
// ambil 3 artikel terbaru yang sudah dipublish & approved
$artStmt = $pdo->query("
    SELECT title, slug, category, published_at
    FROM articles
    WHERE is_published = 1
      AND status = 'approved'
    ORDER BY published_at DESC
    LIMIT 3
");
$latestArticles = $artStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (!empty($latestArticles)): ?>
<section class="section section-articles-home">
    <div class="container">
        <h2 class="reviews-title">Artikel Terbaru</h2>
        <p class="reviews-subtitle">
            Baca tulisan dari psikolog kami tentang kesehatan mental.
        </p>

        <div class="article-list-home">
  <?php foreach ($latestArticles as $a): ?>
    <a class="article-card-home"
       href="<?= $base; ?>/artikel-detail.php?slug=<?= urlencode($a['slug']); ?>">
        <h3><?= htmlspecialchars($a['title']); ?></h3>
        <div class="article-meta-home">
          <?php if (!empty($a['published_at'])): ?>
            <?= htmlspecialchars(date('d M Y', strtotime($a['published_at']))); ?> •
          <?php endif; ?>
          <?= htmlspecialchars(ucfirst(str_replace('_',' ', $a['category']))); ?>
        </div>
        <span class="article-readmore">Baca selengkapnya ↗</span>
    </a>
  <?php endforeach; ?>
</div>


        <a href="<?= $base; ?>/artikel.php" class="btn-articles-all">
            Lihat Semua Artikel
        </a>
    </div>
</section>
<?php endif; ?>

<!-- ULASAN / TESTIMONI -->
<!-- ULASAN / TESTIMONI -->
<section class="reviews-section container">
    <h2 class="reviews-title">Testimoni Klien</h2>
    <p class="reviews-subtitle">
        Dengarkan pengalaman mereka yang telah menemukan bantuan melalui RASA.
    </p>

    <div class="reviews-grid">
        <?php
        // ambil maksimal 6 ulasan terbaru per user
        $stmt = $pdo->query("
            SELECT r.rating, r.comment, u.full_name
            FROM reviews r
            JOIN (
                SELECT user_id, MAX(created_at) AS last_created
                FROM reviews
                GROUP BY user_id
            ) AS x
                  ON x.user_id = r.user_id
                 AND x.last_created = r.created_at
            LEFT JOIN users u ON u.id = r.user_id
            ORDER BY r.created_at DESC
            LIMIT 6
        ");
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($reviews):

            // palet warna untuk icon dan avatar
            $palette = [
                '#f97316', // orange
                '#22c55e', // green
                '#6366f1', // indigo
                '#ec4899', // pink
                '#0ea5e9', // sky
                '#a855f7', // purple
                '#eab308', // amber
                '#14b8a6'  // teal
            ];

            foreach ($reviews as $index => $rev):

                // warna icon kutip atas berdasarkan urutan kartu
                $topColor = $palette[$index % count($palette)];

                // warna avatar konsisten per nama klien (pakai hash nama)
                $name       = (string) ($rev['full_name'] ?? '');
                $hash       = crc32($name);
                $avatarColor = $palette[$hash % count($palette)];
        ?>
        <article class="review-card">
            <div class="review-top-icon" style="background: <?= $topColor; ?>;">
                <i class="fas fa-quote-right"></i>
            </div>

            <div class="review-stars">
                <?php for ($i = 0; $i < 5; $i++): ?>
                    <i class="<?= $i < (int) $rev['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                <?php endfor; ?>
            </div>

            <p class="review-text">
                <?= htmlspecialchars($rev['comment']); ?>
            </p>

            <div class="review-footer">
                <div class="review-avatar" style="background: <?= $avatarColor; ?>;">
                    <?= strtoupper(substr($name, 0, 2)); ?>
                </div>
                <div class="review-meta">
                    <div class="review-name"><?= htmlspecialchars($name); ?></div>
                    <div class="review-role">Klien RASA</div>
                </div>
            </div>
        </article>
        <?php
            endforeach;
        else:
            echo '<p>Belum ada ulasan.</p>';
        endif;
        ?>
    </div>

    <div class="reviews-actions">
        <a href="<?= $base; ?>/reviews.php" class="btn-show-reviews">
            Lihat Semua Ulasan
        </a>
    </div>
</section>


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
      <a href="<?= $base; ?>/login.php">Login</a>
      <a href="<?= $base; ?>/register.php">Daftar</a>
      <a href="<?= $base; ?>/komplain_web.php">Laporkan Masalah Web</a>
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
