<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$userLogin = wajibLogin($pdo);

$suksesMsg = getFlash('sukses');
$errorMsg  = getFlash('error');

$pageTitle  = 'Pengaturan Akun - Hubble';
$pageDesc   = 'Pengaturan akun Hubble - ubah profil dan kata sandi.';
$activeMenu = 'akun';

include __DIR__ . '/../includes/header.php';
?>

  <h1 class="page-title">Pengaturan Akun</h1>

  <?php if ($suksesMsg): ?>
    <div class="alert-success"><?= e($suksesMsg) ?></div>
  <?php endif; ?>

  <?php if ($errorMsg): ?>
    <div class="alert-error"><?= e($errorMsg) ?></div>
  <?php endif; ?>

  <div class="settings-layout">
    <!-- Kolom kiri: form pengaturan -->
    <div class="settings-form">
      <!-- Bagian PROFIL -->
      <p class="section-label">PROFIL</p>

      <form id="form-profil" method="POST" action="<?= base_url('proses/akun.php') ?>">
        <input type="hidden" name="aksi" value="update_profil">

        <div class="form-group">
          <label for="nama-lengkap">Nama Lengkap</label>
          <input type="text" id="nama-lengkap" name="nama" value="<?= e(old('nama', $userLogin['nama'])) ?>" placeholder="Nama lengkap" required>
        </div>

        <div class="form-group">
          <label for="email-profil">Email</label>
          <input type="email" id="email-profil" name="email" value="<?= e(old('email', $userLogin['email'])) ?>" placeholder="nama@gmail.com" required>
        </div>

        <button type="submit" class="btn-sm-primary">Simpan Perubahan</button>
      </form>

      <!-- Bagian KATA SANDI -->
      <p class="section-label">KATA SANDI</p>

      <form id="form-sandi" method="POST" action="<?= base_url('proses/akun.php') ?>">
        <input type="hidden" name="aksi" value="update_sandi">

        <div class="form-group">
          <label for="sandi-lama">Kata sandi saat ini</label>
          <input type="password" id="sandi-lama" name="sandi_lama" placeholder="********" required>
        </div>

        <div class="form-group">
          <label for="sandi-baru">Kata sandi baru</label>
          <input type="password" id="sandi-baru" name="sandi_baru" placeholder="********" required>
        </div>

        <button type="submit" class="btn-sm-outline">Ubah kata sandi</button>
      </form>

      <!-- Keluar dari akun -->
      <div class="logout-section">
        <p>Keluar dari akun</p>
        <form method="POST" action="<?= base_url('auth/logout.php') ?>" style="background:none; border:none; padding:0; margin:0;">
          <button type="submit" class="btn-logout">Log out</button>
        </form>
      </div>
    </div>

    <!-- Kolom kanan: kartu profil -->
    <div class="profile-card">
      <div class="profile-card-top">
        <img src="<?= base_url('assets/img/avatar.svg') ?>" alt="Avatar">
        <div>
          <div class="profile-card-name"><?= e($userLogin['nama']) ?></div>
          <div class="profile-card-email"><?= e($userLogin['email']) ?></div>
        </div>
      </div>
      <div class="profile-card-divider"></div>
      <p class="profile-card-joined">
        Bergabung sejak: <?= formatTanggalIndo($userLogin['created_at'] ?? null, false, false, true) ?>
      </p>
    </div>
  </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
