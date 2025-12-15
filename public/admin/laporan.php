<?php
session_start();
require dirname(__DIR__, 2) . '/includes/db.php';
$config = require dirname(__DIR__, 2) . '/includes/config.php';
$base   = $config['base_url'];

// Cek login & role admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: {$base}/login.php");
    exit;
}
$adminName = $_SESSION['full_name'] ?? 'Admin';

// Ambil bulan & tahun dari query string
$bulan = isset($_GET['bulan']) ? (int) $_GET['bulan'] : (int) date('m');
$tahun = isset($_GET['tahun']) ? (int) $_GET['tahun'] : (int) date('Y');

if ($bulan < 1 || $bulan > 12) {
    $bulan = (int) date('m');
}
if ($tahun < 2000 || $tahun > (int) date('Y') + 1) {
    $tahun = (int) date('Y');
}

// Periode awal & akhir
$from = sprintf('%04d-%02d-01', $tahun, $bulan);
$to   = date('Y-m-t 23:59:59', strtotime($from));

// RINGKASAN SESI + PENDAPATAN
$sqlAgg = "SELECT 
              COUNT(DISTINCT b.id) AS total_sesi,
              SUM(CASE WHEN b.consultation_type = 'chat'  THEN 1 ELSE 0 END) AS total_chat,
              SUM(CASE WHEN b.consultation_type = 'video' THEN 1 ELSE 0 END) AS total_video,
              SUM(CASE WHEN b.consultation_type = 'both'  THEN 1 ELSE 0 END) AS total_both,
              COALESCE(SUM(p.amount),0) AS total_pendapatan
           FROM bookings b
           LEFT JOIN payments p 
                  ON p.booking_id = b.id 
                 AND p.status = 'success'
           WHERE b.status IN ('approved','done')
             AND b.created_at BETWEEN :from AND :to";

$stAgg = $pdo->prepare($sqlAgg);
$stAgg->execute([':from' => $from, ':to' => $to]);
$agg = $stAgg->fetch(PDO::FETCH_ASSOC);

// SESI PER PSIKOLOG
$sqlDoc = "SELECT d.display_name,
                  COUNT(b.id) AS total_sesi
           FROM bookings b
           JOIN doctors d ON d.id = b.doctor_id
           WHERE b.status IN ('approved','done')
             AND d.status = 'active'
             AND b.created_at BETWEEN :from AND :to
           GROUP BY d.id
           ORDER BY total_sesi DESC";

$stDoc   = $pdo->prepare($sqlDoc);
$stDoc->execute([':from' => $from, ':to' => $to]);
$byDoctor = $stDoc->fetchAll(PDO::FETCH_ASSOC);

// Data grafik pendapatan 12 bulan terakhir
$sqlChart = "
    SELECT 
        DATE_FORMAT(b.created_at, '%Y-%m') AS ym,
        COALESCE(SUM(p.amount),0) AS total_pendapatan,
        COUNT(DISTINCT b.id)      AS total_sesi
    FROM bookings b
    LEFT JOIN payments p 
           ON p.booking_id = b.id 
          AND p.status = 'success'
    WHERE b.status IN ('approved','done')
    GROUP BY ym
    ORDER BY ym ASC
    LIMIT 12
";
$stChart   = $pdo->query($sqlChart);
$chartRows = $stChart->fetchAll(PDO::FETCH_ASSOC);

$chartData = [
    'labels'   => array_column($chartRows, 'ym'),
    'revenue'  => array_map('floatval', array_column($chartRows, 'total_pendapatan')),
    'sessions' => array_map('intval', array_column($chartRows, 'total_sesi')),
];

// tandai menu aktif
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan - RASA</title>
    <link rel="stylesheet" href="<?= $base; ?>/assets/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<header class="admin-topbar-main">
    <div class="topbar-left">
        <div class="topbar-logo">
            <button class="sidebar-toggle" type="button"
                    onclick="document.body.classList.toggle('sidebar-collapsed')">
                ☰
            </button>
            <span class="logo-mark">R</span>
            <span class="logo-text">RASA Admin</span>
        </div>

        <div class="topbar-page">
            <div class="topbar-title">Kelola Laporan Keuangan</div>
        </div>
    </div>

    <div class="topbar-right">
        <button class="user-toggle" type="button" id="userToggle">
            <div class="user-toggle-avatar">
                <span><?= strtoupper(substr($adminName, 0, 1)); ?></span>
            </div>
            <span class="user-toggle-name"><?= htmlspecialchars($adminName); ?></span>
            <span class="user-toggle-caret">▾</span>
        </button>

        <div class="user-dropdown" id="userDropdown">
            <a href="<?= $base; ?>/admin/logout_admin.php" class="user-dropdown-item">
                <span class="user-dropdown-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="4" y="3" width="9" height="18" rx="2"></rect>
                        <circle cx="11" cy="12" r="0.8" fill="white"></circle>
                        <path d="M14 12h6"></path>
                        <path d="M18 9l3 3-3 3"></path>
                    </svg>
                </span>
                <span>Sign out</span>
            </a>
        </div>
    </div>
