<?php
if(!defined('SOURCES')) die("Error");

/* Tạo bảng phân hệ đào tạo nếu chưa có (idempotent). Gọi trước mọi thao tác đọc/ghi. */
function dt_ensure_tables()
{
	global $d;
	static $done = false;
	if($done) return;

	$d->rawQuery("CREATE TABLE IF NOT EXISTS table_dt_khoa (
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
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$d->rawQuery("CREATE TABLE IF NOT EXISTS table_dt_hocvien (
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
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$d->rawQuery("CREATE TABLE IF NOT EXISTS table_dt_xe (
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
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$d->rawQuery("CREATE TABLE IF NOT EXISTS table_dt_giaovien (
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
		ma_csdt VARCHAR(50) DEFAULT '',
		so_gplx VARCHAR(50) DEFAULT '',
		ngay_cap_gplx VARCHAR(20) DEFAULT '',
		ngay_hh_gplx VARCHAR(20) DEFAULT '',
		noi_ct VARCHAR(255) DEFAULT '',
		hinh_thuc_td VARCHAR(50) DEFAULT '',
		loai_hinh_dt VARCHAR(50) DEFAULT '',
		so_qd_gcn VARCHAR(100) DEFAULT '',
		ngay_qd_gcn VARCHAR(20) DEFAULT '',
		noi_cap_gcn VARCHAR(255) DEFAULT '',
		trinh_do VARCHAR(100) DEFAULT '',
		chuyen_mon VARCHAR(255) DEFAULT '',
		su_pham VARCHAR(100) DEFAULT '',
		tuyen_dung VARCHAR(50) DEFAULT '',
		ghi_chu VARCHAR(500) DEFAULT '',
		matkhau VARCHAR(255) DEFAULT '',
		ngaytao INT DEFAULT 0,
		UNIQUE KEY uq_dt_gv_cccd (cccd),
		KEY idx_dt_gv_gvkey (gv_key)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$d->rawQuery("CREATE TABLE IF NOT EXISTS table_dt_lythuyet (
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
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$d->rawQuery("CREATE TABLE IF NOT EXISTS table_dt_mophong (
		id INT AUTO_INCREMENT PRIMARY KEY,
		id_khoa INT NOT NULL DEFAULT 0,
		cccd VARCHAR(20) DEFAULT '',
		tien_do DECIMAL(6,2) DEFAULT 0,
		dat TINYINT DEFAULT 0,
		ngaytao INT DEFAULT 0,
		UNIQUE KEY uq_dt_mophong (id_khoa, cccd),
		KEY idx_dt_mophong_cccd (cccd)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$d->rawQuery("CREATE TABLE IF NOT EXISTS table_dt_cabin_kq (
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
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$d->rawQuery("CREATE TABLE IF NOT EXISTS table_dt_dat_phien (
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
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$d->rawQuery("CREATE TABLE IF NOT EXISTS table_dt_dat_override (
		id INT AUTO_INCREMENT PRIMARY KEY,
		id_khoa INT NOT NULL DEFAULT 0,
		cccd VARCHAR(20) DEFAULT '',
		dat TINYINT NOT NULL DEFAULT 1,
		nguoi_nhap VARCHAR(100) DEFAULT '',
		ngaytao INT DEFAULT 0,
		UNIQUE KEY uq_dt_dat_override (id_khoa, cccd)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$d->rawQuery("CREATE TABLE IF NOT EXISTS table_dt_thuchanh_hinh (
		id INT AUTO_INCREMENT PRIMARY KEY,
		id_khoa INT NOT NULL DEFAULT 0,
		cccd VARCHAR(20) DEFAULT '',
		gio DECIMAL(8,2) DEFAULT 0,
		km DECIMAL(10,2) DEFAULT 0,
		dat_override TINYINT NULL DEFAULT NULL,
		nguoi_nhap VARCHAR(255) DEFAULT '',
		ngaytao INT DEFAULT 0,
		UNIQUE KEY uq_dt_hinh (id_khoa, cccd)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$d->rawQuery("CREATE TABLE IF NOT EXISTS table_dt_import_log (
		id INT AUTO_INCREMENT PRIMARY KEY,
		module VARCHAR(50) DEFAULT '',
		filename VARCHAR(500) DEFAULT '',
		so_dong INT DEFAULT 0,
		so_loi INT DEFAULT 0,
		user VARCHAR(255) DEFAULT '',
		ngaytao INT DEFAULT 0
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$d->rawQuery("CREATE TABLE IF NOT EXISTS table_dt_ky_thi (
		id INT AUTO_INCREMENT PRIMARY KEY,
		ten VARCHAR(255) DEFAULT '',
		ngay_kt DATE NULL DEFAULT NULL,
		ghi_chu VARCHAR(500) DEFAULT '',
		user_tao VARCHAR(255) DEFAULT '',
		ngaytao INT DEFAULT 0,
		KEY idx_dt_kythi_ngay (ngay_kt)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$d->rawQuery("CREATE TABLE IF NOT EXISTS table_dt_ky_thi_hv (
		id INT AUTO_INCREMENT PRIMARY KEY,
		id_ky INT NOT NULL DEFAULT 0,
		id_khoa INT NOT NULL DEFAULT 0,
		cccd VARCHAR(20) DEFAULT '',
		ma_hv VARCHAR(100) DEFAULT '',
		hoten VARCHAR(255) DEFAULT '',
		nam_sinh VARCHAR(20) DEFAULT '',
		hang VARCHAR(20) DEFAULT '',
		ma_khoa VARCHAR(100) DEFAULT '',
		ten_khoa VARCHAR(255) DEFAULT '',
		giaovien VARCHAR(255) DEFAULT '',
		diem_lt VARCHAR(20) DEFAULT '',
		lt VARCHAR(20) DEFAULT '',
		diem_mp VARCHAR(20) DEFAULT '',
		mp VARCHAR(20) DEFAULT '',
		ket_qua_tong VARCHAR(30) DEFAULT '',
		diem_hinh VARCHAR(20) DEFAULT '',
		hinh VARCHAR(20) DEFAULT '',
		diem_duong VARCHAR(20) DEFAULT '',
		duong VARCHAR(20) DEFAULT '',
		chu_chi VARCHAR(20) DEFAULT '',
		diem_chu_chi VARCHAR(20) DEFAULT '',
		ket_luan VARCHAR(30) DEFAULT '',
		co_ket_qua TINYINT DEFAULT 0,
		ngaytao INT DEFAULT 0,
		UNIQUE KEY uq_dt_kythi_hv (id_ky, cccd),
		KEY idx_dt_kythi_hv_cccd (cccd)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$d->rawQuery("CREATE TABLE IF NOT EXISTS table_dt_htkh (
		id INT AUTO_INCREMENT PRIMARY KEY,
		id_khoa INT NOT NULL DEFAULT 0,
		cccd VARCHAR(20) DEFAULT '',
		ma_hv VARCHAR(100) DEFAULT '',
		so_giay VARCHAR(100) DEFAULT '',
		ngay_cap VARCHAR(20) DEFAULT '',
		ghi_chu VARCHAR(255) DEFAULT '',
		nguoi_nhap VARCHAR(255) DEFAULT '',
		ngaytao INT DEFAULT 0,
		UNIQUE KEY uq_dt_htkh (id_khoa, cccd),
		KEY idx_dt_htkh_cccd (cccd)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$d->rawQuery("CREATE TABLE IF NOT EXISTS table_dt_sat_hach (
		id INT AUTO_INCREMENT PRIMARY KEY,
		id_khoa INT NOT NULL DEFAULT 0,
		cccd VARCHAR(20) DEFAULT '',
		ma_hv VARCHAR(100) DEFAULT '',
		hoten VARCHAR(255) DEFAULT '',
		nam_sinh VARCHAR(20) DEFAULT '',
		hang VARCHAR(20) DEFAULT '',
		ma_khoa VARCHAR(100) DEFAULT '',
		ten_khoa VARCHAR(255) DEFAULT '',
		ngay_sh VARCHAR(20) DEFAULT '',
		so_gplx VARCHAR(50) DEFAULT '',
		ket_qua VARCHAR(30) DEFAULT '',
		ghi_chu VARCHAR(255) DEFAULT '',
		nguoi_nhap VARCHAR(255) DEFAULT '',
		ngaytao INT DEFAULT 0,
		UNIQUE KEY uq_dt_sat_hach (id_khoa, cccd),
		KEY idx_dt_sat_hach_cccd (cccd)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$d->rawQuery("CREATE TABLE IF NOT EXISTS table_dt_audit (
		id INT AUTO_INCREMENT PRIMARY KEY,
		action VARCHAR(50) DEFAULT '',
		entity VARCHAR(50) DEFAULT '',
		affected INT DEFAULT 0,
		detail VARCHAR(1000) DEFAULT '',
		user VARCHAR(255) DEFAULT '',
		ip VARCHAR(50) DEFAULT '',
		ngaytao INT DEFAULT 0,
		KEY idx_dt_audit_ngay (ngaytao)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$d->rawQuery("CREATE TABLE IF NOT EXISTS table_dt_migration (
		id INT AUTO_INCREMENT PRIMARY KEY,
		mkey VARCHAR(100) NOT NULL,
		ngaytao INT DEFAULT 0,
		UNIQUE KEY uq_dt_migration_key (mkey)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	if(function_exists('dt_migration_applied') && !dt_migration_applied('gv_ext_cols_v1')) { dt_ensure_gv_columns(); dt_migration_mark('gv_ext_cols_v1'); }
	if(function_exists('dt_migration_applied') && !dt_migration_applied('hinh_override_v1')) { dt_ensure_hinh_override(); dt_migration_mark('hinh_override_v1'); }
	if(function_exists('dt_migration_applied') && !dt_migration_applied('xe_gv_lifecycle_v1')) { dt_ensure_lifecycle_cols(); dt_migration_mark('xe_gv_lifecycle_v1'); }
	if(function_exists('dt_migration_applied') && !dt_migration_applied('gv_commitment_v1')) { dt_ensure_gv_commitment_cols(); dt_migration_mark('gv_commitment_v1'); }
	if(function_exists('dt_migration_applied') && !dt_migration_applied('xe_ghichu_v1')) { dt_ensure_xe_ghichu(); dt_migration_mark('xe_ghichu_v1'); }

	$done = true;
}

/* Cột ghi chú cho xe tập lái (nhập tay trong mục chỉnh sửa). */
function dt_ensure_xe_ghichu()
{
	global $d;
	$exist = $d->rawQueryOne("select column_name from information_schema.columns where table_schema = database() and table_name = 'table_dt_xe' and column_name = 'ghi_chu' limit 0,1");
	if(!$exist) $d->rawQuery("ALTER TABLE table_dt_xe ADD COLUMN ghi_chu VARCHAR(500) DEFAULT ''");
}

/* Cam kết gắn bó của giáo viên: số năm + ngày bắt đầu (để tính thời hạn còn lại). */
function dt_ensure_gv_commitment_cols()
{
	global $d;
	$cols = array(
		'cam_ket_nam' => "INT DEFAULT 0",
		'cam_ket_tu_ngay' => "VARCHAR(20) DEFAULT ''",
	);
	foreach($cols as $col => $def)
	{
		$exist = $d->rawQueryOne("select column_name from information_schema.columns where table_schema = database() and table_name = 'table_dt_giaovien' and column_name = ? limit 0,1", array($col));
		if(!$exist) $d->rawQuery("ALTER TABLE table_dt_giaovien ADD COLUMN `$col` $def");
	}
}

/* Thêm cột vòng đời (thanh lý) + cam kết cho xe và giáo viên nếu bảng cũ chưa có. */
function dt_ensure_lifecycle_cols()
{
	global $d;
	$xe = array(
		'ngay_hethan' => "VARCHAR(20) DEFAULT ''",
		'cam_ket' => "VARCHAR(500) DEFAULT ''",
		'thanh_ly' => "TINYINT DEFAULT 0",
		'ngay_thanh_ly' => "VARCHAR(20) DEFAULT ''",
	);
	foreach($xe as $col => $def)
	{
		$exist = $d->rawQueryOne("select column_name from information_schema.columns where table_schema = database() and table_name = 'table_dt_xe' and column_name = ? limit 0,1", array($col));
		if(!$exist) $d->rawQuery("ALTER TABLE table_dt_xe ADD COLUMN `$col` $def");
	}
	$gv = array(
		'thanh_ly' => "TINYINT DEFAULT 0",
		'ngay_thanh_ly' => "VARCHAR(20) DEFAULT ''",
	);
	foreach($gv as $col => $def)
	{
		$exist = $d->rawQueryOne("select column_name from information_schema.columns where table_schema = database() and table_name = 'table_dt_giaovien' and column_name = ? limit 0,1", array($col));
		if(!$exist) $d->rawQuery("ALTER TABLE table_dt_giaovien ADD COLUMN `$col` $def");
	}
}

/* Thêm cột mở rộng cho bảng giáo viên nếu bảng cũ chưa có (an toàn, idempotent). */
function dt_ensure_hinh_override()
{
	global $d;
	$exist = $d->rawQueryOne("select column_name from information_schema.columns where table_schema = database() and table_name = 'table_dt_thuchanh_hinh' and column_name = 'dat_override' limit 0,1");
	if(!$exist) $d->rawQuery("ALTER TABLE table_dt_thuchanh_hinh ADD COLUMN dat_override TINYINT NULL DEFAULT NULL AFTER km");
}

function dt_ensure_gv_columns()
{
	global $d;
	$cols = array(
		'ma_csdt' => "VARCHAR(50) DEFAULT ''",
		'so_gplx' => "VARCHAR(50) DEFAULT ''",
		'ngay_cap_gplx' => "VARCHAR(20) DEFAULT ''",
		'ngay_hh_gplx' => "VARCHAR(20) DEFAULT ''",
		'noi_ct' => "VARCHAR(255) DEFAULT ''",
		'hinh_thuc_td' => "VARCHAR(50) DEFAULT ''",
		'loai_hinh_dt' => "VARCHAR(50) DEFAULT ''",
		'so_qd_gcn' => "VARCHAR(100) DEFAULT ''",
		'ngay_qd_gcn' => "VARCHAR(20) DEFAULT ''",
		'noi_cap_gcn' => "VARCHAR(255) DEFAULT ''",
		'trinh_do' => "VARCHAR(100) DEFAULT ''",
		'chuyen_mon' => "VARCHAR(255) DEFAULT ''",
		'su_pham' => "VARCHAR(100) DEFAULT ''",
		'tuyen_dung' => "VARCHAR(50) DEFAULT ''",
		'ghi_chu' => "VARCHAR(500) DEFAULT ''",
	);
	foreach($cols as $col => $def)
	{
		$exist = $d->rawQueryOne("select column_name from information_schema.columns where table_schema = database() and table_name = 'table_dt_giaovien' and column_name = ? limit 0,1", array($col));
		if(!$exist) $d->rawQuery("ALTER TABLE table_dt_giaovien ADD COLUMN `$col` $def");
	}
}

/* Ghi một dòng nhật ký import. */
function dt_log_import($module, $filename, $soDong, $soLoi)
{
	global $d;
	$d->insert('dt_import_log', array(
		'module' => (string)$module,
		'filename' => (string)$filename,
		'so_dong' => (int)$soDong,
		'so_loi' => (int)$soLoi,
		'user' => dt_username(),
		'ngaytao' => time(),
	));
}
