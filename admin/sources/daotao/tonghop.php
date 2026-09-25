<?php
if(!defined('SOURCES')) die("Error");

/* Đọc bộ lọc tổng hợp từ request. */
function dt_tonghop_filters()
{
	return array(
		'id_khoa' => isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0,
		'tu_ngay' => isset($_REQUEST['tu_ngay']) ? trim($_REQUEST['tu_ngay']) : '',
		'toi_ngay' => isset($_REQUEST['toi_ngay']) ? trim($_REQUEST['toi_ngay']) : '',
		'cccd' => isset($_REQUEST['cccd']) ? dt_normalize_cccd($_REQUEST['cccd']) : '',
		'hoten' => isset($_REQUEST['hoten']) ? trim($_REQUEST['hoten']) : '',
		'hang' => isset($_REQUEST['hang']) ? trim($_REQUEST['hang']) : '',
		'gv' => isset($_REQUEST['gv']) ? trim($_REQUEST['gv']) : '',
	);
}

/* Lấy học viên theo bộ lọc rồi tính tổng hợp 4 module. */
function dt_tonghop_rows($f)
{
	global $d;

	$where = "1"; $params = array();
	if($f['id_khoa']) { $where .= " and h.id_khoa = ?"; $params[] = $f['id_khoa']; }
	if($f['cccd'] !== '') { $where .= " and h.cccd like ?"; $params[] = '%'.$f['cccd'].'%'; }
	if($f['hoten'] !== '') { $where .= " and h.hoten like ?"; $params[] = '%'.$f['hoten'].'%'; }
	if($f['hang'] !== '') { $where .= " and h.hang = ?"; $params[] = dt_norm_hang($f['hang']); }
	if($f['gv'] !== '') { $where .= " and h.gv_key = ?"; $params[] = dt_gv_key($f['gv']); }
	if($f['tu_ngay'] !== '' || $f['toi_ngay'] !== '')
	{
		// Lọc theo khoảng ngày học DAT: chỉ giữ học viên có phiên trong khoảng.
		$sub = "select 1 from #_dt_dat_phien dp where dp.cccd = h.cccd and dp.id_khoa = h.id_khoa";
		if($f['tu_ngay'] !== '') { $sub .= " and dp.ngay_hoc >= ?"; }
		if($f['toi_ngay'] !== '') { $sub .= " and dp.ngay_hoc <= ?"; }
		$where .= " and exists ($sub)";
		if($f['tu_ngay'] !== '') $params[] = $f['tu_ngay'];
		if($f['toi_ngay'] !== '') $params[] = $f['toi_ngay'];
	}

	$hvs = $d->rawQuery("select h.*, k.ma_khoa, k.ten_khoa from #_dt_hocvien h left join #_dt_khoa k on k.id = h.id_khoa where $where order by h.hoten asc", $params);
	$out = array();
	foreach($hvs as $hv)
	{
		$sum = dt_student_summary($hv);
		$out[] = array('hv' => $hv, 'sum' => $sum);
	}
	return $out;
}

function dt_tonghop_list()
{
	global $items, $ds_khoa, $filters, $ds_gv;
	$ds_khoa = dt_khoa_options();
	$filters = dt_tonghop_filters();
	$items = ($filters['id_khoa'] || $filters['cccd'] !== '' || $filters['hoten'] !== '') ? dt_tonghop_rows($filters) : array();
}

function dt_tonghop_export()
{
	global $d;

	$f = dt_tonghop_filters();
	$rows = dt_tonghop_rows($f);

	require_once LIBRARIES.'PHPExcel.php';
	$objPHPExcel = new PHPExcel();
	$sheet = $objPHPExcel->getActiveSheet();
	$sheet->setTitle('Tong hop dao tao');

	$headers = array('STT','Mã HV','Họ và tên','CCCD','Hạng','Khóa','Lý thuyết','Cabin','Th.hành hình','DAT',
		'Tổng giờ H','Tổng km H','Tổng giờ Đ','Tổng km Đ','A','B','C','D','Kết luận');
	$col = 'A';
	foreach($headers as $h)
	{
		$sheet->setCellValue($col.'1', $h);
		$sheet->getStyle($col.'1')->getFont()->setBold(true);
		$col++;
	}

	$r = 2; $stt = 1;
	foreach($rows as $it)
	{
		$hv = $it['hv']; $s = $it['sum']; $agg = $s['dat']['agg'];
		$sheet->setCellValueExplicit('A'.$r, $stt, PHPExcel_Cell_DataType::TYPE_NUMERIC);
		$sheet->setCellValueExplicit('B'.$r, $hv['ma_hv'], PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->setCellValue('C'.$r, $hv['hoten']);
		$sheet->setCellValueExplicit('D'.$r, $hv['cccd'], PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->setCellValue('E'.$r, $hv['hang']);
		$sheet->setCellValue('F'.$r, isset($hv['ma_khoa']) ? $hv['ma_khoa'] : '');
		$sheet->setCellValue('G'.$r, $s['ly_thuyet']['dat'] ? 'Đạt' : 'Chưa đạt');
		$sheet->setCellValue('H'.$r, $s['cabin']['dat'] ? 'Đạt' : 'Chưa đạt');
		$sheet->setCellValue('I'.$r, $s['hinh']['dat'] ? 'Đạt' : 'Chưa đạt');
		$sheet->setCellValue('J'.$r, $s['dat']['dat'] ? 'Đạt' : 'Chưa đạt');
		$sheet->setCellValue('K'.$r, $s['hinh']['gio']);
		$sheet->setCellValue('L'.$r, $s['hinh']['km']);
		$sheet->setCellValue('M'.$r, $agg['a']);
		$sheet->setCellValue('N'.$r, $agg['e']);
		$sheet->setCellValue('O'.$r, $agg['a']);
		$sheet->setCellValue('P'.$r, $agg['b']);
		$sheet->setCellValue('Q'.$r, $agg['c']);
		$sheet->setCellValue('R'.$r, $agg['d']);
		$sheet->setCellValue('S'.$r, $s['du_dieu_kien'] ? 'Đủ điều kiện' : 'Chưa đủ');
		$r++; $stt++;
	}

	foreach(range('A','S') as $c) $sheet->getColumnDimension($c)->setAutoSize(true);

	$filename = 'tonghop-daotao-'.date('Ymd_His').'.xlsx';
	if(ob_get_length()) ob_end_clean();
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment;filename="'.$filename.'"');
	header('Cache-Control: max-age=0');
	$writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	$writer->save('php://output');
	exit();
}
