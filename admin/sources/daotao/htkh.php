<?php
if(!defined('SOURCES')) die("Error");

/* ============================================================
 * Mô đun Số giấy HTKH (giấy chứng nhận hoàn thành khóa học).
 * Cập nhật số giấy theo học viên: nhập tay hoặc import Excel.
 * ============================================================ */

/* Danh sách học viên của 1 khóa kèm số giấy HTKH. */
function dt_htkh_list()
{
	global $d, $items, $ds_khoa, $id_khoa_sel, $total_items;
	$ds_khoa = dt_khoa_options();
	$id_khoa_sel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$items = array(); $total_items = 0;
	$kw = dt_mb_lower(dt_req('keyword'));
	if(!$id_khoa_sel && $kw === '') return;

	if($id_khoa_sel)
		$hvs = $d->rawQuery("select h.id, h.cccd, h.ma_hv, h.hoten, h.hang, h.id_khoa, h.gv_hoten from #_dt_hocvien h where h.id_khoa = ? order by h.hoten asc", array($id_khoa_sel));
	else
	{
		$like = '%'.dt_req('keyword').'%';
		$hvs = $d->rawQuery("select h.id, h.cccd, h.ma_hv, h.hoten, h.hang, h.id_khoa, h.gv_hoten from #_dt_hocvien h where (h.hoten like ? or h.cccd like ? or h.ma_hv like ?) order by h.hoten asc limit 0,200", array($like, $like, $like));
	}
	$ids = array();
	foreach($hvs as $hv) $ids[(int)$hv['id_khoa']] = true;
	$byCccd = array();
	if($ids)
	{
		$in = implode(',', array_map('intval', array_keys($ids)));
		foreach($d->rawQuery("select id_khoa, cccd, so_giay, ngay_cap, ghi_chu from #_dt_htkh where id_khoa in ($in)") as $r)
			$byCccd[$r['id_khoa'].'|'.$r['cccd']] = $r;
	}
	foreach($hvs as $hv)
	{
		if($kw !== '' && strpos(dt_mb_lower($hv['hoten'].' '.$hv['cccd'].' '.$hv['ma_hv']), $kw) === false) continue;
		$hv['htkh'] = isset($byCccd[$hv['id_khoa'].'|'.$hv['cccd']]) ? $byCccd[$hv['id_khoa'].'|'.$hv['cccd']] : null;
		$items[] = $hv;
	}
	$total_items = count($items);
}

/* Lưu số giấy HTKH cho 1 học viên (nhập tay). */
function dt_htkh_save()
{
	global $d, $func;
	$idKhoa = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$cccd = isset($_POST['cccd']) ? dt_normalize_cccd($_POST['cccd']) : '';
	$back = "index.php?com=daotao&act=htkh&id_khoa=".$idKhoa;
	if(!$idKhoa || $cccd === '') $func->transfer("Thiếu dữ liệu học viên", $back, false);
	$hv = dt_find_hocvien_by_cccd($cccd, $idKhoa);
	if(!$hv) $func->transfer("Học viên không thuộc khóa", $back, false);

	$data = array(
		'id_khoa' => $idKhoa, 'cccd' => $hv['cccd'], 'ma_hv' => $hv['ma_hv'],
		'so_giay' => htmlspecialchars(trim($_POST['so_giay'] ?? ''), ENT_QUOTES, 'UTF-8'),
		'ngay_cap' => htmlspecialchars(trim($_POST['ngay_cap'] ?? ''), ENT_QUOTES, 'UTF-8'),
		'ghi_chu' => htmlspecialchars(trim($_POST['ghi_chu'] ?? ''), ENT_QUOTES, 'UTF-8'),
		'nguoi_nhap' => dt_username(),
	);
	$exist = $d->rawQueryOne("select id from #_dt_htkh where id_khoa = ? and cccd = ? limit 0,1", array($idKhoa, $hv['cccd']));
	if($exist && $exist['id']) { $d->where('id', $exist['id']); $d->update('dt_htkh', $data); }
	else { $data['ngaytao'] = time(); $d->insert('dt_htkh', $data); }
	dt_audit('update', 'htkh', 1, 'cccd='.$hv['cccd']);
	$func->transfer("Đã lưu số giấy HTKH cho ".$hv['hoten'], $back);
}

/* Màn import Excel. */
function dt_htkh_upload_form()
{
	global $d, $ds_khoa, $id_khoa_sel;
	$ds_khoa = dt_khoa_options();
	$id_khoa_sel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
}

