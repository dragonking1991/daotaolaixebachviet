-- Migration: Phân hệ Quản lý quá trình đào tạo học viên (từ khai giảng → kiểm tra)
-- Chạy SQL này nếu các bảng chưa tồn tại. Idempotent, an toàn cho MySQL 8.0 và MariaDB.
-- Không đụng tới table_product và table_cabin_* (phân hệ đăng ký lịch cabin) đang có.

-- Khóa học đào tạo (5 thông tin: hạng, khóa, mã khóa, ngày khai giảng, ngày mãn khóa)
CREATE TABLE IF NOT EXISTS table_dt_khoa (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ma_khoa VARCHAR(100) NOT NULL,
  ten_khoa VARCHAR(255) DEFAULT '',
  hang VARCHAR(20) DEFAULT '',
  ngay_khaigiang DATE NULL DEFAULT NULL,
  ngay_manhoa DATE NULL DEFAULT NULL,
  he_daotao VARCHAR(100) DEFAULT '',
  ngaytao INT DEFAULT 0,
  user_tao VARCHAR(255) DEFAULT '',
  stt INT DEFAULT 0,
  hienthi TINYINT DEFAULT 1,
  UNIQUE KEY uq_dt_khoa_ma (ma_khoa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Học viên: bảng cầu nối ánh xạ mã học viên (trung tâm) ⇄ CCCD, dùng chung cho mọi module
CREATE TABLE IF NOT EXISTS table_dt_hocvien (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_khoa INT NOT NULL DEFAULT 0,
  ma_hv VARCHAR(100) DEFAULT '',
  cccd VARCHAR(20) DEFAULT '',
  hoten VARCHAR(255) DEFAULT '',
  ngaysinh VARCHAR(20) DEFAULT '',
  hang VARCHAR(20) DEFAULT '',
  gv_key VARCHAR(255) DEFAULT '',
  gv_hoten VARCHAR(255) DEFAULT '',
  nguoi_gioithieu VARCHAR(255) DEFAULT '',
  he_daotao VARCHAR(100) DEFAULT '',
  ngaytao INT DEFAULT 0,
  UNIQUE KEY uq_dt_hocvien_cccd_khoa (id_khoa, cccd),
  KEY idx_dt_hocvien_mahv (ma_hv),
  KEY idx_dt_hocvien_cccd (cccd),
  KEY idx_dt_hocvien_gvkey (gv_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Xe tập lái
CREATE TABLE IF NOT EXISTS table_dt_xe (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bien_so VARCHAR(50) NOT NULL,
  hang_xe VARCHAR(20) DEFAULT '',
  hang_dt VARCHAR(50) DEFAULT '',
  so_dangky VARCHAR(100) DEFAULT '',
  so_khung VARCHAR(100) DEFAULT '',
  so_may VARCHAR(100) DEFAULT '',
  loai_xe VARCHAR(50) DEFAULT '',
  nhan_hieu VARCHAR(100) DEFAULT '',
  gv_key VARCHAR(255) DEFAULT '',
  gv_hoten VARCHAR(255) DEFAULT '',
  ngaytao INT DEFAULT 0,
  UNIQUE KEY uq_dt_xe_bienso (bien_so),
  KEY idx_dt_xe_hang (hang_xe)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Giáo viên (có CCCD -> dùng cho cổng đăng nhập giáo viên)
CREATE TABLE IF NOT EXISTS table_dt_giaovien (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cccd VARCHAR(20) NOT NULL,
  hoten VARCHAR(255) DEFAULT '',
  gv_key VARCHAR(255) DEFAULT '',
  ngaysinh VARCHAR(20) DEFAULT '',
  gioitinh VARCHAR(10) DEFAULT '',
  hang_gplx VARCHAR(50) DEFAULT '',
  hang_daotao_phep VARCHAR(50) DEFAULT '',
  sdt VARCHAR(50) DEFAULT '',
  dia_chi VARCHAR(500) DEFAULT '',
  matkhau VARCHAR(255) DEFAULT '',
  ngaytao INT DEFAULT 0,
  UNIQUE KEY uq_dt_gv_cccd (cccd),
  KEY idx_dt_gv_gvkey (gv_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Kết quả lý thuyết theo từng môn (6 môn). Join theo CCCD.
CREATE TABLE IF NOT EXISTS table_dt_lythuyet (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_khoa INT NOT NULL DEFAULT 0,
  cccd VARCHAR(20) DEFAULT '',
  mon VARCHAR(30) NOT NULL,
  tien_do DECIMAL(6,2) DEFAULT 0,
  diem_kt DECIMAL(6,2) DEFAULT 0,
  dat TINYINT DEFAULT 0,
  ngaytao INT DEFAULT 0,
  UNIQUE KEY uq_dt_lythuyet (id_khoa, cccd, mon),
  KEY idx_dt_lythuyet_cccd (cccd)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Kết quả cabin (import file cabin - 13C1). Join theo mã học viên.
CREATE TABLE IF NOT EXISTS table_dt_cabin_kq (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_khoa INT NOT NULL DEFAULT 0,
  ma_hv VARCHAR(100) DEFAULT '',
  cccd VARCHAR(20) DEFAULT '',
  tong_thoigian VARCHAR(50) DEFAULT '',
  so_noidung INT DEFAULT 0,
  dat TINYINT DEFAULT 0,
  ghi_chu VARCHAR(255) DEFAULT '',
  ngaytao INT DEFAULT 0,
  UNIQUE KEY uq_dt_cabin_kq (id_khoa, ma_hv),
  KEY idx_dt_cabin_kq_cccd (cccd)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Phiên học DAT (thực hành trên đường). Chống trùng theo mã phiên học.
CREATE TABLE IF NOT EXISTS table_dt_dat_phien (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ma_phien VARCHAR(100) NOT NULL,
  ma_hv VARCHAR(100) DEFAULT '',
  cccd VARCHAR(20) DEFAULT '',
  id_khoa INT NOT NULL DEFAULT 0,
  hang VARCHAR(20) DEFAULT '',
  tg_batdau DATETIME NULL DEFAULT NULL,
  tg_ketthuc DATETIME NULL DEFAULT NULL,
  gio_thuchanh DECIMAL(8,4) DEFAULT 0,
  km DECIMAL(10,3) DEFAULT 0,
  la_xe_tudong TINYINT DEFAULT 0,
  gio_dem DECIMAL(8,4) DEFAULT 0,
  bien_so VARCHAR(50) DEFAULT '',
  gv_hoten VARCHAR(255) DEFAULT '',
  trang_thai VARCHAR(50) DEFAULT '',
  ngay_hoc DATE NULL DEFAULT NULL,
  ngaytao INT DEFAULT 0,
  UNIQUE KEY uq_dt_dat_phien (ma_phien),
  KEY idx_dt_dat_mahv (ma_hv),
  KEY idx_dt_dat_khoa (id_khoa),
  KEY idx_dt_dat_ngay (ngay_hoc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Thực hành trong hình (nhập tay 2 tham số). Join theo CCCD.
CREATE TABLE IF NOT EXISTS table_dt_thuchanh_hinh (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_khoa INT NOT NULL DEFAULT 0,
  cccd VARCHAR(20) DEFAULT '',
  gio DECIMAL(8,2) DEFAULT 0,
  km DECIMAL(10,2) DEFAULT 0,
  nguoi_nhap VARCHAR(255) DEFAULT '',
  ngaytao INT DEFAULT 0,
  UNIQUE KEY uq_dt_hinh (id_khoa, cccd)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Nhật ký import (truy vết ai import gì, ngày nào, bao nhiêu dòng/lỗi)
CREATE TABLE IF NOT EXISTS table_dt_import_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  module VARCHAR(50) DEFAULT '',
  filename VARCHAR(500) DEFAULT '',
  so_dong INT DEFAULT 0,
  so_loi INT DEFAULT 0,
  user VARCHAR(255) DEFAULT '',
  ngaytao INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
