<?php
if(!defined('SOURCES')) die("Error");

/* Số giờ đêm (khung 18h–5h) của một phiên, tính theo phút rồi quy ra giờ. */
function dt_night_hours($startDt, $endDt)
{
	$s = $startDt ? strtotime($startDt) : false;
	$e = $endDt ? strtotime($endDt) : false;
	if($s === false || $e === false || $e <= $s) return 0.0;
	$minutes = 0; $iter = 0;
	for($t = $s; $t < $e && $iter < 2000; $t += 60, $iter++)
	{
		$h = (int)date('G', $t);
		if($h >= 18 || $h < 5) $minutes++;
	}
	return round($minutes / 60, 4);
}

/* Cộng dồn tham số DAT theo học viên (mọi phiên khả dụng của khóa). */
function dt_dat_aggregate($cccd, $idKhoa)
{
	global $d;
	$rows = $d->rawQuery("select gio_thuchanh, la_xe_tudong, gio_dem, km from #_dt_dat_phien where id_khoa = ? and cccd = ?", array($idKhoa, $cccd));
	$agg = array('a' => 0, 'b' => 0, 'c' => 0, 'd' => 0, 'e' => 0);
	foreach($rows as $rw)
	{
		$gt = (float)$rw['gio_thuchanh'];
		if((int)$rw['la_xe_tudong'] === 1) $agg['c'] += $gt; else $agg['d'] += $gt;
		$agg['b'] += (float)$rw['gio_dem'];
		$agg['e'] += (float)$rw['km'];
	}
	$agg['a'] = $agg['c'] + $agg['d'];
	foreach($agg as $k => $v) $agg[$k] = round($v, 4);
	return $agg;
}

function dt_dat_upload_form()
{
	global $ds_khoa, $id_khoa_sel;
	$ds_khoa = dt_khoa_options();
	$id_khoa_sel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
}

/* Tra cứu tổng hợp DAT theo khóa (mỗi học viên 1 dòng, có tham số A/B/C/D/E). */
function dt_dat_list()
{
	global $d, $func, $items, $phien, $ds_khoa, $id_khoa_sel, $total_items;

	$ds_khoa = dt_khoa_options();
	$id_khoa_sel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$items = array(); $phien = array();
	$total_items = 0;
	$kw = dt_mb_lower(dt_req('keyword'));
	if(!$id_khoa_sel && $kw === '') return;
	if($id_khoa_sel)
	{
		$phien = $d->rawQuery("select * from #_dt_dat_phien where id_khoa = ? order by id desc", array($id_khoa_sel));
		$hvs = $d->rawQuery("select cccd, ma_hv, hoten, hang, id_khoa from #_dt_hocvien where id_khoa = ? order by hoten asc", array($id_khoa_sel));
	}
	else
	{
		$like = '%'.dt_req('keyword').'%';
		$hvs = $d->rawQuery("select cccd, ma_hv, hoten, hang, id_khoa from #_dt_hocvien where (hoten like ? or cccd like ? or ma_hv like ?) order by hoten asc limit 0,200", array($like, $like, $like));
	}
	$tt = dt_req('trang_thai');
	foreach($hvs as $hv)
	{
		if($kw !== '' && strpos(dt_mb_lower($hv['hoten'].' '.$hv['cccd'].' '.$hv['ma_hv']), $kw) === false) continue;
		$idKhoaHv = (int)$hv['id_khoa'];
		$agg = dt_dat_aggregate($hv['cccd'], $idKhoaHv);
		list($dat, $thieu) = dt_dat_danhgia($hv['hang'], $agg);
		$override = dt_dat_override_get($idKhoaHv, $hv['cccd']) === 1;
		if($override) { $dat = 1; $thieu = array(); }
		if($tt === 'dat' && !$dat) continue;
		if($tt === 'chua' && $dat) continue;
		$rPhien = $d->rawQueryOne("select count(*) as c from #_dt_dat_phien where id_khoa = ? and cccd = ?", array($idKhoaHv, $hv['cccd']));
		$soPhien = (int)($rPhien ? $rPhien['c'] : 0);
		$items[] = array('hv' => $hv, 'agg' => $agg, 'dat' => $dat, 'thieu' => $thieu, 'so_phien' => $soPhien, 'override' => $override);
	}
	$total_items = count($items);
}

