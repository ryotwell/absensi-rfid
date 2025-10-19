<?php
// check_login.php
include 'db_connect.php';

// Pastikan session sudah dimulai (atau include db_connect.php yang sudah memulainya)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user belum login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
    // Redirect ke halaman login jika belum login
    header("location: login.php");
    exit;
}

// Opsional: Untuk keamanan tambahan, Anda dapat memeriksa IP atau User Agent
// untuk mencegah pembajakan session, tetapi sesi standar sudah cukup.
?>