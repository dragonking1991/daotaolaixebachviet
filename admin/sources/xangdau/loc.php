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
	$xd_detail_hocviens_da_thanh_toan = $d->rawQuery("select * from #_xd_hocvien where gv_key = ? and ngay_thanh_toan is not null order by ".xd_hocvien_order_clause(), array($gvKey));
	$xd_detail_hoadons = $d->rawQuery("select * from #_xd_hoadon where gv_key = ? and da_quyettoan = 0 order by ngay_hoa_don desc, id desc", array($gvKey));
	$xd_detail_hocviens = $d->rawQuery("select * from #_xd_hocvien where gv_key = ? and ngay_thanh_toan is null order by ".xd_hocvien_order_clause(), array($gvKey));
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

/* Duyệt một giáo viên trong một transaction: tạo bảng kê, ghi thanh toán học viên và quyết toán hóa đơn; lỗi bất kỳ bước nào thì hoàn tác toàn bộ. */
function xd_loc_approve_teacher($gvKey, $students, $ky, $fromDate, $toDate)
{
	global $d;
	$today = date('Y-m-d'); $username = xd_username(); $total = 0;
	foreach($students as $student) $total += (float)$student['so_tien_thanh_toan'];
	$run = function($sql, $params) use ($d) { $d->rawQuery($sql, $params); return (string)$d->getLastErrorCode() === '00000'; };
	$d->startTransaction();
	try
	{
		if(!$run("insert into #_xd_bangke (ngay_lap, ky, tong_hocvien, tong_tien, user_tao, ngaytao) values (?, ?, ?, ?, ?, ?)", array($today, $ky, count($students), $total, $username, time())))
			throw new RuntimeException('bangke');
		$idBangke = (int)$d->getLastInsertId();
		if($idBangke <= 0) throw new RuntimeException('bangke id');
		foreach($students as $student)
			if(!$run("update #_xd_hocvien set ngay_thanh_toan = ?, dinh_muc = ?, so_tien_thanh_toan = ?, id_bangke = ?, quan_ly_duyet = 1 where id = ? and ngay_thanh_toan is null", array($today, $student['dinh_muc'], $student['so_tien_thanh_toan'], $idBangke, (int)$student['id'])))
				throw new RuntimeException('hocvien');
		$invoiceWhere = 'gv_key = ? and da_quyettoan = 0 and hop_le = 1'; $params = array($idBangke, $gvKey);
		if($ky !== '') { $invoiceWhere .= ' and ky = ?'; $params[] = $ky; }
		if($fromDate !== '') { $invoiceWhere .= ' and ngay_hoa_don >= ?'; $params[] = $fromDate; }
		if($toDate !== '') { $invoiceWhere .= ' and ngay_hoa_don <= ?'; $params[] = $toDate; }
		if(!$run("update #_xd_hoadon set da_quyettoan = 1, ngay_thanh_toan = ?, quan_ly_duyet = 1, id_bangke = ? where $invoiceWhere", array_merge(array($today), $params)))
			throw new RuntimeException('hoadon');
		$d->commit();
		return true;
	}
	catch(Exception $error)
	{
		$d->rollback();
		return false;
	}
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
	if(!xd_loc_approve_teacher($gvKey, $selectedTeacher, $ky, $fromDate, $toDate))
		$func->transfer("Không thể duyệt giáo viên; dữ liệu đã được hoàn tác, chưa ghi nhận thanh toán nào.", xd_loc_params_url(), false);
	$func->transfer("Đã duyệt và ghi nhận thanh toán cho giáo viên.", xd_loc_params_url(), true);
}

