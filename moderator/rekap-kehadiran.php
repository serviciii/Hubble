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

$stmtPeserta = $pdo->prepare("
    SELECT pr.id_peserta, u.nama, u.email,
           p.id_presensi, p.status AS status_presensi, p.alasan_izin, p.attended_at, p.status_ketepatan
    FROM peserta_rapat pr
    JOIN users u ON u.id_user = pr.id_user
    LEFT JOIN presensi p ON p.id_peserta = pr.id_peserta
    WHERE pr.id_rapat = ?
    ORDER BY pr.id_peserta ASC
");
$stmtPeserta->execute([$rapat['id_rapat']]);
$pesertaList = $stmtPeserta->fetchAll();

$adaDataPresensi = false;
foreach ($pesertaList as $p) {
    if (!empty($p['id_presensi'])) {
        $adaDataPresensi = true;
        break;
    }
}

$pageTitle  = 'Rekap Kehadiran - Hubble';
$pageDesc   = 'Rekap kehadiran peserta rapat di Hubble.';
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
    <a href="<?= base_url('moderator/notulensi.php?id=' . $rapat['id_rapat']) ?>" class="tab">Notulensi</a>
    <a href="<?= base_url('moderator/rekap-kehadiran.php?id=' . $rapat['id_rapat']) ?>" class="tab active">Rekap Kehadiran</a>
  </nav>

  <section class="recap-card">
    <h2 class="recap-title">Rekap Kehadiran</h2>

    <?php if (!$adaDataPresensi): ?>
      <!-- Keadaan 1: Kosong -->
      <div class="recap-empty" id="state-recap-empty">
        <h3>Belum ada Data Presensi</h3>
        <p>Data presensi belum tersedia</p>
      </div>
    <?php else: ?>
      <!-- Keadaan 2: Berisi -->
      <div class="table-wrap" id="state-recap-filled">
        <table class="att-table">
          <thead>
            <tr>
              <th>Nama</th>
              <th>Email</th>
              <th>Status</th>
              <th class="col-center">Waktu presensi</th>
              <th class="col-center">Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pesertaList as $peserta): ?>
              <tr>
                <td><?= e($peserta['nama']) ?></td>
                <td class="email"><?= e($peserta['email']) ?></td>
                <td>
                  <?php if ($peserta['status_presensi'] === 'hadir'): ?>
                    <span class="st-hadir">Hadir</span>
                  <?php elseif ($peserta['status_presensi'] === 'izin'): ?>
                    <span class="st-izin">Izin</span>
                  <?php else: ?>
                    <span style="color: #888; font-size: 11px;">Belum diisi</span>
                  <?php endif; ?>
                </td>
                <td class="col-center col-time">
                  <?= ($peserta['status_presensi'] === 'hadir' && !empty($peserta['attended_at']))
                      ? date('H:i', strtotime($peserta['attended_at']))
                      : '–' ?>
                </td>
                <td class="col-center col-note">
                  <?php if ($peserta['status_presensi'] === 'izin'): ?>
                    <?= e($peserta['alasan_izin'] ?: '-') ?>
                  <?php elseif ($peserta['status_presensi'] === 'hadir'): ?>
                    <?= $peserta['status_ketepatan'] === 'terlambat' ? 'Terlambat' : 'Tepat waktu' ?>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
