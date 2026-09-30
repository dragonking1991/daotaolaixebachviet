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
	global $d, $func, $items, $ds_khoa, $id_khoa_sel, $ds_mon;

	$ds_khoa = dt_khoa_options();
	$ds_mon = dt_mon_lythuyet();
	$id_khoa_sel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$items = array();
	if(!$id_khoa_sel) return;

	$hvs = $d->rawQuery("select * from #_dt_hocvien where id_khoa = ? order by hoten asc", array($id_khoa_sel));
	$rows = $d->rawQuery("select cccd, mon, tien_do, diem_kt, dat from #_dt_lythuyet where id_khoa = ?", array($id_khoa_sel));
	$byCccd = array();
	foreach($rows as $rw) $byCccd[$rw['cccd']][$rw['mon']] = $rw;

	$kw = dt_mb_lower(dt_req('keyword'));
	$tt = dt_req('trang_thai');
	$tongMon = count($ds_mon);
	foreach($hvs as $hv)
	{
		if($kw !== '' && strpos(dt_mb_lower($hv['hoten'].' '.$hv['cccd'].' '.$hv['ma_hv']), $kw) === false) continue;
		$monData = isset($byCccd[$hv['cccd']]) ? $byCccd[$hv['cccd']] : array();
		if($tt !== '')
		{
			$soDat = 0;
			foreach($ds_mon as $mk => $lbl) if(isset($monData[$mk]) && (int)$monData[$mk]['dat'] === 1) $soDat++;
			if($tt === 'dat' && $soDat < $tongMon) continue;
			if($tt === 'chua' && $soDat >= $tongMon) continue;
		}
		$items[] = array('hv' => $hv, 'mon' => $monData);
	}
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
		'diem_kt' => array('diemkiemtra'),
	);
	$contains = array(
		'cccd' => array('madangnhap','madangky'),
		'tien_do' => array('tiendohoanthanh','tiendo'),
		'diem_kt' => array('diemkiemtra'),
	);
	list($map, $score) = dt_detect_header($merged, $aliases, $contains);
	if(!isset($map['cccd']) || !isset($map['tien_do']) || !isset($map['diem_kt']))
		dt_import_notice("Không nhận diện đủ các cột 'Mã đăng nhập', 'Tiến độ hoàn thành', 'Điểm kiểm tra'.", $back, false);

	dt_backup_tables(array('dt_lythuyet'), 'imp_lythuyet');
	$d->startTransaction();
	$ok = 0; $err = 0; $errMsgs = array(); $emptyStreak = 0;
	for($r = 2; $r <= $highestRow; $r++)
	{
		$row = dt_read_row($sheet, $r, $highestCol);
		$cccdRaw = dt_val($row, $map, 'cccd');
		$cccd = dt_normalize_cccd($cccdRaw);
		if(strlen($cccd) < 9)
		{
			if($cccdRaw === '' && ++$emptyStreak >= 40) break;
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

		$tienDo = dt_parse_number(dt_val($row, $map, 'tien_do'));
		$diemKt = dt_parse_number(dt_val($row, $map, 'diem_kt'));
		$dat = dt_lythuyet_dat($tienDo, $diemKt);

		$data = array('id_khoa' => $idKhoa, 'cccd' => $hv['cccd'], 'mon' => $mon,
			'tien_do' => $tienDo, 'diem_kt' => $diemKt, 'dat' => $dat);

		$exist = $d->rawQueryOne("select id from #_dt_lythuyet where id_khoa = ? and cccd = ? and mon = ? limit 0,1", array($idKhoa, $hv['cccd'], $mon));
		if($exist && $exist['id']) { $d->where('id', $exist['id']); $d->update('dt_lythuyet', $data); }
		else { $data['ngaytao'] = time(); $d->insert('dt_lythuyet', $data); }
		$ok++;
	}

	$d->commit();
	dt_audit('import', 'lythuyet:'.$mon, $ok, $file['name']);
	dt_log_import('lythuyet:'.$mon, $file['name'], $ok, $err);
	$msg = "Import môn ".$dsMon[$mon].": $ok học viên".($err ? ", $err lỗi" : "");
	if(!empty($errMsgs)) $msg .= " — ".implode('; ', $errMsgs);
	dt_import_notice($msg, "index.php?com=daotao&act=lythuyet".($idKhoaSel ? "&id_khoa=".$idKhoaSel : ""), $err === 0);
}
