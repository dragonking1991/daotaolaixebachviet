<?php
if(!defined('SOURCES')) die("Error");

/* ============================================================
 * Xuất Excel dùng chung cho toàn bộ phân hệ đào tạo.
 * Mỗi mục dùng chung bộ lọc với danh sách (dt_*_where) nên
 * file xuất ra khớp đúng kết quả đang xem/lọc trên màn hình.
 * ============================================================ */

/* Đọc 1 tham số lọc dạng chuỗi đã trim. */
function dt_req($key, $default = '')
{
	return isset($_REQUEST[$key]) && $_REQUEST[$key] !== '' ? trim((string)$_REQUEST[$key]) : $default;
}

/* Tạo link "Xuất Excel" giữ nguyên bộ lọc hiện tại (dùng trong template). */
function dt_export_link($act, $keys)
{
	$q = array('com' => 'daotao', 'act' => $act);
	foreach($keys as $k)
	{
		if(!isset($_REQUEST[$k])) continue;
		$v = trim((string)$_REQUEST[$k]);
		if($v === '' || ($k === 'id_khoa' && $v === '0')) continue;
		$q[$k] = $v;
	}
	return 'index.php?'.htmlspecialchars(http_build_query($q));
}

/* Xuất mảng dòng (mỗi dòng là mảng ô) ra file .xlsx rồi kết thúc.
 * $textCols: danh sách chỉ số cột (0-based) ép kiểu chuỗi để giữ số 0 đầu (CCCD, biển số...). */
function dt_xlsx_simple($title, $filename, $headers, $rows, $textCols = array(), $redCells = array())
{
	require_once LIBRARIES.'PHPExcel.php';
	$objPHPExcel = new PHPExcel();
	$sheet = $objPHPExcel->getActiveSheet();
	$sheet->setTitle(mb_substr($title, 0, 28));
	$textCols = array_flip($textCols);

	$colCount = count($headers);
	for($c = 0; $c < $colCount; $c++)
	{
		$letter = PHPExcel_Cell::stringFromColumnIndex($c);
		$sheet->setCellValue($letter.'1', $headers[$c]);
		$sheet->getStyle($letter.'1')->getFont()->setBold(true);
		$sheet->getStyle($letter.'1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2F9');
	}

	$r = 2;
	foreach($rows as $row)
	{
		for($c = 0; $c < $colCount; $c++)
		{
			$letter = PHPExcel_Cell::stringFromColumnIndex($c);
			$val = isset($row[$c]) ? $row[$c] : '';
			if(isset($textCols[$c]))
				$sheet->setCellValueExplicit($letter.$r, (string)$val, PHPExcel_Cell_DataType::TYPE_STRING);
			else
				$sheet->setCellValue($letter.$r, $val);
		}
		$r++;
	}

	// Tô đỏ các ô "chưa đạt": $redCells chứa key "dataRowIndex,colIndex" (0-based theo $rows & cột).
	if(!empty($redCells))
	{
		foreach($rows as $ri => $row)
		{
			for($c = 0; $c < $colCount; $c++)
			{
				if(empty($redCells[$ri.','.$c])) continue;
				$letter = PHPExcel_Cell::stringFromColumnIndex($c);
				$cell = $letter.($ri + 2);
				$sheet->getStyle($cell)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('F8D7DA');
				$sheet->getStyle($cell)->getFont()->getColor()->setRGB('C0392B');
			}
		}
	}

	for($c = 0; $c < $colCount; $c++)
		$sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($c))->setAutoSize(true);
	$sheet->freezePane('A2');

	if(ob_get_length()) ob_end_clean();
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment;filename="'.$filename.'"');
	header('Cache-Control: max-age=0');
	$writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	$writer->save('php://output');
	exit();
}

/* -------- Bộ lọc dùng chung (danh sách + xuất) -------- */

