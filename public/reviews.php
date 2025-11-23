<?php
require_once __DIR__.'/../includes/db.php';
$config = require __DIR__.'/../includes/config.php';
$base = $config['base_url'];
session_start();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Semua Ulasan - RASA</title>
    <link rel="stylesheet" href="<?php echo $base;?>/assets/style.css">
</head>

<body>

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
        </nav>
    </div>
</header>

<section class="container" style="margin-top:40px;">
    <h2>Semua Ulasan Pengguna</h2>

    <?php
    $reviews = $pdo->query("
        SELECT r.rating, r.comment, u.full_name
        FROM reviews r
        LEFT JOIN users u ON u.id = r.user_id
        ORDER BY r.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($reviews as $rev) {
        echo "<div class='review card' style='margin-bottom:15px;'>
                <p>\"".htmlspecialchars($rev['comment'])."\"</p>
                <div class='by'>— ".htmlspecialchars($rev['full_name'])." ({$rev['rating']}/5)</div>
              </div>";
    }
    ?>

</section>

<footer class="footer">
    © RASA - Ruang Asa dan Sadar
</footer>

</body>
</html>
