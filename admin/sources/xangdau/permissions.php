<?php
if(!defined('SOURCES')) die("Error");

/* ============================ Quyền ============================ */

function xd_permission_denied($act = '')
{
	global $func, $login_admin;

	if(!$func->check_permission()) return false; // super admin
	if(!isset($_SESSION[$login_admin]['active']) || $_SESSION[$login_admin]['active'] != true) return true;
	if(!isset($_SESSION['list_quyen']) || !is_array($_SESSION['list_quyen'])) return true;

	$permissions = array('xangdau_man', 'hoadon_man', 'order_man', 'product_man_cabin', 'xangdau_ketoan_check');
	$hasAny = false;
	foreach($permissions as $permission)
	{
		if(in_array($permission, $_SESSION['list_quyen'])) { $hasAny = true; break; }
	}
	if(!$hasAny) return true;

	// Kế toán (không có quyền quản lý nào khác) chỉ được phép import file, xem và kiểm tra.
	// Chỉ áp dụng khi KHÔNG có quyền quản lý nào khác — tránh chặn nhầm các nhóm quyền
	// dùng nút "Chọn tất cả" (tick luôn cả ô kế toán lẫn ô quản lý trong cùng khối).
	if($act !== '' && xd_is_ketoan_only() && xd_ketoan_action_denied($act)) return true;

	return false;
}

/**
 * Tài khoản có được gán quyền kế toán xăng dầu (xangdau_ketoan_check) hay không.
 */
function xd_has_ketoan_permission()
{
	return isset($_SESSION['list_quyen']) && is_array($_SESSION['list_quyen']) && in_array('xangdau_ketoan_check', $_SESSION['list_quyen']);
}

/**
 * Tài khoản CHỈ có quyền kế toán (không có bất kỳ quyền quản lý xangdau/hoadon/order/cabin nào khác).
 * Dùng để phân biệt "kế toán thật" với nhóm quyền quản lý đã bấm "Chọn tất cả" (tick luôn ô kế toán).
 */
function xd_is_ketoan_only()
{
	if(!xd_has_ketoan_permission()) return false;
	foreach(array('xangdau_man', 'hoadon_man', 'order_man', 'product_man_cabin') as $p)
	{
		if(in_array($p, $_SESSION['list_quyen'])) return false;
	}
	return true;
}

/**
 * Danh sách hành động kế toán được phép: import file, xem danh sách, kiểm tra,
 * xóa toàn bộ, chuyển trạng thái thanh toán học viên.
 * Không có: cấu hình, duyệt, xuất bảng kê quyết toán.
 */
function xd_ketoan_action_denied($act)
{
	$allowedActs = array(
		'loc', 'xemGiaoVien', 'kiemTraGiaoVien', 'huyKiemTraGiaoVien', 'locKiemTra', 'locDuyet',
		'hoadon', 'uploadHoadon', 'uploadHoadonExcel', 'deleteAllHoadon',
		'hocvien', 'uploadHocvien', 'uploadHocvienExcel', 'deleteAllHocvien', 'updateHocvienStatus',
	);
	return !in_array($act, $allowedActs);
}

/**
 * Có quyền duyệt thanh toán (nút "Duyệt") hay không: super admin hoặc có quyền quản lý.
 * Nếu tài khoản CHỈ có quyền kế toán (không kèm quyền quản lý nào) thì không được duyệt.
 */
function xd_can_duyet()
{
	global $func;
	if(!$func->check_permission()) return true; // super admin
	if(!isset($_SESSION['list_quyen']) || !is_array($_SESSION['list_quyen'])) return false;
	foreach(array('xangdau_man', 'hoadon_man', 'order_man', 'product_man_cabin') as $p)
	{
		if(in_array($p, $_SESSION['list_quyen'])) return true;
	}
	return false;
}

/**
 * Có quyền kế toán kiểm tra (nút "Kiểm tra") hay không: chỉ tài khoản được gán quyền kế toán.
 * Admin/quản lý (kể cả super admin) không hiển thị nút này — đây là việc của kế toán.
 */
function xd_can_kiem_tra()
{
	return xd_has_ketoan_permission();
}