function dt_dat_upload_excel()
{
	global $d, $func;

	$idKhoaSel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$back = "index.php?com=daotao&act=uploadDat".($idKhoaSel ? "&id_khoa=".$idKhoaSel : "");
	if(!isset($_FILES['file-excel']) || $_FILES['file-excel']['error'] != 0)
		dt_import_notice("Vui lòng chọn file Excel", $back, false);

	$file = $_FILES['file-excel'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	list($sheet, $highestRow, $highestCol) = dt_open_upload_sheet($file, $ext, $back);

	$aliases = array(
		'ma_phien' => array('maphienhoc','maphien'),
		'tg_batdau' => array('thoigianbatdauphien','thoigianbatdau'),
		'tg_ketthuc' => array('thoigianketthucphien','thoigianketthuc'),
		'gio_thuchanh' => array('thoigianthuchanhgio','thoigianthuchanh'),
		'km' => array('quangduongthuchanhkm','quangduongthuchanh','quangduong'),
		'ma_hv' => array('mahocvien'),
		'khoa' => array('makhoahoc','makhoa','khoa'),
		'gv_hoten' => array('hovatengiaovien','tengiaovien'),
		'hang' => array('hangdaotao'),
		'bien_so' => array('biensoxe','bienso'),
		'trang_thai' => array('trangthai'),
	);
	$contains = array(
		'ma_phien' => array('maphien'),
		'tg_batdau' => array('batdau'),
		'tg_ketthuc' => array('ketthuc'),
		'gio_thuchanh' => array('thoigianthuchanh'),
		'km' => array('quangduong'),
		'ma_hv' => array('mahocvien'),
		'khoa' => array('makhoa'),
		'hang' => array('hangdaotao'),
		'bien_so' => array('bienso'),
	);

	list($headerRow, $map, $score) = dt_find_header_row($sheet, $highestRow, $highestCol, $aliases, $contains, 12);
	if(!isset($map['ma_phien']) || !isset($map['ma_hv']))
		dt_import_notice("Không nhận diện được cột 'Mã phiên học' và 'Mã học viên' trong file DAT.", $back, false);

	dt_backup_tables(array('dt_dat_phien'), 'imp_dat');
	$d->startTransaction();
	$them = 0; $trung = 0; $err = 0; $errMsgs = array(); $emptyStreak = 0;
	for($r = $headerRow + 1; $r <= $highestRow; $r++)
	{
		$row = dt_read_row($sheet, $r, $highestCol);
		$maPhien = dt_val($row, $map, 'ma_phien');
		$maHv = dt_val($row, $map, 'ma_hv');
		if($maPhien === '' && $maHv === '') { if(++$emptyStreak >= 40) break; continue; }
		$emptyStreak = 0;
		if($maPhien === '') continue;

		$exist = $d->rawQueryOne("select id from #_dt_dat_phien where ma_phien = ? limit 0,1", array($maPhien));
		if($exist && $exist['id']) { $trung++; continue; }

		$khoaCode = dt_val($row, $map, 'khoa');
		$lookupKhoaId = $idKhoaSel;
		if(!$lookupKhoaId && $khoaCode !== '')
		{
			$khoa = $d->rawQueryOne("select id from #_dt_khoa where ma_khoa = ? limit 0,1", array($khoaCode));
			if($khoa) $lookupKhoaId = (int)$khoa['id'];
		}
		$hv = dt_find_hocvien_by_mahv($maHv, $lookupKhoaId);
		if(!$hv)
		{
			$err++;
			if(count($errMsgs) < 12) $errMsgs[] = "Dòng $r: mã HV $maHv chưa có trong danh sách học viên";
			continue;
		}
		$idKhoa = (int)$hv['id_khoa'];

		$tgBatdau = dt_parse_datetime(dt_val($row, $map, 'tg_batdau'));
		$tgKetthuc = dt_parse_datetime(dt_val($row, $map, 'tg_ketthuc'));
		$bienSo = preg_replace('/\s+/', '', dt_val($row, $map, 'bien_so'));

		$data = array(
			'ma_phien' => $maPhien,
			'ma_hv' => $maHv,
			'cccd' => $hv['cccd'],
			'id_khoa' => $idKhoa,
			'hang' => dt_norm_hang(dt_val($row, $map, 'hang') !== '' ? dt_val($row, $map, 'hang') : $hv['hang']),
			'tg_batdau' => $tgBatdau,
			'tg_ketthuc' => $tgKetthuc,
			'gio_thuchanh' => dt_parse_number(dt_val($row, $map, 'gio_thuchanh')),
			'km' => dt_parse_number(dt_val($row, $map, 'km')),
			'la_xe_tudong' => dt_xe_la_tudong($bienSo) ? 1 : 0,
			'gio_dem' => dt_night_hours($tgBatdau, $tgKetthuc),
			'bien_so' => $bienSo,
			'gv_hoten' => dt_val($row, $map, 'gv_hoten'),
			'trang_thai' => dt_val($row, $map, 'trang_thai'),
			'ngay_hoc' => $tgBatdau ? substr($tgBatdau, 0, 10) : null,
			'ngaytao' => time(),
		);
		if($d->insert('dt_dat_phien', $data)) $them++;
	}

	$d->commit();
	dt_audit('import', 'dat', $them, $file['name']);
	dt_log_import('dat', $file['name'], $them, $err);
	$msg = "Import DAT: thêm $them phiên mới, bỏ qua $trung phiên đã có".($err ? ", $err phiên lỗi (HV chưa có)" : "");
	if(!empty($errMsgs)) $msg .= " — ".implode('; ', $errMsgs);
	dt_import_notice($msg, "index.php?com=daotao&act=dat".($idKhoaSel ? "&id_khoa=".$idKhoaSel : ""), ($them > 0 || $err === 0));
}

/* Đánh dấu DAT Đạt (override) toàn bộ học viên trong khóa. */
function dt_dat_dat_all()
{
	global $d, $func;

	$idKhoa = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$back = "index.php?com=daotao&act=dat".($idKhoa ? "&id_khoa=".$idKhoa : "");
	if(!$idKhoa) dt_import_notice("Thiếu khóa học", $back, false);

	$hvs = $d->rawQuery("select cccd from #_dt_hocvien where id_khoa = ?", array($idKhoa));
	dt_backup_tables(array('dt_dat_override'), 'dat_datall');
	$d->startTransaction();
	foreach($hvs as $hv)
	{
		$data = array('id_khoa' => $idKhoa, 'cccd' => $hv['cccd'], 'dat' => 1, 'nguoi_nhap' => dt_username());
		$exist = $d->rawQueryOne("select id from #_dt_dat_override where id_khoa = ? and cccd = ? limit 0,1", array($idKhoa, $hv['cccd']));
		if($exist && $exist['id']) { $d->where('id', $exist['id']); $d->update('dt_dat_override', $data); }
		else { $data['ngaytao'] = time(); $d->insert('dt_dat_override', $data); }
	}
	$d->commit();
	dt_audit('update', 'dat_dat_all', count($hvs), 'id_khoa='.$idKhoa);
	dt_import_notice("Đã đánh dấu Đạt DAT cho ".count($hvs)." học viên", $back, true);
}

/* Bỏ override Đạt DAT cho 1 học viên. */
function dt_dat_override_clear()
{
	global $d, $func;
	$idKhoa = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$cccd = isset($_REQUEST['cccd']) ? dt_normalize_cccd($_REQUEST['cccd']) : '';
	$back = "index.php?com=daotao&act=dat".($idKhoa ? "&id_khoa=".$idKhoa : "");
	if($idKhoa && $cccd !== '') $d->rawQuery("delete from #_dt_dat_override where id_khoa = ? and cccd = ?", array($idKhoa, $cccd));
	$func->transfer("Đã bỏ xác nhận Đạt DAT", $back);
}
