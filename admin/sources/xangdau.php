<?php
if(!defined('SOURCES')) die("Error");

require_once LIBRARIES.'xangdau_config.php';

/* ============================================================
 * Bộ đọc XLSX dạng STREAM (bộ nhớ thấp) — dùng cho file .xlsb đã chuyển đổi.
 * File chuyển đổi từ LibreOffice có thể khai báo sheet tới 1.048.576 dòng khiến
 * PHPExcel (nạp cả DOM) cạn bộ nhớ. Đọc theo luồng bằng XMLReader để tránh OOM.
 * ============================================================ */
if(!class_exists('XdCell'))
{
	class XdCell
	{
		private $v;
		public function __construct($v){ $this->v = $v; }
		public function getValue(){ return $this->v; }
		public function getCalculatedValue(){ return $this->v; }
		public function getOldCalculatedValue(){ return $this->v; }
		public function getFormattedValue(){ return ($this->v === null) ? '' : (string)$this->v; }
	}
}
if(!class_exists('XdArraySheet'))
{
	class XdArraySheet
	{
		private $rows; private $maxRow; private $maxCol;
		public function __construct($rows, $maxRow, $maxCol){ $this->rows = $rows; $this->maxRow = (int)$maxRow; $this->maxCol = (int)$maxCol; }
		public function getCellByColumnAndRow($col, $row)
		{
			$v = isset($this->rows[$row][$col]) ? $this->rows[$row][$col] : '';
			return new XdCell($v);
		}
		public function getHighestRow(){ return max(1, $this->maxRow); }
		public function getHighestColumn(){ return PHPExcel_Cell::stringFromColumnIndex(max(0, $this->maxCol - 1)); }
		public function getTitle(){ return 'sheet'; }
	}
}

switch($act)
{
	// ---- Cấu hình định mức ----
	case "config":
		if(xd_permission_denied()) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_get_config();
		$template = "xangdau/config/item_edit";
		break;
	case "saveConfig":
		if(xd_permission_denied()) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_save_config();
		break;

	// ---- Hóa đơn XD ----
	case "hoadon":
		if(xd_permission_denied()) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_get_hoadon();
		$template = "xangdau/hoadon/items";
		break;
	case "uploadHoadon":
		if(xd_permission_denied()) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		$template = "xangdau/uploadHoadon/items";
		break;
	case "uploadHoadonExcel":
		if(xd_permission_denied()) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_upload_hoadon_excel();
		break;
	case "deleteHoadon":
		if(xd_permission_denied()) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_delete_hoadon();
		break;
	case "deleteAllHoadon":
		if(xd_permission_denied()) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_delete_all_hoadon();
		break;

	// ---- Học viên XD ----
	case "hocvien":
		if(xd_permission_denied()) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_get_hocvien();
		$template = "xangdau/hocvien/items";
		break;
	case "uploadHocvien":
		if(xd_permission_denied()) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		$template = "xangdau/uploadHocvien/items";
		break;
	case "uploadHocvienExcel":
		if(xd_permission_denied()) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_upload_hocvien_excel();
		break;
	case "deleteHocvien":
		if(xd_permission_denied()) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_delete_hocvien();
		break;
	case "updateHocvienStatus":
		if(xd_permission_denied()) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_update_hocvien_status();
		break;
	case "deleteAllHocvien":
		if(xd_permission_denied()) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_delete_all_hocvien();
		break;

	// ---- Lọc thanh toán & bảng kê ----
	case "loc":
		if(xd_permission_denied()) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_loc_preview();
		$template = "xangdau/loc/items";
		break;
	case "xemGiaoVien":
		if(xd_permission_denied()) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_get_giao_vien_detail();
		$template = "xangdau/loc/detail";
		break;
	case "kiemTraGiaoVien":
		if(xd_permission_denied()) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_kiem_tra_giao_vien();
		break;
	case "duyetGiaoVien":
		if(xd_permission_denied()) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_duyet_giao_vien();
		break;
	case "duyetTatCaGiaoVien":
		if(xd_permission_denied()) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_duyet_tat_ca_giao_vien();
		break;
	case "xuatTatCaBangKe":
		if(xd_permission_denied()) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_xuat_tat_ca_bang_ke();
		break;
	case "xuatBangKeGiaoVien":
		if(xd_permission_denied()) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_xuat_bangke_giao_vien();
		break;
	case "xuatBangKe":
		if(xd_permission_denied()) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_xuat_bangke();
		break;

	default:
		$func->redirect("index.php?com=xangdau&act=config");
}

/* ============================ Quyền & bảng ============================ */

function xd_permission_denied()
{
	global $func, $login_admin;

	if(!$func->check_permission()) return false; // super admin
	if(!isset($_SESSION[$login_admin]['active']) || $_SESSION[$login_admin]['active'] != true) return true;
	if(!isset($_SESSION['list_quyen']) || !is_array($_SESSION['list_quyen'])) return true;

	$permissions = array('xangdau_man', 'hoadon_man', 'order_man', 'product_man_cabin');
	foreach($permissions as $permission)
	{
		if(in_array($permission, $_SESSION['list_quyen'])) return false;
	}

	return true;
}

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

/* ============================ Helpers dùng chung ============================ */

function xd_mb_lower($s)
{
	return function_exists('mb_strtolower') ? mb_strtolower((string)$s, 'UTF-8') : strtolower((string)$s);
}

function xd_mb_sub($s, $start, $len)
{
	return function_exists('mb_substr') ? mb_substr((string)$s, $start, $len, 'UTF-8') : substr((string)$s, $start, $len);
}

function xd_norm_header($label)
{
	$label = xd_mb_lower(trim((string)$label));
	$search  = array('à','á','ả','ã','ạ','ă','ằ','ắ','ẳ','ẵ','ặ','â','ầ','ấ','ẩ','ẫ','ậ','đ','è','é','ẻ','ẽ','ẹ','ê','ề','ế','ể','ễ','ệ','ì','í','ỉ','ĩ','ị','ò','ó','ỏ','õ','ọ','ô','ồ','ố','ổ','ỗ','ộ','ơ','ờ','ớ','ở','ỡ','ợ','ù','ú','ủ','ũ','ụ','ư','ừ','ứ','ử','ữ','ự','ỳ','ý','ỷ','ỹ','ỵ');
	$replace = array('a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','d','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y');
	$label = str_replace($search, $replace, $label);
	return preg_replace('/[^a-z0-9]+/', '', $label);
}

function xd_normalize_cccd($value)
{
	return preg_replace('/\D+/', '', (string)$value);
}

/**
 * Khóa định danh giáo viên theo TÊN (các file import không có CCCD giáo viên).
 * Chuẩn hóa: bỏ dấu, chữ thường, bỏ danh xưng (thầy/cô), gộp khoảng trắng.
 */
function xd_gv_key($name)
{
	$name = xd_mb_lower(trim((string)$name));
	if($name === '') return '';
	$search  = array('à','á','ả','ã','ạ','ă','ằ','ắ','ẳ','ẵ','ặ','â','ầ','ấ','ẩ','ẫ','ậ','đ','è','é','ẻ','ẽ','ẹ','ê','ề','ế','ể','ễ','ệ','ì','í','ỉ','ĩ','ị','ò','ó','ỏ','õ','ọ','ô','ồ','ố','ổ','ỗ','ộ','ơ','ờ','ớ','ở','ỡ','ợ','ù','ú','ủ','ũ','ụ','ư','ừ','ứ','ử','ữ','ự','ỳ','ý','ỷ','ỹ','ỵ');
	$replace = array('a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','d','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y');
	$name = str_replace($search, $replace, $name);
	$name = preg_replace('/[^a-z0-9\s]+/', ' ', $name);
	$name = preg_replace('/\s+/', ' ', trim($name));
	// Bỏ danh xưng đầu chuỗi
	$name = preg_replace('/^(thay|co)\s+/', '', $name);
	return trim($name);
}

function xd_cccd_variants($cccd)
{
	$cccd = xd_normalize_cccd($cccd);
	$variants = array($cccd);
	if(strlen($cccd) == 11) $variants[] = '0'.$cccd;
	elseif(strlen($cccd) == 12 && substr($cccd, 0, 1) === '0') $variants[] = substr($cccd, 1);
	return array_values(array_unique(array_filter($variants, function($v){ return $v !== ''; })));
}

/**
 * Đọc giá trị THÔ (underlying) của ô — cần cho ngày (serial Excel) và tiền (số).
 */
function xd_cell_raw($sheet, $column, $row)
{
	$cell = $sheet->getCellByColumnAndRow($column, $row);
	try { $value = $cell->getCalculatedValue(); }
	catch(Exception $e) { $value = $cell->getValue(); }
	if($value === null) $value = $cell->getValue();
	return $value;
}

/**
 * Ngày từ ô: ưu tiên serial Excel (không phụ thuộc định dạng M/D/Y hay D/M/Y).
 */
function xd_date_from_cell($sheet, $column, $row)
{
	$raw = xd_cell_raw($sheet, $column, $row);
	if(is_numeric($raw) && (float)$raw > 0 && class_exists('PHPExcel_Shared_Date'))
	{
		$ts = PHPExcel_Shared_Date::ExcelToPHP((float)$raw);
		if($ts > 0) return date('Y-m-d', $ts);
	}
	return xd_parse_date((string)$raw);
}

function xd_parse_date($rawValue)
{
	$rawValue = trim((string)$rawValue);
	if($rawValue === '') return null;

	if(is_numeric($rawValue))
	{
		$excelDate = (float)$rawValue;
		if($excelDate > 0 && class_exists('PHPExcel_Shared_Date'))
		{
			$ts = PHPExcel_Shared_Date::ExcelToPHP($excelDate);
			if($ts > 0) return date('Y-m-d', $ts);
		}
	}

	if(preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $rawValue)) return $rawValue;

	// Chuỗi ngày kiểu Việt Nam D/M/Y (vd '13/05/1990') — chấp nhận '/', '-', '.'
	if(preg_match('#^(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{4})$#', $rawValue, $m))
	{
		$day = (int)$m[1]; $mon = (int)$m[2];
		if($day >= 1 && $day <= 31 && $mon >= 1 && $mon <= 12)
			return $m[3].'-'.str_pad($mon, 2, '0', STR_PAD_LEFT).'-'.str_pad($day, 2, '0', STR_PAD_LEFT);
	}

	$tmp = str_replace('.', '/', $rawValue);
	$tmp = preg_replace('/\s+/', ' ', $tmp);
	$ts = strtotime($tmp);
	if($ts !== false) return date('Y-m-d', $ts);

	return null;
}

/**
 * Tiền từ ô. Quy tắc theo dữ liệu thực tế:
 *  - Ô là SỐ (float/int) => giá trị nhập theo đơn vị NGHÌN (vd 608.75 => 608.750đ), nhân 1000.
 *    (guard: số >= 100000 coi như đã đủ đơn vị, không nhân.)
 *  - Ô là CHUỖI có dấu phẩy/chấm ngăn cách (vd "1,000,000") => số đầy đủ.
 */
function xd_money_from_cell($sheet, $column, $row)
{
	$raw = xd_cell_raw($sheet, $column, $row);

	if(is_int($raw) || is_float($raw))
	{
		$val = (float)$raw;
		if($val <= 0) return 0.0;
		if($val < 100000) return round($val * 1000); // đơn vị nghìn
		return round($val);
	}

	return xd_parse_money((string)$raw);
}

