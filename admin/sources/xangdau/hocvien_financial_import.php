<?php
if(!defined('SOURCES')) die("Error");

function xd_student_financial_fields()
{
	return array(
		'hoc_phi' => 'Số tiền học phí',
		'mien_giam' => 'Miễn giảm',
		'hoc_phi_da_dong' => 'Số tiền đã đóng',
		'hoc_phi_con_thieu' => 'Số tiền còn thiếu',
		'tam_ung_nguon' => 'Số tiền tạm ứng',
		'thanh_toan_luong_nguon' => 'Thanh toán Lương',
	);
}

function xd_roster_query($sql, $params = array())
{
	global $d;
	$result = $d->rawQuery($sql, $params);
	if($result === false || $d->getLastErrorCode() !== '00000')
		throw new RuntimeException('Không thể đọc/ghi dữ liệu import học viên XD.');
	return $result;
}

function xd_roster_value($sheet, $column, $row)
{
	$cell = $sheet->getCellByColumnAndRow($column, $row);
	$value = $cell->getValue();
	if(is_string($value) && isset($value[0]) && $value[0] === '=')
	{
		$value = method_exists($cell, 'getOldCalculatedValue') ? $cell->getOldCalculatedValue() : null;
		if($value === null) throw new InvalidArgumentException('Công thức chưa có giá trị lưu sẵn; vui lòng tính lại và lưu file bằng Excel.');
	}
	if(is_string($value) && preg_match('/^#(REF!|VALUE!|N\/A|DIV\/0!|NAME\?|NUM!|NULL!)/', $value))
		throw new InvalidArgumentException('Ô có lỗi Excel; vui lòng kiểm tra và lưu lại file.');
	return $value === null ? '' : trim((string)$value);
}

function xd_roster_amount($value)
{
	$value = preg_replace('/[\s\x{00A0}]+/u', '', (string)$value);
	if($value === '') return 0;
	if(preg_match('/^\d{1,3}([.,]\d{3})+$/', $value)) $value = str_replace(array('.', ','), '', $value);
	if(!preg_match('/^\d+(?:\.0+)?$/', $value) || (float)$value > 9999999999999)
		throw new InvalidArgumentException('Số tiền phải là số nguyên đồng không âm, tối đa 13 chữ số.');
	return (int)$value;
}

function xd_roster_submission_date($value)
{
	$value = trim((string)$value);
	if($value === '') return null;
	if(is_numeric($value) && (float)$value >= 1 && (float)$value < 2958466)
	{
		$value = PHPExcel_Shared_Date::ExcelToPHPObject(floor((float)$value))->format('Y-m-d');
	}
	if(preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts))
	{
		$year = (int)$parts[1]; $month = (int)$parts[2]; $day = (int)$parts[3];
	}
	elseif(preg_match('#^(\d{1,2})([/-])(\d{1,2})\2(\d{4})$#', $value, $parts))
	{
		$day = (int)$parts[1]; $month = (int)$parts[3]; $year = (int)$parts[4];
	}
	else throw new InvalidArgumentException('Dùng ngày dd-mm-yyyy, dd/mm/yyyy, yyyy-mm-dd hoặc ngày Excel hợp lệ.');
	if($year < 1000 || !checkdate($month, $day, $year))
		throw new InvalidArgumentException('Ngày không tồn tại hoặc ngoài phạm vi lưu trữ.');
	return sprintf('%04d-%02d-%02d', $year, $month, $day);
}

function xd_roster_header($sheet, $highestRow, $highestCol)
{
	$aliases = array(
		'ten' => array('hovaten', 'hoten', 'tenhocvien'),
		'cccd' => array('socmndhc', 'socmndhochieu', 'socccd', 'cccd', 'cmnd', 'socccdcmnd'),
		'ngaysinh' => array('ngaysinh', 'ngaythangnamsinh'),
		'khoa' => array('khoa', 'makhoa', 'khoahoc'),
		'nhom' => array('hedaotao', 'nhom', 'ghichu'),
		'gv' => array('giaovien', 'phanxe', 'gvphutrach'),
		'nguoinop' => array('nguoinophoso', 'nguoinop'),
		'ngay_nop_ho_so' => array('ngaynophoso'),
		'hoc_phi' => array('sotienhocphi', 'hocphi'),
		'mien_giam' => array('miengiam'),
		'hoc_phi_da_dong' => array('sotiendadong', 'hocphidadong'),
		'hoc_phi_con_thieu' => array('sotienconthieu', 'hocphiconthieu'),
		'tam_ung_nguon' => array('sotientamung'),
		'thanh_toan_luong_nguon' => array('thanhtoanluong'),
	);
	return xd_detect_header($sheet, $highestRow, $highestCol, $aliases);
}

