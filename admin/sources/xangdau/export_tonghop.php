<?php
if(!defined('SOURCES')) die("Error");

/* ============================ Xuất file tổng hợp thanh toán theo giáo viên ============================ */

function xd_xuat_tong_hop_giao_vien()
{
	global $d, $func;

	require_once LIBRARIES.'PHPExcel.php';

	$ky = isset($_REQUEST['ky']) ? trim((string)$_REQUEST['ky']) : '';
	$fromDate = (isset($_REQUEST['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['from_date'])) ? $_REQUEST['from_date'] : '';
	$toDate = (isset($_REQUEST['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['to_date'])) ? $_REQUEST['to_date'] : '';

	list($selected, $summary) = xd_run_algorithm($d, $ky, $fromDate, $toDate);

	$rows = array();
	foreach($summary as $g) { if((int)$g['so_hv_chon'] > 0) $rows[] = $g; }
	if(empty($rows)) $func->transfer("Không có giáo viên nào đủ điều kiện để xuất file tổng hợp.", "index.php?com=xangdau&act=loc", false);

	usort($rows, function($a, $b) { return strcmp((string)$a['gv_hoten'], (string)$b['gv_hoten']); });

	$setting = $d->rawQueryOne("select tenvi from #_setting limit 0,1");
	$companyName = (!empty($setting['tenvi'])) ? (function_exists('mb_strtoupper') ? mb_strtoupper($setting['tenvi'], 'UTF-8') : strtoupper($setting['tenvi'])) : 'TRUNG TÂM GIÁO DỤC NGHỀ NGHIỆP';

	$objPHPExcel = new PHPExcel();
	$ws = $objPHPExcel->getActiveSheet();

	$ws->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(11);
	$ws->getDefaultRowDimension()->setRowHeight(18);
	$ws->setShowGridlines(false);

	foreach(array('A' => 6, 'B' => 28, 'C' => 12, 'D' => 16, 'E' => 14) as $column => $width)
		$ws->getColumnDimension($column)->setWidth($width);

	$ws->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
	$ws->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
	$ws->getPageMargins()->setTop(0.5)->setRight(0.5)->setBottom(0.5)->setLeft(0.5);

	$tableBorder = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => '000000'))));

	$r = 1;
	$ws->setCellValue('A'.$r, $companyName); $ws->mergeCells('A'.$r.':E'.$r);
	$ws->getStyle('A'.$r)->getFont()->setBold(true)->setSize(13);
	$ws->getStyle('A'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

	$r++;
	$ws->setCellValue('A'.$r, 'THANH TOÁN XĂNG DẦU'.($ky !== '' ? ' '.(function_exists('mb_strtoupper') ? mb_strtoupper($ky, 'UTF-8') : strtoupper($ky)) : ''));
	$ws->mergeCells('A'.$r.':E'.$r);
	$ws->getStyle('A'.$r)->getFont()->setBold(true)->setSize(12);
	$ws->getStyle('A'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

	$r += 2;
	$hdRow = $r;
	$headers = array('STT', 'Giáo viên', 'SL HV TT', 'Số tiền', 'Ghi chú');
	$col = 'A';
	foreach($headers as $h) { $ws->setCellValue($col.$r, $h); $col++; }
	$ws->getStyle('A'.$r.':E'.$r)->getFont()->setBold(true);
	$ws->getStyle('A'.$r.':E'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$ws->getStyle('A'.$r.':E'.$r)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

	$r++;
	$stt = 1;
	$tongHv = 0;
	$tongTien = 0.0;
	foreach($rows as $g)
	{
		$ws->setCellValueExplicit('A'.$r, $stt, PHPExcel_Cell_DataType::TYPE_NUMERIC);
		$ws->setCellValue('B'.$r, $g['gv_hoten'] !== '' ? $g['gv_hoten'] : $g['gv_key']);
		$ws->setCellValueExplicit('C'.$r, (int)$g['so_hv_chon'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
		$ws->setCellValueExplicit('D'.$r, (int)round((float)$g['tong_chi']), PHPExcel_Cell_DataType::TYPE_NUMERIC);
		$ws->setCellValue('E'.$r, '');
		$tongHv += (int)$g['so_hv_chon'];
		$tongTien += (float)$g['tong_chi'];
		$stt++; $r++;
	}

	$ws->setCellValue('B'.$r, 'Tổng cộng');
	$ws->getStyle('B'.$r)->getFont()->setBold(true);
	$ws->setCellValueExplicit('C'.$r, $tongHv, PHPExcel_Cell_DataType::TYPE_NUMERIC);
	$ws->setCellValueExplicit('D'.$r, (int)round($tongTien), PHPExcel_Cell_DataType::TYPE_NUMERIC);
	$ws->getStyle('C'.$r.':D'.$r)->getFont()->setBold(true);

	$ws->getStyle('A'.$hdRow.':E'.$r)->applyFromArray($tableBorder);
	$ws->getStyle('D'.($hdRow + 1).':D'.$r)->getNumberFormat()->setFormatCode('#,##0');
	$ws->getStyle('A'.$hdRow.':E'.$r)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$ws->getStyle('A'.($hdRow + 1).':A'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$ws->getStyle('C'.($hdRow + 1).':D'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

	$r += 2;
	$ws->setCellValue('A'.$r, 'Bằng chữ: '.xd_so_thanh_chu($tongTien).'.');
	$ws->mergeCells('A'.$r.':E'.$r);
	$ws->getStyle('A'.$r)->getFont()->setItalic(true)->setBold(true);

	$r += 2;
	$ws->setCellValue('D'.$r, 'TP. HCM, ngày ... tháng ... năm '.date('Y'));
	$ws->mergeCells('D'.$r.':E'.$r);
	$ws->getStyle('D'.$r)->getFont()->setItalic(true);
	$ws->getStyle('D'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

	$r++;
	$ws->setCellValue('A'.$r, 'Giám đốc');
	$ws->mergeCells('A'.$r.':B'.$r);
	$ws->setCellValue('D'.$r, 'Người lập');
	$ws->mergeCells('D'.$r.':E'.$r);
	$ws->getStyle('A'.$r.':E'.$r)->getFont()->setBold(true);
	$ws->getStyle('A'.$r.':B'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$ws->getStyle('D'.$r.':E'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

	$objPHPExcel->setActiveSheetIndex(0);
	$filename = 'tong_hop_thanh_toan_xd_'.date('Ymd_His').'.xlsx';

	while(ob_get_level() > 0) ob_end_clean();
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment; filename="'.$filename.'"');
	header('Cache-Control: max-age=0');

	$writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	$writer->save('php://output');
	exit;
}
