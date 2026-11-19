<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$userLogin = wajibLogin($pdo);
$idRapat   = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($idRapat > 0) {
    $stmt = $pdo->prepare("
        SELECT r.*, pr.role
        FROM peserta_rapat pr
        JOIN rapat r ON r.id_rapat = pr.id_rapat
        WHERE pr.id_user = ? AND pr.id_rapat = ? AND r.status != 'dibatalkan'
        LIMIT 1
    ");
    $stmt->execute([$userLogin['id_user'], $idRapat]);
} else {
    $stmt = $pdo->prepare("
        SELECT r.*, pr.role
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

// Jika ternyata pengguna adalah Notulis atau Moderator, arahkan ke halamannya masing-masing
if ($rapat['role'] === 'Notulis') {
    redirect('notulis/notulensi.php?id=' . $rapat['id_rapat']);
}
if ($rapat['role'] === 'Moderator') {
    redirect('moderator/notulensi.php?id=' . $rapat['id_rapat']);
}

$stmtNotulen = $pdo->prepare("SELECT * FROM notulen WHERE id_rapat = ? LIMIT 1");
$stmtNotulen->execute([$rapat['id_rapat']]);
$notulen = $stmtNotulen->fetch() ?: null;

$pageTitle  = 'Notulensi - Hubble';
$pageDesc   = 'Notulensi rapat untuk anggota di Hubble.';
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
    <a href="<?= base_url('peserta/detail-rapat.php?id=' . $rapat['id_rapat']) ?>" class="tab">Jadwal &amp; agenda</a>
    <a href="<?= base_url('peserta/presensi.php?id=' . $rapat['id_rapat']) ?>" class="tab">Presensi</a>
    <a href="<?= base_url('peserta/notulensi.php?id=' . $rapat['id_rapat']) ?>" class="tab active">Notulensi</a>
  </nav>

  <section class="nt-member-card">
    <h2>Notulen</h2>
    <?php if ($notulen && in_array($notulen['status'], ['finalized', 'published'], true)): ?>
      <textarea class="nt-member-text" readonly aria-label="Notulen"><?= e($notulen['isi_notulen']) ?></textarea>
    <?php else: ?>
      <p style="font-size: 14px; color: #666; margin-top: 8px;">
        Notulensi rapat belum dipublikasikan oleh moderator.
      </p>
    <?php endif; ?>
  </section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
