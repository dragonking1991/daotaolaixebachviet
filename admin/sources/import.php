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
		$value = $worksheet->getCellByColumnAndRow($column, $row)->getFormattedValue();
		if(is_object($value) && method_exists($value, '__toString')) $value = (string)$value;
		return trim((string)$value);
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

	/* Upload excel */
	function uploadExcel()
	{
		global $d, $type, $func, $config;

		if(isset($_POST['importExcel']))
		{
			$file_type = $_FILES['file-excel']['type'];
			$file_extension = strtolower(pathinfo($_FILES['file-excel']['name'], PATHINFO_EXTENSION));

			if(in_array($file_extension, array('xls', 'xlsx', 'xlsm'), true) || $file_type == "application/vnd.ms-excel" || $file_type == "application/x-ms-excel" || $file_type == "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")
			{
				$mess = '';
				$warning = '';
				$skippedQrRows = array();
				$sourceFileName = $_FILES["file-excel"]["name"];
				$filename = $func->changeTitle($_FILES["file-excel"]["name"]);
				move_uploaded_file($_FILES["file-excel"]["tmp_name"],$filename);			
				
				require LIBRARIES.'PHPExcel.php';
				require_once LIBRARIES.'PHPExcel/IOFactory.php';

				$objPHPExcel = PHPExcel_IOFactory::load($filename);

				foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) 
				{
					$worksheetTitle = $worksheet->getTitle();
					$highestRow = $worksheet->getHighestRow();
					$highestColumn = $worksheet->getHighestColumn();
					$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
					$qrImageMap = buildQrImageMapFromDrawings($worksheet);

					$nrColumns = ord($highestColumn) - 64;

					if($type == 'nhan-vien')
					{
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

						$currentDepartment = '';

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
								continue;
							}

							if($employeeName === '') continue;
							if($employeeCccd !== '') $employeeReference = $employeeCccd;
							if($employeeReference === '') $employeeReference = buildEmployeeReferenceCode($worksheetTitle, $employeeOrder, $employeeName);

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
								$proimport = $d->rawQueryOne("select id from #_product where cccd = ? and type = ? limit 0,1", array($employeeCccd, $type));

								if(isset($proimport['id']) && $proimport['id'] > 0)
								{
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