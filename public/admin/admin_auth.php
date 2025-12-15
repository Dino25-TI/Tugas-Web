<?php
// RASA/public/admin/admin_auth.php
session_start();

if (!isset($_SESSION['admin_id'])) {
    // sesuaikan dengan halaman login admin kamu
    header('Location: index.php');
    exit;
}
