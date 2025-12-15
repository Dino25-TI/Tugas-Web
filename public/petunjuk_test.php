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
    <title>Petunjuk Tes Kepribadian</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{
            font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
            color:#1d1d1f;
            background:#f4fbf7;
        }
        .wrapper{
            min-height:100vh;
            display:flex;
            align-items:flex-start;
            justify-content:center;
            padding:100px 16px 60px;
        }
        .content{
            max-width:900px;
            width:100%;
            text-align:center;
        }
        .hero-image{
            width:220px;
            height:220px;
            border-radius:24px;
            overflow:hidden;
            margin:0 auto 32px;
            box-shadow:0 10px 30px rgba(0,0,0,0.12);
        }
        .hero-image img{
            width:100%;
            height:100%;
            object-fit:cover;
            display:block;
        }
        h1{
            font-size:26px;
            margin-bottom:18px;
        }
        .desc{
            font-size:15px;
            line-height:1.7;
            color:#4a4a4f;
            max-width:720px;
            margin:0 auto 28px;
        }
        .btn-main{
            display:inline-block;
            padding:11px 32px;
            border-radius:999px;
            background:#ff8a00;
            color:#fff;
            font-weight:600;
            text-decoration:none;
            margin-bottom:32px;
            box-shadow:0 10px 24px rgba(255,138,0,0.35);
        }
        .notice-box{
            margin:0 auto;
            max-width:860px;
            background:#fff7e6;
            border-radius:18px;
            padding:22px 26px;
            text-align:left;
            border:1px solid #ffd18a;
        }
        .notice-item{
            display:flex;
            align-items:flex-start;
            gap:14px;
            margin-bottom:10px;
            font-size:14px;
            color:#4a4a4f;
        }
        .notice-badge{
            width:26px;
            height:26px;
            border-radius:50%;
            background:#003b73;
            color:#fff;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:13px;
            flex-shrink:0;
        }
        @media (max-width:768px){
            .wrapper{padding-top:80px;}
            .hero-image{width:180px;height:180px;}
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="content">

        <div class="hero-image">
            <img src="<?php echo $base; ?>/assets/images/test_kepribadian.jpg"
                 alt="Ilustrasi tes kepribadian">
        </div>

        <h1>Tes Kepribadian</h1>

        <p class="desc">
            Tes ini tidak ditujukan untuk mendiagnosis gangguan psikologis, tetapi untuk membantumu
            mengenali kondisi dirimu saat ini. Gunakan hasilnya sebagai gambaran awal sebelum
            berkonsultasi dengan profesional bila diperlukan.
        </p>

        <a href="<?php echo $base; ?>/test.php" class="btn-main">
            Mulai Tes
        </a>

        <div class="notice-box">
            <div class="notice-item">
                <div class="notice-badge">1</div>
                <div>Tes ini bukan untuk mendiagnosis gangguan psikologis, melainkan untuk memberi gambaran kondisimu saat ini.</div>
            </div>
            <div class="notice-item">
                <div class="notice-badge">2</div>
                <div>Tidak ada jawaban benar atau salah. Pilih jawaban yang paling menggambarkan dirimu.</div>
            </div>
            <div class="notice-item">
                <div class="notice-badge">3</div>
                <div>Hindari memilih jawaban netral agar hasil tes lebih maksimal.</div>
            </div>
        </div>

    </div>
</div>
</body>
</html>
