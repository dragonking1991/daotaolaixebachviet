<?php
if(!defined('SOURCES')) die('Error');
require_once SOURCES.'daotao/sathach_link.php';

function dt_sathach_summary_date($value, $date1904 = false)
{
	$value = trim((string)$value);
	if($value === '') throw new InvalidArgumentException('Thiếu ngày sát hạch.');
	if(is_numeric($value))
	{
		$serial = (float)$value;
		if(!is_finite($serial) || $serial < 1 || $serial > 2958465) throw new InvalidArgumentException('Ngày Excel không hợp lệ.');
		$calendar = PHPExcel_Shared_Date::getExcelCalendar();
		PHPExcel_Shared_Date::setExcelCalendar(PHPExcel_Shared_Date::CALENDAR_WINDOWS_1900);
		$date = gmdate('Y-m-d', PHPExcel_Shared_Date::ExcelToPHP(floor($serial) + ($date1904 ? 1462 : 0)));
		PHPExcel_Shared_Date::setExcelCalendar($calendar);
		return $date;
	}
	if(preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $parts))
		list($year, $month, $day) = array((int)$parts[1], (int)$parts[2], (int)$parts[3]);
	elseif(preg_match('/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})$/', $value, $parts))
		list($year, $month, $day) = array((int)$parts[3], (int)$parts[2], (int)$parts[1]);
	else throw new InvalidArgumentException('Ngày phải là ngày Excel, dd/mm/yyyy hoặc yyyy-mm-dd.');
	if(!checkdate($month, $day, $year)) throw new InvalidArgumentException('Ngày sát hạch không tồn tại.');
	return sprintf('%04d-%02d-%02d', $year, $month, $day);
}

