<?php
if(!defined('SOURCES')) die("Error");

function dt_lythuyet_upload_form()
{
	global $ds_khoa, $id_khoa_sel, $ds_mon;
	$ds_khoa = dt_khoa_options();
	$ds_mon = dt_mon_lythuyet();
	$id_khoa_sel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
}

/* Danh sách tổng hợp 6 môn theo học viên của một khóa. */
function dt_lythuyet_list()
{
	global $d, $func, $items, $ds_khoa, $id_khoa_sel, $ds_mon, $du_lieu_filter, $total_items, $lt_mode, $module_stats;

	$ds_khoa = dt_khoa_options();
	$ds_mon = dt_mon_lythuyet();
	$id_khoa_sel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$lt_mode = 'import';
	$du_lieu_filter = dt_req('du_lieu', 'co');
	if(!in_array($du_lieu_filter, array('co', 'tat_ca', 'chua'), true)) $du_lieu_filter = 'co';
	$items = array();
	$total_items = 0;
	$module_stats = array('dat' => 0, 'chua' => 0, 'mon' => array());
	$kw = dt_mb_lower(dt_req('keyword'));
	// Cho phép tra cứu CCCD/tên trên toàn bộ khóa khi chưa chọn khóa (nếu có từ khóa).
	if(!$id_khoa_sel && $kw === '') return;

	if($id_khoa_sel)
	{
		$khoaHang = '';
		$rk = $d->rawQueryOne("select hang from #_dt_khoa where id = ?", array($id_khoa_sel));
		if($rk) $khoaHang = $rk['hang'];
		if(dt_is_nang_hang($khoaHang))
		{
			$ds_mon = dt_mon_lythuyet_nang_hang();
			$lt_mode = 'check';
		}
		$hvs = $d->rawQuery("select * from #_dt_hocvien where id_khoa = ? order by substring_index(hoten, ' ', -1) asc, hoten asc", array($id_khoa_sel));
		$rows = $d->rawQuery("select id, id_khoa, cccd, mon, tien_do, diem_kt, dat from #_dt_lythuyet where id_khoa = ?", array($id_khoa_sel));
		$mpRows = $d->rawQuery("select id_khoa, cccd, tien_do, dat from #_dt_mophong where id_khoa = ?", array($id_khoa_sel));
	}
	else
	{
		$like = '%'.dt_req('keyword').'%';
		$hvs = $d->rawQuery("select * from #_dt_hocvien where (hoten like ? or cccd like ? or ma_hv like ?) order by substring_index(hoten, ' ', -1) asc, hoten asc limit 0,200", array($like, $like, $like));
		$ids = array();
		foreach($hvs as $hv) $ids[(int)$hv['id_khoa']] = true;
		$rows = array();
		$mpRows = array();
		if($ids)
		{
			$in = implode(',', array_map('intval', array_keys($ids)));
			$rows = $d->rawQuery("select id, id_khoa, cccd, mon, tien_do, diem_kt, dat from #_dt_lythuyet where id_khoa in ($in)");
			$mpRows = $d->rawQuery("select id_khoa, cccd, tien_do, dat from #_dt_mophong where id_khoa in ($in)");
		}
	}
	$byCccd = array();
	foreach($rows as $rw)
	{
		$rw['dat'] = dt_lythuyet_dat($rw['tien_do'], $rw['diem_kt'], $rw['mon']);
		$byCccd[$rw['id_khoa'].'|'.$rw['cccd']][$rw['mon']] = $rw;
	}
	foreach($mpRows as $rw)
	{
		$key = $rw['id_khoa'].'|'.$rw['cccd'];
		$mp = array('id' => 0, 'id_khoa' => $rw['id_khoa'], 'cccd' => $rw['cccd'], 'mon' => 'mophong', 'tien_do' => $rw['tien_do'], 'diem_kt' => 0, 'dat' => dt_lythuyet_dat($rw['tien_do'], 0, 'mophong'), 'standalone' => 1);
		if(isset($byCccd[$key]['mophong'])) $mp['id'] = $byCccd[$key]['mophong']['id'];
		$byCccd[$key]['mophong'] = $mp;
	}

	$tt = dt_req('trang_thai');
	$gvFilter = dt_gv_key(dt_req('gv'));
	foreach($hvs as $hv)
	{
		if($kw !== '' && strpos(dt_mb_lower($hv['hoten'].' '.$hv['cccd'].' '.$hv['ma_hv']), $kw) === false) continue;
		if($gvFilter !== '' && (string)$hv['gv_key'] !== $gvFilter) continue;
		$key = $hv['id_khoa'].'|'.$hv['cccd'];
		$monData = isset($byCccd[$key]) ? $byCccd[$key] : array();
		if($du_lieu_filter === 'co' && empty($monData)) continue;
		if($du_lieu_filter === 'chua' && !empty($monData)) continue;
		$rowMons = dt_mon_lythuyet_for_hang($hv['hang']);
		$soDat = 0;
		foreach($rowMons as $mk => $lbl)
		{
			$isDat = isset($monData[$mk]) && (int)$monData[$mk]['dat'] === 1;
			if($isDat) $soDat++;
			if(!isset($module_stats['mon'][$mk])) $module_stats['mon'][$mk] = array('label' => $lbl, 'dat' => 0, 'chua' => 0);
			$module_stats['mon'][$mk][$isDat ? 'dat' : 'chua']++;
		}
		$tongMon = count($rowMons);
		$module_stats[$soDat >= $tongMon ? 'dat' : 'chua']++;
		if($tt !== '')
		{
			if($tt === 'dat' && $soDat < $tongMon) continue;
			if($tt === 'chua' && $soDat >= $tongMon) continue;
		}
		$items[] = array('hv' => $hv, 'mon' => $monData, 'ds_mon' => $rowMons);
	}
	$total_items = count($items);
}

