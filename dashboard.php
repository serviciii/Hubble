<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

$userLogin = wajibLogin($pdo);

// Ambil semua rapat aktif yang diikuti pengguna (baik sebagai Moderator, Notulis, maupun Anggota)
$stmt = $pdo->prepare("
    SELECT r.*, pr.id_peserta, pr.role
    FROM peserta_rapat pr
    JOIN rapat r ON r.id_rapat = pr.id_rapat
    WHERE pr.id_user = ? AND r.status != 'dibatalkan'
    ORDER BY r.tanggal ASC, r.waktu_mulai ASC
");
$stmt->execute([$userLogin['id_user']]);
$rapatList = $stmt->fetchAll();

$totalRapat = count($rapatList);

// Hitung total status izin milik pengguna ini
$stmtIzin = $pdo->prepare("
    SELECT COUNT(*)
    FROM presensi p
    JOIN peserta_rapat pr ON pr.id_peserta = p.id_peserta
    WHERE pr.id_user = ? AND p.status = 'izin'
");
$stmtIzin->execute([$userLogin['id_user']]);
$totalIzin = (int) $stmtIzin->fetchColumn();

$infoMessage     = getFlash('info');
$joinBerhasilUrl = getFlash('join_berhasil_url');
$joinGagalPesan  = getFlash('join_gagal');
$openJoinKode    = getFlash('open_join_kode');

$pageTitle  = 'Dashboard - Hubble';
$pageDesc   = 'Dashboard Hubble - kelola dan pantau semua rapat Anda.';
$activeMenu = 'dashboard';

include __DIR__ . '/includes/header.php';
?>

  <!-- Header: judul + aksi -->
  <div class="dashboard-header">
    <div class="dashboard-title">
      <h1>Dashboard</h1>
      <p><?= formatTanggalIndo(date('Y-m-d')) ?></p>
    </div>
    <div class="dashboard-actions">
      <input type="text" id="input-kode-rapat" placeholder="Masukkan kode rapat" readonly>
      <button class="btn-buat-rapat" type="button" data-href="<?= base_url('moderator/buat-rapat.php') ?>">+ Buat Rapat</button>
    </div>
  </div>

  <?php if ($infoMessage): ?>
    <div class="alert-error"><?= e($infoMessage) ?></div>
  <?php endif; ?>

  <!-- Kartu statistik -->
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-label">Rapat</div>
      <div class="stat-value"><?= $totalRapat ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Izin</div>
      <div class="stat-value"><?= $totalIzin ?></div>
    </div>
  </div>

  <!-- Rapat Mendatang -->
  <section class="upcoming-section">
    <h2>Rapat Mendatang</h2>

    <?php if (empty($rapatList)): ?>
      <div class="upcoming-empty">
        <img src="<?= base_url('assets/img/calendar.svg') ?>" alt="Ilustrasi kalender">
        <div class="upcoming-empty-text">
          <p>Belum ada rapat mendatang</p>
          <p>Rapatmu akan muncul di sini.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="upcoming-list">
        <?php foreach ($rapatList as $rapat): ?>
          <?php
            $isModerator = ((int) $rapat['dibuat_oleh'] === (int) $userLogin['id_user']);
            $linkTujuan  = $isModerator
                ? base_url('moderator/detail-rapat.php?id=' . $rapat['id_rapat'])
                : base_url('peserta/detail-rapat.php?id=' . $rapat['id_rapat']);
          ?>
          <a href="<?= $linkTujuan ?>" class="upcoming-item">
            <div class="upcoming-item-info">
              <div class="upcoming-item-title"><?= e($rapat['judul']) ?></div>
              <div class="upcoming-item-date">
                <?= formatTanggalIndo($rapat['tanggal']) ?>
                &bull; <?= substr($rapat['waktu_mulai'], 0, 5) ?> - <?= substr($rapat['waktu_selesai'], 0, 5) ?>
              </div>
            </div>
            <div class="upcoming-item-meta">
              <span class="upcoming-badge-code">Kode: <?= e($rapat['kode_rapat']) ?></span>
              <span class="upcoming-badge-role"><?= $isModerator ? 'Moderator' : e($rapat['role']) ?></span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- Modal Join Rapat -->
  <div class="modal-overlay join-overlay <?= $openJoinKode ? 'show' : '' ?>" id="modal-join">
    <div class="join-card" role="dialog" aria-modal="true" aria-labelledby="join-title">
      <h2 class="join-title" id="join-title">Join Rapat</h2>
      <p class="join-sub">Masukkan kode atau link rapat</p>
      <form class="join-form" id="form-join" method="POST" action="<?= base_url('proses/join-rapat.php') ?>">
        <label class="join-label" for="join-kode">Kode / Link Rapat</label>
        <input class="join-input" type="text" id="join-kode" name="kode"
          value="<?= e($openJoinKode ?: old('kode')) ?>" placeholder="Contoh: HBL9A1B" required>

        <label class="join-label" for="join-pass">Passcode (Opsional)</label>
        <input class="join-input" type="password" id="join-pass" name="passcode" placeholder="Masukkan Passcode">

        <button type="submit" class="join-btn" id="btn-lanjut">Lanjut</button>
      </form>
    </div>
  </div>

  <!-- Modal Berhasil Join -->
  <div class="modal-overlay join-overlay <?= $joinBerhasilUrl ? 'show' : '' ?>" id="modal-berhasil">
    <div class="join-card join-result" role="dialog" aria-modal="true">
      <h2 class="join-title">Berhasil Join</h2>
      <p class="join-sub">Kamu akan diarahkan ke rapat</p>
      <svg class="join-icon" viewBox="0 0 146 146" aria-hidden="true">
        <circle cx="73" cy="73" r="68" fill="none" stroke="#2FB162" stroke-width="10" />
        <path d="M40 76 L64 100 L108 52" fill="none" stroke="#2FB162" stroke-width="12" stroke-linecap="butt" stroke-linejoin="miter" />
      </svg>
      <button type="button" class="join-btn" id="btn-masuk-rapat"
        data-href="<?= e($joinBerhasilUrl ?: base_url('dashboard.php')) ?>">
        Masuk ke Rapat
      </button>
    </div>
  </div>

  <!-- Modal Gagal Join -->
  <div class="modal-overlay join-overlay <?= $joinGagalPesan ? 'show' : '' ?>" id="modal-gagal">
    <div class="join-card join-result" role="dialog" aria-modal="true">
      <h2 class="join-title">Gagal Join</h2>
      <p class="join-sub"><?= e($joinGagalPesan ?: 'Kode atau passcode yang kamu masukkan tidak sesuai. Coba periksa kembali.') ?></p>
      <svg class="join-icon" viewBox="0 0 146 146" aria-hidden="true">
        <circle cx="73" cy="73" r="66" fill="none" stroke="#A01C1C" stroke-width="8" />
        <path d="M47 47 L99 99 M99 47 L47 99" fill="none" stroke="#A01C1C" stroke-width="9" stroke-linecap="round" />
      </svg>
      <button type="button" class="join-btn" id="btn-coba-lagi">Coba Lagi</button>
    </div>
  </div>

<?php
$extraScripts = ['assets/js/join.js'];
include __DIR__ . '/includes/footer.php';
?>