function dt_khoa_where()
{
	global $d;
	$where = ""; $params = array();
	$kw = dt_req('keyword');
	if($kw !== '')
	{
		$where .= " and (k.ma_khoa like ? or k.ten_khoa like ? or k.hang like ?)";
		$like = '%'.$kw.'%';
		$params[] = $like; $params[] = $like; $params[] = $like;
	}
	if(dt_req('hang') !== '') { $where .= " and k.hang = ?"; $params[] = dt_norm_hang(dt_req('hang')); }
	if(dt_req('he') !== '') { $where .= " and k.he_daotao like ?"; $params[] = '%'.dt_req('he').'%'; }
	if(dt_req('tu_ngay') !== '') { $where .= " and k.ngay_khaigiang >= ?"; $params[] = dt_req('tu_ngay'); }
	if(dt_req('toi_ngay') !== '') { $where .= " and k.ngay_khaigiang <= ?"; $params[] = dt_req('toi_ngay'); }
	return array($where, $params);
}

function dt_hocvien_where()
{
	global $d;
	$where = ""; $params = array();
	$idKhoa = (int)dt_req('id_khoa', 0);
	if($idKhoa) { $where .= " and h.id_khoa = ?"; $params[] = $idKhoa; }
	$kw = dt_req('keyword');
	if($kw !== '')
	{
		$where .= " and (h.hoten like ? or h.cccd like ? or h.ma_hv like ?)";
		$like = '%'.$kw.'%';
		$params[] = $like; $params[] = $like; $params[] = $like;
	}
	if(dt_req('hang') !== '') { $where .= " and h.hang = ?"; $params[] = dt_norm_hang(dt_req('hang')); }
	if(dt_req('gv') !== '') { $where .= " and h.gv_key = ?"; $params[] = dt_gv_key(dt_req('gv')); }
	if(dt_req('he') !== '') { $where .= " and h.he_daotao like ?"; $params[] = '%'.dt_req('he').'%'; }
	return array($where, $params);
}

function dt_xe_where()
{
	global $d;
	$where = ""; $params = array();
	$kw = dt_req('keyword');
	if($kw !== '')
	{
		$where .= " and (bien_so like ? or gv_hoten like ? or so_khung like ? or so_may like ?)";
		$like = '%'.$kw.'%';
		$params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
	}
	if(dt_req('hang') !== '')
	{
		$hang = dt_norm_hang(dt_req('hang'));
		if($hang === 'CE') $where .= " and hang_xe in ('CE','FC')";
		else { $where .= " and hang_xe = ?"; $params[] = $hang; }
	}
	if(dt_req('loai_xe') !== '') { $where .= " and loai_xe like ?"; $params[] = '%'.dt_req('loai_xe').'%'; }
	if(dt_req('nhan_hieu') !== '') { $where .= " and nhan_hieu like ?"; $params[] = '%'.dt_req('nhan_hieu').'%'; }
	// Mặc định hiển thị tất cả (kể cả đã thanh lý). Lọc theo trạng thái: hoat_dong / da_thanh_ly.
	$tl = dt_req('tt_tl');
	if($tl === 'da_thanh_ly') $where .= " and thanh_ly = 1";
	elseif($tl === 'hoat_dong') $where .= " and (thanh_ly is null or thanh_ly = 0)";
	$expiry = dt_req('expiry');
	$dateExpr = "coalesce(str_to_date(nullif(ngay_hethan,''),'%d/%m/%Y'),str_to_date(nullif(ngay_hethan,''),'%Y-%m-%d'))";
	if($expiry === 'sap_het') $where .= " and $dateExpr >= curdate() and $dateExpr < date_add(curdate(), interval 30 day)";
	elseif($expiry === 'het_han') $where .= " and $dateExpr < curdate()";
	elseif($expiry === 'con_han') $where .= " and $dateExpr >= date_add(curdate(), interval 30 day)";
	return array($where, $params);
}

