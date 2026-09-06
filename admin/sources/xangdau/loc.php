<?php
if(!defined('SOURCES')) die("Error");

/* ============================ Lọc, kiểm tra & duyệt thanh toán ============================ */

function xd_loc_params_url($gvKey = '')
{
	$url = "index.php?com=xangdau&act=loc";
	foreach(array('ky', 'from_date', 'to_date') as $key) if(isset($_REQUEST[$key]) && $_REQUEST[$key] !== '') $url .= '&'.$key.'='.urlencode($_REQUEST[$key]);
	if($gvKey !== '') $url .= '&gv_key='.urlencode($gvKey);
	return $url;
}

function xd_get_giao_vien_detail()
{
	global $d, $xd_detail_gv, $xd_detail_hoadons, $xd_detail_hocviens, $xd_detail_config;
	$gvKey = isset($_REQUEST['gv_key']) ? trim((string)$_REQUEST['gv_key']) : '';
	$xd_detail_gv = array('gv_key' => $gvKey, 'gv_hoten' => $gvKey);
	$xd_detail_hoadons = array(); $xd_detail_hocviens = array(); $xd_detail_config = getXdConfig($d);
	if($gvKey === '') return;
	$row = $d->rawQueryOne("select max(gv_hoten) as gv_hoten from #_xd_hoadon where gv_key = ?", array($gvKey));
	if($row && $row['gv_hoten'] !== '') $xd_detail_gv['gv_hoten'] = $row['gv_hoten'];
	$xd_detail_hoadons = $d->rawQuery("select * from #_xd_hoadon where gv_key = ? and da_quyettoan = 0 order by ngay_hoa_don desc, id desc", array($gvKey));
	$xd_detail_hocviens = $d->rawQuery("select * from #_xd_hocvien where gv_key = ? and ngay_thanh_toan is null order by id asc", array($gvKey));
}

function xd_kiem_tra_giao_vien()
{
	global $d, $func;
	$gvKey = isset($_REQUEST['gv_key']) ? trim((string)$_REQUEST['gv_key']) : '';
	if($gvKey === '') $func->transfer("Không xác định được giáo viên.", xd_loc_params_url(), false);
	$d->rawQuery("update #_xd_hoadon set ke_toan_kiem_tra = 1 where gv_key = ? and da_quyettoan = 0", array($gvKey));
	$d->rawQuery("update #_xd_hocvien set ke_toan_kiem_tra = 1 where gv_key = ? and ngay_thanh_toan is null", array($gvKey));
	$func->transfer("Đã ghi nhận kế toán kiểm tra giáo viên.", xd_loc_params_url(), true);
}

/**
 * Chuyển trạng thái ngược lại (chưa kiểm tra) cho giáo viên đã kiểm tra — dùng khi kiểm tra nhầm.
 * Chỉ áp dụng cho hóa đơn/học viên chưa quyết toán (đã quyết toán thì không còn hiển thị ở đây).
 */
function xd_huy_kiem_tra_giao_vien()
{
	global $d, $func;
	$gvKey = isset($_REQUEST['gv_key']) ? trim((string)$_REQUEST['gv_key']) : '';
	if($gvKey === '') $func->transfer("Không xác định được giáo viên.", xd_loc_params_url(), false);
	$d->rawQuery("update #_xd_hoadon set ke_toan_kiem_tra = 0 where gv_key = ? and da_quyettoan = 0", array($gvKey));
	$d->rawQuery("update #_xd_hocvien set ke_toan_kiem_tra = 0 where gv_key = ? and ngay_thanh_toan is null", array($gvKey));
	$func->transfer("Đã chuyển giáo viên về trạng thái chưa kiểm tra.", xd_loc_params_url(), true);
}

