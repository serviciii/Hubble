<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$userLogin = wajibLogin($pdo);

/**
 * Mengambil kode bersih meskipun pengguna menempelkan URL lengkap.
 */
function ekstrakKode(string $input): string
{
    $input = trim($input);
    if (strpos($input, 'kode=') !== false) {
        parse_str((string) parse_url($input, PHP_URL_QUERY), $params);
        if (!empty($params['kode'])) {
            $input = $params['kode'];
        }
    } elseif (strpos($input, '/') !== false) {
        $path = (string) (parse_url($input, PHP_URL_PATH) ?: $input);
        $segmen = explode('/', trim($path, '/'));
        $input = end($segmen);
    }
    return strtoupper(trim($input));
}

// 1. Jika dibuka via Link Join langsung (?kode=HBL...)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['kode'])) {
    $kode = ekstrakKode((string) $_GET['kode']);

    $stmt = $pdo->prepare("SELECT * FROM rapat WHERE kode_rapat = ? AND status != 'dibatalkan' LIMIT 1");
    $stmt->execute([$kode]);
    $rapat = $stmt->fetch();

    if (!$rapat) {
        setFlash('join_gagal', 'Link rapat tidak valid atau rapat sudah dibatalkan.');
        redirect('dashboard.php');
    }

    // Cek apakah sudah terdaftar
    $stmtCek = $pdo->prepare('SELECT * FROM peserta_rapat WHERE id_rapat = ? AND id_user = ? LIMIT 1');
    $stmtCek->execute([$rapat['id_rapat'], $userLogin['id_user']]);
    $terdaftar = $stmtCek->fetch();

    if ($terdaftar) {
        if ($terdaftar['role'] === 'Moderator') {
            redirect('moderator/detail-rapat.php?id=' . $rapat['id_rapat']);
        }
        redirect('peserta/detail-rapat.php?id=' . $rapat['id_rapat']);
    }

    // Jika rapat memakai passcode, buka modal join di dashboard untuk input passcode
    if (!empty($rapat['passcode_hash'])) {
        setFlash('open_join_kode', $rapat['kode_rapat']);
        redirect('dashboard.php');
    }

    // Tanpa passcode: otomatis daftarkan sebagai Anggota
    $stmtIns = $pdo->prepare("INSERT INTO peserta_rapat (id_rapat, id_user, role) VALUES (?, ?, 'Anggota')");
    $stmtIns->execute([$rapat['id_rapat'], $userLogin['id_user']]);

    redirect('peserta/detail-rapat.php?id=' . $rapat['id_rapat']);
}

// 2. Jika dikirim lewat form modal Join Rapat di Dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputMentah = trim($_POST['kode'] ?? '');
    $passcode    = (string) ($_POST['passcode'] ?? '');

    if ($inputMentah === '') {
        setFlash('join_gagal', 'Kode atau link rapat wajib diisi.');
        redirect('dashboard.php');
    }

    $kode = ekstrakKode($inputMentah);

    $stmt = $pdo->prepare("SELECT * FROM rapat WHERE kode_rapat = ? AND status != 'dibatalkan' LIMIT 1");
    $stmt->execute([$kode]);
    $rapat = $stmt->fetch();

    if (!$rapat) {
        setOld(['kode' => $inputMentah]);
        setFlash('join_gagal', 'Kode atau link rapat tidak ditemukan.');
        redirect('dashboard.php');
    }

    $stmtCek = $pdo->prepare('SELECT * FROM peserta_rapat WHERE id_rapat = ? AND id_user = ? LIMIT 1');
    $stmtCek->execute([$rapat['id_rapat'], $userLogin['id_user']]);
    $terdaftar = $stmtCek->fetch();

    if ($terdaftar) {
        $tujuan = $terdaftar['role'] === 'Moderator'
            ? base_url('moderator/detail-rapat.php?id=' . $rapat['id_rapat'])
            : base_url('peserta/detail-rapat.php?id=' . $rapat['id_rapat']);

        setFlash('join_berhasil_url', $tujuan);
        redirect('dashboard.php');
    }

    // Cek passcode jika rapat dilindungi passcode
    if (!empty($rapat['passcode_hash'])) {
        if ($passcode === '' || !password_verify($passcode, $rapat['passcode_hash'])) {
            setOld(['kode' => $inputMentah]);
            setFlash('join_gagal', 'Passcode yang kamu masukkan tidak sesuai.');
            redirect('dashboard.php');
        }
    }

    $stmtIns = $pdo->prepare("INSERT INTO peserta_rapat (id_rapat, id_user, role) VALUES (?, ?, 'Anggota')");
    $stmtIns->execute([$rapat['id_rapat'], $userLogin['id_user']]);

    setFlash('join_berhasil_url', base_url('peserta/detail-rapat.php?id=' . $rapat['id_rapat']));
    redirect('dashboard.php');
}

redirect('dashboard.php');