function xd_roster_parse($sheet, $highestRow, $map, $headerRow, $defaults = array())
{
	if(!isset($map['ten'], $map['cccd']))
		throw new InvalidArgumentException('Không nhận diện được cột Họ và tên và Số CMND/HC hoặc CCCD.');
	$rows = array(); $errors = array(); $seen = array();
	for($r = $headerRow + 1; $r <= $highestRow; $r++)
	{
		try
		{
			$read = function($key) use ($sheet, $map, $r) {
				return isset($map[$key]) ? xd_roster_value($sheet, $map[$key], $r) : '';
			};
			$name = $read('ten'); $rawIdentity = $read('cccd');
			if($name === '' && $rawIdentity === '') continue;
			$identity = xd_normalize_cccd($rawIdentity);
			if($name === '' || !preg_match('/^\d{9,12}$/', $identity))
				throw new InvalidArgumentException('Thiếu họ tên hoặc số CMND/CCCD không hợp lệ (9–12 chữ số).');
			foreach(xd_cccd_variants($identity) as $variant)
			{
				if(isset($seen[$variant])) throw new InvalidArgumentException('CMND/CCCD trùng với dòng '.$seen[$variant].'.');
			}
			foreach(xd_cccd_variants($identity) as $variant) $seen[$variant] = $r;
			$course = $read('khoa');
			if($course === '') $course = trim((string)($defaults['khoa'] ?? ''));
			$group = strtoupper($read('nhom'));
			$groupFromFile = $group !== '';
			if($group === '') $group = strtoupper(trim((string)($defaults['nhom'] ?? '')));
			$dateRaw = $read('ngaysinh');
			$date = $dateRaw === '' ? '' : xd_parse_date($dateRaw);
			if($dateRaw !== '' && $date === null) throw new InvalidArgumentException('Ngày sinh không hợp lệ.');
			$row = array('row'=>$r, 'ho_ten'=>$name, 'cccd'=>$identity, 'ngaysinh'=>$date,
				'khoa'=>$course, 'nhom'=>$group, 'nhom_tu_file'=>$groupFromFile,
				'nguoi_nop'=>$read('nguoinop'), 'gv_hoten'=>$read('gv'), 'financial'=>array());
			if(isset($map['ngay_nop_ho_so']))
			{
				try { $row['ngay_nop_ho_so'] = xd_roster_submission_date($read('ngay_nop_ho_so')); }
				catch(InvalidArgumentException $error) { throw new InvalidArgumentException('Ngày nộp hồ sơ: '.$error->getMessage()); }
			}
			foreach(xd_student_financial_fields() as $key => $label)
			{
				if(isset($map[$key]))
				{
					try { $row['financial'][$key] = xd_roster_amount($read($key)); }
					catch(InvalidArgumentException $error) { throw new InvalidArgumentException($label.': '.$error->getMessage()); }
				}
			}
			$rows[] = $row;
		}
		catch(InvalidArgumentException $error) { $errors[] = 'Dòng '.$r.': '.$error->getMessage(); }
	}
	if($errors) throw new InvalidArgumentException(implode("\n", array_slice($errors, 0, 20)));
	if(!$rows) throw new InvalidArgumentException('File không có học viên hợp lệ.');
	return $rows;
}

function xd_roster_plan($rows, $lock = false)
{
	$plan = array('rows'=>array(), 'new'=>0, 'updated'=>0, 'missing_teachers'=>0, 'group_changes'=>0,
		'totals'=>array_fill_keys(array_keys(xd_student_financial_fields()), 0));
	foreach($rows as $row)
	{
		if($row['nhom'] !== '' && !in_array($row['nhom'], array('BT','CK','DAT'), true))
			throw new InvalidArgumentException('Dòng '.$row['row'].': Hệ đào tạo/Nhóm chỉ nhận BT, CK hoặc DAT.');
		$variants = xd_cccd_variants($row['cccd']);
		$marks = implode(',', array_fill(0, count($variants), '?'));
		$matches = xd_roster_query("select * from #_xd_hocvien where cccd in ($marks)".($lock ? ' for update' : ''), $variants);
		if(count($matches) > 1) throw new InvalidArgumentException('Dòng '.$row['row'].': định danh khớp nhiều hồ sơ; chưa import.');
		$existing = $matches ? $matches[0] : null;
		if(!$existing && ($row['khoa'] === '' || mb_strlen($row['khoa'], 'UTF-8') > 100 || !in_array($row['nhom'], array('BT','CK','DAT'), true)))
			throw new InvalidArgumentException('Dòng '.$row['row'].': học viên mới cần khóa XD (tối đa 100 ký tự) và nhóm BT/CK/DAT.');
		if(!$existing && $row['gv_hoten'] === '')
			throw new InvalidArgumentException('Dòng '.$row['row'].': học viên mới chưa có giáo viên; bổ sung Giáo viên trong file trước khi import.');
		$row['existing'] = $existing;
		if($existing)
		{
			$row['khoa'] = $existing['khoa'];
			if(empty($row['nhom_tu_file']) || $row['nhom'] === '') $row['nhom'] = $existing['nhom'];
			if($row['nhom'] !== $existing['nhom']) $plan['group_changes']++;
			$plan['updated']++;
		}
		else $plan['new']++;
		if($row['gv_hoten'] === '') $plan['missing_teachers']++;
		foreach($row['financial'] as $key => $amount) $plan['totals'][$key] += $amount;
		$plan['rows'][] = $row;
	}
	return $plan;
}

