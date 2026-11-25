<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$userLogin = wajibLogin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}

$aksi = $_POST['aksi'] ?? '';

// 1. BUAT RAPAT BARU
if ($aksi === 'buat') {
    $judul        = trim($_POST['judul'] ?? '');
    $tanggal      = trim($_POST['tanggal'] ?? '');
    $lokasi       = trim($_POST['lokasi'] ?? '');
    $waktuMulai   = trim($_POST['waktu_mulai'] ?? '');
    $waktuSelesai = trim($_POST['waktu_selesai'] ?? '');
    $linkMeeting  = trim($_POST['link_meeting'] ?? '');
    $passcode     = trim($_POST['passcode'] ?? '');
    $agenda       = trim($_POST['agenda'] ?? '');
    $berulang     = ($_POST['berulang'] ?? '0') === '1' ? 1 : 0;
    $polaBerulang = $berulang === 1 ? trim($_POST['pola_berulang'] ?? '') : null;

    $oldInput = [
        'judul'         => $judul,
        'tanggal'       => $tanggal,
        'lokasi'        => $lokasi,
        'waktu_mulai'   => $waktuMulai,
        'waktu_selesai' => $waktuSelesai,
        'link_meeting'  => $linkMeeting,
        'agenda'        => $agenda,
        'berulang'      => (string) $berulang,
        'pola_berulang' => (string) $polaBerulang,
    ];

    if ($judul === '' || $tanggal === '' || $waktuMulai === '' || $waktuSelesai === '') {
        setFlash('error', 'Judul, tanggal, waktu mulai, dan waktu selesai wajib diisi.');
        setOld($oldInput);
        redirect('moderator/buat-rapat.php');
    }

    if ($waktuSelesai <= $waktuMulai) {
        setFlash('error', 'Waktu selesai harus lebih akhir dari waktu mulai.');
        setOld($oldInput);
        redirect('moderator/buat-rapat.php');
    }

    if ($berulang === 1 && !in_array($polaBerulang, ['harian', 'mingguan', 'bulanan'], true)) {
        setFlash('error', 'Pilih pola berulang (Harian, Mingguan, atau Bulanan).');
        setOld($oldInput);
        redirect('moderator/buat-rapat.php');
    }

    // Buat kode rapat unik (contoh: HBL9A4F1C)
    do {
        $kodeRapat = 'HBL' . strtoupper(bin2hex(random_bytes(3)));
        $stmtCek   = $pdo->prepare('SELECT id_rapat FROM rapat WHERE kode_rapat = ? LIMIT 1');
        $stmtCek->execute([$kodeRapat]);
    } while ($stmtCek->fetch());

    $passcodePlain = $passcode !== '' ? $passcode : null;
    $passcodeHash  = $passcode !== '' ? password_hash($passcode, PASSWORD_DEFAULT) : null;

    $pdo->beginTransaction();
    try {
        $stmtIns = $pdo->prepare("
            INSERT INTO rapat (
                dibuat_oleh, judul, tanggal, waktu_mulai, waktu_selesai,
                lokasi, link_meeting, passcode_hash, passcode, agenda,
                berulang, pola_berulang, kode_rapat, status, presensi_dibuka
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'terjadwal', 0)
        ");
        $stmtIns->execute([
            $userLogin['id_user'],
            $judul,
            $tanggal,
            $waktuMulai,
            $waktuSelesai,
            $lokasi !== '' ? $lokasi : null,
            $linkMeeting !== '' ? $linkMeeting : null,
            $passcodeHash,
            $passcodePlain,
            $agenda !== '' ? $agenda : null,
            $berulang,
            $polaBerulang,
            $kodeRapat,
        ]);

        $idRapatBaru = (int) $pdo->lastInsertId();

        // Masukkan pembuat rapat sebagai Moderator di tabel peserta_rapat
        $stmtPeserta = $pdo->prepare("INSERT INTO peserta_rapat (id_rapat, id_user, role) VALUES (?, ?, 'Moderator')");
        $stmtPeserta->execute([$idRapatBaru, $userLogin['id_user']]);

        $pdo->commit();
        setFlash('sukses', 'Jadwal rapat berhasil dibuat.');
        redirect('moderator/detail-rapat.php?id=' . $idRapatBaru);
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('error', 'Terjadi kesalahan saat menyimpan rapat.');
        setOld($oldInput);
        redirect('moderator/buat-rapat.php');
    }
}

// 2. EDIT RAPAT
if ($aksi === 'edit') {
    $idRapat = (int) ($_POST['id_rapat'] ?? 0);

    $stmtRapat = $pdo->prepare("SELECT * FROM rapat WHERE id_rapat = ? AND dibuat_oleh = ? AND status != 'dibatalkan' LIMIT 1");
    $stmtRapat->execute([$idRapat, $userLogin['id_user']]);
    $rapat = $stmtRapat->fetch();

    if (!$rapat) {
        redirect('dashboard.php');
    }

    $judul        = trim($_POST['judul'] ?? '');
    $tanggal      = trim($_POST['tanggal'] ?? '');
    $lokasi       = trim($_POST['lokasi'] ?? '');
    $waktuMulai   = trim($_POST['waktu_mulai'] ?? '');
    $waktuSelesai = trim($_POST['waktu_selesai'] ?? '');
    $linkMeeting  = trim($_POST['link_meeting'] ?? '');
    $passcode     = trim($_POST['passcode'] ?? '');
    $agenda       = trim($_POST['agenda'] ?? '');
    $berulang     = ($_POST['berulang'] ?? '0') === '1' ? 1 : 0;
    $polaBerulang = $berulang === 1 ? trim($_POST['pola_berulang'] ?? '') : null;

    if ($judul === '' || $tanggal === '' || $waktuMulai === '' || $waktuSelesai === '') {
        setFlash('error', 'Judul, tanggal, waktu mulai, dan waktu selesai wajib diisi.');
        redirect('moderator/edit-rapat.php?id=' . $idRapat);
    }

    if ($waktuSelesai <= $waktuMulai) {
        setFlash('error', 'Waktu selesai harus lebih akhir dari waktu mulai.');
        redirect('moderator/edit-rapat.php?id=' . $idRapat);
    }

    if ($berulang === 1 && !in_array($polaBerulang, ['harian', 'mingguan', 'bulanan'], true)) {
        setFlash('error', 'Pilih pola berulang (Harian, Mingguan, atau Bulanan).');
        redirect('moderator/edit-rapat.php?id=' . $idRapat);
    }

    $passcodePlain = $passcode !== '' ? $passcode : ($rapat['passcode'] ?? null);
    $passcodeHash  = $passcode !== ''
        ? password_hash($passcode, PASSWORD_DEFAULT)
        : $rapat['passcode_hash'];

    $stmtUpd = $pdo->prepare("
        UPDATE rapat SET
            judul = ?, tanggal = ?, waktu_mulai = ?, waktu_selesai = ?,
            lokasi = ?, link_meeting = ?, passcode_hash = ?, passcode = ?, agenda = ?,
            berulang = ?, pola_berulang = ?
        WHERE id_rapat = ?
    ");
    $stmtUpd->execute([
        $judul,
        $tanggal,
        $waktuMulai,
        $waktuSelesai,
        $lokasi !== '' ? $lokasi : null,
        $linkMeeting !== '' ? $linkMeeting : null,
        $passcodeHash,
        $passcodePlain,
        $agenda !== '' ? $agenda : null,
        $berulang,
        $polaBerulang,
        $idRapat,
    ]);

    setFlash('sukses', 'Perubahan rapat berhasil disimpan.');
    redirect('moderator/detail-rapat.php?id=' . $idRapat);
}

// 3. BATALKAN RAPAT
if ($aksi === 'batalkan') {
    $idRapat = (int) ($_POST['id_rapat'] ?? 0);
    $stmtBatal = $pdo->prepare("UPDATE rapat SET status = 'dibatalkan' WHERE id_rapat = ? AND dibuat_oleh = ?");
    $stmtBatal->execute([$idRapat, $userLogin['id_user']]);

    redirect('dashboard.php');
}

redirect('dashboard.php');