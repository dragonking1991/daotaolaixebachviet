<?php
if(!defined('SOURCES')) die('Error');

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
		$sheet = xd_stream_read_xlsx($file['tmp_name'], array('filetong','tonghop','sheet1'), $highestRow, $highestCol, 20001);
		if(!$sheet) throw new RuntimeException('Không đọc được sheet kết quả tổng hợp.');
		$highestCol--;
		if($highestRow > 20000) throw new RuntimeException('File vượt quá 20.000 dòng; vui lòng chia nhỏ trước khi import.');
	}
	elseif($ext === 'xls')
	{
		list($sheet, $highestRow, $highestCol) = dt_open_upload_sheet($file, $ext, 'index.php?com=daotao&act=uploadSathach', array('file','tong','sheet1'));
		$date1904 = PHPExcel_Shared_Date::getExcelCalendar() === PHPExcel_Shared_Date::CALENDAR_MAC_1904;
	}
	else throw new InvalidArgumentException('Chỉ nhận file .xlsx, .xls hoặc .xlsm.');
	$aliases = array(
		'hoten' => array('hovaten','hoten'),
		'cccd' => array('socccdcc','socccd','cccd','sochungminh','socmnd'),
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
	list($headerRow, $map) = dt_find_header_row($sheet, $highestRow, $highestCol, $aliases, array());
	foreach(array('hoten','cccd','hang','lt','hinh','ket_qua','ngay_sh') as $field)
		if(!isset($map[$field])) throw new InvalidArgumentException('File tổng hợp thiếu cột '.$field.'.');
	$rows = array(); $groups = array(); $seen = array();
	for($rowNumber = $headerRow + 1; $rowNumber <= $highestRow; $rowNumber++)
	{
		$row = dt_read_row($sheet, $rowNumber, $highestCol);
		$name = dt_val($row, $map, 'hoten');
		$cccd = dt_normalize_cccd(dt_val($row, $map, 'cccd'));
		if($name === '' && $cccd === '') continue;
		try
		{
			if($name === '' || !in_array(strlen($cccd), array(9,11,12), true)) throw new InvalidArgumentException('Thiếu họ tên hoặc CCCD không hợp lệ.');
			if(strlen($cccd) === 11) $cccd = '0'.$cccd;
			$date = dt_sathach_summary_date(dt_val($row, $map, 'ngay_sh'), $date1904);
			$hang = dt_val($row, $map, 'hang');
			if(!preg_match('/^[ABCDEF]/i', $hang)) throw new InvalidArgumentException('Hạng xe không xác định.');
			$kind = stripos($hang, 'A') === 0 ? 'moto' : 'oto';
			if($kind === 'oto' && !isset($map['duong'])) throw new InvalidArgumentException('File ô tô thiếu cột Đường.');
			$key = $date.'|'.$kind;
			$identity = $key.'|'.$cccd;
			if(isset($seen[$identity])) throw new InvalidArgumentException('Trùng CCCD và ngày thi với dòng '.$seen[$identity].'.');
			$seen[$identity] = $rowNumber;
			$data = array('cccd'=>$cccd,'hoten'=>$name,'hang'=>$hang,'ngay_sh'=>$date);
			foreach(array('nam_sinh','noi_dung_sh','lt','mp','hinh','duong','ket_qua','ma_khoa','so_gplx','ghi_chu') as $field)
			{
				$value = dt_val($row, $map, $field);
				if($value !== '' && !($kind === 'moto' && in_array($field, array('mp','duong'), true))) $data[$field] = $value;
			}
			if(empty($data['ket_qua'])) throw new InvalidArgumentException('Thiếu kết quả sát hạch.');
			$data['ket_luan'] = $data['ket_qua'];
			if(empty($data['so_gplx']))
			{
				$licence = dt_val($row, $map, 'gplx_phu');
				if(preg_match('/^\d{10,20}$/', $licence)) $data['so_gplx'] = $licence;
			}
			foreach(array('hoten'=>255,'hang'=>20,'noi_dung_sh'=>255,'lt'=>100,'mp'=>100,'hinh'=>100,'duong'=>100,'ket_qua'=>255,'ma_khoa'=>100,'so_gplx'=>50,'ghi_chu'=>255,'nam_sinh'=>20) as $field=>$max)
				if(isset($data[$field]) && mb_strlen($data[$field], 'UTF-8') > $max) throw new InvalidArgumentException('Cột '.$field.' vượt độ dài cho phép.');
			$groups[$key] = array('ngay'=>$date,'loai'=>$kind);
			$rows[] = array('row'=>$rowNumber,'group'=>$key,'data'=>$data);
		}
		catch(InvalidArgumentException $error) { throw new InvalidArgumentException('Dòng '.$rowNumber.': '.$error->getMessage()); }
	}
	if(!$rows) throw new InvalidArgumentException('File không có kết quả hợp lệ.');
	ksort($groups);
	return array('rows'=>$rows,'groups'=>$groups);
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
	global $d;
	$back = 'index.php?com=daotao&act=uploadSathach&import_mode=summary';
	if(empty($_SESSION['dt_sh_csrf']) || !hash_equals($_SESSION['dt_sh_csrf'], (string)($_POST['csrf'] ?? '')))
		dt_import_notice('Phiên import không hợp lệ; vui lòng tải lại trang.', $back, false);
	$file = $_FILES['file-excel'] ?? array();
	if(!$file || (int)($file['error'] ?? -1) !== UPLOAD_ERR_OK) dt_import_notice('Vui lòng chọn file Excel.', $back, false);
	$courseId = (int)($_POST['id_khoa'] ?? 0);
	try
	{
		$parsed = dt_sathach_summary_read($file);
		if($courseId && !dt_sathach_summary_query('select id from #_dt_khoa where id=?', array($courseId))) throw new InvalidArgumentException('Khóa được chọn không tồn tại.');
		$identities = array();
		foreach($parsed['rows'] as $row) $identities[$row['data']['cccd']] = true;
		$variants = array();
		foreach(array_keys($identities) as $cccd) foreach(dt_cccd_variants((string)$cccd) as $variant) $variants[$variant] = true;
		$students = array();
		foreach(array_chunk(array_keys($variants), 500) as $chunk)
		{
			$params = array_map('strval', $chunk);
			$where = 'cccd in ('.implode(',', array_fill(0, count($chunk), '?')).')';
			if($courseId) { $where .= ' and id_khoa=?'; $params[] = $courseId; }
			$matches = dt_sathach_summary_query('select id_khoa,ma_hv,cccd,gv_hoten from #_dt_hocvien where '.$where.' order by id desc', $params);
			foreach($matches as $match)
			{
				$key = dt_normalize_cccd($match['cccd']);
				if(strlen($key) === 11) $key = '0'.$key;
				if(!isset($students[$key])) $students[$key] = $match;
			}
		}
	}
	catch(Exception $error) { dt_import_notice($error->getMessage().' Chưa thay đổi dữ liệu.', $back, false); }
	$locked = false; $transaction = false; $errorMessage = ''; $periodIds = array();
	$added = 0; $updated = 0; $created = 0;
	try
	{
		$lock = dt_sathach_summary_query("select get_lock('bachviet_sh_summary_import',10) as acquired");
		if((int)($lock[0]['acquired'] ?? 0) !== 1) throw new RuntimeException('Có lượt import tổng hợp khác đang chạy; vui lòng thử lại.');
		$locked = true;
		if(!dt_backup_tables(array('dt_sat_hach','dt_sh_ky'), 'imp_sh_summary')) throw new RuntimeException('Không sao lưu được dữ liệu trước import.');
		$d->startTransaction(); $transaction = true;
		foreach($parsed['groups'] as $key => $group)
		{
			$periods = dt_sathach_summary_query('select id from #_dt_sh_ky where ngay=? and loai=? order by id limit 2 for update', array($group['ngay'],$group['loai']));
			if(count($periods) > 1) throw new RuntimeException('Ngày '.$group['ngay'].' có nhiều kỳ '.($group['loai']==='moto'?'mô tô':'ô tô').'; cần xác định kỳ trước khi import.');
			if($periods) $periodIds[$key] = (int)$periods[0]['id'];
			else
			{
				$name = ($group['loai']==='moto'?'Mô tô':'Ô tô').' - '.$group['ngay'].' (tổng hợp)';
				dt_sathach_summary_query('insert into #_dt_sh_ky (ten,ngay,loai,nguoi_nhap,ngaytao) values (?,?,?,?,?)', array($name,$group['ngay'],$group['loai'],dt_username(),time()));
				$periodIds[$key] = (int)$d->getLastInsertId();
				$created++;
			}
		}
		$ids = array_values($periodIds);
		$existing = dt_sathach_summary_query('select id,id_ky,cccd from #_dt_sat_hach where id_ky in ('.implode(',', array_fill(0, count($ids), '?')).') for update', $ids);
		$byIdentity = array();
		foreach($existing as $record)
		{
			$cccd = dt_normalize_cccd($record['cccd']);
			if(strlen($cccd) === 11) $cccd = '0'.$cccd;
			$key = $record['id_ky'].'|'.$cccd;
			if(isset($byIdentity[$key])) throw new RuntimeException('Kỳ '.$record['id_ky'].' có nhiều hồ sơ cùng CCCD '.$cccd.'.');
			$byIdentity[$key] = (int)$record['id'];
		}
		foreach($parsed['rows'] as $row)
		{
			$data = $row['data'];
			$data['id_ky'] = $periodIds[$row['group']];
			$data['nguoi_nhap'] = dt_username();
			$key = $data['id_ky'].'|'.$data['cccd'];
			if(isset($byIdentity[$key]))
			{
				$assignments = array();
				foreach(array_keys($data) as $field) $assignments[] = '`'.$field.'`=?';
				dt_sathach_summary_query('update #_dt_sat_hach set '.implode(',', $assignments).' where id=?', array_merge(array_values($data), array($byIdentity[$key])));
				$updated++;
			}
			else
			{
				$student = $students[$data['cccd']] ?? null;
				$data['id_khoa'] = $student ? (int)$student['id_khoa'] : $courseId;
				if($student) { $data['ma_hv']=$student['ma_hv']; $data['giaovien']=$student['gv_hoten']; }
				$data['ngaytao'] = time();
				dt_sathach_summary_query('insert into #_dt_sat_hach (`'.implode('`,`', array_keys($data)).'`) values ('.implode(',', array_fill(0, count($data), '?')).')', array_values($data));
				$added++;
			}
		}
		if($d->commit() === false) throw new RuntimeException('Không thể hoàn tất import.');
		$transaction = false;
	}
	catch(Exception $error)
	{
		if($transaction) $d->rollback();
		$errorMessage = $error->getMessage();
	}
	finally
	{
		if($locked) $d->rawQuery("select release_lock('bachviet_sh_summary_import')");
	}
	if($errorMessage !== '') dt_import_notice($errorMessage.' Chưa thay đổi dữ liệu.', $back, false);
	$dates = array_column($parsed['groups'], 'ngay');
	$lastDate = max($dates);
	dt_audit('import','sat_hach',count($parsed['rows']),'summary '.$file['name'].' periods='.implode(',', $ids));
	dt_log_import('sat_hach_tong_hop',$file['name'],count($parsed['rows']),0);
	dt_import_notice('Đã import tổng hợp '.count($parsed['rows']).' học viên theo '.count($periodIds).' kỳ: tạo '.$created.' kỳ, thêm '.$added.', cập nhật '.$updated.'.', 'index.php?com=daotao&act=sathach&ngay_sh='.$lastDate, true);
}