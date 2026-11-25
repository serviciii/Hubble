<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

wajibTamu();

$errorMsg  = getFlash('error');
$pageTitle = 'Daftar - Hubble';
$pageDesc  = 'Buat akun Hubble baru untuk mulai mengelola rapat.';
$isGuest   = true;

include __DIR__ . '/../includes/header.php';
?>

  <main class="auth-page">
    <div class="auth-card">
      <div class="logo">
        <div class="logo-circle"></div>
        <span class="logo-text">Hubble</span>
      </div>

      <?php if ($errorMsg): ?>
        <div class="alert-error"><?= e($errorMsg) ?></div>
      <?php endif; ?>

      <form id="form-daftar" method="POST" action="<?= base_url('proses/auth.php') ?>">
        <input type="hidden" name="aksi" value="daftar">

        <div class="form-group">
          <label for="nama">Nama Lengkap</label>
          <input type="text" id="nama" name="nama" value="<?= e(old('nama')) ?>" placeholder="Nama Anda" required>
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" placeholder="nama@email.com" required>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>

        <div class="form-group">
          <label for="konfirmasi-password">Konfirmasi Password</label>
          <input type="password" id="konfirmasi-password" name="password_confirmation" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-primary">Buat Akun</button>

        <a href="<?= base_url('dashboard.php') ?>" class="btn-google">
        <a href="<?= base_url('proses/google-login.php') ?>" class="btn-outline-gray">Masuk dengan google</a>
      </form>

      <p class="auth-footer">Sudah punya akun? <a href="<?= base_url('auth/masuk.php') ?>">Masuk</a></p>
    </div>
  </main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
