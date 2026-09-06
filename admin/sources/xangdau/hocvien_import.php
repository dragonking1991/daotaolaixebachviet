<?php
if(!defined('SOURCES')) die("Error");

/* ============================ Import học viên (chống trùng CCCD) ============================ */

function xd_upload_hocvien_excel()
{
	global $d, $func;

	@ini_set('memory_limit', '1024M');
	@set_time_limit(300);

	$backUrl = "index.php?com=xangdau&act=uploadHocvien";

	if(!isset($_FILES['file-excel']) || (int)$_FILES['file-excel']['error'] !== UPLOAD_ERR_OK)
		xd_hocvien_import_error("Vui lòng chọn file Excel hợp lệ", $backUrl);

	$file = $_FILES['file-excel'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	if($ext !== 'xlsx') xd_hocvien_import_error("Import học viên chỉ hỗ trợ file .xlsx. Vui lòng mở file XLSB/XLS bằng Excel và lưu lại dưới dạng .xlsx rồi thử lại.", $backUrl);

	list($objPHPExcel, $sheet, $highestRow, $highestColIndex) = xd_open_upload_sheet($file, $ext, $backUrl, array('hocvien'));

	// Cột theo mẫu thực tế: STT | HỌ VÀ TÊN | KHÓA | Ngày sinh | Số CCCD | NGƯỜI NỘP | GIÁO VIÊN/PHÂN XE | Nhóm/GHI CHÚ | đã tt | Menu | ...
	$aliasGroups = array(
		'ten'      => array('hovaten', 'hoten', 'tenhocvien', 'hocvien', 'tenhv'),
		'khoa'     => array('khoa'),
		'ngaysinh' => array('ngaythangnamsinh', 'ngaysinh', 'namsinh'),
		'cccd'     => array('socccdcch', 'socccd', 'cccd', 'cccdcc', 'cmnd', 'cccdhv'),
		'nguoinop' => array('nguoinop', 'nguoinophoso'),
		'gv'       => array('phanxe', 'giaovien', 'gv', 'tengiaovien', 'gvphutrach'),
		'nhom'     => array('nhom', 'loai', 'phanloai', 'nhomdaotao', 'ghichu'),
		'datt'     => array('datt', 'dathanhtoan', 'trangthaithanhtoan', 'tinhtrangthanhtoan'),
	);
	$containsRules = array(
		'ten'      => array('has' => array('hovaten')),
		'cccd'     => array('has' => array('cccd')),
		'gv'       => array('has' => array('phanxe')),
		'nhom'     => array('has' => array('nhom')),
		'ngaysinh' => array('has' => array('sinh')),
		'nguoinop' => array('has' => array('nguoinop')),
		'datt'     => array('has' => array('datt')),
	);

	list($headerRow, $map, $headerScore) = xd_detect_header($sheet, $highestRow, $highestColIndex, $aliasGroups, $containsRules);

	// Chỉ giả định thứ tự cột mẫu khi hoàn toàn không nhận diện được tiêu đề nào.
	if($headerScore <= 0)
	{
		$map = array('ten' => 1, 'khoa' => 2, 'ngaysinh' => 3, 'cccd' => 4, 'nguoinop' => 5, 'gv' => 6, 'nhom' => 7, 'datt' => 8);
		$headerRow = 1;
	}

	// Nếu chưa nhận diện được cột nhóm theo tiêu đề (vd tiêu đề "GHI CHÚ" mơ hồ),
	// chọn cột có nhiều giá trị thuộc {BT, CK, DAT} nhất trong vùng dữ liệu.
	if(!isset($map['nhom']))
	{
		$bestCol = -1; $bestHits = 0;
		$usedCols = array_flip($map);
		$scanTo = min($highestRow, $headerRow + 60);
		for($col = 0; $col < $highestColIndex; $col++)
		{
			if(isset($usedCols[$col])) continue;
			$hits = 0;
			for($row = $headerRow + 1; $row <= $scanTo; $row++)
			{
				$v = strtoupper(trim(xd_cell($sheet, $col, $row)));
				if(in_array($v, array('BT', 'CK', 'DAT'), true)) $hits++;
			}
			if($hits > $bestHits) { $bestHits = $hits; $bestCol = $col; }
		}
		if($bestCol >= 0 && $bestHits > 0) $map['nhom'] = $bestCol;
	}

	// Có thể tồn tại nhiều cột cùng tiêu đề "Ngày ... sinh" (một cột hiển thị, một cột dữ liệu).
	// Chọn cột ngày sinh có NHIỀU giá trị nhất trong vùng dữ liệu để tránh chọn nhầm cột rỗng.
	{
		$sinhCols = array();
		for($col = 0; $col < $highestColIndex; $col++)
		{
			if(strpos(xd_norm_header($sheet->getCellByColumnAndRow($col, $headerRow)->getValue()), 'sinh') !== false) $sinhCols[] = $col;
		}
		if(count($sinhCols) > 1)
		{
			$bestCol = isset($map['ngaysinh']) ? $map['ngaysinh'] : $sinhCols[0]; $bestHits = -1;
			$scanTo = min($highestRow, $headerRow + 60);
			foreach($sinhCols as $col)
			{
				$hits = 0;
				// Dùng giá trị THÔ (serial) vì readDataOnly khiến getFormattedValue rỗng với ô ngày.
				for($row = $headerRow + 1; $row <= $scanTo; $row++) { $rv = xd_cell_raw($sheet, $col, $row); if($rv !== null && $rv !== '') $hits++; }
				if($hits > $bestHits) { $bestHits = $hits; $bestCol = $col; }
			}
			$map['ngaysinh'] = $bestCol;
		}
	}

	$username = xd_username();
	$rows = array();
	$errors = array();
	$seenInFile = array();

	// Bắt buộc xác định được các cột cốt lõi, tránh đọc nhầm hoặc lưu thiếu dữ liệu
	if(!isset($map['cccd']))
	{
		xd_hocvien_import_error("Không xác định được cột CCCD học viên trong file. Vui lòng đặt tên cột chứa chuỗi 'CCCD' (ví dụ: Số CCCD/CC) và thử lại.", $backUrl);
	}
	if(!isset($map['ten']))
	{
		xd_hocvien_import_error("Không xác định được cột Họ tên học viên trong file. Vui lòng đặt tên cột chứa 'Họ và tên' và thử lại.", $backUrl);
	}
	if(!isset($map['gv']))
	{
		xd_hocvien_import_error("Không xác định được cột Giáo viên trong file. Vui lòng đặt tên cột chứa 'Giáo viên' và thử lại.", $backUrl);
	}

	$emptyStreak = 0;
	for($row = $headerRow + 1; $row <= $highestRow; $row++)
	{
		$ten = xd_val($sheet, $map, 'ten', $row);
		$khoa = xd_val($sheet, $map, 'khoa', $row);
		$ngaysinh = isset($map['ngaysinh']) ? xd_date_from_cell($sheet, $map['ngaysinh'], $row) : '';
		if($ngaysinh === null) $ngaysinh = '';
		$cccdRaw = xd_val($sheet, $map, 'cccd', $row);
		$cccd = xd_normalize_cccd($cccdRaw);
		$nguoinop = xd_val($sheet, $map, 'nguoinop', $row);
		$gvten = xd_val($sheet, $map, 'gv', $row);
		$gvkey = xd_gv_key($gvten);
		$nhom = strtoupper(trim(xd_val($sheet, $map, 'nhom', $row)));
		// Cột "đã tt": chỉ khi giá trị đúng là "r" mới coi là đã thanh toán
		$dattRaw = isset($map['datt']) ? strtolower(trim(xd_val($sheet, $map, 'datt', $row))) : '';
		$daTT = ($dattRaw === 'r');

		if($ten === '' && $cccd === '')
		{
			// Dừng sớm khi gặp nhiều dòng trống liên tiếp (file chuyển đổi có thể có vùng dùng đến hết bảng)
			if(++$emptyStreak > 50) break;
			continue; // dòng trống
		}
		$emptyStreak = 0;

		if($cccd === '')
		{
			$errors[] = "Dòng $row: thiếu số CCCD học viên.";
			continue;
		}
		if($ten === '')
		{
			$errors[] = "Dòng $row: thiếu họ tên học viên.";
			continue;
		}
		if($gvten === '')
		{
			$errors[] = "Dòng $row: thiếu tên giáo viên phụ trách.";
			continue;
		}
		if(!in_array($nhom, array('BT', 'CK', 'DAT'), true))
		{
			$errors[] = "Dòng $row: nhóm không hợp lệ (chỉ nhận BT, CK, DAT). Giá trị nhận được: '".htmlspecialchars($nhom)."'.";
			continue;
		}

		// Trùng trong chính file
		if(isset($seenInFile[$cccd]))
		{
			$errors[] = "Dòng $row: CCCD $cccd bị lặp trong file (đã xuất hiện ở dòng ".$seenInFile[$cccd].").";
			continue;
		}
		$seenInFile[$cccd] = $row;

		$rows[] = array(
			'row' => $row, 'ten' => $ten, 'khoa' => $khoa, 'ngaysinh' => $ngaysinh, 'cccd' => $cccd,
			'nguoinop' => $nguoinop, 'nhom' => $nhom, 'gvten' => $gvten, 'gvkey' => $gvkey, 'datt' => $daTT, 'existing_id' => 0
		);
	}

	// Học viên đã có nhưng chưa thanh toán được phép import lại để cập nhật trạng thái.
	foreach($rows as &$r)
	{
		$variants = xd_cccd_variants($r['cccd']);
		$placeholders = implode(',', array_fill(0, count($variants), '?'));
		$existing = $d->rawQueryOne("select id, ngay_thanh_toan from #_xd_hocvien where cccd in ($placeholders) limit 0,1", $variants);
		if($existing && isset($existing['id']))
		{
			if($existing['ngay_thanh_toan'] !== null)
				$errors[] = "Dòng ".$r['row'].": CCCD ".$r['cccd']." đã thanh toán, không thể import ghi đè.";
			else
				$r['existing_id'] = (int)$existing['id'];
		}
	}
	unset($r);

	if(!empty($errors))
	{
		xd_hocvien_import_error("Không lưu file do có lỗi trùng lặp/không hợp lệ (đã chặn toàn bộ):<br>".implode("<br>", array_slice($errors, 0, 20)), $backUrl);
	}

	if(empty($rows)) xd_hocvien_import_error("File không có dòng học viên hợp lệ nào.", $backUrl);

	// Ghi all-or-nothing
	$inserted = 0;
	$updated = 0;
	$paidCount = 0;
	$failRows = array();
	$today = date('Y-m-d');
	$config = getXdConfig($d);
	$d->startTransaction();
	foreach($rows as $r)
	{
		// Học viên đã thanh toán trước đó (cột "đã tt" có chữ "r") -> đánh dấu ngày TT + phí theo nhóm để loại khỏi thuật toán lọc
		$daTT = !empty($r['datt']);
		$ngayTT  = $daTT ? $today : null;
		$dinhMuc = $daTT ? (int)$config['dinh_muc'] : 0;
		$soTien  = $daTT ? (int)xdMucTheoNhom($config, $r['nhom']) : 0;
		if($r['existing_id'] > 0)
		{
			$ok = $d->rawQuery(
				"update #_xd_hocvien set ho_ten = ?, cccd = ?, ngaysinh = ?, khoa = ?, nhom = ?, nguoi_nop = ?, gv_hoten = ?, gv_key = ?, dinh_muc = ?, so_tien_thanh_toan = ?, ngay_thanh_toan = ?, id_bangke = 0 where id = ? and ngay_thanh_toan is null",
				array($r['ten'], $r['cccd'], $r['ngaysinh'], $r['khoa'], $r['nhom'], $r['nguoinop'], $r['gvten'], $r['gvkey'], $dinhMuc, $soTien, $ngayTT, $r['existing_id'])
			);
		}
		else
		{
			$ok = $d->rawQuery(
				"insert into #_xd_hocvien (ho_ten, cccd, ngaysinh, khoa, nhom, nguoi_nop, gv_cccd, gv_hoten, gv_key, dinh_muc, so_tien_thanh_toan, ngay_thanh_toan, id_bangke, ngaytao, user_tao) values (?, ?, ?, ?, ?, ?, '', ?, ?, ?, ?, ?, 0, ?, ?)",
				array($r['ten'], $r['cccd'], $r['ngaysinh'], $r['khoa'], $r['nhom'], $r['nguoinop'], $r['gvten'], $r['gvkey'], $dinhMuc, $soTien, $ngayTT, time(), $username)
			);
		}
		if($ok === false) $failRows[] = $r['row'];
		else { if($r['existing_id'] > 0) $updated++; else $inserted++; if($ngayTT !== null) $paidCount++; }
	}

	if(!empty($failRows))
	{
		$d->rollback();
		xd_hocvien_import_error("Import thất bại khi lưu dữ liệu (không lưu dòng nào). Ví dụ dòng: ".implode(', ', array_slice($failRows, 0, 10)), $backUrl);
	}

	$d->commit();
	$msg = "Import thành công $inserted học viên mới";
	if($updated > 0) $msg .= "; cập nhật $updated học viên đã có";
	$msg .= ".";
	if($paidCount > 0) $msg .= " Trong đó $paidCount học viên đã thanh toán (cột \"đã tt\" = r).";
	$func->transfer($msg, "index.php?com=xangdau&act=hocvien");
}

function xd_hocvien_import_error($message, $backUrl)
{
	if(session_status() !== PHP_SESSION_ACTIVE) @session_start();
	$_SESSION['xd_hocvien_import_error'] = $message;
	header('Location: '.$backUrl);
	exit();
}