function dt_sathach_summary_read($file)
{
	require_once LIBRARIES.'PHPExcel.php';
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	$date1904 = false;
	if(in_array($ext, array('xlsx','xlsm'), true))
	{
		require_once SOURCES.'xangdau/helpers.php';
		require_once SOURCES.'xangdau/classes.php';
		require_once SOURCES.'xangdau/excel_convert.php';
		require_once SOURCES.'xangdau/excel_stream.php';
		$zip = new ZipArchive();
		if($zip->open($file['tmp_name']) !== true) throw new RuntimeException('Không đọc được file Excel.');
		$xml = $zip->getFromName('xl/workbook.xml');
		$zip->close();
		$book = $xml === false ? false : @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET);
		if(!$book) throw new RuntimeException('Cấu trúc workbook không hợp lệ.');
		$date1904 = isset($book->workbookPr) && in_array((string)$book->workbookPr['date1904'], array('1','true'), true);
		$identityColumns = array();
		$filter = function($values, $number) use (&$identityColumns) {
			if($number <= 12)
			{
				list($columns, $score) = dt_detect_header($values, array('hoten'=>array('ho','hovaten','hoten'),'cccd'=>array('socmndhc','socccdcc','socccd','cccd','sochungminh','socmnd')));
				if($score === 2) $identityColumns = $columns;
				return true;
			}
			if(!$identityColumns) return false;
			$name = trim((string)($values[$identityColumns['hoten']] ?? ''));
			$identity = trim((string)($values[$identityColumns['cccd']] ?? ''));
			return ($name !== '' && $name !== '0') || ($identity !== '' && $identity !== '0');
		};
		$sheet = xd_stream_read_xlsx($file['tmp_name'], array('filetong','tonghop','sheet1'), $highestRow, $highestCol, 1048576, $filter);
		if(!$sheet) throw new RuntimeException('Không đọc được sheet kết quả tổng hợp.');
		$highestCol--;
	}
	elseif($ext === 'xls')
	{
		list($sheet, $highestRow, $highestCol) = dt_open_upload_sheet($file, $ext, 'index.php?com=daotao&act=uploadSathach', array('file','tong','sheet1'));
		$highestRow=$sheet->getHighestRow();
		$date1904 = PHPExcel_Shared_Date::getExcelCalendar() === PHPExcel_Shared_Date::CALENDAR_MAC_1904;
	}
	else throw new InvalidArgumentException('Chỉ nhận file .xlsx, .xls hoặc .xlsm.');
	$aliases = array(
		'hoten' => array('ho','hovaten','hoten'),
		'cccd' => array('socmndhc','socccdcc','socccd','cccd','sochungminh','socmnd'),
		'giaovien' => array('giaovien','gvhuongdan','gv'),
		'nam_sinh' => array('ngaysinh','namsinh'),
		'hang' => array('hang','hanggplx'),
		'noi_dung_sh' => array('noidungsathach','noidungsh'),
		'lt' => array('lt','ketquamonlythuyet','ketqualythuyet'),
		'mp' => array('mp','ketquamonmophong','ketquamophong'),
		'hinh' => array('th','hinh','ketquamonhinh','ketquahinh'),
		'duong' => array('d','duong','ketquamonduong','ketquaduong'),
		'ket_qua' => array('daurot','ketquasathach','ketqua','ketluan'),
		'ngay_sh' => array('ngaysh','ngaysathach','ngaythi'),
		'ma_khoa' => array('makhoa','makhoahoc'),
		'so_gplx' => array('sogplx','sogiaypheplaixe'),
		'gplx_phu' => array('gplx'),
		'ghi_chu' => array('ghichu'),
	);
	list($headerRow, $map) = dt_find_header_row($sheet, $highestRow, $highestCol, $aliases, array('giaovien'=>array('giaovien')));
	$headers = dt_read_row($sheet, $headerRow, $highestCol);
	if(!isset($map['ngay_sh']) && ($map['hoten'] ?? -1)===1 && ($map['cccd'] ?? -1)===2
		&& ($map['lt'] ?? -1)===6 && ($map['hinh'] ?? -1)===7 && ($map['ket_qua'] ?? -1)===8
		&& dt_norm_header($headers[1] ?? '')==='ho' && dt_norm_header($headers[2] ?? '')==='socmndhc' && trim($headers[9] ?? '')==='') $map['ngay_sh']=9;
	foreach(array('hoten','cccd','hang','lt','hinh','ket_qua','ngay_sh') as $field)
		if(!isset($map[$field])) throw new InvalidArgumentException('File tổng hợp thiếu cột '.$field.'.');
	$rows = array(); $groups = array(); $seen = array(); $records = array(); $duplicates=0;
	for($rowNumber = $headerRow + 1; $rowNumber <= $highestRow; $rowNumber++)
	{
		$row = dt_read_row($sheet, $rowNumber, $highestCol);
		$name = dt_val($row, $map, 'hoten'); $cccdRaw=dt_val($row,$map,'cccd');
		if(($name==='' || $name==='0') && ($cccdRaw==='' || $cccdRaw==='0')) continue;
		$mapped=array(); foreach($map as $field=>$column) $mapped[$field]=dt_val($row,$map,$field);
		$raw=array(); foreach($row as $column=>$value) if($value!=='') $raw[PHPExcel_Cell::stringFromColumnIndex($column).': '.($headers[$column] ?? '')]=$value;
		$recordIndex=count($records);
		$records[]=array('row'=>$rowNumber,'raw'=>$raw,'data'=>$mapped,'date1904'=>$date1904,'status'=>'pending','reason'=>'','identity'=>'');
		try
		{
			$data=dt_sathach_summary_validate($mapped,$date1904);
			$kind=stripos($data['hang'],'A')===0?'moto':'oto';
			if($kind==='oto' && !isset($map['duong'])) throw new InvalidArgumentException('File ô tô thiếu cột Đường.');
			$key=$data['ngay_sh'].'|'.$kind; $identity=$key.'|'.$data['cccd'];
			$records[$recordIndex]['identity']=$identity;
			if(isset($seen[$identity]))
			{
				$index=$seen[$identity]; $duplicates++;
				$rows[$index]['sources'][]=$recordIndex;
				if(!empty($rows[$index]['blocked'])) throw new InvalidArgumentException('Lượt thi trùng đang có dữ liệu mâu thuẫn, cần bổ sung.');
				try { $rows[$index]['data']=dt_sathach_summary_merge($rows[$index]['data'],$data); }
				catch(InvalidArgumentException $error)
				{
					$rows[$index]['blocked']=true;
					foreach($rows[$index]['sources'] as $source){$records[$source]['status']='pending';$records[$source]['reason']=$error->getMessage();}
					throw $error;
				}
				$records[$recordIndex]['status']='merged';
			}
			else
			{
				$seen[$identity]=count($rows);
				$rows[]=array('row'=>$rowNumber,'group'=>$key,'data'=>$data,'sources'=>array($recordIndex));
				$records[$recordIndex]['status']='imported';
			}
		}
		catch(InvalidArgumentException $error){$records[$recordIndex]['status']='pending';$records[$recordIndex]['reason']=$error->getMessage();}
	}
	$accepted=array(); foreach($rows as $row) if(empty($row['blocked'])){$accepted[]=$row;$groups[$row['group']]=array('ngay'=>$row['data']['ngay_sh'],'loai'=>stripos($row['data']['hang'],'A')===0?'moto':'oto');}
	if(!$records) throw new InvalidArgumentException('File không có học viên.');
	ksort($groups);
	return array('rows'=>$accepted,'groups'=>$groups,'records'=>$records,'duplicates'=>$duplicates);
}

