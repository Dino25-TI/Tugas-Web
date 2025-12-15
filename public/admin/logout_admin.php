<?php
session_start();

// hapus semua data session
session_unset();
session_destroy();

// load config untuk base url
require_once __DIR__ . '/../../includes/config.php';
$config = require __DIR__ . '/../../includes/config.php';
$base = $config['base_url'];

// kembali ke halaman login
header("Location: {$base}/login.php");
exit;
