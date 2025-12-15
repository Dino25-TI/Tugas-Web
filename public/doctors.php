<?php
require_once __DIR__ . '/../includes/db.php';
$config = require __DIR__ . '/../includes/config.php';
$base   = $config['base_url'];

session_start();

// Wajib login dan bukan admin
if (!isset($_SESSION['user_id'])) {
    header("Location: {$base}/login.php");
    exit;
}
if (($_SESSION['role'] ?? '') === 'admin') {
    header("Location: {$base}/admin/index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Cek langganan aktif
$stmtSub = $pdo->prepare(
    "SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active'"
);
$stmtSub->execute([$user_id]);
$isSubscribed = $stmtSub->fetch(PDO::FETCH_ASSOC);

// ============= PARAMETER FILTER =============
$mode     = $_GET['mode']  ?? 'schedule';          // schedule | all
$day      = isset($_GET['day']) ? (int)$_GET['day'] : 0; // 0=semua, 1..7
$time     = $_GET['time']  ?? 'all';               // all|pagi|siang|sore|malam
$startStr = $_GET['start'] ?? null;                // YYYY-MM-DD
$endStr   = $_GET['end']   ?? null;                // YYYY-MM-DD
$dateStr  = $_GET['date']  ?? null;                // YYYY-MM-DD
$q        = trim($_GET['q'] ?? '');                // keyword nama psikolog

// ============= DATA DOKTER (dengan search nama) =============
if ($q !== '') {
    $stmt = $pdo->prepare("
        SELECT *
        FROM doctors
        WHERE status = 'active'
          AND display_name LIKE ?
        ORDER BY rating DESC
    ");
    $keyword = "%{$q}%";
    $stmt->execute([$keyword]);
} else {
    $stmt = $pdo->query("
        SELECT *
        FROM doctors
        WHERE status = 'active'
        ORDER BY rating DESC
    ");
}
$allDoctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Hitung status online dari last_seen_at
$now = time();
$ONLINE_WINDOW = 5 * 60; // 5 menit
$dayOfWeekNow = (int) date('N'); // 1=Senin..7=Minggu
$timeNow = date('H:i'); // jam:menit sekarang

foreach ($allDoctors as &$doc) {
    $isOnline = false;

    // Ambil jadwal hari ini
    $doctorSchedulesToday = $schedules[$doc['id']] ?? [];
    $isInWorkingHours = false;

    foreach ($doctorSchedulesToday as $slot) {
        if ((int)$slot['day_of_week'] === $dayOfWeekNow && $slot['is_available']) {
            if ($timeNow >= $slot['start_time'] && $timeNow <= $slot['end_time']) {
                $isInWorkingHours = true;
                break;
            }
        }
    }

    $lastActive = !empty($doc['last_seen_at']) ? strtotime($doc['last_seen_at']) : false;
    $isRecentlyActive = ($lastActive !== false && ($now - $lastActive) <= $ONLINE_WINDOW);

    // Online jika aktif terakhir di web ATAU sedang jam kerja
    if ($isRecentlyActive || $isInWorkingHours) {
        $isOnline = true;
    }

    $doc['is_online_calc'] = $isOnline;
}
unset($doc);


// ============= DATA JADWAL DOKTER =============
$schedStmt = $pdo->query("
    SELECT doctor_id, day_of_week, start_time, end_time, is_available
    FROM doctor_schedules
    ORDER BY day_of_week, start_time
");
$schedulesRaw = $schedStmt->fetchAll(PDO::FETCH_ASSOC);

$schedules = [];
foreach ($schedulesRaw as $row) {
    $schedules[$row['doctor_id']][] = $row;
}

$days = [
    1 => 'Senin',
    2 => 'Selasa',
    3 => 'Rabu',
    4 => 'Kamis',
    5 => 'Jumat',
    6 => 'Sabtu',
    7 => 'Minggu'
];

// ============= RANGE TANGGAL UNTUK STRIP =============
// default: hari ini s/d +6 hari kalau belum ada start/end
if (!$startStr || !$endStr) {
    $today   = new DateTime();
    $startObj = $today;
    $endObj   = (clone $today)->modify('+6 days');
    $startStr = $startObj->format('Y-m-d');
    $endStr   = $endObj->format('Y-m-d');
} else {
    $startObj = new DateTime($startStr);
    $endObj   = new DateTime($endStr);
}

// tanggal aktif di strip
if (!$dateStr) {
    $dateStr = $startStr;
}

// ============= HELPER FILTER WAKTU =============
function matchTimeSlot(string $start, string $end, string $timeFilter): bool
{
    if ($timeFilter === 'all') {
        return true;
    }

    $s   = (int) substr($start, 0, 2);
    $e   = (int) substr($end,   0, 2);
    $mid = (int) floor(($s + $e) / 2);

    switch ($timeFilter) {
        case 'pagi':  return $mid >= 6  && $mid < 12;
        case 'siang': return $mid >= 12 && $mid < 16;
        case 'sore':  return $mid >= 16 && $mid < 19;
        case 'malam': return $mid >= 19 && $mid <= 23;
        default:      return true;
    }
}

// ============= FILTER DOKTER SESUAI MODE & WAKTU =============
$doctors = [];

if ($mode === 'all') {
    $doctors = $allDoctors;
} else {
    foreach ($allDoctors as $d) {
        $docSched = $schedules[$d['id']] ?? [];
        if (!$docSched) {
            continue;
        }

        if ($day === 0) {
            foreach ($docSched as $slot) {
                if (matchTimeSlot($slot['start_time'], $slot['end_time'], $time)) {
                    $doctors[] = $d;
                    break;
                }
            }
        } else {
            foreach ($docSched as $slot) {
                if (
                    (int)$slot['day_of_week'] === $day &&
                    matchTimeSlot($slot['start_time'], $slot['end_time'], $time)
                ) {
                    $doctors[] = $d;
                    break;
                }
            }
        }
    }
}

// ============= PERSIAPAN RANGE UNTUK STRIP =============
$period = new DatePeriod(
    $startObj,
    new DateInterval('P1D'),
    (clone $endObj)->modify('+1 day')
);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Jadwal Psikolog - RASA</title>

    <link rel="stylesheet" href="<?= $base; ?>/assets/doctor_style.css">

    <!-- Litepicker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css">
    <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>
</head>
<body>

<div class="page-wrap">

    <!-- TAB ATAS -->
    <div class="tab-bar">
        <a class="tab-btn <?= $mode === 'schedule' ? 'tab-active' : ''; ?>"
           href="?mode=schedule<?= $day ? '&day='.$day : ''; ?>">Jadwal Psikolog</a>

        <a class="tab-btn <?= $mode === 'all' ? 'tab-active' : ''; ?>"
           href="?mode=all">Semua Psikolog</a>
    </div>

    <!-- FILTER DROPDOWN + SEARCH -->
    <div class="filter-bar">
        <div class="filter-item">
            <select id="filterDay" name="day">
                <option value="0" <?= $day === 0 ? 'selected' : ''; ?>>Semua Hari</option>
                <?php foreach ($days as $num => $label): ?>
                    <option value="<?= $num; ?>" <?= $day === $num ? 'selected' : ''; ?>>
                        <?= $label; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-item">
            <select id="filterTime" name="time">
                <option value="all"   <?= $time === 'all'   ? 'selected' : ''; ?>>Semua Waktu</option>
                <option value="pagi"  <?= $time === 'pagi'  ? 'selected' : ''; ?>>Pagi</option>
                <option value="siang" <?= $time === 'siang' ? 'selected' : ''; ?>>Siang</option>
                <option value="sore"  <?= $time === 'sore'  ? 'selected' : ''; ?>>Sore</option>
                <option value="malam" <?= $time === 'malam' ? 'selected' : ''; ?>>Malam</option>
            </select>
        </div>

        <div class="filter-item filter-search">
            <input type="text" id="filterKeyword" name="q"
                   value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                   placeholder="Masukkan kata kunci">
            <button type="button" class="btn-search" id="btnSearch">🔍</button>
        </div>
    </div>

    <!-- STRIP TANGGAL DINAMIS -->
    <div class="date-strip">
        <button class="date-btn date-main" id="btnDateRange">
            <span class="icon">📅</span>
            <span class="text">
                <strong id="rangeLabel">Pilih Tanggal</strong><br>
                <small id="rangeSub">
                    <?= $startObj->format('j M'); ?> - <?= $endObj->format('j M'); ?>
                </small>
            </span>
        </button>

        <a class="date-btn <?= $day === 0 ? 'active' : ''; ?>"
           href="?mode=schedule&day=0">Semua Jadwal</a>

        <?php foreach ($period as $d): 
            $val       = $d->format('Y-m-d');
            $isActive  = ($val === $dateStr) ? 'active' : '';
            $hariIdx   = (int)$d->format('N'); // 1=Mon..7=Sun
            $labelHari = $days[$hariIdx] ?? '';
        ?>
            <a class="date-btn <?= $isActive; ?>"
               href="?mode=schedule&day=<?= $hariIdx; ?>&start=<?= $startObj->format('Y-m-d'); ?>&end=<?= $endObj->format('Y-m-d'); ?>&date=<?= $val; ?>">
                <?= $d->format('j M'); ?><br>
                <span><?= $labelHari; ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- LIST DOKTER -->
    <div class="doctor-list">
        <?php if (!$doctors): ?>
            <p>Tidak ada psikolog dengan jadwal pada pilihan ini.</p>
        <?php else: ?>
            <?php foreach ($doctors as $d): ?>
                <?php
                $docSched   = $schedules[$d['id']] ?? [];
                $jadwalText = 'Belum ada jadwal';

                if ($docSched) {
                    $slotToShow = $docSched[0];

                    if ($day !== 0) {
                        foreach ($docSched as $s) {
                            if ((int)$s['day_of_week'] === $day) {
                                $slotToShow = $s;
                                break;
                            }
                        }
                    }

                    $jadwalText =
                        ($days[$slotToShow['day_of_week']] ?? 'Hari ?') . ' • ' .
                        substr($slotToShow['start_time'], 0, 5) . ' - ' .
                        substr($slotToShow['end_time'],   0, 5) .
                        ($slotToShow['is_available'] ? ' • Tersedia' : ' • Penuh');
                }
                ?>
                <div class="doctor-row">
                    <div class="doctor-left">
                        <div class="doctor-photo">
                            <img src="<?= $base; ?>/assets/images/doctors/<?= htmlspecialchars($d['photo']); ?>"
                                 alt="<?= htmlspecialchars($d['display_name']); ?>">
                        </div>
                        <div class="doctor-main">
                            <div class="doctor-name"><?= htmlspecialchars($d['display_name']); ?></div>
                            <div class="doctor-title"><?= htmlspecialchars($d['title']); ?></div>
                            <div class="doctor-meta">
                                <span>⭐ <?= $d['rating']; ?>/5</span>
                            <?php if (!empty($d['is_online_calc'])): ?>
                                <span class="badge-online">Online</span>
                            <?php else: ?>
                                <span class="badge-offline">Offline</span>
                            <?php endif; ?>

                            </div>
                            <div class="doctor-schedule-text">
                                <?= $jadwalText; ?>
                            </div>
                        </div>
                    </div>
                    <div class="doctor-right">
                        <a href="<?= $base; ?>/doctor.php?id=<?= $d['id']; ?>" class="btn-outline">Lihat Detail</a>
                        <a href="<?= $base; ?>/book.php?doctor_id=<?= $d['id']; ?>" class="btn-primary">Booking Sesi</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="back-btn-container">
        <a href="<?= $base; ?>" class="btn-back">Kembali ke Beranda</a>
    </div>

</div><!-- /.page-wrap -->

<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnDate   = document.getElementById('btnDateRange');
    const label     = document.getElementById('rangeLabel');
    const sub       = document.getElementById('rangeSub');

    // Litepicker
    if (btnDate && window.Litepicker) {
        const picker = new Litepicker({
            element: btnDate,
            singleMode: false,
            numberOfMonths: 1,
            numberOfColumns: 1,
            format: 'YYYY-MM-DD',
            autoApply: true,
            setup: (picker) => {
                picker.on('selected', (date1, date2) => {
                    if (!date1 || !date2) return;

                    const start = date1.format('YYYY-MM-DD');
                    const end   = date2.format('YYYY-MM-DD');

                    label.textContent = 'Pilih Tanggal';
                    sub.textContent   = date1.format('D MMM') + ' - ' + date2.format('D MMM');

                    const params = new URLSearchParams(window.location.search);
                    params.set('mode', 'schedule');
                    params.set('start', start);
                    params.set('end',   end);
                    params.set('date',  start);

                    const jsDate = new Date(start);
                    let day = jsDate.getDay(); // 0=Sun..6=Sat
                    day = (day === 0) ? 7 : day; // 1=Mon..7=Sun
                    params.set('day', day.toString().trim());

                    window.location.search = params.toString();
                });
            }
        });

        btnDate.addEventListener('click', function (e) {
            e.preventDefault();
            picker.show();
        });
    }

    // FILTER HARI + WAKTU + SEARCH
    const selDay    = document.getElementById('filterDay');
    const selTime   = document.getElementById('filterTime');
    const inpQ      = document.getElementById('filterKeyword');
    const btnSearch = document.getElementById('btnSearch');

    function applyFilter() {
        const params = new URLSearchParams(window.location.search);

        params.set('mode', 'schedule');
        if (selDay)  params.set('day',  selDay.value);
        if (selTime) params.set('time', selTime.value);

        if (inpQ && inpQ.value.trim() !== '') {
            params.set('q', inpQ.value.trim());
        } else {
            params.delete('q');
        }

        window.location.search = params.toString();
    }

    if (selDay)   selDay.addEventListener('change', applyFilter);
    if (selTime)  selTime.addEventListener('change', applyFilter);
    if (btnSearch) btnSearch.addEventListener('click', applyFilter);
});
</script>

</body>
</html>
