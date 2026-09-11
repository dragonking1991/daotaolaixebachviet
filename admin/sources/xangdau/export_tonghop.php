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

	$companyName = 'TRUNG TÂM GIÁO DỤC NGHỀ NGHIỆP BÁCH VIỆT';

	$objPHPExcel = new PHPExcel();
	$ws = $objPHPExcel->getActiveSheet();

	$ws->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(11);
	$ws->getDefaultRowDimension()->setRowHeight(18);
	$ws->setShowGridlines(false);

	foreach(array('A' => 6, 'B' => 28, 'C' => 20, 'D' => 16, 'E' => 16) as $column => $width)
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
	$ws->getStyle('A'.$r.':E'.$r)->getAlignment()->setWrapText(true);
	$ws->getRowDimension($r)->setRowHeight(30);

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

function xd_xuat_da_kiem_tra_giao_vien()
{
	global $d, $func;

	require_once LIBRARIES.'PHPExcel.php';

	$fromDate = (isset($_REQUEST['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['from_date'])) ? $_REQUEST['from_date'] : '';
	$toDate = (isset($_REQUEST['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['to_date'])) ? $_REQUEST['to_date'] : '';
	$keyword = isset($_REQUEST['keyword']) ? trim((string)$_REQUEST['keyword']) : '';
	$isPendingApproval = isset($_REQUEST['cho_duyet']) && $_REQUEST['cho_duyet'] === '1';

	if($isPendingApproval)
	{
		$rows = $d->rawQuery(
			"select g.gv_key, max(g.gv_hoten) as gv_hoten, max(g.ngay_kiem_tra) as ngay_kiem_tra from (
				select h.gv_key, max(h.gv_hoten) as gv_hoten, max(h.ngay_kiem_tra) as ngay_kiem_tra
				from #_xd_hoadon h where h.gv_key <> '' and h.da_quyettoan = 0 and h.ke_toan_kiem_tra = 1
				and not exists (select 1 from #_xd_hoadon paid where paid.gv_key = h.gv_key and paid.da_quyettoan = 1) group by h.gv_key
				union all
				select h.gv_key, max(h.gv_hoten) as gv_hoten, max(checked.ngay_kiem_tra) as ngay_kiem_tra
				from #_xd_hocvien h left join #_xd_hoadon checked on checked.gv_key = h.gv_key and checked.da_quyettoan = 0 and checked.ke_toan_kiem_tra = 1
				where h.gv_key <> '' and h.ngay_thanh_toan is null and h.ke_toan_kiem_tra = 1
				and not exists (select 1 from #_xd_hoadon paid where paid.gv_key = h.gv_key and paid.da_quyettoan = 1) group by h.gv_key
			) g group by g.gv_key order by gv_hoten asc"
		);
		$config = getXdConfig($d);
		foreach($rows as &$row)
		{
			$students = $d->rawQuery("select nhom from #_xd_hocvien where gv_key = ? and ngay_thanh_toan is null and ke_toan_kiem_tra = 1", array($row['gv_key']));
			$row['so_hv'] = 0;
			$row['tong_chi'] = 0.0;
			foreach($students as $student)
			{
				$row['so_hv']++;
				$row['tong_chi'] += (float)xdMucTheoNhom($config, $student['nhom']);
			}
		}
		unset($row);
	}
	else
	{
		$where = 'h.gv_key <> "" and h.da_quyettoan = 0 and h.ke_toan_kiem_tra = 1';
		$params = array();
		if($keyword !== '') { $where .= ' and (h.gv_hoten like ? or h.gv_key like ?)'; $params[] = '%'.$keyword.'%'; $params[] = '%'.$keyword.'%'; }
		if($fromDate !== '') { $where .= ' and h.ngay_kiem_tra >= ?'; $params[] = $fromDate; }
		if($toDate !== '') { $where .= ' and h.ngay_kiem_tra <= ?'; $params[] = $toDate; }
		$rows = $d->rawQuery("select h.gv_key, max(h.gv_hoten) as gv_hoten, count(distinct h.id) as so_hd, sum(h.tong_tien) as tong_tien, max(h.ngay_kiem_tra) as ngay_kiem_tra from #_xd_hoadon h where $where group by h.gv_key order by h.gv_hoten asc", $params);
	}

	if(empty($rows)) $func->transfer("Không có giáo viên nào đã kiểm toán.", "index.php?com=xangdau&act=".($isPendingApproval ? 'locDuyet' : 'locDaThanhToan'), false);

	usort($rows, function($a, $b) { return strcmp((string)$a['gv_hoten'], (string)$b['gv_hoten']); });

	$companyName = 'TRUNG TÂM GIÁO DỤC NGHỀ NGHIỆP BÁCH VIỆT';

	$objPHPExcel = new PHPExcel();
	$ws = $objPHPExcel->getActiveSheet();

	$ws->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(11);
	$ws->getDefaultRowDimension()->setRowHeight(18);
	$ws->setShowGridlines(false);

	foreach(array('A' => 6, 'B' => 28, 'C' => 24, 'D' => 16, 'E' => 16) as $column => $width)
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
	$ws->setCellValue('A'.$r, $isPendingApproval ? 'TỔNG HỢP GIÁO VIÊN CHỜ DUYỆT THANH TOÁN' : 'GIÁO VIÊN ĐÃ KIỂM TOÁN XĂNG DẦU');
	$ws->mergeCells('A'.$r.':E'.$r);
	$ws->getStyle('A'.$r)->getFont()->setBold(true)->setSize(12);
	$ws->getStyle('A'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

	$r += 2;
	$hdRow = $r;
	$headers = $isPendingApproval ? array('STT', 'Giáo viên', "Số HV\nthanh toán", 'Tổng chi', 'Ngày kiểm tra') : array('STT', 'Giáo viên', 'Số HĐ', 'Tổng tiền', 'Ngày kiểm tra');
	$col = 'A';
	foreach($headers as $h) { $ws->setCellValue($col.$r, $h); $col++; }
	$ws->getStyle('A'.$r.':E'.$r)->getFont()->setBold(true);
	$ws->getStyle('A'.$r.':E'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$ws->getStyle('A'.$r.':E'.$r)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$ws->getStyle('A'.$r.':E'.$r)->getAlignment()->setWrapText(true);
	$ws->getRowDimension($r)->setRowHeight(36);

	$r++;
	$stt = 1;
	$tongSoLuong = 0;
	$tongTien = 0.0;
	foreach($rows as $g)
	{
		$ws->setCellValueExplicit('A'.$r, $stt, PHPExcel_Cell_DataType::TYPE_NUMERIC);
		$ws->setCellValue('B'.$r, $g['gv_hoten'] !== '' ? $g['gv_hoten'] : $g['gv_key']);
		$soLuong = $isPendingApproval ? (int)$g['so_hv'] : (int)$g['so_hd'];
		$soTien = $isPendingApproval ? (float)$g['tong_chi'] : (float)$g['tong_tien'];
		$ws->setCellValueExplicit('C'.$r, $soLuong, PHPExcel_Cell_DataType::TYPE_NUMERIC);
		$ws->setCellValueExplicit('D'.$r, (int)round($soTien), PHPExcel_Cell_DataType::TYPE_NUMERIC);
		$ws->setCellValue('E'.$r, !empty($g['ngay_kiem_tra']) ? date('d/m/Y', strtotime($g['ngay_kiem_tra'])) : '');
		$tongSoLuong += $soLuong;
		$tongTien += $soTien;
		$stt++; $r++;
	}

	$ws->setCellValue('B'.$r, 'Tổng cộng');
	$ws->getStyle('B'.$r)->getFont()->setBold(true);
	$ws->setCellValueExplicit('C'.$r, $tongSoLuong, PHPExcel_Cell_DataType::TYPE_NUMERIC);
	$ws->setCellValueExplicit('D'.$r, (int)round($tongTien), PHPExcel_Cell_DataType::TYPE_NUMERIC);
	$ws->getStyle('C'.$r.':D'.$r)->getFont()->setBold(true);

	$ws->getStyle('A'.$hdRow.':E'.$r)->applyFromArray($tableBorder);
	$ws->getStyle('D'.($hdRow + 1).':D'.$r)->getNumberFormat()->setFormatCode('#,##0');
	$ws->getStyle('A'.$hdRow.':E'.$r)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$ws->getStyle('A'.($hdRow + 1).':A'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$ws->getStyle('C'.($hdRow + 1).':E'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

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
	$ws->setCellValue('D'.$r, 'Kế toán');
	$ws->mergeCells('D'.$r.':E'.$r);
	$ws->getStyle('A'.$r.':E'.$r)->getFont()->setBold(true);
	$ws->getStyle('A'.$r.':B'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$ws->getStyle('D'.$r.':E'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

	$objPHPExcel->setActiveSheetIndex(0);
	$filename = ($isPendingApproval ? 'tong_hop_gv_cho_duyet_xd_' : 'gv_da_kiem_toan_xd_').date('Ymd_His').'.xlsx';

	while(ob_get_level() > 0) ob_end_clean();
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment; filename="'.$filename.'"');
	header('Cache-Control: max-age=0');

	$writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	$writer->save('php://output');
	exit;
}
function xd_xuat_tong_hop_da_duyet()
{
	global $d, $func;
	require_once LIBRARIES.'PHPExcel.php';

	$ky = isset($_REQUEST['ky']) ? trim((string)$_REQUEST['ky']) : '';
	$fromDate = (isset($_REQUEST['paid_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['paid_from'])) ? $_REQUEST['paid_from'] : '';
	$toDate = (isset($_REQUEST['paid_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['paid_to'])) ? $_REQUEST['paid_to'] : '';
	$keyword = isset($_REQUEST['keyword']) ? trim((string)$_REQUEST['keyword']) : '';
	$where = 'b.gv_key <> "" and b.da_quyettoan = 1';
	$params = array();
	if($keyword !== '') { $where .= ' and (b.gv_hoten like ? or b.gv_key like ?)'; $params[] = '%'.$keyword.'%'; $params[] = '%'.$keyword.'%'; }
	if($ky !== '') { $where .= ' and b.ky = ?'; $params[] = $ky; }
	if($fromDate !== '') { $where .= ' and b.ngay_thanh_toan >= ?'; $params[] = $fromDate; }
	if($toDate !== '') { $where .= ' and b.ngay_thanh_toan <= ?'; $params[] = $toDate; }

	$rows = $d->rawQuery(
		"select b.gv_key, b.gv_hoten, count(h.id) as so_hv, coalesce(sum(h.so_tien_thanh_toan), 0) as tong_chi, min(h.ngay_thanh_toan) as ngay_thanh_toan, max(h.ngay_thanh_toan) as ngay_thanh_toan_den
		 from (select gv_key, max(gv_hoten) as gv_hoten from #_xd_hoadon b where $where group by gv_key) b
		 left join #_xd_hocvien h on h.gv_key = b.gv_key and h.ngay_thanh_toan is not null group by b.gv_key, b.gv_hoten order by b.gv_hoten asc",
		$params
	);
	if(empty($rows)) $func->transfer("Không có giáo viên nào đã duyệt để xuất.", "index.php?com=xangdau&act=locDaThanhToan", false);

	$companyName = 'TRUNG TÂM GIÁO DỤC NGHỀ NGHIỆP BÁCH VIỆT';
	$objPHPExcel = new PHPExcel();
	$ws = $objPHPExcel->getActiveSheet();
	$ws->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(11);
	$ws->getDefaultRowDimension()->setRowHeight(18);
	$ws->setShowGridlines(false);
	foreach(array('A'=>6, 'B'=>30, 'C'=>20, 'D'=>18, 'E'=>18) as $column => $width) $ws->getColumnDimension($column)->setWidth($width);
	$ws->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
	$ws->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
	$ws->getPageMargins()->setTop(0.5)->setRight(0.5)->setBottom(0.5)->setLeft(0.5);
	$tableBorder = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => '000000'))));
	$r = 1; $ws->setCellValue('A'.$r, $companyName); $ws->mergeCells('A'.$r.':E'.$r); $ws->getStyle('A'.$r)->getFont()->setBold(true)->setSize(13); $ws->getStyle('A'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$r++; $ws->setCellValue('A'.$r, 'TỔNG HỢP GIÁO VIÊN ĐÃ DUYỆT'); $ws->mergeCells('A'.$r.':E'.$r); $ws->getStyle('A'.$r)->getFont()->setBold(true)->setSize(12); $ws->getStyle('A'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$r += 2; $hdRow = $r; $headers = array('STT', 'Giáo viên', 'Số HV thanh toán', 'Tổng chi', 'Ngày thanh toán'); $col='A'; foreach($headers as $h){ $ws->setCellValue($col.$r,$h); $col++; } $ws->getStyle('A'.$r.':E'.$r)->getFont()->setBold(true); $ws->getStyle('A'.$r.':E'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER); $ws->getStyle('A'.$r.':E'.$r)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER); $ws->getStyle('A'.$r.':E'.$r)->getAlignment()->setWrapText(true); $ws->getRowDimension($r)->setRowHeight(30);
	$r++; $stt=1; $tongHocVien=0; $tongTien=0.0; foreach($rows as $row){ $ws->setCellValueExplicit('A'.$r, $stt, PHPExcel_Cell_DataType::TYPE_NUMERIC); $ws->setCellValue('B'.$r, $row['gv_hoten'] !== '' ? $row['gv_hoten'] : $row['gv_key']); $ws->setCellValueExplicit('C'.$r, (int)$row['so_hv'], PHPExcel_Cell_DataType::TYPE_NUMERIC); $ws->setCellValueExplicit('D'.$r, (int)round((float)$row['tong_chi']), PHPExcel_Cell_DataType::TYPE_NUMERIC); $ws->setCellValue('E'.$r, !empty($row['ngay_thanh_toan']) ? date('d/m/Y', strtotime($row['ngay_thanh_toan'])) : '-'); $tongHocVien += (int)$row['so_hv']; $tongTien += (float)$row['tong_chi']; $stt++; $r++; }
	$ws->setCellValue('B'.$r, 'Tổng cộng'); $ws->getStyle('B'.$r)->getFont()->setBold(true); $ws->setCellValueExplicit('C'.$r, $tongHocVien, PHPExcel_Cell_DataType::TYPE_NUMERIC); $ws->setCellValueExplicit('D'.$r, (int)round($tongTien), PHPExcel_Cell_DataType::TYPE_NUMERIC); $ws->getStyle('C'.$r.':D'.$r)->getFont()->setBold(true); $ws->getStyle('A'.$hdRow.':E'.$r)->applyFromArray($tableBorder); $ws->getStyle('D'.($hdRow + 1).':D'.$r)->getNumberFormat()->setFormatCode('#,##0');
	$r += 2; $ws->setCellValue('A'.$r, 'Bằng chữ: '.xd_so_thanh_chu($tongTien).'.'); $ws->mergeCells('A'.$r.':E'.$r); $ws->getStyle('A'.$r)->getFont()->setItalic(true)->setBold(true);
	$r += 2; $ws->setCellValue('D'.$r, 'TP. HCM, ngày ... tháng ... năm '.date('Y')); $ws->mergeCells('D'.$r.':E'.$r); $ws->getStyle('D'.$r)->getFont()->setItalic(true); $ws->getStyle('D'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$r++; $ws->setCellValue('A'.$r, 'Giám đốc'); $ws->mergeCells('A'.$r.':B'.$r); $ws->setCellValue('D'.$r, 'Người lập'); $ws->mergeCells('D'.$r.':E'.$r); $ws->getStyle('A'.$r.':E'.$r)->getFont()->setBold(true); $ws->getStyle('A'.$r.':B'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER); $ws->getStyle('D'.$r.':E'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->setActiveSheetIndex(0); $filename='tong_hop_gv_da_duyet_'.date('Ymd_His').'.xlsx'; while(ob_get_level() > 0) ob_end_clean(); header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment; filename="'.$filename.'"'); header('Cache-Control: max-age=0'); $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007'); $writer->save('php://output'); exit;
}
