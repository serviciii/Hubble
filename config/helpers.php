<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Menghasilkan URL lengkap aplikasi secara otomatis (mendukung localhost/hubble maupun virtual host).
 */
function base_url(string $path = ''): string
{
    static $rootUrl = null;

    if ($rootUrl === null) {
        $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $docRoot  = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
        $appRoot  = str_replace('\\', '/', dirname(__DIR__));

        $basePath = '';
        if ($docRoot !== '' && strpos($appRoot, $docRoot) === 0) {
            $basePath = substr($appRoot, strlen($docRoot));
        }

        $rootUrl = $scheme . '://' . $host . rtrim($basePath, '/');
    }

    $cleanPath = ltrim($path, '/');
    return $cleanPath === '' ? $rootUrl . '/' : $rootUrl . '/' . $cleanPath;
}

/**
 * Mengamankan teks output HTML agar terhindar dari XSS.
 */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Mengarahkan browser ke halaman lain di dalam aplikasi.
 */
function redirect(string $path): void
{
    header('Location: ' . base_url($path));
    exit;
}

/**
 * Menyimpan pesan sementara (flash message) di session.
 */
function setFlash(string $key, $value): void
{
    $_SESSION['_flash'][$key] = $value;
}

/**
 * Mengambil dan langsung menghapus flash message dari session.
 */
function getFlash(string $key, $default = null)
{
    if (isset($_SESSION['_flash'][$key])) {
        $val = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);
        return $val;
    }
    return $default;
}

/**
 * Menyimpan isian form lama saat validasi gagal.
 */
function setOld(array $data): void
{
    $_SESSION['_old'] = $data;
}

/**
 * Mengambil isian form lama untuk ditampilkan kembali di input.
 */
function old(string $key, $default = ''): string
{
    if (isset($_SESSION['_old'][$key])) {
        $val = $_SESSION['_old'][$key];
        unset($_SESSION['_old'][$key]);
        return (string) $val;
    }
    return (string) $default;
}

/**
 * Mengambil data pengguna yang sedang login dari database.
 */
function getUserLogin(PDO $pdo): ?array
{
    static $cachedUser = null;
    static $fetched = false;

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    if (!$fetched) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id_user = ? LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        $cachedUser = $stmt->fetch() ?: null;
        $fetched = true;
    }

    return $cachedUser;
}

/**
 * Memastikan halaman hanya bisa dibuka oleh pengguna yang sudah login.
 */
function wajibLogin(PDO $pdo): array
{
    $user = getUserLogin($pdo);
    if (!$user) {
        unset($_SESSION['user_id']);
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && !empty($_SERVER['REQUEST_URI'])) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
        }
        redirect('auth/masuk.php');
    }
    return $user;
}

/**
 * Memastikan halaman login/daftar hanya dibuka jika belum login.
 */
function wajibTamu(): void
{
    if (!empty($_SESSION['user_id'])) {
        redirect('dashboard.php');
    }
}

/**
 * Format tanggal ke Bahasa Indonesia (contoh: Kamis, 19 November 2026).
 */
function formatTanggalIndo(?string $tanggal, bool $denganHari = true, bool $denganJam = false, bool $bulanSingkat = false): string
{
    if (empty($tanggal)) {
        return '-';
    }

    $timestamp = strtotime($tanggal);
    if ($timestamp === false) {
        return $tanggal;
    }

    $namaHari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $namaBulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $namaBulanPendek = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
    ];

    $hari  = $namaHari[(int) date('w', $timestamp)];
    $tgl   = date('d', $timestamp);
    $bulan = $bulanSingkat
        ? $namaBulanPendek[(int) date('n', $timestamp)]
        : $namaBulan[(int) date('n', $timestamp)];
    $tahun = date('Y', $timestamp);

    $hasil = ($denganHari ? $hari . ', ' : '') . "{$tgl} {$bulan} {$tahun}";

    if ($denganJam) {
        $hasil .= ', ' . date('H.i', $timestamp);
    }

    return $hasil;
}
