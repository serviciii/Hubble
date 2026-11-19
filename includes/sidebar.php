<?php
$activeMenu = $activeMenu ?? 'dashboard';
$userLogin  = $userLogin ?? getUserLogin($pdo);

// Tentukan tujuan menu "Rapat saya" secara otomatis sesuai rapat terakhir pengguna
$linkRapatSaya = base_url('moderator/detail-rapat.php');
if ($userLogin) {
    $stmtNav = $pdo->prepare("
        SELECT r.id_rapat, pr.role
        FROM peserta_rapat pr
        JOIN rapat r ON r.id_rapat = pr.id_rapat
        WHERE pr.id_user = ? AND r.status != 'dibatalkan'
        ORDER BY r.id_rapat DESC
        LIMIT 1
    ");
    $stmtNav->execute([$userLogin['id_user']]);
    $rapatTerakhir = $stmtNav->fetch();

    if ($rapatTerakhir) {
        if ($rapatTerakhir['role'] === 'Moderator') {
            $linkRapatSaya = base_url('moderator/detail-rapat.php?id=' . $rapatTerakhir['id_rapat']);
        } else {
            $linkRapatSaya = base_url('peserta/detail-rapat.php?id=' . $rapatTerakhir['id_rapat']);
        }
    }
}
?>
<aside class="sidebar">
  <!-- Logo utama -->
    <a href="<?= base_url('dashboard.php') ?>" class="logo" style="text-decoration: none; color: inherit;">
      <div class="logo-circle"></div>
      <span class="logo-text">Hubble</span>
    </a>

  <!-- Tombol Hamburger (hanya tampil di mobile <= 768px) -->
  <button type="button" class="mobile-menu-btn" id="btn-mobile-menu" aria-label="Buka menu navigasi" aria-expanded="false">
    <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true">
      <path d="M3 6h18M3 12h18M3 18h18" stroke="#000" stroke-width="2" stroke-linecap="round"/>
    </svg>
  </button>

  <!-- Latar gelap saat drawer mobile terbuka -->
  <div class="sidebar-backdrop" id="sidebar-backdrop"></div>

  <!-- Pembungkus menu & profil (Sidebar di desktop, Drawer di mobile) -->
  <div class="sidebar-nav-drawer" id="sidebar-drawer">
    <!-- Header khusus drawer mobile -->
    <div class="mobile-drawer-header">
      <div class="logo">
        <div class="logo-circle"></div>
        <span class="logo-text">Hubble</span>
      </div>
      <button type="button" class="mobile-close-btn" id="btn-close-menu" aria-label="Tutup menu navigasi">
        <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true">
          <path d="M5 5l14 14M19 5L5 19" stroke="#000" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>
    </div>

    <!-- Menu navigasi: Dashboard & Rapat saya -->
    <ul class="sidebar-menu">
      <li class="<?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
        <a href="<?= base_url('dashboard.php') ?>">
          <span class="menu-icon"></span>
          Dashboard
        </a>
      </li>
      <li class="<?= $activeMenu === 'rapat' ? 'active' : '' ?>">
        <a href="<?= $linkRapatSaya ?>">
          <span class="menu-icon"></span>
          Rapat saya
        </a>
      </li>
    </ul>

    <!-- Profil & Pengaturan Akun di bagian bawah -->
    <a href="<?= base_url('akun/pengaturan.php') ?>" class="sidebar-profile <?= $activeMenu === 'akun' ? 'active' : '' ?>">
      <img src="<?= base_url('assets/img/avatar.svg') ?>" alt="Avatar">
      <div class="sidebar-profile-info">
        <span class="sidebar-profile-name"><?= e($userLogin['nama'] ?? 'Hubble') ?></span>
        <span class="sidebar-profile-email"><?= e($userLogin['email'] ?? '') ?></span>
        <span class="mobile-account-label">Pengaturan Akun</span>
      </div>
    </a>
  </div>
</aside>
