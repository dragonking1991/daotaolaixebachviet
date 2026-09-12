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
	global $d, $xd_detail_gv, $xd_detail_hoadons, $xd_detail_hocviens, $xd_detail_hoadons_da_thanh_toan, $xd_detail_hocviens_da_thanh_toan, $xd_detail_config, $xd_detail_da_kiem_tra, $xd_detail_da_thanh_toan;
	$gvKey = isset($_REQUEST['gv_key']) ? trim((string)$_REQUEST['gv_key']) : '';
	$xd_detail_gv = array('gv_key' => $gvKey, 'gv_hoten' => $gvKey);
	$xd_detail_hoadons = array(); $xd_detail_hocviens = array(); $xd_detail_hoadons_da_thanh_toan = array(); $xd_detail_hocviens_da_thanh_toan = array(); $xd_detail_config = getXdConfig($d); $xd_detail_da_kiem_tra = false; $xd_detail_da_thanh_toan = isset($_REQUEST['paid']) && $_REQUEST['paid'] === '1';
	if($gvKey === '') return;
	$row = $d->rawQueryOne("select max(gv_hoten) as gv_hoten from #_xd_hoadon where gv_key = ?", array($gvKey));
	if($row && $row['gv_hoten'] !== '') $xd_detail_gv['gv_hoten'] = $row['gv_hoten'];
	$xd_detail_hoadons_da_thanh_toan = $d->rawQuery("select * from #_xd_hoadon where gv_key = ? and da_quyettoan = 1 order by ngay_hoa_don desc, id desc", array($gvKey));
	$xd_detail_hocviens_da_thanh_toan = $d->rawQuery("select * from #_xd_hocvien where gv_key = ? and ngay_thanh_toan is not null order by id asc", array($gvKey));
	$xd_detail_hoadons = $d->rawQuery("select * from #_xd_hoadon where gv_key = ? and da_quyettoan = 0 order by ngay_hoa_don desc, id desc", array($gvKey));
	$xd_detail_hocviens = $d->rawQuery("select * from #_xd_hocvien where gv_key = ? and ngay_thanh_toan is null order by id asc", array($gvKey));
	if($xd_detail_da_thanh_toan) return;
	$chuaKiemTraHoaDon = $d->rawQueryOne("select count(*) as total from #_xd_hoadon where gv_key = ? and da_quyettoan = 0 and ke_toan_kiem_tra = 0", array($gvKey));
	$chuaKiemTraHocVien = $d->rawQueryOne("select count(*) as total from #_xd_hocvien where gv_key = ? and ngay_thanh_toan is null and ke_toan_kiem_tra = 0", array($gvKey));
	$xd_detail_da_kiem_tra = (int)($chuaKiemTraHoaDon['total'] ?? 0) === 0 && (int)($chuaKiemTraHocVien['total'] ?? 0) === 0;
}

function xd_kiem_tra_giao_vien()
{
	global $d, $func;
	$gvKey = isset($_REQUEST['gv_key']) ? trim((string)$_REQUEST['gv_key']) : '';
	if($gvKey === '') $func->transfer("Không xác định được giáo viên.", xd_loc_params_url(), false);
	$d->rawQuery("update #_xd_hoadon set ke_toan_kiem_tra = 1, ngay_kiem_tra = ? where gv_key = ? and da_quyettoan = 0", array(date('Y-m-d'), $gvKey));
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
	$d->rawQuery("update #_xd_hoadon set ke_toan_kiem_tra = 0, ngay_kiem_tra = null where gv_key = ? and da_quyettoan = 0", array($gvKey));
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
	$invoiceWhere = 'gv_key = ? and da_quyettoan = 0 and hop_le = 1'; $params = array($idBangke, $gvKey);
	if($ky !== '') { $invoiceWhere .= ' and ky = ?'; $params[] = $ky; }
	if($fromDate !== '') { $invoiceWhere .= ' and ngay_hoa_don >= ?'; $params[] = $fromDate; }
	if($toDate !== '') { $invoiceWhere .= ' and ngay_hoa_don <= ?'; $params[] = $toDate; }
	$d->rawQuery("update #_xd_hoadon set da_quyettoan = 1, ngay_thanh_toan = ?, quan_ly_duyet = 1, id_bangke = ? where $invoiceWhere", array_merge(array($today), $params));
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
		$invoiceWhere = 'gv_key = ? and da_quyettoan = 0 and hop_le = 1'; $invoiceParams = array($idBangke, $gvKey);
		if($ky !== '') { $invoiceWhere .= ' and ky = ?'; $invoiceParams[] = $ky; }
		if($fromDate !== '') { $invoiceWhere .= ' and ngay_hoa_don >= ?'; $invoiceParams[] = $fromDate; }
		if($toDate !== '') { $invoiceWhere .= ' and ngay_hoa_don <= ?'; $invoiceParams[] = $toDate; }
		$d->rawQuery("update #_xd_hoadon set da_quyettoan = 1, ngay_thanh_toan = ?, quan_ly_duyet = 1, id_bangke = ? where $invoiceWhere", array_merge(array($today), $invoiceParams));
		$d->commit(); $approved++;
	}
	$func->transfer("Đã duyệt $approved giáo viên.", xd_loc_params_url(), true);
}

