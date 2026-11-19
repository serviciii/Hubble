<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$userLogin = wajibLogin($pdo);
$idRapat   = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($idRapat > 0) {
    $stmt = $pdo->prepare("
        SELECT r.*, pr.id_peserta, pr.role
        FROM peserta_rapat pr
        JOIN rapat r ON r.id_rapat = pr.id_rapat
        WHERE pr.id_user = ? AND pr.id_rapat = ? AND r.status != 'dibatalkan'
        LIMIT 1
    ");
    $stmt->execute([$userLogin['id_user'], $idRapat]);
} else {
    $stmt = $pdo->prepare("
        SELECT r.*, pr.id_peserta, pr.role
        FROM peserta_rapat pr
        JOIN rapat r ON r.id_rapat = pr.id_rapat
        WHERE pr.id_user = ? AND pr.role != 'Moderator' AND r.status != 'dibatalkan'
        ORDER BY pr.id_peserta DESC
        LIMIT 1
    ");
    $stmt->execute([$userLogin['id_user']]);
}
$rapat = $stmt->fetch();

if (!$rapat) {
    redirect('dashboard.php');
}

// Ambil catatan presensi milik peserta ini
$stmtPres = $pdo->prepare("SELECT * FROM presensi WHERE id_peserta = ? LIMIT 1");
$stmtPres->execute([$rapat['id_peserta']]);
$presensiSaya = $stmtPres->fetch() ?: null;

$linkNotulensi = $rapat['role'] === 'Notulis'
    ? base_url('notulis/notulensi.php?id=' . $rapat['id_rapat'])
    : base_url('peserta/notulensi.php?id=' . $rapat['id_rapat']);

$errorMsg   = getFlash('error');
$pageTitle  = 'Presensi - Hubble';
$pageDesc   = 'Presensi rapat untuk anggota di Hubble: tandai kehadiran atau ajukan izin.';
$activeMenu = 'rapat';

include __DIR__ . '/../includes/header.php';
?>

  <div class="detail-header">
    <div>
      <h1><?= e($rapat['judul']) ?></h1>
      <p>
        <?= formatTanggalIndo($rapat['tanggal']) ?>&nbsp;
        <?= substr($rapat['waktu_mulai'], 0, 5) ?> - <?= substr($rapat['waktu_selesai'], 0, 5) ?>
      </p>
    </div>
    <div class="detail-buttons">
      <a href="<?= e($rapat['link_meeting'] ?: 'https://meet.google.com/') ?>" target="_blank" rel="noopener" class="btn-detail">Buka link meeting</a>
    </div>
  </div>

  <!-- Bar tab anggota (3 tab) -->
  <nav class="tabs">
    <a href="<?= base_url('peserta/detail-rapat.php?id=' . $rapat['id_rapat']) ?>" class="tab">Jadwal &amp; Agenda</a>
    <a href="<?= base_url('peserta/presensi.php?id=' . $rapat['id_rapat']) ?>" class="tab active">Presensi</a>
    <a href="<?= $linkNotulensi ?>" class="tab">Notulensi</a>
  </nav>

  <?php if ($errorMsg): ?>
    <div class="alert-error alert-full"><?= e($errorMsg) ?></div>
  <?php endif; ?>

  <?php if ((int) $rapat['presensi_dibuka'] === 0 && $rapat['status'] === 'terjadwal'): ?>
    <!-- Tampilan A: presensi belum dibuka -->
    <section class="pa-lock" data-view="belum">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path fill="#000" d="M12 1.5a5 5 0 0 0-5 5V9H6a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3v-8a3 3 0 0 0-3-3h-1V6.5a5 5 0 0 0-5-5zm-3 5a3 3 0 0 1 6 0V9H9V6.5zm3 7.5a1.5 1.5 0 0 1 1 2.6V19h-2v-2.4A1.5 1.5 0 0 1 12 14z"/>
      </svg>
      <h2>Presensi belum dibuka</h2>
    </section>

  <?php elseif ($presensiSaya): ?>
    <!-- Tampilan C: presensi berhasil dicatat -->
    <section class="pa-success" data-view="berhasil">
      <svg class="pa-success-icon" viewBox="0 0 146 146" aria-hidden="true">
        <circle cx="73" cy="73" r="68" fill="none" stroke="#2FB162" stroke-width="10" />
        <path d="M40 76 L64 100 L108 52" fill="none" stroke="#2FB162" stroke-width="12" stroke-linecap="butt" stroke-linejoin="miter" />
      </svg>
      <h2>Presensi berhasil dicatat</h2>
      <p>Status kamu:</p>
      <?php if ($presensiSaya['status'] === 'hadir'): ?>
        <span class="pa-status-pill">
          <span class="pa-status-dot"></span>
          <?= $presensiSaya['status_ketepatan'] === 'terlambat' ? 'Hadir terlambat' : 'Hadir tepat waktu' ?>
        </span>
      <?php else: ?>
        <span class="pa-status-pill" style="color: #C06A00;">
          <span class="pa-status-dot"></span>
          Izin (<?= e($presensiSaya['alasan_izin']) ?>)
        </span>
      <?php endif; ?>
    </section>

  <?php elseif ((int) $rapat['presensi_dibuka'] === 1): ?>
    <!-- Tampilan B: presensi dibuka -->
    <section class="pa-open" data-view="dibuka">
      <div class="pa-banner">
        <div class="pa-banner-title">
          <svg viewBox="0 0 38 38" aria-hidden="true">
            <circle cx="19" cy="19" r="17" fill="none" stroke="#3358FF" stroke-width="2"/>
            <path d="M19 9 V19 L25.5 24" fill="none" stroke="#3358FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Presensi dibuka</span>
        </div>
        <p>Kamu bisa isi hingga hari ini</p>
      </div>

      <h2 class="pa-heading">Tandai kehadiran kamu</h2>
      <p class="pa-subheading">Pilih salah satu untuk mengonfirmasi</p>

      <div class="pa-choices">
        <form method="POST" action="<?= base_url('proses/presensi.php') ?>" style="display: contents;">
          <input type="hidden" name="aksi" value="simpan_peserta">
          <input type="hidden" name="id_rapat" value="<?= $rapat['id_rapat'] ?>">
          <input type="hidden" name="status" value="hadir">
          <button type="submit" class="pa-choice pa-choice-hadir" id="btn-hadir">
            <span class="pa-choice-row">
              <svg viewBox="0 0 34 34" aria-hidden="true">
                <path d="M3 17.5 L12.5 27 L31 7" fill="none" stroke="#1F8A34" stroke-width="1.6"/>
              </svg>
              Hadir
            </span>
            <span class="pa-choice-sub">Saya mengikuti rapat ini</span>
          </button>
        </form>

        <button type="button" class="pa-choice pa-choice-izin" id="btn-izin">
          <span class="pa-choice-row">
            <svg viewBox="0 0 40 40" aria-hidden="true">
              <circle cx="20" cy="20" r="18" fill="none" stroke="#A01C1C" stroke-width="2.4"/>
              <circle cx="20" cy="11.5" r="2.2" fill="#A01C1C"/>
              <path d="M20 17 V30" stroke="#A01C1C" stroke-width="3.2" stroke-linecap="round"/>
            </svg>
            Izin
          </span>
          <span class="pa-choice-sub">Saya tidak bisa hadir</span>
        </button>
      </div>
    </section>

    <!-- Tampilan D: form izin -->
    <section class="pa-izin" data-view="izin" hidden>
      <button type="button" class="pa-back" id="btn-kembali">&larr; Kembali</button>

      <div class="pa-picked">
        <p>Kamu memilih:</p>
        <span class="pa-badge">Izin</span>
      </div>

      <form method="POST" action="<?= base_url('proses/presensi.php') ?>">
        <input type="hidden" name="aksi" value="simpan_peserta">
        <input type="hidden" name="id_rapat" value="<?= $rapat['id_rapat'] ?>">
        <input type="hidden" name="status" value="izin">
        <label class="pa-label" for="alasan-izin">Alasan izin</label>
        <textarea class="pa-textarea" id="alasan-izin" name="alasan_izin" placeholder="Contoh: ada kegiatan lain yang bersamaan, sakit, dsb." required></textarea>

        <button type="submit" class="pa-submit" id="btn-kirim">Kirim Presensi</button>
      </form>
    </section>

  <?php else: ?>
    <!-- Tampilan E: presensi sudah ditutup & belum mengisi -->
    <section class="pa-closed" data-view="ditutup">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path fill="#000" d="M12 1.5a5 5 0 0 0-5 5V9H6a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3v-8a3 3 0 0 0-3-3h-1V6.5a5 5 0 0 0-5-5zm-3 5a3 3 0 0 1 6 0V9H9V6.5zm3 7.5a1.5 1.5 0 0 1 1 2.6V19h-2v-2.4A1.5 1.5 0 0 1 12 14z"/>
      </svg>
      <h2>Presensi sudah ditutup</h2>
      <p>Kamu tidak melakukan presensi untuk rapat ini.<br>Hubungi moderator jika kamu sebenarnya hadir.</p>
    </section>
  <?php endif; ?>

<?php
$extraScripts = ['assets/js/presensi-anggota.js'];
include __DIR__ . '/../includes/footer.php';
?>
