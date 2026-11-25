<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/google.php';

// 1. Validasi state & kode dari Google
if (empty($_GET['code']) || empty($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
    unset($_SESSION['oauth_state']);
    setFlash('error', 'Autentikasi Google dibatalkan atau tidak valid.');
    redirect('auth/masuk.php');
}
unset($_SESSION['oauth_state']);

// 2. Tukar authorization code dengan Access Token
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'code'          => $_GET['code'],
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code',
    ]),
]);
$tokenResponse = json_decode(curl_exec($ch), true);
curl_close($ch);

if (empty($tokenResponse['access_token'])) {
    setFlash('error', 'Gagal mendapatkan token akses dari Google.');
    redirect('auth/masuk.php');
}

// 3. Ambil data profil user (Nama, Email, Google ID)
$chUser = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
curl_setopt_array($chUser, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $tokenResponse['access_token']],
]);
$googleUser = json_decode(curl_exec($chUser), true);
curl_close($chUser);

if (empty($googleUser['email'])) {
    setFlash('error', 'Gagal membaca data email dari akun Google.');
    redirect('auth/masuk.php');
}

$email    = $googleUser['email'];
$nama     = $googleUser['name'] ?? explode('@', $email)[0];
$googleId = $googleUser['id'];

// 4 Cek berdasarkan google_id dulu, atau email jika baru pertama kali menautkan Google
$stmt = $pdo->prepare('SELECT * FROM users WHERE google_id = ? OR email = ? LIMIT 1');
$stmt->execute([$googleId, $email]);
$user = $stmt->fetch();

if ($user) {
    if (empty($user['google_id'])) {
        $upd = $pdo->prepare('UPDATE users SET google_id = ? WHERE id_user = ?');
        $upd->execute([$googleId, $user['id_user']]);
    }
    $userId = $user['id_user'];
} else {
    $ins = $pdo->prepare('INSERT INTO users (nama, email, google_id) VALUES (?, ?, ?)');
    $ins->execute([$nama, $email, $googleId]);
    $userId = $pdo->lastInsertId();
}

if ($user) {
    // Jika akun sudah ada tapi belum tertaut google_id, tautkan sekarang
    if (empty($user['google_id'])) {
        $upd = $pdo->prepare('UPDATE users SET google_id = ? WHERE id_user = ?');
        $upd->execute([$googleId, $user['id_user']]);
    }
    $userId = $user['id_user'];
} else {
    // Jika belum punya akun sama sekali, otomatis daftarkan (Auto-Register)
    $ins = $pdo->prepare('INSERT INTO users (nama, email, google_id) VALUES (?, ?, ?)');
    $ins->execute([$nama, $email, $googleId]);
    $userId = $pdo->lastInsertId();
}

// 5. Simpan session login dan arahkan ke dashboard
$_SESSION['user_id'] = $userId;
redirect('dashboard.php');