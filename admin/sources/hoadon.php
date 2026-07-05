<?php
if(!defined('SOURCES')) die("Error");

switch($act)
{
	case "man":
		if(hoadon_permission_denied(array('hoadon_man', 'cabin_man', 'product_man_cabin', 'order_man'))) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		hoadon_ensure_table();
		get_items_hoadon();
		$template = "hoadon/man/items";
		break;
	case "upload":
		if(hoadon_permission_denied(array('hoadon_upload', 'cabin_upload', 'import_man_cabin'))) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		hoadon_ensure_table();
		$template = "hoadon/upload/items";
		break;
	case "uploadExcel":
		if(hoadon_permission_denied(array('hoadon_upload', 'cabin_upload', 'import_man_cabin'))) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		hoadon_ensure_table();
		uploadExcel_hoadon();
		break;
	case "delete":
		if(hoadon_permission_denied(array('hoadon_man', 'cabin_man', 'product_man_cabin', 'order_man'))) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		hoadon_ensure_table();
		delete_item_hoadon();
		break;
	case "deleteAll":
		if(hoadon_permission_denied(array('hoadon_man', 'cabin_man', 'product_man_cabin', 'order_man'))) $func->transfer("Bạn không có quyền vào trang này", "index.php", false);
		hoadon_ensure_table();
		delete_all_hoadon();
		break;
	default:
		$template = "404";
}

function hoadon_permission_denied($permissions = array())
{
	global $func, $login_admin;

	if(!$func->check_permission()) return false;
	if(!isset($_SESSION[$login_admin]['active']) || $_SESSION[$login_admin]['active'] != true) return true;
	if(!isset($_SESSION['list_quyen']) || !is_array($_SESSION['list_quyen'])) return true;

	foreach($permissions as $permission)
	{
		if(in_array($permission, $_SESSION['list_quyen'])) return false;
	}

	return true;
}

function hoadon_ensure_table()
{
	global $d;

	$d->rawQuery(
		"CREATE TABLE IF NOT EXISTS #_hoadon (\n"
		. " id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,\n"
		. " ma_so_hoa_don VARCHAR(191) NOT NULL,\n"
		. " ho_ten_nguoi_mua VARCHAR(255) NOT NULL DEFAULT '',\n"
		. " chi_tiet_hoa_don TEXT NULL,\n"
		. " loai_hoa_don VARCHAR(20) NOT NULL DEFAULT '',\n"
		. " ngay_hoa_don DATE NULL,\n"
		. " tong_tien DECIMAL(18,2) NULL DEFAULT NULL,\n"
		. " ngaytao INT(11) UNSIGNED NOT NULL DEFAULT 0,\n"
		. " ngaysua INT(11) UNSIGNED NOT NULL DEFAULT 0,\n"
		. " user_tao VARCHAR(100) NULL DEFAULT '',\n"
		. " user_sua VARCHAR(100) NULL DEFAULT '',\n"
		. " thong_tin_hoa_don LONGTEXT NULL,\n"
		. " PRIMARY KEY (id),\n"
		. " UNIQUE KEY uniq_ma_so_hoa_don (ma_so_hoa_don),\n"
		. " KEY idx_ngay_hoa_don (ngay_hoa_don)\n"
		. ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
	);

	$hasThongTinHoaDon = $d->rawQueryOne(
		"select count(*) as total from information_schema.columns where table_schema = database() and table_name = 'table_hoadon' and column_name = 'thong_tin_hoa_don'"
	);

	if(empty($hasThongTinHoaDon) || !isset($hasThongTinHoaDon['total']) || (int)$hasThongTinHoaDon['total'] <= 0)
	{
		$d->rawQuery("ALTER TABLE #_hoadon ADD COLUMN thong_tin_hoa_don LONGTEXT NULL AFTER user_sua");
	}

	$hasLoaiHoaDon = $d->rawQueryOne(
		"select count(*) as total from information_schema.columns where table_schema = database() and table_name = 'table_hoadon' and column_name = 'loai_hoa_don'"
	);

	if(empty($hasLoaiHoaDon) || !isset($hasLoaiHoaDon['total']) || (int)$hasLoaiHoaDon['total'] <= 0)
	{
		$d->rawQuery("ALTER TABLE #_hoadon ADD COLUMN loai_hoa_don VARCHAR(20) NOT NULL DEFAULT '' AFTER chi_tiet_hoa_don");
	}
}

