<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$userLogin = wajibLogin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}

$aksi    = $_POST['aksi'] ?? '';
$idRapat = (int) ($_POST['id_rapat'] ?? 0);

// A. MODERATOR MEMBUKA PRESENSI
if ($aksi === 'buka') {
    $stmt = $pdo->prepare("SELECT * FROM rapat WHERE id_rapat = ? AND dibuat_oleh = ? AND status != 'dibatalkan' LIMIT 1");
    $stmt->execute([$idRapat, $userLogin['id_user']]);
    $rapat = $stmt->fetch();

    if ($rapat) {
        $statusBaru = $rapat['status'] === 'terjadwal' ? 'berlangsung' : $rapat['status'];
        $stmtUpd = $pdo->prepare('UPDATE rapat SET presensi_dibuka = 1, status = ? WHERE id_rapat = ?');
        $stmtUpd->execute([$statusBaru, $idRapat]);
    }

    redirect('moderator/presensi.php?id=' . $idRapat);
}

// B. MODERATOR MENUTUP PRESENSI
if ($aksi === 'tutup') {
    $stmt = $pdo->prepare("UPDATE rapat SET presensi_dibuka = 0, status = 'selesai' WHERE id_rapat = ? AND dibuat_oleh = ?");
    $stmt->execute([$idRapat, $userLogin['id_user']]);

    redirect('moderator/presensi.php?id=' . $idRapat);
}

