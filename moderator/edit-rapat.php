<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$userLogin = wajibLogin($pdo);
$idRapat   = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM rapat WHERE id_rapat = ? AND dibuat_oleh = ? AND status != 'dibatalkan' LIMIT 1");
$stmt->execute([$idRapat, $userLogin['id_user']]);
$rapat = $stmt->fetch();

if (!$rapat) {
    redirect('dashboard.php');
}

$errorMsg      = getFlash('error');
$berulangAktif = (int) old('berulang', (string) $rapat['berulang']) === 1;
$polaDipilih   = old('pola_berulang', $rapat['pola_berulang'] ?? '');

$pageTitle  = 'Mengedit rapat - Hubble';
$pageDesc   = 'Mengedit dan membatalkan rapat di Hubble.';
$activeMenu = 'rapat';

include __DIR__ . '/../includes/header.php';
?>

  <h1 class="page-title">Mengedit rapat dan membatalkan rapat</h1>

  <?php if ($errorMsg): ?>
    <div class="alert-error"><?= e($errorMsg) ?></div>
  <?php endif; ?>

  <div class="meeting-layout">
    <form class="meeting-form" id="meeting-form" method="POST" action="<?= base_url('proses/rapat-moderator.php') ?>">
      <input type="hidden" name="aksi" value="edit">
      <input type="hidden" name="id_rapat" value="<?= $rapat['id_rapat'] ?>">
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
        <input class="mf-input" type="text" id="judul" name="judul" value="<?= e(old('judul', $rapat['judul'])) ?>"
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
          <input class="mf-input" type="date" id="tanggal" name="tanggal" value="<?= e(old('tanggal', $rapat['tanggal'])) ?>" required>
        </div>
        <div>
          <label class="mf-label" for="lokasi">Lokasi</label>
          <input class="mf-input" type="text" id="lokasi" name="lokasi" value="<?= e(old('lokasi', $rapat['lokasi'] ?? '')) ?>" placeholder="Room 12">
        </div>
      </div>

      <div class="mf-row mf-field">
        <div>
          <label class="mf-label" for="waktu-mulai">Waktu mulai</label>
          <input class="mf-input" type="time" id="waktu-mulai" name="waktu_mulai"
            value="<?= e(old('waktu_mulai', substr($rapat['waktu_mulai'], 0, 5))) ?>" required>
        </div>
        <div>
          <label class="mf-label" for="waktu-selesai">Waktu selesai</label>
          <input class="mf-input" type="time" id="waktu-selesai" name="waktu_selesai"
            value="<?= e(old('waktu_selesai', substr($rapat['waktu_selesai'], 0, 5))) ?>" required>
        </div>
      </div>

      <div class="mf-field">
        <label class="mf-label" for="link">Link video conference</label>
        <input class="mf-input" type="text" id="link" name="link_meeting" value="<?= e(old('link_meeting', $rapat['link_meeting'] ?? '')) ?>"
          placeholder="https://meet.google.com/xxx-yyyy-zzz">
      </div>

      <div class="mf-field">
        <label class="mf-label" for="passcode">Passcode (Kosongkan jika tidak diubah)</label>
        <input class="mf-input" type="text" id="passcode" name="passcode" placeholder="Masukkan passcode baru">
      </div>

      <div class="mf-field">
        <label class="mf-label" for="agenda">Agenda</label>
        <textarea class="mf-textarea" id="agenda" name="agenda"
          placeholder="1. Pembukaan&#10;2. Pembahasan progres&#10;3. Penutup"><?= e(old('agenda', $rapat['agenda'] ?? '')) ?></textarea>
      </div>

      <div class="mf-actions">
        <button type="submit" class="btn-form btn-form-primary" id="btn-simpan">Simpan perubahan</button>
        <button type="button" class="btn-form btn-form-outline dark" id="btn-batal"
          data-href="<?= base_url('moderator/detail-rapat.php?id=' . $rapat['id_rapat']) ?>">Batalkan perubahan</button>
        <button type="submit" form="form-batalkan-rapat" class="btn-form btn-form-danger" id="btn-batalkan-rapat"
          onclick="return confirm('Yakin ingin membatalkan rapat ini?')">Batalkan rapat</button>
      </div>
    </form>

    <form id="form-batalkan-rapat" method="POST" action="<?= base_url('proses/rapat-moderator.php') ?>" hidden>
      <input type="hidden" name="aksi" value="batalkan">
      <input type="hidden" name="id_rapat" value="<?= $rapat['id_rapat'] ?>">
    </form>
  </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
