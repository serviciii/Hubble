<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$userLogin = wajibLogin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}

$aksi    = $_POST['aksi'] ?? '';
$idRapat = (int) ($_POST['id_rapat'] ?? 0);

// === BAGIAN MODERATOR ===
if (in_array($aksi, ['tunjuk', 'revisi', 'publikasi'], true)) {
    $stmtRapat = $pdo->prepare("SELECT * FROM rapat WHERE id_rapat = ? AND dibuat_oleh = ? AND status != 'dibatalkan' LIMIT 1");
    $stmtRapat->execute([$idRapat, $userLogin['id_user']]);
    $rapat = $stmtRapat->fetch();

    if (!$rapat) {
        redirect('dashboard.php');
    }

    // 1. Tunjuk / Ganti Notulis
    if ($aksi === 'tunjuk') {
        $idPeserta = (int) ($_POST['id_peserta'] ?? 0);

        $stmtCalon = $pdo->prepare("
            SELECT pr.*, u.nama
            FROM peserta_rapat pr
            JOIN users u ON u.id_user = pr.id_user
            WHERE pr.id_rapat = ? AND pr.id_peserta = ? AND pr.role != 'Moderator'
            LIMIT 1
        ");
        $stmtCalon->execute([$idRapat, $idPeserta]);
        $calon = $stmtCalon->fetch();

        if (!$calon) {
            redirect('moderator/notulensi.php?id=' . $idRapat);
        }

        if ($calon['role'] === 'Notulis') {
            setFlash('error_sama', true);
            redirect('moderator/notulensi.php?id=' . $idRapat);
        }

        $pdo->beginTransaction();
        try {
            // Kembalikan notulis lama menjadi Anggota
            $stmtReset = $pdo->prepare("UPDATE peserta_rapat SET role = 'Anggota' WHERE id_rapat = ? AND role = 'Notulis'");
            $stmtReset->execute([$idRapat]);

            // Tetapkan notulis baru
            $stmtSet = $pdo->prepare("UPDATE peserta_rapat SET role = 'Notulis' WHERE id_peserta = ?");
            $stmtSet->execute([$idPeserta]);

            // Siapkan atau perbarui penulis pada tabel notulen
            $stmtCekNotulen = $pdo->prepare('SELECT id_notulen FROM notulen WHERE id_rapat = ? LIMIT 1');
            $stmtCekNotulen->execute([$idRapat]);
            if ($stmtCekNotulen->fetch()) {
                $stmtUpd = $pdo->prepare('UPDATE notulen SET id_penulis_peserta = ? WHERE id_rapat = ?');
                $stmtUpd->execute([$idPeserta, $idRapat]);
            } else {
                $stmtIns = $pdo->prepare("INSERT INTO notulen (id_rapat, id_penulis_peserta, status) VALUES (?, ?, 'draft')");
                $stmtIns->execute([$idRapat, $idPeserta]);
            }

            $pdo->commit();
            setFlash('sukses_tunjuk_nama', $calon['nama']);
        } catch (Exception $e) {
            $pdo->rollBack();
        }

        redirect('moderator/notulensi.php?id=' . $idRapat);
    }

    // Ambil ID peserta notulis saat ini (jika ada)
    $stmtNotulis = $pdo->prepare("SELECT id_peserta FROM peserta_rapat WHERE id_rapat = ? AND role = 'Notulis' LIMIT 1");
    $stmtNotulis->execute([$idRapat]);
    $idNotulis = $stmtNotulis->fetchColumn() ?: null;

    $stmtCekNotulen = $pdo->prepare('SELECT id_notulen FROM notulen WHERE id_rapat = ? LIMIT 1');
    $stmtCekNotulen->execute([$idRapat]);
    $notulenAda = (bool) $stmtCekNotulen->fetch();

    // 2. Minta Revisi
    if ($aksi === 'revisi') {
        $catatan = trim($_POST['review_note'] ?? '') ?: 'Silakan perbaiki dan lengkapi isi notulensi.';

        if ($notulenAda) {
            $stmtUpd = $pdo->prepare("
                UPDATE notulen SET
                    status = 'revision_requested',
                    review_note = ?,
                    reviewed_by_user_id = ?
                WHERE id_rapat = ?
            ");
            $stmtUpd->execute([$catatan, $userLogin['id_user'], $idRapat]);
        } else {
            $stmtIns = $pdo->prepare("
                INSERT INTO notulen (id_rapat, id_penulis_peserta, status, review_note, reviewed_by_user_id)
                VALUES (?, ?, 'revision_requested', ?, ?)
            ");
            $stmtIns->execute([$idRapat, $idNotulis, $catatan, $userLogin['id_user']]);
        }

        redirect('moderator/notulensi.php?id=' . $idRapat);
    }

    // 3. Finalisasi & Publikasi
    if ($aksi === 'publikasi') {
        $sekarang = date('Y-m-d H:i:s');

        if ($notulenAda) {
            $stmtUpd = $pdo->prepare("
                UPDATE notulen SET
                    status = 'published',
                    finalized_at = ?,
                    reviewed_by_user_id = ?
                WHERE id_rapat = ?
            ");
            $stmtUpd->execute([$sekarang, $userLogin['id_user'], $idRapat]);
        } else {
            $stmtIns = $pdo->prepare("
                INSERT INTO notulen (id_rapat, id_penulis_peserta, status, finalized_at, reviewed_by_user_id)
                VALUES (?, ?, 'published', ?, ?)
            ");
            $stmtIns->execute([$idRapat, $idNotulis, $sekarang, $userLogin['id_user']]);
        }

        redirect('moderator/notulensi.php?id=' . $idRapat);
    }
}

// === BAGIAN NOTULIS ===
if (in_array($aksi, ['simpan_draf', 'ajukan', 'tarik'], true)) {
    $stmtPeserta = $pdo->prepare("
        SELECT pr.*
        FROM peserta_rapat pr
        JOIN rapat r ON r.id_rapat = pr.id_rapat
        WHERE pr.id_user = ? AND pr.id_rapat = ? AND pr.role = 'Notulis' AND r.status != 'dibatalkan'
        LIMIT 1
    ");
    $stmtPeserta->execute([$userLogin['id_user'], $idRapat]);
    $keikutsertaan = $stmtPeserta->fetch();

    if (!$keikutsertaan) {
        redirect('dashboard.php');
    }

    $stmtNotulen = $pdo->prepare('SELECT * FROM notulen WHERE id_rapat = ? LIMIT 1');
    $stmtNotulen->execute([$idRapat]);
    $notulen = $stmtNotulen->fetch();

    if (!$notulen) {
        $stmtIns = $pdo->prepare("INSERT INTO notulen (id_rapat, id_penulis_peserta, status) VALUES (?, ?, 'draft')");
        $stmtIns->execute([$idRapat, $keikutsertaan['id_peserta']]);
        $notulen = ['status' => 'draft'];
    }

    if ($aksi === 'tarik') {
        if ($notulen['status'] === 'submitted') {
            $stmtUpd = $pdo->prepare("UPDATE notulen SET status = 'draft' WHERE id_rapat = ?");
            $stmtUpd->execute([$idRapat]);
        }
        setFlash('sukses', 'Pengajuan notulensi berhasil ditarik kembali ke draf.');
        redirect('notulis/notulensi.php?id=' . $idRapat);
    }

    $isiNotulen = trim($_POST['isi_notulen'] ?? '');

    if ($aksi === 'ajukan') {
        if ($isiNotulen === '') {
            setFlash('error', 'Isi notulensi tidak boleh kosong sebelum diajukan.');
            redirect('notulis/notulensi.php?id=' . $idRapat);
        }

        $stmtUpd = $pdo->prepare("
            UPDATE notulen SET
                isi_notulen = ?,
                id_penulis_peserta = ?,
                status = 'submitted',
                submitted_at = ?
            WHERE id_rapat = ?
        ");
        $stmtUpd->execute([$isiNotulen, $keikutsertaan['id_peserta'], date('Y-m-d H:i:s'), $idRapat]);

        redirect('notulis/notulensi.php?id=' . $idRapat);
    }

    // Default: simpan_draf
    $statusBaru = in_array($notulen['status'], ['draft', 'revision_requested'], true)
        ? $notulen['status']
        : 'draft';

    $stmtUpd = $pdo->prepare("
        UPDATE notulen SET
            isi_notulen = ?,
            id_penulis_peserta = ?,
            status = ?
        WHERE id_rapat = ?
    ");
    $stmtUpd->execute([$isiNotulen, $keikutsertaan['id_peserta'], $statusBaru, $idRapat]);

    setFlash('sukses', 'Draf notulensi berhasil disimpan.');
    redirect('notulis/notulensi.php?id=' . $idRapat);
}

redirect('dashboard.php');
