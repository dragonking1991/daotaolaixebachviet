<?php
if(!defined('SOURCES')) die("Error");

require_once SOURCES.'daotao/schema.php';
require_once SOURCES.'daotao/config_daotao.php';
require_once SOURCES.'daotao/helpers.php';
require_once SOURCES.'daotao/ops.php';
require_once SOURCES.'daotao/excel_open.php';
require_once SOURCES.'daotao/khoa.php';
require_once SOURCES.'daotao/hocvien.php';
require_once SOURCES.'daotao/xe.php';
require_once SOURCES.'daotao/giaovien.php';
require_once SOURCES.'daotao/lythuyet.php';
require_once SOURCES.'daotao/mophong.php';
require_once SOURCES.'daotao/cabin_kq.php';
require_once SOURCES.'daotao/thuchanh_hinh.php';
require_once SOURCES.'daotao/dat.php';
require_once SOURCES.'xangdau/daotao_sync.php';
require_once SOURCES.'daotao/tonghop.php';
require_once SOURCES.'daotao/export.php';
require_once SOURCES.'daotao/crud.php';
require_once SOURCES.'daotao/taichinh.php';
require_once SOURCES.'daotao/kythi.php';
require_once SOURCES.'daotao/ketquadaotao.php';
require_once SOURCES.'daotao/htkh.php';
require_once SOURCES.'daotao/sathach.php';
require_once SOURCES.'daotao/conggv.php';
$DT_MAN = array('daotao_man');
$DT_UP  = array('daotao_upload', 'daotao_import', 'daotao_man');
$DT_VIEW = array('daotao_tracuu', 'daotao_import', 'daotao_upload', 'daotao_man');

/* Danh sách quyền hợp lệ cho 1 mục đào tạo theo thao tác.
 * $action ∈ view|edit|delete|import|export. Mỗi mục có quyền riêng daotao_<mục>_<action>.
 * Giữ tương thích ngược: daotao_man = toàn quyền; daotao_<mục> (cũ) = xem/sửa/xóa/xuất;
 * daotao_tracuu = xem/xuất mọi mục; daotao_import/upload = import + xem mọi mục. */
function dt_p($section, $action = 'view')
{
	$sec = 'daotao_'.$section;
	$allow = array('daotao_man'); // toàn quyền đào tạo
	if($section === 'cabinkq') $allow[] = 'product_man_cabin'; // quyền Học Cabin chỉ mở mục cabin
	$allow[] = $sec.'_'.$action; // quyền chi tiết theo mục + thao tác
	if($action !== 'import') $allow[] = $sec; // quyền gộp "xem & quản lý" cũ = xem/sửa/xóa/xuất
	if($action === 'view' || $action === 'export') $allow[] = 'daotao_tracuu';
	if($action === 'view') { $allow[] = 'daotao_import'; $allow[] = 'daotao_upload'; }
	if($action === 'import') { $allow[] = 'daotao_import'; $allow[] = 'daotao_upload'; }
	return array_values(array_unique($allow));
}

/* Tài khoản hiện tại có quyền thao tác trên mục không (dùng để ẩn/hiện nút trong template). */
function dt_can($section, $action = 'view')
{
	return !dt_permission_denied(dt_p($section, $action));
}

/* Mục đào tạo tương ứng với entity của CRUD dùng chung (mophong gộp vào lý thuyết). */
function dt_crud_section()
{
	$entity = preg_replace('/[^a-z]/', '', isset($_REQUEST['entity']) ? $_REQUEST['entity'] : '');
	$map = array('mophong' => 'lythuyet');
	if(isset($map[$entity])) return $map[$entity];
	$known = array('khoa', 'hocvien', 'xe', 'giaovien', 'lythuyet', 'cabinkq', 'dat', 'hinh');
	return in_array($entity, $known, true) ? $entity : 'khoa';
}

