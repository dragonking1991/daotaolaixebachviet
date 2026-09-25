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
	global $d, $func, $items, $ds_khoa, $id_khoa_sel;

	$ds_khoa = dt_khoa_options();
	$id_khoa_sel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$items = array();
	if(!$id_khoa_sel) return;

	$hvs = $d->rawQuery("select cccd, ma_hv, hoten, hang from #_dt_hocvien where id_khoa = ? order by hoten asc", array($id_khoa_sel));
	foreach($hvs as $hv)
	{
		$agg = dt_dat_aggregate($hv['cccd'], $id_khoa_sel);
		list($dat, $thieu) = dt_dat_danhgia($hv['hang'], $agg);
		$rPhien = $d->rawQueryOne("select count(*) as c from #_dt_dat_phien where id_khoa = ? and cccd = ?", array($id_khoa_sel, $hv['cccd']));
		$soPhien = (int)($rPhien ? $rPhien['c'] : 0);
		$items[] = array('hv' => $hv, 'agg' => $agg, 'dat' => $dat, 'thieu' => $thieu, 'so_phien' => $soPhien);
	}
}

function dt_dat_upload_excel()
{
	global $d, $func;

	$idKhoa = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$back = "index.php?com=daotao&act=uploadDat&id_khoa=".$idKhoa;
	if(!$idKhoa) $func->transfer("Vui lòng chọn khóa học", $back, false);
	if(!isset($_FILES['file-excel']) || $_FILES['file-excel']['error'] != 0)
		$func->transfer("Vui lòng chọn file Excel", $back, false);

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
		'hang' => array('hangdaotao'),
		'bien_so' => array('bienso'),
	);

	list($headerRow, $map, $score) = dt_find_header_row($sheet, $highestRow, $highestCol, $aliases, $contains, 12);
	if(!isset($map['ma_phien']) || !isset($map['ma_hv']))
		$func->transfer("Không nhận diện được cột 'Mã phiên học' và 'Mã học viên' trong file DAT.", $back, false);

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

		$hv = dt_find_hocvien_by_mahv($maHv, $idKhoa);
		if(!$hv)
		{
			$err++;
			if(count($errMsgs) < 12) $errMsgs[] = "Dòng $r: mã HV $maHv không thuộc khóa";
			continue;
		}

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

	dt_log_import('dat', $file['name'], $them, $err);
	$msg = "Import DAT: thêm $them phiên mới, bỏ qua $trung phiên đã có".($err ? ", $err phiên lỗi (HV không thuộc khóa)" : "");
	if(!empty($errMsgs)) $msg .= " — ".implode('; ', $errMsgs);
	$func->transfer($msg, "index.php?com=daotao&act=dat&id_khoa=".$idKhoa, $err === 0);
}
