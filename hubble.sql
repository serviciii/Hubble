-- ============================================================================
-- Hubble - Skema Database MySQL 8.0+ / MariaDB 10.4+
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `hubble`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `hubble`;

-- 1. TABEL USERS
CREATE TABLE `users` (
  `id_user`       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama`          VARCHAR(120)    NOT NULL,
  `email`         VARCHAR(191)    NOT NULL,
  `password_hash` VARCHAR(255)    NULL,
  `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id_user`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABEL RAPAT
CREATE TABLE `rapat` (
  `id_rapat`        BIGINT UNSIGNED                                    NOT NULL AUTO_INCREMENT,
  `dibuat_oleh`     BIGINT UNSIGNED                                    NOT NULL,
  `judul`           VARCHAR(180)                                       NOT NULL,
  `tanggal`         DATE                                               NOT NULL,
  `waktu_mulai`     TIME                                               NOT NULL,
  `waktu_selesai`   TIME                                               NOT NULL,
  `lokasi`          VARCHAR(180)                                       NULL,
  `link_meeting`    VARCHAR(1000)                                      NULL,
  `passcode_hash`   VARCHAR(255)                                       NULL,
  `agenda`          TEXT                                               NULL,
  `berulang`        TINYINT(1)                                         NOT NULL DEFAULT 0,
  `pola_berulang`   ENUM('harian','mingguan','bulanan')                NULL,
  `kode_rapat`      VARCHAR(16)                                        NOT NULL,
  `status`          ENUM('terjadwal','berlangsung','selesai','dibatalkan') NOT NULL DEFAULT 'terjadwal',
  `presensi_dibuka` TINYINT(1)                                         NOT NULL DEFAULT 0,
  `created_at`      TIMESTAMP                                          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP                                          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id_rapat`),
  UNIQUE KEY `uq_rapat_kode` (`kode_rapat`),
  KEY `idx_rapat_dibuat_oleh` (`dibuat_oleh`),
  KEY `idx_rapat_tanggal` (`tanggal`),
  KEY `idx_rapat_status` (`status`),

  CONSTRAINT `fk_rapat_dibuat_oleh`
    FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id_user`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

  CONSTRAINT `chk_rapat_waktu`
    CHECK (`waktu_selesai` > `waktu_mulai`),

  CONSTRAINT `chk_rapat_pola_berulang`
    CHECK (
      (`berulang` = 0 AND `pola_berulang` IS NULL)
      OR
      (`berulang` = 1 AND `pola_berulang` IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABEL PESERTA_RAPAT
CREATE TABLE `peserta_rapat` (
  `id_peserta` BIGINT UNSIGNED                        NOT NULL AUTO_INCREMENT,
  `id_rapat`   BIGINT UNSIGNED                        NOT NULL,
  `id_user`    BIGINT UNSIGNED                        NOT NULL,
  `role`       ENUM('Moderator','Notulis','Anggota')  NOT NULL DEFAULT 'Anggota',
  `joined_at`  TIMESTAMP                              NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id_peserta`),
  UNIQUE KEY `uq_peserta_rapat_user` (`id_rapat`, `id_user`),
  KEY `idx_peserta_rapat_user` (`id_user`),
  KEY `idx_peserta_rapat_role` (`id_rapat`, `role`),

  CONSTRAINT `fk_peserta_rapat_rapat`
    FOREIGN KEY (`id_rapat`) REFERENCES `rapat` (`id_rapat`)
    ON UPDATE CASCADE
    ON DELETE CASCADE,

  CONSTRAINT `fk_peserta_rapat_user`
    FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABEL PRESENSI
CREATE TABLE `presensi` (
  `id_presensi`           BIGINT UNSIGNED                      NOT NULL AUTO_INCREMENT,
  `id_peserta`            BIGINT UNSIGNED                      NOT NULL,
  `status`                ENUM('hadir','izin')                 NOT NULL,
  `alasan_izin`           TEXT                                 NULL,
  `attended_at`           TIMESTAMP                            NULL,
  `status_ketepatan`      ENUM('tepat_waktu','terlambat')      NULL,
  `overridden_at`         TIMESTAMP                            NULL,
  `overridden_by_user_id` BIGINT UNSIGNED                      NULL,
  `created_at`            TIMESTAMP                            NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            TIMESTAMP                            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id_presensi`),
  UNIQUE KEY `uq_presensi_peserta` (`id_peserta`),
  KEY `idx_presensi_status` (`status`),
  KEY `idx_presensi_overridden_by` (`overridden_by_user_id`),

  CONSTRAINT `fk_presensi_peserta`
    FOREIGN KEY (`id_peserta`) REFERENCES `peserta_rapat` (`id_peserta`)
    ON UPDATE CASCADE
    ON DELETE CASCADE,

  CONSTRAINT `fk_presensi_overridden_by`
    FOREIGN KEY (`overridden_by_user_id`) REFERENCES `users` (`id_user`)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABEL NOTULEN
CREATE TABLE `notulen` (
  `id_notulen`          BIGINT UNSIGNED                                                              NOT NULL AUTO_INCREMENT,
  `id_rapat`            BIGINT UNSIGNED                                                              NOT NULL,
  `id_penulis_peserta`  BIGINT UNSIGNED                                                              NULL,
  `isi_notulen`         LONGTEXT                                                                     NULL,
  `status`              ENUM('draft','submitted','revision_requested','finalized','published')       NOT NULL DEFAULT 'draft',
  `submitted_at`        TIMESTAMP                                                                    NULL,
  `finalized_at`        TIMESTAMP                                                                    NULL,
  `reviewed_by_user_id` BIGINT UNSIGNED                                                              NULL,
  `review_note`         TEXT                                                                         NULL,
  `created_at`          TIMESTAMP                                                                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP                                                                    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id_notulen`),
  UNIQUE KEY `uq_notulen_rapat` (`id_rapat`),
  KEY `idx_notulen_penulis` (`id_penulis_peserta`),
  KEY `idx_notulen_status` (`status`),
  KEY `idx_notulen_reviewed_by` (`reviewed_by_user_id`),

  CONSTRAINT `fk_notulen_rapat`
    FOREIGN KEY (`id_rapat`) REFERENCES `rapat` (`id_rapat`)
    ON UPDATE CASCADE
    ON DELETE CASCADE,

  CONSTRAINT `fk_notulen_penulis`
    FOREIGN KEY (`id_penulis_peserta`) REFERENCES `peserta_rapat` (`id_peserta`)
    ON UPDATE CASCADE
    ON DELETE SET NULL,

  CONSTRAINT `fk_notulen_reviewed_by`
    FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id_user`)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
