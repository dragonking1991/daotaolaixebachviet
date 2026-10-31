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

	// Cột theo mẫu thực tế: STT | Số hóa đơn | Ngày | Thông tin bán hàng | Chi tiết | Số tiền HĐ | Biển số xe | HĐ từ trang thuế | GV | Hợp lệ | Note1
	$aliasGroups = array(
		'ma'      => array('sohoadon', 'mahoadon', 'masohoadon', 'sohd', 'mahd'),
		'ngay'    => array('ngay', 'ngayhoadon', 'ngayhd', 'ngaylap', 'date'),
		'ttbanhang' => array('thongtinbanhang', 'thongtinbanha', 'ttbanhang'),
		'chitiet'  => array('chitiet', 'chit'),
		'tien'    => array('sotienhd', 'sotienhoadon', 'tongtien', 'tienhoadon', 'thanhtien', 'sotien'),
		'bienso'  => array('bienso', 'biensoxe'),
		'gv'      => array('gv', 'giaovien', 'tengiaovien', 'tengv', 'phanxe'),
		'hople'   => array('hople'),
		'note1'   => array('note1', 'ghichu1'),
	);
	$containsRules = array(
		'ma'        => array('has' => array('hoadon')),
		'ttbanhang' => array('has' => array('banhang')),
		'tien'      => array('has' => array('tien')),
		'gv'        => array('has' => array('gv')),
		'bienso'    => array('has' => array('bienso')),
		'hople'     => array('has' => array('hople')),
	);

	list($headerRow, $map, $headerScore) = xd_detect_header($sheet, $highestRow, $highestColIndex, $aliasGroups, $containsRules);

	// Nếu không nhận diện được tiêu đề nào -> giả định đúng thứ tự cột mẫu (có cột STT ở [0])
	if($headerScore <= 0)
	{
		$map = array('ma' => 1, 'ngay' => 2, 'ttbanhang' => 3, 'chitiet' => 4, 'tien' => 5, 'bienso' => 6, 'gv' => 8, 'hople' => 9, 'note1' => 10);
		$headerRow = 1;
	}

	if(!isset($map['ma']))  $func->transfer("Không xác định được cột 'Số hóa đơn' trong file. Vui lòng kiểm tra tiêu đề cột.", $backUrl, false);
	if(!isset($map['tien'])) $func->transfer("Không xác định được cột 'Số tiền HĐ' trong file. Vui lòng kiểm tra tiêu đề cột.", $backUrl, false);
	if(!isset($map['gv']))  $func->transfer("Không xác định được cột 'GV' (tên giáo viên) trong file. Vui lòng kiểm tra tiêu đề cột.", $backUrl, false);

	// Nếu không nhận diện được cột "hợp lệ" theo tiêu đề (header đặt tên khác lạ), tìm cột chứa
	// chủ yếu là "x"/để trống trong vùng dữ liệu — quy ước cột đánh dấu hợp lệ thường nằm ở cuối bảng.
	if(!isset($map['hople']))
	{
		$usedCols = array_flip($map);
		$scanTo = min($highestRow, $headerRow + 200);
		$bestCol = -1;
		for($col = $highestColIndex - 1; $col >= 0; $col--)
		{
			if(isset($usedCols[$col])) continue;
			$xHits = 0; $filled = 0;
			for($row = $headerRow + 1; $row <= $scanTo; $row++)
			{
				$v = xd_mb_lower(trim(xd_cell($sheet, $col, $row)));
				if($v === '') continue;
				$filled++;
				if($v === 'x') $xHits++;
			}
			// Cột hợp lệ: phần lớn giá trị đã điền là "x" (không lẫn số tiền/tên/ngày...)
			if($filled > 0 && $xHits > 0 && $xHits === $filled) { $bestCol = $col; break; }
		}
		if($bestCol >= 0) $map['hople'] = $bestCol;
	}

	// Ghi nhận tiêu đề cột "hợp lệ" đã nhận diện được (nếu có) để hiển thị ra thông báo, giúp phát hiện ngay nếu bắt nhầm cột.
	$hopLeHeaderText = isset($map['hople']) ? xd_cell($sheet, $map['hople'], $headerRow) : null;

	// Thu thập các dòng hợp lệ
	$rows = array();
	$dateErrors = array();
	$emptyStreak = 0;
	for($row = $headerRow + 1; $row <= $highestRow; $row++)
	{
		$ma = xd_cell($sheet, $map['ma'], $row);
		$gvten = xd_cell($sheet, $map['gv'], $row);
		$bienso = isset($map['bienso']) ? xd_cell($sheet, $map['bienso'], $row) : '';
		$ttbanhang = isset($map['ttbanhang']) ? xd_cell($sheet, $map['ttbanhang'], $row) : '';
		$chitiet = isset($map['chitiet']) ? xd_cell($sheet, $map['chitiet'], $row) : '';
		$note1 = isset($map['note1']) ? xd_cell($sheet, $map['note1'], $row) : '';

		if($ma === '' && $gvten === '')
		{
			continue; // dòng trống
		}
		$emptyStreak = 0;
		if($ma === '')
		{
			// bỏ qua dòng không có số hóa đơn (vd dòng tổng)
			continue;
		}

		$ngay = xd_date_from_cell($sheet, $map['ngay'], $row);
		if($ngay === null && trim((string)xd_cell_raw($sheet, $map['ngay'], $row)) !== '')
		{
			$dateErrors[] = "Dòng $row: ngày hóa đơn '".htmlspecialchars(trim((string)xd_cell_raw($sheet, $map['ngay'], $row)))."' không tồn tại hoặc sai định dạng.";
			continue;
		}
		$tien = xd_money_from_cell($sheet, $map['tien'], $row);
		$gvkey = xd_gv_key($gvten);
		$hopLe = xd_hople_from_cell($sheet, $map, $row);

		$rows[] = array(
			'row' => $row, 'ma' => $ma, 'ngay' => $ngay, 'tien' => $tien,
			'gvten' => $gvten, 'gvkey' => $gvkey, 'bienso' => $bienso,
			'ttbanhang' => $ttbanhang, 'chitiet' => $chitiet, 'note1' => $note1, 'hople' => $hopLe
		);
	}

	if(!empty($dateErrors) && function_exists('xd_import_record_failure')) xd_import_record_failure('hoadon', implode('<br>', $dateErrors), false);
	if(!empty($dateErrors))
		$func->transfer("Không import file vì có ngày không hợp lệ (chưa lưu hóa đơn nào):<br>".implode("<br>", array_slice($dateErrors, 0, 20)), $backUrl, false);
	if(empty($rows)) $func->transfer("File không có dòng hóa đơn hợp lệ nào.", $backUrl, false);

	// Chặn import đè kỳ đã tồn tại dữ liệu (nếu có nhập kỳ) — trừ khi chọn "Đồng bộ lại"
	// để cập nhật cờ hợp lệ/Note 1 cho hóa đơn chưa quyết toán (dedup đảm bảo không tạo bản ghi trùng).
	$resync = !empty($_POST['resync']);
	/* Nhiều file cùng một lần tải lên (cùng upload_session) được coi là cùng một lượt kỳ. */
	$uploadSession = preg_replace('/[^a-f0-9]/', '', (string)($_POST['upload_session'] ?? ''));
	$sameBatch = $uploadSession !== '' && isset($_SESSION['xd_hd_batch'][$uploadSession]) && $_SESSION['xd_hd_batch'][$uploadSession] === $kyForm;
	if($kyForm !== '' && !$resync && !$sameBatch)
	{
		$existKy = $d->rawQueryOne("select count(*) as num from #_xd_hoadon where ky = ?", array($kyForm));
		if($existKy && (int)$existKy['num'] > 0)
			$func->transfer("Kỳ <strong>".htmlspecialchars($kyForm)."</strong> đã được import trước đó. Không thể import đè để tránh trùng dữ liệu. Tích \"Đồng bộ lại\" nếu muốn cập nhật cờ hợp lệ cho kỳ này.", $backUrl, false);
	}

	$username = xd_username();
	$inserted = 0;
	$updatedFixed = 0;
	$skippedDup = 0;
	$skippedLocked = 0;
	$movedKy = 0;
	$errors = array();
	$seenKeys = array();
	$hopLeCount = 0;
	$khongHopLeCount = 0;

	$snapshot = xd_backup_table_json('xd_hoadon', 'imp_hoadon');
	if($snapshot === '') $func->transfer("Không sao lưu được dữ liệu trước khi import; chưa lưu hóa đơn nào.", $backUrl, false);
	$affectedIds = array();
	$d->startTransaction();
	foreach($rows as $r)
	{
		// Số HĐ có thể trùng giữa các giáo viên khác nhau -> khóa trùng gồm cả GV (Mã HĐ + Ngày HĐ + GV)
		$key = $r['ma'].'|'.($r['ngay'] === null ? '' : $r['ngay']).'|'.$r['gvkey'];
		if(isset($seenKeys[$key])) { $skippedDup++; continue; }
		$seenKeys[$key] = 1;

		// Kiểm tra trùng (ma_hoa_don + ngay_hoa_don + gv_key)
		$exists = $d->rawQueryOne(
			"select id, da_quyettoan, hop_le, note_1, ky from #_xd_hoadon where ma_hoa_don = ? and (ngay_hoa_don <=> ?) and gv_key = ? limit 0,1",
			array($r['ma'], $r['ngay'], $r['gvkey'])
		);
		if($exists && isset($exists['id']))
		{
			if((int)$exists['da_quyettoan'] === 1) { $skippedLocked++; continue; }
			// HĐ đã có nhưng chưa quyết toán: đồng bộ lại cờ "Hợp lệ" + Note 1 theo file
			// (sửa được dữ liệu cũ bị nhận diện sai mà không tạo bản ghi trùng).
			$newKy = ($resync && $kyForm !== '') ? $kyForm : (string)$exists['ky'];
			if((int)$exists['hop_le'] !== (int)$r['hople'] || (string)$exists['note_1'] !== (string)$r['note1'] || (string)$exists['ky'] !== $newKy)
			{
				$d->rawQuery("update #_xd_hoadon set hop_le = ?, note_1 = ?, ky = ? where id = ? and da_quyettoan = 0", array($r['hople'], $r['note1'], $newKy, $exists['id']));
				if((string)$exists['ky'] !== $newKy) $movedKy++;
				$updatedFixed++;
				$affectedIds[] = (int)$exists['id'];
			}
			else $skippedDup++;
			continue;
		}

		$ok = $d->rawQuery(
			"insert into #_xd_hoadon (gv_cccd, gv_hoten, gv_key, note_1, ma_hoa_don, thong_tin_ban_hang, chi_tiet, ngay_hoa_don, tong_tien, bien_so, ky, da_quyettoan, hop_le, id_bangke, ngaytao, user_tao) values ('', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 0, ?, ?)",
			array($r['gvten'], $r['gvkey'], $r['note1'], $r['ma'], $r['ttbanhang'], $r['chitiet'], $r['ngay'], $r['tien'], $r['bienso'], $kyForm, $r['hople'], time(), $username)
		);
		if($ok === false || $d->getLastErrorCode() !== '00000')
		{
			$err = $d->getLastError();
			$errors[] = "Dòng ".$r['row'].": lỗi lưu dữ liệu".(is_array($err) && isset($err[2]) ? ' ('.$err[2].')' : '');
		}
		else { $inserted++; $affectedIds[] = (int)$d->pdo()->lastInsertId(); if((int)$r['hople'] === 1) $hopLeCount++; else $khongHopLeCount++; }
	}

	if(!empty($errors))
	{
		$d->rollback();
		$func->transfer("Import thất bại:<br>".implode("<br>", array_slice($errors, 0, 10)), $backUrl, false);
	}

	if($d->transactionFailed())
	{
		$d->rollback();
		$func->transfer("Import thất bại khi ghi cơ sở dữ liệu; đã hoàn tác toàn bộ file.", $backUrl, false);
	}
	$d->commit();
	if($uploadSession !== '' && $kyForm !== '') $_SESSION['xd_hd_batch'][$uploadSession] = $kyForm;
	xd_import_history_done('hoadon', array('filename' => (string)$file['name'], 'so_them' => $inserted, 'so_capnhat' => $updatedFixed,
		'backup' => $snapshot, 'ids' => $affectedIds, 'link' => 'index.php?com=xangdau&act=hoadon'.($kyForm !== '' ? '&ky='.urlencode($kyForm) : '')));

	$msg = "Import thành công $inserted hóa đơn.";
	if($updatedFixed > 0) $msg .= "<br>Đã đồng bộ lại $updatedFixed hóa đơn chưa quyết toán (cập nhật cờ Hợp lệ / Note 1".($movedKy > 0 ? " / chuyển $movedKy hóa đơn sang kỳ ".htmlspecialchars($kyForm) : "").").";
	if($skippedDup > 0) $msg .= "<br>Bỏ qua $skippedDup hóa đơn đã có, không thay đổi (trùng Mã HĐ + Ngày HĐ + Giáo viên).";
	if($skippedLocked > 0) $msg .= "<br>Bỏ qua $skippedLocked hóa đơn đã quyết toán (bị khóa).";
	if($hopLeHeaderText !== null) $msg .= "<br><em>Cột \"Hợp lệ\" nhận diện từ tiêu đề: \"".htmlspecialchars($hopLeHeaderText)."\" — $hopLeCount hợp lệ, $khongHopLeCount không hợp lệ.</em>";
	else $msg .= "<br><em>Không tìm thấy cột \"Hợp lệ\"/Note1 trong file — tất cả được coi là hợp lệ mặc định.</em>";
	$func->transfer($msg, "index.php?com=xangdau&act=hoadon");
}
