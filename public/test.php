<?php
require_once __DIR__.'/../includes/db.php';
$config = require __DIR__.'/../includes/config.php';
$base   = $config['base_url'];
session_start();

if (($_SESSION['role'] ?? '') === 'admin') {
    header("Location: {$base}/admin/index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM tests WHERE slug = ?");
$stmt->execute(['mh-check']);
$test = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$test) { echo "Test tidak ditemukan"; exit; }

$questions = $pdo->prepare("SELECT * FROM test_questions WHERE test_id=? ORDER BY q_order");
$questions->execute([$test['id']]);
$qs    = $questions->fetchAll(PDO::FETCH_ASSOC);
$total = count($qs);

$showResult = false;
$resultText = "";
$score      = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($qs as $q) {
        $val   = intval($_POST['q'.$q['id']] ?? 0);
        $score += $val;
    }

    if ($score <= 6)          $resultText = "Skor rendah — kondisi umum baik.";
    elseif ($score <= 12)     $resultText = "Perhatian ringan — perhatikan gejala.";
    else                      $resultText = "Skor tinggi — pertimbangkan konsultasi.";

    if (isset($_SESSION['user_id'])) {
        $ins = $pdo->prepare(
            "INSERT INTO test_responses (user_id,test_id,score,result_text) VALUES (?,?,?,?)"
        );
        $ins->execute([$_SESSION['user_id'], $test['id'], $score, $resultText]);
    }

    $showResult = true;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($test['title']); ?></title>
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/test.css">
</head>
<body>
<div class="container">

<?php if (!$showResult): ?>

    <div class="card animate-card">
        <h3><?php echo htmlspecialchars($test['title']); ?></h3>

        <!-- Progress sederhana di atas -->
        <p style="text-align:center;margin-bottom:18px;color:#777;">
            Pertanyaan ke <span id="qNumber">1</span>/<?php echo $total; ?>
        </p>

        <form method="post" id="testForm">
            <?php foreach ($qs as $i => $q): ?>
    <div class="question-slide" data-index="<?php echo $i; ?>"
         style="<?php echo $i===0?'':'display:none'; ?>">
        <div class="question-text">
            <?php echo htmlspecialchars($q['question_text']); ?>
        </div>

        <div class="options">
            <?php
            $choices = [
                0 => 'Tidak Pernah',
                1 => 'Kadang-kadang',
                2 => 'Sering',
                3 => 'Hampir Selalu'
            ];
            foreach ($choices as $val => $label): ?>
                <button type="button"
                        class="option-btn"
                        data-qid="<?php echo $q['id']; ?>"
                        data-value="<?php echo $val; ?>">
                    <?php echo $label; ?>
                </button>
            <?php endforeach; ?>
            <input type="hidden"
                   name="q<?php echo $q['id']; ?>"
                   id="q<?php echo $q['id']; ?>"
                   value="">
        </div>
    </div>
<?php endforeach; ?>


            <div class="nav-buttons" style="margin-top:10px;">
                <button type="button" class="btn-nav" id="prevBtn">&lt;</button>
                <button type="button" class="btn-nav" id="nextBtn">&gt;</button>
            </div>

            <button type="submit" class="btn" id="submitBtn" style="display:none;margin-top:18px;">
                Selesai &amp; Lihat Hasil
            </button>
        </form>
    </div>

<?php else:
    $class = '';
    if ($score <= 6)          $class = 'low';
    elseif ($score <= 12)     $class = 'medium';
    else                      $class = 'high';

    // tentukan gambar sesuai kelas
    $img = $base.'/assets/images/result-medium.jpg'; // default
    if ($class === 'low')   $img = $base.'/assets/images/result-low.jpg';
    if ($class === 'high')  $img = $base.'/assets/images/result-high.jpg';
?>
<div class="result-card animate-result <?php echo $class; ?>">

    <div class="result-image">
      <div class="result-image-inner">
        <img src="<?php echo $img; ?>" alt="Ilustrasi hasil tes">
      </div>
    </div>


    <h3>Ringkasan Hasil Tes</h3>
    <p><?php echo htmlspecialchars($resultText); ?> (Skor <?php echo $score; ?>)</p>

    <?php if ($class === 'high'): ?>
        <p>
            Skor menunjukkan adanya gejala yang cukup signifikan. 
            Kamu disarankan berkonsultasi dengan psikolog agar bisa mendapatkan dukungan yang tepat.
        </p>
        <a class="btn" href="<?php echo $base; ?>/doctors.php">Ajukan Konsultasi</a>
    <?php elseif ($class === 'medium'): ?>
        <p>
            Ada beberapa hal yang perlu diperhatikan. Jaga pola hidup sehat dan pertimbangkan konseling bila keluhan berlanjut.
        </p>
        <a class="btn" href="<?php echo $base; ?>/doctors.php">Lihat Psikolog</a>
    <?php else: ?>
        <p>
            Kondisimu tampak cukup stabil. Pertahankan kebiasaan baik dan tetap peka terhadap perubahan perasaanmu.
        </p>
    <?php endif; ?>

    <p style="margin-top:18px;font-weight:600;">
        Terima kasih karena telah menggunakan fitur tes kesehatan mental di RASA.
    </p>

    <div style="margin-top:12px; display:flex; justify-content:center; gap:12px; flex-wrap:wrap;">
        <a class="btn" href="<?php echo $base; ?>/test.php">Ulangi Tes</a>
        <a class="btn" href="<?php echo $base; ?>/index.php" style="background:linear-gradient(45deg,#ff8a00,#ffb74d);">
            Kembali ke Beranda
        </a>
    </div>
</div>
<?php endif; ?>


</div>

<?php if (!$showResult): ?>
<script>
const slides  = document.querySelectorAll('.question-slide');
const total   = <?php echo $total; ?>;
let current   = 0;

const qNumber   = document.getElementById('qNumber');
const prevBtn   = document.getElementById('prevBtn');
const nextBtn   = document.getElementById('nextBtn');
const submitBtn = document.getElementById('submitBtn');

function updateUI(){
    slides.forEach((s,i)=>{ s.style.display = (i===current)?'block':'none'; });
    qNumber.textContent = current+1;

    prevBtn.disabled        = (current===0);
    nextBtn.style.display   = (current===total-1)?'none':'inline-block';
    // tombol selesai cuma muncul di slide terakhir
    submitBtn.style.display = (current===total-1)?'block':'none';
}

prevBtn.addEventListener('click', ()=>{
    if(current>0){ current--; updateUI(); }
});
nextBtn.addEventListener('click', ()=>{
    if(current<total-1){ current++; updateUI(); }
});

// klik opsi
document.querySelectorAll('.option-btn').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        const qid = btn.dataset.qid;
        const val = btn.dataset.value;

        document.getElementById('q'+qid).value = val;

        document.querySelectorAll('.option-btn[data-qid="'+qid+'"]').forEach(b=>{
            b.classList.toggle('active', b===btn);
        });
    });
});

// cegah submit kalau soal terakhir belum dipilih
document.getElementById('testForm').addEventListener('submit', function(e){
    const lastSlide = slides[total-1];
    const lastQid   = lastSlide.querySelector('.option-btn').dataset.qid;
    const lastVal   = document.getElementById('q'+lastQid).value;

    if(!lastVal){
        e.preventDefault();
        alert('Silakan pilih jawaban untuk pertanyaan terakhir dulu.');
    }
});

updateUI();
</script>

<?php endif; ?>
</body>
</html>
