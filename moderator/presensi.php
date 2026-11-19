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

// Ambil daftar peserta + status presensinya
$stmtPeserta = $pdo->prepare("
    SELECT pr.id_peserta, pr.role, u.nama, u.email,
           p.status AS status_presensi, p.attended_at
    FROM peserta_rapat pr
    JOIN users u ON u.id_user = pr.id_user
    LEFT JOIN presensi p ON p.id_peserta = pr.id_peserta
    WHERE pr.id_rapat = ?
    ORDER BY pr.id_peserta ASC
");
$stmtPeserta->execute([$rapat['id_rapat']]);
$pesertaList = $stmtPeserta->fetchAll();

$totalPeserta = count($pesertaList);
$jumlahHadir  = 0;
foreach ($pesertaList as $p) {
    if ($p['status_presensi'] === 'hadir') {
        $jumlahHadir++;
    }
}
$rataKehadiran = $totalPeserta > 0 ? round(($jumlahHadir / $totalPeserta) * 100) : 0;

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM rapat WHERE dibuat_oleh = ? AND status != 'dibatalkan'");
$stmtTotal->execute([$userLogin['id_user']]);
$totalRapat = (int) $stmtTotal->fetchColumn();

$belumPernahDibuka = ((int) $rapat['presensi_dibuka'] === 0 && $rapat['status'] === 'terjadwal');

$pageTitle  = 'Presensi - Hubble';
$pageDesc   = 'Presensi rapat di Hubble: buka presensi dan koreksi status kehadiran peserta.';
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
    <a href="<?= base_url('moderator/presensi.php?id=' . $rapat['id_rapat']) ?>" class="tab active">Presensi</a>
    <a href="<?= base_url('moderator/notulensi.php?id=' . $rapat['id_rapat']) ?>" class="tab">Notulensi</a>
    <a href="<?= base_url('moderator/rekap-kehadiran.php?id=' . $rapat['id_rapat']) ?>" class="tab">Rekap Kehadiran</a>
  </nav>

  <?php if ($belumPernahDibuka): ?>
    <!-- Keadaan A: presensi belum dibuka -->
    <section class="lock-card" id="state-closed">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path fill="#000" d="M12 1.5a5 5 0 0 0-5 5V9H6a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3v-8a3 3 0 0 0-3-3h-1V6.5a5 5 0 0 0-5-5zm-3 5a3 3 0 0 1 6 0V9H9V6.5zm3 7.5a1.5 1.5 0 0 1 1 2.6V19h-2v-2.4A1.5 1.5 0 0 1 12 14z"/>
      </svg>
      <h2>Presensi belum dibuka</h2>
      <p>Anggota belum bisa mengisi presensi. Buka presensi agar peserta dapat melakukan konfirmasi kehadiran.</p>
      <form method="POST" action="<?= base_url('proses/presensi.php') ?>">
        <input type="hidden" name="aksi" value="buka">
        <input type="hidden" name="id_rapat" value="<?= $rapat['id_rapat'] ?>">
        <button type="submit" class="btn-lock" id="btn-buka-presensi">Buka Presensi</button>
      </form>
    </section>
  <?php else: ?>
    <!-- Keadaan B: presensi dibuka atau sudah ditutup -->
    <section id="state-open">
      <div class="stats-row stats-big">
        <div class="stat-card">
          <div class="stat-label">Rata-rata kehadiran</div>
          <div class="stat-value"><?= $rataKehadiran ?>%</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Total rapat</div>
          <div class="stat-value"><?= $totalRapat ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Total peserta</div>
          <div class="stat-value"><?= $totalPeserta ?></div>
        </div>
      </div>

      <?php if ((int) $rapat['presensi_dibuka'] === 1): ?>
        <div class="presence-banner">
          <div class="presence-banner-text">
            <svg viewBox="0 0 30 30" aria-hidden="true">
              <circle cx="15" cy="15" r="13" fill="none" stroke="#2FB162" stroke-width="2"/>
              <path d="M8.5 15.5 L13 20 L21.5 10.5" fill="none" stroke="#2FB162" stroke-width="2"/>
            </svg>
            Presensi sedang dibuka
          </div>
          <button type="button" class="btn-banner" id="btn-tutup-presensi">Tutup Presensi</button>
        </div>
      <?php else: ?>
        <div class="presence-banner" style="background: #f5f5f5; border-color: #888;">
          <div class="presence-banner-text" style="color: #333;">
            Presensi sudah ditutup
          </div>
          <form method="POST" action="<?= base_url('proses/presensi.php') ?>">
            <input type="hidden" name="aksi" value="buka">
            <input type="hidden" name="id_rapat" value="<?= $rapat['id_rapat'] ?>">
            <button type="submit" class="btn-banner">Buka Kembali</button>
          </form>
        </div>
      <?php endif; ?>

      <!-- Tabel presensi -->
      <div class="table-wrap">
        <table class="att-table">
          <thead>
            <tr>
              <th>Nama</th>
              <th>Email</th>
              <th>Status</th>
              <th class="col-center">Waktu presensi</th>
              <th class="col-right">Tindak lanjut</th>
            </tr>
          </thead>
          <tbody id="att-body">
            <?php foreach ($pesertaList as $peserta): ?>
              <?php
                $statusKode  = $peserta['status_presensi'] ?: 'belum';
                $waktuTampil = ($statusKode === 'hadir' && !empty($peserta['attended_at']))
                    ? date('H:i', strtotime($peserta['attended_at']))
                    : '–';
              ?>
              <tr data-peserta-id="<?= $peserta['id_peserta'] ?>"
                  data-name="<?= e($peserta['nama']) ?>"
                  data-email="<?= e($peserta['email']) ?>"
                  data-status="<?= e($statusKode) ?>">
                <td><?= e($peserta['nama']) ?></td>
                <td class="email"><?= e($peserta['email']) ?></td>
                <td>
                  <?php if ($statusKode === 'hadir'): ?>
                    <span class="st-hadir">Hadir</span>
                  <?php elseif ($statusKode === 'izin'): ?>
                    <span class="st-izin">Izin</span>
                  <?php else: ?>
                    <span style="color: #888; font-size: 11px;">Belum diisi</span>
                  <?php endif; ?>
                </td>
                <td class="col-center col-time"><?= $waktuTampil ?></td>
                <td class="col-right"><button type="button" class="btn-koreksi">Koreksi</button></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>

  <!-- Modal koreksi status presensi -->
  <div class="modal-overlay" id="modal-koreksi">
    <div class="pm-card" role="dialog" aria-modal="true" aria-labelledby="koreksi-title">
      <button type="button" class="pm-close" data-close aria-label="Tutup">
        <svg viewBox="0 0 28 28" aria-hidden="true"><path d="M3 3 L25 25 M25 3 L3 25" stroke="#000" stroke-width="1.5" fill="none"/></svg>
      </button>
      <h2 class="pm-title" id="koreksi-title">Koreksi Status Presensi</h2>
      <div class="pm-user">
        <svg class="pm-avatar" viewBox="0 0 32 32" aria-hidden="true">
          <circle cx="16" cy="16" r="16" fill="#000"/>
          <circle cx="16" cy="12.5" r="5" fill="#fff"/>
          <path d="M6.5 27c1-5 5-8 9.5-8s8.500 3 9.500 8c-2.500 2.700-5.800 4-9.500 4s-7-1.300-9.500-4z" fill="#fff"/>
        </svg>
        <div class="pm-user-info">
          <span class="pm-user-name" id="koreksi-name">-</span>
          <span class="pm-user-email" id="koreksi-email">-</span>
        </div>
      </div>
      <div class="pm-status-row">
        <span>Status saat ini</span>
        <span class="pm-pill" id="koreksi-current">-</span>
      </div>

      <form method="POST" action="<?= base_url('proses/presensi.php') ?>">
        <input type="hidden" name="aksi" value="koreksi">
        <input type="hidden" name="id_rapat" value="<?= $rapat['id_rapat'] ?>">
        <input type="hidden" name="id_peserta" id="koreksi-id-peserta">
        <p class="pm-sub">Ubah status menjadi:</p>
        <label class="pm-radio"><input type="radio" name="status" value="hadir"> Hadir</label>
        <label class="pm-radio"><input type="radio" name="status" value="izin"> Izin</label>
        <div class="pm-actions">
          <button type="submit" class="pm-btn pm-btn-primary" id="btn-simpan-koreksi">Simpan</button>
          <button type="button" class="pm-btn pm-btn-outline" data-close>Batal</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal tutup presensi -->
  <div class="modal-overlay" id="modal-tutup">
    <div class="pm-card" role="dialog" aria-modal="true" aria-labelledby="tutup-title">
      <button type="button" class="pm-close" data-close aria-label="Tutup">
        <svg viewBox="0 0 28 28" aria-hidden="true"><path d="M3 3 L25 25 M25 3 L3 25" stroke="#000" stroke-width="1.5" fill="none"/></svg>
      </button>
      <h2 class="pm-title" id="tutup-title">Tutup Presensi?</h2>
      <p class="pm-text">Setelah ditutup, anggota tidak bisa mengisi atau mengubah presensi.</p>
      <form method="POST" action="<?= base_url('proses/presensi.php') ?>" class="pm-actions">
        <input type="hidden" name="aksi" value="tutup">
        <input type="hidden" name="id_rapat" value="<?= $rapat['id_rapat'] ?>">
        <button type="submit" class="pm-btn pm-btn-primary">Tutup</button>
        <button type="button" class="pm-btn pm-btn-outline" data-close>Batal</button>
      </form>
    </div>
  </div>

<?php
$extraScripts = ['assets/js/presensi.js'];
include __DIR__ . '/../includes/footer.php';
?>
