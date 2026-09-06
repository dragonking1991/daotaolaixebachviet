<?php
if(!defined('SOURCES')) die("Error");

/* ============================ Bảng dữ liệu ============================ */

function xd_ensure_tables()
{
	global $d;

	$d->rawQuery(
		"CREATE TABLE IF NOT EXISTS #_xd_config (\n"
		. " id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,\n"
		. " config_key VARCHAR(100) NOT NULL,\n"
		. " config_value VARCHAR(191) NOT NULL DEFAULT '',\n"
		. " PRIMARY KEY (id),\n"
		. " UNIQUE KEY uniq_xd_config_key (config_key)\n"
		. ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
	);

	$d->rawQuery(
		"CREATE TABLE IF NOT EXISTS #_xd_bangke (\n"
		. " id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,\n"
		. " ngay_lap DATE NULL,\n"
		. " ky VARCHAR(100) NOT NULL DEFAULT '',\n"
		. " tong_hocvien INT(10) UNSIGNED NOT NULL DEFAULT 0,\n"
		. " tong_tien DECIMAL(18,2) NOT NULL DEFAULT 0,\n"
		. " user_tao VARCHAR(100) NOT NULL DEFAULT '',\n"
		. " ngaytao INT(11) UNSIGNED NOT NULL DEFAULT 0,\n"
		. " PRIMARY KEY (id),\n"
		. " KEY idx_xd_bangke_ky (ky)\n"
		. ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
	);

	$d->rawQuery(
		"CREATE TABLE IF NOT EXISTS #_xd_hoadon (\n"
		. " id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,\n"
		. " gv_cccd VARCHAR(20) NOT NULL DEFAULT '',\n"
		. " gv_hoten VARCHAR(255) NOT NULL DEFAULT '',\n"
		. " ma_hoa_don VARCHAR(191) NOT NULL,\n"
		. " ngay_hoa_don DATE NULL,\n"
		. " tong_tien DECIMAL(18,2) NOT NULL DEFAULT 0,\n"
		. " ky VARCHAR(100) NOT NULL DEFAULT '',\n"
		. " da_quyettoan TINYINT(1) NOT NULL DEFAULT 0,\n"
		. " id_bangke INT(10) UNSIGNED NOT NULL DEFAULT 0,\n"
		. " ngaytao INT(11) UNSIGNED NOT NULL DEFAULT 0,\n"
		. " user_tao VARCHAR(100) NOT NULL DEFAULT '',\n"
		. " PRIMARY KEY (id),\n"
		. " UNIQUE KEY uniq_xd_hoadon_ma_ngay_gv (ma_hoa_don, ngay_hoa_don, gv_key),\n"
		. " KEY idx_xd_hoadon_gv (gv_cccd),\n"
		. " KEY idx_xd_hoadon_ky (ky),\n"
		. " KEY idx_xd_hoadon_ngay (ngay_hoa_don),\n"
		. " KEY idx_xd_hoadon_quyettoan (da_quyettoan)\n"
		. ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
	);

	$d->rawQuery(
		"CREATE TABLE IF NOT EXISTS #_xd_hocvien (\n"
		. " id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,\n"
		. " ho_ten VARCHAR(255) NOT NULL DEFAULT '',\n"
		. " cccd VARCHAR(20) NOT NULL,\n"
		. " ngaysinh VARCHAR(20) NOT NULL DEFAULT '',\n"
		. " nhom VARCHAR(10) NOT NULL DEFAULT 'BT',\n"
		. " gv_cccd VARCHAR(20) NOT NULL DEFAULT '',\n"
		. " gv_hoten VARCHAR(255) NOT NULL DEFAULT '',\n"
		. " dinh_muc DECIMAL(18,2) NOT NULL DEFAULT 0,\n"
		. " so_tien_thanh_toan DECIMAL(18,2) NOT NULL DEFAULT 0,\n"
		. " ngay_thanh_toan DATE NULL DEFAULT NULL,\n"
		. " id_bangke INT(10) UNSIGNED NOT NULL DEFAULT 0,\n"
		. " ngaytao INT(11) UNSIGNED NOT NULL DEFAULT 0,\n"
		. " user_tao VARCHAR(100) NOT NULL DEFAULT '',\n"
		. " PRIMARY KEY (id),\n"
		. " UNIQUE KEY uniq_xd_hocvien_cccd (cccd),\n"
		. " KEY idx_xd_hocvien_gv (gv_cccd),\n"
		. " KEY idx_xd_hocvien_ngaytt (ngay_thanh_toan),\n"
		. " KEY idx_xd_hocvien_nhom (nhom)\n"
		. ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
	);

	// Bổ sung cột cho mô hình dữ liệu thực tế (idempotent) — GV liên kết theo TÊN (gv_key)
	xd_ensure_column('xd_hoadon', 'gv_key', "ADD COLUMN gv_key VARCHAR(191) NOT NULL DEFAULT '' AFTER gv_hoten");
	xd_ensure_column('xd_hoadon', 'bien_so', "ADD COLUMN bien_so VARCHAR(50) NOT NULL DEFAULT '' AFTER tong_tien");
	xd_ensure_column('xd_hoadon', 'thong_tin_ban_hang', "ADD COLUMN thong_tin_ban_hang VARCHAR(255) NOT NULL DEFAULT '' AFTER ma_hoa_don");
	xd_ensure_column('xd_hoadon', 'chi_tiet', "ADD COLUMN chi_tiet VARCHAR(50) NOT NULL DEFAULT '' AFTER thong_tin_ban_hang");
	xd_ensure_column('xd_hoadon', 'ke_toan_kiem_tra', "ADD COLUMN ke_toan_kiem_tra TINYINT(1) NOT NULL DEFAULT 0 AFTER da_quyettoan");
	xd_ensure_column('xd_hoadon', 'quan_ly_duyet', "ADD COLUMN quan_ly_duyet TINYINT(1) NOT NULL DEFAULT 0 AFTER ke_toan_kiem_tra");
	xd_ensure_index('xd_hoadon', 'idx_xd_hoadon_gvkey', "ADD KEY idx_xd_hoadon_gvkey (gv_key)");
	xd_ensure_invoice_unique_key();

	xd_ensure_column('xd_hocvien', 'gv_key', "ADD COLUMN gv_key VARCHAR(191) NOT NULL DEFAULT '' AFTER gv_hoten");
	xd_ensure_column('xd_hocvien', 'khoa', "ADD COLUMN khoa VARCHAR(100) NOT NULL DEFAULT '' AFTER ngaysinh");
	xd_ensure_column('xd_hocvien', 'nguoi_nop', "ADD COLUMN nguoi_nop VARCHAR(255) NOT NULL DEFAULT '' AFTER nhom");
	xd_ensure_column('xd_hocvien', 'ke_toan_kiem_tra', "ADD COLUMN ke_toan_kiem_tra TINYINT(1) NOT NULL DEFAULT 0 AFTER ngay_thanh_toan");
	xd_ensure_column('xd_hocvien', 'quan_ly_duyet', "ADD COLUMN quan_ly_duyet TINYINT(1) NOT NULL DEFAULT 0 AFTER ke_toan_kiem_tra");
	xd_ensure_index('xd_hocvien', 'idx_xd_hocvien_gvkey', "ADD KEY idx_xd_hocvien_gvkey (gv_key)");
}

