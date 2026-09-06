<?php
if(!defined('SOURCES')) die("Error");

require_once LIBRARIES.'xangdau_config.php';

/* Các module chức năng của xăng dầu, được tách nhỏ để dễ bảo trì. */
require_once SOURCES.'xangdau/classes.php';
require_once SOURCES.'xangdau/permissions.php';
require_once SOURCES.'xangdau/schema.php';
require_once SOURCES.'xangdau/helpers.php';
require_once SOURCES.'xangdau/excel_cell.php';
require_once SOURCES.'xangdau/config.php';
require_once SOURCES.'xangdau/hoadon_crud.php';
require_once SOURCES.'xangdau/excel_convert.php';
require_once SOURCES.'xangdau/excel_stream.php';
require_once SOURCES.'xangdau/excel_open.php';
require_once SOURCES.'xangdau/hoadon_import.php';
require_once SOURCES.'xangdau/hocvien_crud.php';
require_once SOURCES.'xangdau/hocvien_import.php';
require_once SOURCES.'xangdau/algorithm.php';
require_once SOURCES.'xangdau/loc.php';
require_once SOURCES.'xangdau/export_list.php';
require_once SOURCES.'xangdau/export_quyettoan.php';
require_once SOURCES.'xangdau/export_excel.php';
require_once SOURCES.'xangdau/so_thanh_chu.php';
require_once SOURCES.'xangdau/export_tonghop.php';

switch($act)
{
	// ---- Cấu hình định mức ----
	case "config":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_get_config();
		$template = "xangdau/config/item_edit";
		break;
	case "saveConfig":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_save_config();
		break;

	// ---- Hóa đơn XD ----
	case "hoadon":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_get_hoadon();
		$template = "xangdau/hoadon/items";
		break;
	case "uploadHoadon":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		$template = "xangdau/uploadHoadon/items";
		break;
	case "uploadHoadonExcel":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_upload_hoadon_excel();
		break;
	case "deleteHoadon":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_delete_hoadon();
		break;
	case "deleteAllHoadon":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_delete_all_hoadon();
		break;

	// ---- Học viên XD ----
	case "hocvien":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_get_hocvien();
		$template = "xangdau/hocvien/items";
		break;
	case "uploadHocvien":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		$template = "xangdau/uploadHocvien/items";
		break;
	case "uploadHocvienExcel":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_upload_hocvien_excel();
		break;
	case "deleteHocvien":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_delete_hocvien();
		break;
	case "updateHocvienStatus":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_update_hocvien_status();
		break;
	case "deleteAllHocvien":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_delete_all_hocvien();
		break;

	// ---- Lọc thanh toán & bảng kê ----
	case "loc":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_loc_preview();
		$template = "xangdau/loc/items";
		break;
	case "xemGiaoVien":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_get_giao_vien_detail();
		$template = "xangdau/loc/detail";
		break;
	case "kiemTraGiaoVien":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_kiem_tra_giao_vien();
		break;
	case "huyKiemTraGiaoVien":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_huy_kiem_tra_giao_vien();
		break;
	case "duyetGiaoVien":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_duyet_giao_vien();
		break;
	case "duyetTatCaGiaoVien":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_duyet_tat_ca_giao_vien();
		break;
	case "locKiemTra":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_loc_kiem_tra();
		$template = "xangdau/loc/items_kiem_tra";
		break;
	case "locDuyet":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_loc_duyet();
		$template = "xangdau/loc/items_duyet";
		break;
	case "xuatToanBoDanhSachHocVien":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_xuat_toan_bo_danh_sach_hoc_vien();
		break;
	case "xuatTatCaBangKe":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_xuat_tat_ca_bang_ke();
		break;
	case "xuatTongHopGiaoVien":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_xuat_tong_hop_giao_vien();
		break;
	case "xuatBangKeGiaoVien":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_xuat_bangke_giao_vien();
		break;
	case "xuatBangKe":
		if(xd_permission_denied($act)) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		xd_ensure_tables();
		xd_xuat_bangke();
		break;

	default:
		$func->redirect("index.php?com=xangdau&act=config");
}