function dt_guard($perms)
{
	global $func;
	if(dt_permission_denied($perms)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
	dt_ensure_tables();
}

function dt_admin_guard()
{
	global $func;
	if(!dt_is_admin()) $func->transfer("Thao tác này chỉ dành cho quản trị viên", "index.php?com=daotao", false);
}

switch($act)
{
	case 'taichinh': dt_guard(dt_p('taichinh','view')); dt_finance_list(); $template='daotao/taichinh/items'; break;
	case 'createSathachPeriod': dt_guard(dt_p('sathach','edit')); dt_sathach_create_period(); break;
	case 'saveTaichinh': dt_guard(dt_p('taichinh','edit')); dt_finance_save(); break;
	case 'exportTaichinh': dt_guard(dt_p('taichinh','export')); dt_finance_export(); break;
	/* ---------- Khóa học ---------- */
	case "khoa":       dt_guard(dt_p('khoa','view'));   dt_khoa_list();   $template = "daotao/khoa/items"; break;
	case "exportKhoa": dt_guard(dt_p('khoa','view'));   dt_khoa_export(); break;
	case "khoaAdd":    dt_guard(dt_p('khoa','manage')); dt_khoa_form();   $template = "daotao/khoa/item_add"; break;
	case "khoaEdit":   dt_guard(dt_p('khoa','edit')); dt_khoa_form();   $template = "daotao/khoa/item_add"; break;
	case "khoaSave":   dt_guard(dt_p('khoa','edit')); dt_khoa_save();   break;
	// Không hỗ trợ xóa từng khóa qua link; chỉ xóa các khóa được chọn.
	case "khoaDelete": $func->transfer("Vui lòng chọn khóa cần xóa trong danh sách", "index.php?com=daotao&act=khoa", false); break;
	case "khoaDeleteSelected": dt_guard(dt_p('khoa','delete')); dt_khoa_delete_selected(); break;
	case "uploadKhoa":      dt_guard(dt_p('khoa','import')); dt_khoa_upload_form(); $template = "daotao/khoa/upload"; break;
	case "uploadKhoaExcel": dt_guard(dt_p('khoa','import')); dt_khoa_upload_excel(); break;
	case "crudEdit":   dt_guard(dt_p(dt_crud_section(),'edit')); dt_crud_form(); $template = "daotao/crud_form"; break;
	case "crudSave":   dt_guard(dt_p(dt_crud_section(),'edit')); dt_crud_save(); break;
	case "crudDelete": dt_guard(dt_p(dt_crud_section(),'delete')); dt_crud_delete(); break;
	case "crudDeleteSelected": dt_guard(dt_p(dt_crud_section(),'delete')); dt_crud_delete_selected(); break;
	case "modulePassSelected": dt_guard(dt_p(dt_crud_section(),'edit')); dt_module_pass_selected(); break;
	case "moduleDeleteSelected": dt_guard(dt_p(dt_crud_section(),'delete')); dt_module_delete_selected(); break;
	case "crudUndo":   dt_guard(dt_p(dt_crud_section(),'edit')); dt_crud_undo(); break;
	case "crudDeleteAll": dt_guard(dt_p(dt_crud_section(),'delete')); dt_admin_guard(); if(preg_replace('/[^a-z]/','', isset($_REQUEST['entity'])?$_REQUEST['entity']:'') === 'khoa') $func->transfer("Chức năng xóa khóa đào tạo đã được tắt", "index.php?com=daotao&act=khoa", false); dt_crud_delete_all(); break;

	/* ---------- Học viên ---------- */
	case "hocvien":            dt_guard(dt_p('hocvien','view'));   dt_hocvien_list();   $template = "daotao/hocvien/items"; break;
	case "exportHocvien":      dt_guard(dt_p('hocvien','export')); dt_hocvien_export(); break;
	case "uploadHocvien":      dt_guard(dt_p('hocvien','import')); dt_hocvien_upload_form(); $template = "daotao/hocvien/upload"; break;
	case "uploadHocvienExcel": dt_guard(dt_p('hocvien','import')); dt_hocvien_upload_excel(); break;

	/* ---------- Xe ---------- */
	case "xe":            dt_guard(dt_p('xe','view'));   dt_xe_list();         $template = "daotao/xe/items"; break;
	case "exportXe":      dt_guard(dt_p('xe','export')); dt_xe_export(); break;
	case "xeThanhLy":     dt_guard(dt_p('xe','edit')); dt_xe_thanh_ly(); break;
	case "uploadXe":      dt_guard(dt_p('xe','import')); $template = "daotao/xe/upload"; break;
	case "uploadXeExcel": dt_guard(dt_p('xe','import')); dt_xe_upload_excel(); break;

	/* ---------- Giáo viên ---------- */
	case "giaovien":            dt_guard(dt_p('giaovien','view'));   dt_giaovien_list();  $template = "daotao/giaovien/items"; break;
	case "exportGiaovien":      dt_guard(dt_p('giaovien','export')); dt_giaovien_export(); break;
	case "uploadGiaovien":      dt_guard(dt_p('giaovien','import')); $template = "daotao/giaovien/upload"; break;
	case "uploadGiaovienExcel": dt_guard(dt_p('giaovien','import')); dt_giaovien_upload_excel(); break;
	case "gvResetPass":         dt_guard(dt_p('giaovien','edit')); dt_giaovien_reset_pass(); break;
	case "gvThanhLy":           dt_guard(dt_p('giaovien','edit')); dt_giaovien_thanh_ly(); break;

	/* ---------- Lý thuyết ---------- */
	case "lythuyet":            dt_guard(dt_p('lythuyet','view'));   dt_lythuyet_list();  $template = "daotao/lythuyet/items"; break;
	case "exportLythuyet":      dt_guard(dt_p('lythuyet','export')); dt_lythuyet_export(); break;
	case "uploadLythuyet":      dt_guard(dt_p('lythuyet','import')); dt_lythuyet_upload_form(); $template = "daotao/lythuyet/upload"; break;
	case "uploadLythuyetExcel": dt_guard(dt_p('lythuyet','import')); dt_lythuyet_upload_excel(); break;
	case "saveLythuyetCheck":   dt_guard(dt_p('lythuyet','edit')); dt_lythuyet_save_check(); break;

	/* ---------- Cabin kết quả ---------- */
	case "cabinkq":          dt_guard(dt_p('cabinkq','view'));   dt_cabin_list();  $template = "daotao/cabinkq/items"; break;
	case "exportCabin":      dt_guard(dt_p('cabinkq','export')); dt_cabin_export(); break;
	case "uploadCabin":      dt_guard(dt_p('cabinkq','import')); dt_cabin_upload_form(); $template = "daotao/cabinkq/upload"; break;
	case "uploadCabinExcel": dt_guard(dt_p('cabinkq','import')); dt_cabin_upload_excel(); break;

	/* ---------- Thực hành trong hình ---------- */
	case "hinh":     dt_guard(dt_p('hinh','view'));   dt_hinh_list(); $template = "daotao/hinh/items"; break;
	case "exportHinh": dt_guard(dt_p('hinh','export')); dt_hinh_export(); break;
	case "saveHinh": dt_guard(dt_p('hinh','edit')); dt_hinh_save(); break;
	case "saveHinhSelected": dt_guard(dt_p('hinh','edit')); dt_hinh_save_selected(); break;
	case "hinhDatAll": dt_guard(dt_p('hinh','edit')); dt_hinh_dat_all(); break;
	/* ---------- DAT ---------- */
	case "dat":            dt_guard(dt_p('dat','view'));   dt_dat_list();  $template = "daotao/dat/items"; break;
	case "exportDat":      dt_guard(dt_p('dat','export')); dt_dat_export(); break;
	case "uploadDat":      dt_guard(dt_p('dat','import')); dt_dat_upload_form(); $template = "daotao/dat/upload"; break;
	case "uploadDatExcel": dt_guard(dt_p('dat','import')); dt_dat_upload_excel(); break;
	case "datDatAll":      dt_guard(dt_p('dat','edit')); dt_dat_dat_all(); break;
	case "datOverrideClear": dt_guard(dt_p('dat','edit')); dt_dat_override_clear(); break;

	/* ---------- Tổng hợp ---------- */
	case "tonghop":       dt_guard(dt_p('tonghop','view')); dt_tonghop_list();   $template = "daotao/tonghop/items"; break;
	case "exportTonghop": dt_guard(dt_p('tonghop','export')); dt_tonghop_export(); break;

	/* ---------- Ngày kiểm tra (kỳ kiểm tra TTSH) ---------- */
	case "kythi":            dt_guard(dt_p('kythi','view'));   dt_kythi_list();   $template = "daotao/kythi/items"; break;
	case "kythiDetail":      dt_guard(dt_p('kythi','view'));  dt_kythi_detail(); $template = "daotao/kythi/detail"; break;
	case "kythiSave":        dt_guard(dt_p('kythi','edit'));  dt_kythi_save(); break;
	case "kythiDelete":      dt_guard(dt_p('kythi','delete'));  dt_kythi_delete(); break;
	case "kythiAddEligible": dt_guard(dt_p('kythi','edit'));  dt_kythi_add_eligible(); break;
	case "kythiAddOne":      dt_guard(dt_p('kythi','edit'));  dt_kythi_add_one(); break;
	case "kythiAddManual":   dt_guard(dt_p('kythi','edit'));  dt_kythi_add_manual(); break;
	case "kythiRemove":      dt_guard(dt_p('kythi','edit'));  dt_kythi_remove_hv(); break;
	case "kythiRemoveSelected": dt_guard(dt_p('kythi','delete')); dt_kythi_remove_selected(); break;
	case "exportKythi":      dt_guard(dt_p('kythi','export')); dt_kythi_export(); break;
	case "kythiUpload":      dt_guard(dt_p('kythi','import'));   dt_kythi_upload_form(); $template = "daotao/kythi/upload"; break;
	case "kythiImportExcel": dt_guard(dt_p('kythi','import'));   dt_kythi_import_excel(); break;
	case "kythiRoster":      dt_guard(dt_p('kythi','import'));   dt_kythi_roster_form(); $template = "daotao/kythi/roster"; break;
	case "kythiImportRoster":dt_guard(dt_p('kythi','import'));   dt_kythi_import_roster(); break;
	case "kythiTracuu":      dt_guard(dt_p('kythi','view')); dt_kythi_tracuu(); $template = "daotao/kythi/tracuu"; break;

	/* ---------- Thông tin kết quả đào tạo ---------- */
	case "ketquadaotao":  dt_guard(dt_p('ketqua','view')); dt_ketqua_list();   $template = "daotao/ketquadaotao/items"; break;
	case "exportKetqua":  dt_guard(dt_p('ketqua','export')); dt_ketqua_export(); break;

	/* ---------- Số giấy HTKH ---------- */
	case "htkh":            dt_guard(dt_p('htkh','view')); dt_htkh_list();   $template = "daotao/htkh/items"; break;
	case "saveHtkh":        dt_guard(dt_p('htkh','edit'));  dt_htkh_save(); break;
	case "htkhCapGiay":     dt_guard(dt_p('htkh','edit'));  dt_htkh_cap_giay(); break;
	case "htkhCapGiayKy":   dt_guard(dt_p('htkh','edit'));  dt_htkh_cap_giay_ky(); break;
	case "htkhPrint":       dt_guard(dt_p('htkh','view'));  dt_htkh_print(); break;
	case "htkhPrintKy":     dt_guard(dt_p('htkh','view'));  dt_htkh_print_ky(); break;
	case "htkhExcelKy":     dt_guard(dt_p('htkh','export')); dt_htkh_export_ky('xlsx'); break;
	case "htkhWordKy":      dt_guard(dt_p('htkh','export')); dt_htkh_export_ky('docx'); break;
	case "uploadHtkh":      dt_guard(dt_p('htkh','import'));   dt_htkh_upload_form(); $template = "daotao/htkh/upload"; break;
	case "uploadHtkhExcel": dt_guard(dt_p('htkh','import'));   dt_htkh_upload_excel(); break;
	case "exportHtkh":      dt_guard(dt_p('htkh','export')); dt_htkh_export(); break;

	/* ---------- Kết quả sát hạch ---------- */
	case "sathach":            dt_guard(dt_p('sathach','view')); dt_sathach_list();   $template = "daotao/sathach/items"; break;
	case "sathachTracuu":      dt_guard(dt_p('sathach','view')); dt_sathach_tracuu(); $template = "daotao/sathach/tracuu"; break;
	case "uploadSathach":      dt_guard(dt_p('sathach','import'));   dt_sathach_upload_form(); $template = "daotao/sathach/upload"; break;
	case "uploadSathachExcel": dt_guard(dt_p('sathach','import'));   dt_sathach_upload_excel(); break;
	case "exportSathach":      dt_guard(dt_p('sathach','export')); dt_sathach_export(); break;

	/* ---------- Tổng quan / Nhật ký / Sao lưu ---------- */
	case "dashboard": dt_guard($DT_MAN); dt_dashboard_page(); $template = "daotao/dashboard/items"; break;
	case "audit":     dt_guard($DT_MAN); dt_audit_page();     $template = "daotao/audit/items"; break;
	case "backup":    dt_guard($DT_MAN); dt_backup_page();    $template = "daotao/backup/items"; break;
	case "backupRestore":  dt_guard($DT_MAN); dt_backup_restore_action(); break;
	case "backupDownload": dt_guard($DT_MAN); dt_backup_download_action(); break;

	/* ---------- Cấu hình cổng giáo viên ---------- */
	case "congGvConfig":     dt_guard($DT_MAN); dt_conggv_config_page(); $template = "daotao/conggv_config/items"; break;
	case "congGvConfigSave": dt_guard($DT_MAN); dt_conggv_config_save(); break;

	default:
		dt_guard($DT_MAN);
		dt_khoa_list();
		$template = "daotao/khoa/items";
}
