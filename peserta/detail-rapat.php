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

// Ambil info moderator, notulis, dan total peserta
$stmtPeserta = $pdo->prepare("
    SELECT pr.role, u.nama
    FROM peserta_rapat pr
    JOIN users u ON u.id_user = pr.id_user
    WHERE pr.id_rapat = ?
");
$stmtPeserta->execute([$rapat['id_rapat']]);
$pesertaList = $stmtPeserta->fetchAll();

$moderatorNama = '-';
$notulisNama   = 'Belum ditunjuk';
foreach ($pesertaList as $p) {
    if ($p['role'] === 'Moderator') $moderatorNama = $p['nama'];
    if ($p['role'] === 'Notulis') $notulisNama = $p['nama'];
}

$linkNotulensi = $rapat['role'] === 'Notulis'
    ? base_url('notulis/notulensi.php?id=' . $rapat['id_rapat'])
    : base_url('peserta/notulensi.php?id=' . $rapat['id_rapat']);

$pageTitle  = $rapat['judul'] . ' - Hubble';
$pageDesc   = 'Detail rapat untuk anggota di Hubble: jadwal dan agenda.';
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
    <a href="<?= base_url('peserta/detail-rapat.php?id=' . $rapat['id_rapat']) ?>" class="tab active">Jadwal &amp; Agenda</a>
    <a href="<?= base_url('peserta/presensi.php?id=' . $rapat['id_rapat']) ?>" class="tab">Presensi</a>
    <a href="<?= $linkNotulensi ?>" class="tab">Notulensi</a>
  </nav>

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
      Kode Rapat: <strong><?= e($rapat['kode_rapat']) ?></strong><br>
      Lokasi: <?= e($rapat['lokasi'] ?: '-') ?><br>
      Moderator: <?= e($moderatorNama) ?><br>
      Notulis: <?= e($notulisNama) ?><br>
      Peserta: <?= count($pesertaList) ?>
    </aside>
  </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