function dt_giaovien_where()
{
	global $d;
	$where = ""; $params = array();
	$kw = dt_req('keyword');
	if($kw !== '')
	{
		$where .= " and (hoten like ? or cccd like ? or sdt like ?)";
		$like = '%'.$kw.'%';
		$params[] = $like; $params[] = $like; $params[] = $like;
	}
	if(dt_req('gioitinh') !== '') { $where .= " and gioitinh = ?"; $params[] = dt_req('gioitinh'); }
	if(dt_req('hang_gplx') !== '') { $where .= " and hang_gplx like ?"; $params[] = '%'.dt_req('hang_gplx').'%'; }
	if(dt_req('hang') !== '') { $where .= " and hang_daotao_phep like ?"; $params[] = '%'.dt_req('hang').'%'; }
	// Mặc định hiển thị tất cả (kể cả đã thanh lý). Lọc theo trạng thái: hoat_dong / da_thanh_ly.
	$tl = dt_req('tt_tl');
	if($tl === 'da_thanh_ly') $where .= " and thanh_ly = 1";
	elseif($tl === 'hoat_dong') $where .= " and (thanh_ly is null or thanh_ly = 0)";
	$expiry = dt_req('expiry');
	$dateExpr = "coalesce(str_to_date(nullif(ngay_hh_gplx,''),'%d/%m/%Y'),str_to_date(nullif(ngay_hh_gplx,''),'%Y-%m-%d'))";
	if($expiry === 'sap_het') $where .= " and $dateExpr >= curdate() and $dateExpr < date_add(curdate(), interval 30 day)";
	elseif($expiry === 'het_han') $where .= " and $dateExpr < curdate()";
	return array($where, $params);
}

function dt_cabin_where($includeStatus = true)
{
	global $d;
	$where = ""; $params = array();
	$idKhoa = (int)dt_req('id_khoa', 0);
	if($idKhoa) { $where .= " and h.id_khoa = ?"; $params[] = $idKhoa; }
	$kw = dt_req('keyword');
	if($kw !== '')
	{
		$where .= " and (h.hoten like ? or h.cccd like ? or h.ma_hv like ?)";
		$like = '%'.$kw.'%';
		$params[] = $like; $params[] = $like; $params[] = $like;
	}
	$tt = $includeStatus ? dt_req('trang_thai') : '';
	if($tt === 'dat') { $where .= " and c.dat = 1"; }
	elseif($tt === 'chua') { $where .= " and (c.id is null or c.dat = 0)"; }
	if(dt_req('gv') !== '') { $where .= " and h.gv_key = ?"; $params[] = dt_gv_key(dt_req('gv')); }
	return array($where, $params);
}

/* -------- Hàm xuất cho từng mục -------- */

function dt_khoa_export()
{
	global $d;
	list($where, $params) = dt_khoa_where();
	$rows = $d->rawQuery("select k.*, (select count(*) from #_dt_hocvien h where h.id_khoa = k.id) as so_hoc_vien from #_dt_khoa k where k.hienthi >= 0 $where order by k.ngay_khaigiang desc, k.id desc", $params);
	$headers = array('STT','Mã khóa','Tên khóa','Hạng','Ngày khai giảng','Ngày mãn khóa','Hệ đào tạo','Số học viên');
	$out = array(); $stt = 1;
	foreach($rows as $k)
		$out[] = array($stt++, $k['ma_khoa'], $k['ten_khoa'], $k['hang'], dt_export_date($k['ngay_khaigiang']), dt_export_date($k['ngay_manhoa']), $k['he_daotao'], (int)$k['so_hoc_vien']);
	dt_xlsx_simple('Khoa dao tao', 'khoa-dao-tao-'.date('Ymd_His').'.xlsx', $headers, $out, array(1));
}

function dt_hocvien_export()
{
	global $d;
	list($where, $params) = dt_hocvien_where();
	$rows = $d->rawQuery("select h.*, k.ma_khoa, k.ten_khoa from #_dt_hocvien h left join #_dt_khoa k on k.id = h.id_khoa where 1 $where order by substring_index(h.hoten, ' ', -1) asc, h.hoten asc", $params);
	$headers = array('STT','Mã HV','Họ và tên','CCCD','Ngày sinh','Hạng','Khóa','Giáo viên','Người giới thiệu','Hệ đào tạo');
	$out = array(); $stt = 1;
	foreach($rows as $h)
		$out[] = array($stt++, $h['ma_hv'], $h['hoten'], $h['cccd'], $h['ngaysinh'], $h['hang'], $h['ma_khoa'], $h['gv_hoten'], $h['nguoi_gioithieu'], $h['he_daotao']);
	dt_xlsx_simple('Hoc vien', 'hoc-vien-'.date('Ymd_His').'.xlsx', $headers, $out, array(1, 3));
}