// C. MODERATOR MENGOREKSI PRESENSI PESERTA
if ($aksi === 'koreksi') {
    $idPeserta = (int) ($_POST['id_peserta'] ?? 0);
    $status    = $_POST['status'] ?? '';

    $stmtRapat = $pdo->prepare("SELECT id_rapat FROM rapat WHERE id_rapat = ? AND dibuat_oleh = ? AND status != 'dibatalkan' LIMIT 1");
    $stmtRapat->execute([$idRapat, $userLogin['id_user']]);
    if (!$stmtRapat->fetch() || !in_array($status, ['hadir', 'izin'], true)) {
        redirect('dashboard.php');
    }

    $stmtPeserta = $pdo->prepare('SELECT id_peserta FROM peserta_rapat WHERE id_rapat = ? AND id_peserta = ? LIMIT 1');
    $stmtPeserta->execute([$idRapat, $idPeserta]);
    if ($stmtPeserta->fetch()) {
        $stmtLama = $pdo->prepare('SELECT * FROM presensi WHERE id_peserta = ? LIMIT 1');
        $stmtLama->execute([$idPeserta]);
        $lama     = $stmtLama->fetch();
        $sekarang = date('Y-m-d H:i:s');

        if ($status === 'hadir') {
            $attendedAt = ($lama && !empty($lama['attended_at'])) ? $lama['attended_at'] : $sekarang;
            $ketepatan  = ($lama && !empty($lama['status_ketepatan'])) ? $lama['status_ketepatan'] : 'tepat_waktu';

            if ($lama) {
                $stmtUpd = $pdo->prepare("
                    UPDATE presensi SET
                        status = 'hadir', alasan_izin = NULL, attended_at = ?,
                        status_ketepatan = ?, overridden_at = ?, overridden_by_user_id = ?
                    WHERE id_peserta = ?
                ");
                $stmtUpd->execute([$attendedAt, $ketepatan, $sekarang, $userLogin['id_user'], $idPeserta]);
            } else {
                $stmtIns = $pdo->prepare("
                    INSERT INTO presensi (id_peserta, status, alasan_izin, attended_at, status_ketepatan, overridden_at, overridden_by_user_id)
                    VALUES (?, 'hadir', NULL, ?, ?, ?, ?)
                ");
                $stmtIns->execute([$idPeserta, $attendedAt, $ketepatan, $sekarang, $userLogin['id_user']]);
            }
        } else {
            $alasan = ($lama && !empty($lama['alasan_izin'])) ? $lama['alasan_izin'] : 'Dikoreksi oleh Moderator';

            if ($lama) {
                $stmtUpd = $pdo->prepare("
                    UPDATE presensi SET
                        status = 'izin', alasan_izin = ?, attended_at = NULL,
                        status_ketepatan = NULL, overridden_at = ?, overridden_by_user_id = ?
                    WHERE id_peserta = ?
                ");
                $stmtUpd->execute([$alasan, $sekarang, $userLogin['id_user'], $idPeserta]);
            } else {
                $stmtIns = $pdo->prepare("
                    INSERT INTO presensi (id_peserta, status, alasan_izin, attended_at, status_ketepatan, overridden_at, overridden_by_user_id)
                    VALUES (?, 'izin', ?, NULL, NULL, ?, ?)
                ");
                $stmtIns->execute([$idPeserta, $alasan, $sekarang, $userLogin['id_user']]);
            }
        }
    }

    redirect('moderator/presensi.php?id=' . $idRapat);
}

// D. PESERTA MENGISI PRESENSI SENDIRI (HADIR / IZIN)
if ($aksi === 'simpan_peserta') {
    $status     = $_POST['status'] ?? '';
    $alasanIzin = trim($_POST['alasan_izin'] ?? '');

    $stmt = $pdo->prepare("
        SELECT r.*, pr.id_peserta
        FROM peserta_rapat pr
        JOIN rapat r ON r.id_rapat = pr.id_rapat
        WHERE pr.id_user = ? AND pr.id_rapat = ? AND r.status != 'dibatalkan'
        LIMIT 1
    ");
    $stmt->execute([$userLogin['id_user'], $idRapat]);
    $rapat = $stmt->fetch();

    if (!$rapat) {
        redirect('dashboard.php');
    }

    if ((int) $rapat['presensi_dibuka'] !== 1) {
        redirect('peserta/presensi.php?id=' . $idRapat);
    }

    if (!in_array($status, ['hadir', 'izin'], true)) {
        setFlash('error', 'Pilihan status presensi tidak valid.');
        redirect('peserta/presensi.php?id=' . $idRapat);
    }

    if ($status === 'izin' && $alasanIzin === '') {
        setFlash('error', 'Alasan izin wajib diisi jika memilih Izin.');
        redirect('peserta/presensi.php?id=' . $idRapat);
    }

    $stmtCek = $pdo->prepare('SELECT id_presensi FROM presensi WHERE id_peserta = ? LIMIT 1');
    $stmtCek->execute([$rapat['id_peserta']]);
    $sudahAda = $stmtCek->fetch();

    if ($status === 'hadir') {
        $batasTepatWaktu = strtotime($rapat['tanggal'] . ' ' . $rapat['waktu_mulai']) + (15 * 60);
        $ketepatan       = time() > $batasTepatWaktu ? 'terlambat' : 'tepat_waktu';
        $sekarang        = date('Y-m-d H:i:s');

        if ($sudahAda) {
            $stmtUpd = $pdo->prepare("UPDATE presensi SET status = 'hadir', alasan_izin = NULL, attended_at = ?, status_ketepatan = ? WHERE id_peserta = ?");
            $stmtUpd->execute([$sekarang, $ketepatan, $rapat['id_peserta']]);
        } else {
            $stmtIns = $pdo->prepare("INSERT INTO presensi (id_peserta, status, alasan_izin, attended_at, status_ketepatan) VALUES (?, 'hadir', NULL, ?, ?)");
            $stmtIns->execute([$rapat['id_peserta'], $sekarang, $ketepatan]);
        }
    } else {
        if ($sudahAda) {
            $stmtUpd = $pdo->prepare("UPDATE presensi SET status = 'izin', alasan_izin = ?, attended_at = NULL, status_ketepatan = NULL WHERE id_peserta = ?");
            $stmtUpd->execute([$alasanIzin, $rapat['id_peserta']]);
        } else {
            $stmtIns = $pdo->prepare("INSERT INTO presensi (id_peserta, status, alasan_izin, attended_at, status_ketepatan) VALUES (?, 'izin', ?, NULL, NULL)");
            $stmtIns->execute([$rapat['id_peserta'], $alasanIzin]);
        }
    }

    redirect('peserta/presensi.php?id=' . $idRapat);
}

redirect('dashboard.php');
