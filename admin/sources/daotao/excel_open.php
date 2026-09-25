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
	if($ext !== 'xlsx')
		$func->transfer("Chỉ hỗ trợ file .xlsx. Vui lòng mở file và lưu lại dưới định dạng .xlsx rồi import lại.", $backUrl, false);

	$inputFileName = $file['tmp_name'];
	if(empty($inputFileName) || !is_readable($inputFileName))
		$func->transfer("Không đọc được file tạm. Vui lòng thử lại.", $backUrl, false);

	if(is_string($sheetHints)) $sheetHints = ($sheetHints === '') ? array() : array($sheetHints);

	try {
		$reader = PHPExcel_IOFactory::createReader('Excel2007');
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
		$val = $sheet->getCellByColumnAndRow($c, $rowNum)->getValue();
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