/* Import số giấy HTKH từ Excel (ghép theo CCCD hoặc Mã HV). */
function dt_htkh_upload_excel()
{
	global $d, $func;
	$idKhoaSel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$back = "index.php?com=daotao&act=uploadHtkh".($idKhoaSel ? "&id_khoa=".$idKhoaSel : "");
	if(!isset($_FILES['file-excel']) || $_FILES['file-excel']['error'] != 0)
		dt_import_notice("Vui lòng chọn file Excel", $back, false);
	$file = $_FILES['file-excel'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	list($sheet, $highestRow, $highestCol) = dt_open_upload_sheet($file, $ext, $back);

	$aliases = array(
		'cccd' => array('cccd','socccd','socmt','socmnd'),
		'ma_hv' => array('mahv','mahocvien'),
		'so_giay' => array('sogiayhtkh','sogiay','sohtkh','sochungnhan','sogcn','sogiaychungnhan'),
		'ngay_cap' => array('ngaycap','ngaycapgiay'),
		'ghi_chu' => array('ghichu'),
	);
	$contains = array(
		'cccd' => array('cccd','cmt'),
		'so_giay' => array('htkh','chungnhan','sogiay'),
		'ma_hv' => array('mahv','mahocvien'),
	);
	list($headerRow, $map, $score) = dt_find_header_row($sheet, $highestRow, $highestCol, $aliases, $contains);
	if($score <= 0 || !isset($map['so_giay']) || (!isset($map['cccd']) && !isset($map['ma_hv'])))
		dt_import_notice("Không nhận diện được cột 'Số giấy HTKH' và 'CCCD/Mã HV'.", $back, false);

	dt_backup_tables(array('dt_htkh'), 'imp_htkh');
	$d->startTransaction();
	$ok = 0; $err = 0; $errMsgs = array(); $emptyStreak = 0;
	for($r = $headerRow + 1; $r <= $highestRow; $r++)
	{
		$row = dt_read_row($sheet, $r, $highestCol);
		$cccd = dt_normalize_cccd(dt_val($row, $map, 'cccd'));
		$maHv = dt_val($row, $map, 'ma_hv');
		$soGiay = dt_val($row, $map, 'so_giay');
		if($cccd === '' && $maHv === '') { if(++$emptyStreak >= 30) break; continue; }
		$emptyStreak = 0;
		if($soGiay === '') continue;

		$hv = $cccd !== '' ? dt_find_hocvien_by_cccd($cccd, $idKhoaSel) : dt_find_hocvien_by_mahv($maHv, $idKhoaSel);
		if(!$hv)
		{
			$err++;
			if(count($errMsgs) < 12) $errMsgs[] = "Dòng $r: ".($cccd ?: $maHv)." chưa có trong danh sách học viên";
			continue;
		}
		$data = array(
			'id_khoa' => (int)$hv['id_khoa'], 'cccd' => $hv['cccd'], 'ma_hv' => $hv['ma_hv'],
			'so_giay' => $soGiay, 'ngay_cap' => dt_val($row, $map, 'ngay_cap'), 'ghi_chu' => dt_val($row, $map, 'ghi_chu'),
			'nguoi_nhap' => dt_username(),
		);
		$exist = $d->rawQueryOne("select id from #_dt_htkh where id_khoa = ? and cccd = ? limit 0,1", array($hv['id_khoa'], $hv['cccd']));
		if($exist && $exist['id']) { $d->where('id', $exist['id']); $d->update('dt_htkh', $data); }
		else { $data['ngaytao'] = time(); $d->insert('dt_htkh', $data); }
		$ok++;
	}
	$d->commit();
	dt_audit('import', 'htkh', $ok, $file['name']);
	dt_log_import('htkh', $file['name'], $ok, $err);
	$msg = "Import số giấy HTKH: $ok học viên".($err ? ", $err lỗi" : "");
	if(!empty($errMsgs)) $msg .= " — ".implode('; ', $errMsgs);
	dt_import_notice($msg, "index.php?com=daotao&act=htkh".($idKhoaSel ? "&id_khoa=".$idKhoaSel : ""), ($ok > 0 || $err === 0));
}

/* Xuất Excel số giấy HTKH theo khóa. */
function dt_htkh_export()
{
	global $d, $func;
	$idKhoa = (int)dt_req('id_khoa', 0);
	if(!$idKhoa) $func->transfer('Vui lòng chọn khóa để xuất', 'index.php?com=daotao&act=htkh', false);
	$hvs = $d->rawQuery("select h.cccd, h.ma_hv, h.hoten, h.hang, h.gv_hoten from #_dt_hocvien h where h.id_khoa = ? order by h.hoten asc", array($idKhoa));
	$byCccd = array();
	foreach($d->rawQuery("select cccd, so_giay, ngay_cap, ghi_chu from #_dt_htkh where id_khoa = ?", array($idKhoa)) as $r) $byCccd[$r['cccd']] = $r;
	$headers = array('STT','Mã HV','Họ và tên','CCCD','Hạng','Giáo viên','Số giấy HTKH','Ngày cấp','Ghi chú');
	$out = array(); $stt = 1;
	foreach($hvs as $hv)
	{
		$h = isset($byCccd[$hv['cccd']]) ? $byCccd[$hv['cccd']] : null;
		$out[] = array($stt++, $hv['ma_hv'], $hv['hoten'], $hv['cccd'], $hv['hang'], $hv['gv_hoten'],
			$h ? $h['so_giay'] : '', $h ? $h['ngay_cap'] : '', $h ? $h['ghi_chu'] : '');
	}
	dt_xlsx_simple('So giay HTKH', 'so-giay-htkh-'.date('Ymd_His').'.xlsx', $headers, $out, array(1, 3));
}
