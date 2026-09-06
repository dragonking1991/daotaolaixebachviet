<?php
if(!defined('SOURCES')) die("Error");

/* ============================ Quyết toán & xuất bảng kê ============================ */

function xd_xuat_bangke()
{
	global $d, $func;

	$ky = isset($_REQUEST['ky']) ? trim((string)$_REQUEST['ky']) : '';
	$fromDate = (isset($_REQUEST['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['from_date'])) ? $_REQUEST['from_date'] : '';
	$toDate = (isset($_REQUEST['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['to_date'])) ? $_REQUEST['to_date'] : '';

	$backUrl = "index.php?com=xangdau&act=loc";
	if($ky !== '') $backUrl .= '&ky='.urlencode($ky);

	list($selected, $summary, $config) = xd_run_algorithm($d, $ky, $fromDate, $toDate);

	if(empty($selected)) $func->transfer("Không có học viên nào đủ điều kiện thanh toán để xuất bảng kê.", $backUrl, false);

	$username = xd_username();
	$today = date('Y-m-d');
	$tongTien = 0.0;
	foreach($selected as $hv) $tongTien += (float)$hv['so_tien_thanh_toan'];

	// Quyết toán trong giao dịch
	$d->startTransaction();

	$okBangke = $d->rawQuery(
		"insert into #_xd_bangke (ngay_lap, ky, tong_hocvien, tong_tien, user_tao, ngaytao) values (?, ?, ?, ?, ?, ?)",
		array($today, $ky, count($selected), $tongTien, $username, time())
	);
	if($okBangke === false)
	{
		$d->rollback();
		$func->transfer("Không tạo được bảng kê. Vui lòng thử lại.", $backUrl, false);
	}
	$idBangke = (int)$d->getLastInsertId();

	// Ghi ngày thanh toán cho học viên được chọn (điều kiện lại ngay_thanh_toan is null để chống race)
	foreach($selected as $hv)
	{
		$d->rawQuery(
			"update #_xd_hocvien set ngay_thanh_toan = ?, dinh_muc = ?, so_tien_thanh_toan = ?, id_bangke = ? where id = ? and ngay_thanh_toan is null",
			array($today, $hv['dinh_muc'], $hv['so_tien_thanh_toan'], $idBangke, (int)$hv['id'])
		);
	}

	// Khóa hóa đơn đã dùng của các GV có học viên được trích (theo gv_key)
	$gvList = array();
	foreach($selected as $hv) $gvList[$hv['gv_key']] = 1;
	foreach(array_keys($gvList) as $gvKey)
	{
		if($gvKey === '') continue;
		$hoadonWhere = " and da_quyettoan = 0";
		$params = array($idBangke, $gvKey);
		if($ky !== '') { $hoadonWhere .= " and ky = ?"; $params[] = $ky; }
		if($fromDate !== '') { $hoadonWhere .= " and ngay_hoa_don >= ?"; $params[] = $fromDate; }
		if($toDate !== '') { $hoadonWhere .= " and ngay_hoa_don <= ?"; $params[] = $toDate; }
		$d->rawQuery("update #_xd_hoadon set da_quyettoan = 1, id_bangke = ? where gv_key = ? $hoadonWhere", $params);
	}

	$d->commit();

	// Xuất file Excel bảng kê (mỗi giáo viên một sheet, dựng lại từ dữ liệu đã quyết toán của đợt)
	xd_export_bangke_excel($d, $idBangke, $today, $ky);
	// xd_export_bangke_excel sẽ exit sau khi stream file
}
