<?php
$pageTitle = $pageTitle ?? 'Hubble';
$pageDesc  = $pageDesc ?? 'Hubble - Aplikasi pengelolaan rapat sederhana.';
$isGuest   = $isGuest ?? false;
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= e($pageDesc) ?>">
  <title><?= e($pageTitle) ?></title>
  <!-- Google Fonts: Poppins & Roboto Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Roboto+Mono:wght@400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/responsive/layout-dashboard.css') ?>">
  <?php if (!$isGuest): ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/responsive/rapat-akun.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/responsive/presensi-notulensi.css') ?>">
  <?php endif; ?>
</head>

<body>
<?php if (!$isGuest): ?>
  <div class="app-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="main-content">
<?php endif; ?>
