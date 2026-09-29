<?php
if(!defined('SOURCES')) die("Error");

/**
 * Mở một sheet dữ liệu từ file .xlsx upload.
 * $sheetHints: mảng chuỗi (norm, không dấu, không khoảng trắng) chọn sheet theo tên; rỗng = sheet đầu.
 * @return array [sheet, highestRow, highestColIndex]  (thoát bằng transfer nếu lỗi)
 */
function dt_open_upload_sheet($file, $ext, $backUrl, $sheetHints = array())
{
	global $func;

	@ini_set('memory_limit', '1024M');
	require_once LIBRARIES.'PHPExcel.php';

	$ext = strtolower($ext);
	if($ext !== 'xlsx' && $ext !== 'xls')
		$func->transfer("Chỉ hỗ trợ file .xlsx hoặc .xls. Vui lòng lưu lại đúng định dạng rồi import lại.", $backUrl, false);

	$inputFileName = $file['tmp_name'];
	if(empty($inputFileName) || !is_readable($inputFileName))
		$func->transfer("Không đọc được file tạm. Vui lòng thử lại.", $backUrl, false);

	if(is_string($sheetHints)) $sheetHints = ($sheetHints === '') ? array() : array($sheetHints);

	try {
		$reader = PHPExcel_IOFactory::createReader($ext === 'xls' ? 'Excel5' : 'Excel2007');
		$reader->setReadDataOnly(true);

		$targetSheet = null;
		if(!empty($sheetHints))
		{
			$names = $reader->listWorksheetNames($inputFileName);
			foreach($names as $name)
			{
				$norm = preg_replace('/[^a-z0-9]+/', '', dt_mb_lower($name));
				foreach($sheetHints as $hint)
				{
					if($hint !== '' && strpos($norm, $hint) !== false) { $targetSheet = $name; break 2; }
				}
			}
			if($targetSheet !== null) $reader->setLoadSheetsOnly($targetSheet);
		}

		$objPHPExcel = $reader->load($inputFileName);
		$sheet = ($targetSheet !== null) ? $objPHPExcel->getSheetByName($targetSheet) : $objPHPExcel->getSheet(0);
		if($sheet === null) $sheet = $objPHPExcel->getSheet(0);
	} catch(Exception $e) {
		$func->transfer("Lỗi đọc file Excel: ".$e->getMessage(), $backUrl, false);
		return null;
	}

	$highestRow = $sheet->getHighestRow();
	$highestColIndex = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn()) - 1;
	if($highestRow > 20000) $highestRow = 20000; // an toàn với file khai báo dòng khổng lồ

	return array($sheet, $highestRow, $highestColIndex);
}

/* Đọc 1 dòng thành mảng chỉ số cột 0-based (giá trị RAW, đã trim). */
function dt_read_row($sheet, $rowNum, $highestColIndex)
{
	$row = array();
	for($c = 0; $c <= $highestColIndex; $c++)
	{
		$cell = $sheet->getCellByColumnAndRow($c, $rowNum);
		$val = $cell->getValue();
		if(is_string($val) && strlen($val) > 0 && $val[0] === '=')
		{
			$calculated = '';
			try { $calculated = $cell->getCalculatedValue(); } catch(Exception $e) { $calculated = ''; }
			if($calculated !== null && $calculated !== '' && $calculated !== $val && strpos((string)$calculated, '#') !== 0)
				$val = $calculated;
			else
				$val = '';
		}
		$row[$c] = ($val === null) ? '' : trim((string)$val);
	}
	return $row;
}

/* Tìm dòng tiêu đề trong N dòng đầu: dòng có nhiều field khớp alias nhất (score cao nhất). */
function dt_find_header_row($sheet, $highestRow, $highestColIndex, $aliasGroups, $containsRules = array(), $scanRows = 12)
{
	$bestRow = 1; $bestMap = array(); $bestScore = -1;
	$limit = min($scanRows, $highestRow);
	for($r = 1; $r <= $limit; $r++)
	{
		$cells = dt_read_row($sheet, $r, $highestColIndex);
		list($map, $score) = dt_detect_header($cells, $aliasGroups, $containsRules);
		if($score > $bestScore) { $bestScore = $score; $bestMap = $map; $bestRow = $r; }
	}
	return array($bestRow, $bestMap, $bestScore);
}

/**
 * Trích "Mã khóa học" và "Hạng đào tạo" ghi ở phần tiêu đề file (dạng nhãn:giá trị),
 * ví dụ file cabin: dòng "Mã khóa học : K13C1", "Hạng đào tạo : C1".
 * Quét từ đầu tới trước dòng header dữ liệu. @return array('khoa'=>..,'hang'=>..)
 */
function dt_extract_meta_khoa($sheet, $maxRow, $highestColIndex)
{
	$khoa = ''; $hang = '';
	$maxRow = min((int)$maxRow, 25);
	for($r = 1; $r <= $maxRow; $r++)
	{
		for($c = 0; $c <= $highestColIndex; $c++)
		{
			$val = $sheet->getCellByColumnAndRow($c, $r)->getValue();
			if($val === null || trim((string)$val) === '') continue;
			$txt = trim((string)$val);
			$norm = dt_norm_header($txt);
			$isKhoa = ($khoa === '' && (strpos($norm, 'makhoahoc') !== false || strpos($norm, 'makhoa') !== false));
			$isHang = ($hang === '' && strpos($norm, 'hangdaotao') !== false && strpos($norm, 'duocphep') === false);
			if(!$isKhoa && !$isHang) continue;

			$v = '';
			if(strpos($txt, ':') !== false) { $parts = explode(':', $txt); $v = trim(end($parts)); }
			if($v === '')
			{
				for($cc = $c + 1; $cc <= $highestColIndex; $cc++)
				{
					$nv = $sheet->getCellByColumnAndRow($cc, $r)->getValue();
					if($nv !== null && trim((string)$nv) !== '') { $v = trim((string)$nv); break; }
				}
			}
			if($v === '') continue;
			if($isKhoa) $khoa = $v; elseif($isHang) $hang = $v;
		}
		if($khoa !== '' && $hang !== '') break;
	}
	return array('khoa' => $khoa, 'hang' => $hang);
}
