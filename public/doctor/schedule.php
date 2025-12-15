<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
$config = require __DIR__ . '/../../includes/config.php';

$base = rtrim($config['base_url'], '/');

// Cek login & role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'psychologist') {
    header("Location: {$base}/login.php");
    exit;
}

$doctor_id = (int) ($_SESSION['doctor_id'] ?? 0);
if ($doctor_id <= 0) {
    die('Akun ini belum terhubung ke data psikolog.');
}

// Flash message
$flash = $_SESSION['flash_schedule'] ?? '';
unset($_SESSION['flash_schedule']);

$days = [
    1 => 'Senin',
    2 => 'Selasa',
    3 => 'Rabu',
    4 => 'Kamis',
    5 => 'Jumat',
    6 => 'Sabtu',
    7 => 'Minggu',
];

// Handle tambah jadwal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $day   = (int) ($_POST['day_of_week'] ?? 0);
    $start = $_POST['start_time'] ?? '';
    $end   = $_POST['end_time'] ?? '';

    if ($day >= 1 && $day <= 7 && $start !== '' && $end !== '') {
        $ins = $pdo->prepare("
            INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, is_available)
            VALUES (:doctor_id, :day, :start, :end, 1)
        ");
        $ins->execute([
            ':doctor_id' => $doctor_id,
            ':day'       => $day,
            ':start'     => $start,
            ':end'       => $end,
        ]);

        $_SESSION['flash_schedule'] = 'Jadwal berhasil ditambahkan.';
        header("Location: {$base}/doctor/schedule.php");
        exit;
    }
}

// Handle hapus jadwal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int) ($_POST['schedule_id'] ?? 0);

    if ($id > 0) {
        $del = $pdo->prepare("
            DELETE FROM doctor_schedules
            WHERE id = ? AND doctor_id = ?
        ");
        $del->execute([$id, $doctor_id]);

        $_SESSION['flash_schedule'] = 'Jadwal berhasil dihapus.';
        header("Location: {$base}/doctor/schedule.php");
        exit;
    }
}

// Ambil jadwal dokter
$stmt = $pdo->prepare("
    SELECT id, day_of_week, start_time, end_time, is_available
    FROM doctor_schedules
    WHERE doctor_id = ?
    ORDER BY day_of_week, start_time
");
$stmt->execute([$doctor_id]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Jadwal Psikolog</title>
    <link rel="stylesheet" href="<?= $base; ?>/assets/doctor_dashboard.css">
    <link rel="stylesheet" href="<?= $base; ?>/assets/doctor_schedule.css">
</head>
<body>
<div class="doclayout">
    <header class="topbar-main">
        <div class="topbar-left">
            <button class="topbar-menu" type="button" onclick="toggleSidebar()">☰</button>

            <div class="topbar-brand">
                <span class="brand-dot">
                    <?= strtoupper(substr($doctorProfile['display_name'] ?? 'P', 0, 1)); ?>
                </span>
                <span class="brand-text">RASA Psikolog</span>
            </div>
        </div>

        <div class="topbar-spacer"></div>

        <div class="topbar-user">
            <span class="topbar-user-avatar">
                <?= strtoupper(substr($doctorProfile['display_name'] ?? 'P', 0, 1)); ?>
            </span>
            <span class="topbar-user-name">
                <?= htmlspecialchars($doctorProfile['display_name'] ?? 'Psikolog'); ?>
            </span>
        </div>
    </header>

    <!-- SIDEBAR KIRI -->
    <aside class="doc-sidebar">
        <nav class="doc-menu">
            <a href="<?= $base; ?>/doctor/index.php">
                <span class="icon">🏠</span>
                <span class="label">Dashboard</span>
            </a>
            <a href="<?= $base; ?>/doctor/profile.php">
                <span class="icon">👤</span>
                <span class="label">Profil Saya</span>
            </a>
            <a href="<?= $base; ?>/doctor/schedule.php" class="active">
                <span class="icon">📅</span>
                <span class="label">Jadwal Saya</span>
            </a>
            <a href="<?= $base; ?>/doctor/history.php">
                <span class="icon">📜</span>
                <span class="label">Riwayat Pasien</span>
            </a>
            <a href="<?= $base; ?>/doctor/my_reviews.php">
                <span class="icon">💬</span>
                <span class="label">Ulasan untuk Saya</span>
            </a>
            <a href="<?= $base; ?>/doctor/artikel_saya.php">
                <span class="icon">📄</span>
                <span class="label">Artikel Saya</span>
            </a>
            <a href="<?= $base; ?>/logout.php">
                <span class="icon">⏏</span>
                <span class="label">Logout</span>
            </a>
        </nav>
    </aside>

    <main class="doc-main">
        <div class="docdash-main--schedule">
            <?php if ($flash): ?>
                <div class="toast-success">
                    <?= htmlspecialchars($flash); ?>
                </div>
            <?php endif; ?>

            <!-- Header jadwal opsional -->
            <!--
            <header class="docdash-header-schedule">
                <div>
                    <h1>Jadwal Praktik</h1>
                    <p>Atur jam konsultasi yang tersedia.</p>
                </div>
            </header>
            -->

            <section class="card">
                <h2>Tambah Jadwal Baru</h2>

                <form method="post" class="schedule-form">
                    <input type="hidden" name="action" value="add">

                    <div class="schedule-row">
                        <div class="schedule-col">
                            <label for="day_of_week">Hari</label>
                            <select id="day_of_week" name="day_of_week" required>
                                <option value="">Pilih hari</option>
                                <?php foreach ($days as $num => $label): ?>
                                    <option value="<?= $num; ?>"><?= $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="schedule-row schedule-row--time">
                        <div class="schedule-col">
                            <label for="start_time">Jam Mulai</label>
                            <input id="start_time" type="time" name="start_time" required>
                        </div>

                        <div class="schedule-col">
                            <label for="end_time">Jam Selesai</label>
                            <input id="end_time" type="time" name="end_time" required>
                        </div>
                    </div>

                    <div class="schedule-actions">
                        <button type="submit" class="btn-schedule-main">Simpan Jadwal</button>
                    </div>
                </form>
            </section>

            <section class="card">
                <h2>Jadwal Saat Ini</h2>

                <?php if ($schedules): ?>
                    <table class="table-small">
                        <thead>
                        <tr>
                            <th>Hari</th>
                            <th>Jam</th>
                            <th>Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($schedules as $s): ?>
                            <tr>
                                <td><?= $days[$s['day_of_week']] ?? $s['day_of_week']; ?></td>
                                <td><?= substr($s['start_time'], 0, 5); ?> - <?= substr($s['end_time'], 0, 5); ?></td>
                                <td>
                                    <form
                                        method="post"
                                        style="display:inline"
                                        onsubmit="return confirm('Hapus jadwal ini?');"
                                    >
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="schedule_id" value="<?= $s['id']; ?>">
                                        <button type="submit" class="btn-outline">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Belum ada jadwal. Tambahkan minimal satu jadwal di atas.</p>
                <?php endif; ?>
            </section>
        </div>
    </main>
</div>

<script>
function toggleSidebar() {
    const layout  = document.querySelector('.doclayout');
    const sidebar = document.querySelector('.doc-sidebar');
    if (!layout || !sidebar) return;

    layout.classList.toggle('sidebar-open');
    sidebar.classList.toggle('expand');
}
</script>
</body>
</html>
