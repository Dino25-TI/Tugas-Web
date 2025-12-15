<?php
session_start();
require __DIR__ . '/../includes/db.php';
$config = require __DIR__ . '/../includes/config.php';
$base   = $config['base_url'];

// ambil semua artikel yang dipublish
$stmt = $pdo->query("
    SELECT title, slug, category, published_at
    FROM articles
    WHERE is_published = 1
    ORDER BY published_at DESC
");
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Artikel - RASA</title>

    <link rel="stylesheet" href="<?= $base; ?>/assets/style.css">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          crossorigin="anonymous"
          referrerpolicy="no-referrer">

    <style>
        body {
    background-image: url('<?= $base; ?>/assets/images/baca.png');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    color: #1a1f1d;
    line-height: 1.6;
}
        .page-wrapper {
            min-height: calc(100vh - 80px) /* dorong footer ke bawah */
        }
        .section-article {
            padding-top: 120px;
            padding-bottom: 80px;
            
        }
        .article-list {
            list-style: none;
            padding-left: 0;
            margin-top: 24px;
        }
        .article-item + .article-item {
            margin-top: 16px;
        }
        .article-item a {
            font-weight: 600;
            text-decoration: none;
            color: #0f172a;
        }
        .article-item a:hover {
            text-decoration: underline;
        }
        .article-meta {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 4px;
        }

.section-article{
    padding-top: 120px;
    padding-bottom: 80px;
}

.article-wrapper{
    max-width: 800px;
    padding: 32px 32px 28px;
    border-radius: 8px;
    background: rgba(255,255,255,0.92);
    box-shadow: 0 18px 40px rgba(15,23,42,0.16);
    backdrop-filter: blur(3px); /* boleh dihapus kalau berat */
    margin: 0 auto 80px;
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
    <section class="section section-article">
        <div class="container article-wrapper">
            <h1>Artikel & FAQ</h1>

            <?php if (empty($articles)): ?>
                <p>Belum ada konten.</p>
            <?php else: ?>
                <ul class="article-list">
                    <?php foreach ($articles as $a): ?>
                        <li class="article-item">
                            <a href="<?= $base; ?>/artikel-detail.php?slug=<?= urlencode($a['slug']); ?>">
                                <?= htmlspecialchars($a['title']); ?>
                            </a>
                            <?php if (!empty($a['published_at'])): ?>
                                <div class="article-meta">
                                    <?= htmlspecialchars(date('d M Y', strtotime($a['published_at']))); ?>
                                    • <?= htmlspecialchars(ucfirst(str_replace('_',' ', $a['category']))); ?>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
</main>

</body>
</html>
