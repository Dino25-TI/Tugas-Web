<?php
session_start();
session_unset();
session_destroy();

$config = require __DIR__ . '/../includes/config.php';
$base = $config['base_url'];

header("Location: $base/index.php");
exit;

