<?php	
	if(!defined('SOURCES')) die("Error");

	/* Kiểm tra active import */
	if(isset($config['product']))
	{
		$arrCheck = array();
		foreach($config['product'] as $k => $v) if(isset($v['import']) && $v['import'] == true) $arrCheck[] = $k;
		if(!count($arrCheck) || !in_array($type,$arrCheck)) $func->transfer("Trang không tồn tại", "index.php", false);
	}
	else
	{
		$func->transfer("Trang không tồn tại", "index.php", false);
	}

	switch($act)
	{
		case "man":
			getImages();
			$template = "import/man/items";
			break;

		case "uploadImages":
			uploadImages();
			break;

		case "editImages":
			editImages();
			$template = "import/man/item_edit";
			break;

		case "saveImages":
			saveImages();
			break;

		case "deleteImages":
			deleteImages();
			break;

		case "uploadExcel":
			uploadExcel();
			break;

		default:
			$template = "404";
	}

	/* Get image */
	function getImages()
	{
		global $d, $func, $type, $curPage, $items, $paging;

		$per_page = 10;
		$startpoint = ($curPage * $per_page) - $per_page;
		$limit = " limit ".$startpoint.",".$per_page;
		$sql = "select * from #_excel where type = ? order by stt,id desc $limit";
		$items = $d->rawQuery($sql,array($type));
		$sqlNum = "select count(*) as 'num' from #_excel where type = ? order by stt,id desc";
		$count = $d->rawQueryOne($sqlNum,array($type));
		$total = $count['num'];
		$url = "index.php?com=import&act=man&type=".$type;
		$paging = $func->pagination($total,$per_page,$curPage,$url);
	}

	/* Edit image */
	function editImages()
	{
		global $d, $func, $item, $type, $curPage;

		$id = (isset($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if(!$id) $func->transfer("Không nhận được dữ liệu", "index.php?com=import&act=man&type=".$type."&p=".$curPage, false);

		$item = $d->rawQueryOne("select * from #_excel where id = ? and type = ? limit 0,1",array($id,$type));

		if(!$item['id']) $func->transfer("Dữ liệu không có thực", "index.php?com=import&act=man&type=".$type."&p=".$curPage, false);
	}

	/* Save image */
	function saveImages()
	{
		global $d, $item, $func, $type, $curPage, $config;

		if(empty($_POST)) $func->transfer("Không nhận được dữ liệu", "index.php?com=import&act=man&type=".$type."&p=".$curPage, false);

		$id = htmlspecialchars($_POST['id']);

		if($id)
		{
			if(isset($_FILES['file']))
			{
				$file_name = $func->uploadName($_FILES['file']["name"]);
				if($photo = $func->uploadImage("file", $config['import']['img_type'], UPLOAD_EXCEL, $file_name))
				{
					$data['photo'] = $photo;
					$row = $d->rawQueryOne("select id, photo from #_excel where id = ? and type = ? limit 0,1",array($id,$type));
					if(isset($row['id']) && $row['id'] > 0) $func->delete_file(UPLOAD_EXCEL.$row['photo']);
					
					$d->where('id', $id);
					$d->where('type', $type);
					if($d->update('excel',$data)) $func->transfer("Cập nhật dữ liệu thành công", "index.php?com=import&act=man&type=".$type."&p=".$curPage);
					else $func->transfer("Cập nhật dữ liệu bị lỗi", "index.php?com=import&act=man&type=".$type."&p=".$curPage, false);
				}
				else
				{
					$func->transfer("Không nhận được hình ảnh mới", "index.php?com=import&act=editImages&id=".$id."&type=".$type."&p=".$curPage, false);
				}
			}
		}
		else
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=import&act=man&type=".$type."&p=".$curPage, false);
		}
	}

	/* Upload image */
	function uploadImages()
	{
		global $d, $type, $func, $config;

		if(isset($_POST['uploadImg']) && isset($_FILES['files'])) 
		{
			$arr_chuoi = '';
			$arr_file_del = array();

			if(isset($_POST['jfiler-items-exclude-files-0']))
			{
				$arr_chuoi = str_replace('"','',$_POST['jfiler-items-exclude-files-0']);
				$arr_chuoi = str_replace('[','',$arr_chuoi);
				$arr_chuoi = str_replace(']','',$arr_chuoi);
				$arr_chuoi = str_replace('\\','',$arr_chuoi);
				$arr_chuoi = str_replace('0://','',$arr_chuoi);
				$arr_file_del = explode(',',$arr_chuoi);
			}

			$dem = 0;
	        $myFile = $_FILES['files'];
	        $fileCount = count($myFile["name"]);

	        for($i=0; $i<$fileCount; $i++) 
	        {
	        	if(!in_array($myFile["name"][$i], $arr_file_del, true))
	        	{
	        		$_FILES['file'] = array('name' => $myFile['name'][$i],'type' => $myFile['type'][$i],'tmp_name' => $myFile['tmp_name'][$i],'error' => $myFile['error'][$i],'size' => $myFile['size'][$i]);
	        		$file_name = $func->uploadName($myFile["name"][$i]);
	        		if($photo = $func->uploadImage("file", $config['import']['img_type'], UPLOAD_EXCEL, $file_name))
	        		{
	        			$data['photo'] = $photo;
	        			$data['stt'] = (isset($_POST['stt-filer'][$dem]) && $_POST['stt-filer'][$dem] > 0) ? (int)$_POST['stt-filer'][$dem] : 0;
	        			$data['type'] = $type;
	        			$d->insert('excel',$data);
	        		}
	        		$dem++;
	        	}
	        }
	        $func->transfer("Lưu hình ảnh thành công", "index.php?com=import&act=man&type=".$type);
	    }
	    else
	    {
	    	$func->transfer("Dữ liệu rỗng", "index.php?com=import&act=man&type=".$type, false);
	    }
	}

	/* Delete image */
	function deleteImages()
	{
		global $d, $type, $func, $curPage;

		$id = (isset($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if($id)
		{
			$row = $d->rawQueryOne("select id, photo from #_excel where id = ? and type = ? limit 0,1",array($id,$type));

			if(isset($row['id']) && $row['id'] > 0)
			{
				$func->delete_file(UPLOAD_EXCEL.$row['photo']);
				$d->rawQuery("delete from #_excel where id = ? and type = ?",array($id,$type));
				$func->transfer("Xóa dữ liệu thành công", "index.php?com=import&act=man&type=".$type."&p=".$curPage);
			}
			else $func->transfer("Xóa dữ liệu bị lỗi", "index.php?com=import&act=man&type=".$type."&p=".$curPage, false);
		}
		elseif(isset($_GET['listid']))
		{
			$listid = explode(",",$_GET['listid']);

			for($i=0;$i<count($listid);$i++)
			{
				$id = htmlspecialchars($listid[$i]);
				$row = $d->rawQueryOne("select id, photo from #_excel where id = ? and type = ? limit 0,1",array($id,$type));

				if(isset($row['id']) && $row['id'] > 0)
				{
					$func->delete_file(UPLOAD_EXCEL.$row['photo']);
					$d->rawQuery("delete from #_excel where id = ? and type = ?",array($id,$type));
				}
			}
			
			$func->transfer("Xóa dữ liệu thành công", "index.php?com=import&act=man&type=".$type."&p=".$curPage);
		} 
		else $func->transfer("Không nhận được dữ liệu", "index.php?com=import&act=man&type=".$type."&p=".$curPage, false);
	}

	/* Transfer image */
	function transferphoto($photo)
	{
		global $d;

		$oldpath = UPLOAD_EXCEL.$photo;
		$newpath = UPLOAD_PRODUCT.$photo;

		if(file_exists($oldpath))
		{
			if(rename($oldpath,$newpath))
			{
				$d->rawQuery("delete from #_excel where photo = ?",array($photo));
			}
		}
	}

	function normalizeImportHeaderLabel($label)
	{
		$label = strtolower(trim((string)$label));
		$search = array('à','á','ả','ã','ạ','ă','ằ','ắ','ẳ','ẵ','ặ','â','ầ','ấ','ẩ','ẫ','ậ','đ','è','é','ẻ','ẽ','ẹ','ê','ề','ế','ể','ễ','ệ','ì','í','ỉ','ĩ','ị','ò','ó','ỏ','õ','ọ','ô','ồ','ố','ổ','ỗ','ộ','ơ','ờ','ớ','ở','ỡ','ợ','ù','ú','ủ','ũ','ụ','ư','ừ','ứ','ử','ữ','ự','ỳ','ý','ỷ','ỹ','ỵ');
		$replace = array('a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','d','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y');
		$label = str_replace($search, $replace, $label);
		return preg_replace('/[^a-z0-9]+/', '', $label);
	}

	function findImportHeaderColumnIndex($worksheet, $highestColumnIndex, $aliases)
	{
		for($column = 0; $column < $highestColumnIndex; $column++)
		{
			$headerValue = $worksheet->getCellByColumnAndRow($column, 1)->getValue();
			$headerValue = normalizeImportHeaderLabel($headerValue);

			if($headerValue !== '' && in_array($headerValue, $aliases, true)) return $column;
		}

		return null;
	}

	function buildQrImageMapFromDrawings($worksheet)
	{
		$map = array();
		foreach($worksheet->getDrawingCollection() as $drawing)
		{
			$coords = $drawing->getCoordinates();
			if(!preg_match('/^([A-Z]+)(\d+)$/', $coords, $matches)) continue;
			if($matches[1] !== 'G') continue;
			$drawingRow = (int)$matches[2];

			$imgData = null;
			if($drawing instanceof PHPExcel_Worksheet_MemoryDrawing)
			{
				ob_start();
				call_user_func($drawing->getRenderingFunction(), $drawing->getImageResource());
				$imgData = ob_get_clean();
			}
			elseif($drawing instanceof PHPExcel_Worksheet_Drawing)
			{
				$path = $drawing->getPath();
				// Handle ZIP archive paths (XLSX files)
				if(!empty($path)) {
					$imgData = @file_get_contents($path);
					if($imgData === false) $imgData = null;
				}
			}

			if(!empty($imgData)) $map[$drawingRow] = $imgData;
		}
		return $map;
	}

	function findImportHeaderMapColumnIndex($headerMap, $aliases)
	{
		foreach($aliases as $alias)
		{
			if(isset($headerMap[$alias])) return $headerMap[$alias];
		}

		return null;
	}

	function getImportCellStringValue($worksheet, $column, $row)
	{
		$cell = $worksheet->getCellByColumnAndRow($column, $row);
		$value = '';

		try
		{
			$value = $cell->getFormattedValue();
		}
		catch(Exception $e)
		{
			/*
			 * Some payroll files contain formulas that PHPExcel cannot evaluate
			 * in this runtime. Fall back to cached/raw cell value instead of
			 * interrupting the entire import process.
			 */
			if(method_exists($cell, 'getOldCalculatedValue')) $value = $cell->getOldCalculatedValue();
			if($value === null || $value === '') $value = $cell->getValue();
		}

		if(is_object($value) && method_exists($value, '__toString')) $value = (string)$value;
		if(is_array($value)) $value = json_encode($value, JSON_UNESCAPED_UNICODE);

		return trim((string)$value);
	}

	function parseImportIntegerValue($rawValue)
	{
		$rawValue = trim((string)$rawValue);
		if($rawValue === '') return null;

		$normalized = str_replace(array("\xc2\xa0", ' '), '', $rawValue);
		if($normalized === '') return null;

		if(preg_match('/^-?\d+$/', $normalized)) return (int)$normalized;

		if(preg_match('/^-?\d{1,3}([.,]\d{3})+$/', $normalized))
		{
			$normalized = str_replace(array('.', ','), '', $normalized);
			return (int)$normalized;
		}

		if(preg_match('/^-?\d+[.,]\d+$/', $normalized))
		{
			$normalized = str_replace(',', '.', $normalized);
			return (int)round((float)$normalized);
		}

		return null;
	}

	function detectEmployeeDepartmentFromRow($rowValues)
	{
		$nonEmptyValues = array();

		foreach($rowValues as $value)
		{
			$value = trim((string)$value);
			if($value !== '') $nonEmptyValues[] = $value;
		}

		if(count($nonEmptyValues) !== 1) return '';

		$department = $nonEmptyValues[0];
		if(stripos(normalizeImportHeaderLabel($department), 'bophan') === 0) return $department;

		return '';
	}

	function isEmployeeDepartmentSummaryRow($employeeName, $employeeOrder)
	{
		$employeeName = trim((string)$employeeName);
		if($employeeName === '') return false;

		$employeeOrder = trim((string)$employeeOrder);
		if($employeeOrder !== '' && is_numeric($employeeOrder)) return false;

		$normalizedName = normalizeImportHeaderLabel($employeeName);
		foreach(array('bophan', 'phongban', 'khoi', 'nhanvien', 'bangiamdoc') as $prefix)
		{
			if(stripos($normalizedName, $prefix) === 0) return true;
		}

		return false;
	}

	function buildEmployeeReferenceCode($worksheetTitle, $employeeOrder, $employeeName)
	{
		$employeeOrder = trim((string)$employeeOrder);
		$employeeName = trim((string)$employeeName);

		$normalizedName = normalizeImportHeaderLabel($employeeName);
		$hashBase = $employeeOrder.'|'.$normalizedName;
		$hash = strtoupper(substr(md5($hashBase), 0, 10));

		if($employeeOrder !== '' && is_numeric($employeeOrder)) return 'NV-'.(int)$employeeOrder.'-'.$hash;

		return 'NV-'.$hash;
	}

	function getEmployeeRowValueByAliases($rowDetail, $aliases)
	{
		if(!is_array($rowDetail) || empty($rowDetail)) return '';

		foreach($aliases as $alias)
		{
			if(isset($rowDetail[$alias]))
			{
				$value = trim((string)$rowDetail[$alias]);
				if($value !== '') return $value;
			}
		}

		return '';
	}

	function detectEmployeeHeaderRow($worksheet, $highestRow, $fallbackHighestColumnIndex)
	{
		$maxScanRows = min((int)$highestRow, 20);
		if($maxScanRows <= 0) return 12;

		$targetAliases = array(
			'stt', 'hovaten', 'hoten', 'ten', 'chucvu', 'songaylamviec', 'luongchinh',
			'thuongletet', 'tiencom', 'phucapxangxe', 'dayltsathach', 'chieusinhtttn',
			'khacdtkhac', 'lamthemgio', 'dienthoai', 'tongthunhap', 'nldnopbhxh105',
			'ttnopbhxh215', 'thunhapchiuthue', 'giamtrugiacanh', 'sonpt', 'nguoiphuthuoc',
			'thunhaptinhthue', 'bac', 'thuetncn', 'luongthucnhan', 'nghiavugv'
		);

		$bestRow = 12;
		$bestScore = 0;

		for($row = 1; $row <= $maxScanRows; $row++)
		{
			$scanColumns = getEmployeeImportColumnIndex($worksheet, $fallbackHighestColumnIndex, $row);
			$headerKeys = array();

			for($column = 0; $column < $scanColumns; $column++)
			{
				$headerKey = normalizeImportHeaderLabel(getImportCellStringValue($worksheet, $column, $row));
				if($headerKey === '') continue;
				$headerKeys[$headerKey] = true;
			}

			if(empty($headerKeys)) continue;

			$score = 0;
			foreach($targetAliases as $alias)
			{
				if(isset($headerKeys[$alias])) $score++;
			}

			if(isset($headerKeys['hovaten']) || isset($headerKeys['hoten']) || isset($headerKeys['ten'])) $score += 5;
			if(isset($headerKeys['chucvu'])) $score += 2;
			if(isset($headerKeys['luongthucnhan'])) $score += 2;

			if($score > $bestScore)
			{
				$bestScore = $score;
				$bestRow = $row;
			}
		}

		if($bestScore <= 0) return 12;

		return $bestRow;
	}

	function getEmployeeImportColumnIndex($worksheet, $fallbackHighestColumnIndex, $headerRow)
	{
		$maxScanColumns = min((int)$fallbackHighestColumnIndex, 1000);
		$lastHeaderColumn = 0;
		$consecutiveEmpty = 0;
		$foundHeader = false;

		for($column = 0; $column < $maxScanColumns; $column++)
		{
			$headerValue = normalizeImportHeaderLabel(getImportCellStringValue($worksheet, $column, $headerRow));
			if($headerValue !== '')
			{
				$foundHeader = true;
				$lastHeaderColumn = $column + 1;
				$consecutiveEmpty = 0;
			}
			elseif($foundHeader)
			{
				$consecutiveEmpty++;
				if($consecutiveEmpty >= 30) break;
			}
		}

		if($lastHeaderColumn <= 0) return min((int)$fallbackHighestColumnIndex, 60);

		return $lastHeaderColumn;
	}

	function detectEmployeeImportSheetTitle($objPHPExcel)
	{
		$sheetNames = array();
		foreach($objPHPExcel->getWorksheetIterator() as $worksheet)
		{
			$title = trim((string)$worksheet->getTitle());
			$sheetNames[] = $title;

			if($title === 'L') return $title;
		}

		$keywords = array('nhanvien', 'luong', 'bangluong', 'payroll', 'employee');
		foreach($objPHPExcel->getWorksheetIterator() as $worksheet)
		{
			$title = trim((string)$worksheet->getTitle());
			$normalizedTitle = normalizeImportHeaderLabel($title);
			foreach($keywords as $keyword)
			{
				if(strpos($normalizedTitle, $keyword) !== false) return $title;
			}

			$highestRow = $worksheet->getHighestRow();
			$highestColumn = $worksheet->getHighestColumn();
			$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
			$headerRow = detectEmployeeHeaderRow($worksheet, $highestRow, $highestColumnIndex);
			$headerMap = array();
			$scanColumns = getEmployeeImportColumnIndex($worksheet, $highestColumnIndex, $headerRow);

			for($column = 0; $column < $scanColumns; $column++)
			{
				$headerKey = normalizeImportHeaderLabel(getImportCellStringValue($worksheet, $column, $headerRow));
				if($headerKey === '') continue;
				if(!isset($headerMap[$headerKey])) $headerMap[$headerKey] = $column;
			}

			$nameColumn = findImportHeaderMapColumnIndex($headerMap, array('hovaten', 'hoten', 'ten'));
			$baseSalaryColumn = findImportHeaderMapColumnIndex($headerMap, array('luongchinh'));
			$totalIncomeColumn = findImportHeaderMapColumnIndex($headerMap, array('tongthunhap'));
			$netSalaryColumn = findImportHeaderMapColumnIndex($headerMap, array('luongthucnhan', 'luongthycnhan'));

			if($nameColumn !== null && ($baseSalaryColumn !== null || $totalIncomeColumn !== null || $netSalaryColumn !== null))
			{
				return $title;
			}
		}

		if(count($sheetNames) > 0) return $sheetNames[0];

		return '';
	}

	/* Lương theo tháng — file 3 sheet VP / GVTH / CTV */
	function payrollMonthlyMatchSheets($sheetNames)
	{
		$config = payrollMonthlySheetConfig();
		$found = array();
		foreach($sheetNames as $name)
		{
			$key = strtoupper(trim((string)$name));
			if(isset($config[$key]) && !isset($found[$key])) $found[$key] = $name;
		}

		return $found;
	}

	function payrollMonthlyCellRaw($worksheet, $column, $row)
	{
		$cell = $worksheet->getCellByColumnAndRow($column, $row);
		$value = $cell->getValue();
		// Công thức tham chiếu sheet khác: dùng giá trị Excel đã tính sẵn trong file
		if(is_string($value) && $value !== '' && $value[0] === '=') $value = $cell->getOldCalculatedValue();
		if(is_object($value) && method_exists($value, '__toString')) $value = (string)$value;

		return $value;
	}

	function payrollMonthlyDetectMonth($worksheet)
	{
		for($row = 1; $row <= 5; $row++)
		{
			for($column = 0; $column < 10; $column++)
			{
				$value = payrollMonthlyCellRaw($worksheet, $column, $row);
				if(!is_string($value)) continue;
				if(preg_match('/th[aá]ng\s*(\d{1,2})\s*(?:n[aă]m|\/|-)\s*(\d{4})/iu', $value, $m) && (int)$m[1] >= 1 && (int)$m[1] <= 12)
				{
					return sprintf('%04d-%02d', (int)$m[2], (int)$m[1]);
				}
			}
		}

		return '';
	}

	function payrollMonthlyParseSheet($worksheet, $sheetConfig, &$errors)
	{
		$sheetTitle = $worksheet->getTitle();
		$highestRow = $worksheet->getHighestRow();
		$highestColumnIndex = min(PHPExcel_Cell::columnIndexFromString($worksheet->getHighestColumn()), 80);

		$headerRow = 0;
		for($row = 1; $row <= min(20, $highestRow); $row++)
		{
			if(payrollMonthlyNorm(payrollMonthlyCellRaw($worksheet, 0, $row)) !== 'stt') continue;
			if(in_array(payrollMonthlyNorm(payrollMonthlyCellRaw($worksheet, 1, $row)), array('hovaten', 'hoten'), true))
			{
				$headerRow = $row;
				break;
			}
		}

		if(!$headerRow)
		{
			$errors[] = 'Sheet '.$sheetTitle.': không tìm thấy dòng tiêu đề (STT | Họ và tên).';
			return array();
		}

		// Tiêu đề gộp nhiều dòng: gom chữ ở 4 dòng tiêu đề của từng cột
		$headerColumns = array();
		for($column = 0; $column < $highestColumnIndex; $column++)
		{
			$keys = array();
			for($row = $headerRow; $row <= $headerRow + 3; $row++)
			{
				$value = payrollMonthlyCellRaw($worksheet, $column, $row);
				if(!is_string($value)) continue;
				$key = payrollMonthlyNorm($value);
				if($key !== '') $keys[$key] = true;
			}
			foreach(array_keys($keys) as $key) $headerColumns[$key][] = $column;
		}

		$columnMap = array();
		foreach($sheetConfig['fields'] as $field)
		{
			$column = null;
			if(isset($field['after']))
			{
				if(isset($columnMap[$field['after']])) $column = $columnMap[$field['after']] + 1;
			}
			else
			{
				$occurrence = isset($field['occurrence']) ? (int)$field['occurrence'] : 1;
				foreach($field['aliases'] as $alias)
				{
					if(isset($headerColumns[$alias][$occurrence - 1]))
					{
						$column = $headerColumns[$alias][$occurrence - 1];
						break;
					}
				}
			}

			if($column === null)
			{
				$errors[] = 'Sheet '.$sheetTitle.': không tìm thấy cột "'.$field['label'].'".';
				continue;
			}
			$columnMap[$field['key']] = $column;
		}

		$cccdColumn = isset($headerColumns['cccd'][0]) ? $headerColumns['cccd'][0] : null;
		$titleColumn = isset($headerColumns['chucvu'][0]) ? $headerColumns['chucvu'][0] : null;
		if($cccdColumn === null)
		{
			$errors[] = 'Sheet '.$sheetTitle.': không có cột CCCD, bỏ qua cả sheet.';
			return array();
		}

		$rows = array();
		for($row = $headerRow + 1; $row <= $highestRow; $row++)
		{
			$order = payrollMonthlyCellRaw($worksheet, 0, $row);
			if(strpos(payrollMonthlyNorm($order), 'tong') === 0) break;

			$name = trim((string)payrollMonthlyCellRaw($worksheet, 1, $row));
			if(!is_numeric($order) || $name === '' || is_numeric($name)) continue;

			$values = array();
			foreach($sheetConfig['fields'] as $field)
			{
				$value = isset($columnMap[$field['key']]) ? payrollMonthlyCellRaw($worksheet, $columnMap[$field['key']], $row) : null;
				if(is_string($value))
				{
					$value = trim($value);
					if($value === '') $value = null;
				}
				if(is_bool($value)) $value = null;
				if(is_int($value) || is_float($value)) $value = ($field['type'] === 'int') ? (int)round($value) : (float)round($value);
				$values[$field['key']] = $value;
			}

			$rows[] = array(
				'row' => $row,
				'stt' => (int)$order,
				'name' => preg_replace('/\s+/u', ' ', $name),
				'cccd' => payrollMonthlyCccd(payrollMonthlyCellRaw($worksheet, $cccdColumn, $row)),
				'chuc_vu' => ($titleColumn !== null) ? trim((string)payrollMonthlyCellRaw($worksheet, $titleColumn, $row)) : '',
				'values' => $values
			);
		}

		return $rows;
	}

	function payrollMonthlyLegacyColumns($sheetConfig, $values)
	{
		$data = array();
		foreach(array('payroll_so_ngay_lam_viec', 'payroll_luong_chinh', 'payroll_thuong_le_tet', 'payroll_tien_com', 'payroll_phu_cap_xang_xe', 'payroll_day_lt_sat_hach', 'payroll_chieu_sinh_tttn', 'payroll_khac_dt_khac', 'payroll_lam_them_gio', 'payroll_dien_thoai', 'payroll_tong_thu_nhap', 'payroll_nld_nop_bhxh_10_5', 'payroll_tt_nop_bhxh_21_5', 'payroll_thu_nhap_chiu_thue', 'payroll_giam_tru_gia_canh', 'payroll_so_npt', 'payroll_nguoi_phu_thuoc', 'payroll_thu_nhap_tinh_thue', 'payroll_bac', 'payroll_thue_tncn', 'payroll_luong_thuc_nhan', 'payroll_nghia_vu_gv') as $column) $data[$column] = '';
		foreach(array('payroll_td', 'payroll_ss', 'payroll_c1', 'payroll_ce') as $column) $data[$column] = null;

		$moneyMap = array(
			'thuc_linh' => 'payroll_luong_thuc_nhan',
			'bhxh' => 'payroll_nld_nop_bhxh_10_5',
			'thue_tncn' => 'payroll_thue_tncn',
			'thu_nhap_danh_nghia' => 'payroll_tong_thu_nhap',
			'tien_com' => 'payroll_tien_com',
			'dien_thoai' => 'payroll_dien_thoai',
			'xang_xe' => 'payroll_phu_cap_xang_xe',
			'them_gio' => 'payroll_lam_them_gio',
			'hoa_hong_chieu_sinh' => 'payroll_chieu_sinh_tttn',
			'npt' => 'payroll_nguoi_phu_thuoc',
			'tntt' => 'payroll_thu_nhap_tinh_thue',
			'luong_cb_pctn' => 'payroll_luong_chinh',
			'luong' => 'payroll_luong_chinh',
			'luong_cb_xang_xe' => 'payroll_nghia_vu_gv'
		);
		foreach($moneyMap as $key => $column)
		{
			if(!isset($values[$key])) continue;
			$data[$column] = is_numeric($values[$key]) ? number_format((float)$values[$key], 0, ',', '.') : htmlspecialchars((string)$values[$key]);
		}
		if(isset($values['btd']) && is_numeric($values['btd'])) $data['payroll_td'] = (int)$values['btd'];
		if(isset($values['ce']) && is_numeric($values['ce'])) $data['payroll_ce'] = (int)$values['ce'];

		return $data;
	}

	function importEmployeePayrollMonthly($objPHPExcel, $matchedSheets, $sourceFileName)
	{
		global $d, $func, $type;

		$backUrl = "index.php?com=import&act=man&type=".$type;
		payrollMonthlyEnsureTable($d);
		$config = payrollMonthlySheetConfig();

		$thang = '';
		$monthInput = isset($_POST['payroll_month']) ? trim((string)$_POST['payroll_month']) : '';
		if($monthInput !== '')
		{
			if(!preg_match('/^(\d{4})-(\d{2})$/', $monthInput, $m) || (int)$m[2] < 1 || (int)$m[2] > 12) $func->transfer("Tháng lương không hợp lệ.", $backUrl, false);
			$thang = $monthInput;
		}
		if($thang === '')
		{
			foreach($matchedSheets as $title)
			{
				$thang = payrollMonthlyDetectMonth($objPHPExcel->getSheetByName($title));
				if($thang !== '') break;
			}
		}
		if($thang === '') $func->transfer("Không xác định được tháng lương trong file (dòng \"Tháng .. năm ....\"). Vui lòng chọn Tháng lương rồi import lại.", $backUrl, false);

		$errors = array();
		$parsed = array();
		foreach($matchedSheets as $key => $title)
		{
			$worksheet = $objPHPExcel->getSheetByName($title);
			if($worksheet) $parsed[$key] = payrollMonthlyParseSheet($worksheet, $config[$key], $errors);
		}

		$byCccd = array();
		$usedReferences = array();
		$employees = $d->rawQuery("select id, tenvi, cccd, ma_tra_cuu, payroll_department from #_product where type = ?", array($type));
		foreach((array)$employees as $employee)
		{
			$cccd = payrollMonthlyCccd($employee['cccd']);
			if($cccd !== '' && !isset($byCccd[$cccd])) $byCccd[$cccd] = $employee;
			if($employee['ma_tra_cuu'] !== '') $usedReferences[$employee['ma_tra_cuu']] = true;
		}

		$teacherCccd = array();
		try
		{
			foreach((array)$d->rawQuery("select cccd from #_dt_giaovien where cccd <> ''") as $teacher) $teacherCccd[payrollMonthlyCccd($teacher['cccd'])] = true;
		}
		catch(Exception $e)
		{
			$teacherCccd = array();
		}

		$counts = array();
		$inserted = 0;
		$updated = 0;
		$teacherLinked = 0;
		$teacherUnlinked = 0;
		$seenCccd = array();
		$dbError = '';

		$d->startTransaction();
		foreach($parsed as $key => $rows)
		{
			$sheetConfig = $config[$key];
			$counts[$key] = 0;

			foreach($rows as $item)
			{
				$where = 'Sheet '.$matchedSheets[$key].' dòng '.$item['row'].' ('.$item['name'].')';
				if($item['cccd'] === '')
				{
					$errors[] = $where.': thiếu CCCD, bỏ qua.';
					continue;
				}
				if(!in_array(strlen($item['cccd']), array(9, 12), true))
				{
					$errors[] = $where.': CCCD "'.$item['cccd'].'" không hợp lệ, bỏ qua.';
					continue;
				}
				if(isset($seenCccd[$item['cccd']]))
				{
					$errors[] = $where.': trùng CCCD với '.$seenCccd[$item['cccd']].', bỏ qua.';
					continue;
				}
				$seenCccd[$item['cccd']] = $where;

				$fields = array();
				foreach($sheetConfig['fields'] as $field)
				{
					$fields[] = array('key' => $field['key'], 'label' => $field['label'], 'type' => $field['type'], 'value' => $item['values'][$field['key']]);
				}

				$productId = 0;
				if($sheetConfig['department'] !== 'van_phong')
				{
					// GVTH/CTV thuộc Đào tạo > Giáo viên: chỉ lưu lương theo CCCD, không tạo nhân viên
					if(isset($teacherCccd[$item['cccd']])) $teacherLinked++;
					else
					{
						$teacherUnlinked++;
						$errors[] = $where.': chưa có trong Đào tạo > Giáo viên (vẫn lưu lương theo CCCD).';
					}
				}
				else
				{
					$existing = isset($byCccd[$item['cccd']]) ? $byCccd[$item['cccd']] : null;

					$productData = array(
						'tenvi' => htmlspecialchars($item['name']),
						'tenkhongdauvi' => $func->changeTitle(htmlspecialchars($item['name'])),
						'cccd' => $item['cccd'],
						'hang' => $sheetConfig['label'],
						'payroll_phong_ban' => $sheetConfig['label'],
						'payroll_department' => $sheetConfig['department']
					);
					if($item['chuc_vu'] !== '') $productData['khoa'] = htmlspecialchars($item['chuc_vu']);

					$isLatest = true;
					if($existing)
					{
						$latest = $d->rawQueryOne("select max(thang) as thang from #_nhanvien_luong where cccd = ?", array($item['cccd']));
						if(!empty($latest['thang']) && $latest['thang'] > $thang) $isLatest = false;
					}

					if($isLatest)
					{
						$productData = array_merge($productData, payrollMonthlyLegacyColumns($sheetConfig, $item['values']));
						$summary = array();
						foreach($fields as $field) $summary[] = array('label' => $field['label'], 'value' => payrollMonthlyFormatValue($field['value'], $field['type']));
						$productData['options2'] = json_encode(array(
							'payroll_monthly' => array('thang' => $thang, 'sheet' => $key, 'fields' => $summary),
							'import_meta' => array('file_name' => $sourceFileName, 'sheet_name' => $matchedSheets[$key], 'imported_at' => time())
						), JSON_UNESCAPED_UNICODE);
					}

					if($existing)
					{
						$productId = (int)$existing['id'];
						$d->where('id', $productId);
						$d->where('type', $type);
						if(!$d->update('product', $productData)) { $dbError = $where.': không cập nhật được nhân viên.'; break 2; }
						$updated++;
					}
					else
					{
						$reference = $item['cccd'];
						if(isset($usedReferences[$reference])) $reference = buildEmployeeReferenceCode($matchedSheets[$key], '', $item['name'].'|'.$key.'|'.$item['row']);
						$productData['ma_tra_cuu'] = $reference;
						$productData['stt'] = $item['stt'];
						$productData['type'] = $type;
						$productData['hienthi'] = 1;
						$productData['ngaytao'] = time();
						$productId = (int)$d->insert('product', $productData);
						if($productId <= 0) { $dbError = $where.': không thêm được nhân viên.'; break 2; }
						$usedReferences[$reference] = true;
						$byCccd[$item['cccd']] = array('id' => $productId);
						$inserted++;
					}
				}

				$record = array(
					'id_product' => $productId,
					'sheet' => $key,
					'bo_phan' => $sheetConfig['department'],
					'hoten' => $item['name'],
					'cccd' => $item['cccd'],
					'chuc_vu' => $item['chuc_vu'],
					'du_lieu' => json_encode($fields, JSON_UNESCAPED_UNICODE),
					'file_name' => $sourceFileName,
					'ngaytao' => time()
				);
				$current = $d->rawQueryOne("select id from #_nhanvien_luong where cccd = ? and thang = ? limit 0,1", array($item['cccd'], $thang));
				if(!empty($current['id']))
				{
					$d->where('id', $current['id']);
					$saved = $d->update('nhanvien_luong', $record);
				}
				else
				{
					$record['cccd'] = $item['cccd'];
					$record['thang'] = $thang;
					$saved = $d->insert('nhanvien_luong', $record);
				}
				if(!$saved) { $dbError = $where.': không lưu được lương tháng.'; break 2; }

				$counts[$key]++;
			}
		}

		if($dbError !== '')
		{
			$d->rollback();
			$func->transfer('Import thất bại, không có dữ liệu nào được lưu.<br>'.htmlspecialchars($dbError), $backUrl, false);
		}
		$d->commit();

		$parts = array();
		foreach($counts as $key => $count) $parts[] = htmlspecialchars($matchedSheets[$key]).': '.$count;
		$message = 'Import lương '.payrollMonthlyLabel($thang).' — '.implode(', ', $parts).'.';
		if(isset($counts['VP'])) $message .= '<br>Văn phòng → Quản lý nhân viên: thêm mới '.$inserted.', cập nhật '.$updated.'.';
		if($teacherLinked + $teacherUnlinked > 0) $message .= '<br>GVTH/CTV → Đào tạo > Giáo viên: khớp '.$teacherLinked.', chưa có hồ sơ '.$teacherUnlinked.'.';
		if(!empty($errors)) $message .= '<br>Cảnh báo:<br>'.implode('<br>', array_map('htmlspecialchars', array_slice($errors, 0, 30)));

		$func->transfer($message, $backUrl, array_sum($counts) > 0);
	}

	/* Upload excel */
	function uploadExcel()
	{
		global $d, $type, $func, $config;

		if(isset($_POST['importExcel']))
		{
			if(!isset($_FILES['file-excel']) || !is_array($_FILES['file-excel']))
			{
				$func->transfer("Không nhận được tập tin upload. Vui lòng chọn lại file Excel.", "index.php?com=import&act=man&type=".$type, false);
			}

			$uploadError = (int)$_FILES['file-excel']['error'];
			if($uploadError !== UPLOAD_ERR_OK)
			{
				$uploadErrors = array(
					UPLOAD_ERR_INI_SIZE => "Tập tin vượt quá giới hạn upload của máy chủ.",
					UPLOAD_ERR_FORM_SIZE => "Tập tin vượt quá giới hạn upload của biểu mẫu.",
					UPLOAD_ERR_PARTIAL => "Tập tin chỉ được tải lên một phần.",
					UPLOAD_ERR_NO_FILE => "Bạn chưa chọn tập tin Excel để import.",
					UPLOAD_ERR_NO_TMP_DIR => "Máy chủ thiếu thư mục tạm để xử lý upload.",
					UPLOAD_ERR_CANT_WRITE => "Máy chủ không thể ghi tập tin upload.",
					UPLOAD_ERR_EXTENSION => "Upload bị chặn bởi extension PHP trên máy chủ."
				);

				$uploadMessage = (isset($uploadErrors[$uploadError])) ? $uploadErrors[$uploadError] : "Upload tập tin thất bại (mã lỗi: ".$uploadError.").";
				$func->transfer($uploadMessage, "index.php?com=import&act=man&type=".$type, false);
			}

			@ini_set('max_execution_time', '300');
			@set_time_limit(300);
			@ini_set('memory_limit', '512M');

			$importType = $type;
			register_shutdown_function(function() use ($importType) {
				$lastError = error_get_last();
				if(!$lastError) return;

				$fatalTypes = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR);
				if(!in_array($lastError['type'], $fatalTypes, true)) return;

				error_log('[uploadExcel]['.$importType.'] '.$lastError['message'].' in '.$lastError['file'].':'.$lastError['line']);

				if(headers_sent()) return;

				$url = 'index.php?com=import&act=man&type='.urlencode($importType);
				echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta http-equiv="refresh" content="1;url='.$url.'"></head><body style="font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;color:#b02a37;">Import thất bại do lỗi hệ thống hoặc file quá lớn. Vui lòng thử lại với file nhẹ hơn.</body></html>';
			});

			$file_type = $_FILES['file-excel']['type'];
			$file_extension = strtolower(pathinfo($_FILES['file-excel']['name'], PATHINFO_EXTENSION));

			if(in_array($file_extension, array('xls', 'xlsx', 'xlsm'), true) || $file_type == "application/vnd.ms-excel" || $file_type == "application/x-ms-excel" || $file_type == "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")
			{
				$mess = '';
				$warning = '';
				$skippedQrRows = array();
				$sourceFileName = $_FILES["file-excel"]["name"];
				$filename = $func->changeTitle($_FILES["file-excel"]["name"]);
				if(!move_uploaded_file($_FILES["file-excel"]["tmp_name"],$filename))
				{
					$func->transfer("Không thể tải file Excel lên máy chủ. Vui lòng thử lại.", "index.php?com=import&act=man&type=".$type, false);
				}
				
				require LIBRARIES.'PHPExcel.php';
				require_once LIBRARIES.'PHPExcel/IOFactory.php';

				if($type == 'nhan-vien')
				{
					require_once LIBRARIES.'payroll_monthly.php';
					$payrollSheets = array();
					try
					{
						$payrollReader = PHPExcel_IOFactory::createReaderForFile($filename);
						$payrollSheets = payrollMonthlyMatchSheets($payrollReader->listWorksheetNames($filename));
					}
					catch(Exception $e)
					{
						$payrollSheets = array();
					}

					if(!empty($payrollSheets))
					{
						try
						{
							$payrollReader->setLoadSheetsOnly(array_values($payrollSheets));
							$payrollBook = $payrollReader->load($filename);
						}
						catch(Exception $e)
						{
							@unlink($filename);
							$func->transfer("Không thể đọc file lương. Vui lòng kiểm tra lại định dạng file (.xls/.xlsx/.xlsm).", "index.php?com=import&act=man&type=".$type, false);
						}
						@unlink($filename);
						importEmployeePayrollMonthly($payrollBook, $payrollSheets, $sourceFileName);
					}
				}

				try
				{
					$objPHPExcel = PHPExcel_IOFactory::load($filename);
				}
				catch(Exception $e)
				{
					@unlink($filename);
					$func->transfer("Không thể đọc file Excel. Vui lòng kiểm tra lại định dạng file (.xls/.xlsx/.xlsm).", "index.php?com=import&act=man&type=".$type, false);
				}

				$employeeImportSheetTitle = '';

				// Task 16: Với nhan-vien, kiểm tra và tự chọn sheet phù hợp trước khi xử lý
				if($type == 'nhan-vien')
				{
					$sheetNames = array();
					foreach($objPHPExcel->getWorksheetIterator() as $_ws) $sheetNames[] = $_ws->getTitle();
					$employeeImportSheetTitle = detectEmployeeImportSheetTitle($objPHPExcel);

					if($employeeImportSheetTitle === '')
					{
						$mess = 'Không tìm thấy sheet nhân viên hợp lệ trong file. Các sheet hiện có: '.htmlspecialchars(implode(', ', $sheetNames)).'. Vui lòng kiểm tra lại file lương (.xlsm/.xlsx).';
						@unlink($filename);
						$func->transfer($mess, "index.php?com=import&act=man&type=".$type, false);
					}
				}
				foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) 
				{
					$worksheetTitle = $worksheet->getTitle();
					// Với nhan-vien chỉ xử lý sheet được tự động chọn
					if($type == 'nhan-vien' && $worksheetTitle !== $employeeImportSheetTitle) continue;

					$highestRow = $worksheet->getHighestRow();
					$highestColumn = $worksheet->getHighestColumn();
					$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
					$qrImageMap = buildQrImageMapFromDrawings($worksheet);

					$nrColumns = ord($highestColumn) - 64;

					if($type == 'nhan-vien')
					{
					// Task 16: Nếu không có dữ liệu nào thì bỏ qua (tránh trường hợp sheet rỗng)
					if($highestRow <= 1) { $mess .= 'Sheet "L" không có dữ liệu.<br>'; continue; }

					$headerRow = detectEmployeeHeaderRow($worksheet, $highestRow, $highestColumnIndex);
						$employeeColumnIndex = getEmployeeImportColumnIndex($worksheet, $highestColumnIndex, $headerRow);
						$dataStartRow = $headerRow + 1;

						$headerMap = array();
						$sourceHeaders = array();

						for($column = 0; $column < $employeeColumnIndex; $column++)
						{
							$headerKey = normalizeImportHeaderLabel(getImportCellStringValue($worksheet, $column, $headerRow));
							if($headerKey === '') continue;

							if(!isset($headerMap[$headerKey])) $headerMap[$headerKey] = $column;
							$sourceHeaders[] = $headerKey;
						}

						$nameColumn = findImportHeaderMapColumnIndex($headerMap, array('hovaten', 'hoten', 'ten'));
						$cccdColumn = findImportHeaderMapColumnIndex($headerMap, array('cccd', 'socccd', 'cancuoc', 'socancuoc'));
						$referenceColumn = findImportHeaderMapColumnIndex($headerMap, array('matracuu', 'mathamchieu', 'masothamchieu', 'sohieu'));
						$departmentColumn = findImportHeaderMapColumnIndex($headerMap, array('bophan', 'phongban', 'donvi'));
						$titleColumn = findImportHeaderMapColumnIndex($headerMap, array('chucvu', 'vitri'));
						$birthDateColumn = findImportHeaderMapColumnIndex($headerMap, array('ngaysinh', 'namsinh'));
						$orderColumn = findImportHeaderMapColumnIndex($headerMap, array('stt'));
						$baseSalaryColumn = findImportHeaderMapColumnIndex($headerMap, array('luongchinh'));
						$totalIncomeColumn = findImportHeaderMapColumnIndex($headerMap, array('tongthunhap'));
						$netSalaryColumn = findImportHeaderMapColumnIndex($headerMap, array('luongthucnhan', 'luongthycnhan'));

						if($nameColumn === null || ($baseSalaryColumn === null && $totalIncomeColumn === null && $netSalaryColumn === null)) continue;

						// Task 19: Cột học viên điều hành (GV)
						$tdColumn = findImportHeaderMapColumnIndex($headerMap, array('td'));
						$ssColumn = findImportHeaderMapColumnIndex($headerMap, array('ss'));
						$c1Column = findImportHeaderMapColumnIndex($headerMap, array('c1'));
						$ceColumn = findImportHeaderMapColumnIndex($headerMap, array('ce'));

						$currentDepartment = '';
						// Task 17: Flag theo dõi đã qua dòng "Bộ phận giáo viên" chưa
						$isGiaoVienSection = false;

						for($row = $dataStartRow; $row <= $highestRow; $row++)
						{
							$rowDetail = array();

							for($column = 0; $column < $employeeColumnIndex; $column++)
							{
								$headerKey = normalizeImportHeaderLabel(getImportCellStringValue($worksheet, $column, $headerRow));
								if($headerKey === '') $headerKey = 'cot'.($column + 1);
								$rowDetail[$headerKey] = getImportCellStringValue($worksheet, $column, $row);
							}

							$departmentFromRow = detectEmployeeDepartmentFromRow($rowDetail);
							if($departmentFromRow !== '')
							{
								$currentDepartment = $departmentFromRow;
								// Task 17: Nhận diện mốc "Bộ phận giáo viên"
								if(!$isGiaoVienSection && stripos(normalizeImportHeaderLabel($departmentFromRow), 'bophangiaovien') !== false) $isGiaoVienSection = true;
								continue;
							}

							$employeeName = ($nameColumn !== null) ? getImportCellStringValue($worksheet, $nameColumn, $row) : '';
							$employeeCccd = ($cccdColumn !== null) ? getImportCellStringValue($worksheet, $cccdColumn, $row) : '';
							$employeeDepartment = ($departmentColumn !== null) ? getImportCellStringValue($worksheet, $departmentColumn, $row) : $currentDepartment;
							$employeeTitle = ($titleColumn !== null) ? getImportCellStringValue($worksheet, $titleColumn, $row) : '';
							$employeeBirthDate = ($birthDateColumn !== null) ? getImportCellStringValue($worksheet, $birthDateColumn, $row) : '';
							$employeeOrder = ($orderColumn !== null) ? getImportCellStringValue($worksheet, $orderColumn, $row) : '';
							$employeeReference = ($referenceColumn !== null) ? getImportCellStringValue($worksheet, $referenceColumn, $row) : '';

							if(isEmployeeDepartmentSummaryRow($employeeName, $employeeOrder))
							{
								$currentDepartment = $employeeName;
								// Task 17: Kiểm tra lần nữa khi phát hiện qua isEmployeeDepartmentSummaryRow
								if(!$isGiaoVienSection && stripos(normalizeImportHeaderLabel($employeeName), 'bophangiaovien') !== false) $isGiaoVienSection = true;
								continue;
							}

							if($employeeName === '') continue;
							if($employeeCccd !== '') $employeeReference = $employeeCccd;
							if($employeeReference === '') $employeeReference = buildEmployeeReferenceCode($worksheetTitle, $employeeOrder, $employeeName);

							// Task 17: Phân loại bộ phận
							$payrollDeptClassification = $isGiaoVienSection ? 'giao_vien' : 'van_phong';

							// Task 19: Đọc cột học viên điều hành (chỉ GV)
							$payrollTd = null;
							$payrollSs = null;
							$payrollC1 = null;
							$payrollCe = null;
							if($isGiaoVienSection)
							{
								$rawTd = ($tdColumn !== null) ? getImportCellStringValue($worksheet, $tdColumn, $row) : getEmployeeRowValueByAliases($rowDetail, array('cot35'));
								$rawSs = ($ssColumn !== null) ? getImportCellStringValue($worksheet, $ssColumn, $row) : getEmployeeRowValueByAliases($rowDetail, array('cot36'));
								$rawC1 = ($c1Column !== null) ? getImportCellStringValue($worksheet, $c1Column, $row) : getEmployeeRowValueByAliases($rowDetail, array('cot37'));
								$rawCe = ($ceColumn !== null) ? getImportCellStringValue($worksheet, $ceColumn, $row) : getEmployeeRowValueByAliases($rowDetail, array('cot38'));

								$parsedTd = parseImportIntegerValue($rawTd);
								$parsedSs = parseImportIntegerValue($rawSs);
								$parsedC1 = parseImportIntegerValue($rawC1);
								$parsedCe = parseImportIntegerValue($rawCe);

								if($parsedTd !== null) $payrollTd = $parsedTd;
								if($parsedSs !== null) $payrollSs = $parsedSs;
								if($parsedC1 !== null) $payrollC1 = $parsedC1;
								if($parsedCe !== null) $payrollCe = $parsedCe;
							}

							$payrollWorkingDays = getEmployeeRowValueByAliases($rowDetail, array('songaylamviec'));
							$payrollBaseSalary = getEmployeeRowValueByAliases($rowDetail, array('luongchinh'));
							$payrollHolidayBonus = getEmployeeRowValueByAliases($rowDetail, array('thuongletet'));
							$payrollMealAllowance = getEmployeeRowValueByAliases($rowDetail, array('tiencom'));
							$payrollFuelAllowance = getEmployeeRowValueByAliases($rowDetail, array('phucapxangxe'));
							$payrollSatHach = getEmployeeRowValueByAliases($rowDetail, array('dayltsathach'));
							$payrollTttn = getEmployeeRowValueByAliases($rowDetail, array('chieusinhtttn'));
							$payrollOther = getEmployeeRowValueByAliases($rowDetail, array('khacdtkhac'));
							$payrollOvertime = getEmployeeRowValueByAliases($rowDetail, array('lamthemgio'));
							$payrollPhone = getEmployeeRowValueByAliases($rowDetail, array('dienthoai', 'ienthoai'));
							$payrollTotalIncome = getEmployeeRowValueByAliases($rowDetail, array('tongthunhap'));
							$payrollNldBhxh = getEmployeeRowValueByAliases($rowDetail, array('nldnopbhxh105'));
							$payrollTtBhxh = getEmployeeRowValueByAliases($rowDetail, array('ttnopbhxh215'));
							$payrollTaxableIncome = getEmployeeRowValueByAliases($rowDetail, array('thunhapchiuthue'));
							$payrollFamilyDeduction = getEmployeeRowValueByAliases($rowDetail, array('giamtrugiacanh'));
							$payrollDependents = getEmployeeRowValueByAliases($rowDetail, array('sonpt'));
							$payrollDependentPeople = getEmployeeRowValueByAliases($rowDetail, array('nguoiphuthuoc'));
							$payrollTaxIncome = getEmployeeRowValueByAliases($rowDetail, array('thunhaptinhthue'));
							$payrollLevel = getEmployeeRowValueByAliases($rowDetail, array('bac'));
							$payrollPersonalTax = getEmployeeRowValueByAliases($rowDetail, array('thuetncn'));
							$payrollNetSalary = getEmployeeRowValueByAliases($rowDetail, array('luongthucnhan', 'luongthycnhan'));
							$payrollTeacherDuty = getEmployeeRowValueByAliases($rowDetail, array('nghiavugv'));

							$hasPayrollSignals = ($payrollBaseSalary !== '' || $payrollTotalIncome !== '' || $payrollNetSalary !== '');
							if(($employeeOrder === '' || !is_numeric($employeeOrder)) && !$hasPayrollSignals) continue;

							$data = array();
							$data['stt'] = (int)$employeeOrder;
							$data['tenvi'] = ($employeeName !== '') ? htmlspecialchars($employeeName) : '';
							$data['tenkhongdauvi'] = ($data['tenvi'] !== '') ? $func->changeTitle($data['tenvi']) : '';
							$data['cccd'] = ($employeeCccd !== '') ? htmlspecialchars($employeeCccd) : '';
							$data['ma_tra_cuu'] = ($employeeReference !== '') ? htmlspecialchars($employeeReference) : '';
							$data['hang'] = ($employeeDepartment !== '') ? htmlspecialchars($employeeDepartment) : '';
							$data['khoa'] = ($employeeTitle !== '') ? htmlspecialchars($employeeTitle) : '';
							$data['ngaysinh'] = ($employeeBirthDate !== '') ? htmlspecialchars($employeeBirthDate) : '';
							$data['payroll_phong_ban'] = ($employeeDepartment !== '') ? htmlspecialchars($employeeDepartment) : '';
							$data['payroll_so_ngay_lam_viec'] = ($payrollWorkingDays !== '') ? htmlspecialchars($payrollWorkingDays) : '';
							$data['payroll_luong_chinh'] = ($payrollBaseSalary !== '') ? htmlspecialchars($payrollBaseSalary) : '';
							$data['payroll_thuong_le_tet'] = ($payrollHolidayBonus !== '') ? htmlspecialchars($payrollHolidayBonus) : '';
							$data['payroll_tien_com'] = ($payrollMealAllowance !== '') ? htmlspecialchars($payrollMealAllowance) : '';
							$data['payroll_phu_cap_xang_xe'] = ($payrollFuelAllowance !== '') ? htmlspecialchars($payrollFuelAllowance) : '';
							$data['payroll_day_lt_sat_hach'] = ($payrollSatHach !== '') ? htmlspecialchars($payrollSatHach) : '';
							$data['payroll_chieu_sinh_tttn'] = ($payrollTttn !== '') ? htmlspecialchars($payrollTttn) : '';
							$data['payroll_khac_dt_khac'] = ($payrollOther !== '') ? htmlspecialchars($payrollOther) : '';
							$data['payroll_lam_them_gio'] = ($payrollOvertime !== '') ? htmlspecialchars($payrollOvertime) : '';
							$data['payroll_dien_thoai'] = ($payrollPhone !== '') ? htmlspecialchars($payrollPhone) : '';
							$data['payroll_tong_thu_nhap'] = ($payrollTotalIncome !== '') ? htmlspecialchars($payrollTotalIncome) : '';
							$data['payroll_nld_nop_bhxh_10_5'] = ($payrollNldBhxh !== '') ? htmlspecialchars($payrollNldBhxh) : '';
							$data['payroll_tt_nop_bhxh_21_5'] = ($payrollTtBhxh !== '') ? htmlspecialchars($payrollTtBhxh) : '';
							$data['payroll_thu_nhap_chiu_thue'] = ($payrollTaxableIncome !== '') ? htmlspecialchars($payrollTaxableIncome) : '';
							$data['payroll_giam_tru_gia_canh'] = ($payrollFamilyDeduction !== '') ? htmlspecialchars($payrollFamilyDeduction) : '';
							$data['payroll_so_npt'] = ($payrollDependents !== '') ? htmlspecialchars($payrollDependents) : '';
							$data['payroll_nguoi_phu_thuoc'] = ($payrollDependentPeople !== '') ? htmlspecialchars($payrollDependentPeople) : '';
							$data['payroll_thu_nhap_tinh_thue'] = ($payrollTaxIncome !== '') ? htmlspecialchars($payrollTaxIncome) : '';
							$data['payroll_bac'] = ($payrollLevel !== '') ? htmlspecialchars($payrollLevel) : '';
							$data['payroll_thue_tncn'] = ($payrollPersonalTax !== '') ? htmlspecialchars($payrollPersonalTax) : '';
							$data['payroll_luong_thuc_nhan'] = ($payrollNetSalary !== '') ? htmlspecialchars($payrollNetSalary) : '';
							$data['payroll_nghia_vu_gv'] = ($payrollTeacherDuty !== '') ? htmlspecialchars($payrollTeacherDuty) : '';
							// Task 17 + 19: Lưu phân loại bộ phận và số học viên
							$data['payroll_department'] = $payrollDeptClassification;
							$data['payroll_td'] = $payrollTd;
							$data['payroll_ss'] = $payrollSs;
							$data['payroll_c1'] = $payrollC1;
							$data['payroll_ce'] = $payrollCe;
							$data['type'] = $type;
							$data['hienthi'] = 1;
							$data['options2'] = json_encode(array(
								'source_headers' => array_values(array_unique($sourceHeaders)),
								'header_row' => $headerRow,
								'detail' => $rowDetail,
								'import_meta' => array(
									'file_name' => $sourceFileName,
									'sheet_name' => $worksheetTitle,
									'imported_at' => time()
								)
							), JSON_UNESCAPED_UNICODE);

							if($employeeCccd !== '')
							{
								$proimport = $d->rawQueryOne("select id, ma_tra_cuu from #_product where cccd = ? and type = ? limit 0,1", array($employeeCccd, $type));

								if(isset($proimport['id']) && $proimport['id'] > 0)
								{
									// CCCD đã tồn tại: chỉ cập nhật dữ liệu lương/thông tin khác, không ghi đè mã tra cứu hiện có.
									if($type == 'nhan-vien') unset($data['ma_tra_cuu']);
									$d->where('type', $type);
									$d->where('cccd', $employeeCccd);
									if(!$d->update('product', $data)) $mess .= 'Lỗi tại dòng: '.$row."<br>";
								}
								else
								{
									if(!$d->insert('product', $data)) $mess .= 'Lỗi tại dòng: '.$row."<br>";
								}
							}
							else
							{
								$proimport = $d->rawQueryOne("select id from #_product where ma_tra_cuu = ? and type = ? limit 0,1", array($employeeReference, $type));

								if(isset($proimport['id']) && $proimport['id'] > 0)
								{
									$d->where('type', $type);
									$d->where('ma_tra_cuu', $employeeReference);
									if(!$d->update('product', $data)) $mess .= 'Lỗi tại dòng: '.$row."<br>";
								}
								else
								{
									if(!$d->insert('product', $data)) $mess .= 'Lỗi tại dòng: '.$row."<br>";
								}
							}
						}

						continue;
					}

					for($row=2;$row<=$highestRow;++$row)
					{
						$cell = $worksheet->getCellByColumnAndRow(3, $row);
						$cccd = $cell->getValue();

						if($cccd!="")
						{
							$cell = $worksheet->getCellByColumnAndRow(0, $row);
							$stt = $cell->getValue();

							$cell = $worksheet->getCellByColumnAndRow(1, $row);
							$tenvi = $cell->getValue();

							$cell = $worksheet->getCellByColumnAndRow(2, $row);
							$ngaysinh = $cell->getValue();

							$cell = $worksheet->getCellByColumnAndRow(3, $row);
							$cccd = $cell->getValue();

							$cell = $worksheet->getCellByColumnAndRow(4, $row);
							if($type!='gplx') $hang = $cell->getValue();
							else $gplx = $cell->getValue();

							$cell = $worksheet->getCellByColumnAndRow(5, $row);
							if($type!='gxn') $gia = $cell->getValue();
							else $khoa = $cell->getValue();

							$cell = $worksheet->getCellByColumnAndRow(6, $row);
							$gxn = $cell->getValue();

/* Find QR image for this row from Excel column G */
								$qrImageData = null;
								for($r = $row; $r <= $row + 6; $r++)
								{
									if(isset($qrImageMap[$r])) { $qrImageData = $qrImageMap[$r]; break; }
							}

							/* Gán dữ liệu */
							$data = array();
							$data['stt'] = (int)$stt;
							$data['tenvi'] = ($tenvi != '') ? htmlspecialchars($tenvi) : '';
							$data['tenkhongdauvi'] = ($data['tenvi'] != '') ? $func->changeTitle($data['tenvi']) : '';
							$data['ngaysinh'] = ($ngaysinh != '') ? htmlspecialchars($ngaysinh) : '';
							$data['cccd'] = ($cccd != '') ? htmlspecialchars($cccd) : '';
							$data['gplx'] = ($gplx != '') ? htmlspecialchars($gplx) : '';

							$data['hang'] = ($hang != '') ? htmlspecialchars($hang) : '';
							$data['khoa'] = ($khoa != '') ? htmlspecialchars($khoa) : '';
							$data['gxn'] = ($gxn != '') ? htmlspecialchars($gxn) : '';

							$data['gia'] = (isset($gia) && $gia != '') ? str_replace(".","",$gia) : 0;


							$data['type'] = $type;
							$data['hienthi'] = 1;

/* Save QR image directly from Excel column G for type 'qr' */
								if($type == 'qr' && $cccd != '')
								{
									if($qrImageData !== null)
									{
										$qr_filename = 'qr-'.$data['cccd'].'.png';
										$qr_filepath = ROOT.'/../upload/product/'.$qr_filename;
										file_put_contents($qr_filepath, $qrImageData);
										$data['photo'] = $qr_filename;
									}
									else
									{
										$skippedQrRows[] = $row;
								}
							}

								$proimport = $d->rawQueryOne("select id from #_product where cccd = ? and type = ? limit 0,1",array($cccd,$type));

							if(isset($proimport['id']) && $proimport['id'] > 0)
							{
								$d->where('type', $type);
								$d->where('cccd', $cccd);
								if($d->update('product',$data))
								{
								}
								else
								{
									$mess.='Lỗi tại dòng: '.$row."<br>";
								}
							}
							else
							{
								if($d->insert('product',$data))
								{
								}
								else
								{
									$mess.='Lỗi tại dòng: '.$row."<br>";
								}
							}
						}
					}
				}

				if(count($skippedQrRows) > 0)
				{
					$warning = 'Cảnh báo: Bỏ qua lưu QR tại các dòng không có ảnh QR: '.implode(', ', $skippedQrRows)."<br>";
				}

				/* Xóa tập tin sau khi đã import xong */
				unlink($filename);

				/* Kiểm tra kết quả import */
				if($mess == '')
				{
					$mess = "Import danh sách thành công";
					if($warning != '') $mess .= "<br>".$warning;
					$func->transfer($mess, "index.php?com=import&act=man&type=".$type);
				}
				else
				{
					if($warning != '') $mess .= $warning;
					$func->transfer($mess, "index.php?com=import&act=man&type=".$type, false);
				}
			}
			else
			{
				$mess = "Không hỗ trợ kiểu tập tin này";
				$func->transfer($mess, "index.php?com=import&act=man&type=".$type, false);
			}
		}
		else
		{
			$func->transfer("Dữ liệu rỗng", "index.php?com=import&act=man&type=".$type, false);
		}
	}
?>