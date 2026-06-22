-- Migration: Đăng ký lịch học cabin
-- Chạy SQL này nếu các bảng chưa tồn tại

-- Bảng khóa học cabin
CREATE TABLE IF NOT EXISTS table_cabin_khoahoc (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ten VARCHAR(255) NOT NULL,
  ngay_batdau DATE NOT NULL,
  ngay_ketthuc DATE NOT NULL,
  suc_chua_ca INT DEFAULT 3,
  han_dangky DATETIME NULL DEFAULT NULL,
  ngaytao INT DEFAULT 0,
  user_tao VARCHAR(255) DEFAULT '',
  stt INT DEFAULT 0,
  hienthi TINYINT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng đăng ký ca học cabin (mỗi dòng = 1 học viên đặt 1 ca)
CREATE TABLE IF NOT EXISTS table_cabin_dangky (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_khoahoc INT NOT NULL DEFAULT 0,
  id_hocvien INT NOT NULL DEFAULT 0,
  cccd VARCHAR(20) DEFAULT '',
  ngay_hoc DATE NOT NULL,
  ca INT NOT NULL DEFAULT 0,
  gio_b_d VARCHAR(10) DEFAULT '',
  gio_kt VARCHAR(10) DEFAULT '',
  trang_thai TINYINT DEFAULT 1,
  ngaytao INT DEFAULT 0,
  UNIQUE KEY uq_cabin_dangky (id_khoahoc, id_hocvien, ngay_hoc, ca),
  KEY idx_cabin_slot (id_khoahoc, ngay_hoc, ca)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Liên kết học viên (table_product type='cabin') với khóa học cabin
-- Idempotent cho cả MySQL 8.0 (không hỗ trợ ADD COLUMN IF NOT EXISTS) và MariaDB
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'table_product'
    AND COLUMN_NAME = 'id_cabin_khoahoc'
);
SET @ddl := IF(@col_exists = 0,
  'ALTER TABLE table_product ADD COLUMN id_cabin_khoahoc INT DEFAULT 0',
  'SELECT 1'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Hạn đăng ký lịch học cabin cho mỗi khóa (sau thời điểm này không cho học viên tự đăng ký).
-- NULL = không giới hạn riêng (chỉ chốt theo ngay_ketthuc).
SET @col_han := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'table_cabin_khoahoc'
    AND COLUMN_NAME = 'han_dangky'
);
SET @ddl_han := IF(@col_han = 0,
  'ALTER TABLE table_cabin_khoahoc ADD COLUMN han_dangky DATETIME NULL DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @ddl_han;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
