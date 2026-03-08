-- ============================================================
-- MIGRATION v2: Features 5 (Log), 6 (Aturan), 9 (Aset), 10 (Waitlist)
-- Run once against the reservasi_tvri database
-- ============================================================
USE `reservasi_tvri`;

-- ----------------------------------------------------------------
-- Feature 5: Activity Logs
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT UNSIGNED DEFAULT NULL,
    `user_nama`   VARCHAR(100) DEFAULT NULL COMMENT 'Denormalized snapshot',
    `action`      VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50)  DEFAULT NULL,
    `entity_id`   INT UNSIGNED DEFAULT NULL,
    `description` TEXT NOT NULL,
    `ip_address`  VARCHAR(45)  DEFAULT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_logs_user`    (`user_id`),
    INDEX `idx_logs_entity`  (`entity_type`, `entity_id`),
    INDEX `idx_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- Feature 6: Settings / Aturan Reservasi
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
    `key`         VARCHAR(100) NOT NULL,
    `value`       TEXT         DEFAULT NULL,
    `label`       VARCHAR(200) DEFAULT NULL,
    `description` TEXT         DEFAULT NULL,
    `type`        ENUM('text','number','time','boolean') NOT NULL DEFAULT 'text',
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`key`, `value`, `label`, `description`, `type`) VALUES
('max_duration_hours',  '8',     'Maks. Durasi Reservasi (Jam)',           'Batas maksimal durasi 1 sesi reservasi dalam jam. 0 = tidak dibatasi.',        'number'),
('max_advance_days',    '30',    'Maks. Pemesanan di Muka (Hari)',          'Berapa hari ke depan user boleh melakukan reservasi.',                          'number'),
('min_advance_hours',   '1',     'Min. Pengajuan Sebelum Mulai (Jam)',      'Reservasi harus diajukan setidaknya X jam sebelum waktu mulai.',                'number'),
('max_active_per_user', '5',     'Maks. Reservasi Aktif per User',          'Jumlah reservasi berstatus Pending/Approved yang boleh dimiliki 1 user. 0 = tidak dibatasi.', 'number'),
('booking_start_hour',  '06:00', 'Jam Paling Awal Pemesanan',              'Waktu mulai reservasi tidak boleh sebelum jam ini.',                           'time'),
('booking_end_hour',    '22:00', 'Jam Paling Akhir Pemesanan',             'Waktu selesai reservasi tidak boleh melewati jam ini.',                        'time')
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);

-- ----------------------------------------------------------------
-- Feature 9: Spesifikasi column for resources (MySQL 8 compatible)
-- ----------------------------------------------------------------
SET @prepStmt = (
  SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'resources' AND COLUMN_NAME = 'spesifikasi') > 0,
    'SELECT 1',
    'ALTER TABLE `resources` ADD COLUMN `spesifikasi` TEXT DEFAULT NULL AFTER `kapasitas`'
  )
);
PREPARE migrateStmt FROM @prepStmt;
EXECUTE migrateStmt;
DEALLOCATE PREPARE migrateStmt;

-- ----------------------------------------------------------------
-- Feature 10: Waitlist / Antrian
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `waitlist` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT UNSIGNED NOT NULL,
    `resource_id`   INT UNSIGNED NOT NULL,
    `waktu_mulai`   DATETIME NOT NULL,
    `waktu_selesai` DATETIME NOT NULL,
    `keperluan`     VARCHAR(255) NOT NULL,
    `keterangan`    TEXT DEFAULT NULL,
    `status`        ENUM('Waiting','Notified','Converted','Expired','Cancelled') NOT NULL DEFAULT 'Waiting',
    `notified_at`   DATETIME DEFAULT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_waitlist_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_waitlist_resource`
        FOREIGN KEY (`resource_id`) REFERENCES `resources`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_waitlist_user`     (`user_id`),
    INDEX `idx_waitlist_resource` (`resource_id`),
    INDEX `idx_waitlist_status`   (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
