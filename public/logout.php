<?php
require_once __DIR__ . '/../includes/config.php';
$config = require __DIR__ . '/../includes/config.php';
$base   = $config['base_url'];

session_start();

// hapus semua data session
$_SESSION = [];
session_unset();
session_destroy();
// redirect ke halaman login
header("Location: {$base}/login.php");
exit;
