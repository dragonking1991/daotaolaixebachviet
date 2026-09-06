<?php
if(!defined('SOURCES')) die("Error");

/* ============================ Xuất Excel bảng kê (mỗi giáo viên 1 sheet) ============================ */

function xd_export_bangke_excel($d, $idBangke, $today, $ky, $onlyGvKey = '', $previewSelected = array(), $fromDate = '', $toDate = '', $allPreview = false)
{
	require_once LIBRARIES.'PHPExcel.php';

	$setting = $d->rawQueryOne("select tenvi from #_setting limit 0,1");
	$companyName = (!empty($setting['tenvi'])) ? (function_exists('mb_strtoupper') ? mb_strtoupper($setting['tenvi'], 'UTF-8') : strtoupper($setting['tenvi'])) : 'TRUNG TÂM GIÁO DỤC NGHỀ NGHIỆP';

	// Lấy hóa đơn và học viên của đợt, gom theo giáo viên (gv_key)
	if($idBangke > 0)
	{
		$hoadons = $d->rawQuery("select * from #_xd_hoadon where id_bangke = ? order by gv_hoten asc, ngay_hoa_don asc, id asc", array($idBangke));
		$hocviens = $d->rawQuery("select * from #_xd_hocvien where id_bangke = ? order by gv_hoten asc, id asc", array($idBangke));
	}
	else
	{
		$invoiceWhere = $allPreview ? ' where da_quyettoan = 0' : ' where gv_key = ?'; $invoiceParams = $allPreview ? array() : array($onlyGvKey);
		if($ky !== '') { $invoiceWhere .= ' and ky = ?'; $invoiceParams[] = $ky; }
		if($fromDate !== '') { $invoiceWhere .= ' and ngay_hoa_don >= ?'; $invoiceParams[] = $fromDate; }
		if($toDate !== '') { $invoiceWhere .= ' and ngay_hoa_don <= ?'; $invoiceParams[] = $toDate; }
		$hoadons = $d->rawQuery("select * from #_xd_hoadon $invoiceWhere order by gv_hoten asc, ngay_hoa_don asc, id asc", $invoiceParams);
		$hocviens = $previewSelected;
	}

	$hdByGv = array();
	$gvTen = array();
	if(!empty($hoadons)) foreach($hoadons as $h) { $hdByGv[$h['gv_key']][] = $h; $gvTen[$h['gv_key']] = $h['gv_hoten']; }
	$hvByGv = array();
	if(!empty($hocviens)) foreach($hocviens as $h) { $hvByGv[$h['gv_key']][] = $h; if(!isset($gvTen[$h['gv_key']])) $gvTen[$h['gv_key']] = $h['gv_hoten']; }

	// Danh sách GV = các GV có học viên được trích (theo thứ tự tên)
	$gvKeys = array_keys($hvByGv);
	usort($gvKeys, function($a, $b) use ($gvTen) { return strcmp((string)$gvTen[$a], (string)$gvTen[$b]); });
	if(empty($gvKeys)) $gvKeys = array_keys($gvTen);

	$objPHPExcel = new PHPExcel();
	$objPHPExcel->removeSheetByIndex(0);

	$thin = PHPExcel_Style_Border::BORDER_THIN;
	$usedTitles = array();
	$sheetIndex = 0;

	foreach($gvKeys as $gvKey)
	{
		$ten = isset($gvTen[$gvKey]) ? $gvTen[$gvKey] : $gvKey;

		// Tên sheet hợp lệ (<=31 ký tự, không ký tự cấm, không trùng)
		$title = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', ' ', (string)$ten);
		$title = trim(xd_mb_sub($title, 0, 28));
		if($title === '') $title = 'GV';
		$baseTitle = $title; $k = 1;
		while(isset($usedTitles[$title])) { $title = xd_mb_sub($baseTitle, 0, 26).' '.(++$k); }
		$usedTitles[$title] = 1;

		$ws = new PHPExcel_Worksheet($objPHPExcel, $title);
		$objPHPExcel->addSheet($ws, $sheetIndex++);
		$ws->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(11);
		$ws->getDefaultRowDimension()->setRowHeight(20);
		$ws->setShowGridlines(false);
		$tableBorder = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => '000000'))));
		foreach(array('A'=>5, 'B'=>10, 'C'=>14, 'D'=>11, 'E'=>9, 'F'=>9, 'G'=>11, 'H'=>9) as $column => $width)
			$ws->getColumnDimension($column)->setWidth($width);
		$ws->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
		$ws->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);

		// ---- Tiêu đề ----
		$ws->setCellValue('A1', $companyName);
		$ws->mergeCells('A1:H1');
		$ws->setCellValue('A2', 'BẢNG KÊ TRÍCH CHI PHÍ NHIÊN LIỆU - Số: '.($idBangke > 0 ? $idBangke : '..........'));
		$ws->mergeCells('A2:H2');
		$ws->setCellValue('A3', 'Giáo viên: '.$ten);
		$ws->mergeCells('A3:H3');
		$ws->setCellValue('A4', 'Ngày quyết toán: '.date('d/m/Y', strtotime($today)).($ky !== '' ? '    -    Kỳ: '.$ky : ''));
		$ws->mergeCells('A4:H4');
		$ws->getStyle('A1:H4')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$ws->getStyle('A1:H4')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$ws->getStyle('A1:H2')->getFont()->setBold(true);
		$ws->getStyle('A1')->getFont()->setSize(13);
		$ws->getStyle('A2')->getFont()->setSize(12);
		$ws->getRowDimension(1)->setRowHeight(24);
		$ws->getRowDimension(2)->setRowHeight(24);

		// ---- Bảng Nội dung (hóa đơn) ----
		$r = 6;
		$ws->setCellValue('A'.$r, 'Nội dung'); $ws->mergeCells('A'.$r.':G'.$r);
		$ws->getStyle('A'.$r)->getFont()->setBold(true);
		$ws->getStyle('A'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$r++;
		$hdHeadRow = $r;
		$hdHeaders = array('STT', 'Số hóa đơn', 'Ngày', 'Thông tin bán hàng', 'Chi tiết', 'Số tiền HĐ', 'Biển số xe');
		$col = 'A';
		foreach($hdHeaders as $h) { $ws->setCellValue($col.$r, $h); $col++; }
		$ws->getStyle('A'.$r.':G'.$r)->getFont()->setBold(true);
		$ws->getStyle('A'.$r.':G'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$r++;

		$stt = 1; $tongHd = 0.0;
		$listHd = isset($hdByGv[$gvKey]) ? $hdByGv[$gvKey] : array();
		foreach($listHd as $h)
		{
			$ws->setCellValueExplicit('A'.$r, $stt, PHPExcel_Cell_DataType::TYPE_NUMERIC);
			$ws->setCellValueExplicit('B'.$r, $h['ma_hoa_don'], PHPExcel_Cell_DataType::TYPE_STRING);
			$ws->setCellValue('C'.$r, $h['ngay_hoa_don'] ? date('d/m/Y', strtotime($h['ngay_hoa_don'])) : '');
			$ws->setCellValue('D'.$r, isset($h['thong_tin_ban_hang']) ? $h['thong_tin_ban_hang'] : '');
			$ws->setCellValue('E'.$r, isset($h['chi_tiet']) ? $h['chi_tiet'] : '');
			$ws->setCellValueExplicit('F'.$r, (int)round((float)$h['tong_tien']), PHPExcel_Cell_DataType::TYPE_NUMERIC);
			$ws->setCellValue('G'.$r, isset($h['bien_so']) ? $h['bien_so'] : '');
			$tongHd += (float)$h['tong_tien'];
			$stt++; $r++;
		}
		$ws->setCellValue('E'.$r, 'Tổng cộng');
		$ws->getStyle('E'.$r)->getFont()->setBold(true);
		$ws->setCellValueExplicit('F'.$r, (int)round($tongHd), PHPExcel_Cell_DataType::TYPE_NUMERIC);
		$ws->getStyle('F'.$r)->getFont()->setBold(true);
		$ws->getStyle('A'.$hdHeadRow.':G'.$r)->applyFromArray($tableBorder);
		$ws->getStyle('F'.($hdHeadRow + 1).':F'.$r)->getNumberFormat()->setFormatCode('#,##0');
		$ws->getStyle('A'.$hdHeadRow.':G'.$r)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$ws->getStyle('A'.$hdHeadRow.':C'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$ws->getStyle('E'.$hdHeadRow.':G'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

		// ---- Bảng Danh sách học viên ----
		$r += 2;
		$ws->setCellValue('A'.$r, 'DANH SÁCH HỌC VIÊN'); $ws->mergeCells('A'.$r.':H'.$r);
		$ws->getStyle('A'.$r)->getFont()->setBold(true);
		$ws->getStyle('A'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$r++;
		$hvHeadRow = $r;
		$hvHeaders = array('STT', 'Khóa', 'Họ tên học viên', 'CCCD/CC', 'Năm sinh', 'Định mức', 'Số tiền thanh toán', 'Nhóm');
		$col = 'A';
		foreach($hvHeaders as $h) { $ws->setCellValue($col.$r, $h); $col++; }
		$ws->getStyle('A'.$r.':H'.$r)->getFont()->setBold(true);
		$r++;

		$stt = 1; $tongDinhMuc = 0.0; $tongTt = 0.0;
		$listHv = isset($hvByGv[$gvKey]) ? $hvByGv[$gvKey] : array();
		foreach($listHv as $hv)
		{
			$namSinh = (!empty($hv['ngaysinh']) && strtotime($hv['ngaysinh']) !== false) ? date('d/m/Y', strtotime($hv['ngaysinh'])) : (string)$hv['ngaysinh'];
			$ws->setCellValueExplicit('A'.$r, $stt, PHPExcel_Cell_DataType::TYPE_NUMERIC);
			$ws->setCellValue('B'.$r, isset($hv['khoa']) ? $hv['khoa'] : '');
			$ws->setCellValue('C'.$r, $hv['ho_ten']);
			$ws->setCellValueExplicit('D'.$r, $hv['cccd'], PHPExcel_Cell_DataType::TYPE_STRING);
			$ws->setCellValue('E'.$r, $namSinh);
			$ws->setCellValueExplicit('F'.$r, (int)round((float)$hv['dinh_muc']), PHPExcel_Cell_DataType::TYPE_NUMERIC);
			$ws->setCellValueExplicit('G'.$r, (int)round((float)$hv['so_tien_thanh_toan']), PHPExcel_Cell_DataType::TYPE_NUMERIC);
			$ws->setCellValue('H'.$r, $hv['nhom']);
			$tongDinhMuc += (float)$hv['dinh_muc'];
			$tongTt += (float)$hv['so_tien_thanh_toan'];
			$stt++; $r++;
		}
		$ws->setCellValue('C'.$r, 'Tổng cộng');
		$ws->getStyle('C'.$r)->getFont()->setBold(true);
		$ws->setCellValueExplicit('F'.$r, (int)round($tongDinhMuc), PHPExcel_Cell_DataType::TYPE_NUMERIC);
		$ws->setCellValueExplicit('G'.$r, (int)round($tongTt), PHPExcel_Cell_DataType::TYPE_NUMERIC);
		$ws->getStyle('F'.$r.':G'.$r)->getFont()->setBold(true);
		$ws->getStyle('A'.$hvHeadRow.':H'.$r)->applyFromArray($tableBorder);
		$ws->getStyle('F'.($hvHeadRow + 1).':G'.$r)->getNumberFormat()->setFormatCode('#,##0');
		$ws->getStyle('A'.$hvHeadRow.':H'.$r)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$ws->getStyle('A'.$hvHeadRow.':B'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$ws->getStyle('D'.$hvHeadRow.':H'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

		// ---- Cam kết và chữ ký ----
		$r += 2;
		$ws->setCellValue('A'.$r, '-Tôi xin cam kết và chịu trách nhiệm về tính chính xác, hợp lệ của các thông tin, dữ liệu đào tạo và chứng từ liên quan trên.');
		$ws->mergeCells('A'.$r.':H'.$r);
		$ws->getStyle('A'.$r)->getAlignment()->setWrapText(true);
		$r += 2;
		$ws->setCellValue('A'.$r, 'Phòng Đào tạo');
		$ws->setCellValue('C'.$r, 'Kế Toán');
		$ws->setCellValue('F'.$r, 'Giáo viên quyết toán');
		$ws->mergeCells('A'.$r.':B'.$r);
		$ws->mergeCells('C'.$r.':E'.$r);
		$ws->mergeCells('F'.$r.':H'.$r);
		$ws->getStyle('A'.$r.':H'.$r)->getFont()->setBold(true);
		$ws->getStyle('A'.$r.':H'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		for($visibleRow = 1; $visibleRow <= $r; $visibleRow++) $ws->getRowDimension($visibleRow)->setVisible(true);
	}

	if($objPHPExcel->getSheetCount() === 0)
	{
		$ws = new PHPExcel_Worksheet($objPHPExcel, 'BangKe');
		$objPHPExcel->addSheet($ws, 0);
		$ws->setCellValue('A1', 'Không có dữ liệu bảng kê.');
	}

	$objPHPExcel->setActiveSheetIndex(0);

	$filename = 'bang_ke_trich_chi_phi_xd_'.$idBangke.'_'.date('Ymd').'.xlsx';

	while(ob_get_level() > 0) ob_end_clean();
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment; filename="'.$filename.'"');
	header('Cache-Control: max-age=0');

	$writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	$writer->save('php://output');
	exit;
}
