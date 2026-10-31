<?php
/*
 * Chống CSRF cho module Đào tạo và Xăng dầu.
 * Mọi POST đều cần token; GET chỉ được miễn token với các trang xem/xuất trong danh sách dưới đây,
 * nên act ghi dữ liệu mới thêm sau này mặc định vẫn được bảo vệ.
 * Token được admin/assets/js/admin-csrf.js tự gắn vào form và link nội bộ của hai module.
 */

function admin_csrf_modules()
{
	return array(
		'daotao' => array(
			'', 'dashboard', 'dieukienDat', 'exportDieukienDat', 'taichinh', 'exportTaichinh', 'sathachPending',
			'khoa', 'exportKhoa', 'khoaAdd', 'khoaEdit', 'uploadKhoa', 'crudEdit',
			'hocvien', 'exportHocvien', 'uploadHocvien', 'xe', 'exportXe', 'uploadXe',
			'giaovien', 'exportGiaovien', 'uploadGiaovien', 'lythuyet', 'exportLythuyet', 'uploadLythuyet',
			'cabinkq', 'exportCabin', 'uploadCabin', 'hinh', 'exportHinh', 'dat', 'exportDat', 'uploadDat',
			'tonghop', 'exportTonghop', 'kythi', 'kythiDetail', 'exportKythi', 'kythiUpload', 'kythiRoster', 'kythiTracuu',
			'ketquadaotao', 'exportKetqua', 'htkh', 'uploadHtkh', 'exportHtkh', 'htkhPrint', 'htkhPrintKy', 'htkhExcelKy', 'htkhWordKy',
			'sathach', 'sathachTracuu', 'uploadSathach', 'exportSathach', 'audit', 'backup', 'backupDownload', 'congGvConfig',
		),
		'xangdau' => array(
			'', 'khoa', 'config', 'hoadon', 'uploadHoadon', 'hocvien', 'ce', 'ceInvoiceTemplate', 'uploadHocvien',
			'loc', 'xemGiaoVien', 'locKiemTra', 'locDuyet', 'locDaThanhToan',
			'xuatToanBoDanhSachHocVien', 'xuatTongHopDaDuyet', 'xuatHoadonExcel', 'xuatHocvienExcel', 'xuatTatCaBangKe',
			'xuatTongHopGiaoVien', 'xuatDaKiemTraGiaoVien', 'xuatBangKeGiaoVien', 'xuatBangKeDaDuyetGiaoVien', 'xuatBangKe',
		),
	);
}

function admin_csrf_token()
{
	if(empty($_SESSION['admin_csrf']) || !is_string($_SESSION['admin_csrf']))
		$_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
	return $_SESSION['admin_csrf'];
}

function admin_csrf_valid()
{
	$sent = '';
	if(isset($_POST['_csrf'])) $sent = (string)$_POST['_csrf'];
	elseif(isset($_GET['_csrf'])) $sent = (string)$_GET['_csrf'];
	elseif(isset($_SERVER['HTTP_X_CSRF_TOKEN'])) $sent = (string)$_SERVER['HTTP_X_CSRF_TOKEN'];
	return $sent !== '' && !empty($_SESSION['admin_csrf']) && hash_equals((string)$_SESSION['admin_csrf'], $sent);
}

function admin_csrf_required($com, $act, $method)
{
	$modules = admin_csrf_modules();
	if(!isset($modules[$com])) return false;
	if(strtoupper((string)$method) === 'POST') return true;
	return !in_array((string)$act, $modules[$com], true);
}

function admin_csrf_guard($com, $act)
{
	global $func;
	$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
	if(!admin_csrf_required($com, $act, $method) || admin_csrf_valid()) return;
	$back = 'index.php?com='.$com;
	if(strtoupper($method) === 'POST' && empty($_POST) && !empty($_SERVER['CONTENT_LENGTH']))
		$func->transfer('Dữ liệu gửi lên quá lớn hoặc phiên đã hết hạn. Vui lòng tải lại trang rồi thử lại.', $back, false);
	$func->transfer('Phiên thao tác không hợp lệ hoặc đã hết hạn. Vui lòng tải lại trang rồi thực hiện lại.', $back, false);
}
