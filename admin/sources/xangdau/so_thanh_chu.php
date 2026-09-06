<?php
if(!defined('SOURCES')) die("Error");

/* ============================ Đọc số tiền thành chữ (VNĐ) ============================ */

/**
 * Đọc 1 nhóm 3 chữ số thành chữ (vd "241" -> "hai trăm bốn mươi mốt").
 * $khongPhaiNhomDau: true nếu không phải nhóm cao nhất (cần đọc đủ "trăm" kể cả khi = 0).
 */
function xd_doc_ba_so($group, $khongPhaiNhomDau)
{
	$chuSo = array('không','một','hai','ba','bốn','năm','sáu','bảy','tám','chín');
	$tram = (int)$group[0];
	$chuc = (int)$group[1];
	$donvi = (int)$group[2];

	if($tram === 0 && $chuc === 0 && $donvi === 0) return '';

	$str = '';
	if($tram > 0 || $khongPhaiNhomDau) $str .= $chuSo[$tram].' trăm ';

	if($chuc === 0)
	{
		if($donvi > 0) $str .= (($tram > 0 || $khongPhaiNhomDau) ? 'lẻ ' : '').$chuSo[$donvi];
	}
	elseif($chuc === 1)
	{
		$str .= 'mười';
		if($donvi === 1) $str .= ' một';
		elseif($donvi === 5) $str .= ' lăm';
		elseif($donvi > 0) $str .= ' '.$chuSo[$donvi];
	}
	else
	{
		$str .= $chuSo[$chuc].' mươi';
		if($donvi === 1) $str .= ' mốt';
		elseif($donvi === 5) $str .= ' lăm';
		elseif($donvi > 0) $str .= ' '.$chuSo[$donvi];
	}

	return trim($str);
}

/**
 * Đọc số tiền (VNĐ, số nguyên không âm) thành chữ, dùng cho dòng "Bằng chữ" trên chứng từ.
 */
function xd_so_thanh_chu($number)
{
	$number = (int)round((float)$number);
	if($number <= 0) return 'Không đồng';

	$donVi = array('', ' nghìn', ' triệu', ' tỷ', ' nghìn tỷ', ' triệu tỷ');

	$str = (string)$number;
	$pad = (3 - (strlen($str) % 3)) % 3;
	$str = str_repeat('0', $pad).$str;
	$soNhom = strlen($str) / 3;

	$groups = array();
	for($i = 0; $i < $soNhom; $i++) $groups[] = substr($str, $i * 3, 3);

	$result = '';
	foreach($groups as $i => $group)
	{
		$text = xd_doc_ba_so($group, $i > 0);
		if($text !== '') $result .= $text.$donVi[$soNhom - 1 - $i].' ';
	}

	$result = trim(preg_replace('/\s+/', ' ', $result));
	if($result === '') return 'Không đồng';

	$firstLetter = function_exists('mb_strtoupper') ? mb_strtoupper(xd_mb_sub($result, 0, 1), 'UTF-8') : strtoupper(xd_mb_sub($result, 0, 1));
	$restLen = function_exists('mb_strlen') ? mb_strlen($result, 'UTF-8') - 1 : strlen($result) - 1;
	$rest = xd_mb_sub($result, 1, $restLen);

	return $firstLetter.$rest.' đồng chẵn';
}