function xd_roster_save($rows)
{
	global $d;
	$d->startTransaction();
	try
	{
		$plan = xd_roster_plan($rows, true);
		$directory = dirname(__DIR__, 3).'/backups';
		if(!is_dir($directory) && !mkdir($directory, 0700, true)) throw new RuntimeException('Không tạo được thư mục sao lưu.');
		$backup = $directory.'/xd_roster_'.date('Ymd_His').'_'.bin2hex(random_bytes(6)).'.json';
		$json = json_encode(array('rows'=>$plan['rows']), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
		if($json === false || file_put_contents($backup, $json, LOCK_EX) !== strlen($json))
			throw new RuntimeException('Không sao lưu được; chưa import học viên.');
		$last = xd_roster_query('select max(lan_import) as last_import from #_xd_hocvien');
		$sequence = max(time(), (int)($last[0]['last_import'] ?? 0) + 1);
		foreach($plan['rows'] as $row)
		{
			$data = $row['financial'];
			if(!empty($row['ngay_nop_ho_so'])) $data['ngay_nop_ho_so'] = $row['ngay_nop_ho_so'];
			if($row['existing'])
			{
				$data['nhom'] = $row['nhom'];
				$data['ho_ten'] = $row['ho_ten'];
				if($row['ngaysinh'] !== '') $data['ngaysinh'] = $row['ngaysinh'];
				if($row['nguoi_nop'] !== '') $data['nguoi_nop'] = $row['nguoi_nop'];
				if($row['existing']['user_tao'] !== 'Đồng bộ DAT' && $row['existing']['ngay_thanh_toan'] === null &&
					(int)$row['existing']['id_bangke'] === 0 && empty($row['existing']['quan_ly_duyet']) &&
					(float)$row['existing']['so_tien_thanh_toan'] == 0 && $row['gv_hoten'] !== '')
				{
					$data['gv_hoten'] = $row['gv_hoten']; $data['gv_key'] = xd_gv_key($row['gv_hoten']);
				}
				$sets = array(); $params = array();
				foreach($data as $key => $value) { $sets[] = '`'.$key.'`=?'; $params[] = $value; }
				$params[] = (int)$row['existing']['id'];
				xd_roster_query('update #_xd_hocvien set '.implode(',', $sets).' where id=?', $params);
			}
			else
			{
				$data += array('ho_ten'=>$row['ho_ten'], 'cccd'=>$row['cccd'], 'ngaysinh'=>$row['ngaysinh'],
					'khoa'=>$row['khoa'], 'nhom'=>$row['nhom'], 'nguoi_nop'=>$row['nguoi_nop'],
					'gv_hoten'=>$row['gv_hoten'], 'gv_key'=>xd_gv_key($row['gv_hoten']),
					'lan_import'=>$sequence, 'thu_tu_file'=>$row['row'], 'ngaytao'=>time(), 'user_tao'=>xd_username());
				xd_roster_query('insert into #_xd_hocvien (`'.implode('`,`', array_keys($data)).'`) values ('.
					implode(',', array_fill(0, count($data), '?')).')', array_values($data));
			}
		}
		if($d->commit() === false) throw new RuntimeException('Không thể hoàn tất import học viên XD.');
		$plan['backup'] = basename($backup);
		return $plan;
	}
	catch(Throwable $error) { $d->rollback(); throw $error; }
}

function xd_roster_upload($sheet = null, $highestRow = 0, $map = array(), $headerRow = 1)
{
	global $func, $xd_roster_preview;
	$back = 'index.php?com=xangdau&act=uploadHocvien';
	try
	{
		if(isset($_POST['confirm_roster']))
		{
			$pending = $_SESSION['xd_roster_pending'] ?? null;
			if(!$pending || !hash_equals($pending['token'], (string)$_POST['confirm_roster']))
				throw new InvalidArgumentException('Bản xem trước không còn hợp lệ; vui lòng chọn file và xem trước lại.');
			$result = xd_roster_save($pending['rows']);
			unset($_SESSION['xd_roster_pending']);
		}
		else
		{
			unset($_SESSION['xd_roster_pending']);
			$rows = xd_roster_parse($sheet, $highestRow, $map, $headerRow,
				array('khoa'=>$_POST['khoa_mac_dinh'] ?? '', 'nhom'=>$_POST['nhom_mac_dinh'] ?? ''));
			$xd_roster_preview = xd_roster_plan($rows);
			$token = bin2hex(random_bytes(24));
			$_SESSION['xd_roster_pending'] = array('token'=>$token, 'rows'=>$rows);
			$xd_roster_preview['token'] = $token;
			return true;
		}
	}
	catch(Exception $error) { xd_hocvien_import_error(nl2br(htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8')), $back); }
	$func->transfer('Đã thêm '.$result['new'].' và cập nhật '.$result['updated'].' học viên XD; đổi nhóm '.$result['group_changes'].' hồ sơ theo Excel. Giữ nguyên lịch sử thanh toán. Sao lưu: '.$result['backup'],
		'index.php?com=xangdau&act=hocvien');
}