function dt_lythuyet_upload_excel()
{
	global $d, $func;

	$idKhoaSel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$mon = isset($_REQUEST['mon']) ? preg_replace('/[^a-z0-9_]/', '', $_REQUEST['mon']) : '';
	$back = "index.php?com=daotao&act=uploadLythuyet".($idKhoaSel ? "&id_khoa=".$idKhoaSel : "");
	$dsMon = dt_mon_lythuyet();

	if(!isset($dsMon[$mon])) dt_import_notice("Vui lòng chọn môn học hợp lệ", $back, false);
	if(!isset($_FILES['file-excel']) || $_FILES['file-excel']['error'] != 0)
		dt_import_notice("Vui lòng chọn file Excel", $back, false);
	$file = $_FILES['file-excel'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	list($sheet, $highestRow, $highestCol) = dt_open_upload_sheet($file, $ext, $back);

	// Header nhiều dòng: gộp 4 dòng đầu để mỗi cột có đủ nhãn.
	$depth = min(4, $highestRow);
	$merged = array();
	for($c = 0; $c <= $highestCol; $c++)
	{
		$parts = array();
		for($r = 1; $r <= $depth; $r++)
		{
			$v = $sheet->getCellByColumnAndRow($c, $r)->getValue();
			if($v !== null && trim((string)$v) !== '') $parts[] = trim((string)$v);
		}
		$merged[$c] = implode(' ', $parts);
	}

	$aliases = array(
		'cccd' => array('madangnhap','madangky','cccd'),
		'tien_do' => array('tiendohoanthanh'),
		'diem_kt' => array('diemkiemtra','diemmophong'),
		'xem_giao_trinh' => array(),
		'on_luyen' => array(),
		'kiem_tra_gio' => array(),
	);
	$contains = array(
		'cccd' => array('madangnhap','madangky'),
		'tien_do' => array('tiendohoanthanh'),
		'diem_kt' => array('diemkiemtra'),
		'xem_giao_trinh' => array('xemgiaotrinh'),
		'on_luyen' => array('onluyen'),
		'kiem_tra_gio' => array('kiemtra'),
	);
	list($map, $score) = dt_detect_header($merged, $aliases, $contains);
	// Tiến độ = (Xem giáo trình + Ôn luyện + Kiểm tra) / mẫu số * 100 (Note LT); nếu thiếu 3 cột thì đọc cột 'Tiến độ hoàn thành'.
	$have3 = isset($map['xem_giao_trinh']) && isset($map['on_luyen']) && isset($map['kiem_tra_gio']);
	if($mon === 'mophong')
	{
		if(!isset($map['cccd']) || (!isset($map['on_luyen']) && !isset($map['tien_do'])))
			dt_import_notice("Không nhận diện được cột 'Mã đăng nhập' và tiến độ (Ôn luyện / Tiến độ hoàn thành) trong file mô phỏng.", $back, false);
	}
	elseif(!isset($map['cccd']) || (!$have3 && !isset($map['tien_do'])))
		dt_import_notice("Không nhận diện đủ cột: 'Mã đăng nhập' và tiến độ (3 cột giờ hoặc 'Tiến độ hoàn thành').", $back, false);

	$backupTables = $mon === 'mophong' ? array('dt_lythuyet', 'dt_mophong') : array('dt_lythuyet');
	dt_backup_tables($backupTables, 'imp_lythuyet');
	$d->startTransaction();
	$ok = 0; $err = 0; $errMsgs = array(); $emptyStreak = 0;
	for($r = 2; $r <= $highestRow; $r++)
	{
		$row = dt_read_row($sheet, $r, $highestCol);
		$cccdRaw = dt_val($row, $map, 'cccd');
		$cccd = dt_normalize_cccd($cccdRaw);
		if(strlen($cccd) < 9)
		{
			continue; // bỏ qua dòng đánh dấu / sub-header
		}
		$emptyStreak = 0;

		$hv = dt_find_hocvien_by_cccd($cccd, $idKhoaSel);
		if(!$hv)
		{
			$err++;
			if(count($errMsgs) < 12) $errMsgs[] = "Dòng $r: CCCD $cccd chưa có trong danh sách học viên";
			continue;
		}
		$idKhoa = (int)$hv['id_khoa'];

		$denom = dt_lythuyet_denom($mon);
		if($mon === 'mophong')
			$tienDo = (isset($map['on_luyen']) && $denom > 0) ? round(dt_parse_number(dt_val($row, $map, 'on_luyen')) / $denom * 100, 2) : dt_parse_number(dt_val($row, $map, 'tien_do'));
		elseif($have3 && $denom > 0)
		{
			$sum = dt_parse_number(dt_val($row, $map, 'xem_giao_trinh')) + dt_parse_number(dt_val($row, $map, 'on_luyen')) + dt_parse_number(dt_val($row, $map, 'kiem_tra_gio'));
			$tienDo = round($sum / $denom * 100, 2);
		}
		else $tienDo = dt_parse_number(dt_val($row, $map, 'tien_do'));
		$diemKt = dt_parse_number(dt_val($row, $map, 'diem_kt'));
		$dat = dt_lythuyet_dat($tienDo, $diemKt, $mon);

		$data = array('id_khoa' => $idKhoa, 'cccd' => $hv['cccd'], 'mon' => $mon,
			'tien_do' => $tienDo, 'diem_kt' => $diemKt, 'dat' => $dat);

		$exist = $d->rawQueryOne("select id from #_dt_lythuyet where id_khoa = ? and cccd = ? and mon = ? limit 0,1", array($idKhoa, $hv['cccd'], $mon));
		if($exist && $exist['id']) { $d->where('id', $exist['id']); $d->update('dt_lythuyet', $data); }
		else { $data['ngaytao'] = time(); $d->insert('dt_lythuyet', $data); }
		if($mon === 'mophong')
			$d->rawQuery("insert into #_dt_mophong (id_khoa, cccd, tien_do, dat, ngaytao) values (?, ?, ?, ?, ?) on duplicate key update tien_do = values(tien_do), dat = values(dat)", array($idKhoa, $hv['cccd'], $tienDo, dt_mophong_dat($tienDo), time()));
		$ok++;
	}

	if($d->transactionFailed()) { $d->rollback(); dt_import_notice('Import thất bại khi ghi cơ sở dữ liệu; đã hoàn tác toàn bộ file, chưa lưu dòng nào. Vui lòng kiểm tra dữ liệu (độ dài, định dạng) rồi thử lại.', $back, false); }
	$d->commit();
	dt_audit('import', 'lythuyet:'.$mon, $ok, $file['name']);
	dt_log_import('lythuyet:'.$mon, $file['name'], $ok, $err);
	$msg = "Import môn ".$dsMon[$mon].": $ok học viên".($err ? ", $err lỗi" : "");
	if(!empty($errMsgs)) $msg .= " — ".implode('; ', $errMsgs);
	dt_import_notice($msg, "index.php?com=daotao&act=lythuyet".($idKhoaSel ? "&id_khoa=".$idKhoaSel : ""), ($ok > 0 || $err === 0));
}

/* Lưu kết quả check 4 môn lý thuyết nâng hạng (C/CE). POST: id_khoa, check[cccd][mon]=1, nút "Đạt toàn bộ". */
function dt_lythuyet_save_check()
{
	global $d, $func;

	$idKhoa = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$back = "index.php?com=daotao&act=lythuyet".($idKhoa ? "&id_khoa=".$idKhoa : "");
	if(!$idKhoa) dt_import_notice("Thiếu khóa học", $back, false);

	$rk = $d->rawQueryOne("select hang from #_dt_khoa where id = ?", array($idKhoa));
	if(!$rk || !dt_is_nang_hang($rk['hang']))
		dt_import_notice("Khóa này không phải khóa nâng hạng", $back, false);

	$dsMon = dt_mon_lythuyet_nang_hang();
	$datAll = !empty($_POST['dat_all']);
	$check = isset($_POST['check']) && is_array($_POST['check']) ? $_POST['check'] : array();

	$hvs = $d->rawQuery("select cccd from #_dt_hocvien where id_khoa = ?", array($idKhoa));
	dt_backup_tables(array('dt_lythuyet', 'dt_mophong'), 'imp_lythuyet');
	$d->startTransaction();
	$n = 0;
	foreach($hvs as $hv)
	{
		$cccd = $hv['cccd'];
		$cc = isset($check[$cccd]) && is_array($check[$cccd]) ? $check[$cccd] : array();
		foreach($dsMon as $mk => $lbl)
		{
			$dat = ($datAll || !empty($cc[$mk])) ? 1 : 0;
			$data = array('id_khoa' => $idKhoa, 'cccd' => $cccd, 'mon' => $mk,
				'tien_do' => $dat ? 100 : 0, 'diem_kt' => 0, 'dat' => $dat);
			$exist = $d->rawQueryOne("select id from #_dt_lythuyet where id_khoa = ? and cccd = ? and mon = ? limit 0,1", array($idKhoa, $cccd, $mk));
			if($exist && $exist['id']) { $d->where('id', $exist['id']); $d->update('dt_lythuyet', $data); }
			else { $data['ngaytao'] = time(); $d->insert('dt_lythuyet', $data); }
			if($mk === 'mophong')
				$d->rawQuery("insert into #_dt_mophong (id_khoa, cccd, tien_do, dat, ngaytao) values (?, ?, ?, ?, ?) on duplicate key update tien_do = values(tien_do), dat = values(dat)", array($idKhoa, $cccd, $data['tien_do'], $dat, time()));
			$n++;
		}
	}
	$d->commit();
	dt_audit('check', 'lythuyet_nang_hang', $n, '');
	dt_import_notice("Đã lưu kết quả lý thuyết nâng hạng (".count($hvs)." học viên)", $back, true);
}
