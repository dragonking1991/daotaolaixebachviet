<?php
if(!defined('SOURCES')) die("Error");

/* ============================ Quyền ============================ */

function xd_permission_denied($act = '')
{
	global $func, $login_admin;

	if(!$func->check_permission()) return false; // super admin
	if(!isset($_SESSION[$login_admin]['active']) || $_SESSION[$login_admin]['active'] != true) return true;
	if(!isset($_SESSION['list_quyen']) || !is_array($_SESSION['list_quyen'])) return true;

	$permissions = array(
		'xangdau_man', 'hoadon_man', 'order_man', 'product_man_cabin', 'xangdau_ketoan_check',
		'xangdau_view', 'xangdau_import', 'xangdau_duyet', 'xangdau_xoa', 'xangdau_config',
	);
	$hasAny = false;
	foreach($permissions as $permission)
	{
		if(in_array($permission, $_SESSION['list_quyen'])) { $hasAny = true; break; }
	}
	if(!$hasAny) return true;

	// Tài khoản không có quyền quản lý toàn phần (xangdau_man/hoadon_man/order_man/product_man_cabin)
	// chỉ được thực hiện đúng hành động ứng với các mục chi tiết đã được gán (kế toán/import/duyệt/xóa/cấu hình).
	if($act !== '' && !xd_has_full_manage() && xd_act_denied($act)) return true;

	return false;
}

/**
 * Có quyền quản lý toàn phần xăng dầu hay không (bỏ qua mọi kiểm tra chi tiết theo hành động).
 */
function xd_has_full_manage()
{
	if(!isset($_SESSION['list_quyen']) || !is_array($_SESSION['list_quyen'])) return false;
	foreach(array('xangdau_man', 'hoadon_man', 'order_man', 'product_man_cabin') as $p)
	{
		if(in_array($p, $_SESSION['list_quyen'])) return true;
	}
	return false;
}

/**
 * Tài khoản có được gán một quyền chi tiết cụ thể hay không (vd 'xangdau_ketoan_check', 'xangdau_xoa'...).
 */
function xd_has_perm($key)
{
	return isset($_SESSION['list_quyen']) && is_array($_SESSION['list_quyen']) && in_array($key, $_SESSION['list_quyen']);
}

/**
 * Tài khoản có được gán quyền kế toán xăng dầu (xangdau_ketoan_check) hay không.
 */
function xd_has_ketoan_permission()
{
	return xd_has_perm('xangdau_ketoan_check');
}

/**
 * Tài khoản CHỈ có quyền kế toán (không có bất kỳ quyền quản lý xangdau/hoadon/order/cabin nào khác).
 * Dùng để phân biệt "kế toán thật" với nhóm quyền quản lý đã bấm "Chọn tất cả" (tick luôn ô kế toán).
 */
function xd_is_ketoan_only()
{
	return xd_has_ketoan_permission() && !xd_has_full_manage();
}

/**
 * Kiểm tra 1 hành động ($act) có bị chặn hay không đối với tài khoản KHÔNG có quyền quản lý toàn phần.
 * Mỗi hành động yêu cầu đúng 1 (hoặc vài) mục chi tiết tương ứng đã được gán cho tài khoản.
 */
function xd_act_denied($act)
{
	$configActs = array('config', 'saveConfig');
	$deleteActs = array('deleteHoadon', 'deleteAllHoadon', 'deleteHocvien', 'deleteAllHocvien');
	$importActs = array('uploadHoadon', 'uploadHoadonExcel', 'uploadHocvien', 'uploadHocvienExcel');
	$kiemtraActs = array('kiemTraGiaoVien', 'huyKiemTraGiaoVien');
	$duyetActs = array('duyetGiaoVien', 'duyetTatCaGiaoVien');
	$exportActs = array('xuatBangKeGiaoVien', 'xuatTongHopGiaoVien', 'xuatTatCaBangKe', 'xuatToanBoDanhSachHocVien', 'xuatBangKe');
	$viewActs = array('loc', 'xemGiaoVien', 'locKiemTra', 'locDuyet', 'locDaThanhToan', 'hoadon', 'hocvien');

	if(in_array($act, $configActs, true)) return !xd_can_config();
	if(in_array($act, $deleteActs, true)) return !xd_can_xoa();
	if(in_array($act, $importActs, true)) return !xd_can_import();
	if(in_array($act, $kiemtraActs, true)) return !xd_can_kiem_tra();
	if(in_array($act, $duyetActs, true)) return !xd_can_duyet();
	if(in_array($act, $exportActs, true)) return !(xd_can_duyet() || xd_can_kiem_tra());
	if($act === 'updateHocvienStatus') return !(xd_can_import() || xd_can_kiem_tra());
	if(in_array($act, $viewActs, true)) return false; // đã qua kiểm tra hasAny ở trên, cho xem tự do

	return false; // hành động chưa liệt kê: không chặn thêm
}

/**
 * Có quyền duyệt thanh toán (nút "Duyệt") hay không: super admin, có quyền quản lý,
 * hoặc được gán riêng mục chi tiết "Duyệt thanh toán" (xangdau_duyet).
 */
function xd_can_duyet()
{
	global $func;
	if(!$func->check_permission()) return true; // super admin
	return xd_has_full_manage() || xd_has_perm('xangdau_duyet');
}

/**
 * Có quyền kế toán kiểm tra (nút "Kiểm tra") hay không: tài khoản được gán quyền kế toán,
 * hoặc admin/quản lý (xd_can_duyet()) — admin xem và thao tác được như kế toán, cộng thêm quyền duyệt riêng.
 */
function xd_can_kiem_tra()
{
	return xd_has_ketoan_permission() || xd_can_duyet();
}

/**
 * Có quyền xóa toàn bộ hóa đơn/học viên hay không: super admin, quyền quản lý, hoặc mục chi tiết "Xóa toàn bộ".
 */
function xd_can_xoa()
{
	global $func;
	if(!$func->check_permission()) return true; // super admin
	return xd_has_full_manage() || xd_has_perm('xangdau_xoa');
}

/**
 * Có quyền import file hóa đơn/học viên hay không: super admin, quyền quản lý, kế toán,
 * hoặc mục chi tiết "Import file".
 */
function xd_can_import()
{
	global $func;
	if(!$func->check_permission()) return true; // super admin
	return xd_has_full_manage() || xd_has_ketoan_permission() || xd_has_perm('xangdau_import');
}

/**
 * Có quyền vào trang cấu hình định mức hay không: super admin, quyền quản lý, hoặc mục chi tiết "Cấu hình định mức".
 */
function xd_can_config()
{
	global $func;
	if(!$func->check_permission()) return true; // super admin
	return xd_has_full_manage() || xd_has_perm('xangdau_config');
}

