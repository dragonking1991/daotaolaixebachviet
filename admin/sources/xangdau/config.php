<?php
if(!defined('SOURCES')) die("Error");

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

	$keys = array(
		'xd_dinh_muc',
		'xd_dinh_muc_ck', 'xd_dinh_muc_dat',
		'xd_dinh_muc_ck_bss', 'xd_dinh_muc_ck_btd', 'xd_dinh_muc_ck_c1', 'xd_dinh_muc_ck_c', 'xd_dinh_muc_ck_ce',
		'xd_dinh_muc_dat_bss', 'xd_dinh_muc_dat_btd', 'xd_dinh_muc_dat_c1', 'xd_dinh_muc_dat_c', 'xd_dinh_muc_dat_ce',
		'xd_muc_bt', 'xd_muc_ck', 'xd_muc_dat'
	);
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