function xd_ensure_column($table, $column, $alterAdd)
{
	global $d;
	$has = $d->rawQueryOne(
		"select count(*) as total from information_schema.columns where table_schema = database() and table_name = ? and column_name = ?",
		array('table_'.$table, $column)
	);
	if(empty($has) || (int)$has['total'] <= 0)
	{
		$d->rawQuery("ALTER TABLE #_$table $alterAdd");
	}
}

function xd_ensure_index($table, $indexName, $alterAdd)
{
	global $d;
	$has = $d->rawQueryOne(
		"select count(*) as total from information_schema.statistics where table_schema = database() and table_name = ? and index_name = ?",
		array('table_'.$table, $indexName)
	);
	if(empty($has) || (int)$has['total'] <= 0)
	{
		$d->rawQuery("ALTER TABLE #_$table $alterAdd");
	}
}

function xd_ensure_invoice_unique_key()
{
	global $d;
	$index = $d->rawQueryOne(
		"select index_name, group_concat(column_name order by seq_in_index separator ',') as columns_list
		 from information_schema.statistics
		 where table_schema = database() and table_name = 'table_xd_hoadon' and index_name in ('uniq_xd_hoadon_ma_ngay', 'uniq_xd_hoadon_ma_ngay_gv')
		 group by index_name order by index_name = 'uniq_xd_hoadon_ma_ngay_gv' desc limit 0,1",
		array()
	);
	if(!$index || $index['index_name'] !== 'uniq_xd_hoadon_ma_ngay_gv' || $index['columns_list'] !== 'ma_hoa_don,ngay_hoa_don,gv_key')
	{
		$d->rawQuery("alter table #_xd_hoadon drop index if exists uniq_xd_hoadon_ma_ngay, add unique key uniq_xd_hoadon_ma_ngay_gv (ma_hoa_don, ngay_hoa_don, gv_key)");
	}
}
