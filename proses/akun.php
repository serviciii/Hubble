<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$userLogin = wajibLogin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('akun/pengaturan.php');
}

$aksi = $_POST['aksi'] ?? '';

if ($aksi === 'update_profil') {
    $nama  = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($nama === '' || $email === '') {
        setFlash('error', 'Nama lengkap dan email wajib diisi.');
        setOld(['nama' => $nama, 'email' => $email]);
        redirect('akun/pengaturan.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Format email tidak valid.');
        setOld(['nama' => $nama, 'email' => $email]);
        redirect('akun/pengaturan.php');
    }

    $stmtCek = $pdo->prepare('SELECT id_user FROM users WHERE email = ? AND id_user != ? LIMIT 1');
    $stmtCek->execute([$email, $userLogin['id_user']]);
    if ($stmtCek->fetch()) {
        setFlash('error', 'Email sudah dipakai akun lain.');
        setOld(['nama' => $nama, 'email' => $email]);
        redirect('akun/pengaturan.php');
    }

    $stmtUpd = $pdo->prepare('UPDATE users SET nama = ?, email = ? WHERE id_user = ?');
    $stmtUpd->execute([$nama, $email, $userLogin['id_user']]);

    setFlash('sukses', 'Profil berhasil diperbarui.');
    redirect('akun/pengaturan.php');
}

if ($aksi === 'update_sandi') {
    $sandiLama = $_POST['sandi_lama'] ?? '';
    $sandiBaru = $_POST['sandi_baru'] ?? '';

    if ($sandiLama === '' || $sandiBaru === '') {
        setFlash('error', 'Kata sandi saat ini dan kata sandi baru wajib diisi.');
        redirect('akun/pengaturan.php');
    }

    if (strlen($sandiBaru) < 6) {
        setFlash('error', 'Kata sandi baru minimal 6 karakter.');
        redirect('akun/pengaturan.php');
    }

    if (empty($userLogin['password_hash']) || !password_verify($sandiLama, $userLogin['password_hash'])) {
        setFlash('error', 'Kata sandi saat ini salah.');
        redirect('akun/pengaturan.php');
    }

    $hashBaru = password_hash($sandiBaru, PASSWORD_DEFAULT);
    $stmtUpd  = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id_user = ?');
    $stmtUpd->execute([$hashBaru, $userLogin['id_user']]);

    setFlash('sukses', 'Kata sandi berhasil diubah.');
    redirect('akun/pengaturan.php');
}

redirect('akun/pengaturan.php');