function dt_xe_export()
{
	global $d;
	list($where, $params) = dt_xe_where();
	$rows = $d->rawQuery("select * from #_dt_xe where 1 $where order by bien_so asc", $params);
	$headers = array('STT','Biển số','Hạng xe','Hạng đào tạo','Số đăng ký','Số khung','Số máy','Loại xe','Nhãn hiệu','Giáo viên','Ngày HH GPLX tập lái','Trạng thái','Ngày thanh lý','Mã CSĐT','Năm SX','Số GPXTL','Ngày cấp GPXTL','Ngày cấp GCNKĐ','Ngày HH GCNKĐ','Sở hữu','Số IMEI','Số Seri');
	$out = array(); $stt = 1;
	foreach($rows as $x)
	{
		$out[] = array($stt++, $x['bien_so'], dt_norm_hang($x['hang_xe']), $x['hang_dt'], $x['so_dangky'], $x['so_khung'], $x['so_may'], $x['loai_xe'], $x['nhan_hieu'], $x['gv_hoten'], isset($x['ngay_hethan'])?$x['ngay_hethan']:'', !empty($x['thanh_ly'])?'Đã thanh lý':'Đang hoạt động', isset($x['ngay_thanh_ly'])?$x['ngay_thanh_ly']:'', isset($x['ma_csdt'])?$x['ma_csdt']:'', isset($x['nam_sx'])?$x['nam_sx']:'', isset($x['so_gpxtl'])?$x['so_gpxtl']:'', isset($x['ngay_cap_gpxtl'])?$x['ngay_cap_gpxtl']:'', isset($x['ngay_cap_gcnkd'])?$x['ngay_cap_gcnkd']:'', isset($x['ngay_hethan_gcnkd'])?$x['ngay_hethan_gcnkd']:'', isset($x['so_huu'])?$x['so_huu']:'', isset($x['so_imei'])?$x['so_imei']:'', isset($x['so_seri'])?$x['so_seri']:'');
	}
	dt_xlsx_simple('Xe tap lai', 'xe-tap-lai-'.date('Ymd_His').'.xlsx', $headers, $out, array(1, 4, 5, 6));
}

function dt_giaovien_export()
{
	global $d;
	list($where, $params) = dt_giaovien_where();
	$rows = $d->rawQuery("select * from #_dt_giaovien where 1 $where order by substring_index(hoten, ' ', -1) asc, hoten asc", $params);
	$headers = array('STT','Mã CSĐT','Họ và tên','CCCD','Ngày sinh','Giới tính','Điện thoại','Địa chỉ','Hạng GPLX','Số GPLX','Ngày cấp GPLX','Ngày HH GPLX','Hạng ĐT được phép','Loại hình ĐT','Hình thức TD','Ngày TD','Trình độ','Chuyên môn','Sư phạm','Nơi công tác','Số QĐ GCN','Ngày QĐ GCN','Nơi cấp GCN','Ghi chú');
	$out = array(); $stt = 1;
	foreach($rows as $g)
	{
		$gt = $g['gioitinh'] === 'F' ? 'Nữ' : ($g['gioitinh'] === 'M' ? 'Nam' : $g['gioitinh']);
		$out[] = array($stt++, $g['ma_csdt'], $g['hoten'], $g['cccd'], $g['ngaysinh'], $gt, $g['sdt'], $g['dia_chi'], $g['hang_gplx'], $g['so_gplx'], $g['ngay_cap_gplx'], $g['ngay_hh_gplx'], $g['hang_daotao_phep'], $g['loai_hinh_dt'], $g['hinh_thuc_td'], $g['tuyen_dung'], $g['trinh_do'], $g['chuyen_mon'], $g['su_pham'], $g['noi_ct'], isset($g['so_qd_gcn'])?$g['so_qd_gcn']:'', isset($g['ngay_qd_gcn'])?$g['ngay_qd_gcn']:'', isset($g['noi_cap_gcn'])?$g['noi_cap_gcn']:'', isset($g['ghi_chu'])?$g['ghi_chu']:'');
	}
	dt_xlsx_simple('Giao vien', 'giao-vien-'.date('Ymd_His').'.xlsx', $headers, $out, array(3, 6, 9));
}

