<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('auth/masuk.php');
}

$aksi = $_POST['aksi'] ?? '';

if ($aksi === 'masuk') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        setFlash('error', 'Email dan password wajib diisi.');
        setOld(['email' => $email]);
        redirect('auth/masuk.php');
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && !empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id_user'];

        if (!empty($_SESSION['intended_url'])) {
            $tujuan = $_SESSION['intended_url'];
            unset($_SESSION['intended_url']);
            header('Location: ' . $tujuan);
            exit;
        }

        redirect('dashboard.php');
    }

    setFlash('error', 'Email atau password tidak sesuai.');
    setOld(['email' => $email]);
    redirect('auth/masuk.php');
}

if ($aksi === 'daftar') {
    $nama       = trim($_POST['nama'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $konfirmasi = $_POST['password_confirmation'] ?? '';

    if ($nama === '' || $email === '' || $password === '') {
        setFlash('error', 'Semua kolom wajib diisi.');
        setOld(['nama' => $nama, 'email' => $email]);
        redirect('auth/daftar.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Format email tidak valid.');
        setOld(['nama' => $nama, 'email' => $email]);
        redirect('auth/daftar.php');
    }

    if (strlen($password) < 6) {
        setFlash('error', 'Password minimal 6 karakter.');
        setOld(['nama' => $nama, 'email' => $email]);
        redirect('auth/daftar.php');
    }

    if ($password !== $konfirmasi) {
        setFlash('error', 'Konfirmasi password tidak cocok.');
        setOld(['nama' => $nama, 'email' => $email]);
        redirect('auth/daftar.php');
    }

    $stmtCek = $pdo->prepare('SELECT id_user FROM users WHERE email = ? LIMIT 1');
    $stmtCek->execute([$email]);
    if ($stmtCek->fetch()) {
        setFlash('error', 'Email ini sudah terdaftar.');
        setOld(['nama' => $nama, 'email' => $email]);
        redirect('auth/daftar.php');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmtIns = $pdo->prepare('INSERT INTO users (nama, email, password_hash) VALUES (?, ?, ?)');
    $stmtIns->execute([$nama, $email, $hash]);

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $pdo->lastInsertId();

    redirect('dashboard.php');
}

redirect('auth/masuk.php');
