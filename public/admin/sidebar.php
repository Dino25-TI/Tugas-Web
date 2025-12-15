<?php
// fungsi kecil buat kasih kelas active
if (!function_exists('is_active_menu')) {
    function is_active_menu($name, $activeMenu) {
        return $name === $activeMenu ? 'active' : '';
    }
}
?>

<div class="admin-sidebar">
    <!-- MENU -->
    <div class="admin-menu">
        <div class="admin-menu-title">Menu</div>

        <!-- Booking & Pembayaran -->
        <a href="index.php"
           class="admin-link <?= is_active_menu('booking', $activeMenu ?? '') ?>">
            <span class="label">
                <span class="menu-icon">
                    <!-- icon dashboard -->
                    <svg viewBox="0 0 24 24">
                        <rect x="4" y="4" width="7" height="7" rx="2"></rect>
                        <rect x="13" y="4" width="7" height="7" rx="2"></rect>
                        <rect x="4" y="13" width="7" height="7" rx="2"></rect>
                        <rect x="13" y="13" width="7" height="7" rx="2"></rect>
                    </svg>
                </span>
                <span class="menu-text">Booking &amp; Pembayaran</span>
            </span>
        </a>

        <!-- Kelola User -->
        <a href="manage_user.php"
           class="admin-link <?= is_active_menu('user', $activeMenu ?? '') ?>">
            <span class="label">
                <span class="menu-icon">
                    <!-- icon user -->
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="9" r="3"></circle>
                        <path d="M6 19c1.3-2.2 3.2-3.5 6-3.5s4.7 1.3 6 3.5"></path>
                    </svg>
                </span>
                <span class="menu-text">Kelola User</span>
            </span>
        </a>

        <!-- Kelola Psikolog -->
        <a href="manage_psikolog.php"
           class="admin-link <?= is_active_menu('psikolog', $activeMenu ?? '') ?>">
            <span class="label">
                <span class="menu-icon">
                    <!-- icon psikolog / user plus -->
                    <svg viewBox="0 0 24 24">
                        <circle cx="9" cy="9" r="3"></circle>
                        <path d="M4 19c1.2-2.1 2.9-3.3 5-3.3"></path>
                        <path d="M17 8v6"></path>
                        <path d="M14 11h6"></path>
                    </svg>
                </span>
                <span class="menu-text">Kelola Psikolog</span>
            </span>
        </a>

        <!-- Kelola Komplain -->
        <a href="komplain.php"
           class="admin-link <?= is_active_menu('komplain', $activeMenu ?? '') ?>">
            <span class="label">
                <span class="menu-icon">
                    <!-- icon chat -->
                    <svg viewBox="0 0 24 24">
                        <path d="M5 5h14v10H8l-3 3z"></path>
                    </svg>
                </span>
                <span class="menu-text">Kelola Komplain</span>
            </span>
        </a>
         <!-- Laporan -->
        <a href="laporan.php"
            class="admin-link <?= is_active_menu('laporan', $activeMenu ?? '') ?>">
                <span class="label">
                    <span class="menu-icon">
            <svg viewBox="0 0 24 24">
                <path d="M5 19V9l3-2 4 3 4-6 3 2v13H5z"
                      fill="none" stroke-width="1.6"></path>
            </svg>
        </span>
        <span class="menu-text">Laporan</span>
    </span>
</a>

        <!-- Review Artikel Dokter -->
        <a href="review_artikel.php"
           class="admin-link <?= is_active_menu('artikel', $activeMenu ?? '') ?>">
            <span class="label">
                <span class="menu-icon">
                    <!-- icon document -->
                    <svg viewBox="0 0 24 24">
                        <path d="M7 4h8l3 3v13H7z"></path>
                        <path d="M9 9h6"></path>
                        <path d="M9 13h4"></path>
                    </svg>
                </span>
                <span class="menu-text">Review Artikel Dokter</span>
            </span>
        </a>

        <!-- Logout -->
        <a href="logout_admin.php"
           class="admin-link <?= is_active_menu('logout', $activeMenu ?? '') ?>">
            <span class="label">
                <span class="menu-icon">
                    <!-- icon logout -->
                    <svg viewBox="0 0 24 24">
                        <path d="M10 5H6v14h4"></path>
                        <path d="M14 12H4"></path>
                        <path d="M19 12l-5-4v8z"></path>
                    </svg>
                </span>
                <span class="menu-text">Logout</span>
            </span>
        </a>
    </div>
</div>