function dt_cabin_export()
{
	global $d;
	list($where, $params) = dt_cabin_where();
	$rows = $d->rawQuery("select c.*, h.hoten, h.cccd as hv_cccd, h.ma_hv as roster_ma_hv, h.gv_hoten from #_dt_hocvien h left join #_dt_cabin_kq c on (c.id_khoa = h.id_khoa and c.ma_hv = h.ma_hv) where 1 $where order by substring_index(h.hoten, ' ', -1) asc, h.hoten asc", $params);
	$headers = array('STT','Mã HV','Họ và tên','CCCD','Giáo viên','Tổng thời gian','Số nội dung','Kết quả','Ghi chú');
	$out = array(); $stt = 1;
	foreach($rows as $c)
		$out[] = array($stt++, $c['roster_ma_hv'], $c['hoten'], $c['hv_cccd'], $c['gv_hoten'], $c['tong_thoigian'], (int)$c['so_noidung'], empty($c['id']) ? 'Chưa nhập' : ((int)$c['dat'] === 1 ? 'Đạt' : 'Chưa đạt'), $c['ghi_chu']);
	dt_xlsx_simple('Cabin', 'cabin-'.date('Ymd_His').'.xlsx', $headers, $out, array(1, 3));
}

function dt_lythuyet_export()
{
	global $d;
	$idKhoa = (int)dt_req('id_khoa', 0);
	if(!$idKhoa) $GLOBALS['func']->transfer('Vui lòng chọn khóa để xuất lý thuyết', 'index.php?com=daotao&act=lythuyet', false);
	$rk = $d->rawQueryOne("select hang from #_dt_khoa where id = ?", array($idKhoa));
	$dsMon = dt_mon_lythuyet_for_hang($rk ? $rk['hang'] : '');
	$hvs = $d->rawQuery("select * from #_dt_hocvien where id_khoa = ? order by substring_index(hoten, ' ', -1) asc, hoten asc", array($idKhoa));
	$rows = $d->rawQuery("select cccd, mon, tien_do, diem_kt, dat from #_dt_lythuyet where id_khoa = ?", array($idKhoa));
	$byCccd = array();
	foreach($rows as $rw) $byCccd[$rw['cccd']][$rw['mon']] = $rw;

	$kw = dt_mb_lower(dt_req('keyword'));
	$tt = dt_req('trang_thai');
	$gvFilter = dt_gv_key(dt_req('gv'));
	$duLieu = dt_req('du_lieu', 'co');
	if(!in_array($duLieu, array('co', 'tat_ca', 'chua'), true)) $duLieu = 'co';

	$baseCols = 6; // STT, Mã HV, Họ tên, CCCD, Hạng, Giáo viên
	$tongMon = count($dsMon);
	$ketLuanCol = $baseCols + $tongMon * 3; // cột Kết luận LT

	$out = array(); $stt = 1; $redCells = array();
	foreach($hvs as $hv)
	{
		if($kw !== '' && strpos(dt_mb_lower($hv['hoten'].' '.$hv['cccd'].' '.$hv['ma_hv']), $kw) === false) continue;
		if($gvFilter !== '' && (string)$hv['gv_key'] !== $gvFilter) continue;
		$monData = isset($byCccd[$hv['cccd']]) ? $byCccd[$hv['cccd']] : array();
		if($duLieu === 'co' && empty($monData)) continue;
		if($duLieu === 'chua' && !empty($monData)) continue;
		$soDat = 0; $cells = array(); $redRel = array(); $mi = 0;
		foreach($dsMon as $mk => $lbl)
		{
			$m = isset($monData[$mk]) ? $monData[$mk] : null;
			$datMon = ($m && dt_lythuyet_dat($m['tien_do'], $m['diem_kt'], $mk));
			if($datMon) $soDat++;
			$cells[] = $m ? (float)$m['tien_do'] : '';
			$cells[] = $m ? (float)$m['diem_kt'] : '';
			$cells[] = !$m ? 'Chưa có dữ liệu' : ($datMon ? 'Đạt' : 'Chưa đạt');
			if(!$datMon) $redRel[] = $baseCols + $mi * 3 + 2;
			$mi++;
		}
		$datLT = ($soDat >= $tongMon);
		$ketLuan = $datLT ? 'Đạt' : 'Chưa đủ ('.$soDat.'/'.$tongMon.')';
		if($tt === 'dat' && !$datLT) continue;
		if($tt === 'chua' && $datLT) continue;
		$out[] = array_merge(array($stt++, $hv['ma_hv'], $hv['hoten'], $hv['cccd'], $hv['hang'], $hv['gv_hoten']), $cells, array($ketLuan));
		$ri = count($out) - 1;
		foreach($redRel as $cidx) $redCells[$ri.','.$cidx] = true;
		if(!$datLT) $redCells[$ri.','.$ketLuanCol] = true;
	}

	// Header 2 tầng: tên môn gộp (merge) + 3 cột con Tiến độ / Kiểm tra / Kết quả.
	require_once LIBRARIES.'PHPExcel.php';
	$objPHPExcel = new PHPExcel();
	$sheet = $objPHPExcel->getActiveSheet();
	$sheet->setTitle('Ly thuyet');
	$colLetter = function($c) { return PHPExcel_Cell::stringFromColumnIndex($c); };
	$headStyle = function($range) use ($sheet) {
		$sheet->getStyle($range)->getFont()->setBold(true);
		$sheet->getStyle($range)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2F9');
		$sheet->getStyle($range)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
	};

	$fixed = array('STT', 'Mã HV', 'Họ và tên', 'CCCD', 'Hạng', 'Giáo viên');
	foreach($fixed as $c => $label)
	{
		$L = $colLetter($c);
		$sheet->setCellValue($L.'1', $label);
		$sheet->mergeCells($L.'1:'.$L.'2');
		$headStyle($L.'1:'.$L.'2');
	}
	$mi = 0;
	foreach($dsMon as $lbl)
	{
		$c0 = $baseCols + $mi * 3;
		$L0 = $colLetter($c0); $L2 = $colLetter($c0 + 2);
		$sheet->setCellValue($L0.'1', $lbl);
		$sheet->mergeCells($L0.'1:'.$L2.'1');
		$headStyle($L0.'1:'.$L2.'1');
		$sheet->setCellValue($L0.'2', 'Tiến độ');
		$sheet->setCellValue($colLetter($c0 + 1).'2', 'Kiểm tra');
		$sheet->setCellValue($L2.'2', 'Kết quả');
		$headStyle($L0.'2:'.$L2.'2');
		$mi++;
	}
	$LK = $colLetter($ketLuanCol);
	$sheet->setCellValue($LK.'1', 'Kết luận LT');
	$sheet->mergeCells($LK.'1:'.$LK.'2');
	$headStyle($LK.'1:'.$LK.'2');

	$colCount = $ketLuanCol + 1;
	$textCols = array(1 => true, 3 => true); // Mã HV, CCCD
	$r = 3;
	foreach($out as $row)
	{
		for($c = 0; $c < $colCount; $c++)
		{
			$L = $colLetter($c);
			$val = isset($row[$c]) ? $row[$c] : '';
			if(isset($textCols[$c])) $sheet->setCellValueExplicit($L.$r, (string)$val, PHPExcel_Cell_DataType::TYPE_STRING);
			else $sheet->setCellValue($L.$r, $val);
		}
		$r++;
	}
	foreach($out as $ri => $row)
	{
		for($c = 0; $c < $colCount; $c++)
		{
			if(empty($redCells[$ri.','.$c])) continue;
			$cell = $colLetter($c).($ri + 3);
			$sheet->getStyle($cell)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('F8D7DA');
			$sheet->getStyle($cell)->getFont()->getColor()->setRGB('C0392B');
		}
	}
	for($c = 0; $c < $colCount; $c++) $sheet->getColumnDimension($colLetter($c))->setAutoSize(true);
	$sheet->freezePane('A3');

	if(ob_get_length()) ob_end_clean();
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment;filename="ly-thuyet-'.date('Ymd_His').'.xlsx"');
	header('Cache-Control: max-age=0');
	$writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	$writer->save('php://output');
	exit();
}

