<?php
if(!defined('SOURCES')) die("Error");
require_once dirname(__DIR__,3).'/libraries/course_options.php';

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
	$replace = array('a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','d','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y');
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
	$replace = array('a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','d','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y');
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

function xd_hocvien_order_clause($byName = false)
{
	if($byName) return "substring_index(trim(ho_ten), ' ', -1) asc, trim(ho_ten) asc, id asc";
	return "lower(trim(khoa)) asc, lan_import asc, thu_tu_file asc, id asc";
}

function xd_username()
{
	global $login_admin;
	return isset($_SESSION[$login_admin]['username']) ? $_SESSION[$login_admin]['username'] : '';
}

/**
 * Đọc cột "hợp lệ" (note1) của file hóa đơn XD -> 0/1.
 * Cột này dùng dấu "x" để đánh dấu RÕ RÀNG là hợp lệ; để trống nghĩa là KHÔNG hợp lệ.
 * Ngoại lệ: nếu file hoàn toàn không có cột này (map thiếu 'hople') -> mặc định hợp lệ (1)
 * để không phá vỡ các file import cũ chưa từng có cột này.
 * Lưu ý: loại bỏ mọi khoảng trắng kể cả non-breaking space (\xC2\xA0 hay xuất hiện khi copy từ Excel/Web)
 * trước khi so sánh, và chỉ cần ô chứa chữ "x" (không phân biệt hoa/thường) là coi như đã đánh dấu.
 */
function xd_hople_from_cell($sheet, $map, $row)
{
	if(!isset($map['hople'])) return 1;
	$raw = (string)xd_val($sheet, $map, 'hople', $row);
	$clean = preg_replace('/[\s\x{00A0}]+/u', '', $raw);
	if($clean === '') return 0;
	$clean = xd_mb_lower($clean);
	if(strpos($clean, 'x') !== false) return 1;
	$validValues = array('co', 'có', '1', 'yes', 'true', 'dat', 'đạt', 'ok');
	return in_array($clean, $validValues, true) ? 1 : 0;
}

/* Sao lưu toàn bộ bảng XD ra JSON (thư mục backups/ bị chặn truy cập web) trước khi xóa hàng loạt. */
function xd_backup_table_json($table, $tag)
{
	global $d;
	if(!in_array($table, array('xd_hocvien', 'xd_hoadon'), true)) return '';
	$directory = dirname(dirname(dirname(__DIR__))).'/backups';
	if(!is_dir($directory) && !mkdir($directory, 0775, true)) return '';
	$rows = $d->rawQuery('select * from #_'.$table.' order by id');
	if(!is_array($rows)) return '';
	$file = $directory.'/xd_'.$tag.'_'.date('Ymd_His').'_'.bin2hex(random_bytes(4)).'.json';
	$json = json_encode(array('action' => 'delete_all', 'table' => $table, 'rows' => $rows), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	if($json === false || file_put_contents($file, $json, LOCK_EX) !== strlen($json)) return '';
	@chmod($file, 0600);
	/* Ảnh chụp trước import/hoàn tác: giữ 20 bản gần nhất mỗi loại để không đầy ổ đĩa. */
	if(strpos($tag, 'imp_') === 0 || $tag === 'pre_undo')
	{
		$same = glob($directory.'/xd_'.$tag.'_*.json') ?: array();
		if(count($same) > 20)
		{
			usort($same, function($a, $b) { return filemtime($b) - filemtime($a); });
			foreach(array_slice($same, 20) as $old) @unlink($old);
		}
	}
	return basename($file);
}

/* Hiển thị ngày theo dd/mm/yyyy cho mọi định dạng đã lưu (yyyy-mm-dd, d/m/yyyy, d-m-yyyy); giá trị lạ giữ nguyên. */
function xd_display_date($value)
{
	$value = trim((string)$value);
	if($value === '' || strpos($value, '0000-00-00') === 0) return '';
	if(preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})(?:[ T].*)?$/', $value, $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1]))
		return sprintf('%02d/%02d/%04d', $m[3], $m[2], $m[1]);
	if(preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/', $value, $m) && checkdate((int)$m[2], (int)$m[1], (int)$m[3]))
		return sprintf('%02d/%02d/%04d', $m[1], $m[2], $m[3]);
	return $value;
}