function normalize_hoadon_header_label($label)
{
	$label = strtolower(trim((string)$label));
	$search = array('à','á','ả','ã','ạ','ă','ằ','ắ','ẳ','ẵ','ặ','â','ầ','ấ','ẩ','ẫ','ậ','đ','è','é','ẻ','ẽ','ẹ','ê','ề','ế','ể','ễ','ệ','ì','í','ỉ','ĩ','ị','ò','ó','ỏ','õ','ọ','ô','ồ','ố','ổ','ỗ','ộ','ơ','ờ','ớ','ở','ỡ','ợ','ù','ú','ủ','ũ','ụ','ư','ừ','ứ','ử','ữ','ự','ỳ','ý','ỷ','ỹ','ỵ');
	$replace = array('a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','d','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y');
	$label = str_replace($search, $replace, $label);
	return preg_replace('/[^a-z0-9]+/', '', $label);
}

function parse_hoadon_date($rawValue)
{
	$rawValue = trim((string)$rawValue);
	if($rawValue === '') return null;

	if(is_numeric($rawValue))
	{
		$excelDate = (float)$rawValue;
		if($excelDate > 0)
		{
			$ts = PHPExcel_Shared_Date::ExcelToPHP($excelDate);
			if($ts > 0) return date('Y-m-d', $ts);
		}
	}

	if(preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $rawValue, $m)) return $m[3].'-'.$m[2].'-'.$m[1];
	if(preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $rawValue)) return $rawValue;

	$rawValue = str_replace(array('.', ','), '/', $rawValue);
	$rawValue = preg_replace('/\s+/', ' ', $rawValue);
	$ts = strtotime($rawValue);
	if($ts !== false) return date('Y-m-d', $ts);

	return null;
}

function parse_hoadon_money($rawValue)
{
	$rawValue = trim((string)$rawValue);
	if($rawValue === '') return null;

	$rawValue = str_replace(array('VNĐ', 'VND', 'đ', ' '), '', $rawValue);
	$rawValue = str_replace(',', '.', $rawValue);

	if(substr_count($rawValue, '.') > 1)
	{
		$rawValue = str_replace('.', '', $rawValue);
	}

	if(!is_numeric($rawValue)) return null;

	return (float)$rawValue;
}

function hoadon_get_cell_string_value($sheet, $column, $row)
{
	$cell = $sheet->getCellByColumnAndRow($column, $row);
	$value = '';

	try
	{
		$value = $cell->getFormattedValue();
	}
	catch(Exception $e)
	{
		if(method_exists($cell, 'getOldCalculatedValue')) $value = $cell->getOldCalculatedValue();
		if($value === null || $value === '') $value = $cell->getValue();
	}

	if(is_object($value) && method_exists($value, '__toString')) $value = (string)$value;
	if(is_array($value)) $value = json_encode($value, JSON_UNESCAPED_UNICODE);

	return trim((string)$value);
}

function hoadon_column_letters_to_index($letters)
{
	$letters = strtoupper(trim((string)$letters));
	if($letters === '') return 0;

	$index = 0;
	$len = strlen($letters);
	for($i = 0; $i < $len; $i++)
	{
		$ord = ord($letters[$i]);
		if($ord < 65 || $ord > 90) continue;
		$index = ($index * 26) + ($ord - 64);
	}

	return max(0, $index - 1);
}

function hoadon_parse_xlsx_rows_fallback($inputFileName)
{
	if(!class_exists('ZipArchive')) return false;

	$zip = new ZipArchive();
	if($zip->open($inputFileName) !== true) return false;

	$sharedStrings = array();
	$sharedXml = $zip->getFromName('xl/sharedStrings.xml');
	if($sharedXml !== false)
	{
		$shared = @simplexml_load_string($sharedXml);
		if($shared)
		{
			foreach($shared->si as $si)
			{
				$text = '';
				if(isset($si->t))
				{
					$text = (string)$si->t;
				}
				else
				{
					foreach($si->r as $run)
					{
						$text .= (string)$run->t;
					}
				}
				$sharedStrings[] = $text;
			}
		}
	}

	$sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
	$zip->close();
	if($sheetXml === false) return false;

	$sheet = @simplexml_load_string($sheetXml);
	if(!$sheet || !isset($sheet->sheetData)) return false;

	$rows = array();
	foreach($sheet->sheetData->row as $rowNode)
	{
		$rowIndex = (int)$rowNode['r'];
		if($rowIndex <= 0) continue;

		if(!isset($rows[$rowIndex])) $rows[$rowIndex] = array();

		foreach($rowNode->c as $cell)
		{
			$ref = (string)$cell['r'];
			if(!preg_match('/^([A-Z]+)\d+$/i', $ref, $m)) continue;
			$colIndex = hoadon_column_letters_to_index($m[1]);

			$cellType = (string)$cell['t'];
			$value = '';
			if($cellType === 's')
			{
				$sharedIdx = (int)$cell->v;
				$value = isset($sharedStrings[$sharedIdx]) ? $sharedStrings[$sharedIdx] : '';
			}
			elseif($cellType === 'inlineStr')
			{
				$value = isset($cell->is->t) ? (string)$cell->is->t : '';
			}
			else
			{
				$value = isset($cell->v) ? (string)$cell->v : '';
			}

			$rows[$rowIndex][$colIndex] = trim((string)$value);
		}
	}

	return $rows;
}