function xd_parse_money($rawValue)
{
	$rawValue = trim((string)$rawValue);
	if($rawValue === '') return 0.0;

	$rawValue = str_replace(array('VNĐ', 'VND', 'đ', ' '), '', $rawValue);

	$hasComma = strpos($rawValue, ',') !== false;
	$hasDot = strpos($rawValue, '.') !== false;

	if($hasComma && $hasDot)
	{
		// "1,234,567.89" -> bỏ phẩy (ngăn ngàn), giữ chấm thập phân
		$rawValue = str_replace(',', '', $rawValue);
	}
	elseif($hasComma)
	{
		// "1,000,000" -> phẩy là ngăn ngàn
		$rawValue = str_replace(',', '', $rawValue);
	}
	elseif($hasDot)
	{
		// Chuỗi chỉ có chấm: coi là ngăn ngàn (vd "608.750" -> 608750, "1.000.000" -> 1000000)
		$rawValue = str_replace('.', '', $rawValue);
	}

	if(!is_numeric($rawValue)) return 0.0;
	return round((float)$rawValue);
}

function xd_cell($sheet, $column, $row)
{
	$cell = $sheet->getCellByColumnAndRow($column, $row);
	$value = '';
	try { $value = $cell->getFormattedValue(); }
	catch(Exception $e)
	{
		if(method_exists($cell, 'getOldCalculatedValue')) $value = $cell->getOldCalculatedValue();
		if($value === null || $value === '') $value = $cell->getValue();
	}
	if(is_object($value) && method_exists($value, '__toString')) $value = (string)$value;
	if(is_array($value)) $value = json_encode($value, JSON_UNESCAPED_UNICODE);
	return trim((string)$value);
}

/**
 * Tìm dòng header và map cột theo bảng alias.
 * $containsRules (tùy chọn): field => ['has' => [...substring bắt buộc có...], 'not' => [...substring bị loại...]]
 * dùng làm heuristic bổ sung khi alias chính xác không khớp (mẫu file có header đặt tên khác chuẩn, vd "CCCD_HV", "Ten_HV").
 * @return array [headerRow, map(fieldKey => colIndex), score]
 */
function xd_detect_header($sheet, $highestRow, $highestColIndex, $aliasGroups, $containsRules = array())
{
	$bestScore = -1;
	$bestRow = 1;
	$bestMap = array();
	$scanTo = min($highestRow, 15);

	for($scanRow = 1; $scanRow <= $scanTo; $scanRow++)
	{
		$normByCol = array();
		for($col = 0; $col < $highestColIndex; $col++)
		{
			$norm = xd_norm_header($sheet->getCellByColumnAndRow($col, $scanRow)->getValue());
			if($norm !== '') $normByCol[$col] = $norm;
		}
		if(empty($normByCol)) continue;

		$map = array();
		$score = 0;

		// Đợt 1: khớp chính xác theo alias
		foreach($aliasGroups as $field => $aliases)
		{
			foreach($normByCol as $col => $norm)
			{
				if(in_array($norm, $aliases, true) && !isset($map[$field]))
				{
					$map[$field] = $col;
					$score += 2;
					break;
				}
			}
		}

		// Đợt 2: heuristic chứa chuỗi con cho các field chưa khớp (chỉ xét cột chưa được dùng)
		if(!empty($containsRules))
		{
			$usedCols = array_flip($map);
			foreach($containsRules as $field => $rule)
			{
				if(isset($map[$field])) continue;
				$mustHave = isset($rule['has']) ? $rule['has'] : array();
				$mustNot = isset($rule['not']) ? $rule['not'] : array();

				foreach($normByCol as $col => $norm)
				{
					if(isset($usedCols[$col])) continue;

					$ok = true;
					foreach($mustHave as $h) { if(strpos($norm, $h) === false) { $ok = false; break; } }
					if(!$ok) continue;
					foreach($mustNot as $n) { if(strpos($norm, $n) !== false) { $ok = false; break; } }
					if(!$ok) continue;

					$map[$field] = $col;
					$usedCols[$col] = 1;
					$score++;
					break;
				}
			}
		}

		if($score > $bestScore)
		{
			$bestScore = $score;
			$bestRow = $scanRow;
			$bestMap = $map;
		}
	}

	return array($bestRow, $bestMap, $bestScore);
}

/**
 * Đọc giá trị 1 cột đã map; trả về '' nếu field chưa xác định được cột (thay vì đọc nhầm cột khác).
 */
function xd_val($sheet, $map, $field, $row)
{
	if(!isset($map[$field])) return '';
	return xd_cell($sheet, $map[$field], $row);
}

function xd_username()
{
	global $login_admin;
	return isset($_SESSION[$login_admin]['username']) ? $_SESSION[$login_admin]['username'] : '';
}

/* ============================ Cấu hình định mức ============================ */

function xd_get_config()
{
	global $d, $item;
	$item = getXdConfig($d);
}

function xd_save_config()
{
	global $d, $func;

	if(empty($_POST)) $func->transfer("Không nhận được dữ liệu", "index.php?com=xangdau&act=config", false);

	$keys = array('xd_dinh_muc', 'xd_muc_bt', 'xd_muc_ck', 'xd_muc_dat');
	$data = isset($_POST['data']) ? $_POST['data'] : array();

	foreach($keys as $key)
	{
		$rawVal = isset($data[$key]) ? $data[$key] : '';
		$rawVal = str_replace(array('.', ',', ' '), '', trim($rawVal));
		$value = (is_numeric($rawVal) && (int)$rawVal >= 0) ? (int)$rawVal : 0;
		saveXdConfig($d, $key, $value);
	}

	$func->transfer("Cập nhật định mức thanh toán thành công", "index.php?com=xangdau&act=config");
}

/* ============================ Danh sách & xóa hóa đơn ============================ */

