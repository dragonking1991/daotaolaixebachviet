<?php
if(!defined('SOURCES')) die("Error");

/* ============================ Xuất danh sách (bảng kê theo GV / toàn bộ) ============================ */

function xd_xuat_bangke_giao_vien()
{
	global $d, $func;
	$gvKey = isset($_REQUEST['gv_key']) ? trim((string)$_REQUEST['gv_key']) : '';
	$ky = isset($_REQUEST['ky']) ? trim((string)$_REQUEST['ky']) : '';
	$fromDate = (isset($_REQUEST['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['from_date'])) ? $_REQUEST['from_date'] : '';
	$toDate = (isset($_REQUEST['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['to_date'])) ? $_REQUEST['to_date'] : '';
	if($gvKey === '') $func->transfer("Không xác định được giáo viên cần xuất.", "index.php?com=xangdau&act=loc", false);
	list($selected, $summary) = xd_run_algorithm($d, $ky, $fromDate, $toDate);
	$teacherSelected = array();
	foreach($selected as $student) if($student['gv_key'] === $gvKey) $teacherSelected[] = $student;
	xd_export_bangke_excel($d, 0, date('Y-m-d'), $ky, $gvKey, $teacherSelected, $fromDate, $toDate);
}

function xd_xuat_tat_ca_bang_ke()
{
	global $d;
	$ky = isset($_REQUEST['ky']) ? trim((string)$_REQUEST['ky']) : '';
	$fromDate = (isset($_REQUEST['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['from_date'])) ? $_REQUEST['from_date'] : '';
	$toDate = (isset($_REQUEST['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['to_date'])) ? $_REQUEST['to_date'] : '';
	list($selected, $summary) = xd_run_algorithm($d, $ky, $fromDate, $toDate);
	xd_export_bangke_excel($d, 0, date('Y-m-d'), $ky, '', $selected, $fromDate, $toDate, true);
}

function xd_xuat_toan_bo_danh_sach_hoc_vien()
{
	global $d, $func;
	
	require_once LIBRARIES.'PHPExcel.php';
	
	$ky = isset($_REQUEST['ky']) ? trim((string)$_REQUEST['ky']) : '';
	$fromDate = (isset($_REQUEST['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['from_date'])) ? $_REQUEST['from_date'] : '';
	$toDate = (isset($_REQUEST['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['to_date'])) ? $_REQUEST['to_date'] : '';
	
	list($selected, $summary) = xd_run_algorithm($d, $ky, $fromDate, $toDate);
	
	if(empty($selected)) $func->transfer("Không có học viên nào để xuất.", "index.php?com=xangdau&act=loc", false);
	
	$setting = $d->rawQueryOne("select tenvi from #_setting limit 0,1");
	$companyName = (!empty($setting['tenvi'])) ? (function_exists('mb_strtoupper') ? mb_strtoupper($setting['tenvi'], 'UTF-8') : strtoupper($setting['tenvi'])) : 'TRUNG TÂM GIÁO DỤC NGHỀ NGHIỆP';
	
	$objPHPExcel = new PHPExcel();
	$ws = $objPHPExcel->getActiveSheet();
	
	$ws->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(10);
	$ws->getDefaultRowDimension()->setRowHeight(18);
	$ws->setShowGridlines(false);
	
	// Điều chỉnh độ rộng cột để vừa 1 trang A4
	foreach(array('A'=>4, 'B'=>12, 'C'=>18, 'D'=>12, 'E'=>10, 'F'=>10, 'G'=>14, 'H'=>8) as $column => $width)
		$ws->getColumnDimension($column)->setWidth($width);
	
	$ws->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
	$ws->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
	$ws->getPageMargins()->setTop(0.5)->setRight(0.5)->setBottom(0.5)->setLeft(0.5);
	
	$tableBorder = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => '000000'))));
	
	// Tiêu đề
	$r = 1;
	$ws->setCellValue('A'.$r, $companyName); $ws->mergeCells('A'.$r.':H'.$r);
	$ws->getStyle('A'.$r)->getFont()->setBold(true)->setSize(12);
	$ws->getStyle('A'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	$r++; $ws->setCellValue('A'.$r, 'DANH SÁCH HỌC VIÊN THANH TOÁN CHI PHÍ XĂNG DẦU');
	$ws->mergeCells('A'.$r.':H'.$r);
	$ws->getStyle('A'.$r)->getFont()->setBold(true)->setSize(11);
	$ws->getStyle('A'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	$r++; $ws->setCellValue('A'.$r, 'Ngày: '.date('d/m/Y'));
	if($ky !== '') $ws->setCellValue('D'.$r, 'Kỳ: '.$ky);
	
	$r += 2;
	
	// Header bảng
	$hdRow = $r;
	$headers = array('STT', 'Khóa', 'Họ tên', 'CCCD', 'Năm sinh', 'Nhóm', 'Giáo viên', 'Số tiền');
	$col = 'A';
	foreach($headers as $h) { $ws->setCellValue($col.$r, $h); $col++; }
	$ws->getStyle('A'.$r.':H'.$r)->getFont()->setBold(true);
	$ws->getStyle('A'.$r.':H'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	$r++;
	$stt = 1;
	$tongTien = 0.0;
	foreach($selected as $hv)
	{
		$namSinh = (!empty($hv['ngaysinh']) && strtotime($hv['ngaysinh']) !== false) ? date('Y', strtotime($hv['ngaysinh'])) : '';
		$ws->setCellValueExplicit('A'.$r, $stt, PHPExcel_Cell_DataType::TYPE_NUMERIC);
		$ws->setCellValue('B'.$r, isset($hv['khoa']) ? $hv['khoa'] : '');
		$ws->setCellValue('C'.$r, $hv['ho_ten']);
		$ws->setCellValueExplicit('D'.$r, $hv['cccd'], PHPExcel_Cell_DataType::TYPE_STRING);
		$ws->setCellValue('E'.$r, $namSinh);
		$ws->setCellValue('F'.$r, $hv['nhom']);
		$ws->setCellValue('G'.$r, isset($hv['gv_hoten']) && $hv['gv_hoten'] !== '' ? $hv['gv_hoten'] : $hv['gv_key']);
		$ws->setCellValueExplicit('H'.$r, (int)round((float)$hv['so_tien_thanh_toan']), PHPExcel_Cell_DataType::TYPE_NUMERIC);
		$tongTien += (float)$hv['so_tien_thanh_toan'];
		$stt++; $r++;
	}
	
	$ws->setCellValue('C'.$r, 'Tổng cộng');
	$ws->getStyle('C'.$r)->getFont()->setBold(true);
	$ws->setCellValueExplicit('H'.$r, (int)round($tongTien), PHPExcel_Cell_DataType::TYPE_NUMERIC);
	$ws->getStyle('H'.$r)->getFont()->setBold(true);
	
	$ws->getStyle('A'.$hdRow.':H'.$r)->applyFromArray($tableBorder);
	$ws->getStyle('H'.($hdRow+1).':H'.$r)->getNumberFormat()->setFormatCode('#,##0');
	$ws->getStyle('A'.$hdRow.':H'.$r)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$ws->getStyle('A'.$hdRow.':A'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$ws->getStyle('D'.$hdRow.':F'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$ws->getStyle('H'.$hdRow.':H'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	
	$objPHPExcel->setActiveSheetIndex(0);
	$filename = 'danh_sach_hoc_vien_xd_'.date('Ymd_His').'.xlsx';
	
	while(ob_get_level() > 0) ob_end_clean();
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment; filename="'.$filename.'"');
	header('Cache-Control: max-age=0');
	
	$writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	$writer->save('php://output');
	exit;
}
