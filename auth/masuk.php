<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

wajibTamu();

$errorMsg  = getFlash('error');
$pageTitle = 'Masuk - Hubble';
$pageDesc  = 'Masuk ke akun Hubble untuk mengelola rapat Anda.';
$isGuest   = true;

include __DIR__ . '/../includes/header.php';
?>

  <main class="auth-page">
    <div class="auth-card">
      <div class="logo logo-spacer">
        <div class="logo-circle"></div>
        <span class="logo-text">Hubble</span>
      </div>

      <?php if ($errorMsg): ?>
        <div class="alert-error"><?= e($errorMsg) ?></div>
      <?php endif; ?>

      <form id="form-masuk" method="POST" action="<?= base_url('proses/auth.php') ?>">
        <input type="hidden" name="aksi" value="masuk">

        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" placeholder="nama@gmail.com" required>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-primary">Masuk</button>

        <a href="<?= base_url('proses/google-login.php') ?>" class="btn-outline-gray">Masuk dengan google</a>
      </form>

      <p class="auth-footer">Belum punya akun? <a href="<?= base_url('auth/daftar.php') ?>">Daftar</a></p>
    </div>
  </main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