function xd_get_hoadon()
{
	global $d, $func, $curPage, $items, $paging, $xd_filter_keyword, $xd_filter_from, $xd_filter_to, $xd_filter_ky;

	$where = "";
	$params = array();
	$xd_filter_keyword = '';
	$xd_filter_from = '';
	$xd_filter_to = '';
	$xd_filter_ky = '';

	if(isset($_REQUEST['keyword']) && trim($_REQUEST['keyword']) !== '')
	{
		$xd_filter_keyword = trim($_REQUEST['keyword']);
		$where .= " and (ma_hoa_don like ? or gv_hoten like ?)";
		$params[] = '%'.$xd_filter_keyword.'%';
		$params[] = '%'.$xd_filter_keyword.'%';
	}
	if(isset($_REQUEST['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['from_date']))
	{
		$xd_filter_from = $_REQUEST['from_date'];
		$where .= " and ngay_hoa_don >= ?";
		$params[] = $xd_filter_from;
	}
	if(isset($_REQUEST['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['to_date']))
	{
		$xd_filter_to = $_REQUEST['to_date'];
		$where .= " and ngay_hoa_don <= ?";
		$params[] = $xd_filter_to;
	}
	if(isset($_REQUEST['ky']) && trim($_REQUEST['ky']) !== '')
	{
		$xd_filter_ky = trim($_REQUEST['ky']);
		$where .= " and ky = ?";
		$params[] = $xd_filter_ky;
	}

	$per_page = 20;
	$startpoint = ($curPage * $per_page) - $per_page;
	$sql = "select * from #_xd_hoadon where id > 0 $where order by ngay_hoa_don desc, id desc limit ".$startpoint.",".$per_page;
	$items = $d->rawQuery($sql, $params);

	$count = $d->rawQueryOne("select count(*) as num from #_xd_hoadon where id > 0 $where", $params);
	$total = isset($count['num']) ? (int)$count['num'] : 0;

	$url = "index.php?com=xangdau&act=hoadon";
	if($xd_filter_keyword !== '') $url .= '&keyword='.urlencode($xd_filter_keyword);
	if($xd_filter_from !== '') $url .= '&from_date='.urlencode($xd_filter_from);
	if($xd_filter_to !== '') $url .= '&to_date='.urlencode($xd_filter_to);
	if($xd_filter_ky !== '') $url .= '&ky='.urlencode($xd_filter_ky);
	$paging = $func->pagination($total, $per_page, $curPage, $url);
}

function xd_delete_hoadon()
{
	global $d, $func, $curPage;

	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	$redirect = "index.php?com=xangdau&act=hoadon&p=".$curPage;

	if($id > 0)
	{
		$row = $d->rawQueryOne("select da_quyettoan from #_xd_hoadon where id = ? limit 0,1", array($id));
		if($row && (int)$row['da_quyettoan'] === 1) $func->transfer("Hóa đơn đã quyết toán, không thể xóa", $redirect, false);
		$d->rawQuery("delete from #_xd_hoadon where id = ? and da_quyettoan = 0", array($id));
		$func->transfer("Xóa hóa đơn thành công", $redirect);
	}
	elseif(isset($_GET['listid']))
	{
		$listid = explode(",", $_GET['listid']);
		foreach($listid as $tid)
		{
			$tid = (int)$tid;
			if($tid > 0) $d->rawQuery("delete from #_xd_hoadon where id = ? and da_quyettoan = 0", array($tid));
		}
		$func->transfer("Xóa hóa đơn thành công (bỏ qua hóa đơn đã quyết toán)", $redirect);
	}
	else $func->transfer("Không nhận được dữ liệu", $redirect, false);
}

function xd_delete_all_hoadon()
{
	global $d, $func;
	$count = $d->rawQueryOne("select count(*) as num from #_xd_hoadon");
	$ok = $d->rawQuery("delete from #_xd_hoadon");
	$n = isset($count['num']) ? (int)$count['num'] : 0;
	if($ok === false) $func->transfer("Không thể xóa toàn bộ hóa đơn", "index.php?com=xangdau&act=hoadon", false);
	$func->transfer("Đã xóa toàn bộ $n hóa đơn.", "index.php?com=xangdau&act=hoadon");
}

/* ============================ Import hóa đơn ============================ */

/**
 * Chuyển đổi file .xlsb -> .xlsx bằng công cụ có sẵn trên máy chủ.
 * Dò theo thứ tự: LibreOffice (soffice) -> Gnumeric (ssconvert).
 * @return string|false đường dẫn file .xlsx tạm, hoặc false nếu không có công cụ.
 */
function xd_convert_to_xlsx($inputFile)
{
	if(!function_exists('shell_exec')) return false;

	$outDir = sys_get_temp_dir().'/xd_conv_'.uniqid();
	@mkdir($outDir, 0777, true);
	$base = pathinfo($inputFile, PATHINFO_FILENAME);
	$expected = $outDir.'/'.$base.'.xlsx';

	// 1) LibreOffice headless
	$soffice = '';
	foreach(array('soffice', '/Applications/LibreOffice.app/Contents/MacOS/soffice', '/usr/bin/soffice', '/opt/libreoffice/program/soffice') as $cand)
	{
		$which = @shell_exec('command -v '.escapeshellarg($cand).' 2>/dev/null');
		if($cand[0] === '/' && is_executable($cand)) { $soffice = $cand; break; }
		if(!empty($which)) { $soffice = trim($which); break; }
	}
	if($soffice !== '')
	{
		// LibreOffice headless cần một thư mục profile GHI ĐƯỢC (quan trọng khi chạy dưới user www-data).
		$profile = $outDir.'/loprofile';
		@mkdir($profile, 0777, true);
		$timeout = trim((string)@shell_exec('command -v timeout 2>/dev/null'));
		// Gán HOME phải đứng TRƯỚC 'timeout' (nếu không timeout sẽ hiểu HOME=... là tên lệnh).
		$prefix = 'HOME='.escapeshellarg($outDir).' ';
		if($timeout !== '') $prefix .= escapeshellarg($timeout).' 180 ';
		$cmd = $prefix.escapeshellarg($soffice)
			.' -env:UserInstallation=file://'.$profile
			.' --headless --calc --convert-to xlsx --outdir '.escapeshellarg($outDir).' '.escapeshellarg($inputFile).' 2>&1';
		@shell_exec($cmd);
		if(is_file($expected) && filesize($expected) > 0) return $expected;
		// LibreOffice có thể đặt tên khác: quét file .xlsx đầu tiên trong outDir
		$found = glob($outDir.'/*.xlsx');
		if(!empty($found) && is_file($found[0])) return $found[0];
	}

	// 2) Gnumeric ssconvert
	$ssconvert = @shell_exec('command -v ssconvert 2>/dev/null');
	if(!empty($ssconvert))
	{
		@shell_exec(escapeshellarg(trim($ssconvert)).' '.escapeshellarg($inputFile).' '.escapeshellarg($expected).' 2>&1');
		if(is_file($expected) && filesize($expected) > 0) return $expected;
	}

	return false;
}

/**
 * Mở sheet dữ liệu từ file upload, hỗ trợ .xlsx/.xls/.xlsb (xlsb qua chuyển đổi).
 * $sheetHints: mảng chuỗi không dấu (không khoảng trắng) để chọn sheet theo tên
 *   (vd ['hocvien'] hoặc ['hdon','hoadon']); rỗng = sheet đầu.
 *   Chỉ nạp đúng sheet khớp để tránh tốn bộ nhớ với file nhiều/lớn sheet.
 * @return array [objPHPExcel, sheet, highestRow, highestColIndex]  (thoát bằng transfer nếu lỗi)
 */
function xd_open_upload_sheet($file, $ext, $backUrl, $sheetHints = array())
{
	global $func;

	@ini_set('memory_limit', '2048M');
	require_once LIBRARIES.'PHPExcel.php';

	// Bộ lọc giới hạn số dòng đọc: file chuyển đổi có thể khai báo tới 1.048.576 dòng
	// khiến PHPExcel cấp phát khổng lồ. Chỉ đọc trong phạm vi dòng hợp lý.
	if(!class_exists('XdRowLimitReadFilter'))
	{
		class XdRowLimitReadFilter implements PHPExcel_Reader_IReadFilter
		{
			public $maxRow;
			public function __construct($maxRow = 20000) { $this->maxRow = (int)$maxRow; }
			public function readCell($column, $row, $worksheetName = '') { return $row <= $this->maxRow; }
		}
	}

	// Giảm bộ nhớ khi nạp sheet lớn: cache ô ra php://temp (tràn ra đĩa sau ngưỡng).
	if(class_exists('PHPExcel_Settings') && class_exists('PHPExcel_CachedObjectStorageFactory'))
	{
		@PHPExcel_Settings::setCacheStorageMethod(
			PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
			array('memoryCacheSize' => '256MB')
		);
	}

	$inputFileName = $file['tmp_name'];
	if(empty($inputFileName) || !is_readable($inputFileName)) $func->transfer("Không đọc được file tạm. Vui lòng thử lại.", $backUrl, false);

	if(is_string($sheetHints)) $sheetHints = ($sheetHints === '') ? array() : array($sheetHints);

	$readerType = 'Excel2007';
	$loadFile = $inputFileName;
	$convertedFile = '';

	if($ext === 'xlsb')
	{
		$convertedFile = xd_convert_to_xlsx($inputFileName);
		if($convertedFile === false || !is_file($convertedFile))
		{
			$func->transfer("Máy chủ chưa hỗ trợ đọc file .xlsb. Vui lòng mở file và lưu lại dưới định dạng .xlsx rồi import lại.", $backUrl, false);
		}
		$loadFile = $convertedFile;
		$readerType = 'Excel2007';
	}
	elseif($ext === 'xls')
	{
		$readerType = 'Excel5';
	}

	// Đọc XLSX theo luồng để tránh PHPExcel nạp cả workbook vào RAM trên production.
	// File XLSB sau khi chuyển đổi cũng đi qua cùng reader này.
	if($ext === 'xlsx' || $ext === 'xlsb')
	{
		$streamRow = 0; $streamCol = 0;
		$streamSheet = xd_stream_read_xlsx($loadFile, $sheetHints, $streamRow, $streamCol, 20000);
		if($streamSheet !== false)
		{
			xd_cleanup_converted($convertedFile);
			return array(null, $streamSheet, $streamRow, $streamCol);
		}
		// Nếu đọc luồng thất bại thì thử tiếp bằng PHPExcel (best-effort).
	}

	try
	{
		$reader = PHPExcel_IOFactory::createReader($readerType);
		if(method_exists($reader, 'setReadDataOnly')) $reader->setReadDataOnly(true);
		if(method_exists($reader, 'setReadFilter')) $reader->setReadFilter(new XdRowLimitReadFilter(20000));

		// Chọn tên sheet cần nạp (chỉ nạp đúng sheet đó để tiết kiệm bộ nhớ)
		$targetName = '';
		if(!empty($sheetHints) && method_exists($reader, 'listWorksheetNames'))
		{
			$names = $reader->listWorksheetNames($loadFile);
			foreach($sheetHints as $hint)
			{
				foreach($names as $nm)
				{
					if(strpos(xd_norm_header($nm), $hint) !== false) { $targetName = $nm; break 2; }
				}
			}
			if($targetName !== '' && method_exists($reader, 'setLoadSheetsOnly')) $reader->setLoadSheetsOnly($targetName);
		}

		$objPHPExcel = $reader->load($loadFile);
	}
	catch(Throwable $e)
	{
		xd_cleanup_converted($convertedFile);
		$func->transfer("Không đọc được file Excel. Vui lòng lưu lại dưới định dạng .xlsx và thử lại.", $backUrl, false);
		return array();
	}

	// Chọn sheet: nếu đã nạp đúng 1 sheet mục tiêu thì lấy sheet đầu; nếu không, dò theo hint.
	$sheet = null;
	if(!empty($sheetHints))
	{
		foreach($objPHPExcel->getWorksheetIterator() as $ws)
		{
			$title = xd_norm_header($ws->getTitle());
			foreach($sheetHints as $hint)
			{
				if(strpos($title, $hint) !== false) { $sheet = $ws; break 2; }
			}
		}
	}
	if($sheet === null) $sheet = $objPHPExcel->getSheet(0);

	$highestRow = (int)$sheet->getHighestRow();
	$highestColIndex = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());

	xd_cleanup_converted($convertedFile);

	return array($objPHPExcel, $sheet, $highestRow, $highestColIndex);
}

/**
 * Xóa file .xlsx tạm sinh ra từ chuyển đổi .xlsb cùng cả thư mục tạm (profile LibreOffice).
 */
function xd_cleanup_converted($convertedFile)
{
	if($convertedFile === '' || !is_file($convertedFile)) return;
	$dir = dirname($convertedFile);
	@unlink($convertedFile);
	// Chỉ xóa đệ quy nếu là thư mục tạm của module (an toàn)
	if(strpos(basename($dir), 'xd_conv_') === 0) xd_rmtree($dir);
}

function xd_rmtree($dir)
{
	if(!is_dir($dir)) { if(is_file($dir)) @unlink($dir); return; }
	$items = @scandir($dir);
	if(is_array($items))
	{
		foreach($items as $it)
		{
			if($it === '.' || $it === '..') continue;
			$path = $dir.'/'.$it;
			if(is_dir($path)) xd_rmtree($path);
			else @unlink($path);
		}
	}
	@rmdir($dir);
}

function xd_col_ref_to_index($ref)
{
	if(!preg_match('/^([A-Za-z]+)(\d+)$/', $ref, $m)) return array(-1, -1);
	$letters = strtoupper($m[1]); $idx = 0;
	$len = strlen($letters);
	for($i = 0; $i < $len; $i++) $idx = $idx * 26 + (ord($letters[$i]) - 64);
	return array($idx - 1, (int)$m[2]);
}

/**
 * Đọc 1 sheet của file .xlsx theo LUỒNG (XMLReader) để tiết kiệm bộ nhớ.
 * @return XdArraySheet|false  (trả $highestRow, $highestColIndex qua tham chiếu)
 */
function xd_stream_read_xlsx($path, $sheetHints, &$highestRow, &$highestColIndex, $maxRow = 20000)
{
	$highestRow = 0; $highestColIndex = 0;
	if(!class_exists('ZipArchive') || !class_exists('XMLReader')) return false;

	$zip = new ZipArchive();
	if($zip->open($path) !== true) return false;
	$wbXml = $zip->getFromName('xl/workbook.xml');
	$relXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
	$zip->close();
	if($wbXml === false || $relXml === false) return false;

	// Tìm sheet mục tiêu theo tên -> r:id
	$sheetRid = ''; $wb = @simplexml_load_string($wbXml);
	if(!$wb || !isset($wb->sheets)) return false;
	$ns = $wb->getDocNamespaces(true);
	$rns = isset($ns['r']) ? $ns['r'] : 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
	foreach($wb->sheets->sheet as $sh)
	{
		$norm = xd_norm_header((string)$sh['name']);
		foreach($sheetHints as $hint) { if($hint !== '' && strpos($norm, $hint) !== false) { $ra = $sh->attributes($rns); $sheetRid = (string)$ra['id']; break 2; } }
	}
	if($sheetRid === '' && isset($wb->sheets->sheet[0])) { $ra = $wb->sheets->sheet[0]->attributes($rns); $sheetRid = (string)$ra['id']; }
	if($sheetRid === '') return false;

	// r:id -> target path
	$target = ''; $rels = @simplexml_load_string($relXml);
	if($rels) foreach($rels->Relationship as $rel) { if((string)$rel['Id'] === $sheetRid) { $target = (string)$rel['Target']; break; } }
	if($target === '') return false;
	$target = str_replace('../', '', $target);
	if(strpos($target, 'xl/') !== 0) $target = 'xl/'.ltrim($target, '/');

	// Shared strings (stream)
	$shared = array();
	$sr = new XMLReader();
	if(@$sr->open('zip://'.$path.'#xl/sharedStrings.xml'))
	{
		$sdom = new DOMDocument();
		while(@$sr->read())
		{
			if($sr->nodeType === XMLReader::ELEMENT && $sr->localName === 'si')
			{
				$node = $sr->expand($sdom);
				$txt = '';
				if($node) foreach($node->getElementsByTagName('t') as $t) $txt .= $t->textContent;
				$shared[] = $txt;
			}
		}
		$sr->close();
	}

	// Sheet rows (stream, expand từng dòng — bộ nhớ hằng số)
	$rows = array(); $maxColIdx = 0; $lastData = 0; $emptyStreak = 0;
	$rd = new XMLReader();
	if(!@$rd->open('zip://'.$path.'#'.$target)) return false;
	$dom = new DOMDocument();
	while(@$rd->read())
	{
		if($rd->nodeType !== XMLReader::ELEMENT || $rd->localName !== 'row') continue;
		$rowNum = (int)$rd->getAttribute('r');
		if($rowNum <= 0) continue;
		if($rowNum > $maxRow) break;

		$node = $rd->expand($dom);
		if(!$node) continue;
		$anyVal = false;
		foreach($node->childNodes as $c)
		{
			if($c->nodeType !== XML_ELEMENT_NODE || $c->localName !== 'c') continue;
			list($colIdx, ) = xd_col_ref_to_index($c->getAttribute('r'));
			if($colIdx < 0) continue;
			$t = $c->getAttribute('t');
			$val = '';
			foreach($c->childNodes as $ch)
			{
				if($ch->nodeType !== XML_ELEMENT_NODE) continue;
				if($ch->localName === 'v')
				{
					$raw = $ch->textContent;
					if($t === 's') { $ii = (int)$raw; $val = isset($shared[$ii]) ? $shared[$ii] : ''; }
					else $val = $raw;
				}
				elseif($ch->localName === 'is') $val = $ch->textContent;
			}
			$val = trim((string)$val);
			if($val !== '')
			{
				$rows[$rowNum][$colIdx] = $val;
				if($colIdx + 1 > $maxColIdx) $maxColIdx = $colIdx + 1;
				$anyVal = true;
			}
		}
		if($anyVal) { $lastData = $rowNum; $emptyStreak = 0; }
		elseif($lastData > 0 && ++$emptyStreak > 200) break;
	}
	$rd->close();

	$highestRow = max(1, $lastData);
	$highestColIndex = max(1, $maxColIdx);
	return new XdArraySheet($rows, $highestRow, $highestColIndex);
}

function xd_upload_hoadon_excel()
{
	global $d, $func;

	@ini_set('memory_limit', '1024M');
	@set_time_limit(300);

	$backUrl = "index.php?com=xangdau&act=uploadHoadon";

	if(!isset($_FILES['file-excel']) || (int)$_FILES['file-excel']['error'] !== UPLOAD_ERR_OK)
		$func->transfer("Vui lòng chọn file Excel hợp lệ", $backUrl, false);

	$file = $_FILES['file-excel'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	if(!in_array($ext, array('xls', 'xlsx', 'xlsb'))) $func->transfer("Chỉ hỗ trợ file .xls, .xlsx hoặc .xlsb", $backUrl, false);

	// Kỳ nhập trên form (bắt buộc, dùng để chống import đè theo kỳ)
	$kyForm = isset($_POST['ky']) ? trim((string)$_POST['ky']) : '';

	// File tổng hợp có thể có sheet tóm tắt (TH) và sheet chi tiết (HĐơn) -> ưu tiên sheet hóa đơn
	list($objPHPExcel, $sheet, $highestRow, $highestColIndex) = xd_open_upload_sheet($file, $ext, $backUrl, array('hdon', 'hoadon'));

	// Cột theo mẫu thực tế: STT | Số hóa đơn | Ngày | Thông tin bán hàng | Chi tiết | Số tiền HĐ | Biển số xe | HĐ từ trang thuế | GV | Note1 | Note2
	$aliasGroups = array(
		'ma'      => array('sohoadon', 'mahoadon', 'masohoadon', 'sohd', 'mahd'),
		'ngay'    => array('ngay', 'ngayhoadon', 'ngayhd', 'ngaylap', 'date'),
		'ttbanhang' => array('thongtinbanhang', 'thongtinbanha', 'ttbanhang'),
		'chitiet'  => array('chitiet', 'chit'),
		'tien'    => array('sotienhd', 'sotienhoadon', 'tongtien', 'tienhoadon', 'thanhtien', 'sotien'),
		'bienso'  => array('bienso', 'biensoxe'),
		'gv'      => array('gv', 'giaovien', 'tengiaovien', 'tengv', 'phanxe'),
	);
	$containsRules = array(
		'ma'        => array('has' => array('hoadon')),
		'ttbanhang' => array('has' => array('banhang')),
		'tien'      => array('has' => array('tien')),
		'gv'        => array('has' => array('gv')),
		'bienso'    => array('has' => array('bienso')),
	);

	list($headerRow, $map, $headerScore) = xd_detect_header($sheet, $highestRow, $highestColIndex, $aliasGroups, $containsRules);

	// Nếu không nhận diện được tiêu đề nào -> giả định đúng thứ tự cột mẫu (có cột STT ở [0])
	if($headerScore <= 0)
	{
		$map = array('ma' => 1, 'ngay' => 2, 'ttbanhang' => 3, 'chitiet' => 4, 'tien' => 5, 'bienso' => 6, 'gv' => 8);
		$headerRow = 1;
	}

	if(!isset($map['ma']))  $func->transfer("Không xác định được cột 'Số hóa đơn' trong file. Vui lòng kiểm tra tiêu đề cột.", $backUrl, false);
	if(!isset($map['tien'])) $func->transfer("Không xác định được cột 'Số tiền HĐ' trong file. Vui lòng kiểm tra tiêu đề cột.", $backUrl, false);
	if(!isset($map['gv']))  $func->transfer("Không xác định được cột 'GV' (tên giáo viên) trong file. Vui lòng kiểm tra tiêu đề cột.", $backUrl, false);

	// Thu thập các dòng hợp lệ
	$rows = array();
	$emptyStreak = 0;
	for($row = $headerRow + 1; $row <= $highestRow; $row++)
	{
		$ma = xd_cell($sheet, $map['ma'], $row);
		$gvten = xd_cell($sheet, $map['gv'], $row);
		$bienso = isset($map['bienso']) ? xd_cell($sheet, $map['bienso'], $row) : '';
		$ttbanhang = isset($map['ttbanhang']) ? xd_cell($sheet, $map['ttbanhang'], $row) : '';
		$chitiet = isset($map['chitiet']) ? xd_cell($sheet, $map['chitiet'], $row) : '';

		if($ma === '' && $gvten === '')
		{
			if(++$emptyStreak > 50) break;
			continue; // dòng trống
		}
		$emptyStreak = 0;
		if($ma === '')
		{
			// bỏ qua dòng không có số hóa đơn (vd dòng tổng)
			continue;
		}

		$ngay = xd_date_from_cell($sheet, $map['ngay'], $row);
		$tien = xd_money_from_cell($sheet, $map['tien'], $row);
		$gvkey = xd_gv_key($gvten);

		$rows[] = array(
			'row' => $row, 'ma' => $ma, 'ngay' => $ngay, 'tien' => $tien,
			'gvten' => $gvten, 'gvkey' => $gvkey, 'bienso' => $bienso,
			'ttbanhang' => $ttbanhang, 'chitiet' => $chitiet
		);
	}

	if(empty($rows)) $func->transfer("File không có dòng hóa đơn hợp lệ nào.", $backUrl, false);

	// Chặn import đè kỳ đã tồn tại dữ liệu (nếu có nhập kỳ)
	if($kyForm !== '')
	{
		$existKy = $d->rawQueryOne("select count(*) as num from #_xd_hoadon where ky = ?", array($kyForm));
		if($existKy && (int)$existKy['num'] > 0)
			$func->transfer("Kỳ <strong>".htmlspecialchars($kyForm)."</strong> đã được import trước đó. Không thể import đè để tránh trùng dữ liệu.", $backUrl, false);
	}

	$username = xd_username();
	$inserted = 0;
	$skippedDup = 0;
	$skippedLocked = 0;
	$errors = array();
	$seenKeys = array();

	$d->startTransaction();
	foreach($rows as $r)
	{
		// Số HĐ có thể trùng giữa các giáo viên khác nhau -> khóa trùng gồm cả GV (Mã HĐ + Ngày HĐ + GV)
		$key = $r['ma'].'|'.($r['ngay'] === null ? '' : $r['ngay']).'|'.$r['gvkey'];
		if(isset($seenKeys[$key])) { $skippedDup++; continue; }
		$seenKeys[$key] = 1;

		// Kiểm tra trùng (ma_hoa_don + ngay_hoa_don + gv_key)
		$exists = $d->rawQueryOne(
			"select id, da_quyettoan from #_xd_hoadon where ma_hoa_don = ? and (ngay_hoa_don <=> ?) and gv_key = ? limit 0,1",
			array($r['ma'], $r['ngay'], $r['gvkey'])
		);
		if($exists && isset($exists['id']))
		{
			if((int)$exists['da_quyettoan'] === 1) $skippedLocked++;
			else $skippedDup++;
			continue;
		}

		$ok = $d->rawQuery(
			"insert into #_xd_hoadon (gv_cccd, gv_hoten, gv_key, ma_hoa_don, thong_tin_ban_hang, chi_tiet, ngay_hoa_don, tong_tien, bien_so, ky, da_quyettoan, id_bangke, ngaytao, user_tao) values ('', ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?, ?)",
			array($r['gvten'], $r['gvkey'], $r['ma'], $r['ttbanhang'], $r['chitiet'], $r['ngay'], $r['tien'], $r['bienso'], $kyForm, time(), $username)
		);
		if($ok === false)
		{
			$err = $d->getLastError();
			$errors[] = "Dòng ".$r['row'].": lỗi lưu dữ liệu".(is_array($err) && isset($err[2]) ? ' ('.$err[2].')' : '');
		}
		else $inserted++;
	}

	if(!empty($errors))
	{
		$d->rollback();
		$func->transfer("Import thất bại:<br>".implode("<br>", array_slice($errors, 0, 10)), $backUrl, false);
	}

	$d->commit();

	$msg = "Import thành công $inserted hóa đơn.";
	if($skippedDup > 0) $msg .= "<br>Bỏ qua $skippedDup hóa đơn trùng (Mã HĐ + Ngày HĐ).";
	if($skippedLocked > 0) $msg .= "<br>Bỏ qua $skippedLocked hóa đơn đã quyết toán (bị khóa).";
	$func->transfer($msg, "index.php?com=xangdau&act=hoadon");
}

/* ============================ Danh sách & xóa học viên ============================ */

function xd_get_hocvien()
{
	global $d, $func, $curPage, $items, $paging, $xd_filter_keyword, $xd_filter_nhom, $xd_filter_trangthai;

	$where = "";
	$params = array();
	$xd_filter_keyword = '';
	$xd_filter_nhom = '';
	$xd_filter_trangthai = '';

	if(isset($_REQUEST['keyword']) && trim($_REQUEST['keyword']) !== '')
	{
		$xd_filter_keyword = trim($_REQUEST['keyword']);
		$where .= " and (ho_ten like ? or cccd like ? or gv_hoten like ?)";
		$params[] = '%'.$xd_filter_keyword.'%';
		$params[] = '%'.$xd_filter_keyword.'%';
		$params[] = '%'.$xd_filter_keyword.'%';
	}
	if(isset($_REQUEST['nhom']) && in_array($_REQUEST['nhom'], array('BT', 'CK', 'DAT'), true))
	{
		$xd_filter_nhom = $_REQUEST['nhom'];
		$where .= " and nhom = ?";
		$params[] = $xd_filter_nhom;
	}
	if(isset($_REQUEST['trangthai']) && in_array($_REQUEST['trangthai'], array('da', 'chua'), true))
	{
		$xd_filter_trangthai = $_REQUEST['trangthai'];
		$where .= ($xd_filter_trangthai === 'da') ? " and ngay_thanh_toan is not null" : " and ngay_thanh_toan is null";
	}

	$per_page = 20;
	$startpoint = ($curPage * $per_page) - $per_page;
	$sql = "select * from #_xd_hocvien where id > 0 $where order by id asc limit ".$startpoint.",".$per_page;
	$items = $d->rawQuery($sql, $params);

	$count = $d->rawQueryOne("select count(*) as num from #_xd_hocvien where id > 0 $where", $params);
	$total = isset($count['num']) ? (int)$count['num'] : 0;

	$url = "index.php?com=xangdau&act=hocvien";
	if($xd_filter_keyword !== '') $url .= '&keyword='.urlencode($xd_filter_keyword);
	if($xd_filter_nhom !== '') $url .= '&nhom='.urlencode($xd_filter_nhom);
	if($xd_filter_trangthai !== '') $url .= '&trangthai='.urlencode($xd_filter_trangthai);
	$paging = $func->pagination($total, $per_page, $curPage, $url);
}

function xd_delete_hocvien()
{
	global $d, $func, $curPage;

	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	$redirect = "index.php?com=xangdau&act=hocvien&p=".$curPage;

	if($id > 0)
	{
		$row = $d->rawQueryOne("select ngay_thanh_toan from #_xd_hocvien where id = ? limit 0,1", array($id));
		if($row && $row['ngay_thanh_toan'] !== null) $func->transfer("Học viên đã thanh toán, không thể xóa", $redirect, false);
		$d->rawQuery("delete from #_xd_hocvien where id = ? and ngay_thanh_toan is null", array($id));
		$func->transfer("Xóa học viên thành công", $redirect);
	}
	elseif(isset($_GET['listid']))
	{
		$listid = explode(",", $_GET['listid']);
		foreach($listid as $tid)
		{
			$tid = (int)$tid;
			if($tid > 0) $d->rawQuery("delete from #_xd_hocvien where id = ? and ngay_thanh_toan is null", array($tid));
		}
		$func->transfer("Xóa học viên thành công (bỏ qua học viên đã thanh toán)", $redirect);
	}
	else $func->transfer("Không nhận được dữ liệu", $redirect, false);
}

function xd_update_hocvien_status()
{
	global $d, $func, $curPage;

	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	$status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
	$redirect = "index.php?com=xangdau&act=hocvien&p=".(int)$curPage;
	if($id <= 0 || !in_array($status, array('da', 'chua'), true))
		$func->transfer("Trạng thái học viên không hợp lệ", $redirect, false);

	$row = $d->rawQueryOne("select id, nhom from #_xd_hocvien where id = ? limit 0,1", array($id));
	if(empty($row)) $func->transfer("Không tìm thấy học viên", $redirect, false);

	if($status === 'da')
	{
		$config = getXdConfig($d);
		$dinhMuc = (int)$config['dinh_muc'];
		$soTien = (int)xdMucTheoNhom($config, $row['nhom']);
		$ok = $d->rawQuery(
			"update #_xd_hocvien set ngay_thanh_toan = ?, dinh_muc = ?, so_tien_thanh_toan = ?, id_bangke = 0 where id = ?",
			array(date('Y-m-d'), $dinhMuc, $soTien, $id)
		);
		$message = "Đã cập nhật học viên thành đã thanh toán";
	}
	else
	{
		$ok = $d->rawQuery(
			"update #_xd_hocvien set ngay_thanh_toan = NULL, dinh_muc = 0, so_tien_thanh_toan = 0, id_bangke = 0 where id = ?",
			array($id)
		);
		$message = "Đã cập nhật học viên thành chưa thanh toán";
	}

	if($ok === false) $func->transfer("Không thể cập nhật trạng thái học viên", $redirect, false);
	$func->transfer($message, $redirect);
}

function xd_delete_all_hocvien()
{
	global $d, $func;
	$count = $d->rawQueryOne("select count(*) as num from #_xd_hocvien");
	$ok = $d->rawQuery("delete from #_xd_hocvien");
	$n = isset($count['num']) ? (int)$count['num'] : 0;
	if($ok === false) $func->transfer("Không thể xóa toàn bộ học viên", "index.php?com=xangdau&act=hocvien", false);
	$func->transfer("Đã xóa toàn bộ $n học viên.", "index.php?com=xangdau&act=hocvien");
}

/* ============================ Import học viên (chống trùng CCCD) ============================ */

function xd_upload_hocvien_excel()
{
	global $d, $func;

	@ini_set('memory_limit', '1024M');
	@set_time_limit(300);

	$backUrl = "index.php?com=xangdau&act=uploadHocvien";

	if(!isset($_FILES['file-excel']) || (int)$_FILES['file-excel']['error'] !== UPLOAD_ERR_OK)
		xd_hocvien_import_error("Vui lòng chọn file Excel hợp lệ", $backUrl);

	$file = $_FILES['file-excel'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	if($ext !== 'xlsx') xd_hocvien_import_error("Import học viên chỉ hỗ trợ file .xlsx. Vui lòng mở file XLSB/XLS bằng Excel và lưu lại dưới dạng .xlsx rồi thử lại.", $backUrl);

	list($objPHPExcel, $sheet, $highestRow, $highestColIndex) = xd_open_upload_sheet($file, $ext, $backUrl, array('hocvien'));

	// Cột theo mẫu thực tế: STT | HỌ VÀ TÊN | KHÓA | Ngày sinh | Số CCCD | NGƯỜI NỘP | GIÁO VIÊN/PHÂN XE | Nhóm/GHI CHÚ | đã tt | Menu | ...
	$aliasGroups = array(
		'ten'      => array('hovaten', 'hoten', 'tenhocvien', 'hocvien', 'tenhv'),
		'khoa'     => array('khoa'),
		'ngaysinh' => array('ngaythangnamsinh', 'ngaysinh', 'namsinh'),
		'cccd'     => array('socccdcch', 'socccd', 'cccd', 'cccdcc', 'cmnd', 'cccdhv'),
		'nguoinop' => array('nguoinop', 'nguoinophoso'),
		'gv'       => array('phanxe', 'giaovien', 'gv', 'tengiaovien', 'gvphutrach'),
		'nhom'     => array('nhom', 'loai', 'phanloai', 'nhomdaotao', 'ghichu'),
		'datt'     => array('datt', 'dathanhtoan', 'trangthaithanhtoan', 'tinhtrangthanhtoan'),
	);
	$containsRules = array(
		'ten'      => array('has' => array('hovaten')),
		'cccd'     => array('has' => array('cccd')),
		'gv'       => array('has' => array('phanxe')),
		'nhom'     => array('has' => array('nhom')),
		'ngaysinh' => array('has' => array('sinh')),
		'nguoinop' => array('has' => array('nguoinop')),
		'datt'     => array('has' => array('datt')),
	);

	list($headerRow, $map, $headerScore) = xd_detect_header($sheet, $highestRow, $highestColIndex, $aliasGroups, $containsRules);

	// Chỉ giả định thứ tự cột mẫu khi hoàn toàn không nhận diện được tiêu đề nào.
	if($headerScore <= 0)
	{
		$map = array('ten' => 1, 'khoa' => 2, 'ngaysinh' => 3, 'cccd' => 4, 'nguoinop' => 5, 'gv' => 6, 'nhom' => 7, 'datt' => 8);
		$headerRow = 1;
	}

	// Nếu chưa nhận diện được cột nhóm theo tiêu đề (vd tiêu đề "GHI CHÚ" mơ hồ),
	// chọn cột có nhiều giá trị thuộc {BT, CK, DAT} nhất trong vùng dữ liệu.
	if(!isset($map['nhom']))
	{
		$bestCol = -1; $bestHits = 0;
		$usedCols = array_flip($map);
		$scanTo = min($highestRow, $headerRow + 60);
		for($col = 0; $col < $highestColIndex; $col++)
		{
			if(isset($usedCols[$col])) continue;
			$hits = 0;
			for($row = $headerRow + 1; $row <= $scanTo; $row++)
			{
				$v = strtoupper(trim(xd_cell($sheet, $col, $row)));
				if(in_array($v, array('BT', 'CK', 'DAT'), true)) $hits++;
			}
			if($hits > $bestHits) { $bestHits = $hits; $bestCol = $col; }
		}
		if($bestCol >= 0 && $bestHits > 0) $map['nhom'] = $bestCol;
	}

	// Có thể tồn tại nhiều cột cùng tiêu đề "Ngày ... sinh" (một cột hiển thị, một cột dữ liệu).
	// Chọn cột ngày sinh có NHIỀU giá trị nhất trong vùng dữ liệu để tránh chọn nhầm cột rỗng.
	{
		$sinhCols = array();
		for($col = 0; $col < $highestColIndex; $col++)
		{
			if(strpos(xd_norm_header($sheet->getCellByColumnAndRow($col, $headerRow)->getValue()), 'sinh') !== false) $sinhCols[] = $col;
		}
		if(count($sinhCols) > 1)
		{
			$bestCol = isset($map['ngaysinh']) ? $map['ngaysinh'] : $sinhCols[0]; $bestHits = -1;
			$scanTo = min($highestRow, $headerRow + 60);
			foreach($sinhCols as $col)
			{
				$hits = 0;
				// Dùng giá trị THÔ (serial) vì readDataOnly khiến getFormattedValue rỗng với ô ngày.
				for($row = $headerRow + 1; $row <= $scanTo; $row++) { $rv = xd_cell_raw($sheet, $col, $row); if($rv !== null && $rv !== '') $hits++; }
				if($hits > $bestHits) { $bestHits = $hits; $bestCol = $col; }
			}
			$map['ngaysinh'] = $bestCol;
		}
	}

	$username = xd_username();
	$rows = array();
	$errors = array();
	$seenInFile = array();

	// Bắt buộc xác định được các cột cốt lõi, tránh đọc nhầm hoặc lưu thiếu dữ liệu
	if(!isset($map['cccd']))
	{
		xd_hocvien_import_error("Không xác định được cột CCCD học viên trong file. Vui lòng đặt tên cột chứa chuỗi 'CCCD' (ví dụ: Số CCCD/CC) và thử lại.", $backUrl);
	}
	if(!isset($map['ten']))
	{
		xd_hocvien_import_error("Không xác định được cột Họ tên học viên trong file. Vui lòng đặt tên cột chứa 'Họ và tên' và thử lại.", $backUrl);
	}
	if(!isset($map['gv']))
	{
		xd_hocvien_import_error("Không xác định được cột Giáo viên trong file. Vui lòng đặt tên cột chứa 'Giáo viên' và thử lại.", $backUrl);
	}

	$emptyStreak = 0;
	for($row = $headerRow + 1; $row <= $highestRow; $row++)
	{
		$ten = xd_val($sheet, $map, 'ten', $row);
		$khoa = xd_val($sheet, $map, 'khoa', $row);
		$ngaysinh = isset($map['ngaysinh']) ? xd_date_from_cell($sheet, $map['ngaysinh'], $row) : '';
		if($ngaysinh === null) $ngaysinh = '';
		$cccdRaw = xd_val($sheet, $map, 'cccd', $row);
		$cccd = xd_normalize_cccd($cccdRaw);
		$nguoinop = xd_val($sheet, $map, 'nguoinop', $row);
		$gvten = xd_val($sheet, $map, 'gv', $row);
		$gvkey = xd_gv_key($gvten);
		$nhom = strtoupper(trim(xd_val($sheet, $map, 'nhom', $row)));
		// Cột "đã tt": có chữ "r" nghĩa là đã thanh toán, ngược lại là chưa
		$dattRaw = isset($map['datt']) ? strtolower(trim(xd_val($sheet, $map, 'datt', $row))) : '';
		$daTT = ($dattRaw !== '' && strpos($dattRaw, 'r') !== false);

		if($ten === '' && $cccd === '')
		{
			// Dừng sớm khi gặp nhiều dòng trống liên tiếp (file chuyển đổi có thể có vùng dùng đến hết bảng)
			if(++$emptyStreak > 50) break;
			continue; // dòng trống
		}
		$emptyStreak = 0;

		if($cccd === '')
		{
			$errors[] = "Dòng $row: thiếu số CCCD học viên.";
			continue;
		}
		if($ten === '')
		{
			$errors[] = "Dòng $row: thiếu họ tên học viên.";
			continue;
		}
		if($gvten === '')
		{
			$errors[] = "Dòng $row: thiếu tên giáo viên phụ trách.";
			continue;
		}
		if(!in_array($nhom, array('BT', 'CK', 'DAT'), true))
		{
			$errors[] = "Dòng $row: nhóm không hợp lệ (chỉ nhận BT, CK, DAT). Giá trị nhận được: '".htmlspecialchars($nhom)."'.";
			continue;
		}

		// Trùng trong chính file
		if(isset($seenInFile[$cccd]))
		{
			$errors[] = "Dòng $row: CCCD $cccd bị lặp trong file (đã xuất hiện ở dòng ".$seenInFile[$cccd].").";
			continue;
		}
		$seenInFile[$cccd] = $row;

		$rows[] = array(
			'row' => $row, 'ten' => $ten, 'khoa' => $khoa, 'ngaysinh' => $ngaysinh, 'cccd' => $cccd,
			'nguoinop' => $nguoinop, 'nhom' => $nhom, 'gvten' => $gvten, 'gvkey' => $gvkey, 'datt' => $daTT, 'existing_id' => 0
		);
	}

	// Học viên đã có nhưng chưa thanh toán được phép import lại để cập nhật trạng thái.
	foreach($rows as &$r)
	{
		$variants = xd_cccd_variants($r['cccd']);
		$placeholders = implode(',', array_fill(0, count($variants), '?'));
		$existing = $d->rawQueryOne("select id, ngay_thanh_toan from #_xd_hocvien where cccd in ($placeholders) limit 0,1", $variants);
		if($existing && isset($existing['id']))
		{
			if($existing['ngay_thanh_toan'] !== null)
				$errors[] = "Dòng ".$r['row'].": CCCD ".$r['cccd']." đã thanh toán, không thể import ghi đè.";
			else
				$r['existing_id'] = (int)$existing['id'];
		}
	}
	unset($r);

	if(!empty($errors))
	{
		xd_hocvien_import_error("Không lưu file do có lỗi trùng lặp/không hợp lệ (đã chặn toàn bộ):<br>".implode("<br>", array_slice($errors, 0, 20)), $backUrl);
	}

	if(empty($rows)) xd_hocvien_import_error("File không có dòng học viên hợp lệ nào.", $backUrl);

	// Ghi all-or-nothing
	$inserted = 0;
	$updated = 0;
	$paidCount = 0;
	$failRows = array();
	$today = date('Y-m-d');
	$config = getXdConfig($d);
	$d->startTransaction();
	foreach($rows as $r)
	{
		// Học viên đã thanh toán trước đó (cột "đã tt" có chữ "r") -> đánh dấu ngày TT + phí theo nhóm để loại khỏi thuật toán lọc
		$daTT = !empty($r['datt']);
		$ngayTT  = $daTT ? $today : null;
		$dinhMuc = $daTT ? (int)$config['dinh_muc'] : 0;
		$soTien  = $daTT ? (int)xdMucTheoNhom($config, $r['nhom']) : 0;
		if($r['existing_id'] > 0)
		{
			$ok = $d->rawQuery(
				"update #_xd_hocvien set ho_ten = ?, cccd = ?, ngaysinh = ?, khoa = ?, nhom = ?, nguoi_nop = ?, gv_hoten = ?, gv_key = ?, dinh_muc = ?, so_tien_thanh_toan = ?, ngay_thanh_toan = ?, id_bangke = 0 where id = ? and ngay_thanh_toan is null",
				array($r['ten'], $r['cccd'], $r['ngaysinh'], $r['khoa'], $r['nhom'], $r['nguoinop'], $r['gvten'], $r['gvkey'], $dinhMuc, $soTien, $ngayTT, $r['existing_id'])
			);
		}
		else
		{
			$ok = $d->rawQuery(
				"insert into #_xd_hocvien (ho_ten, cccd, ngaysinh, khoa, nhom, nguoi_nop, gv_cccd, gv_hoten, gv_key, dinh_muc, so_tien_thanh_toan, ngay_thanh_toan, id_bangke, ngaytao, user_tao) values (?, ?, ?, ?, ?, ?, '', ?, ?, ?, ?, ?, 0, ?, ?)",
				array($r['ten'], $r['cccd'], $r['ngaysinh'], $r['khoa'], $r['nhom'], $r['nguoinop'], $r['gvten'], $r['gvkey'], $dinhMuc, $soTien, $ngayTT, time(), $username)
			);
		}
		if($ok === false) $failRows[] = $r['row'];
		else { if($r['existing_id'] > 0) $updated++; else $inserted++; if($ngayTT !== null) $paidCount++; }
	}

	if(!empty($failRows))
	{
		$d->rollback();
		xd_hocvien_import_error("Import thất bại khi lưu dữ liệu (không lưu dòng nào). Ví dụ dòng: ".implode(', ', array_slice($failRows, 0, 10)), $backUrl);
	}

	$d->commit();
	$msg = "Import thành công $inserted học viên mới";
	if($updated > 0) $msg .= "; cập nhật $updated học viên đã có";
	$msg .= ".";
	if($paidCount > 0) $msg .= " Trong đó $paidCount học viên đã thanh toán (cột \"đã tt\" = r).";
	$func->transfer($msg, "index.php?com=xangdau&act=hocvien");
}

function xd_hocvien_import_error($message, $backUrl)
{
	if(session_status() !== PHP_SESSION_ACTIVE) @session_start();
	$_SESSION['xd_hocvien_import_error'] = $message;
	header('Location: '.$backUrl);
	exit();
}

/* ============================ Thuật toán lọc thanh toán ============================ */

/**
 * Chạy thuật toán lọc, trả về danh sách học viên được chọn (chưa ghi ngày TT).
 * @return array [selected(list hocvien), summaryByGv, config]
 */
function xd_run_algorithm($d, $ky = '', $fromDate = '', $toDate = '')
{
	$config = getXdConfig($d);
	$dinhMuc = max(1, (int)$config['dinh_muc']); // tránh chia 0

	// Tổng hóa đơn hợp lệ (chưa quyết toán) theo GV (định danh theo TÊN chuẩn hóa gv_key)
	$hoadonWhere = " and da_quyettoan = 0";
	$hoadonParams = array();
	if($ky !== '') { $hoadonWhere .= " and ky = ?"; $hoadonParams[] = $ky; }
	if($fromDate !== '') { $hoadonWhere .= " and ngay_hoa_don >= ?"; $hoadonParams[] = $fromDate; }
	if($toDate !== '') { $hoadonWhere .= " and ngay_hoa_don <= ?"; $hoadonParams[] = $toDate; }

	$sumRows = $d->rawQuery(
		"select gv_key, max(gv_hoten) as gv_hoten, sum(tong_tien) as s_hd, count(*) as so_hd
		 from #_xd_hoadon where gv_key <> '' $hoadonWhere group by gv_key",
		$hoadonParams
	);

	$selected = array();
	$summary = array();

	if(empty($sumRows)) return array($selected, $summary, $config);

	foreach($sumRows as $g)
	{
		$gvKey = $g['gv_key'];
		$sHd = (float)$g['s_hd'];
		$n = (int)floor($sHd / $dinhMuc);

		$row = array(
			'gv_key' => $gvKey,
			'gv_hoten' => $g['gv_hoten'],
			's_hd' => $sHd,
			'so_hd' => (int)$g['so_hd'],
			'n_max' => $n,
			'so_hv_chon' => 0,
			'tong_chi' => 0.0,
			'dinh_muc_toi_da' => $n * $dinhMuc,
			'chenh_lech' => $sHd - ($n * $dinhMuc),
			'ke_toan_kiem_tra' => 0,
			'quan_ly_duyet' => 0,
		);
		$status = $d->rawQueryOne("select min(ke_toan_kiem_tra) as ke_toan_kiem_tra, min(quan_ly_duyet) as quan_ly_duyet from #_xd_hoadon where gv_key = ? and da_quyettoan = 0", array($gvKey));
		if($status) { $row['ke_toan_kiem_tra'] = (int)$status['ke_toan_kiem_tra']; $row['quan_ly_duyet'] = (int)$status['quan_ly_duyet']; }

		if($n > 0)
		{
			$hocviens = $d->rawQuery(
				"select id, ho_ten, cccd, nhom, gv_key, gv_hoten from #_xd_hocvien
				 where gv_key = ? and ngay_thanh_toan is null order by id asc limit ".(int)$n,
				array($gvKey)
			);
			if(!empty($hocviens))
			{
				foreach($hocviens as $hv)
				{
					$soTien = xdMucTheoNhom($config, $hv['nhom']);
					$hv['dinh_muc'] = $dinhMuc;
					$hv['so_tien_thanh_toan'] = $soTien;
					$selected[] = $hv;
					$row['so_hv_chon']++;
					$row['tong_chi'] += $soTien;
				}
			}
		}

		$summary[] = $row;
	}

	return array($selected, $summary, $config);
}

function xd_loc_params_url($gvKey = '')
{
	$url = "index.php?com=xangdau&act=loc";
	foreach(array('ky', 'from_date', 'to_date') as $key) if(isset($_REQUEST[$key]) && $_REQUEST[$key] !== '') $url .= '&'.$key.'='.urlencode($_REQUEST[$key]);
	if($gvKey !== '') $url .= '&gv_key='.urlencode($gvKey);
	return $url;
}

function xd_get_giao_vien_detail()
{
	global $d, $xd_detail_gv, $xd_detail_hoadons, $xd_detail_hocviens, $xd_detail_config;
	$gvKey = isset($_REQUEST['gv_key']) ? trim((string)$_REQUEST['gv_key']) : '';
	$xd_detail_gv = array('gv_key' => $gvKey, 'gv_hoten' => $gvKey);
	$xd_detail_hoadons = array(); $xd_detail_hocviens = array(); $xd_detail_config = getXdConfig($d);
	if($gvKey === '') return;
	$row = $d->rawQueryOne("select max(gv_hoten) as gv_hoten from #_xd_hoadon where gv_key = ?", array($gvKey));
	if($row && $row['gv_hoten'] !== '') $xd_detail_gv['gv_hoten'] = $row['gv_hoten'];
	$xd_detail_hoadons = $d->rawQuery("select * from #_xd_hoadon where gv_key = ? and da_quyettoan = 0 order by ngay_hoa_don desc, id desc", array($gvKey));
	$xd_detail_hocviens = $d->rawQuery("select * from #_xd_hocvien where gv_key = ? and ngay_thanh_toan is null order by id asc", array($gvKey));
}

function xd_kiem_tra_giao_vien()
{
	global $d, $func;
	$gvKey = isset($_REQUEST['gv_key']) ? trim((string)$_REQUEST['gv_key']) : '';
	if($gvKey === '') $func->transfer("Không xác định được giáo viên.", xd_loc_params_url(), false);
	$d->rawQuery("update #_xd_hoadon set ke_toan_kiem_tra = 1 where gv_key = ? and da_quyettoan = 0", array($gvKey));
	$d->rawQuery("update #_xd_hocvien set ke_toan_kiem_tra = 1 where gv_key = ? and ngay_thanh_toan is null", array($gvKey));
	$func->transfer("Đã ghi nhận kế toán kiểm tra giáo viên.", xd_loc_params_url(), true);
}

function xd_duyet_giao_vien()
{
	global $d, $func;
	$gvKey = isset($_REQUEST['gv_key']) ? trim((string)$_REQUEST['gv_key']) : '';
	$ky = isset($_REQUEST['ky']) ? trim((string)$_REQUEST['ky']) : '';
	$fromDate = (isset($_REQUEST['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['from_date'])) ? $_REQUEST['from_date'] : '';
	$toDate = (isset($_REQUEST['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['to_date'])) ? $_REQUEST['to_date'] : '';
	if($gvKey === '') $func->transfer("Không xác định được giáo viên.", xd_loc_params_url(), false);
	$notChecked = $d->rawQueryOne("select count(*) as total from #_xd_hoadon where gv_key = ? and da_quyettoan = 0 and ke_toan_kiem_tra = 0", array($gvKey));
	if($notChecked && (int)$notChecked['total'] > 0) $func->transfer("Kế toán chưa kiểm tra hết hóa đơn của giáo viên.", xd_loc_params_url(), false);
	$notCheckedStudents = $d->rawQueryOne("select count(*) as total from #_xd_hocvien where gv_key = ? and ngay_thanh_toan is null and ke_toan_kiem_tra = 0", array($gvKey));
	if($notCheckedStudents && (int)$notCheckedStudents['total'] > 0) $func->transfer("Kế toán chưa kiểm tra hết học viên của giáo viên.", xd_loc_params_url(), false);
	list($selected, $summary, $config) = xd_run_algorithm($d, $ky, $fromDate, $toDate);
	$selectedTeacher = array(); foreach($selected as $student) if($student['gv_key'] === $gvKey) $selectedTeacher[] = $student;
	if(empty($selectedTeacher)) $func->transfer("Không có học viên đủ điều kiện để duyệt cho giáo viên này.", xd_loc_params_url(), false);
	$today = date('Y-m-d'); $username = xd_username(); $total = 0; foreach($selectedTeacher as $student) $total += (float)$student['so_tien_thanh_toan'];
	$d->startTransaction();
	$ok = $d->rawQuery("insert into #_xd_bangke (ngay_lap, ky, tong_hocvien, tong_tien, user_tao, ngaytao) values (?, ?, ?, ?, ?, ?)", array($today, $ky, count($selectedTeacher), $total, $username, time()));
	if($ok === false) { $d->rollback(); $func->transfer("Không tạo được đợt duyệt.", xd_loc_params_url(), false); }
	$idBangke = (int)$d->getLastInsertId();
	foreach($selectedTeacher as $student) $d->rawQuery("update #_xd_hocvien set ngay_thanh_toan = ?, dinh_muc = ?, so_tien_thanh_toan = ?, id_bangke = ?, quan_ly_duyet = 1 where id = ? and ngay_thanh_toan is null", array($today, $student['dinh_muc'], $student['so_tien_thanh_toan'], $idBangke, (int)$student['id']));
	$invoiceWhere = 'gv_key = ? and da_quyettoan = 0'; $params = array($idBangke, $gvKey);
	if($ky !== '') { $invoiceWhere .= ' and ky = ?'; $params[] = $ky; }
	if($fromDate !== '') { $invoiceWhere .= ' and ngay_hoa_don >= ?'; $params[] = $fromDate; }
	if($toDate !== '') { $invoiceWhere .= ' and ngay_hoa_don <= ?'; $params[] = $toDate; }
	$d->rawQuery("update #_xd_hoadon set da_quyettoan = 1, quan_ly_duyet = 1, id_bangke = ? where $invoiceWhere", $params);
	$d->commit();
	$func->transfer("Đã duyệt và ghi nhận thanh toán cho giáo viên.", xd_loc_params_url(), true);
}

function xd_duyet_tat_ca_giao_vien()
{
	global $d, $func;
	$ky = isset($_REQUEST['ky']) ? trim((string)$_REQUEST['ky']) : '';
	$fromDate = (isset($_REQUEST['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['from_date'])) ? $_REQUEST['from_date'] : '';
	$toDate = (isset($_REQUEST['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['to_date'])) ? $_REQUEST['to_date'] : '';
	$rows = $d->rawQuery("select distinct gv_key from #_xd_hoadon where gv_key <> '' and da_quyettoan = 0 and ke_toan_kiem_tra = 1", array());
	$approved = 0;
	foreach($rows as $row)
	{
		$gvKey = $row['gv_key'];
		$notCheckedStudents = $d->rawQueryOne("select count(*) as total from #_xd_hocvien where gv_key = ? and ngay_thanh_toan is null and ke_toan_kiem_tra = 0", array($gvKey));
		if($notCheckedStudents && (int)$notCheckedStudents['total'] > 0) continue;
		list($selected, $summary, $config) = xd_run_algorithm($d, $ky, $fromDate, $toDate);
		$students = array(); foreach($selected as $student) if($student['gv_key'] === $gvKey) $students[] = $student;
		if(empty($students)) continue;
		$today = date('Y-m-d'); $username = xd_username(); $total = 0; foreach($students as $student) $total += (float)$student['so_tien_thanh_toan'];
		$d->startTransaction();
		$ok = $d->rawQuery("insert into #_xd_bangke (ngay_lap, ky, tong_hocvien, tong_tien, user_tao, ngaytao) values (?, ?, ?, ?, ?, ?)", array($today, $ky, count($students), $total, $username, time()));
		if($ok === false) { $d->rollback(); continue; }
		$idBangke = (int)$d->getLastInsertId();
		foreach($students as $student) $d->rawQuery("update #_xd_hocvien set ngay_thanh_toan = ?, dinh_muc = ?, so_tien_thanh_toan = ?, id_bangke = ?, quan_ly_duyet = 1 where id = ? and ngay_thanh_toan is null", array($today, $student['dinh_muc'], $student['so_tien_thanh_toan'], $idBangke, (int)$student['id']));
		$invoiceWhere = 'gv_key = ? and da_quyettoan = 0'; $invoiceParams = array($idBangke, $gvKey);
		if($ky !== '') { $invoiceWhere .= ' and ky = ?'; $invoiceParams[] = $ky; }
		if($fromDate !== '') { $invoiceWhere .= ' and ngay_hoa_don >= ?'; $invoiceParams[] = $fromDate; }
		if($toDate !== '') { $invoiceWhere .= ' and ngay_hoa_don <= ?'; $invoiceParams[] = $toDate; }
		$d->rawQuery("update #_xd_hoadon set da_quyettoan = 1, quan_ly_duyet = 1, id_bangke = ? where $invoiceWhere", $invoiceParams);
		$d->commit(); $approved++;
	}
	$func->transfer("Đã duyệt $approved giáo viên.", xd_loc_params_url(), true);
}

function xd_loc_preview()
{
	global $d, $xd_loc_selected, $xd_loc_summary, $xd_loc_config, $xd_loc_ky, $xd_loc_from, $xd_loc_to, $xd_loc_ky_options;

	$xd_loc_ky = isset($_REQUEST['ky']) ? trim((string)$_REQUEST['ky']) : '';
	$xd_loc_from = (isset($_REQUEST['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['from_date'])) ? $_REQUEST['from_date'] : '';
	$xd_loc_to = (isset($_REQUEST['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['to_date'])) ? $_REQUEST['to_date'] : '';

	$xd_loc_ky_options = $d->rawQuery("select distinct ky from #_xd_hoadon where ky <> '' and da_quyettoan = 0 order by ky asc");

	list($selected, $summary, $config) = xd_run_algorithm($d, $xd_loc_ky, $xd_loc_from, $xd_loc_to);
	$xd_loc_selected = $selected;
	$xd_loc_summary = $summary;
	$xd_loc_config = $config;
}

function xd_xuat_bangke_giao_vien()
{
	global $d, $func;
	$gvKey = isset($_REQUEST['gv_key']) ? trim((string)$_REQUEST['gv_key']) : '';
	$ky = isset($_REQUEST['ky']) ? trim((string)$_REQUEST['ky']) : '';
	$fromDate = (isset($_REQUEST['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['from_date'])) ? $_REQUEST['from_date'] : '';
	$toDate = (isset($_REQUEST['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['to_date'])) ? $_REQUEST['to_date'] : '';
	if($gvKey === '') $func->transfer("Không xác định được giáo viên cần xuất.", "index.php?com=xangdau&act=loc", false);
	list($selected, $summary) = xd_run_algorithm($d, $ky, $fromDate, $toDate);
	$teacherSelected = array();
	foreach($selected as $student) if($student['gv_key'] === $gvKey) $teacherSelected[] = $student;
	xd_export_bangke_excel($d, 0, date('Y-m-d'), $ky, $gvKey, $teacherSelected, $fromDate, $toDate);
}

function xd_xuat_tat_ca_bang_ke()
{
	global $d;
	$ky = isset($_REQUEST['ky']) ? trim((string)$_REQUEST['ky']) : '';
	$fromDate = (isset($_REQUEST['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['from_date'])) ? $_REQUEST['from_date'] : '';
	$toDate = (isset($_REQUEST['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['to_date'])) ? $_REQUEST['to_date'] : '';
	list($selected, $summary) = xd_run_algorithm($d, $ky, $fromDate, $toDate);
	xd_export_bangke_excel($d, 0, date('Y-m-d'), $ky, '', $selected, $fromDate, $toDate, true);
}

/* ============================ Xuất bảng kê & quyết toán ============================ */

function xd_xuat_bangke()
{
	global $d, $func;

	$ky = isset($_REQUEST['ky']) ? trim((string)$_REQUEST['ky']) : '';
	$fromDate = (isset($_REQUEST['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['from_date'])) ? $_REQUEST['from_date'] : '';
	$toDate = (isset($_REQUEST['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['to_date'])) ? $_REQUEST['to_date'] : '';

	$backUrl = "index.php?com=xangdau&act=loc";
	if($ky !== '') $backUrl .= '&ky='.urlencode($ky);

	list($selected, $summary, $config) = xd_run_algorithm($d, $ky, $fromDate, $toDate);

	if(empty($selected)) $func->transfer("Không có học viên nào đủ điều kiện thanh toán để xuất bảng kê.", $backUrl, false);

	$username = xd_username();
	$today = date('Y-m-d');
	$tongTien = 0.0;
	foreach($selected as $hv) $tongTien += (float)$hv['so_tien_thanh_toan'];

	// Quyết toán trong giao dịch
	$d->startTransaction();

	$okBangke = $d->rawQuery(
		"insert into #_xd_bangke (ngay_lap, ky, tong_hocvien, tong_tien, user_tao, ngaytao) values (?, ?, ?, ?, ?, ?)",
		array($today, $ky, count($selected), $tongTien, $username, time())
	);
	if($okBangke === false)
	{
		$d->rollback();
		$func->transfer("Không tạo được bảng kê. Vui lòng thử lại.", $backUrl, false);
	}
	$idBangke = (int)$d->getLastInsertId();

	// Ghi ngày thanh toán cho học viên được chọn (điều kiện lại ngay_thanh_toan is null để chống race)
	foreach($selected as $hv)
	{
		$d->rawQuery(
			"update #_xd_hocvien set ngay_thanh_toan = ?, dinh_muc = ?, so_tien_thanh_toan = ?, id_bangke = ? where id = ? and ngay_thanh_toan is null",
			array($today, $hv['dinh_muc'], $hv['so_tien_thanh_toan'], $idBangke, (int)$hv['id'])
		);
	}

	// Khóa hóa đơn đã dùng của các GV có học viên được trích (theo gv_key)
	$gvList = array();
	foreach($selected as $hv) $gvList[$hv['gv_key']] = 1;
	foreach(array_keys($gvList) as $gvKey)
	{
		if($gvKey === '') continue;
		$hoadonWhere = " and da_quyettoan = 0";
		$params = array($idBangke, $gvKey);
		if($ky !== '') { $hoadonWhere .= " and ky = ?"; $params[] = $ky; }
		if($fromDate !== '') { $hoadonWhere .= " and ngay_hoa_don >= ?"; $params[] = $fromDate; }
		if($toDate !== '') { $hoadonWhere .= " and ngay_hoa_don <= ?"; $params[] = $toDate; }
		$d->rawQuery("update #_xd_hoadon set da_quyettoan = 1, id_bangke = ? where gv_key = ? $hoadonWhere", $params);
	}

	$d->commit();

	// Xuất file Excel bảng kê (mỗi giáo viên một sheet, dựng lại từ dữ liệu đã quyết toán của đợt)
	xd_export_bangke_excel($d, $idBangke, $today, $ky);
	// xd_export_bangke_excel sẽ exit sau khi stream file
}

function xd_export_bangke_excel($d, $idBangke, $today, $ky, $onlyGvKey = '', $previewSelected = array(), $fromDate = '', $toDate = '', $allPreview = false)
{
	require_once LIBRARIES.'PHPExcel.php';

	$setting = $d->rawQueryOne("select tenvi from #_setting limit 0,1");
	$companyName = (!empty($setting['tenvi'])) ? (function_exists('mb_strtoupper') ? mb_strtoupper($setting['tenvi'], 'UTF-8') : strtoupper($setting['tenvi'])) : 'TRUNG TÂM GIÁO DỤC NGHỀ NGHIỆP';

	// Lấy hóa đơn và học viên của đợt, gom theo giáo viên (gv_key)
	if($idBangke > 0)
	{
		$hoadons = $d->rawQuery("select * from #_xd_hoadon where id_bangke = ? order by gv_hoten asc, ngay_hoa_don asc, id asc", array($idBangke));
		$hocviens = $d->rawQuery("select * from #_xd_hocvien where id_bangke = ? order by gv_hoten asc, id asc", array($idBangke));
	}
	else
	{
		$invoiceWhere = $allPreview ? ' where da_quyettoan = 0' : ' where gv_key = ?'; $invoiceParams = $allPreview ? array() : array($onlyGvKey);
		if($ky !== '') { $invoiceWhere .= ' and ky = ?'; $invoiceParams[] = $ky; }
		if($fromDate !== '') { $invoiceWhere .= ' and ngay_hoa_don >= ?'; $invoiceParams[] = $fromDate; }
		if($toDate !== '') { $invoiceWhere .= ' and ngay_hoa_don <= ?'; $invoiceParams[] = $toDate; }
		$hoadons = $d->rawQuery("select * from #_xd_hoadon $invoiceWhere order by gv_hoten asc, ngay_hoa_don asc, id asc", $invoiceParams);
		$hocviens = $previewSelected;
	}

	$hdByGv = array();
	$gvTen = array();
	if(!empty($hoadons)) foreach($hoadons as $h) { $hdByGv[$h['gv_key']][] = $h; $gvTen[$h['gv_key']] = $h['gv_hoten']; }
	$hvByGv = array();
	if(!empty($hocviens)) foreach($hocviens as $h) { $hvByGv[$h['gv_key']][] = $h; if(!isset($gvTen[$h['gv_key']])) $gvTen[$h['gv_key']] = $h['gv_hoten']; }

	// Danh sách GV = các GV có học viên được trích (theo thứ tự tên)
	$gvKeys = array_keys($hvByGv);
	usort($gvKeys, function($a, $b) use ($gvTen) { return strcmp((string)$gvTen[$a], (string)$gvTen[$b]); });
	if(empty($gvKeys)) $gvKeys = array_keys($gvTen);

	$objPHPExcel = new PHPExcel();
	$objPHPExcel->removeSheetByIndex(0);

	$thin = PHPExcel_Style_Border::BORDER_THIN;
	$usedTitles = array();
	$sheetIndex = 0;

	foreach($gvKeys as $gvKey)
	{
		$ten = isset($gvTen[$gvKey]) ? $gvTen[$gvKey] : $gvKey;

		// Tên sheet hợp lệ (<=31 ký tự, không ký tự cấm, không trùng)
		$title = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', ' ', (string)$ten);
		$title = trim(xd_mb_sub($title, 0, 28));
		if($title === '') $title = 'GV';
		$baseTitle = $title; $k = 1;
		while(isset($usedTitles[$title])) { $title = xd_mb_sub($baseTitle, 0, 26).' '.(++$k); }
		$usedTitles[$title] = 1;

		$ws = new PHPExcel_Worksheet($objPHPExcel, $title);
		$objPHPExcel->addSheet($ws, $sheetIndex++);
		$ws->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(11);
		$ws->getDefaultRowDimension()->setRowHeight(20);
		$ws->setShowGridlines(false);
		$tableBorder = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => '000000'))));
		foreach(array('A'=>8, 'B'=>16, 'C'=>28, 'D'=>22, 'E'=>16, 'F'=>18, 'G'=>18, 'H'=>16) as $column => $width)
			$ws->getColumnDimension($column)->setWidth($width);
		$ws->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
		$ws->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);

		// ---- Tiêu đề ----
		$ws->setCellValue('A1', $companyName);
		$ws->mergeCells('A1:H1');
		$ws->setCellValue('A2', 'BẢNG KÊ TRÍCH CHI PHÍ NHIÊN LIỆU - Số: '.($idBangke > 0 ? $idBangke : '..........'));
		$ws->mergeCells('A2:H2');
		$ws->setCellValue('A3', 'Giáo viên: '.$ten);
		$ws->mergeCells('A3:H3');
		$ws->setCellValue('A4', 'Ngày quyết toán: '.date('d/m/Y', strtotime($today)).($ky !== '' ? '    -    Kỳ: '.$ky : ''));
		$ws->mergeCells('A4:H4');
		$ws->getStyle('A1:H4')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$ws->getStyle('A1:H4')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$ws->getStyle('A1:H2')->getFont()->setBold(true);
		$ws->getStyle('A1')->getFont()->setSize(13);
		$ws->getStyle('A2')->getFont()->setSize(12);
		$ws->getRowDimension(1)->setRowHeight(24);
		$ws->getRowDimension(2)->setRowHeight(24);

		// ---- Bảng Nội dung (hóa đơn) ----
		$r = 6;
		$ws->setCellValue('A'.$r, 'Nội dung'); $ws->mergeCells('A'.$r.':G'.$r);
		$ws->getStyle('A'.$r)->getFont()->setBold(true);
		$ws->getStyle('A'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$r++;
		$hdHeadRow = $r;
		$hdHeaders = array('STT', 'Số hóa đơn', 'Ngày', 'Thông tin bán hàng', 'Chi tiết', 'Số tiền HĐ', 'Biển số xe');
		$col = 'A';
		foreach($hdHeaders as $h) { $ws->setCellValue($col.$r, $h); $col++; }
		$ws->getStyle('A'.$r.':G'.$r)->getFont()->setBold(true);
		$ws->getStyle('A'.$r.':G'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$r++;

		$stt = 1; $tongHd = 0.0;
		$listHd = isset($hdByGv[$gvKey]) ? $hdByGv[$gvKey] : array();
		foreach($listHd as $h)
		{
			$ws->setCellValueExplicit('A'.$r, $stt, PHPExcel_Cell_DataType::TYPE_NUMERIC);
			$ws->setCellValueExplicit('B'.$r, $h['ma_hoa_don'], PHPExcel_Cell_DataType::TYPE_STRING);
			$ws->setCellValue('C'.$r, $h['ngay_hoa_don'] ? date('d/m/Y', strtotime($h['ngay_hoa_don'])) : '');
			$ws->setCellValue('D'.$r, isset($h['thong_tin_ban_hang']) ? $h['thong_tin_ban_hang'] : '');
			$ws->setCellValue('E'.$r, isset($h['chi_tiet']) ? $h['chi_tiet'] : '');
			$ws->setCellValueExplicit('F'.$r, (int)round((float)$h['tong_tien']), PHPExcel_Cell_DataType::TYPE_NUMERIC);
			$ws->setCellValue('G'.$r, isset($h['bien_so']) ? $h['bien_so'] : '');
			$tongHd += (float)$h['tong_tien'];
			$stt++; $r++;
		}
		$ws->setCellValue('E'.$r, 'Tổng cộng');
		$ws->getStyle('E'.$r)->getFont()->setBold(true);
		$ws->setCellValueExplicit('F'.$r, (int)round($tongHd), PHPExcel_Cell_DataType::TYPE_NUMERIC);
		$ws->getStyle('F'.$r)->getFont()->setBold(true);
		$ws->getStyle('A'.$hdHeadRow.':G'.$r)->applyFromArray($tableBorder);
		$ws->getStyle('F'.($hdHeadRow + 1).':F'.$r)->getNumberFormat()->setFormatCode('#,##0');
		$ws->getStyle('A'.$hdHeadRow.':G'.$r)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$ws->getStyle('A'.$hdHeadRow.':C'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$ws->getStyle('E'.$hdHeadRow.':G'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

		// ---- Bảng Danh sách học viên ----
		$r += 2;
		$ws->setCellValue('A'.$r, 'DANH SÁCH HỌC VIÊN'); $ws->mergeCells('A'.$r.':H'.$r);
		$ws->getStyle('A'.$r)->getFont()->setBold(true);
		$ws->getStyle('A'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$r++;
		$hvHeadRow = $r;
		$hvHeaders = array('STT', 'Khóa', 'Họ tên học viên', 'CCCD/CC', 'Năm sinh', 'Định mức', 'Số tiền thanh toán', 'Nhóm');
		$col = 'A';
		foreach($hvHeaders as $h) { $ws->setCellValue($col.$r, $h); $col++; }
		$ws->getStyle('A'.$r.':H'.$r)->getFont()->setBold(true);
		$r++;

		$stt = 1; $tongDinhMuc = 0.0; $tongTt = 0.0;
		$listHv = isset($hvByGv[$gvKey]) ? $hvByGv[$gvKey] : array();
		foreach($listHv as $hv)
		{
			$namSinh = (!empty($hv['ngaysinh']) && strtotime($hv['ngaysinh']) !== false) ? date('d/m/Y', strtotime($hv['ngaysinh'])) : (string)$hv['ngaysinh'];
			$ws->setCellValueExplicit('A'.$r, $stt, PHPExcel_Cell_DataType::TYPE_NUMERIC);
			$ws->setCellValue('B'.$r, isset($hv['khoa']) ? $hv['khoa'] : '');
			$ws->setCellValue('C'.$r, $hv['ho_ten']);
			$ws->setCellValueExplicit('D'.$r, $hv['cccd'], PHPExcel_Cell_DataType::TYPE_STRING);
			$ws->setCellValue('E'.$r, $namSinh);
			$ws->setCellValueExplicit('F'.$r, (int)round((float)$hv['dinh_muc']), PHPExcel_Cell_DataType::TYPE_NUMERIC);
			$ws->setCellValueExplicit('G'.$r, (int)round((float)$hv['so_tien_thanh_toan']), PHPExcel_Cell_DataType::TYPE_NUMERIC);
			$ws->setCellValue('H'.$r, $hv['nhom']);
			$tongDinhMuc += (float)$hv['dinh_muc'];
			$tongTt += (float)$hv['so_tien_thanh_toan'];
			$stt++; $r++;
		}
		$ws->setCellValue('C'.$r, 'Tổng cộng');
		$ws->getStyle('C'.$r)->getFont()->setBold(true);
		$ws->setCellValueExplicit('F'.$r, (int)round($tongDinhMuc), PHPExcel_Cell_DataType::TYPE_NUMERIC);
		$ws->setCellValueExplicit('G'.$r, (int)round($tongTt), PHPExcel_Cell_DataType::TYPE_NUMERIC);
		$ws->getStyle('F'.$r.':G'.$r)->getFont()->setBold(true);
		$ws->getStyle('A'.$hvHeadRow.':H'.$r)->applyFromArray($tableBorder);
		$ws->getStyle('F'.($hvHeadRow + 1).':G'.$r)->getNumberFormat()->setFormatCode('#,##0');
		$ws->getStyle('A'.$hvHeadRow.':H'.$r)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$ws->getStyle('A'.$hvHeadRow.':B'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$ws->getStyle('D'.$hvHeadRow.':H'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

		// ---- Cam kết và chữ ký ----
		$r += 2;
		$ws->setCellValue('A'.$r, '-Tôi xin cam kết và chịu trách nhiệm về tính chính xác, hợp lệ của các thông tin, dữ liệu đào tạo và chứng từ liên quan trên.');
		$ws->mergeCells('A'.$r.':H'.$r);
		$ws->getStyle('A'.$r)->getAlignment()->setWrapText(true);
		$r += 2;
		$ws->setCellValue('A'.$r, 'Phòng Đào tạo');
		$ws->setCellValue('C'.$r, 'Kế Toán');
		$ws->setCellValue('F'.$r, 'Giáo viên quyết toán');
		$ws->mergeCells('A'.$r.':B'.$r);
		$ws->mergeCells('C'.$r.':E'.$r);
		$ws->mergeCells('F'.$r.':H'.$r);
		$ws->getStyle('A'.$r.':H'.$r)->getFont()->setBold(true);
		$ws->getStyle('A'.$r.':H'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		for($visibleRow = 1; $visibleRow <= $r; $visibleRow++) $ws->getRowDimension($visibleRow)->setVisible(true);
	}

	if($objPHPExcel->getSheetCount() === 0)
	{
		$ws = new PHPExcel_Worksheet($objPHPExcel, 'BangKe');
		$objPHPExcel->addSheet($ws, 0);
		$ws->setCellValue('A1', 'Không có dữ liệu bảng kê.');
	}

	$objPHPExcel->setActiveSheetIndex(0);

	$filename = 'bang_ke_trich_chi_phi_xd_'.$idBangke.'_'.date('Ymd').'.xlsx';

	while(ob_get_level() > 0) ob_end_clean();
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment; filename="'.$filename.'"');
	header('Cache-Control: max-age=0');

	$writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	$writer->save('php://output');
	exit;
}