/**
 * Hủy duyệt (hoàn tác quyết toán) cho một giáo viên: đưa hóa đơn & học viên đã duyệt của giáo viên
 * về trạng thái chưa quyết toán/chưa thanh toán và xóa các đợt bảng kê tương ứng.
 * Chỉ hoàn tác các bản ghi đã duyệt qua đợt (id_bangke > 0); không đụng tới thanh toán thủ công (id_bangke = 0).
 */
function xd_huy_duyet_giao_vien()
{
	global $d, $func;
	$redirect = "index.php?com=xangdau&act=locDaThanhToan";
	$gvKey = isset($_REQUEST['gv_key']) ? trim((string)$_REQUEST['gv_key']) : '';
	if($gvKey === '') $func->transfer("Không xác định được giáo viên.", $redirect, false);

	$rows = $d->rawQuery("select distinct id_bangke from #_xd_hoadon where gv_key = ? and da_quyettoan = 1 and id_bangke > 0", array($gvKey));
	$bangkeIds = array();
	if(!empty($rows)) foreach($rows as $row) { $id = (int)$row['id_bangke']; if($id > 0) $bangkeIds[$id] = $id; }
	if(empty($bangkeIds)) $func->transfer("Giáo viên chưa có đợt duyệt nào để hủy.", $redirect, false);

	$placeholders = implode(',', array_fill(0, count($bangkeIds), '?'));
	$ids = array_values($bangkeIds);

	$d->startTransaction();
	$hv = $d->rawQuery("update #_xd_hocvien set ngay_thanh_toan = null, id_bangke = 0, quan_ly_duyet = 0, so_tien_thanh_toan = 0, dinh_muc = 0 where id_bangke in ($placeholders)", $ids);
	$hd = $d->rawQuery("update #_xd_hoadon set da_quyettoan = 0, ngay_thanh_toan = null, quan_ly_duyet = 0, id_bangke = 0 where id_bangke in ($placeholders)", $ids);
	$bk = $d->rawQuery("delete from #_xd_bangke where id in ($placeholders)", $ids);
	if($hv === false || $hd === false || $bk === false) { $d->rollback(); $func->transfer("Không hủy duyệt được. Vui lòng thử lại.", $redirect, false); }
	$d->commit();

	$func->transfer("Đã hủy duyệt giáo viên và hoàn tác ".count($bangkeIds)." đợt. Hóa đơn/học viên đã trở về trạng thái chờ duyệt.", $redirect, true);
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
		"select gv_key, max(gv_hoten) as gv_hoten from #_xd_hoadon where gv_key <> '' and da_quyettoan = 0 and ke_toan_kiem_tra = 0 group by gv_key order by max(gv_hoten) asc"
	);
	$hocviens = $d->rawQuery(
		"select gv_key, max(gv_hoten) as gv_hoten from #_xd_hocvien where gv_key <> '' and ngay_thanh_toan is null and ke_toan_kiem_tra = 0 group by gv_key order by max(gv_hoten) asc"
	);
	
	$data = array();
	$seen = array();
	foreach(array_merge(is_array($hoadons) ? $hoadons : array(), is_array($hocviens) ? $hocviens : array()) as $row)
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
		"select h.gv_key, max(h.gv_hoten) as gv_hoten, max(h.ngay_kiem_tra) as ngay_kiem_tra from #_xd_hoadon h
			where h.gv_key <> '' and h.da_quyettoan = 0 and h.ke_toan_kiem_tra = 1
			and not exists (select 1 from #_xd_hoadon paid where paid.gv_key = h.gv_key and paid.da_quyettoan = 1)
			group by h.gv_key order by max(h.gv_hoten) asc"
	);
	$hocviens = $d->rawQuery(
		"select h.gv_key, max(h.gv_hoten) as gv_hoten, (select max(ngay_kiem_tra) from #_xd_hoadon checked where checked.gv_key = h.gv_key and checked.da_quyettoan = 0 and checked.ke_toan_kiem_tra = 1) as ngay_kiem_tra from #_xd_hocvien h
			where h.gv_key <> '' and h.ngay_thanh_toan is null and h.ke_toan_kiem_tra = 1
			and not exists (select 1 from #_xd_hoadon paid where paid.gv_key = h.gv_key and paid.da_quyettoan = 1)
			group by h.gv_key order by max(h.gv_hoten) asc"
	);
	
	$data = array();
	$seen = array();
	foreach(array_merge(is_array($hoadons) ? $hoadons : array(), is_array($hocviens) ? $hocviens : array()) as $row)
	{
		$key = $row['gv_key'];
		if(!isset($seen[$key]))
		{
			$data[] = array('gv_key' => $row['gv_key'], 'gv_hoten' => $row['gv_hoten'], 'ngay_kiem_tra' => $row['ngay_kiem_tra'] ?? '');
			$seen[$key] = 1;
		}
	}
	
	$xd_loc_duyet_data = $data;
}

