<?php
// Pengaturan zona waktu Indonesia (WIB)
date_default_timezone_set('Asia/Jakarta');

$dbHost = '127.0.0.1';
$dbName = 'hubble';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Koneksi ke database gagal: ' . htmlspecialchars($e->getMessage()));
}