function dt_hinh_export()
{
	global $d;
	$idKhoa = (int)dt_req('id_khoa', 0);
	if(!$idKhoa) $GLOBALS['func']->transfer('Vui lòng chọn khóa để xuất', 'index.php?com=daotao&act=hinh', false);
	$sql = "select h.cccd, h.hoten, h.hang, h.ma_hv, h.gv_hoten, th.gio, th.km, th.dat_override from #_dt_hocvien h "
		. "left join #_dt_thuchanh_hinh th on (th.id_khoa = h.id_khoa and th.cccd = h.cccd) where h.id_khoa = ? order by substring_index(h.hoten, ' ', -1) asc, h.hoten asc";
	$rows = $d->rawQuery($sql, array($idKhoa));
	$kw = dt_mb_lower(dt_req('keyword'));
	$tt = dt_req('trang_thai');
	$gvFilter = dt_gv_key(dt_req('gv'));
	$headers = array('STT','Mã HV','Họ và tên','CCCD','Hạng','Giáo viên','Giờ','Km','Kết quả');
	$out = array(); $stt = 1;
	foreach($rows as $rw)
	{
		if($kw !== '' && strpos(dt_mb_lower($rw['hoten'].' '.$rw['cccd'].' '.$rw['ma_hv']), $kw) === false) continue;
		if($gvFilter !== '' && dt_gv_key($rw['gv_hoten']) !== $gvFilter) continue;
		$dat = dt_hinh_dat($rw['hang'], $rw['gio'], $rw['km'], $rw['dat_override']);
		if($tt === 'dat' && !$dat) continue;
		if($tt === 'chua' && $dat) continue;
		$out[] = array($stt++, $rw['ma_hv'], $rw['hoten'], $rw['cccd'], $rw['hang'], $rw['gv_hoten'], (float)$rw['gio'], (float)$rw['km'], $dat ? 'Đạt' : 'Chưa đạt');
	}
	dt_xlsx_simple('Thuc hanh hinh', 'thuc-hanh-hinh-'.date('Ymd_His').'.xlsx', $headers, $out, array(1, 3));
}

