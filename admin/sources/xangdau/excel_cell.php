<?php
if(!defined('SOURCES')) die("Error");

/**
 * Đọc giá trị THÔ (underlying) của ô — cần cho ngày (serial Excel) và tiền (số).
 */
function xd_cell_raw($sheet, $column, $row)
{
	$cell = $sheet->getCellByColumnAndRow($column, $row);
	try { $value = $cell->getCalculatedValue(); }
	catch(Exception $e) { $value = $cell->getValue(); }
	if($value === null) $value = $cell->getValue();
	return $value;
}

/**
 * Ngày từ ô: ưu tiên serial Excel (không phụ thuộc định dạng M/D/Y hay D/M/Y).
 */
function xd_date_from_cell($sheet, $column, $row)
{
	$raw = xd_cell_raw($sheet, $column, $row);
	if(is_numeric($raw) && (float)$raw > 0 && class_exists('PHPExcel_Shared_Date'))
	{
		$ts = PHPExcel_Shared_Date::ExcelToPHP((float)$raw);
		if($ts > 0) return date('Y-m-d', $ts);
	}
	return xd_parse_date((string)$raw);
}

function xd_parse_date($rawValue)
{
	$rawValue = trim((string)$rawValue);
	if($rawValue === '') return null;

	if(is_numeric($rawValue))
	{
		$excelDate = (float)$rawValue;
		if($excelDate > 0 && class_exists('PHPExcel_Shared_Date'))
		{
			$ts = PHPExcel_Shared_Date::ExcelToPHP($excelDate);
			if($ts > 0) return date('Y-m-d', $ts);
		}
	}

	if(preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $rawValue)) return $rawValue;

	// Chuỗi ngày kiểu Việt Nam D/M/Y (vd '13/05/1990') — chấp nhận '/', '-', '.'
	if(preg_match('#^(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{4})$#', $rawValue, $m))
	{
		$day = (int)$m[1]; $mon = (int)$m[2];
		if($day >= 1 && $day <= 31 && $mon >= 1 && $mon <= 12)
			return $m[3].'-'.str_pad($mon, 2, '0', STR_PAD_LEFT).'-'.str_pad($day, 2, '0', STR_PAD_LEFT);
	}

	$tmp = str_replace('.', '/', $rawValue);
	$tmp = preg_replace('/\s+/', ' ', $tmp);
	$ts = strtotime($tmp);
	if($ts !== false) return date('Y-m-d', $ts);

	return null;
}

/**
 * Tiền từ ô. Quy tắc theo dữ liệu thực tế:
 *  - Ô là SỐ (float/int) => giá trị nhập theo đơn vị NGHÌN (vd 608.75 => 608.750đ), nhân 1000.
 *    (guard: số >= 100000 coi như đã đủ đơn vị, không nhân.)
 *  - Ô là CHUỖI có dấu phẩy/chấm ngăn cách (vd "1,000,000") => số đầy đủ.
 */
function xd_money_from_cell($sheet, $column, $row)
{
	$raw = xd_cell_raw($sheet, $column, $row);

	if(is_int($raw) || is_float($raw))
	{
		$val = (float)$raw;
		if($val <= 0) return 0.0;
		if($val < 100000) return round($val * 1000); // đơn vị nghìn
		return round($val);
	}

	return xd_parse_money((string)$raw);
}

function xd_parse_money($rawValue)
{
	$rawValue = trim((string)$rawValue);
	if($rawValue === '') return 0.0;

	$rawValue = str_replace(array('VNĐ', 'VND', 'đ', ' '), '', $rawValue);

	$hasComma = strpos($rawValue, ',') !== false;
	$hasDot = strpos($rawValue, '.') !== false;

	if($hasComma && $hasDot)
	{
		// "1,234,567.89" -> bỏ phẩy (ngăn ngàn), giữ chấm thập phân
		$rawValue = str_replace(',', '', $rawValue);
	}
	elseif($hasComma)
	{
		// "1,000,000" -> phẩy là ngăn ngàn
		$rawValue = str_replace(',', '', $rawValue);
	}
	elseif($hasDot)
	{
		// Chuỗi chỉ có chấm: coi là ngăn ngàn (vd "608.750" -> 608750, "1.000.000" -> 1000000)
		$rawValue = str_replace('.', '', $rawValue);
	}

	if(!is_numeric($rawValue)) return 0.0;
	return round((float)$rawValue);
}

