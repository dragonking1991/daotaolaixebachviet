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
require_once SOURCES.'daotao/tonghop.php';
require_once SOURCES.'daotao/export.php';
require_once SOURCES.'daotao/crud.php';
require_once SOURCES.'daotao/kythi.php';
require_once SOURCES.'daotao/ketquadaotao.php';
require_once SOURCES.'daotao/htkh.php';
require_once SOURCES.'daotao/sathach.php';

$DT_MAN = array('daotao_man', 'product_man_cabin');
$DT_UP  = array('daotao_upload', 'daotao_import', 'daotao_man', 'product_man_cabin');
$DT_VIEW = array('daotao_tracuu', 'daotao_import', 'daotao_upload', 'daotao_man', 'product_man_cabin');

/* Danh sách quyền hợp lệ cho 1 mục đào tạo theo thao tác (view|manage|import).
 * Luôn kèm các quyền gộp cũ để không phá vỡ nhóm quyền đã cấu hình trước đây. */
function dt_p($section, $mode = 'view')
{
	$sec = 'daotao_'.$section;
	$allow = array('daotao_man', 'product_man_cabin'); // toàn quyền đào tạo
	if($mode === 'manage')
	{
		$allow[] = $sec;
	}
	elseif($mode === 'import')
	{
		$allow[] = $sec.'_import';
		$allow[] = 'daotao_upload';
		$allow[] = 'daotao_import';
	}
	else // view: mục này hoặc bất kỳ quyền gộp xem/nhập nào
	{
		$allow[] = $sec;
		$allow[] = $sec.'_import';
		$allow[] = 'daotao_tracuu';
		$allow[] = 'daotao_import';
		$allow[] = 'daotao_upload';
	}
	return $allow;
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

switch($act)
{
	/* ---------- Khóa học ---------- */
	case "khoa":       dt_guard(dt_p('khoa','view'));   dt_khoa_list();   $template = "daotao/khoa/items"; break;
	case "exportKhoa": dt_guard(dt_p('khoa','view'));   dt_khoa_export(); break;
	case "khoaAdd":    dt_guard(dt_p('khoa','manage')); dt_khoa_form();   $template = "daotao/khoa/item_add"; break;
	case "khoaEdit":   dt_guard(dt_p('khoa','manage')); dt_khoa_form();   $template = "daotao/khoa/item_add"; break;
	case "khoaSave":   dt_guard(dt_p('khoa','manage')); dt_khoa_save();   break;
	case "khoaDelete": dt_guard(dt_p('khoa','manage')); dt_khoa_delete(); break;
	case "khoaDeleteSelected": dt_guard(dt_p('khoa','manage')); dt_khoa_delete_selected(); break;
	case "uploadKhoa":      dt_guard(dt_p('khoa','import')); dt_khoa_upload_form(); $template = "daotao/khoa/upload"; break;
	case "uploadKhoaExcel": dt_guard(dt_p('khoa','import')); dt_khoa_upload_excel(); break;
	case "crudEdit":   dt_guard(dt_p(dt_crud_section(),'manage')); dt_crud_form(); $template = "daotao/crud_form"; break;
	case "crudSave":   dt_guard(dt_p(dt_crud_section(),'manage')); dt_crud_save(); break;
	case "crudDelete": dt_guard(dt_p(dt_crud_section(),'manage')); dt_crud_delete(); break;
	case "crudUndo":   dt_guard(dt_p(dt_crud_section(),'manage')); dt_crud_undo(); break;
	case "crudDeleteAll": dt_guard(dt_p(dt_crud_section(),'manage')); dt_crud_delete_all(); break;

	/* ---------- Học viên ---------- */
	case "hocvien":            dt_guard(dt_p('hocvien','view'));   dt_hocvien_list();   $template = "daotao/hocvien/items"; break;
	case "exportHocvien":      dt_guard(dt_p('hocvien','view'));   dt_hocvien_export(); break;
	case "uploadHocvien":      dt_guard(dt_p('hocvien','import')); dt_hocvien_upload_form(); $template = "daotao/hocvien/upload"; break;
	case "uploadHocvienExcel": dt_guard(dt_p('hocvien','import')); dt_hocvien_upload_excel(); break;

	/* ---------- Xe ---------- */
	case "xe":            dt_guard(dt_p('xe','view'));   dt_xe_list();         $template = "daotao/xe/items"; break;
	case "exportXe":      dt_guard(dt_p('xe','view'));   dt_xe_export(); break;
	case "xeThanhLy":     dt_guard(dt_p('xe','manage')); dt_xe_thanh_ly(); break;
	case "uploadXe":      dt_guard(dt_p('xe','import')); $template = "daotao/xe/upload"; break;
	case "uploadXeExcel": dt_guard(dt_p('xe','import')); dt_xe_upload_excel(); break;

	/* ---------- Giáo viên ---------- */
	case "giaovien":            dt_guard(dt_p('giaovien','view'));   dt_giaovien_list();  $template = "daotao/giaovien/items"; break;
	case "exportGiaovien":      dt_guard(dt_p('giaovien','view'));   dt_giaovien_export(); break;
	case "uploadGiaovien":      dt_guard(dt_p('giaovien','import')); $template = "daotao/giaovien/upload"; break;
	case "uploadGiaovienExcel": dt_guard(dt_p('giaovien','import')); dt_giaovien_upload_excel(); break;
	case "gvResetPass":         dt_guard(dt_p('giaovien','manage')); dt_giaovien_reset_pass(); break;
	case "gvThanhLy":           dt_guard(dt_p('giaovien','manage')); dt_giaovien_thanh_ly(); break;

	/* ---------- Lý thuyết ---------- */
	case "lythuyet":            dt_guard(dt_p('lythuyet','view'));   dt_lythuyet_list();  $template = "daotao/lythuyet/items"; break;
	case "exportLythuyet":      dt_guard(dt_p('lythuyet','view'));   dt_lythuyet_export(); break;
	case "uploadLythuyet":      dt_guard(dt_p('lythuyet','import')); dt_lythuyet_upload_form(); $template = "daotao/lythuyet/upload"; break;
	case "uploadLythuyetExcel": dt_guard(dt_p('lythuyet','import')); dt_lythuyet_upload_excel(); break;
	case "saveLythuyetCheck":   dt_guard(dt_p('lythuyet','manage')); dt_lythuyet_save_check(); break;

	/* ---------- Cabin kết quả ---------- */
	case "cabinkq":          dt_guard(dt_p('cabinkq','view'));   dt_cabin_list();  $template = "daotao/cabinkq/items"; break;
	case "exportCabin":      dt_guard(dt_p('cabinkq','view'));   dt_cabin_export(); break;
	case "uploadCabin":      dt_guard(dt_p('cabinkq','import')); dt_cabin_upload_form(); $template = "daotao/cabinkq/upload"; break;
	case "uploadCabinExcel": dt_guard(dt_p('cabinkq','import')); dt_cabin_upload_excel(); break;

	/* ---------- Thực hành trong hình ---------- */
	case "hinh":     dt_guard(dt_p('hinh','view'));   dt_hinh_list(); $template = "daotao/hinh/items"; break;
	case "exportHinh": dt_guard(dt_p('hinh','view'));   dt_hinh_export(); break;
	case "saveHinh": dt_guard(dt_p('hinh','manage')); dt_hinh_save(); break;
	case "hinhDatAll": dt_guard(dt_p('hinh','manage')); dt_hinh_dat_all(); break;
	/* ---------- DAT ---------- */
	case "dat":            dt_guard(dt_p('dat','view'));   dt_dat_list();  $template = "daotao/dat/items"; break;
	case "exportDat":      dt_guard(dt_p('dat','view'));   dt_dat_export(); break;
	case "uploadDat":      dt_guard(dt_p('dat','import')); dt_dat_upload_form(); $template = "daotao/dat/upload"; break;
	case "uploadDatExcel": dt_guard(dt_p('dat','import')); dt_dat_upload_excel(); break;
	case "datDatAll":      dt_guard(dt_p('dat','manage')); dt_dat_dat_all(); break;
	case "datOverrideClear": dt_guard(dt_p('dat','manage')); dt_dat_override_clear(); break;

	/* ---------- Tổng hợp ---------- */
	case "tonghop":       dt_guard(dt_p('tonghop','view')); dt_tonghop_list();   $template = "daotao/tonghop/items"; break;
	case "exportTonghop": dt_guard(dt_p('tonghop','view')); dt_tonghop_export(); break;

	/* ---------- Ngày kiểm tra (kỳ kiểm tra TTSH) ---------- */
	case "kythi":            dt_guard(dt_p('kythi','view'));   dt_kythi_list();   $template = "daotao/kythi/items"; break;
	case "kythiDetail":      dt_guard(dt_p('kythi','manage'));  dt_kythi_detail(); $template = "daotao/kythi/detail"; break;
	case "kythiSave":        dt_guard(dt_p('kythi','manage'));  dt_kythi_save(); break;
	case "kythiDelete":      dt_guard(dt_p('kythi','manage'));  dt_kythi_delete(); break;
	case "kythiAddEligible": dt_guard(dt_p('kythi','manage'));  dt_kythi_add_eligible(); break;
	case "kythiAddOne":      dt_guard(dt_p('kythi','manage'));  dt_kythi_add_one(); break;
	case "kythiAddManual":   dt_guard(dt_p('kythi','manage'));  dt_kythi_add_manual(); break;
	case "kythiRemove":      dt_guard(dt_p('kythi','manage'));  dt_kythi_remove_hv(); break;
	case "exportKythi":      dt_guard(dt_p('kythi','view')); dt_kythi_export(); break;
	case "kythiUpload":      dt_guard(dt_p('kythi','import'));   dt_kythi_upload_form(); $template = "daotao/kythi/upload"; break;
	case "kythiImportExcel": dt_guard(dt_p('kythi','import'));   dt_kythi_import_excel(); break;
	case "kythiRoster":      dt_guard(dt_p('kythi','import'));   dt_kythi_roster_form(); $template = "daotao/kythi/roster"; break;
	case "kythiImportRoster":dt_guard(dt_p('kythi','import'));   dt_kythi_import_roster(); break;
	case "kythiTracuu":      dt_guard(dt_p('kythi','view')); dt_kythi_tracuu(); $template = "daotao/kythi/tracuu"; break;

	/* ---------- Thông tin kết quả đào tạo ---------- */
	case "ketquadaotao":  dt_guard(dt_p('ketqua','view')); dt_ketqua_list();   $template = "daotao/ketquadaotao/items"; break;
	case "exportKetqua":  dt_guard(dt_p('ketqua','view')); dt_ketqua_export(); break;

	/* ---------- Số giấy HTKH ---------- */
	case "htkh":            dt_guard(dt_p('htkh','view')); dt_htkh_list();   $template = "daotao/htkh/items"; break;
	case "saveHtkh":        dt_guard(dt_p('htkh','manage'));  dt_htkh_save(); break;
	case "uploadHtkh":      dt_guard(dt_p('htkh','import'));   dt_htkh_upload_form(); $template = "daotao/htkh/upload"; break;
	case "uploadHtkhExcel": dt_guard(dt_p('htkh','import'));   dt_htkh_upload_excel(); break;
	case "exportHtkh":      dt_guard(dt_p('htkh','view')); dt_htkh_export(); break;

	/* ---------- Kết quả sát hạch ---------- */
	case "sathach":            dt_guard(dt_p('sathach','view')); dt_sathach_list();   $template = "daotao/sathach/items"; break;
	case "sathachTracuu":      dt_guard(dt_p('sathach','view')); dt_sathach_tracuu(); $template = "daotao/sathach/tracuu"; break;
	case "uploadSathach":      dt_guard(dt_p('sathach','import'));   dt_sathach_upload_form(); $template = "daotao/sathach/upload"; break;
	case "uploadSathachExcel": dt_guard(dt_p('sathach','import'));   dt_sathach_upload_excel(); break;
	case "exportSathach":      dt_guard(dt_p('sathach','view')); dt_sathach_export(); break;

	/* ---------- Tổng quan / Nhật ký / Sao lưu ---------- */
	case "dashboard": dt_guard($DT_MAN); dt_dashboard_page(); $template = "daotao/dashboard/items"; break;
	case "audit":     dt_guard($DT_MAN); dt_audit_page();     $template = "daotao/audit/items"; break;
	case "backup":    dt_guard($DT_MAN); dt_backup_page();    $template = "daotao/backup/items"; break;
	case "backupRestore":  dt_guard($DT_MAN); dt_backup_restore_action(); break;
	case "backupDownload": dt_guard($DT_MAN); dt_backup_download_action(); break;

	default:
		dt_guard($DT_MAN);
		dt_khoa_list();
		$template = "daotao/khoa/items";
}
