<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$userLogin = wajibLogin($pdo);
$idRapat   = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($idRapat > 0) {
    $stmt = $pdo->prepare("SELECT * FROM rapat WHERE id_rapat = ? AND dibuat_oleh = ? AND status != 'dibatalkan' LIMIT 1");
    $stmt->execute([$idRapat, $userLogin['id_user']]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM rapat WHERE dibuat_oleh = ? AND status != 'dibatalkan' ORDER BY id_rapat DESC LIMIT 1");
    $stmt->execute([$userLogin['id_user']]);
}
$rapat = $stmt->fetch();

if (!$rapat) {
    redirect('dashboard.php');
}

// Ambil peserta rapat
$stmtPeserta = $pdo->prepare("
    SELECT pr.id_peserta, pr.role, u.nama, u.email
    FROM peserta_rapat pr
    JOIN users u ON u.id_user = pr.id_user
    WHERE pr.id_rapat = ?
    ORDER BY pr.id_peserta ASC
");
$stmtPeserta->execute([$rapat['id_rapat']]);
$semuaPeserta = $stmtPeserta->fetchAll();

$notulis         = null;
$kandidatNotulis = [];
foreach ($semuaPeserta as $p) {
    if ($p['role'] === 'Notulis') {
        $notulis = $p;
    }
    if ($p['role'] !== 'Moderator') {
        $kandidatNotulis[] = $p;
    }
}

// Ambil data notulen
$stmtNotulen = $pdo->prepare("SELECT * FROM notulen WHERE id_rapat = ? LIMIT 1");
$stmtNotulen->execute([$rapat['id_rapat']]);
$notulen = $stmtNotulen->fetch() ?: null;

$suksesTunjukNama = getFlash('sukses_tunjuk_nama');
$errorSama        = getFlash('error_sama');

$pageTitle  = 'Notulensi - Hubble';
$pageDesc   = 'Notulensi rapat di Hubble: tunjuk notulis, lihat draft, dan publikasikan.';
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
      <a href="<?= base_url('moderator/edit-rapat.php?id=' . $rapat['id_rapat']) ?>" class="btn-detail">Edit Rapat</a>
    </div>
  </div>

  <nav class="tabs tabs-padded">
    <a href="<?= base_url('moderator/detail-rapat.php?id=' . $rapat['id_rapat']) ?>" class="tab">Info &amp; agenda</a>
    <a href="<?= base_url('moderator/presensi.php?id=' . $rapat['id_rapat']) ?>" class="tab">Presensi</a>
    <a href="<?= base_url('moderator/notulensi.php?id=' . $rapat['id_rapat']) ?>" class="tab active">Notulensi</a>
    <a href="<?= base_url('moderator/rekap-kehadiran.php?id=' . $rapat['id_rapat']) ?>" class="tab">Rekap Kehadiran</a>
  </nav>

  <?php if (!$notulis && !$notulen): ?>
    <!-- Keadaan 1: belum ada notulis -->
    <section class="nt-empty" id="state-empty">
      <div class="nt-icon"><span></span></div>
      <h2>Belum ada notulis untuk rapat ini</h2>
      <p>Tunjuk salah satu anggota rapat untuk mencatat<br>jalannya rapat.</p>
      <button type="button" class="nt-btn" id="btn-tunjuk"><span class="nt-btn-icon"></span>Tunjuk notulis</button>
    </section>

  <?php elseif ($notulen && in_array($notulen['status'], ['finalized', 'published'], true)): ?>
    <!-- Keadaan 3: notulen terpublikasi -->
    <section class="nt-published" id="state-published">
      <h2>Notulen</h2>
      <div class="nt-published-text"><?= nl2br(e($notulen['isi_notulen'] ?: 'Tidak ada catatan notulensi.')) ?></div>
    </section>

  <?php else: ?>
    <!-- Keadaan 2: notulis ditunjuk, draft / diajukan / revisi -->
    <section class="nt-draft" id="state-draft">
      <?php if ($notulen && $notulen['status'] === 'revision_requested'): ?>
        <div class="nt-banner nt-banner-yellow" id="banner-revisi" style="height: auto; min-height: 64px; border-radius: 12px; margin-bottom: 24px; padding: 12px 20px; display: flex;">
          <svg viewBox="0 0 24 24" aria-hidden="true" style="width: 28px; height: 28px; flex-shrink: 0;">
            <path fill="#F09B36" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
          </svg>
          <div>
            <div style="font-weight: 600; font-size: 15px; color: #000;">Meminta revisi</div>
            <?php if (!empty($notulen['review_note'])): ?>
              <div style="font-size: 13px; color: #333;">Catatan: <?= e($notulen['review_note']) ?></div>
            <?php endif; ?>
          </div>
        </div>
      <?php elseif ($notulen && $notulen['status'] === 'submitted'): ?>
        <div class="nt-banner nt-banner-yellow" style="height: 58px; border-radius: 12px; margin-bottom: 24px; padding: 0 20px; display: flex;">
          <span style="font-weight: 600; font-size: 14px; color: #000;">Notulis telah mengajukan draf untuk ditinjau.</span>
        </div>
      <?php endif; ?>

      <div class="nt-draft-top">
        <div class="nt-box nt-draft-box">
          <h2>Draft notulensi</h2>
          <?php if ($notulen && !empty($notulen['isi_notulen'])): ?>
            <p><?= nl2br(e($notulen['isi_notulen'])) ?></p>
          <?php else: ?>
            <p>Belum ada draft dari notulis.</p>
          <?php endif; ?>
        </div>
        <div class="nt-box nt-assigned">
          <h2>Notulis ditunjuk</h2>
          <div class="nt-assigned-user">
            <img src="<?= base_url('assets/img/avatar.svg') ?>" alt="Avatar notulis">
            <span id="assigned-name"><?= e($notulis['nama'] ?? '-') ?></span>
          </div>
          <button type="button" class="nt-ganti" id="btn-ganti">Ganti</button>
        </div>
      </div>

      <div class="nt-draft-actions">
        <form method="POST" action="<?= base_url('proses/notulensi.php') ?>">
          <input type="hidden" name="aksi" value="publikasi">
          <input type="hidden" name="id_rapat" value="<?= $rapat['id_rapat'] ?>">
          <button type="submit" class="btn-form btn-form-primary" id="btn-publikasi">Finalisasi</button>
        </form>
        <button type="button" class="btn-form btn-form-outline" id="btn-minta-revisi">Minta Revisi</button>
      </div>
    </section>
  <?php endif; ?>

  <!-- Modal pilih notulis -->
  <div class="dark-overlay" id="modal-pilih">
    <div class="dark-card dark-pick" role="dialog" aria-modal="true">
      <h2>Pilih notulis</h2>
      <p>Pilih satu anggota untuk ditunjuk sebagai notulis.</p>

      <?php if (empty($kandidatNotulis)): ?>
        <p style="font-size: 13px; color: #666; margin-top: 12px;">
          Belum ada peserta yang bergabung ke rapat ini. Bagikan kode atau link rapat terlebih dahulu.
        </p>
      <?php else: ?>
        <?php foreach ($kandidatNotulis as $kandidat): ?>
          <div class="pick-item">
            <span class="pick-avatar"></span>
            <div class="pick-info">
              <span><?= e($kandidat['nama']) ?></span>
              <small><?= e($kandidat['email']) ?></small>
            </div>
            <button type="button" class="pick-btn"
              data-peserta-id="<?= $kandidat['id_peserta'] ?>"
              data-name="<?= e($kandidat['nama']) ?>"
              data-current="<?= $kandidat['role'] === 'Notulis' ? '1' : '0' ?>">
              Pilih
            </button>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Modal konfirmasi tunjuk -->
  <div class="dark-overlay" id="modal-konfirmasi">
    <div class="dark-card dark-confirm" role="dialog" aria-modal="true">
      <h2>Tunjuk sebagai notulis?</h2>
      <p><strong id="confirm-name">-</strong> &nbsp;akan ditunjuk sebagai notulis.</p>
      <form method="POST" action="<?= base_url('proses/notulensi.php') ?>" class="confirm-actions">
        <input type="hidden" name="aksi" value="tunjuk">
        <input type="hidden" name="id_rapat" value="<?= $rapat['id_rapat'] ?>">
        <input type="hidden" name="id_peserta" id="input-tunjuk-peserta-id">
        <button type="button" class="confirm-cancel" id="btn-confirm-batal">Batal</button>
        <button type="submit" class="confirm-ok" id="btn-confirm-ya">Ya, tunjuk</button>
      </form>
    </div>
  </div>

  <!-- Modal sukses tunjuk -->
  <div class="dark-overlay <?= $suksesTunjukNama ? 'show' : '' ?>" id="modal-sukses-tunjuk">
    <div class="dark-card dark-success" role="dialog" aria-modal="true">
      <svg class="modal-check-filled" viewBox="0 0 146 146" aria-hidden="true">
        <circle cx="73" cy="73" r="68" fill="#2FB162" />
        <path d="M44 76 L64 96 L102 52" fill="none" stroke="#fff" stroke-width="12" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
      <h2>Notulis berhasil di tunjuk</h2>
      <p><strong id="success-name"><?= e($suksesTunjukNama ?: '-') ?></strong> sekarang menjadi notulis rapat ini.</p>
      <button type="button" class="btn-form btn-form-primary" id="btn-sukses-selesai">Selesai</button>
    </div>
  </div>

  <!-- Modal error sama -->
  <div class="dark-overlay <?= $errorSama ? 'show' : '' ?>" id="modal-error-sama">
    <div class="dark-card dark-confirm" style="display: flex; align-items: center; justify-content: center; text-align: center;" role="dialog" aria-modal="true">
      <h2 style="font-size: 22px; font-weight: 500; line-height: 1.5; margin-bottom: 30px;">Anggota yang dipilih sudah<br>menjadi notulis</h2>
      <div class="confirm-actions">
        <button type="button" class="confirm-ok" id="btn-error-batalkan">Batalkan</button>
      </div>
    </div>
  </div>

  <!-- Modal catatan minta revisi -->
  <div class="dark-overlay" id="modal-revisi">
    <div class="dark-card dark-confirm" role="dialog" aria-modal="true">
      <h2>Minta Revisi Notulensi</h2>
      <p>Tuliskan bagian yang perlu diperbaiki oleh notulis:</p>
      <form method="POST" action="<?= base_url('proses/notulensi.php') ?>">
        <input type="hidden" name="aksi" value="revisi">
        <input type="hidden" name="id_rapat" value="<?= $rapat['id_rapat'] ?>">
        <textarea name="review_note" rows="3" placeholder="Contoh: Tambahkan rincian kesimpulan dan tindak lanjut"
          style="width: 100%; padding: 10px 12px; border: 1.5px solid #333; border-radius: 8px; font-family: var(--font-family); font-size: 13px; margin-bottom: 16px;" required></textarea>
        <div class="confirm-actions">
          <button type="button" class="confirm-cancel" id="btn-batal-revisi">Batal</button>
          <button type="submit" class="confirm-ok">Kirim Revisi</button>
        </div>
      </form>
    </div>
  </div>

<?php
$extraScripts = ['assets/js/notulensi.js'];
include __DIR__ . '/../includes/footer.php';
?>
