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
    setFlash('info', 'Belum ada rapat yang kamu buat. Silakan buat rapat baru.');
    redirect('dashboard.php');
}

// Ambil daftar peserta rapat beserta peran
$stmtPeserta = $pdo->prepare("
    SELECT pr.*, u.nama, u.email
    FROM peserta_rapat pr
    JOIN users u ON u.id_user = pr.id_user
    WHERE pr.id_rapat = ?
");
$stmtPeserta->execute([$rapat['id_rapat']]);
$pesertaList = $stmtPeserta->fetchAll();

$moderatorNama = $userLogin['nama'];
$notulisNama   = 'Belum ditunjuk';
foreach ($pesertaList as $p) {
    if ($p['role'] === 'Moderator') $moderatorNama = $p['nama'];
    if ($p['role'] === 'Notulis') $notulisNama = $p['nama'];
}

$suksesMsg  = getFlash('sukses');
$pageTitle  = $rapat['judul'] . ' - Hubble';
$pageDesc   = 'Detail rapat di Hubble: info, agenda, presensi, dan notulensi.';
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
    <a href="<?= base_url('moderator/detail-rapat.php?id=' . $rapat['id_rapat']) ?>" class="tab active">Info &amp; agenda</a>
    <a href="<?= base_url('moderator/presensi.php?id=' . $rapat['id_rapat']) ?>" class="tab">Presensi</a>
    <a href="<?= base_url('moderator/notulensi.php?id=' . $rapat['id_rapat']) ?>" class="tab">Notulensi</a>
    <a href="<?= base_url('moderator/rekap-kehadiran.php?id=' . $rapat['id_rapat']) ?>" class="tab">Rekap Kehadiran</a>
  </nav>

  <?php if ($suksesMsg): ?>
    <div class="alert-success alert-full"><?= e($suksesMsg) ?></div>
  <?php endif; ?>

  <div class="detail-body">
    <section>
      <h2 class="detail-section-title">Agenda</h2>
      <div class="agenda-list">
        <?php if (!empty($rapat['agenda'])): ?>
          <?= nl2br(e($rapat['agenda'])) ?>
        <?php else: ?>
          Belum ada catatan agenda untuk rapat ini.
        <?php endif; ?>
      </div>
    </section>

    <aside class="detail-card">
      <h2 class="detail-section-title">Detail</h2>

      <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 6px;">
        <span>Kode Rapat: <strong id="teks-kode-rapat"><?= e($rapat['kode_rapat']) ?></strong></span>
        <button type="button" class="btn-copy-detail" data-copy-target="teks-kode-rapat"
          style="padding: 3px 10px; font-size: 12px; font-weight: 500; border: 1px solid #333; border-radius: 6px; background: #fff; cursor: pointer;">
          Salin
        </button>
      </div>

      <div style="margin-bottom: 10px;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 4px;">
          <span>Link Join Rapat:</span>
          <button type="button" class="btn-copy-detail" data-copy-target="teks-link-rapat"
            style="padding: 3px 10px; font-size: 12px; font-weight: 500; border: 1px solid #333; border-radius: 6px; background: #fff; cursor: pointer;">
            Salin Link
          </button>
        </div>
        <div id="teks-link-rapat"
          style="font-family: 'Roboto Mono', monospace; font-size: 11.5px; background: #f4f6fa; padding: 6px 8px; border-radius: 6px; word-break: break-all; color: #333;">
          <?= e(base_url('proses/join-rapat.php?kode=' . $rapat['kode_rapat'])) ?>
        </div>
      </div>

      Lokasi: <?= e($rapat['lokasi'] ?: '-') ?><br>
      Moderator: <?= e($moderatorNama) ?><br>
      Notulis: <?= e($notulisNama) ?><br>
      Peserta: <?= count($pesertaList) ?>
    </aside>
  </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
