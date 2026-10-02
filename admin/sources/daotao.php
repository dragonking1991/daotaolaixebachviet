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
require_once SOURCES.'daotao/cabin_kq.php';
require_once SOURCES.'daotao/thuchanh_hinh.php';
require_once SOURCES.'daotao/dat.php';
require_once SOURCES.'daotao/tonghop.php';
require_once SOURCES.'daotao/export.php';
require_once SOURCES.'daotao/crud.php';

$DT_MAN = array('daotao_man', 'product_man_cabin');
$DT_UP  = array('daotao_upload', 'daotao_man', 'product_man_cabin');

function dt_guard($perms)
{
	global $func;
	if(dt_permission_denied($perms)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
	dt_ensure_tables();
}

switch($act)
{
	/* ---------- Khóa học ---------- */
	case "khoa":       dt_guard($DT_MAN); dt_khoa_list();   $template = "daotao/khoa/items"; break;
	case "exportKhoa": dt_guard($DT_MAN); dt_khoa_export(); break;
	case "khoaAdd":    dt_guard($DT_MAN); dt_khoa_form();   $template = "daotao/khoa/item_add"; break;
	case "khoaEdit":   dt_guard($DT_MAN); dt_khoa_form();   $template = "daotao/khoa/item_add"; break;
	case "khoaSave":   dt_guard($DT_MAN); dt_khoa_save();   break;
	case "khoaDelete": dt_guard($DT_MAN); dt_khoa_delete(); break;
	case "uploadKhoa":      dt_guard($DT_UP); dt_khoa_upload_form(); $template = "daotao/khoa/upload"; break;
	case "uploadKhoaExcel": dt_guard($DT_UP); dt_khoa_upload_excel(); break;
	case "crudEdit":   dt_guard($DT_MAN); dt_crud_form(); $template = "daotao/crud_form"; break;
	case "crudSave":   dt_guard($DT_MAN); dt_crud_save(); break;
	case "crudDelete": dt_guard($DT_MAN); dt_crud_delete(); break;
	case "crudUndo":   dt_guard($DT_MAN); dt_crud_undo(); break;
	case "crudDeleteAll": dt_guard($DT_MAN); dt_crud_delete_all(); break;

	/* ---------- Học viên ---------- */
	case "hocvien":            dt_guard($DT_MAN); dt_hocvien_list();   $template = "daotao/hocvien/items"; break;
	case "exportHocvien":      dt_guard($DT_MAN); dt_hocvien_export(); break;
	case "uploadHocvien":      dt_guard($DT_UP);  dt_hocvien_upload_form(); $template = "daotao/hocvien/upload"; break;
	case "uploadHocvienExcel": dt_guard($DT_UP);  dt_hocvien_upload_excel(); break;

	/* ---------- Xe ---------- */
	case "xe":            dt_guard($DT_MAN); dt_xe_list();         $template = "daotao/xe/items"; break;
	case "exportXe":      dt_guard($DT_MAN); dt_xe_export(); break;
	case "uploadXe":      dt_guard($DT_UP);  $template = "daotao/xe/upload"; break;
	case "uploadXeExcel": dt_guard($DT_UP);  dt_xe_upload_excel(); break;

	/* ---------- Giáo viên ---------- */
	case "giaovien":            dt_guard($DT_MAN); dt_giaovien_list();  $template = "daotao/giaovien/items"; break;
	case "exportGiaovien":      dt_guard($DT_MAN); dt_giaovien_export(); break;
	case "uploadGiaovien":      dt_guard($DT_UP);  $template = "daotao/giaovien/upload"; break;
	case "uploadGiaovienExcel": dt_guard($DT_UP);  dt_giaovien_upload_excel(); break;
	case "gvResetPass":         dt_guard($DT_MAN); dt_giaovien_reset_pass(); break;

	/* ---------- Lý thuyết ---------- */
	case "lythuyet":            dt_guard($DT_MAN); dt_lythuyet_list();  $template = "daotao/lythuyet/items"; break;
	case "exportLythuyet":      dt_guard($DT_MAN); dt_lythuyet_export(); break;
	case "uploadLythuyet":      dt_guard($DT_UP);  dt_lythuyet_upload_form(); $template = "daotao/lythuyet/upload"; break;
	case "uploadLythuyetExcel": dt_guard($DT_UP);  dt_lythuyet_upload_excel(); break;

	/* ---------- Cabin kết quả ---------- */
	case "cabinkq":          dt_guard($DT_MAN); dt_cabin_list();  $template = "daotao/cabinkq/items"; break;
	case "exportCabin":      dt_guard($DT_MAN); dt_cabin_export(); break;
	case "uploadCabin":      dt_guard($DT_UP);  dt_cabin_upload_form(); $template = "daotao/cabinkq/upload"; break;
	case "uploadCabinExcel": dt_guard($DT_UP);  dt_cabin_upload_excel(); break;

	/* ---------- Thực hành trong hình ---------- */
	case "hinh":     dt_guard($DT_MAN); dt_hinh_list(); $template = "daotao/hinh/items"; break;
	case "exportHinh": dt_guard($DT_MAN); dt_hinh_export(); break;
	case "saveHinh": dt_guard($DT_MAN); dt_hinh_save(); break;

	/* ---------- DAT ---------- */
	case "dat":            dt_guard($DT_MAN); dt_dat_list();  $template = "daotao/dat/items"; break;
	case "exportDat":      dt_guard($DT_MAN); dt_dat_export(); break;
	case "uploadDat":      dt_guard($DT_UP);  dt_dat_upload_form(); $template = "daotao/dat/upload"; break;
	case "uploadDatExcel": dt_guard($DT_UP);  dt_dat_upload_excel(); break;

	/* ---------- Tổng hợp ---------- */
	case "tonghop":       dt_guard($DT_MAN); dt_tonghop_list();   $template = "daotao/tonghop/items"; break;
	case "exportTonghop": dt_guard($DT_MAN); dt_tonghop_export(); break;

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
