<?php
if(!defined('SOURCES')) die("Error");

/* ============================ Cấu hình định mức ============================ */

function xd_get_config()
{
	global $d, $item, $xd_config_hocvien_cccd, $xd_config_hocvien, $xd_config_hocvien_dieu_chinh;
	$item = getXdConfig($d);
	$xd_config_hocvien_cccd = isset($_GET['cccd']) ? trim((string)$_GET['cccd']) : '';
	$xd_config_hocvien = array();
	$xd_config_hocvien_dieu_chinh = array();
	if($xd_config_hocvien_cccd !== '')
	{
		$xd_config_hocvien = $d->rawQueryOne("select id, ho_ten, cccd, khoa, nhom, gv_hoten, dinh_muc_ca_nhan, da_dieu_chinh_tt, so_tien_thanh_toan from #_xd_hocvien where cccd = ? limit 1", array($xd_config_hocvien_cccd));
	}
	if(isset($_GET['danh_sach_dieu_chinh']) && $_GET['danh_sach_dieu_chinh'] === '1')
	{
		$xd_config_hocvien_dieu_chinh = $d->rawQuery("select ho_ten, cccd, khoa, nhom, gv_hoten, so_tien_thanh_toan from #_xd_hocvien where da_dieu_chinh_tt = 1 or dinh_muc_ca_nhan <> 0 order by ho_ten asc", array());
	}
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

	$cccd = isset($_POST['cccd_hocvien']) ? trim((string)$_POST['cccd_hocvien']) : '';
	if($cccd !== '')
	{
		$rawDinhMuc = isset($_POST['so_tien_thanh_toan_ca_nhan']) ? $_POST['so_tien_thanh_toan_ca_nhan'] : '';
		$rawDinhMuc = str_replace(array('.', ',', ' '), '', trim($rawDinhMuc));
		$dinhMucCaNhan = (is_numeric($rawDinhMuc) && (int)$rawDinhMuc >= 0) ? (int)$rawDinhMuc : 0;
		$d->rawQuery("update #_xd_hocvien set dinh_muc_ca_nhan = ?, da_dieu_chinh_tt = 1, so_tien_thanh_toan = 0, ngay_thanh_toan = null, id_bangke = 0, quan_ly_duyet = 0 where cccd = ?", array($dinhMucCaNhan, $cccd));
	}

	$redirect = "index.php?com=xangdau&act=config";
	if($cccd !== '') $redirect .= '&cccd='.urlencode($cccd);
	$func->transfer("Cập nhật định mức thanh toán thành công", $redirect);
}

function xd_xoa_dieu_chinh_hoc_vien()
{
	global $d, $func;
	$cccd = isset($_GET['cccd']) ? trim((string)$_GET['cccd']) : '';
	if($cccd === '') $func->transfer("Không xác định được học viên.", "index.php?com=xangdau&act=config&danh_sach_dieu_chinh=1", false);
	$student = $d->rawQueryOne("select nhom, ngay_thanh_toan from #_xd_hocvien where cccd = ? limit 1", array($cccd));
	if(empty($student)) $func->transfer("Không tìm thấy học viên.", "index.php?com=xangdau&act=config&danh_sach_dieu_chinh=1", false);
	$config = getXdConfig($d);
	$defaultAmount = xdMucTheoNhom($config, $student['nhom']);
	$paidAmount = !empty($student['ngay_thanh_toan']) ? $defaultAmount : 0;
	$d->rawQuery("update #_xd_hocvien set dinh_muc_ca_nhan = 0, da_dieu_chinh_tt = 0, so_tien_thanh_toan = ? where cccd = ?", array($paidAmount, $cccd));
	$func->transfer("Đã xóa điều chỉnh và trả số tiền TT về mức nhóm.", "index.php?com=xangdau&act=config&danh_sach_dieu_chinh=1");
}