function xd_duyet_tat_ca_giao_vien()
{
	global $d, $func;
	$ky = isset($_REQUEST['ky']) ? trim((string)$_REQUEST['ky']) : '';
	$fromDate = (isset($_REQUEST['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['from_date'])) ? $_REQUEST['from_date'] : '';
	$toDate = (isset($_REQUEST['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['to_date'])) ? $_REQUEST['to_date'] : '';
	$rows = $d->rawQuery("select distinct gv_key from #_xd_hoadon where gv_key <> '' and da_quyettoan = 0 and ke_toan_kiem_tra = 1", array());
	$approved = 0; $failed = 0;
	list($selected, $summary, $config) = xd_run_algorithm($d, $ky, $fromDate, $toDate);
	foreach($rows as $row)
	{
		$gvKey = $row['gv_key'];
		$notCheckedStudents = $d->rawQueryOne("select count(*) as total from #_xd_hocvien where gv_key = ? and ngay_thanh_toan is null and ke_toan_kiem_tra = 0", array($gvKey));
		if($notCheckedStudents && (int)$notCheckedStudents['total'] > 0) continue;
		$students = array(); foreach($selected as $student) if($student['gv_key'] === $gvKey) $students[] = $student;
		if(empty($students)) continue;
		if(xd_loc_approve_teacher($gvKey, $students, $ky, $fromDate, $toDate)) $approved++; else $failed++;
	}
	if($failed > 0) $func->transfer("Đã duyệt $approved giáo viên; $failed giáo viên lỗi đã được hoàn tác, vui lòng thử lại.", xd_loc_params_url(), false);
	$func->transfer("Đã duyệt $approved giáo viên.", xd_loc_params_url(), true);
}

/**
 * Hủy duyệt đúng một ngày thanh toán của giáo viên; các ngày khác và thanh toán thủ công được giữ nguyên.
 */
function xd_huy_duyet_giao_vien()
{
	global $d, $func;
	$redirect = "index.php?com=xangdau&act=locDaThanhToan";
	$redirectParams = array();
	foreach(array('keyword', 'paid_from', 'paid_to') as $key)
	{
		$value = isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
		if($value !== '' && ($key === 'keyword' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)))
			$redirectParams[$key] = $value;
	}
	if(!empty($redirectParams)) $redirect .= '&'.http_build_query($redirectParams);

	$gvKey = isset($_POST['gv_key']) ? trim((string)$_POST['gv_key']) : '';
	$paymentDate = (isset($_POST['payment_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['payment_date'])) ? $_POST['payment_date'] : '';
	if($gvKey === '') $func->transfer("Không xác định được giáo viên.", $redirect, false);
	$dateParts = $paymentDate !== '' ? explode('-', $paymentDate) : array();
	if(count($dateParts) !== 3 || !checkdate((int)$dateParts[1], (int)$dateParts[2], (int)$dateParts[0]))
		$func->transfer("Vui lòng chọn ngày thanh toán hợp lệ cần hủy.", $redirect, false);

	$invoiceRows = $d->rawQuery("select distinct id_bangke from #_xd_hoadon where gv_key = ? and ngay_thanh_toan = ? and da_quyettoan = 1 and id_bangke > 0", array($gvKey, $paymentDate));
	$studentRows = $d->rawQuery("select distinct id_bangke from #_xd_hocvien where gv_key = ? and ngay_thanh_toan = ? and id_bangke > 0", array($gvKey, $paymentDate));
	if($invoiceRows === false || $studentRows === false)
		$func->transfer("Không tải được dữ liệu thanh toán để hủy. Vui lòng thử lại.", $redirect, false);
	$bangkeIds = array();
	foreach(array_merge(is_array($invoiceRows) ? $invoiceRows : array(), is_array($studentRows) ? $studentRows : array()) as $row)
	{
		$id = (int)$row['id_bangke'];
		if($id > 0) $bangkeIds[$id] = $id;
	}
	if(empty($bangkeIds)) $func->transfer("Giáo viên chưa có đợt duyệt để hủy trong ngày ".date('d/m/Y', strtotime($paymentDate)).".", $redirect, false);

	$placeholders = implode(',', array_fill(0, count($bangkeIds), '?'));
	$ids = array_values($bangkeIds);

	$d->startTransaction();
	$hv = $d->rawQuery("update #_xd_hocvien set ngay_thanh_toan = null, id_bangke = 0, quan_ly_duyet = 0, so_tien_thanh_toan = 0, dinh_muc = 0 where gv_key = ? and ngay_thanh_toan = ? and id_bangke in ($placeholders)", array_merge(array($gvKey, $paymentDate), $ids));
	$hd = $d->rawQuery("update #_xd_hoadon set da_quyettoan = 0, ngay_thanh_toan = null, quan_ly_duyet = 0, id_bangke = 0 where gv_key = ? and ngay_thanh_toan = ? and da_quyettoan = 1 and id_bangke in ($placeholders)", array_merge(array($gvKey, $paymentDate), $ids));
	if($hv === false || $hd === false)
	{
		$d->rollback();
		$func->transfer("Không hủy duyệt được. Vui lòng thử lại.", $redirect, false);
	}

	foreach($ids as $id)
	{
		$remainingStudents = $d->rawQueryOne("select count(*) as total, coalesce(sum(so_tien_thanh_toan), 0) as tong_tien from #_xd_hocvien where id_bangke = ? and ngay_thanh_toan is not null", array($id));
		$remainingInvoices = $d->rawQueryOne("select count(*) as total from #_xd_hoadon where id_bangke = ? and da_quyettoan = 1", array($id));
		if(!is_array($remainingStudents) || !is_array($remainingInvoices))
		{
			$d->rollback();
			$func->transfer("Không thể xác minh dữ liệu còn lại của bảng kê. Vui lòng thử lại.", $redirect, false);
		}
		$studentCount = (int)($remainingStudents['total'] ?? 0);
		$invoiceCount = (int)($remainingInvoices['total'] ?? 0);
		if($studentCount === 0 && $invoiceCount === 0)
			$bk = $d->rawQuery("delete from #_xd_bangke where id = ?", array($id));
		else
			$bk = $d->rawQuery("update #_xd_bangke set tong_hocvien = ?, tong_tien = ? where id = ?", array($studentCount, (float)($remainingStudents['tong_tien'] ?? 0), $id));
		if($bk === false)
		{
			$d->rollback();
			$func->transfer("Không cập nhật được bảng kê sau khi hủy duyệt. Vui lòng thử lại.", $redirect, false);
		}
	}
	$d->commit();

	$func->transfer("Đã hủy duyệt ngày ".date('d/m/Y', strtotime($paymentDate))." cho giáo viên; các ngày thanh toán khác được giữ nguyên.", $redirect, true);
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
	$invoiceWhere = 'gv_key <> "" and da_quyettoan = 1';
	$studentWhere = 'gv_key <> "" and ngay_thanh_toan is not null';
	$invoiceParams = array();
	$studentParams = array();
	if($xd_loc_paid_keyword !== '')
	{
		$invoiceWhere .= ' and (gv_hoten like ? or gv_key like ?)';
		$studentWhere .= ' and (gv_hoten like ? or gv_key like ?)';
		$invoiceParams[] = '%'.$xd_loc_paid_keyword.'%'; $invoiceParams[] = '%'.$xd_loc_paid_keyword.'%';
		$studentParams[] = '%'.$xd_loc_paid_keyword.'%'; $studentParams[] = '%'.$xd_loc_paid_keyword.'%';
	}
	if($xd_loc_paid_from !== '')
	{
		$invoiceWhere .= ' and ngay_thanh_toan >= ?';
		$studentWhere .= ' and ngay_thanh_toan >= ?';
		$invoiceParams[] = $xd_loc_paid_from; $studentParams[] = $xd_loc_paid_from;
	}
	if($xd_loc_paid_to !== '')
	{
		$invoiceWhere .= ' and ngay_thanh_toan <= ?';
		$studentWhere .= ' and ngay_thanh_toan <= ?';
		$invoiceParams[] = $xd_loc_paid_to; $studentParams[] = $xd_loc_paid_to;
	}

	// Hiển thị một dòng cho mỗi giáo viên/ngày; hủy duyệt từ dòng này chỉ ảnh hưởng đúng ngày đó.
	$rows = $d->rawQuery(
		"select paid.gv_key, max(paid.gv_hoten) as gv_hoten, sum(paid.tong_tien) as tong_tien,
			sum(paid.so_hd) as so_hd, sum(paid.so_hv) as so_hv, sum(paid.so_bangke) as so_bangke,
			paid.ngay_thanh_toan
		 from (
			select gv_key, max(gv_hoten) as gv_hoten, sum(tong_tien) as tong_tien, count(*) as so_hd, 0 as so_hv,
				sum(case when id_bangke > 0 then 1 else 0 end) as so_bangke, ngay_thanh_toan
			from #_xd_hoadon where $invoiceWhere group by gv_key, ngay_thanh_toan
			union all
			select gv_key, max(gv_hoten) as gv_hoten, 0 as tong_tien, 0 as so_hd, count(*) as so_hv,
				sum(case when id_bangke > 0 then 1 else 0 end) as so_bangke, ngay_thanh_toan
			from #_xd_hocvien where $studentWhere group by gv_key, ngay_thanh_toan
		 ) paid
		 group by paid.gv_key, paid.ngay_thanh_toan
		 order by paid.ngay_thanh_toan desc, gv_hoten asc",
		array_merge($invoiceParams, $studentParams)
	);

	$xd_loc_dathanhtoan_data = $rows;
}
