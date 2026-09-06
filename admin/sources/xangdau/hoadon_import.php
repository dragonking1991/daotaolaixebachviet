<?php
if(!defined('SOURCES')) die("Error");

/* ============================ Import hóa đơn ============================ */

function xd_upload_hoadon_excel()
{
	global $d, $func;

	@ini_set('memory_limit', '1024M');
	@set_time_limit(300);

	$backUrl = "index.php?com=xangdau&act=uploadHoadon";

	if(!isset($_FILES['file-excel']) || (int)$_FILES['file-excel']['error'] !== UPLOAD_ERR_OK)
		$func->transfer("Vui lòng chọn file Excel hợp lệ", $backUrl, false);

	$file = $_FILES['file-excel'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	if(!in_array($ext, array('xls', 'xlsx', 'xlsb'))) $func->transfer("Chỉ hỗ trợ file .xls, .xlsx hoặc .xlsb", $backUrl, false);

	// Kỳ nhập trên form (bắt buộc, dùng để chống import đè theo kỳ)
	$kyForm = isset($_POST['ky']) ? trim((string)$_POST['ky']) : '';

	// File tổng hợp có thể có sheet tóm tắt (TH) và sheet chi tiết (HĐơn) -> ưu tiên sheet hóa đơn
	list($objPHPExcel, $sheet, $highestRow, $highestColIndex) = xd_open_upload_sheet($file, $ext, $backUrl, array('hdon', 'hoadon'));

	// Cột theo mẫu thực tế: STT | Số hóa đơn | Ngày | Thông tin bán hàng | Chi tiết | Số tiền HĐ | Biển số xe | HĐ từ trang thuế | GV | Note1 | Note2
	$aliasGroups = array(
		'ma'      => array('sohoadon', 'mahoadon', 'masohoadon', 'sohd', 'mahd'),
		'ngay'    => array('ngay', 'ngayhoadon', 'ngayhd', 'ngaylap', 'date'),
		'ttbanhang' => array('thongtinbanhang', 'thongtinbanha', 'ttbanhang'),
		'chitiet'  => array('chitiet', 'chit'),
		'tien'    => array('sotienhd', 'sotienhoadon', 'tongtien', 'tienhoadon', 'thanhtien', 'sotien'),
		'bienso'  => array('bienso', 'biensoxe'),
		'gv'      => array('gv', 'giaovien', 'tengiaovien', 'tengv', 'phanxe'),
	);
	$containsRules = array(
		'ma'        => array('has' => array('hoadon')),
		'ttbanhang' => array('has' => array('banhang')),
		'tien'      => array('has' => array('tien')),
		'gv'        => array('has' => array('gv')),
		'bienso'    => array('has' => array('bienso')),
	);

	list($headerRow, $map, $headerScore) = xd_detect_header($sheet, $highestRow, $highestColIndex, $aliasGroups, $containsRules);

	// Nếu không nhận diện được tiêu đề nào -> giả định đúng thứ tự cột mẫu (có cột STT ở [0])
	if($headerScore <= 0)
	{
		$map = array('ma' => 1, 'ngay' => 2, 'ttbanhang' => 3, 'chitiet' => 4, 'tien' => 5, 'bienso' => 6, 'gv' => 8);
		$headerRow = 1;
	}

	if(!isset($map['ma']))  $func->transfer("Không xác định được cột 'Số hóa đơn' trong file. Vui lòng kiểm tra tiêu đề cột.", $backUrl, false);
	if(!isset($map['tien'])) $func->transfer("Không xác định được cột 'Số tiền HĐ' trong file. Vui lòng kiểm tra tiêu đề cột.", $backUrl, false);
	if(!isset($map['gv']))  $func->transfer("Không xác định được cột 'GV' (tên giáo viên) trong file. Vui lòng kiểm tra tiêu đề cột.", $backUrl, false);

	// Thu thập các dòng hợp lệ
	$rows = array();
	$emptyStreak = 0;
	for($row = $headerRow + 1; $row <= $highestRow; $row++)
	{
		$ma = xd_cell($sheet, $map['ma'], $row);
		$gvten = xd_cell($sheet, $map['gv'], $row);
		$bienso = isset($map['bienso']) ? xd_cell($sheet, $map['bienso'], $row) : '';
		$ttbanhang = isset($map['ttbanhang']) ? xd_cell($sheet, $map['ttbanhang'], $row) : '';
		$chitiet = isset($map['chitiet']) ? xd_cell($sheet, $map['chitiet'], $row) : '';

		if($ma === '' && $gvten === '')
		{
			if(++$emptyStreak > 50) break;
			continue; // dòng trống
		}
		$emptyStreak = 0;
		if($ma === '')
		{
			// bỏ qua dòng không có số hóa đơn (vd dòng tổng)
			continue;
		}

		$ngay = xd_date_from_cell($sheet, $map['ngay'], $row);
		$tien = xd_money_from_cell($sheet, $map['tien'], $row);
		$gvkey = xd_gv_key($gvten);

		$rows[] = array(
			'row' => $row, 'ma' => $ma, 'ngay' => $ngay, 'tien' => $tien,
			'gvten' => $gvten, 'gvkey' => $gvkey, 'bienso' => $bienso,
			'ttbanhang' => $ttbanhang, 'chitiet' => $chitiet
		);
	}

	if(empty($rows)) $func->transfer("File không có dòng hóa đơn hợp lệ nào.", $backUrl, false);

	// Chặn import đè kỳ đã tồn tại dữ liệu (nếu có nhập kỳ)
	if($kyForm !== '')
	{
		$existKy = $d->rawQueryOne("select count(*) as num from #_xd_hoadon where ky = ?", array($kyForm));
		if($existKy && (int)$existKy['num'] > 0)
			$func->transfer("Kỳ <strong>".htmlspecialchars($kyForm)."</strong> đã được import trước đó. Không thể import đè để tránh trùng dữ liệu.", $backUrl, false);
	}

	$username = xd_username();
	$inserted = 0;
	$skippedDup = 0;
	$skippedLocked = 0;
	$errors = array();
	$seenKeys = array();

	$d->startTransaction();
	foreach($rows as $r)
	{
		// Số HĐ có thể trùng giữa các giáo viên khác nhau -> khóa trùng gồm cả GV (Mã HĐ + Ngày HĐ + GV)
		$key = $r['ma'].'|'.($r['ngay'] === null ? '' : $r['ngay']).'|'.$r['gvkey'];
		if(isset($seenKeys[$key])) { $skippedDup++; continue; }
		$seenKeys[$key] = 1;

		// Kiểm tra trùng (ma_hoa_don + ngay_hoa_don + gv_key)
		$exists = $d->rawQueryOne(
			"select id, da_quyettoan from #_xd_hoadon where ma_hoa_don = ? and (ngay_hoa_don <=> ?) and gv_key = ? limit 0,1",
			array($r['ma'], $r['ngay'], $r['gvkey'])
		);
		if($exists && isset($exists['id']))
		{
			if((int)$exists['da_quyettoan'] === 1) $skippedLocked++;
			else $skippedDup++;
			continue;
		}

		$ok = $d->rawQuery(
			"insert into #_xd_hoadon (gv_cccd, gv_hoten, gv_key, ma_hoa_don, thong_tin_ban_hang, chi_tiet, ngay_hoa_don, tong_tien, bien_so, ky, da_quyettoan, id_bangke, ngaytao, user_tao) values ('', ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?, ?)",
			array($r['gvten'], $r['gvkey'], $r['ma'], $r['ttbanhang'], $r['chitiet'], $r['ngay'], $r['tien'], $r['bienso'], $kyForm, time(), $username)
		);
		if($ok === false)
		{
			$err = $d->getLastError();
			$errors[] = "Dòng ".$r['row'].": lỗi lưu dữ liệu".(is_array($err) && isset($err[2]) ? ' ('.$err[2].')' : '');
		}
		else $inserted++;
	}

	if(!empty($errors))
	{
		$d->rollback();
		$func->transfer("Import thất bại:<br>".implode("<br>", array_slice($errors, 0, 10)), $backUrl, false);
	}

	$d->commit();

	$msg = "Import thành công $inserted hóa đơn.";
	if($skippedDup > 0) $msg .= "<br>Bỏ qua $skippedDup hóa đơn trùng (Mã HĐ + Ngày HĐ).";
	if($skippedLocked > 0) $msg .= "<br>Bỏ qua $skippedLocked hóa đơn đã quyết toán (bị khóa).";
	$func->transfer($msg, "index.php?com=xangdau&act=hoadon");
}
