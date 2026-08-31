-- Migration: Quản lý và thanh toán chi phí xăng dầu (XD) cho giáo viên
-- Idempotent - chạy nhiều lần an toàn

-- 1) Bảng cấu hình tham số thanh toán (key/value)
CREATE TABLE IF NOT EXISTS `table_xd_config` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `config_key` VARCHAR(100) NOT NULL,
  `config_value` VARCHAR(191) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_xd_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) Bảng đợt bảng kê quyết toán
CREATE TABLE IF NOT EXISTS `table_xd_bangke` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ngay_lap` DATE NULL,
  `ky` VARCHAR(100) NOT NULL DEFAULT '',
  `tong_hocvien` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `tong_tien` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `user_tao` VARCHAR(100) NOT NULL DEFAULT '',
  `ngaytao` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_xd_bangke_ky` (`ky`),
  KEY `idx_xd_bangke_ngay` (`ngay_lap`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) Bảng hóa đơn xăng dầu của giáo viên (giáo viên định danh theo TÊN: gv_hoten/gv_key)
CREATE TABLE IF NOT EXISTS `table_xd_hoadon` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `gv_cccd` VARCHAR(20) NOT NULL DEFAULT '',
  `gv_hoten` VARCHAR(255) NOT NULL DEFAULT '',
  `gv_key` VARCHAR(191) NOT NULL DEFAULT '',
  `ma_hoa_don` VARCHAR(191) NOT NULL,
  `thong_tin_ban_hang` VARCHAR(255) NOT NULL DEFAULT '',
  `chi_tiet` VARCHAR(50) NOT NULL DEFAULT '',
  `ngay_hoa_don` DATE NULL,
  `tong_tien` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `bien_so` VARCHAR(50) NOT NULL DEFAULT '',
  `ky` VARCHAR(100) NOT NULL DEFAULT '',
  `da_quyettoan` TINYINT(1) NOT NULL DEFAULT 0,
  `id_bangke` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `ngaytao` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `user_tao` VARCHAR(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_xd_hoadon_ma_ngay` (`ma_hoa_don`, `ngay_hoa_don`),
  KEY `idx_xd_hoadon_gvkey` (`gv_key`),
  KEY `idx_xd_hoadon_ky` (`ky`),
  KEY `idx_xd_hoadon_ngay` (`ngay_hoa_don`),
  KEY `idx_xd_hoadon_quyettoan` (`da_quyettoan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4) Bảng học viên xăng dầu
CREATE TABLE IF NOT EXISTS `table_xd_hocvien` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ho_ten` VARCHAR(255) NOT NULL DEFAULT '',
  `cccd` VARCHAR(20) NOT NULL,
  `ngaysinh` VARCHAR(20) NOT NULL DEFAULT '',
  `khoa` VARCHAR(100) NOT NULL DEFAULT '',
  `nhom` VARCHAR(10) NOT NULL DEFAULT 'BT',
  `nguoi_nop` VARCHAR(255) NOT NULL DEFAULT '',
  `gv_cccd` VARCHAR(20) NOT NULL DEFAULT '',
  `gv_hoten` VARCHAR(255) NOT NULL DEFAULT '',
  `gv_key` VARCHAR(191) NOT NULL DEFAULT '',
  `dinh_muc` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `so_tien_thanh_toan` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `ngay_thanh_toan` DATE NULL DEFAULT NULL,
  `id_bangke` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `ngaytao` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `user_tao` VARCHAR(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_xd_hocvien_cccd` (`cccd`),
  KEY `idx_xd_hocvien_gvkey` (`gv_key`),
  KEY `idx_xd_hocvien_ngaytt` (`ngay_thanh_toan`),
  KEY `idx_xd_hocvien_nhom` (`nhom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Giá trị định mức mặc định (chỉ chèn nếu chưa có)
INSERT INTO `table_xd_config` (`config_key`, `config_value`)
SELECT * FROM (SELECT 'xd_dinh_muc' AS k, '3500000' AS v) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `table_xd_config` WHERE `config_key` = 'xd_dinh_muc') LIMIT 1;

INSERT INTO `table_xd_config` (`config_key`, `config_value`)
SELECT * FROM (SELECT 'xd_muc_bt' AS k, '1200000' AS v) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `table_xd_config` WHERE `config_key` = 'xd_muc_bt') LIMIT 1;

INSERT INTO `table_xd_config` (`config_key`, `config_value`)
SELECT * FROM (SELECT 'xd_muc_ck' AS k, '0' AS v) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `table_xd_config` WHERE `config_key` = 'xd_muc_ck') LIMIT 1;

INSERT INTO `table_xd_config` (`config_key`, `config_value`)
SELECT * FROM (SELECT 'xd_muc_dat' AS k, '0' AS v) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `table_xd_config` WHERE `config_key` = 'xd_muc_dat') LIMIT 1;