function hoadon_get_value_from_rows($rows, $column, $row)
{
	if(isset($rows[$row]) && isset($rows[$row][$column])) return trim((string)$rows[$row][$column]);
	return '';
}

function hoadon_get_max_column_index_from_rows($rows)
{
	$maxCol = -1;
	if(!is_array($rows) || empty($rows)) return 0;

	foreach($rows as $rowValues)
	{
		if(!is_array($rowValues) || empty($rowValues)) continue;
		$rowMax = max(array_keys($rowValues));
		if($rowMax > $maxCol) $maxCol = $rowMax;
	}

	return ($maxCol >= 0) ? ($maxCol + 1) : 0;
}

function hoadon_sort_display_columns($columns)
{
	if(!is_array($columns) || empty($columns)) return array();

	$hiddenAliases = array('stt', 'sst', 'kyhieumauso');
	$filteredColumns = array();
	for($i = 0; $i < count($columns); $i++)
	{
		$normalized = normalize_hoadon_header_label($columns[$i]);
		if(in_array($normalized, $hiddenAliases, true)) continue;
		$filteredColumns[] = $columns[$i];
	}

	$columns = $filteredColumns;
	if(empty($columns)) return array();

	$priorityAliases = array(
		array('masohoadon', 'mahoadon', 'sohoadon', 'invoicecode', 'invoiceno', 'invoiceid'),
		array('hotennguoimuahang', 'hotennguoimua', 'tennguoimuahang', 'nguoimuahang', 'nguoimua', 'buyername', 'buyer', 'hoten'),
		array('chitiethoadon', 'chitiet', 'noidunghoadon', 'diengiai', 'invoicedetail', 'description'),
		array('ngayhoadon', 'ngaylap', 'ngaymua', 'date', 'invoicedate', 'ngay'),
		array('tongtien', 'tongthanhtoan', 'thanhtien', 'totalamount', 'total', 'tien')
	);

	$normalizedByIndex = array();
	for($i = 0; $i < count($columns); $i++)
	{
		$normalizedByIndex[$i] = normalize_hoadon_header_label($columns[$i]);
	}

	$sorted = array();
	$usedIndexes = array();

	for($p = 0; $p < count($priorityAliases); $p++)
	{
		$aliases = $priorityAliases[$p];
		for($i = 0; $i < count($columns); $i++)
		{
			if(isset($usedIndexes[$i])) continue;
			if(in_array($normalizedByIndex[$i], $aliases, true))
			{
				$sorted[] = $columns[$i];
				$usedIndexes[$i] = 1;
				break;
			}
		}
	}

	for($i = 0; $i < count($columns); $i++)
	{
		if(isset($usedIndexes[$i])) continue;
		$sorted[] = $columns[$i];
	}

	return $sorted;
}

