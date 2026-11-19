<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$userLogin = wajibLogin($pdo);

$errorMsg      = getFlash('error');
$berulangAktif = old('berulang', '0') === '1';
$polaDipilih   = old('pola_berulang', '');

$pageTitle  = 'Membuat jadwal rapat - Hubble';
$pageDesc   = 'Membuat jadwal rapat baru di Hubble.';
$activeMenu = 'dashboard';

include __DIR__ . '/../includes/header.php';
?>

  <h1 class="page-title">Membuat jadwal rapat</h1>

  <?php if ($errorMsg): ?>
    <div class="alert-error"><?= e($errorMsg) ?></div>
  <?php endif; ?>

  <div class="meeting-layout">
    <form class="meeting-form" id="meeting-form" method="POST" action="<?= base_url('proses/rapat-moderator.php') ?>">
      <input type="hidden" name="aksi" value="buat">
      <input type="hidden" name="berulang" id="input-berulang" value="<?= $berulangAktif ? '1' : '0' ?>">
      <input type="hidden" name="pola_berulang" id="input-pola-berulang" value="<?= e($polaDipilih) ?>">

      <div class="mf-title-row">
        <label class="mf-label mf-title-label" for="judul">Judul Rapat</label>
        <div class="mf-repeat">
          <span class="mf-label">Berulang</span>
          <button type="button" class="switch <?= $berulangAktif ? 'on' : '' ?>" id="switch-berulang"
            aria-label="Berulang" aria-pressed="<?= $berulangAktif ? 'true' : 'false' ?>"></button>
        </div>
      </div>
      <div class="mf-field">
        <input class="mf-input" type="text" id="judul" name="judul" value="<?= e(old('judul')) ?>"
          placeholder="Rapat koordinasi mingguan" required>
      </div>

      <!-- Muncul tepat di bawah Judul Rapat (mobile/tablet) & di kanan (desktop) saat Berulang aktif -->
      <div class="pattern-col" id="pattern-col" <?= $berulangAktif ? '' : 'hidden' ?>>
        <span class="mf-label">Pola Berulang</span>
        <div class="pattern-buttons">
          <button type="button" class="btn-pattern <?= $polaDipilih === 'harian' ? 'selected' : '' ?>" data-value="harian">Harian</button>
          <button type="button" class="btn-pattern <?= $polaDipilih === 'mingguan' ? 'selected' : '' ?>" data-value="mingguan">Mingguan</button>
          <button type="button" class="btn-pattern <?= $polaDipilih === 'bulanan' ? 'selected' : '' ?>" data-value="bulanan">Bulanan</button>
        </div>
      </div>

      <div class="mf-row mf-field">
        <div>
          <label class="mf-label" for="tanggal">Tanggal</label>
          <input class="mf-input" type="date" id="tanggal" name="tanggal" value="<?= e(old('tanggal')) ?>" required>
        </div>
        <div>
          <label class="mf-label" for="lokasi">Lokasi</label>
          <input class="mf-input" type="text" id="lokasi" name="lokasi" value="<?= e(old('lokasi')) ?>" placeholder="Room 12">
        </div>
      </div>

      <div class="mf-row mf-field">
        <div>
          <label class="mf-label" for="waktu-mulai">Waktu mulai</label>
          <input class="mf-input" type="time" id="waktu-mulai" name="waktu_mulai" value="<?= e(old('waktu_mulai')) ?>" required>
        </div>
        <div>
          <label class="mf-label" for="waktu-selesai">Waktu selesai</label>
          <input class="mf-input" type="time" id="waktu-selesai" name="waktu_selesai" value="<?= e(old('waktu_selesai')) ?>" required>
        </div>
      </div>

      <div class="mf-field">
        <label class="mf-label" for="link">Link video conference</label>
        <input class="mf-input" type="text" id="link" name="link_meeting" value="<?= e(old('link_meeting')) ?>"
          placeholder="https://meet.google.com/xxx-yyyy-zzz">
      </div>

      <div class="mf-field">
        <label class="mf-label" for="passcode">Passcode</label>
        <input class="mf-input" type="text" id="passcode" name="passcode" placeholder="Masukkan passcode akses">
      </div>

      <div class="mf-field">
        <label class="mf-label" for="agenda">Agenda</label>
        <textarea class="mf-textarea" id="agenda" name="agenda"
          placeholder="1. Pembukaan&#10;2. Pembahasan progres&#10;3. Penutup"><?= e(old('agenda')) ?></textarea>
      </div>

      <div class="mf-actions">
        <button type="submit" class="btn-form btn-form-primary" id="btn-simpan">Simpan rapat</button>
        <button type="button" class="btn-form btn-form-outline" id="btn-batal"
          data-href="<?= base_url('dashboard.php') ?>">Batal</button>
      </div>
    </form>
  </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