function xd_loc_da_thanh_toan()
{
	global $d, $xd_loc_dathanhtoan_data, $xd_loc_ky_options, $xd_loc_paid_from, $xd_loc_paid_to, $xd_loc_paid_keyword;

	$xd_loc_ky_options = $d->rawQuery("select distinct ky from #_xd_hoadon where ky <> '' order by ky asc");
	$xd_loc_paid_from = (isset($_REQUEST['paid_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['paid_from'])) ? $_REQUEST['paid_from'] : '';
	$xd_loc_paid_to = (isset($_REQUEST['paid_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['paid_to'])) ? $_REQUEST['paid_to'] : '';
	$xd_loc_paid_keyword = isset($_REQUEST['keyword']) ? trim((string)$_REQUEST['keyword']) : '';
	$where = 'b.gv_key <> "" and b.da_quyettoan = 1';
	$params = array();
	if($xd_loc_paid_keyword !== '') { $where .= ' and (b.gv_hoten like ? or b.gv_key like ?)'; $params[] = '%'.$xd_loc_paid_keyword.'%'; $params[] = '%'.$xd_loc_paid_keyword.'%'; }
	if($xd_loc_paid_from !== '') { $where .= ' and b.ngay_thanh_toan >= ?'; $params[] = $xd_loc_paid_from; }
	if($xd_loc_paid_to !== '') { $where .= ' and b.ngay_thanh_toan <= ?'; $params[] = $xd_loc_paid_to; }

	// Giáo viên có ít nhất 1 hóa đơn hoặc học viên đã quyết toán/thanh toán
	$rows = $d->rawQuery(
		"select b.gv_key, max(b.gv_hoten) as gv_hoten, sum(b.tong_tien) as tong_tien, count(*) as so_hd
			, min(b.ngay_thanh_toan) as ngay_thanh_toan, max(b.ngay_thanh_toan) as ngay_thanh_toan_den
		 from #_xd_hoadon b where $where group by b.gv_key order by gv_hoten asc",
		$params
	);

	$xd_loc_dathanhtoan_data = $rows;
}