function get_items_hoadon()
{
	global $d, $func, $curPage, $items, $paging, $hoadon_filter_keyword, $hoadon_filter_from_date, $hoadon_filter_to_date, $hoadon_filter_loai, $hoadon_excel_columns;

	$where = "";
	$params = array();
	$hoadon_filter_keyword = '';
	$hoadon_filter_from_date = '';
	$hoadon_filter_to_date = '';
	$hoadon_filter_loai = '';
	$hoadon_excel_columns = array();

	if(isset($_REQUEST['keyword']) && trim($_REQUEST['keyword']) !== '')
	{
		$hoadon_filter_keyword = trim($_REQUEST['keyword']);
		$where .= " and (ma_so_hoa_don like ? or ho_ten_nguoi_mua like ? or chi_tiet_hoa_don like ?)";
		$params[] = '%'.$hoadon_filter_keyword.'%';
		$params[] = '%'.$hoadon_filter_keyword.'%';
		$params[] = '%'.$hoadon_filter_keyword.'%';
	}

	if(isset($_REQUEST['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['from_date']))
	{
		$hoadon_filter_from_date = $_REQUEST['from_date'];
		$where .= " and ngay_hoa_don >= ?";
		$params[] = $hoadon_filter_from_date;
	}

	if(isset($_REQUEST['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['to_date']))
	{
		$hoadon_filter_to_date = $_REQUEST['to_date'];
		$where .= " and ngay_hoa_don <= ?";
		$params[] = $hoadon_filter_to_date;
	}

	if(isset($_REQUEST['loai_hoa_don']))
	{
		$loai = trim((string)$_REQUEST['loai_hoa_don']);
		if(in_array($loai, array('mua_vao', 'ban_ra'), true))
		{
			$hoadon_filter_loai = $loai;
			$where .= " and loai_hoa_don = ?";
			$params[] = $hoadon_filter_loai;
		}
	}

	$per_page = 20;
	$startpoint = ($curPage * $per_page) - $per_page;
	$limit = " limit ".$startpoint.",".$per_page;
	$sql = "select * from #_hoadon where id > 0 $where order by ngay_hoa_don desc, id desc $limit";
	$items = $d->rawQuery($sql, $params);

	if(!empty($items))
	{
		$seen = array();
		for($i = 0; $i < count($items); $i++)
		{
			$info = array();
			if(!empty($items[$i]['thong_tin_hoa_don']))
			{
				$decoded = json_decode($items[$i]['thong_tin_hoa_don'], true);
				if(is_array($decoded)) $info = $decoded;
			}

			if(!empty($info))
			{
				foreach($info as $k => $v)
				{
					$key = trim((string)$k);
					if($key === '') continue;
					if(!isset($seen[$key]))
					{
						$seen[$key] = 1;
						$hoadon_excel_columns[] = $key;
					}
				}
			}
		}
	}

	if(empty($hoadon_excel_columns))
	{
		$hoadon_excel_columns = array('Mã số hóa đơn', 'Họ tên người mua hàng', 'Chi tiết hóa đơn', 'Ngày hóa đơn', 'Tổng tiền');
	}
	else
	{
		$hoadon_excel_columns = hoadon_sort_display_columns($hoadon_excel_columns);
	}

	$sqlNum = "select count(*) as num from #_hoadon where id > 0 $where";
	$count = $d->rawQueryOne($sqlNum, $params);
	$total = isset($count['num']) ? (int)$count['num'] : 0;

	$url = "index.php?com=hoadon&act=man";
	if($hoadon_filter_keyword !== '') $url .= '&keyword='.urlencode($hoadon_filter_keyword);
	if($hoadon_filter_from_date !== '') $url .= '&from_date='.urlencode($hoadon_filter_from_date);
	if($hoadon_filter_to_date !== '') $url .= '&to_date='.urlencode($hoadon_filter_to_date);
	if($hoadon_filter_loai !== '') $url .= '&loai_hoa_don='.urlencode($hoadon_filter_loai);
	$paging = $func->pagination($total, $per_page, $curPage, $url);
}

function uploadExcel_hoadon()
{
	global $d, $func, $login_admin;

	@ini_set('memory_limit', '1024M');
	@ini_set('max_execution_time', '300');
	@set_time_limit(300);

	register_shutdown_function(function() use ($func) {
		$error = error_get_last();
		if(!$error) return;

		$fatalTypes = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR);
		if(!in_array($error['type'], $fatalTypes, true)) return;

		$func->transfer("Import hóa đơn bị lỗi hệ thống. Vui lòng kiểm tra lại file Excel hoặc thử lưu lại file ở định dạng .xlsx rồi import lại.", "index.php?com=hoadon&act=upload", false);
	});

	if(!isset($_FILES['file-excel']))
		$func->transfer("Vui lòng chọn file Excel", "index.php?com=hoadon&act=upload", false);

	if(!isset($_FILES['file-excel']['error']) || (int)$_FILES['file-excel']['error'] !== UPLOAD_ERR_OK)
	{
		$uploadError = isset($_FILES['file-excel']['error']) ? (int)$_FILES['file-excel']['error'] : -1;
		$errorMap = array(
			UPLOAD_ERR_INI_SIZE => 'File vượt quá giới hạn upload của server.',
			UPLOAD_ERR_FORM_SIZE => 'File vượt quá giới hạn cho phép của biểu mẫu.',
			UPLOAD_ERR_PARTIAL => 'File upload chưa hoàn tất, vui lòng thử lại.',
			UPLOAD_ERR_NO_FILE => 'Vui lòng chọn file Excel.',
			UPLOAD_ERR_NO_TMP_DIR => 'Server thiếu thư mục tạm để xử lý upload.',
			UPLOAD_ERR_CANT_WRITE => 'Server không thể ghi file tạm.',
			UPLOAD_ERR_EXTENSION => 'Upload bị chặn bởi extension của server.'
		);
		$msg = isset($errorMap[$uploadError]) ? $errorMap[$uploadError] : 'Upload file thất bại, vui lòng thử lại.';
		$func->transfer($msg, "index.php?com=hoadon&act=upload", false);
	}

	$file = $_FILES['file-excel'];
	$originalName = isset($file['name']) ? trim((string)$file['name']) : '';
	$originalNameLower = strtolower($originalName);
	$loaiHoaDon = '';
	if(strpos($originalNameLower, 'hd_purchased_merged') !== false) $loaiHoaDon = 'mua_vao';
	elseif(strpos($originalNameLower, 'hd_sold_merged') !== false) $loaiHoaDon = 'ban_ra';
	elseif(strpos($originalNameLower, 'purchased') !== false) $loaiHoaDon = 'mua_vao';
	elseif(strpos($originalNameLower, 'sold') !== false) $loaiHoaDon = 'ban_ra';

	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	if(!in_array($ext, array('xls', 'xlsx')))
		$func->transfer("Chỉ hỗ trợ file .xls hoặc .xlsx", "index.php?com=hoadon&act=upload", false);

	require_once LIBRARIES.'PHPExcel.php';

	$inputFileName = $file['tmp_name'];
	if(empty($inputFileName) || !is_file($inputFileName) || !is_readable($inputFileName))
		$func->transfer("Không thể đọc file upload tạm thời. Vui lòng thử lại.", "index.php?com=hoadon&act=upload", false);

	$sheet = null;
	$rowsFallback = array();
	$useFallbackRows = false;

	try
	{
		$objReader = ($ext == 'xlsx') ? PHPExcel_IOFactory::createReader('Excel2007') : PHPExcel_IOFactory::createReader('Excel5');
		$objPHPExcel = $objReader->load($inputFileName);
		$sheet = $objPHPExcel->getSheet(0);
		$highestRow = (int)$sheet->getHighestRow();
		$highestColumn = $sheet->getHighestColumn();
		$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
	}
	catch(Throwable $e)
	{
		if($ext !== 'xlsx')
		{
			$func->transfer("Không thể đọc file Excel. Vui lòng kiểm tra cấu trúc file hoặc lưu lại dưới định dạng .xlsx rồi thử lại.", "index.php?com=hoadon&act=upload", false);
		}

		$rowsFallback = hoadon_parse_xlsx_rows_fallback($inputFileName);
		if($rowsFallback === false || empty($rowsFallback))
		{
			$func->transfer("File Excel không hợp lệ hoặc chứa dữ liệu không tương thích. Vui lòng lưu lại file ở định dạng .xlsx và import lại.", "index.php?com=hoadon&act=upload", false);
		}

		$useFallbackRows = true;
		$highestRow = (int)max(array_keys($rowsFallback));
		$highestColumnIndex = max(5, hoadon_get_max_column_index_from_rows($rowsFallback));
	}

	$maSoAliases = array('masohoadon', 'mahoadon', 'sohoadon', 'invoicecode', 'invoiceno', 'invoiceid');
	$buyerAliases = array('hotennguoimuahang', 'tennguoimuatennguoinhanhang', 'tennguoimuatenguoinhanhang', 'tennguoimuahang', 'hotennguoimua', 'nguoimuahang', 'nguoimua', 'buyername', 'buyer', 'hoten');
	$detailAliases = array('chitiethoadon', 'chitiet', 'noidunghoadon', 'diengiai', 'invoicedetail', 'description');
	$dateAliases = array('ngayhoadon', 'ngaylap', 'ngaymua', 'date', 'invoicedate', 'ngay');
	$totalAliases = array('tongtien', 'tongtienchuathue', 'tongthanhtoan', 'thanhtien', 'tien', 'totalamount', 'total');
	$kyHieuAliases = array('kyhieuhoadon', 'kyhieumauso');
	$sellerNameAliases = array('tennguoibantennguoixuathang', 'tennguoiban', 'nguoiban');
	$sellerTaxAliases = array('mstnguoibanmstnguoixuathang', 'mstnguoiban', 'masothuenguoiban');
	$buyerAddressAliases = array('diachinguoimua', 'diachi');

	$headerMap = array();
	$headerTitleMap = array();
	$headerRow = 1;
	$bestScore = -1;
	$scanToRow = min($highestRow, 50);
	for($scanRow = 1; $scanRow <= $scanToRow; $scanRow++)
	{
		$rowMap = array();
		$rowTitleMap = array();
		for($column = 0; $column < $highestColumnIndex; $column++)
		{
			$headerRaw = $useFallbackRows ? hoadon_get_value_from_rows($rowsFallback, $column, $scanRow) : $sheet->getCellByColumnAndRow($column, $scanRow)->getValue();
			$headerRaw = trim((string)$headerRaw);
			$headerNorm = normalize_hoadon_header_label($headerRaw);
			if($headerNorm !== '' && !isset($rowMap[$headerNorm]))
			{
				$rowMap[$headerNorm] = $column;
				$rowTitleMap[$column] = $headerRaw;
			}
		}

		if(empty($rowMap)) continue;

		$score = 0;
		foreach($maSoAliases as $alias) { if(isset($rowMap[$alias])) { $score += 3; break; } }
		foreach($buyerAliases as $alias) { if(isset($rowMap[$alias])) { $score += 3; break; } }
		foreach($detailAliases as $alias) { if(isset($rowMap[$alias])) { $score += 2; break; } }
		foreach($dateAliases as $alias) { if(isset($rowMap[$alias])) { $score += 1; break; } }
		foreach($totalAliases as $alias) { if(isset($rowMap[$alias])) { $score += 1; break; } }

		if($score > $bestScore)
		{
			$bestScore = $score;
			$headerRow = $scanRow;
			$headerMap = $rowMap;
			$headerTitleMap = $rowTitleMap;
		}
	}

	if(empty($headerMap))
	{
		$headerRow = 1;
		for($column = 0; $column < $highestColumnIndex; $column++)
		{
			$headerRaw = $useFallbackRows ? hoadon_get_value_from_rows($rowsFallback, $column, 1) : $sheet->getCellByColumnAndRow($column, 1)->getValue();
			$headerRaw = trim((string)$headerRaw);
			$headerNorm = normalize_hoadon_header_label($headerRaw);
			if($headerNorm !== '' && !isset($headerMap[$headerNorm]))
			{
				$headerMap[$headerNorm] = $column;
				$headerTitleMap[$column] = $headerRaw;
			}
		}
	}

	$maSoCol = null;
	$kyHieuCol = null;
	$buyerCol = null;
	$detailCol = null;
	$dateCol = null;
	$totalCol = null;
	$sellerNameCol = null;
	$sellerTaxCol = null;
	$buyerAddressCol = null;

	foreach($maSoAliases as $alias) if(isset($headerMap[$alias])) { $maSoCol = $headerMap[$alias]; break; }
	foreach($kyHieuAliases as $alias) if(isset($headerMap[$alias])) { $kyHieuCol = $headerMap[$alias]; break; }
	foreach($buyerAliases as $alias) if(isset($headerMap[$alias])) { $buyerCol = $headerMap[$alias]; break; }
	foreach($detailAliases as $alias) if(isset($headerMap[$alias])) { $detailCol = $headerMap[$alias]; break; }
	foreach($dateAliases as $alias) if(isset($headerMap[$alias])) { $dateCol = $headerMap[$alias]; break; }
	foreach($totalAliases as $alias) if(isset($headerMap[$alias])) { $totalCol = $headerMap[$alias]; break; }
	foreach($sellerNameAliases as $alias) if(isset($headerMap[$alias])) { $sellerNameCol = $headerMap[$alias]; break; }
	foreach($sellerTaxAliases as $alias) if(isset($headerMap[$alias])) { $sellerTaxCol = $headerMap[$alias]; break; }
	foreach($buyerAddressAliases as $alias) if(isset($headerMap[$alias])) { $buyerAddressCol = $headerMap[$alias]; break; }

	if(isset($headerMap['hotennguoimuahang'])) $buyerCol = $headerMap['hotennguoimuahang'];
	if(isset($headerMap['chitiethoadon'])) $detailCol = $headerMap['chitiethoadon'];

	if($maSoCol === null) $maSoCol = 0;
	if($buyerCol === null) $buyerCol = 1;
	if($detailCol === null) $detailCol = 2;
	if($dateCol === null) $dateCol = 3;
	if($totalCol === null) $totalCol = 4;

	$imported = 0;
	$inserted = 0;
	$updated = 0;
	$skipped = 0;
	$failed = 0;
	$firstDbError = '';
	$firstFailedRows = array();
	$username = isset($_SESSION[$login_admin]['username']) ? $_SESSION[$login_admin]['username'] : '';

	for($row = $headerRow + 1; $row <= $highestRow; $row++)
	{
		if($useFallbackRows)
		{
			$maSo = hoadon_get_value_from_rows($rowsFallback, $maSoCol, $row);
			$buyer = hoadon_get_value_from_rows($rowsFallback, $buyerCol, $row);
			$detail = hoadon_get_value_from_rows($rowsFallback, $detailCol, $row);
			$dateRaw = hoadon_get_value_from_rows($rowsFallback, $dateCol, $row);
			$totalRaw = hoadon_get_value_from_rows($rowsFallback, $totalCol, $row);
		}
		else
		{
			$maSo = hoadon_get_cell_string_value($sheet, $maSoCol, $row);
			$buyer = hoadon_get_cell_string_value($sheet, $buyerCol, $row);
			$detail = hoadon_get_cell_string_value($sheet, $detailCol, $row);
			$dateRaw = hoadon_get_cell_string_value($sheet, $dateCol, $row);
			$totalRaw = hoadon_get_cell_string_value($sheet, $totalCol, $row);
		}

		if($kyHieuCol !== null)
		{
			$kyHieu = $useFallbackRows ? hoadon_get_value_from_rows($rowsFallback, $kyHieuCol, $row) : hoadon_get_cell_string_value($sheet, $kyHieuCol, $row);
			if($kyHieu !== '' && $maSo !== '' && stripos($maSo, $kyHieu.'-') !== 0) $maSo = $kyHieu.'-'.$maSo;
		}

		$thongTinHoaDon = array();
		for($column = 0; $column < $highestColumnIndex; $column++)
		{
			$valueCol = $useFallbackRows ? hoadon_get_value_from_rows($rowsFallback, $column, $row) : hoadon_get_cell_string_value($sheet, $column, $row);
			if($valueCol === '') continue;

			$labelCol = isset($headerTitleMap[$column]) ? trim((string)$headerTitleMap[$column]) : '';
			if($labelCol === '') $labelCol = 'Cột '.($column + 1);

			$thongTinHoaDon[$labelCol] = $valueCol;
		}

		$thongTinHoaDonJson = null;
		if(!empty($thongTinHoaDon))
		{
			$encodedThongTin = json_encode($thongTinHoaDon, JSON_UNESCAPED_UNICODE);
			if($encodedThongTin !== false) $thongTinHoaDonJson = $encodedThongTin;
		}

		if($buyer === '' && !empty($thongTinHoaDon))
		{
			foreach($thongTinHoaDon as $label => $value)
			{
				$labelNorm = normalize_hoadon_header_label($label);
				if(!in_array($labelNorm, $buyerAliases, true)) continue;
				$buyerValue = trim((string)$value);
				if($buyerValue !== '')
				{
					$buyer = $buyerValue;
					break;
				}
			}
		}

		if($detail === '' && !empty($thongTinHoaDon))
		{
			foreach($thongTinHoaDon as $label => $value)
			{
				$labelNorm = normalize_hoadon_header_label($label);
				if(!in_array($labelNorm, $detailAliases, true)) continue;
				$detailValue = trim((string)$value);
				if($detailValue !== '')
				{
					$detail = $detailValue;
					break;
				}
			}
		}

		if($detail === '' && !empty($thongTinHoaDon))
		{
			$detailPartsFromRow = array();
			for($di = 0; $di < $highestColumnIndex; $di++)
			{
				$labelCol = isset($headerTitleMap[$di]) ? trim((string)$headerTitleMap[$di]) : '';
				if($labelCol === '') continue;

				$labelNorm = normalize_hoadon_header_label($labelCol);
				if(in_array($labelNorm, $maSoAliases, true) || in_array($labelNorm, $buyerAliases, true) || in_array($labelNorm, $dateAliases, true) || in_array($labelNorm, $totalAliases, true)) continue;

				if(!isset($thongTinHoaDon[$labelCol])) continue;
				$value = trim((string)$thongTinHoaDon[$labelCol]);
				if($value === '') continue;

				$detailPartsFromRow[] = $value;
				if(count($detailPartsFromRow) >= 8) break;
			}

			if(!empty($detailPartsFromRow)) $detail = implode(' | ', $detailPartsFromRow);
		}

		if($detail === '')
		{
			$detailParts = array();
			if($sellerNameCol !== null)
			{
				$sellerName = $useFallbackRows ? hoadon_get_value_from_rows($rowsFallback, $sellerNameCol, $row) : hoadon_get_cell_string_value($sheet, $sellerNameCol, $row);
				if($sellerName !== '') $detailParts[] = 'Người bán: '.$sellerName;
			}
			if($sellerTaxCol !== null)
			{
				$sellerTax = $useFallbackRows ? hoadon_get_value_from_rows($rowsFallback, $sellerTaxCol, $row) : hoadon_get_cell_string_value($sheet, $sellerTaxCol, $row);
				if($sellerTax !== '') $detailParts[] = 'MST người bán: '.$sellerTax;
			}
			if($buyerAddressCol !== null)
			{
				$buyerAddress = $useFallbackRows ? hoadon_get_value_from_rows($rowsFallback, $buyerAddressCol, $row) : hoadon_get_cell_string_value($sheet, $buyerAddressCol, $row);
				if($buyerAddress !== '') $detailParts[] = 'Địa chỉ: '.$buyerAddress;
			}
			if($totalRaw !== '') $detailParts[] = 'Tổng tiền: '.$totalRaw;

			if(!empty($detailParts)) $detail = implode(' | ', $detailParts);
		}

		if($maSo === '' && $buyer === '' && $detail === '') continue;
		if($maSo === '')
		{
			$skipped++;
			continue;
		}

		if($buyer === '') $buyer = '-';
		if($detail === '') $detail = '(không có chi tiết)';

		$ngayHoaDon = parse_hoadon_date($dateRaw);
		$tongTien = parse_hoadon_money($totalRaw);

		$data = array(
			'ma_so_hoa_don' => $maSo,
			'ho_ten_nguoi_mua' => $buyer,
			'chi_tiet_hoa_don' => $detail,
			'loai_hoa_don' => $loaiHoaDon,
			'ngay_hoa_don' => $ngayHoaDon,
			'tong_tien' => $tongTien,
			'thong_tin_hoa_don' => $thongTinHoaDonJson,
			'ngaysua' => time(),
			'user_sua' => $username
		);

		$exists = $d->rawQueryOne("select id from #_hoadon where ma_so_hoa_don = ? limit 0,1", array($maSo));
		if($exists && isset($exists['id']) && (int)$exists['id'] > 0)
		{
			$d->where('id', (int)$exists['id']);
			$okUpdate = $d->update('hoadon', $data);
			if($okUpdate !== false)
			{
				$updated++;
				$imported++;
			}
			else
			{
				$failed++;
				if(count($firstFailedRows) < 5) $firstFailedRows[] = $row;
				$errorInfo = $d->getLastError();
				if($firstDbError === '' && is_array($errorInfo) && isset($errorInfo[2]) && trim((string)$errorInfo[2]) !== '')
				{
					$firstDbError = trim((string)$errorInfo[2]);
				}
			}
		}
		else
		{
			$data['ngaytao'] = time();
			$data['user_tao'] = $username;

			$okInsert = $d->insert('hoadon', $data);
			if($okInsert === false)
			{
				$okInsert = $d->rawQuery(
					"insert into #_hoadon (ma_so_hoa_don, ho_ten_nguoi_mua, chi_tiet_hoa_don, loai_hoa_don, ngay_hoa_don, tong_tien, ngaytao, ngaysua, user_tao, user_sua, thong_tin_hoa_don) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
					array(
						$data['ma_so_hoa_don'],
						$data['ho_ten_nguoi_mua'],
						$data['chi_tiet_hoa_don'],
						$data['loai_hoa_don'],
						$data['ngay_hoa_don'],
						$data['tong_tien'],
						$data['ngaytao'],
						$data['ngaysua'],
						$data['user_tao'],
						$data['user_sua'],
						$data['thong_tin_hoa_don']
					)
				);
			}

			if($okInsert !== false)
			{
				$inserted++;
				$imported++;
			}
			else
			{
				$failed++;
				if(count($firstFailedRows) < 5) $firstFailedRows[] = $row;
				$errorInfo = $d->getLastError();
				if($firstDbError === '' && is_array($errorInfo) && isset($errorInfo[2]) && trim((string)$errorInfo[2]) !== '')
				{
					$firstDbError = trim((string)$errorInfo[2]);
				}
			}
		}
	}

	if($imported <= 0)
	{
		$message = "Không có hóa đơn nào được lưu thành công.";
		if($skipped > 0) $message .= "<br>Bỏ qua $skipped dòng thiếu dữ liệu bắt buộc.";
		if($failed > 0)
		{
			$message .= "<br>Lỗi lưu dữ liệu ở $failed dòng";
			if(!empty($firstFailedRows)) $message .= " (ví dụ dòng: ".implode(', ', $firstFailedRows).")";
			$message .= ".";
		}
		if($firstDbError !== '') $message .= "<br>Chi tiết DB: ".htmlspecialchars($firstDbError);
		$func->transfer($message, "index.php?com=hoadon&act=upload", false);
	}

	$message = "Import thành công $imported hóa đơn (thêm mới: $inserted, cập nhật: $updated).";
	if($skipped > 0) $message .= "<br>Bỏ qua $skipped dòng thiếu dữ liệu bắt buộc.";
	if($failed > 0)
	{
		$message .= "<br>Không lưu được $failed dòng";
		if(!empty($firstFailedRows)) $message .= " (ví dụ dòng: ".implode(', ', $firstFailedRows).")";
		$message .= ".";
		if($firstDbError !== '') $message .= "<br>Chi tiết DB: ".htmlspecialchars($firstDbError);
	}

	$func->transfer($message, "index.php?com=hoadon&act=man");
}

function delete_item_hoadon()
{
	global $d, $func, $curPage;

	$id = (isset($_GET['id'])) ? (int)htmlspecialchars($_GET['id']) : 0;
	$linkRedirect = "index.php?com=hoadon&act=man&p=".$curPage;

	if($id)
	{
		$d->rawQuery("delete from #_hoadon where id = ?", array($id));
		$func->transfer("Xóa dữ liệu thành công", $linkRedirect);
	}
	elseif(isset($_GET['listid']))
	{
		$listid = explode(",", $_GET['listid']);
		for($i = 0; $i < count($listid); $i++)
		{
			$tid = (int)htmlspecialchars($listid[$i]);
			if($tid > 0) $d->rawQuery("delete from #_hoadon where id = ?", array($tid));
		}
		$func->transfer("Xóa dữ liệu thành công", $linkRedirect);
	}
	else
	{
		$func->transfer("Không nhận được dữ liệu", $linkRedirect, false);
	}
}

function delete_all_hoadon()
{
	global $d, $func;

	$count = $d->rawQueryOne("select count(*) as num from #_hoadon");
	$total = isset($count['num']) ? (int)$count['num'] : 0;

	$d->rawQuery("delete from #_hoadon");
	$func->transfer("Đã xóa toàn bộ $total hóa đơn", "index.php?com=hoadon&act=man");
}
