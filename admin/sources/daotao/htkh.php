<?php
if(!defined('SOURCES')) die("Error");

/* ============================================================
 * Mô đun Số giấy HTKH (giấy chứng nhận hoàn thành khóa học).
 * Cập nhật số giấy theo học viên: nhập tay hoặc import Excel.
 * ============================================================ */

/* Quy đổi hạng khóa sang hạng in trên giấy HTKH (Update 6):
 * B11/B1 → B; C1 → C1; C → C; CE → CE; (D → CE). */
function dt_htkh_hang_quydoi($hang)
{
	$h = dt_norm_hang($hang);
	if($h === 'B11' || $h === 'B1') return 'B';
	if($h === 'C1') return 'C1';
	if($h === 'C') return 'C';
	if($h === 'CE') return 'CE';
	$u = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', (string)$hang));
	if(strpos($u, 'D') !== false) return 'CE';
	return $h !== '' ? $h : (string)$hang;
}

/* Số giấy tự sinh: "Mã HV - hạng quy đổi" (cho phép sửa tay sau đó). */
function dt_htkh_so_giay($maHv, $hang)
{
	$maHv = trim((string)$maHv);
	if($maHv === '') return '';
	return $maHv.' - '.dt_htkh_hang_quydoi($hang);
}

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
		foreach($d->rawQuery("select id_khoa, cccd, so_giay, ngay_cap, ghi_chu, hang_giay, nguoi_ky, ngay_ky from #_dt_htkh where id_khoa in ($in)") as $r)
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

	$soGiay = trim($_POST['so_giay'] ?? '');
	if($soGiay === '') $soGiay = dt_htkh_so_giay($hv['ma_hv'], $hv['hang']);
	$data = array(
		'id_khoa' => $idKhoa, 'cccd' => $hv['cccd'], 'ma_hv' => $hv['ma_hv'],
		'so_giay' => htmlspecialchars($soGiay, ENT_QUOTES, 'UTF-8'),
		'hang_giay' => dt_htkh_hang_quydoi($hv['hang']),
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

/* Cấp giấy HTKH hàng loạt: sinh số giấy + ghi người ký/ngày ký cho HV đủ điều kiện (hoặc tất cả). */
function dt_htkh_cap_giay()
{
	global $d, $func;
	$idKhoa = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$back = "index.php?com=daotao&act=htkh&id_khoa=".$idKhoa;
	if(!$idKhoa) $func->transfer("Vui lòng chọn khóa", $back, false);
	$nguoiKy = trim($_POST['nguoi_ky'] ?? '');
	$ngayKy = trim($_POST['ngay_ky'] ?? '');
	if($ngayKy === '') $ngayKy = date('d/m/Y');
	$chiDat = empty($_POST['tat_ca']);
	$hvs = $d->rawQuery("select * from #_dt_hocvien where id_khoa = ? order by hoten asc", array($idKhoa));
	$existing = array();
	foreach($d->rawQuery("select cccd, so_giay from #_dt_htkh where id_khoa = ?", array($idKhoa)) as $r) $existing[$r['cccd']] = $r;
	$d->startTransaction();
	$n = 0;
	foreach($hvs as $hv)
	{
		if($chiDat) { $s = dt_student_summary($hv); if(empty($s['du_dieu_kien'])) continue; }
		$ex = isset($existing[$hv['cccd']]) ? $existing[$hv['cccd']] : null;
		$soGiay = ($ex && trim((string)$ex['so_giay']) !== '') ? $ex['so_giay'] : dt_htkh_so_giay($hv['ma_hv'], $hv['hang']);
		$data = array(
			'id_khoa' => $idKhoa, 'cccd' => $hv['cccd'], 'ma_hv' => $hv['ma_hv'],
			'so_giay' => $soGiay, 'hang_giay' => dt_htkh_hang_quydoi($hv['hang']),
			'ngay_cap' => $ngayKy, 'ngay_ky' => $ngayKy, 'nguoi_ky' => $nguoiKy, 'nguoi_nhap' => dt_username(),
		);
		$row = $d->rawQueryOne("select id from #_dt_htkh where id_khoa = ? and cccd = ? limit 0,1", array($idKhoa, $hv['cccd']));
		if($row && $row['id']) { $d->where('id', $row['id']); $d->update('dt_htkh', $data); }
		else { $data['ngaytao'] = time(); $d->insert('dt_htkh', $data); }
		$n++;
	}
	$d->commit();
	dt_audit('capgiay', 'htkh', $n, 'khoa='.$idKhoa.' nguoi_ky='.$nguoiKy);
	$func->transfer("Đã cấp giấy HTKH cho $n học viên".($chiDat ? " đủ điều kiện" : ""), $back);
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

/* In giấy xác nhận HTKH hàng loạt (HTML, mỗi học viên 1 trang) — chỉ in HV đã được cấp giấy. */
function dt_htkh_print()
{
	global $d, $func;
	$idKhoa = (int)dt_req('id_khoa', 0);
	if(!$idKhoa) $func->transfer('Vui lòng chọn khóa để in giấy', 'index.php?com=daotao&act=htkh', false);
	$khoa = $d->rawQueryOne("select * from #_dt_khoa where id = ? limit 0,1", array($idKhoa));
	if(!$khoa) $func->transfer('Khóa không tồn tại', 'index.php?com=daotao&act=htkh', false);
	$hvs = $d->rawQuery("select h.cccd, h.ma_hv, h.hoten, h.hang, h.ngaysinh from #_dt_hocvien h where h.id_khoa = ? order by h.hoten asc", array($idKhoa));
	$byCccd = array();
	foreach($d->rawQuery("select cccd, so_giay, hang_giay, ngay_ky, nguoi_ky, ngay_cap from #_dt_htkh where id_khoa = ?", array($idKhoa)) as $r) $byCccd[$r['cccd']] = $r;

	$thoiGian = trim((string)(isset($khoa['thoi_gian_hoc']) ? $khoa['thoi_gian_hoc'] : ''));
	if($thoiGian === '' && !empty($khoa['ngay_khaigiang']) && !empty($khoa['ngay_manhoa']))
		$thoiGian = date('d/m/Y', strtotime($khoa['ngay_khaigiang'])).' - '.date('d/m/Y', strtotime($khoa['ngay_manhoa']));
	$tenKhoa = trim((string)($khoa['ten_khoa'] !== '' ? $khoa['ten_khoa'] : $khoa['ma_khoa']));
	$e = function($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };

	if(ob_get_length()) ob_end_clean();
	header('Content-Type: text/html; charset=utf-8');
	echo '<!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Giấy xác nhận HTKH — '.$e($tenKhoa).'</title>';
	echo '<style>
		*{box-sizing:border-box} body{font-family:"Times New Roman",serif;margin:0;color:#111}
		.toolbar{position:sticky;top:0;background:#f1f5f9;border-bottom:1px solid #cbd5e1;padding:10px 16px;text-align:center}
		.toolbar button,.toolbar a{font-size:14px;padding:6px 14px;border:1px solid #2563eb;background:#2563eb;color:#fff;border-radius:4px;cursor:pointer;text-decoration:none;margin:0 4px}
		.toolbar a.sec{background:#fff;color:#2563eb}
		.cert{width:210mm;min-height:148mm;margin:10px auto;padding:18mm 20mm;background:#fff;page-break-after:always}
		.c-top{display:flex;justify-content:space-between;font-size:13px;text-align:center}
		.c-top .b{font-weight:700}
		.c-title{text-align:center;margin:22px 0 6px}
		.c-title h1{font-size:20px;margin:0;letter-spacing:.5px}
		.c-title .sub{font-size:14px;margin-top:4px}
		.c-no{text-align:center;font-style:italic;margin-bottom:18px}
		.c-body{font-size:15px;line-height:2}
		.c-body .lbl{display:inline-block;min-width:150px}
		.c-sign{margin-top:26px;display:flex;justify-content:flex-end}
		.c-sign .box{text-align:center;min-width:240px}
		.c-sign .role{font-weight:700}
		.c-sign .hint{font-style:italic;font-size:13px}
		@media print{.toolbar{display:none}.cert{margin:0;box-shadow:none}}
	</style></head><body>';
	echo '<div class="toolbar"><button onclick="window.print()">In / Lưu PDF</button> <a class="sec" href="index.php?com=daotao&act=htkh&id_khoa='.(int)$idKhoa.'">Quay lại</a></div>';

	$count = 0;
	foreach($hvs as $hv)
	{
		$h = isset($byCccd[$hv['cccd']]) ? $byCccd[$hv['cccd']] : null;
		if(!$h || trim((string)$h['so_giay']) === '') continue;
		$count++;
		$ns = !empty($hv['ngaysinh']) ? $hv['ngaysinh'] : '';
		$hangGiay = trim((string)$h['hang_giay']) !== '' ? $h['hang_giay'] : dt_htkh_hang_quydoi($hv['hang']);
		$ngayKy = trim((string)$h['ngay_ky']) !== '' ? $h['ngay_ky'] : $h['ngay_cap'];
		echo '<div class="cert">
			<div class="c-top">
				<div><div>SỞ GIAO THÔNG VẬN TẢI</div><div class="b">TRUNG TÂM GDNN BÁCH VIỆT</div></div>
				<div><div class="b">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div><div>Độc lập - Tự do - Hạnh phúc</div></div>
			</div>
			<div class="c-title"><h1>GIẤY XÁC NHẬN</h1><div class="sub">HOÀN THÀNH KHÓA ĐÀO TẠO</div></div>
			<div class="c-no">Số: '.$e($h['so_giay']).'</div>
			<div class="c-body">
				<div><span class="lbl">Họ và tên:</span> <strong>'.$e($hv['hoten']).'</strong></div>
				<div><span class="lbl">Ngày sinh:</span> '.$e($ns).'</div>
				<div><span class="lbl">Hạng đào tạo:</span> '.$e($hangGiay).'</div>
				<div><span class="lbl">Khóa:</span> '.$e($tenKhoa).'</div>
				<div><span class="lbl">Thời gian học:</span> '.$e($thoiGian).'</div>
			</div>
			<div class="c-sign"><div class="box">
				<div class="hint">'.($ngayKy !== '' ? '....., '.$e($ngayKy) : '....., ngày ... tháng ... năm ...').'</div>
				<div class="role">NGƯỜI KÝ</div>
				<div style="height:56px"></div>
				<div><strong>'.$e($h['nguoi_ky']).'</strong></div>
			</div></div>
		</div>';
	}
	if($count === 0) echo '<div style="text-align:center;padding:40px;color:#64748b">Chưa có học viên nào được cấp giấy. Hãy bấm "Cấp giấy" trước khi in.</div>';
	echo '</body></html>';
	exit();
}
