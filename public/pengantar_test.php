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
    <title>Kata Pengantar Tes Psikologi</title>

    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{
            font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
            color:#1d1d1f;
            background:#f4fbf7;
        }

        .intro-wrap{
    display:flex;
    align-items:flex-start;
    justify-content:center;
    padding:80px 6vw 160px;  /* bawah 160px biar ada ruang untuk scroll */
}



        .intro-layout{
            display:flex;
            gap:48px;
            max-width:1200px;
            width:100%;
            align-items:center;
        }

        /* KARTU TEKS KIRI */
        .intro-card{
    flex:1.1;
    background:transparent;
    padding:0;
    border-radius:0;
    box-shadow:none;
    margin-left:80px;   /* geser teks ke kanan */
}

        .intro-eyebrow{
            font-size:13px;
            letter-spacing:0.12em;
            text-transform:uppercase;
            color:#ff8a00;
            margin-bottom:8px;
            font-weight:600;
        }
        .intro-card h1{
            font-size:34px;
            line-height:1.2;
            margin-bottom:18px;
        }
        .intro-card p{
            font-size:16px;
            line-height:1.6;
            margin-bottom:18px;
            color:#4a4a4f;
        }
        .intro-note{
            font-size:13px;
            color:#777;
            margin-bottom:22px;
        }

        .btn-primary{
            display:inline-block;
            padding:12px 26px;
            border-radius:999px;
            background:#ff8a00;
            color:#fff;
            text-decoration:none;
            font-weight:600;
            font-size:15px;
            box-shadow:0 8px 18px rgba(255,138,0,0.35);
            transition:transform .15s ease,box-shadow .15s ease,background .15s ease;
        }
        .btn-primary:hover{
            background:#e67600;
            transform:translateY(-1px);
            box-shadow:0 10px 22px rgba(255,138,0,0.45);
        }
        .helper-link{
            display:block;
            margin-top:10px;
            font-size:14px;
            color:#777;
        }

        /* GAMBAR KANAN UTUH */
        /* GAMBAR KANAN UTUH – LEBIH BESAR */
.intro-image-wrap{
    flex:2.0;                          /* kolom kanan lebih lebar */
    display:flex;
    justify-content:center;
    align-items:center;
}
.intro-image-wrap img{
    width:1000px;                       /* perbesar gambar */
    max-width:100%;
    height:auto;
    border-radius:40px;
    display:block;
}


        @media (max-width: 900px){
    .intro-wrap{
        padding:40px 16px 60px;
    }
    .intro-layout{
        flex-direction:column-reverse;
        gap:28px;
    }
    .intro-card{
        margin-left:0;          /* jangan geser di mobile */
        padding:0 4px;
    }
    .intro-card h1{
        font-size:26px;
    }
    .intro-image-wrap{
        flex:none;
        justify-content:center;
    }
    .intro-image-wrap img{
        width:260px;           /* cukup besar untuk layar HP */
    }
}

    </style>
</head>
<body>
<div class="intro-wrap">
    <div class="intro-layout">

        <div class="intro-card">
            <div class="intro-eyebrow">Tes Kesehatan Mental</div>
            <h1>Apakah kamu baik‑baik saja hari ini?</h1>
            <p>
                Luangkan beberapa menit untuk mengenali kondisi emosimu melalui Tes Psikologis ini.
                Hasilnya bisa membantumu memahami apa yang sedang kamu rasakan dan langkah apa
                yang bisa diambil selanjutnya.
            </p>
            <p class="intro-note">
                Tes ini bukan diagnosis medis, tetapi dapat menjadi langkah awal untuk lebih peduli
                pada kesehatan mentalmu.
            </p>

            <a href="<?php echo $base; ?>/petunjuk_test.php" class="btn-primary">
                Mulai Tes Sekarang
            </a>
            <span class="helper-link">
                Butuh bantuan? Kamu selalu bisa kembali dan mengulang tes kapan saja.
            </span>
        </div>

        <div class="intro-image-wrap">
            <img src="<?php echo $base; ?>/assets/images/test.png"
                 alt="Ilustrasi kebahagiaan dan hubungan keluarga">
        </div>

    </div>
</div>
</body>
</html>