function dt_sathach_summary_validate($mapped,$date1904=false)
{
	$name=trim((string)($mapped['hoten'] ?? ''));$cccd=dt_normalize_cccd($mapped['cccd'] ?? '');
	if($name==='' || !in_array(strlen($cccd),array(9,11,12),true)) throw new InvalidArgumentException('Thiếu họ tên hoặc CMND/CCCD không hợp lệ.');
	if(strlen($cccd)===11)$cccd='0'.$cccd;
	$hang=trim((string)($mapped['hang'] ?? ''));if(!preg_match('/^[ABCDEF]/i',$hang))throw new InvalidArgumentException('Hạng xe không xác định: '.$hang);
	$data=array('cccd'=>$cccd,'hoten'=>$name,'hang'=>$hang,'ngay_sh'=>dt_sathach_summary_date($mapped['ngay_sh'] ?? '',$date1904));
	foreach(array('nam_sinh','noi_dung_sh','lt','mp','hinh','duong','ket_qua','ma_khoa','so_gplx','ghi_chu','giaovien') as $field)
	{
		$value=trim((string)($mapped[$field] ?? ''));
		if($value!=='' && !(stripos($hang,'A')===0 && in_array($field,array('mp','duong'),true)))$data[$field]=$value;
	}
	if(empty($data['ket_qua']))throw new InvalidArgumentException('Thiếu kết quả sát hạch.');
	if(isset($data['nam_sinh'])&&is_numeric($data['nam_sinh']))$data['nam_sinh']=implode('/',array_reverse(explode('-',dt_sathach_summary_date($data['nam_sinh'],$date1904))));
	$data['ket_luan']=$data['ket_qua'];
	if(empty($data['so_gplx']) && preg_match('/^\d{10,20}$/',(string)($mapped['gplx_phu'] ?? '')))$data['so_gplx']=$mapped['gplx_phu'];
	foreach(array('hoten'=>255,'hang'=>20,'noi_dung_sh'=>255,'lt'=>100,'mp'=>100,'hinh'=>100,'duong'=>100,'ket_qua'=>255,'ma_khoa'=>100,'so_gplx'=>50,'ghi_chu'=>255,'nam_sinh'=>20,'giaovien'=>255) as $field=>$max)
		if(isset($data[$field]) && mb_strlen($data[$field],'UTF-8')>$max)throw new InvalidArgumentException('Cột '.$field.' vượt độ dài cho phép.');
	return $data;
}

function dt_sathach_summary_merge($first,$next)
{
	foreach($next as $field=>$value)
	{
		if(!isset($first[$field]) || $first[$field]===''){$first[$field]=$value;continue;}
		if(dt_mb_lower(trim((string)$first[$field]))===dt_mb_lower(trim((string)$value)))continue;
		if($field==='ghi_chu'){$first[$field].="\n".$value;if(mb_strlen($first[$field],'UTF-8')>255)throw new InvalidArgumentException('Ghi chú gộp vượt 255 ký tự.');continue;}
		throw new InvalidArgumentException('Lượt thi trùng có dữ liệu mâu thuẫn ở cột '.$field.'.');
	}
	return $first;
}

function dt_sathach_summary_query($sql, $params = array())
{
	global $d;
	$result = $d->rawQuery($sql, $params);
	if($d->getLastErrorCode() !== '00000') throw new RuntimeException('Không thể đọc/ghi dữ liệu sát hạch.');
	return $result;
}

function dt_sathach_summary_import()
{
	$back = 'index.php?com=daotao&act=uploadSathach';
	if(empty($_SESSION['dt_sh_csrf']) || !hash_equals($_SESSION['dt_sh_csrf'], (string)($_POST['csrf'] ?? '')))
		dt_import_notice('Phiên import không hợp lệ; vui lòng tải lại trang.', $back, false);
	$file = $_FILES['file-excel'] ?? array();
	if(!$file || (int)($file['error'] ?? -1) !== UPLOAD_ERR_OK) dt_import_notice('Vui lòng chọn file Excel.', $back, false);
	try
	{
		$parsed = dt_sathach_summary_read($file);
		$hash=hash_file('sha256',$file['tmp_name']);
		if($hash===false)throw new RuntimeException('Không đọc được dấu vết file nguồn.');
		$counts=dt_sh_summary_store($parsed,$file['name'],$hash);
	}
	catch(Exception $error){dt_import_notice($error->getMessage().' Chưa thay đổi dữ liệu.',$back,false);return;}
	dt_audit('import','sat_hach',count($parsed['rows']),'summary '.$file['name'].' pending='.$counts['pending']);
	dt_log_import('sat_hach_tong_hop',$file['name'],count($parsed['rows']),$counts['pending']);
	$message='Đã nhập '.$counts['added'].' kết quả mới, cập nhật '.$counts['updated'].' theo '.$counts['periods'].' kỳ trong mục sát hạch; không tạo hồ sơ Học viên/khóa đào tạo; gộp '.$counts['duplicates'].' dòng trùng; '.$counts['pending'].' dòng lỗi/chờ bổ sung.';
	$url=$counts['pending']>0?'index.php?com=daotao&act=sathachPending&file_hash='.$hash:'index.php?com=daotao&act=sathach&ngay_sh='.$counts['last_date'];
	dt_import_notice($message,$url,count($parsed['rows'])>0);
}