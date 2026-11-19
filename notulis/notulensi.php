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
        WHERE pr.id_user = ? AND pr.role = 'Notulis' AND r.status != 'dibatalkan'
        ORDER BY pr.id_peserta DESC
        LIMIT 1
    ");
    $stmt->execute([$userLogin['id_user']]);
}
$rapat = $stmt->fetch();

if (!$rapat) {
    redirect('dashboard.php');
}

if ($rapat['role'] === 'Moderator') {
    redirect('moderator/notulensi.php?id=' . $rapat['id_rapat']);
}
if ($rapat['role'] !== 'Notulis') {
    redirect('peserta/notulensi.php?id=' . $rapat['id_rapat']);
}

// Ambil atau siapkan baris notulen
$stmtNotulen = $pdo->prepare("
    SELECT n.*, u.nama AS reviewer_nama
    FROM notulen n
    LEFT JOIN users u ON u.id_user = n.reviewed_by_user_id
    WHERE n.id_rapat = ?
    LIMIT 1
");
$stmtNotulen->execute([$rapat['id_rapat']]);
$notulen = $stmtNotulen->fetch();

if (!$notulen) {
    $stmtIns = $pdo->prepare("INSERT INTO notulen (id_rapat, id_penulis_peserta, status) VALUES (?, ?, 'draft')");
    $stmtIns->execute([$rapat['id_rapat'], $rapat['id_peserta']]);
    $notulen = [
        'id_rapat'      => $rapat['id_rapat'],
        'isi_notulen'   => '',
        'status'        => 'draft',
        'review_note'   => null,
        'finalized_at'  => null,
        'reviewer_nama' => null,
    ];
}

$suksesMsg  = getFlash('sukses');
$errorMsg   = getFlash('error');
$pageTitle  = 'Notulensi Notulis - Hubble';
$pageDesc   = 'Notulensi rapat untuk notulis di Hubble.';
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

  <!-- Bar tab anggota -->
  <nav class="tabs">
    <a href="<?= base_url('peserta/detail-rapat.php?id=' . $rapat['id_rapat']) ?>" class="tab">Info &amp; agenda</a>
    <a href="<?= base_url('peserta/presensi.php?id=' . $rapat['id_rapat']) ?>" class="tab">Presensi</a>
    <a href="<?= base_url('notulis/notulensi.php?id=' . $rapat['id_rapat']) ?>" class="tab active">Notulensi</a>
  </nav>

  <?php if ($errorMsg): ?>
    <div class="alert-error alert-full"><?= e($errorMsg) ?></div>
  <?php endif; ?>

  <?php if ($suksesMsg): ?>
    <div class="alert-success alert-full"><?= e($suksesMsg) ?></div>
  <?php endif; ?>

  <?php if ($notulen['status'] === 'draft'): ?>
    <!-- Tampilan 1: Draf -->
    <section id="view-draf" class="nt-view">
      <form method="POST" action="<?= base_url('proses/notulensi.php') ?>" class="nt-notulis-card">
        <input type="hidden" name="id_rapat" value="<?= $rapat['id_rapat'] ?>">
        <div class="nt-notulis-header">
          <h2>Draft notulensi</h2>
          <span class="nt-badge nt-badge-draf">[Draf]</span>
        </div>
        <textarea class="nt-notulis-text" id="text-draf" name="isi_notulen"
          placeholder="Tulis poin pembahasan, keputusan, dan action item di sini..."><?= e(old('isi_notulen', $notulen['isi_notulen'] ?? '')) ?></textarea>
        <div class="nt-notulis-actions">
          <button type="submit" name="aksi" value="simpan_draf" class="btn-nt-biru">Simpan Draf</button>
          <button type="submit" name="aksi" value="ajukan" class="btn-nt-outline" id="btn-ajukan-moderator">Ajukan ke Moderator</button>
        </div>
      </form>
    </section>

  <?php elseif ($notulen['status'] === 'revision_requested'): ?>
    <!-- Tampilan 2: Perlu revisi -->
    <section id="view-revisi" class="nt-view">
      <div class="nt-banner nt-banner-yellow">
        <svg viewBox="0 0 24 24" aria-hidden="true" class="nt-banner-icon-pencil">
          <path fill="#F09B36" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
        </svg>
        <div class="nt-banner-text">
          <strong>Moderator meminta revisi</strong>
          <span><?= e($notulen['review_note'] ?: 'Silakan perbaiki isi notulensi.') ?></span>
        </div>
      </div>
      <form method="POST" action="<?= base_url('proses/notulensi.php') ?>" class="nt-notulis-card nt-card-revisi">
        <input type="hidden" name="id_rapat" value="<?= $rapat['id_rapat'] ?>">
        <div class="nt-notulis-header">
          <h2>Draft notulensi</h2>
          <span class="nt-badge nt-badge-revisi">Perlu revisi</span>
        </div>
        <textarea class="nt-notulis-text nt-text-revisi" id="text-revisi" name="isi_notulen"
          placeholder="Tulis poin pembahasan, keputusan, dan action item di sini..."><?= e(old('isi_notulen', $notulen['isi_notulen'] ?? '')) ?></textarea>
        <div class="nt-notulis-actions">
          <button type="submit" name="aksi" value="simpan_draf" class="btn-nt-biru">Simpan Draf</button>
          <button type="submit" name="aksi" value="ajukan" class="btn-nt-outline" id="btn-ajukan-ulang">Ajukan Ulang</button>
        </div>
      </form>
    </section>

  <?php elseif ($notulen['status'] === 'submitted'): ?>
    <!-- Tampilan 3: Diajukan -->
    <section id="view-diajukan" class="nt-view">
      <div class="nt-view-narrow">
        <div class="nt-banner nt-banner-yellow">
          <svg viewBox="0 0 24 24" aria-hidden="true" class="nt-banner-icon-sand">
            <path fill="#E3C25B" d="M6 2v6h.01L6 8.01 10 12l-4 4 .01.01H6V22h12v-5.99h-.01L18 16l-4-4 4-3.99-.01-.01H18V2H6zm10 14.5V20H8v-3.5l4-4 4 4zm-4-5l-4-4V4h8v3.5l-4 4z"/>
            <polygon fill="#7CA6D8" points="8 20 16 20 16 16.5 12 12.5 8 16.5"/>
          </svg>
          <div class="nt-banner-text">
            <strong>Notulensi sudah diajukan</strong>
            <span>Menunggu review dari moderator.</span>
          </div>
        </div>
        <div class="nt-notulis-header-narrow">
          <h2>Draft notulensi</h2>
          <span class="nt-badge nt-badge-draf-solid">[Draf]</span>
        </div>
        <textarea class="nt-notulis-text nt-text-diajukan" id="text-diajukan" readonly aria-label="Draf yang diajukan"><?= e($notulen['isi_notulen'] ?? '') ?></textarea>
        <form method="POST" action="<?= base_url('proses/notulensi.php') ?>">
          <input type="hidden" name="id_rapat" value="<?= $rapat['id_rapat'] ?>">
          <button type="submit" name="aksi" value="tarik" class="btn-nt-biru btn-tarik" id="btn-tarik-pengajuan">Tarik Pengajuan</button>
        </form>
      </div>
    </section>

  <?php else: ?>
    <!-- Tampilan 4: Dipublikasikan -->
    <section id="view-dipublikasikan" class="nt-view">
      <div class="nt-view-narrow">
        <div class="nt-banner nt-banner-green">
          <svg viewBox="0 0 38 38" aria-hidden="true" class="nt-banner-icon-check">
            <circle cx="19" cy="19" r="17" fill="none" stroke="#2FB162" stroke-width="2"/>
            <path d="M11 19 L17 25 L27 13" fill="none" stroke="#2FB162" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <div class="nt-banner-text">
            <strong>Notulensi sudah dipublikasikan</strong>
            <span>Notulensi dapat dilihat oleh peserta.</span>
          </div>
        </div>
        <div class="nt-notulis-header-narrow">
          <h2>Notulensi Rapat</h2>
          <span class="nt-badge nt-badge-final">[Final]</span>
        </div>
        <textarea class="nt-notulis-text nt-text-publikasi" id="text-publikasi" readonly aria-label="Notulensi publik"><?= e($notulen['isi_notulen'] ?? '') ?></textarea>
        <div class="nt-publikasi-footer">
          Dipublikasikan oleh <?= e($notulen['reviewer_nama'] ?? 'Moderator') ?><br>
          <?php if (!empty($notulen['finalized_at'])): ?>
            pada <?= formatTanggalIndo($notulen['finalized_at'], false, true) ?>
          <?php endif; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
