-- ============================================================
-- DATABASE SCHEMA: Sistem Reservasi Studio & Alat Siaran TVRI
-- Engine: MySQL / MariaDB
-- Charset: utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS `reservasi_tvri`
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `reservasi_tvri`;

-- ============================================================
-- TABEL 1: users (RBAC: Admin & Staff)
-- ============================================================
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL COMMENT 'Hashed with password_hash()',
    `role` ENUM('Admin', 'Staff') NOT NULL DEFAULT 'Staff',
    `jabatan` VARCHAR(100) DEFAULT NULL COMMENT 'Jabatan di TVRI',
    `no_telp` VARCHAR(20) DEFAULT NULL,
    `foto` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL 2: resources (Aset: Studio & Alat)
-- ============================================================
CREATE TABLE `resources` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nama` VARCHAR(150) NOT NULL,
    `tipe` ENUM('Studio', 'Alat') NOT NULL,
    `deskripsi` TEXT DEFAULT NULL,
    `lokasi` VARCHAR(150) DEFAULT NULL COMMENT 'Lokasi fisik aset',
    `kapasitas` INT UNSIGNED DEFAULT NULL COMMENT 'Kapasitas studio (orang)',
    `foto` VARCHAR(255) DEFAULT NULL,
    `is_available` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Tersedia, 0=Maintenance',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_resources_tipe` (`tipe`),
    INDEX `idx_resources_available` (`is_available`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL 3: reservations (Transaksi Reservasi)
-- ============================================================
CREATE TABLE `reservations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `resource_id` INT UNSIGNED NOT NULL,
    `keperluan` VARCHAR(255) NOT NULL COMMENT 'Tujuan peminjaman',
    `keterangan` TEXT DEFAULT NULL COMMENT 'Detail tambahan',
    `waktu_mulai` DATETIME NOT NULL,
    `waktu_selesai` DATETIME NOT NULL,
    `status` ENUM('Pending', 'Approved', 'Rejected', 'Cancelled', 'Selesai') NOT NULL DEFAULT 'Pending',
    `catatan_admin` TEXT DEFAULT NULL COMMENT 'Alasan approve/reject',
    `approved_by` INT UNSIGNED DEFAULT NULL COMMENT 'Admin yang approve/reject',
    `approved_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Keys
    CONSTRAINT `fk_reservations_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `fk_reservations_resource`
        FOREIGN KEY (`resource_id`) REFERENCES `resources`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `fk_reservations_admin`
        FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    -- Indexes untuk performa query collision detection
    INDEX `idx_reservations_resource_time` (`resource_id`, `waktu_mulai`, `waktu_selesai`),
    INDEX `idx_reservations_status` (`status`),
    INDEX `idx_reservations_user` (`user_id`),

    -- Constraint: waktu_selesai harus setelah waktu_mulai
    CONSTRAINT `chk_waktu_valid` CHECK (`waktu_selesai` > `waktu_mulai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATA SEED: Admin default & Sample Resources
-- ============================================================

-- Password: Admin123! (hashed with password_hash)
INSERT INTO `users` (`nama_lengkap`, `username`, `password`, `role`, `jabatan`) VALUES
('Administrator TVRI', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'System Administrator'),
('Budi Santoso', 'budi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'Produser'),
('Siti Rahayu', 'siti', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'Kameramen');

INSERT INTO `resources` (`nama`, `tipe`, `deskripsi`, `lokasi`, `kapasitas`) VALUES
('Studio 1 - Utama', 'Studio', 'Studio utama untuk siaran langsung berita nasional. Dilengkapi green screen dan lighting profesional.', 'Gedung A Lantai 1', 50),
('Studio 2 - Mini', 'Studio', 'Studio mini untuk talkshow dan wawancara. Setup intimate dengan 2 set sofa.', 'Gedung A Lantai 2', 20),
('Studio 3 - Podcast', 'Studio', 'Studio podcast dengan acoustic treatment. Cocok untuk recording audio dan video podcast.', 'Gedung B Lantai 1', 8),
('Kamera Sony PXW-Z280', 'Alat', 'Kamera broadcast 4K HDR dengan 3-chip sensor. Termasuk tripod dan carrying case.', 'Gudang Peralatan A', NULL),
('Kamera Canon XF705', 'Alat', 'Camcorder profesional 4K UHD dengan dual-pixel CMOS AF.', 'Gudang Peralatan A', NULL),
('Mic Wireless Sennheiser EW 100', 'Alat', 'Sistem mic wireless UHF dengan handheld transmitter. Range 100m.', 'Gudang Peralatan B', NULL),
('Mic Boom Rode NTG5', 'Alat', 'Shotgun microphone broadcast-grade. Termasuk boom pole dan windshield.', 'Gudang Peralatan B', NULL),
('Lighting Kit Aputure 600D Pro', 'Alat', 'LED daylight fixture 600W dengan Bowens mount. Termasuk light dome dan stand.', 'Gudang Peralatan C', NULL),
('Teleprompter Datavideo TP-900', 'Alat', 'Teleprompter 19 inch untuk studio broadcast. Termasuk software controller.', 'Gudang Peralatan A', NULL);
