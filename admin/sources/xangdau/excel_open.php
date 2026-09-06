<?php
if(!defined('SOURCES')) die("Error");

/**
 * Mở sheet dữ liệu từ file upload, hỗ trợ .xlsx/.xls/.xlsb (xlsb qua chuyển đổi).
 * $sheetHints: mảng chuỗi không dấu (không khoảng trắng) để chọn sheet theo tên
 *   (vd ['hocvien'] hoặc ['hdon','hoadon']); rỗng = sheet đầu.
 *   Chỉ nạp đúng sheet khớp để tránh tốn bộ nhớ với file nhiều/lớn sheet.
 * @return array [objPHPExcel, sheet, highestRow, highestColIndex]  (thoát bằng transfer nếu lỗi)
 */
function xd_open_upload_sheet($file, $ext, $backUrl, $sheetHints = array())
{
	global $func;

	@ini_set('memory_limit', '2048M');
	require_once LIBRARIES.'PHPExcel.php';

	// Bộ lọc giới hạn số dòng đọc: file chuyển đổi có thể khai báo tới 1.048.576 dòng
	// khiến PHPExcel cấp phát khổng lồ. Chỉ đọc trong phạm vi dòng hợp lý.
	if(!class_exists('XdRowLimitReadFilter'))
	{
		class XdRowLimitReadFilter implements PHPExcel_Reader_IReadFilter
		{
			public $maxRow;
			public function __construct($maxRow = 20000) { $this->maxRow = (int)$maxRow; }
			public function readCell($column, $row, $worksheetName = '') { return $row <= $this->maxRow; }
		}
	}

	// Giảm bộ nhớ khi nạp sheet lớn: cache ô ra php://temp (tràn ra đĩa sau ngưỡng).
	if(class_exists('PHPExcel_Settings') && class_exists('PHPExcel_CachedObjectStorageFactory'))
	{
		@PHPExcel_Settings::setCacheStorageMethod(
			PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
			array('memoryCacheSize' => '256MB')
		);
	}

	$inputFileName = $file['tmp_name'];
	if(empty($inputFileName) || !is_readable($inputFileName)) $func->transfer("Không đọc được file tạm. Vui lòng thử lại.", $backUrl, false);

	if(is_string($sheetHints)) $sheetHints = ($sheetHints === '') ? array() : array($sheetHints);

	$readerType = 'Excel2007';
	$loadFile = $inputFileName;
	$convertedFile = '';

	if($ext === 'xlsb')
	{
		$convertedFile = xd_convert_to_xlsx($inputFileName);
		if($convertedFile === false || !is_file($convertedFile))
		{
			$func->transfer("Máy chủ chưa hỗ trợ đọc file .xlsb. Vui lòng mở file và lưu lại dưới định dạng .xlsx rồi import lại.", $backUrl, false);
		}
		$loadFile = $convertedFile;
		$readerType = 'Excel2007';
	}
	elseif($ext === 'xls')
	{
		$readerType = 'Excel5';
	}

	// Đọc XLSX theo luồng để tránh PHPExcel nạp cả workbook vào RAM trên production.
	// File XLSB sau khi chuyển đổi cũng đi qua cùng reader này.
	if($ext === 'xlsx' || $ext === 'xlsb')
	{
		$streamRow = 0; $streamCol = 0;
		$streamSheet = xd_stream_read_xlsx($loadFile, $sheetHints, $streamRow, $streamCol, 20000);
		if($streamSheet !== false)
		{
			xd_cleanup_converted($convertedFile);
			return array(null, $streamSheet, $streamRow, $streamCol);
		}
		// Nếu đọc luồng thất bại thì thử tiếp bằng PHPExcel (best-effort).
	}

	try
	{
		$reader = PHPExcel_IOFactory::createReader($readerType);
		if(method_exists($reader, 'setReadDataOnly')) $reader->setReadDataOnly(true);
		if(method_exists($reader, 'setReadFilter')) $reader->setReadFilter(new XdRowLimitReadFilter(20000));

		// Chọn tên sheet cần nạp (chỉ nạp đúng sheet đó để tiết kiệm bộ nhớ)
		$targetName = '';
		if(!empty($sheetHints) && method_exists($reader, 'listWorksheetNames'))
		{
			$names = $reader->listWorksheetNames($loadFile);
			foreach($sheetHints as $hint)
			{
				foreach($names as $nm)
				{
					if(strpos(xd_norm_header($nm), $hint) !== false) { $targetName = $nm; break 2; }
				}
			}
			if($targetName !== '' && method_exists($reader, 'setLoadSheetsOnly')) $reader->setLoadSheetsOnly($targetName);
		}

		$objPHPExcel = $reader->load($loadFile);
	}
	catch(Throwable $e)
	{
		xd_cleanup_converted($convertedFile);
		$func->transfer("Không đọc được file Excel. Vui lòng lưu lại dưới định dạng .xlsx và thử lại.", $backUrl, false);
		return array();
	}

	// Chọn sheet: nếu đã nạp đúng 1 sheet mục tiêu thì lấy sheet đầu; nếu không, dò theo hint.
	$sheet = null;
	if(!empty($sheetHints))
	{
		foreach($objPHPExcel->getWorksheetIterator() as $ws)
		{
			$title = xd_norm_header($ws->getTitle());
			foreach($sheetHints as $hint)
			{
				if(strpos($title, $hint) !== false) { $sheet = $ws; break 2; }
			}
		}
	}
	if($sheet === null) $sheet = $objPHPExcel->getSheet(0);

	$highestRow = (int)$sheet->getHighestRow();
	$highestColIndex = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());

	xd_cleanup_converted($convertedFile);

	return array($objPHPExcel, $sheet, $highestRow, $highestColIndex);
}
