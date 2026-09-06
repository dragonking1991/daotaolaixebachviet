<?php
if(!defined('SOURCES')) die("Error");

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

function xd_username()
{
	global $login_admin;
	return isset($_SESSION[$login_admin]['username']) ? $_SESSION[$login_admin]['username'] : '';
}