</header>

<div class="admin-shell">
    <?php
    $activeMenu = 'laporan';
    include 'sidebar.php';
    ?>

    <main class="admin-main">
        <!-- Filter bulan & tahun -->
        <div class="table-card">
            <div class="table-header">
                <h3>Pilih Periode</h3>
            </div>
            <div class="table-wrapper">
                <form method="get" class="admin-filter">
                    <div class="filter-group">
                        <label for="bulan">Bulan</label>
                        <select id="bulan" name="bulan">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m; ?>" <?= $m === $bulan ? 'selected' : ''; ?>>
                                    <?= date('F', mktime(0,0,0,$m,1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="tahun">Tahun</label>
                        <input
                            id="tahun"
                            type="number"
                            name="tahun"
                            value="<?= htmlspecialchars($tahun); ?>"
                            min="2000"
                            max="<?= date('Y') + 1; ?>">
                    </div>

                    <button type="submit" class="btn btn-confirm filter-apply">Terapkan</button>
                </form>
            </div>
        </div>

        <!-- Ringkasan -->
        <div class="table-card summary-card">
            <div class="summary-header">
                <div>
                    <h3>Ringkasan</h3>
                    <p class="summary-subtitle">
                        Periode <?= sprintf('%02d', $bulan); ?>/<?= $tahun; ?>
                    </p>
                </div>
            </div>

            <div class="summary-grid">
                <div class="summary-item">
                    <span class="label">Total sesi</span>
                    <span class="value"><?= (int)($agg['total_sesi'] ?? 0); ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Chat</span>
                    <span class="value"><?= (int)($agg['total_chat'] ?? 0); ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Video</span>
                    <span class="value"><?= (int)($agg['total_video'] ?? 0); ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Both</span>
                    <span class="value"><?= (int)($agg['total_both'] ?? 0); ?></span>
                </div>
                <div class="summary-item total">
                    <span class="label">Total pendapatan</span>
                    <span class="value">
                        Rp<?= number_format((float)($agg['total_pendapatan'] ?? 0), 0, ',', '.'); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Grafik Pendapatan -->
        <div class="table-card">
            <div class="table-header">
                <h3>Grafik Pendapatan</h3>
            </div>
            <div class="table-wrapper">
                <canvas id="revenueChart" style="max-height:260px;"></canvas>
            </div>
        </div>

        <!-- Sesi per psikolog -->
        <div class="table-card">
            <div class="table-header">
                <h3>Sesi per Psikolog</h3>
            </div>
            <div class="table-wrapper">
                <table class="table-admin">
                    <thead>
                    <tr>
                        <th>Psikolog</th>
                        <th>Jumlah Sesi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($byDoctor as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['display_name'] ?? ''); ?></td>
                            <td><?= (int)($row['total_sesi'] ?? 0); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
const revenueChartData = <?= json_encode($chartData); ?>;
const ctx  = document.getElementById('revenueChart').getContext('2d');
const data = revenueChartData;

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: data.labels,
        datasets: [
            {
                label: 'Pendapatan',
                data: data.revenue,
                backgroundColor: 'rgba(75, 91, 253, 0.25)',
                borderColor: '#4b5bfd',
                borderWidth: 1.5,
                borderRadius: 6,
                yAxisID: 'y'
            },
            {
                label: 'Jumlah Sesi',
                data: data.sessions,
                type: 'line',
                borderColor: '#16a34a',
                pointBackgroundColor: '#16a34a',
                tension: 0.3,
                borderWidth: 2,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        layout: { padding: { top: 8, right: 16, bottom: 8, left: 8 } },
        plugins: {
            legend: { position: 'top', labels: { usePointStyle: true } }
        },
        scales: {
            y: {
                type: 'linear',
                position: 'left',
                ticks: {
                    callback: v => 'Rp' + v.toLocaleString('id-ID')
                }
            },
            y1: {
                type: 'linear',
                position: 'right',
                grid: { drawOnChartArea: false }
            }
        }
    }
});

// dropdown user
const userToggle   = document.getElementById('userToggle');
const userDropdown = document.getElementById('userDropdown');

if (userToggle && userDropdown) {
    userToggle.addEventListener('click', function (e) {
        e.stopPropagation();
        userDropdown.classList.toggle('open');
    });

    document.addEventListener('click', function () {
        userDropdown.classList.remove('open');
    });
}
</script>
</body>
</html>
