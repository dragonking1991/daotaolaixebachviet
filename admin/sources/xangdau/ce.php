<?php
if(!defined('SOURCES')) die("Error");

function xd_ce_string_length($value)
{
	return function_exists('mb_strlen') ? mb_strlen((string)$value, 'UTF-8') : strlen((string)$value);
}

function xd_get_ce_data()
{
	global $d, $func, $xd_ce_courses, $xd_ce_students, $xd_ce_invoices, $xd_ce_filter_khoa, $xd_ce_filter_keyword;

	dt_ensure_tables();
	$allCourses = $d->rawQuery("select k.id, k.ma_khoa, k.ten_khoa, k.hang,
		(select count(*) from #_xd_ce_hocvien h where h.id_khoa=k.id) as so_hoc_vien
		from #_dt_khoa k where k.hienthi >= 0 order by k.ma_khoa asc");
	if($allCourses === false) $func->transfer("Không tải được danh sách khóa đào tạo CE.", "index.php?com=xangdau&act=hocvien", false);
	$xd_ce_courses = array();
	foreach(is_array($allCourses) ? $allCourses : array() as $course)
		if(dt_norm_hang($course['hang']) === 'CE') $xd_ce_courses[(int)$course['id']] = $course;

	$requestedCourse = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$xd_ce_filter_khoa = isset($xd_ce_courses[$requestedCourse]) ? $requestedCourse : 0;
	$xd_ce_filter_keyword = isset($_REQUEST['keyword']) ? trim((string)$_REQUEST['keyword']) : '';
	$courseIds = array_keys($xd_ce_courses);
	$xd_ce_students = array();
	$xd_ce_invoices = array();
	if(empty($courseIds)) return;

	$courseMarks = implode(',', array_fill(0, count($courseIds), '?'));
	$studentWhere = "id_khoa in ($courseMarks)";
	$invoiceWhere = "id_khoa in ($courseMarks)";
	$studentParams = $courseIds;
	$invoiceParams = $courseIds;
	if($xd_ce_filter_khoa > 0)
	{
		$studentWhere .= " and id_khoa = ?";
		$invoiceWhere .= " and id_khoa = ?";
		$studentParams[] = $xd_ce_filter_khoa;
		$invoiceParams[] = $xd_ce_filter_khoa;
	}
	if($xd_ce_filter_keyword !== '')
	{
		$studentWhere .= " and (ho_ten like ? or cccd like ? or ma_khoa like ? or gv_hoten like ?)";
		$invoiceWhere .= " and (ma_hoa_don like ? or gv_hoten like ? or ky like ? or ma_khoa like ?)";
		foreach(array(1, 2, 3, 4) as $i) $studentParams[] = '%'.$xd_ce_filter_keyword.'%';
		foreach(array(1, 2, 3, 4) as $i) $invoiceParams[] = '%'.$xd_ce_filter_keyword.'%';
	}
	$xd_ce_students = $d->rawQuery("select * from #_xd_ce_hocvien where $studentWhere order by ma_khoa asc, id_khoa asc, id asc", $studentParams);
	$xd_ce_invoices = $d->rawQuery("select * from #_xd_ce_hoadon where $invoiceWhere order by ma_khoa asc, ngay_hoa_don desc, id desc", $invoiceParams);
	if($xd_ce_students === false || $xd_ce_invoices === false)
		$func->transfer("Không tải được danh sách học viên/hóa đơn CE.", "index.php?com=xangdau&act=ce", false);
}

function xd_ce_save_invoice()
{
	global $d, $func;

	$back = "index.php?com=xangdau&act=ce";
	$idKhoa = isset($_POST['id_khoa']) ? (int)$_POST['id_khoa'] : 0;
	$invoiceNo = isset($_POST['ma_hoa_don']) ? trim((string)$_POST['ma_hoa_don']) : '';
	$invoiceDate = isset($_POST['ngay_hoa_don']) ? trim((string)$_POST['ngay_hoa_don']) : '';
	$amountRaw = isset($_POST['tong_tien']) ? trim((string)$_POST['tong_tien']) : '';
	$teacher = isset($_POST['gv_hoten']) ? trim((string)$_POST['gv_hoten']) : '';
	$period = isset($_POST['ky']) ? trim((string)$_POST['ky']) : '';

	$dateParts = preg_match('/^\d{4}-\d{2}-\d{2}$/', $invoiceDate) ? explode('-', $invoiceDate) : array();
	if(count($dateParts) !== 3 || !checkdate((int)$dateParts[1], (int)$dateParts[2], (int)$dateParts[0]))
		$func->transfer("Ngày hóa đơn không hợp lệ.", $back, false);
	if($idKhoa <= 0 || $invoiceNo === '' || $teacher === '' || $period === '')
		$func->transfer("Vui lòng nhập đủ khóa CE, số hóa đơn, giáo viên và kỳ.", $back, false);
	if(xd_ce_string_length($invoiceNo) > 191 || xd_ce_string_length($teacher) > 255 || xd_ce_string_length($period) > 100)
		$func->transfer("Số hóa đơn, tên giáo viên hoặc kỳ vượt quá độ dài cho phép.", $back, false);
	if(!preg_match('/^[0-9]{1,16}$/', $amountRaw) || (float)$amountRaw <= 0)
		$func->transfer("Số tiền hóa đơn phải lớn hơn 0 và là số nguyên đồng (tối đa 16 chữ số).", $back, false);
	$amount = (float)$amountRaw;

	$course = $d->rawQueryOne("select id, ma_khoa, hang from #_dt_khoa where id = ? limit 0,1", array($idKhoa));
	if(!$course || dt_norm_hang($course['hang']) !== 'CE')
		$func->transfer("Chỉ được lưu hóa đơn thuộc khóa CE hợp lệ.", $back, false);
	if(xd_ce_string_length($course['ma_khoa']) > 100)
		$func->transfer("Mã khóa CE vượt quá độ dài cho phép lưu hóa đơn.", $back, false);

	$exists = $d->rawQueryOne("select id from #_xd_ce_hoadon where id_khoa = ? and ma_hoa_don = ? and ngay_hoa_don = ? limit 0,1", array($idKhoa, $invoiceNo, $invoiceDate));
	if($exists && !empty($exists['id']))
		$func->transfer("Số hóa đơn này đã tồn tại trong khóa CE đã chọn.", $back, false);

	$teacherKey = xd_gv_key($teacher);
	if(strlen($teacherKey) > 191)
		$func->transfer("Tên giáo viên quá dài sau khi chuẩn hóa để lưu.", $back, false);
	$ok = $d->rawQuery(
		"insert into #_xd_ce_hoadon (id_khoa, ma_khoa, ma_hoa_don, ngay_hoa_don, tong_tien, gv_hoten, gv_key, ky, ngaytao, user_tao)
		 values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
		array($idKhoa, $course['ma_khoa'], $invoiceNo, $invoiceDate, (float)$amount, $teacher, $teacherKey, $period, time(), xd_username())
	);
	if($ok === false || $d->getLastErrorCode() !== '00000') $func->transfer("Không lưu được hóa đơn CE. Vui lòng kiểm tra dữ liệu và thử lại.", $back, false);
	$func->transfer("Đã lưu hóa đơn CE cho khóa ".htmlspecialchars($course['ma_khoa']).".", $back, true);
}

function xd_ce_upload_invoices()
{
	global $d, $func;
	$back = "index.php?com=xangdau&act=ce";
	if(!isset($_FILES['file-excel']) || (int)$_FILES['file-excel']['error'] !== UPLOAD_ERR_OK)
		$func->transfer("Vui lòng chọn file Excel hóa đơn CE hợp lệ.", $back, false);

	$file = $_FILES['file-excel'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	if(!in_array($ext, array('xls', 'xlsx', 'xlsb'), true))
		$func->transfer("File import hóa đơn CE chỉ hỗ trợ .xls, .xlsx hoặc .xlsb.", $back, false);

	list($workbook, $sheet, $highestRow, $highestCol) = xd_open_upload_sheet($file, $ext, $back);
	$aliases = array(
		'course' => array('makhoace', 'makhoahoc', 'makhoa', 'khoace'),
		'number' => array('sohoadon', 'mahoadon', 'sohd', 'mahd'),
		'date' => array('ngayhoadon', 'ngayhd', 'ngay'),
		'amount' => array('sotien', 'tongtien', 'sotienhoadon', 'thanhtien'),
		'teacher' => array('giaovien', 'tengiaovien', 'tengv', 'gv'),
		'period' => array('ky', 'kytonghop', 'kyhoadon'),
	);
	$contains = array(
		'course' => array('has' => array('khoa')),
		'number' => array('has' => array('hoadon')),
		'amount' => array('has' => array('tien')),
		'teacher' => array('has' => array('gv')),
	);
	list($headerRow, $map, $headerScore) = xd_detect_header($sheet, $highestRow, $highestCol, $aliases, $contains);
	if($headerScore <= 0) $func->transfer("Không nhận diện được hàng tiêu đề. Tải mẫu Excel hóa đơn CE và dùng đúng các cột.", $back, false);
	foreach(array('course', 'number', 'date', 'amount', 'teacher', 'period') as $required)
		if(!isset($map[$required])) $func->transfer("File thiếu cột bắt buộc cho hóa đơn CE: ".$required.".", $back, false);

	$courses = $d->rawQuery("select id, ma_khoa, hang from #_dt_khoa where hienthi >= 0");
	if($courses === false) $func->transfer("Không đọc được danh sách khóa CE.", $back, false);
	$ceByCode = array();
	foreach($courses as $course)
		if(dt_norm_hang($course['hang'] ?? '') === 'CE') $ceByCode[strtolower(trim((string)$course['ma_khoa']))] = $course;

	$rows = array();
	$errors = array();
	$seen = array();
	$emptyStreak = 0;
	for($row = $headerRow + 1; $row <= $highestRow; $row++)
	{
		$courseCode = trim(xd_cell($sheet, $map['course'], $row));
		$invoiceNo = trim(xd_cell($sheet, $map['number'], $row));
		$teacher = trim(xd_cell($sheet, $map['teacher'], $row));
		$period = trim(xd_cell($sheet, $map['period'], $row));
		if($courseCode === '' && $invoiceNo === '' && $teacher === '')
		{
			continue;
		}
		$emptyStreak = 0;

		$course = isset($ceByCode[strtolower($courseCode)]) ? $ceByCode[strtolower($courseCode)] : null;
		$date = xd_date_from_cell($sheet, $map['date'], $row);
		$dateParts = $date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? explode('-', $date) : array();
		$amount = xd_money_from_cell($sheet, $map['amount'], $row);
		$errorsInRow = array();
		if(!$course) $errorsInRow[] = 'mã khóa không tồn tại hoặc không phải khóa CE';
		if($invoiceNo === '') $errorsInRow[] = 'thiếu số hóa đơn';
		if(count($dateParts) !== 3 || !checkdate((int)($dateParts[1] ?? 0), (int)($dateParts[2] ?? 0), (int)($dateParts[0] ?? 0))) $errorsInRow[] = 'ngày hóa đơn không hợp lệ';
		if($amount <= 0) $errorsInRow[] = 'số tiền phải lớn hơn 0';
		if($teacher === '') $errorsInRow[] = 'thiếu giáo viên';
		if($period === '') $errorsInRow[] = 'thiếu kỳ';
		$teacherKey = $teacher !== '' ? xd_gv_key($teacher) : '';
		if($course && xd_ce_string_length($course['ma_khoa']) > 100) $errorsInRow[] = 'mã khóa vượt quá 100 ký tự';
		if(xd_ce_string_length($invoiceNo) > 191) $errorsInRow[] = 'số hóa đơn vượt quá 191 ký tự';
		if(xd_ce_string_length($teacher) > 255 || strlen($teacherKey) > 191) $errorsInRow[] = 'tên giáo viên vượt quá giới hạn lưu trữ';
		if(xd_ce_string_length($period) > 100) $errorsInRow[] = 'kỳ vượt quá 100 ký tự';
		if($amount > 9999999999999999) $errorsInRow[] = 'số tiền vượt quá giới hạn lưu trữ';
		if(!empty($errorsInRow))
		{
			$errors[] = "Dòng $row: ".implode(', ', $errorsInRow).'.';
			continue;
		}

		$key = (int)$course['id'].'|'.$invoiceNo.'|'.$date;
		if(isset($seen[$key]))
		{
			$errors[] = "Dòng $row: hóa đơn bị lặp trong file cho khóa $courseCode.";
			continue;
		}
		$seen[$key] = 1;
		$exists = $d->rawQueryOne("select id from #_xd_ce_hoadon where id_khoa = ? and ma_hoa_don = ? and ngay_hoa_don = ? limit 0,1", array((int)$course['id'], $invoiceNo, $date));
		if($exists && !empty($exists['id']))
		{
			$errors[] = "Dòng $row: hóa đơn đã tồn tại trong khóa $courseCode.";
			continue;
		}
		$rows[] = array((int)$course['id'], $course['ma_khoa'], $invoiceNo, $date, $amount, $teacher, $teacherKey, $period);
	}
	if(empty($rows) && empty($errors)) $errors[] = 'File không có dòng hóa đơn CE hợp lệ.';
	if(!empty($errors) && function_exists('xd_import_record_failure')) xd_import_record_failure('ce', implode('<br>', $errors), false);
	if(!empty($errors))
		$func->transfer("Không import hóa đơn CE; file được kiểm tra toàn bộ trước khi lưu:<br>".implode('<br>', array_slice($errors, 0, 30)), $back, false);

	$username = xd_username();
	$d->startTransaction();
	foreach($rows as $r)
	{
		$ok = $d->rawQuery(
			"insert into #_xd_ce_hoadon (id_khoa, ma_khoa, ma_hoa_don, ngay_hoa_don, tong_tien, gv_hoten, gv_key, ky, ngaytao, user_tao)
			 values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
			array($r[0], $r[1], $r[2], $r[3], $r[4], $r[5], $r[6], $r[7], time(), $username)
		);
		if($ok === false || $d->getLastErrorCode() !== '00000')
		{
			$d->rollback();
			$func->transfer("Import hóa đơn CE thất bại khi ghi dữ liệu; đã hoàn tác toàn bộ file.", $back, false);
		}
	}
	$d->commit();
	if(function_exists('xd_import_history_done')) xd_import_history_done('ce', array('filename' => (string)$file['name'], 'so_them' => count($rows), 'link' => $back));
	$func->transfer("Đã import ".count($rows)." hóa đơn CE theo khóa.", $back, true);
}

function xd_ce_delete_invoice()
{
	global $d, $func;
	$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
	if($id <= 0) $func->transfer("Không xác định được hóa đơn CE.", "index.php?com=xangdau&act=ce", false);
	$ok = $d->rawQuery("delete from #_xd_ce_hoadon where id = ?", array($id));
	if($ok === false || $d->getLastErrorCode() !== '00000') $func->transfer("Không xóa được hóa đơn CE.", "index.php?com=xangdau&act=ce", false);
	$func->transfer("Đã xóa hóa đơn CE.", "index.php?com=xangdau&act=ce", true);
}

function xd_ce_invoice_template()
{
	require_once LIBRARIES.'PHPExcel.php';
	$workbook = new PHPExcel();
	$sheet = $workbook->getActiveSheet();
	$sheet->setTitle('Hoa don CE');
	$headers = array('Mã khóa CE', 'Số hóa đơn', 'Ngày hóa đơn', 'Số tiền', 'Giáo viên', 'Kỳ');
	$widths = array(24, 20, 18, 20, 28, 16);
	foreach($headers as $column => $header)
	{
		$cell = PHPExcel_Cell::stringFromColumnIndex($column).'1';
		$sheet->setCellValue($cell, $header);
		$sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($column))->setWidth($widths[$column]);
	}
	$sheet->getStyle('A1:F1')->getFont()->setBold(true);
	$sheet->freezePane('A2');
	while(ob_get_level() > 0) ob_end_clean();
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment; filename="mau_import_hoa_don_CE.xlsx"');
	header('Cache-Control: max-age=0');
	PHPExcel_IOFactory::createWriter($workbook, 'Excel2007')->save('php://output');
	exit;
}