function xd_duyet_giao_vien()
{
	global $d, $func;
	$gvKey = isset($_REQUEST['gv_key']) ? trim((string)$_REQUEST['gv_key']) : '';
	$ky = isset($_REQUEST['ky']) ? trim((string)$_REQUEST['ky']) : '';
	$fromDate = (isset($_REQUEST['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['from_date'])) ? $_REQUEST['from_date'] : '';
	$toDate = (isset($_REQUEST['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['to_date'])) ? $_REQUEST['to_date'] : '';
	if($gvKey === '') $func->transfer("Không xác định được giáo viên.", xd_loc_params_url(), false);
	$notChecked = $d->rawQueryOne("select count(*) as total from #_xd_hoadon where gv_key = ? and da_quyettoan = 0 and ke_toan_kiem_tra = 0", array($gvKey));
	if($notChecked && (int)$notChecked['total'] > 0) $func->transfer("Kế toán chưa kiểm tra hết hóa đơn của giáo viên.", xd_loc_params_url(), false);
	$notCheckedStudents = $d->rawQueryOne("select count(*) as total from #_xd_hocvien where gv_key = ? and ngay_thanh_toan is null and ke_toan_kiem_tra = 0", array($gvKey));
	if($notCheckedStudents && (int)$notCheckedStudents['total'] > 0) $func->transfer("Kế toán chưa kiểm tra hết học viên của giáo viên.", xd_loc_params_url(), false);
	list($selected, $summary, $config) = xd_run_algorithm($d, $ky, $fromDate, $toDate);
	$selectedTeacher = array(); foreach($selected as $student) if($student['gv_key'] === $gvKey) $selectedTeacher[] = $student;
	if(empty($selectedTeacher)) $func->transfer("Không có học viên đủ điều kiện để duyệt cho giáo viên này.", xd_loc_params_url(), false);
	$today = date('Y-m-d'); $username = xd_username(); $total = 0; foreach($selectedTeacher as $student) $total += (float)$student['so_tien_thanh_toan'];
	$d->startTransaction();
	$ok = $d->rawQuery("insert into #_xd_bangke (ngay_lap, ky, tong_hocvien, tong_tien, user_tao, ngaytao) values (?, ?, ?, ?, ?, ?)", array($today, $ky, count($selectedTeacher), $total, $username, time()));
	if($ok === false) { $d->rollback(); $func->transfer("Không tạo được đợt duyệt.", xd_loc_params_url(), false); }
	$idBangke = (int)$d->getLastInsertId();
	foreach($selectedTeacher as $student) $d->rawQuery("update #_xd_hocvien set ngay_thanh_toan = ?, dinh_muc = ?, so_tien_thanh_toan = ?, id_bangke = ?, quan_ly_duyet = 1 where id = ? and ngay_thanh_toan is null", array($today, $student['dinh_muc'], $student['so_tien_thanh_toan'], $idBangke, (int)$student['id']));
	$invoiceWhere = 'gv_key = ? and da_quyettoan = 0'; $params = array($idBangke, $gvKey);
	if($ky !== '') { $invoiceWhere .= ' and ky = ?'; $params[] = $ky; }
	if($fromDate !== '') { $invoiceWhere .= ' and ngay_hoa_don >= ?'; $params[] = $fromDate; }
	if($toDate !== '') { $invoiceWhere .= ' and ngay_hoa_don <= ?'; $params[] = $toDate; }
	$d->rawQuery("update #_xd_hoadon set da_quyettoan = 1, quan_ly_duyet = 1, id_bangke = ? where $invoiceWhere", $params);
	$d->commit();
	$func->transfer("Đã duyệt và ghi nhận thanh toán cho giáo viên.", xd_loc_params_url(), true);
}

function xd_duyet_tat_ca_giao_vien()
{
	global $d, $func;
	$ky = isset($_REQUEST['ky']) ? trim((string)$_REQUEST['ky']) : '';
	$fromDate = (isset($_REQUEST['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['from_date'])) ? $_REQUEST['from_date'] : '';
	$toDate = (isset($_REQUEST['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['to_date'])) ? $_REQUEST['to_date'] : '';
	$rows = $d->rawQuery("select distinct gv_key from #_xd_hoadon where gv_key <> '' and da_quyettoan = 0 and ke_toan_kiem_tra = 1", array());
	$approved = 0;
	foreach($rows as $row)
	{
		$gvKey = $row['gv_key'];
		$notCheckedStudents = $d->rawQueryOne("select count(*) as total from #_xd_hocvien where gv_key = ? and ngay_thanh_toan is null and ke_toan_kiem_tra = 0", array($gvKey));
		if($notCheckedStudents && (int)$notCheckedStudents['total'] > 0) continue;
		list($selected, $summary, $config) = xd_run_algorithm($d, $ky, $fromDate, $toDate);
		$students = array(); foreach($selected as $student) if($student['gv_key'] === $gvKey) $students[] = $student;
		if(empty($students)) continue;
		$today = date('Y-m-d'); $username = xd_username(); $total = 0; foreach($students as $student) $total += (float)$student['so_tien_thanh_toan'];
		$d->startTransaction();
		$ok = $d->rawQuery("insert into #_xd_bangke (ngay_lap, ky, tong_hocvien, tong_tien, user_tao, ngaytao) values (?, ?, ?, ?, ?, ?)", array($today, $ky, count($students), $total, $username, time()));
		if($ok === false) { $d->rollback(); continue; }
		$idBangke = (int)$d->getLastInsertId();
		foreach($students as $student) $d->rawQuery("update #_xd_hocvien set ngay_thanh_toan = ?, dinh_muc = ?, so_tien_thanh_toan = ?, id_bangke = ?, quan_ly_duyet = 1 where id = ? and ngay_thanh_toan is null", array($today, $student['dinh_muc'], $student['so_tien_thanh_toan'], $idBangke, (int)$student['id']));
		$invoiceWhere = 'gv_key = ? and da_quyettoan = 0'; $invoiceParams = array($idBangke, $gvKey);
		if($ky !== '') { $invoiceWhere .= ' and ky = ?'; $invoiceParams[] = $ky; }
		if($fromDate !== '') { $invoiceWhere .= ' and ngay_hoa_don >= ?'; $invoiceParams[] = $fromDate; }
		if($toDate !== '') { $invoiceWhere .= ' and ngay_hoa_don <= ?'; $invoiceParams[] = $toDate; }
		$d->rawQuery("update #_xd_hoadon set da_quyettoan = 1, quan_ly_duyet = 1, id_bangke = ? where $invoiceWhere", $invoiceParams);
		$d->commit(); $approved++;
	}
	$func->transfer("Đã duyệt $approved giáo viên.", xd_loc_params_url(), true);
}

function xd_loc_preview()
{
	global $d, $xd_loc_selected, $xd_loc_summary, $xd_loc_config, $xd_loc_ky, $xd_loc_from, $xd_loc_to, $xd_loc_ky_options;

	$xd_loc_ky = isset($_REQUEST['ky']) ? trim((string)$_REQUEST['ky']) : '';
	$xd_loc_from = (isset($_REQUEST['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['from_date'])) ? $_REQUEST['from_date'] : '';
	$xd_loc_to = (isset($_REQUEST['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['to_date'])) ? $_REQUEST['to_date'] : '';

	$xd_loc_ky_options = $d->rawQuery("select distinct ky from #_xd_hoadon where ky <> '' and da_quyettoan = 0 order by ky asc");

	list($selected, $summary, $config) = xd_run_algorithm($d, $xd_loc_ky, $xd_loc_from, $xd_loc_to);
	$xd_loc_selected = $selected;
	$xd_loc_summary = $summary;
	$xd_loc_config = $config;
}

function xd_loc_kiem_tra()
{
	global $d, $xd_loc_kiem_tra_data, $xd_loc_ky_options;
	
	$xd_loc_ky_options = $d->rawQuery("select distinct ky from #_xd_hoadon where ky <> '' and da_quyettoan = 0 order by ky asc");
	
	// Lấy tất cả giáo viên có hóa đơn/học viên chưa kiểm tra
	$hoadons = $d->rawQuery(
		"select distinct gv_key, max(gv_hoten) as gv_hoten from #_xd_hoadon where gv_key <> '' and da_quyettoan = 0 and ke_toan_kiem_tra = 0 order by gv_hoten asc"
	);
	$hocviens = $d->rawQuery(
		"select distinct gv_key, max(gv_hoten) as gv_hoten from #_xd_hocvien where gv_key <> '' and ngay_thanh_toan is null and ke_toan_kiem_tra = 0 order by gv_hoten asc"
	);
	
	$data = array();
	$seen = array();
	foreach(array_merge($hoadons, $hocviens) as $row)
	{
		$key = $row['gv_key'];
		if(!isset($seen[$key]))
		{
			$data[] = array('gv_key' => $row['gv_key'], 'gv_hoten' => $row['gv_hoten']);
			$seen[$key] = 1;
		}
	}
	
	$xd_loc_kiem_tra_data = $data;
}

function xd_loc_duyet()
{
	global $d, $xd_loc_duyet_data, $xd_loc_ky_options;
	
	$xd_loc_ky_options = $d->rawQuery("select distinct ky from #_xd_hoadon where ky <> '' and da_quyettoan = 0 order by ky asc");
	
	// Lấy tất cả giáo viên đã kiểm tra nhưng chưa duyệt
	$hoadons = $d->rawQuery(
		"select distinct gv_key, max(gv_hoten) as gv_hoten from #_xd_hoadon where gv_key <> '' and da_quyettoan = 0 and ke_toan_kiem_tra = 1 order by gv_hoten asc"
	);
	$hocviens = $d->rawQuery(
		"select distinct gv_key, max(gv_hoten) as gv_hoten from #_xd_hocvien where gv_key <> '' and ngay_thanh_toan is null and ke_toan_kiem_tra = 1 order by gv_hoten asc"
	);
	
	$data = array();
	$seen = array();
	foreach(array_merge($hoadons, $hocviens) as $row)
	{
		$key = $row['gv_key'];
		if(!isset($seen[$key]))
		{
			$data[] = array('gv_key' => $row['gv_key'], 'gv_hoten' => $row['gv_hoten']);
			$seen[$key] = 1;
		}
	}
	
	$xd_loc_duyet_data = $data;
}
