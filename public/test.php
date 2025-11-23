<?php
require_once __DIR__.'/../includes/db.php';
$config = require __DIR__.'/../includes/config.php';
$base = $config['base_url'];
session_start();

$stmt = $pdo->prepare("SELECT * FROM tests WHERE slug = ?");
$stmt->execute(['mh-check']);
$test = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$test){ echo "Test tidak ditemukan"; exit; }

$questions = $pdo->prepare("SELECT * FROM test_questions WHERE test_id=? ORDER BY q_order");
$questions->execute([$test['id']]);
$qs = $questions->fetchAll(PDO::FETCH_ASSOC);

$showResult = false;
$resultText = "";
$score = 0;

if($_SERVER['REQUEST_METHOD']==='POST'){
    foreach($qs as $q){
        $val = intval($_POST['q'.$q['id']] ?? 0);
        $score += $val;
    }

    if($score <= 6) $resultText = "Skor rendah — kondisi umum baik.";
    elseif($score <= 12) $resultText = "Perhatian ringan — perhatikan gejala.";
    else $resultText = "Skor tinggi — pertimbangkan konsultasi.";

    if(isset($_SESSION['user_id'])){
        $ins = $pdo->prepare("INSERT INTO test_responses (user_id,test_id,score,result_text) VALUES (?,?,?,?)");
        $ins->execute([$_SESSION['user_id'],$test['id'],$score,$resultText]);
    }

    $showResult = true;
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title><?php echo htmlspecialchars($test['title']); ?></title>
<link rel="stylesheet" href="<?php echo $base;?>/assets/test.css">
</head>
<body>
<div class="container">

<?php if(!$showResult): ?>
  <!-- FORM TEST -->
  <div class="card animate-card">
    <h3><?php echo htmlspecialchars($test['title']); ?></h3>
    <form method="post">
      <?php foreach($qs as $q): ?>
        <div class="question">
          <div class="question-text"><?php echo htmlspecialchars($q['question_text']); ?></div>
          <select name="q<?php echo $q['id']; ?>">
            <option value="0">Tidak sama sekali</option>
            <option value="1">Beberapa hari</option>
            <option value="2">Lebih dari 1 minggu</option>
            <option value="3">Hampir setiap hari</option>
          </select>
        </div>
      <?php endforeach; ?>
      <button class="btn" type="submit">Kirim</button>
    </form>
  </div>
<?php else: 
// CLASS sesuai skor
$class = '';
if($score <= 6) $class = 'low';
elseif($score <= 12) $class = 'medium';
else $class = 'high';
?>
  <!-- HASIL TEST -->
  <div class="result-card animate-result <?php echo $class; ?>">
    <h3>Hasil Test</h3>
    <p><?php echo $resultText; ?> (skor <?php echo $score; ?>)</p>
    <a class="btn" href="<?php echo $base; ?>/index.php">Kembali</a>
  </div>
<?php endif; ?>

</div>
</body>
</html>