function dt_dat_export()
{
	global $d;
	$idKhoa = (int)dt_req('id_khoa', 0);
	if(!$idKhoa) $GLOBALS['func']->transfer('Vui lòng chọn khóa để xuất', 'index.php?com=daotao&act=dat', false);
	$hvs = $d->rawQuery("select cccd, ma_hv, hoten, hang, gv_hoten from #_dt_hocvien where id_khoa = ? order by substring_index(hoten, ' ', -1) asc, hoten asc", array($idKhoa));
	$kw = dt_mb_lower(dt_req('keyword'));
	$tt = dt_req('trang_thai');
	$headers = array('STT','Mã HV','Họ và tên','CCCD','Hạng','Giáo viên','Số phiên','Tổng giờ (A)','Giờ đêm (B)','Giờ tự động (C)','Giờ số sàn (D)','Km (E)','Kết quả','Nội dung thiếu');
	$out = array(); $stt = 1;
	foreach($hvs as $hv)
	{
		if($kw !== '' && strpos(dt_mb_lower($hv['hoten'].' '.$hv['cccd'].' '.$hv['ma_hv']), $kw) === false) continue;
		$agg = dt_dat_aggregate($hv['cccd'], $idKhoa);
		list($dat, $thieu) = dt_dat_danhgia($hv['hang'], $agg);
		if(dt_dat_override_get($idKhoa, $hv['cccd']) === 1) { $dat = 1; $thieu = array(); }
		if($tt === 'dat' && !$dat) continue;
		if($tt === 'chua' && $dat) continue;
		$rPhien = $d->rawQueryOne("select count(*) as c from #_dt_dat_phien where id_khoa = ? and cccd = ?", array($idKhoa, $hv['cccd']));
		$out[] = array($stt++, $hv['ma_hv'], $hv['hoten'], $hv['cccd'], $hv['hang'], $hv['gv_hoten'], (int)($rPhien ? $rPhien['c'] : 0),
			$agg['a'], $agg['b'], $agg['c'], $agg['d'], $agg['e'], $dat ? 'Đủ điều kiện' : 'Chưa đủ', implode('; ', $thieu));
	}
	dt_xlsx_simple('DAT', 'dat-'.date('Ymd_His').'.xlsx', $headers, $out, array(1, 3));
}

/* Định dạng ngày an toàn cho Excel (bỏ 0000-00-00 / rỗng). */
function dt_export_date($v)
{
	$v = trim((string)$v);
	if($v === '' || strpos($v, '0000-00-00') === 0) return '';
	$ts = strtotime($v);
	return $ts ? date('d/m/Y', $ts) : $v;
}