function xd_cell($sheet, $column, $row)
{
	$cell = $sheet->getCellByColumnAndRow($column, $row);
	$value = '';
	try { $value = $cell->getFormattedValue(); }
	catch(Exception $e)
	{
		if(method_exists($cell, 'getOldCalculatedValue')) $value = $cell->getOldCalculatedValue();
		if($value === null || $value === '') $value = $cell->getValue();
	}
	if(is_object($value) && method_exists($value, '__toString')) $value = (string)$value;
	if(is_array($value)) $value = json_encode($value, JSON_UNESCAPED_UNICODE);
	return trim((string)$value);
}

/**
 * Tìm dòng header và map cột theo bảng alias.
 * $containsRules (tùy chọn): field => ['has' => [...substring bắt buộc có...], 'not' => [...substring bị loại...]]
 * dùng làm heuristic bổ sung khi alias chính xác không khớp (mẫu file có header đặt tên khác chuẩn, vd "CCCD_HV", "Ten_HV").
 * @return array [headerRow, map(fieldKey => colIndex), score]
 */
function xd_detect_header($sheet, $highestRow, $highestColIndex, $aliasGroups, $containsRules = array())
{
	$bestScore = -1;
	$bestRow = 1;
	$bestMap = array();
	$scanTo = min($highestRow, 15);

	for($scanRow = 1; $scanRow <= $scanTo; $scanRow++)
	{
		$normByCol = array();
		for($col = 0; $col < $highestColIndex; $col++)
		{
			$norm = xd_norm_header($sheet->getCellByColumnAndRow($col, $scanRow)->getValue());
			if($norm !== '') $normByCol[$col] = $norm;
		}
		if(empty($normByCol)) continue;

		$map = array();
		$score = 0;

		// Đợt 1: khớp chính xác theo alias
		foreach($aliasGroups as $field => $aliases)
		{
			foreach($normByCol as $col => $norm)
			{
				if(in_array($norm, $aliases, true) && !isset($map[$field]))
				{
					$map[$field] = $col;
					$score += 2;
					break;
				}
			}
		}

		// Đợt 2: heuristic chứa chuỗi con cho các field chưa khớp (chỉ xét cột chưa được dùng)
		if(!empty($containsRules))
		{
			$usedCols = array_flip($map);
			foreach($containsRules as $field => $rule)
			{
				if(isset($map[$field])) continue;
				$mustHave = isset($rule['has']) ? $rule['has'] : array();
				$mustNot = isset($rule['not']) ? $rule['not'] : array();

				foreach($normByCol as $col => $norm)
				{
					if(isset($usedCols[$col])) continue;

					$ok = true;
					foreach($mustHave as $h) { if(strpos($norm, $h) === false) { $ok = false; break; } }
					if(!$ok) continue;
					foreach($mustNot as $n) { if(strpos($norm, $n) !== false) { $ok = false; break; } }
					if(!$ok) continue;

					$map[$field] = $col;
					$usedCols[$col] = 1;
					$score++;
					break;
				}
			}
		}

		if($score > $bestScore)
		{
			$bestScore = $score;
			$bestRow = $scanRow;
			$bestMap = $map;
		}
	}

	return array($bestRow, $bestMap, $bestScore);
}

/**
 * Đọc giá trị 1 cột đã map; trả về '' nếu field chưa xác định được cột (thay vì đọc nhầm cột khác).
 */
function xd_val($sheet, $map, $field, $row)
{
	if(!isset($map[$field])) return '';
	return xd_cell($sheet, $map[$field], $row);
}
