<?php
if(!defined('SOURCES')) die("Error");

/* ============================ Lọc, kiểm tra & duyệt thanh toán ============================ */

function xd_loc_params_url($gvKey = '', $act = 'loc')
{
	$act = preg_replace('/[^a-zA-Z0-9]/', '', (string)$act);
	if($act === '') $act = 'loc';
	$url = "index.php?com=xangdau&act=".$act;
	foreach(array('ky', 'from_date', 'to_date', 'gv_keyword', 'ma_hoa_don', 'sort') as $key)
		if(isset($_REQUEST[$key]) && is_scalar($_REQUEST[$key]) && $_REQUEST[$key] !== '') $url .= '&'.$key.'='.urlencode((string)$_REQUEST[$key]);
	if($gvKey !== '') $url .= '&gv_key='.urlencode($gvKey);
	return $url;
}

function xd_get_giao_vien_detail()
{
	global $d, $xd_detail_gv, $xd_detail_hoadons, $xd_detail_hocviens, $xd_detail_hoadons_da_thanh_toan, $xd_detail_hocviens_da_thanh_toan, $xd_detail_course_names, $xd_detail_config, $xd_detail_da_kiem_tra, $xd_detail_da_thanh_toan;
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
	$courseCodes = array();
	foreach(array_merge(is_array($xd_detail_hocviens_da_thanh_toan)?$xd_detail_hocviens_da_thanh_toan:array(),is_array($xd_detail_hocviens)?$xd_detail_hocviens:array()) as $student)
		if(trim((string)($student['khoa']??''))!=='')$courseCodes[]=trim((string)$student['khoa']);
	$xd_detail_course_names = xd_course_name_map($courseCodes);
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

function xd_loc_bulk_csrf_token()
{
	if(session_status() !== PHP_SESSION_ACTIVE) @session_start();
	if(empty($_SESSION['xd_loc_bulk_csrf'])) $_SESSION['xd_loc_bulk_csrf'] = bin2hex(random_bytes(24));
	return (string)$_SESSION['xd_loc_bulk_csrf'];
}

function xd_loc_bulk_check($markChecked, $returnUrl = '')
{
	global $d, $func;
	$back = $returnUrl !== '' ? $returnUrl : ($markChecked ? 'index.php?com=xangdau&act=locKiemTra' : 'index.php?com=xangdau&act=locDuyet');
	if(strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST')
		$func->transfer('Thao tác không hợp lệ.', $back, false);
	$token = (string)($_SESSION['xd_loc_bulk_csrf'] ?? '');
	if($token === '' || !hash_equals($token, (string)($_POST['csrf'] ?? '')))
		$func->transfer('Phiên thao tác không hợp lệ; vui lòng tải lại trang.', $back, false);
	$gvKeys = array_values(array_unique(array_filter(array_map(function($value) {
		return trim((string)$value);
	}, (array)($_POST['gv_keys'] ?? array())), function($value) { return $value !== ''; })));
	if(!$gvKeys) $func->transfer('Danh sách không có giáo viên để cập nhật.', $back, false);
	$placeholders = implode(',', array_fill(0, count($gvKeys), '?'));
	$invoiceSql = $markChecked
		? "update #_xd_hoadon set ke_toan_kiem_tra = 1, ngay_kiem_tra = ? where gv_key in ($placeholders) and da_quyettoan = 0"
		: "update #_xd_hoadon set ke_toan_kiem_tra = 0, ngay_kiem_tra = null where gv_key in ($placeholders) and da_quyettoan = 0";
	$invoiceParams = $markChecked ? array_merge(array(date('Y-m-d')), $gvKeys) : $gvKeys;
	$d->startTransaction();
	$invoiceResult = $d->rawQuery($invoiceSql, $invoiceParams);
	if($invoiceResult === false || $d->getLastErrorCode() !== '00000')
	{
		$d->rollback();
		$func->transfer('Không cập nhật được trạng thái kiểm tra hóa đơn.', $back, false);
	}
	$studentResult = $d->rawQuery(
		"update #_xd_hocvien set ke_toan_kiem_tra = ? where gv_key in ($placeholders) and ngay_thanh_toan is null",
		array_merge(array($markChecked ? 1 : 0), $gvKeys)
	);
	if($studentResult === false || $d->getLastErrorCode() !== '00000')
	{
		$d->rollback();
		$func->transfer('Không cập nhật được trạng thái kiểm tra học viên; đã hoàn tác thao tác.', $back, false);
	}
	if($d->commit() === false)
	{
		$d->rollback();
		$func->transfer('Không thể hoàn tất cập nhật trạng thái kiểm tra.', $back, false);
	}
	$func->transfer(($markChecked ? 'Đã đánh dấu kiểm tra ' : 'Đã bỏ kiểm tra ').count($gvKeys).' giáo viên.', $back, true);
}

function xd_loc_bulk_action()
{
	global $func, $xd_loc_summary, $xd_loc_selected, $xd_loc_ky, $xd_loc_from, $xd_loc_to;
	if(strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST')
		$func->transfer('Thao tác không hợp lệ.', 'index.php?com=xangdau&act=loc', false);
	$_REQUEST = array_merge((array)$_REQUEST, (array)$_POST);
	$back = xd_safe_ret($_POST['ret'] ?? '', xd_loc_params_url('', 'loc'));
	$token = (string)($_SESSION['xd_loc_bulk_csrf'] ?? '');
	if($token === '' || !hash_equals($token, (string)($_POST['csrf'] ?? '')))
		$func->transfer('Phiên thao tác không hợp lệ; vui lòng tải lại trang.', $back, false);
	$operation = trim((string)($_POST['operation'] ?? ''));
	if(in_array($operation, array('check','uncheck'), true) && !xd_can_kiem_tra())
		$func->transfer('Bạn không có quyền kiểm tra hóa đơn.', $back, false);
	if($operation === 'approve' && !xd_can_duyet())
		$func->transfer('Bạn không có quyền duyệt thanh toán.', $back, false);
	if(!in_array($operation, array('check','uncheck','approve'), true))
		$func->transfer('Chọn thao tác cần thực hiện.', $back, false);

	xd_loc_preview();
	$requested = array_values(array_unique(array_filter(array_map(function($value) {
		return trim((string)$value);
	}, (array)($_POST['gv_keys'] ?? array())), function($value) { return $value !== ''; })));
	$available = array();
	foreach($xd_loc_summary as $teacher) $available[(string)$teacher['gv_key']] = true;
	$gvKeys = array_values(array_filter($requested, function($gvKey) use ($available) { return isset($available[$gvKey]); }));
	if(!$gvKeys) $func->transfer('Chọn ít nhất một giáo viên trong danh sách hiện tại.', $back, false);
	$_POST['gv_keys'] = $gvKeys;
	if($operation === 'check' || $operation === 'uncheck')
		xd_loc_bulk_check($operation === 'check', $back);

	$studentsByTeacher = array();
	$selectedKeys = array_fill_keys($gvKeys, true);
	foreach($xd_loc_selected as $student)
		if(isset($selectedKeys[(string)$student['gv_key']])) $studentsByTeacher[(string)$student['gv_key']][] = $student;
	$approved = 0;
	$failed = array();
	foreach($gvKeys as $gvKey)
	{
		$teacherName = $gvKey;
		foreach($xd_loc_summary as $teacher) if((string)$teacher['gv_key'] === $gvKey) { $teacherName = (string)($teacher['gv_hoten'] !== '' ? $teacher['gv_hoten'] : $gvKey); break; }
		$unCheckedInvoice = $GLOBALS['d']->rawQueryOne('select count(*) as total from #_xd_hoadon where gv_key=? and da_quyettoan=0 and ke_toan_kiem_tra=0', array($gvKey));
		$unCheckedStudent = $GLOBALS['d']->rawQueryOne('select count(*) as total from #_xd_hocvien where gv_key=? and ngay_thanh_toan is null and ke_toan_kiem_tra=0', array($gvKey));
		if((int)($unCheckedInvoice['total'] ?? 0) > 0 || (int)($unCheckedStudent['total'] ?? 0) > 0) { $failed[] = $teacherName.': chưa được kế toán kiểm tra, hãy "Đánh dấu đã kiểm tra" trước'; continue; }
		if(empty($studentsByTeacher[$gvKey])) { $failed[] = $teacherName.': không còn học viên đủ điều kiện thanh toán trong bộ lọc hiện tại'; continue; }
		if(xd_loc_approve_teacher($gvKey, $studentsByTeacher[$gvKey], $xd_loc_ky, $xd_loc_from, $xd_loc_to)) $approved++;
		else $failed[] = $teacherName.': lỗi ghi dữ liệu, đã hoàn tác';
	}
	$message = 'Đã duyệt '.$approved.'/'.count($gvKeys).' giáo viên.';
	if($failed) $message .= '<br>Không duyệt được:<br>'.implode('<br>', array_map('htmlspecialchars', array_slice($failed, 0, 15)));
	$func->transfer($message, $back, !$failed);
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
	$gvKeyword = trim((string)($_REQUEST['gv_keyword'] ?? ''));
	$invoiceNumber = trim((string)($_REQUEST['ma_hoa_don'] ?? ''));
	$where = "gv_key <> '' and da_quyettoan = 0 and hop_le = 1 and ke_toan_kiem_tra = 1";
	$params = array();
	if($ky !== '') { $where .= ' and ky = ?'; $params[] = $ky; }
	if($fromDate !== '') { $where .= ' and ngay_hoa_don >= ?'; $params[] = $fromDate; }
	if($toDate !== '') { $where .= ' and ngay_hoa_don <= ?'; $params[] = $toDate; }
	if($gvKeyword !== '') { $where .= ' and (gv_hoten like ? or gv_key like ?)'; $params[] = '%'.$gvKeyword.'%'; $params[] = '%'.$gvKeyword.'%'; }
	if($invoiceNumber !== '') { $where .= ' and ma_hoa_don like ?'; $params[] = '%'.$invoiceNumber.'%'; }
	$rows = $d->rawQuery("select distinct gv_key from #_xd_hoadon where $where", $params);
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
function xd_loc_cancel_teacher_payment($gvKey, $paymentDate)
{
	global $d;
	$gvKey = trim((string)$gvKey);
	$dateParts = explode('-', (string)$paymentDate);
	if($gvKey === '') throw new InvalidArgumentException('Không xác định được giáo viên.');
	if(count($dateParts) !== 3 || !checkdate((int)$dateParts[1], (int)$dateParts[2], (int)$dateParts[0]))
		throw new InvalidArgumentException('Ngày thanh toán không hợp lệ.');

	$invoiceRows = $d->rawQuery("select distinct id_bangke from #_xd_hoadon where gv_key = ? and ngay_thanh_toan = ? and da_quyettoan = 1 and id_bangke > 0", array($gvKey, $paymentDate));
	$studentRows = $d->rawQuery("select distinct id_bangke from #_xd_hocvien where gv_key = ? and ngay_thanh_toan = ? and id_bangke > 0", array($gvKey, $paymentDate));
	if($invoiceRows === false || $studentRows === false || $d->getLastErrorCode() !== '00000')
		throw new RuntimeException('Không tải được dữ liệu thanh toán để hủy.');
	$bangkeIds = array();
	foreach(array_merge(is_array($invoiceRows) ? $invoiceRows : array(), is_array($studentRows) ? $studentRows : array()) as $row)
	{
		$id = (int)$row['id_bangke'];
		if($id > 0) $bangkeIds[$id] = $id;
	}
	if(empty($bangkeIds)) throw new InvalidArgumentException('Giáo viên chưa có đợt duyệt để hủy trong ngày '.date('d/m/Y', strtotime($paymentDate)).'.');
	$ids = array_values($bangkeIds);
	$placeholders = implode(',', array_fill(0, count($ids), '?'));
	$d->startTransaction();
	try
	{
		$hv = $d->rawQuery("update #_xd_hocvien set ngay_thanh_toan = null, id_bangke = 0, quan_ly_duyet = 0, so_tien_thanh_toan = 0, dinh_muc = 0 where gv_key = ? and ngay_thanh_toan = ? and id_bangke in ($placeholders)", array_merge(array($gvKey, $paymentDate), $ids));
		if($hv === false || $d->getLastErrorCode() !== '00000') throw new RuntimeException('Không hủy duyệt được học viên.');
		$hd = $d->rawQuery("update #_xd_hoadon set da_quyettoan = 0, ngay_thanh_toan = null, quan_ly_duyet = 0, id_bangke = 0 where gv_key = ? and ngay_thanh_toan = ? and da_quyettoan = 1 and id_bangke in ($placeholders)", array_merge(array($gvKey, $paymentDate), $ids));
		if($hd === false || $d->getLastErrorCode() !== '00000') throw new RuntimeException('Không hủy duyệt được hóa đơn.');
		foreach($ids as $id)
		{
			$remainingStudents = $d->rawQueryOne("select count(*) as total, coalesce(sum(so_tien_thanh_toan), 0) as tong_tien from #_xd_hocvien where id_bangke = ? and ngay_thanh_toan is not null", array($id));
			$remainingInvoices = $d->rawQueryOne("select count(*) as total from #_xd_hoadon where id_bangke = ? and da_quyettoan = 1", array($id));
			if(!is_array($remainingStudents) || !is_array($remainingInvoices) || $d->getLastErrorCode() !== '00000') throw new RuntimeException('Không thể xác minh dữ liệu còn lại của bảng kê.');
			$studentCount = (int)($remainingStudents['total'] ?? 0);
			$invoiceCount = (int)($remainingInvoices['total'] ?? 0);
			$bk = ($studentCount === 0 && $invoiceCount === 0)
				? $d->rawQuery("delete from #_xd_bangke where id = ?", array($id))
				: $d->rawQuery("update #_xd_bangke set tong_hocvien = ?, tong_tien = ? where id = ?", array($studentCount, (float)($remainingStudents['tong_tien'] ?? 0), $id));
			if($bk === false || $d->getLastErrorCode() !== '00000') throw new RuntimeException('Không cập nhật được bảng kê sau khi hủy duyệt.');
		}
		if($d->commit() === false) throw new RuntimeException('Không thể hoàn tất hủy duyệt.');
	}
	catch(Exception $error)
	{
		$d->rollback();
		throw $error;
	}
	return 'Đã hủy duyệt ngày '.date('d/m/Y', strtotime($paymentDate)).' cho giáo viên '.$gvKey.'; các ngày thanh toán khác được giữ nguyên.';
}

function xd_huy_duyet_giao_vien()
{
	global $func;
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
	$paymentDate = isset($_POST['payment_date']) ? trim((string)$_POST['payment_date']) : '';
	try { $message = xd_loc_cancel_teacher_payment($gvKey, $paymentDate); }
	catch(Exception $error) { $func->transfer($error->getMessage(), $redirect, false); }
	$func->transfer($message, $redirect, true);
}

function xd_loc_paid_return_url($input)
{
	$url = 'index.php?com=xangdau&act=locDaThanhToan';
	$params = array();
	foreach(array('keyword','paid_from','paid_to') as $key)
	{
		$value = trim((string)($input[$key] ?? ''));
		if($value !== '' && ($key === 'keyword' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value))) $params[$key] = $value;
	}
	return $params ? $url.'&'.http_build_query($params) : $url;
}

function xd_huy_duyet_giao_vien_chon()
{
	global $func;
	$redirect = xd_safe_ret($_POST['ret'] ?? '', xd_loc_paid_return_url($_POST));
	$rows = (array)($_POST['rows'] ?? array());
	$selected = array();
	foreach($rows as $row)
	{
		if(empty($row['selected'])) continue;
		$gvKey = trim((string)($row['gv_key'] ?? ''));
		$date = trim((string)($row['payment_date'] ?? ''));
		if($gvKey === '' || $date === '') continue;
		$selected[$gvKey.'|'.$date] = array('gv_key'=>$gvKey,'payment_date'=>$date);
	}
	if(!$selected) $func->transfer('Chọn ít nhất một đợt thanh toán cần bỏ duyệt.', $redirect, false);
	$done = 0; $failed = array();
	foreach($selected as $row)
	{
		try { xd_loc_cancel_teacher_payment($row['gv_key'], $row['payment_date']); $done++; }
		catch(Exception $error) { $failed[] = $row['gv_key'].' ('.date('d/m/Y', strtotime($row['payment_date'])).'): '.$error->getMessage(); }
	}
	$message = 'Đã hủy duyệt '.$done.'/'.count($selected).' đợt thanh toán.';
	if($failed) $message .= '<br>'.implode('<br>', array_slice($failed, 0, 10));
	$func->transfer($message, $redirect, empty($failed));
}

function xd_loc_preview()
{
	global $d, $xd_loc_selected, $xd_loc_summary, $xd_loc_config, $xd_loc_ky, $xd_loc_from, $xd_loc_to, $xd_loc_ky_options, $xd_loc_gv_keyword, $xd_loc_ma_hoa_don, $xd_loc_sort;

	$xd_loc_ky = isset($_REQUEST['ky']) ? trim((string)$_REQUEST['ky']) : '';
	$xd_loc_from = (isset($_REQUEST['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['from_date'])) ? $_REQUEST['from_date'] : '';
	$xd_loc_to = (isset($_REQUEST['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['to_date'])) ? $_REQUEST['to_date'] : '';
	$xd_loc_gv_keyword = trim((string)($_REQUEST['gv_keyword'] ?? ''));
	$xd_loc_ma_hoa_don = trim((string)($_REQUEST['ma_hoa_don'] ?? ''));
	$sortOptions = array('teacher_asc', 'invoice_desc', 'invoice_asc', 'date_desc', 'date_asc');
	$xd_loc_sort = isset($_REQUEST['sort']) && in_array($_REQUEST['sort'], $sortOptions, true) ? $_REQUEST['sort'] : 'teacher_asc';

	$xd_loc_ky_options = $d->rawQuery("select distinct ky from #_xd_hoadon where ky <> '' and da_quyettoan = 0 order by ky asc");

	list($selected, $summary, $config) = xd_run_algorithm($d, $xd_loc_ky, $xd_loc_from, $xd_loc_to);
	$xd_loc_selected = $selected;
	$xd_loc_config = $config;
	$invoiceKeys = array();
	if($xd_loc_ma_hoa_don !== '')
	{
		$where = "gv_key <> '' and da_quyettoan = 0 and hop_le = 1 and ma_hoa_don like ?";
		$params = array('%'.$xd_loc_ma_hoa_don.'%');
		if($xd_loc_ky !== '') { $where .= ' and ky = ?'; $params[] = $xd_loc_ky; }
		if($xd_loc_from !== '') { $where .= ' and ngay_hoa_don >= ?'; $params[] = $xd_loc_from; }
		if($xd_loc_to !== '') { $where .= ' and ngay_hoa_don <= ?'; $params[] = $xd_loc_to; }
		$matches = $d->rawQuery("select distinct gv_key from #_xd_hoadon where $where", $params);
		foreach(is_array($matches) ? $matches : array() as $match) $invoiceKeys[(string)$match['gv_key']] = true;
	}
	$visibleKeys = array();
	$filteredSummary = array();
	foreach($summary as $row)
	{
		$name = (string)($row['gv_hoten'] !== '' ? $row['gv_hoten'] : $row['gv_key']);
		if($xd_loc_gv_keyword !== '' && function_exists('mb_stripos') && mb_stripos($name, $xd_loc_gv_keyword, 0, 'UTF-8') === false && mb_stripos((string)$row['gv_key'], $xd_loc_gv_keyword, 0, 'UTF-8') === false) continue;
		if($xd_loc_gv_keyword !== '' && !function_exists('mb_stripos') && stripos($name.' '.$row['gv_key'], $xd_loc_gv_keyword) === false) continue;
		if($xd_loc_ma_hoa_don !== '' && !isset($invoiceKeys[(string)$row['gv_key']])) continue;
		$visibleKeys[(string)$row['gv_key']] = true;
		$filteredSummary[] = $row;
	}
	$xd_loc_summary = $filteredSummary;
	$xd_loc_selected = array_values(array_filter($selected, function($student) use ($visibleKeys) { return isset($visibleKeys[(string)$student['gv_key']]); }));
	$courseCodes = array_values(array_unique(array_filter(array_map(function($student) {
		return trim((string)($student['khoa'] ?? ''));
	}, $xd_loc_selected), function($code) { return $code !== ''; })));
	$courseNames = xd_course_name_map($courseCodes);
	foreach($xd_loc_selected as &$student) $student['khoa_ten'] = xd_course_display_name($student['khoa'] ?? '', $courseNames);
	unset($student);
	usort($xd_loc_summary, function($a, $b) use ($xd_loc_sort) {
		if($xd_loc_sort === 'invoice_desc') $compare = (int)$b['so_hd'] <=> (int)$a['so_hd'];
		elseif($xd_loc_sort === 'invoice_asc') $compare = (int)$a['so_hd'] <=> (int)$b['so_hd'];
		elseif($xd_loc_sort === 'date_desc') $compare = strcmp((string)$b['ngay_hd_gan_nhat'], (string)$a['ngay_hd_gan_nhat']);
		elseif($xd_loc_sort === 'date_asc') $compare = strcmp((string)$a['ngay_hd_gan_nhat'], (string)$b['ngay_hd_gan_nhat']);
		else $compare = strcmp(xd_gv_sort_key((string)$a['gv_hoten']), xd_gv_sort_key((string)$b['gv_hoten']));
		if($compare !== 0) return $compare;
		return strcmp(xd_gv_key((string)$a['gv_key']), xd_gv_key((string)$b['gv_key']));
	});
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
	
	usort($data, function($a, $b) { return strcmp(xd_gv_sort_key((string)$a['gv_hoten']), xd_gv_sort_key((string)$b['gv_hoten'])); });
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
	
	usort($data, function($a, $b) { return strcmp(xd_gv_sort_key((string)$a['gv_hoten']), xd_gv_sort_key((string)$b['gv_hoten'])); });
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
