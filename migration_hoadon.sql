-- Tạo bảng quản lý hóa đơn cho admin
CREATE TABLE IF NOT EXISTS `table_hoadon` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ma_so_hoa_don` VARCHAR(191) NOT NULL,
  `ho_ten_nguoi_mua` VARCHAR(255) NOT NULL DEFAULT '',
  `chi_tiet_hoa_don` TEXT NULL,
  `loai_hoa_don` VARCHAR(20) NOT NULL DEFAULT '',
  `ngay_hoa_don` DATE NULL,
  `tong_tien` DECIMAL(18,2) NULL DEFAULT NULL,
  `ngaytao` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `ngaysua` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `user_tao` VARCHAR(100) NULL DEFAULT '',
  `user_sua` VARCHAR(100) NULL DEFAULT '',
  `thong_tin_hoa_don` LONGTEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ma_so_hoa_don` (`ma_so_hoa_don`),
  KEY `idx_loai_hoa_don` (`loai_hoa_don`),
  KEY `idx_ngay_hoa_don` (`ngay_hoa_don`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
