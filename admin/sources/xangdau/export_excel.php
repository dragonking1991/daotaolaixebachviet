<?php
if(!defined('SOURCES')) die("Error");

/* ============================ Xuất Excel bảng kê (mỗi giáo viên 1 sheet) ============================ */

function xd_export_bangke_excel($d, $idBangke, $today, $ky, $onlyGvKey = '', $previewSelected = array(), $fromDate = '', $toDate = '', $allPreview = false)
{
	require_once LIBRARIES.'PHPExcel.php';

	$companyName = 'TRUNG TÂM GIÁO DỤC NGHỀ NGHIỆP BÁCH VIỆT';

	// Lấy hóa đơn và học viên của đợt, gom theo giáo viên (gv_key)
	if($idBangke > 0)
	{
		$hoadons = $d->rawQuery("select * from #_xd_hoadon where id_bangke = ? order by gv_hoten asc, ngay_hoa_don asc, id asc", array($idBangke));
		$hocviens = $d->rawQuery("select * from #_xd_hocvien where id_bangke = ? order by gv_hoten asc, id asc", array($idBangke));
	}
	else
	{
		$invoiceWhere = $allPreview ? ' where da_quyettoan = 0 and hop_le = 1' : ' where gv_key = ? and hop_le = 1'; $invoiceParams = $allPreview ? array() : array($onlyGvKey);
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
		// Dùng chung một khung A:H cho cả hai bảng để khi in hai bảng có cùng bề rộng.
		foreach(array('A'=>5, 'B'=>11, 'C'=>18, 'D'=>28, 'E'=>12, 'F'=>14, 'G'=>17, 'H'=>13) as $column => $width)
			$ws->getColumnDimension($column)->setWidth($width);
		$ws->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
		$ws->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
		$ws->getPageSetup()->setFitToWidth(1);
		$ws->getPageSetup()->setFitToHeight(0);
		$ws->getPageSetup()->setFitToPage(true);
		$ws->getPageSetup()->setHorizontalCentered(true);
		$ws->getPageSetup()->setPrintArea('A1:H1');
		$ws->getPageMargins()->setTop(0.35)->setRight(0.35)->setBottom(0.35)->setLeft(0.35);
		$ws->getPageMargins()->setHeader(0.15)->setFooter(0.15);

		// ---- Tiêu đề ----
		$ws->setCellValue('A1', $companyName);
		$ws->mergeCells('A1:H1');
		$ws->setCellValue('A2', 'BẢNG KÊ TRÍCH CHI PHÍ NHIÊN LIỆU - Số : '.($idBangke > 0 ? $idBangke : '..........'));
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
		$ws->setCellValue('H'.$r, 'Ghi chú'); $ws->mergeCells('H'.$r.':H'.($r + 1));
		$ws->getStyle('A'.$r)->getFont()->setBold(true);
		$ws->getStyle('A'.$r.':H'.$r)->getFont()->setBold(true);
		$ws->getStyle('A'.$r.':H'.($r + 1))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$r++;
		$hdHeadRow = $r;
		$hdHeaders = array('STT', 'Số HĐ', 'Ngày', 'Thông tin bán hàng', 'Chi tiết', 'Số tiền HĐ', 'Biển số xe');
		$col = 'A';
		foreach($hdHeaders as $h) { $ws->setCellValue($col.$r, $h); $col++; }
		$ws->getStyle('A'.$r.':G'.$r)->getFont()->setBold(true);
		$ws->getStyle('A'.$r.':G'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$ws->getStyle('A'.$r.':G'.$r)->getAlignment()->setWrapText(true);
		$ws->getRowDimension($r)->setRowHeight(28);
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
			$ws->setCellValue('H'.$r, isset($h['note_1']) ? $h['note_1'] : '');
			$tongHd += (float)$h['tong_tien'];
			$stt++; $r++;
		}
		$ws->setCellValue('A'.$r, 'Tổng cộng');
		$ws->mergeCells('A'.$r.':E'.$r);
		$ws->getStyle('A'.$r)->getFont()->setBold(true);
		$ws->getStyle('A'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$ws->setCellValueExplicit('F'.$r, (int)round($tongHd), PHPExcel_Cell_DataType::TYPE_NUMERIC);
		$ws->getStyle('F'.$r)->getFont()->setBold(true);
		$ws->getStyle('A'.($hdHeadRow - 1).':H'.$r)->applyFromArray($tableBorder);
		$ws->getStyle('F'.($hdHeadRow + 1).':F'.$r)->getNumberFormat()->setFormatCode('#,##0');
		$ws->getStyle('A'.$hdHeadRow.':H'.$r)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$ws->getStyle('A'.$hdHeadRow.':C'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$ws->getStyle('E'.$hdHeadRow.':H'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$ws->getStyle('H'.$hdHeadRow.':H'.$r)->getAlignment()->setWrapText(true);

		// ---- Bảng Danh sách học viên ----
		$r += 2;
		$ws->setCellValue('A'.$r, 'DANH SÁCH HỌC VIÊN'); $ws->mergeCells('A'.$r.':H'.$r);
		$ws->getStyle('A'.$r)->getFont()->setBold(true);
		$ws->getStyle('A'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$r++;
		$hvHeadRow = $r;
		$hvHeaders = array('STT', 'Khóa', 'CCCD/CC', 'Họ tên học viên', 'Năm sinh', 'Định mức', 'Số tiền thanh toán', 'Nhóm');
		$col = 'A';
		foreach($hvHeaders as $h) { $ws->setCellValue($col.$r, $h); $col++; }
		$ws->getStyle('A'.$r.':H'.$r)->getFont()->setBold(true);
		$ws->getStyle('A'.$r.':H'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$ws->getStyle('A'.$r.':H'.$r)->getAlignment()->setWrapText(true);
		$ws->getRowDimension($r)->setRowHeight(28);
		$r++;

		$stt = 1; $tongDinhMuc = 0.0; $tongTt = 0.0;
		$listHv = isset($hvByGv[$gvKey]) ? $hvByGv[$gvKey] : array();
		foreach($listHv as $hv)
		{
			$namSinh = (!empty($hv['ngaysinh']) && strtotime($hv['ngaysinh']) !== false) ? date('d/m/Y', strtotime($hv['ngaysinh'])) : (string)$hv['ngaysinh'];
			$ws->setCellValueExplicit('A'.$r, $stt, PHPExcel_Cell_DataType::TYPE_NUMERIC);
			$ws->setCellValue('B'.$r, isset($hv['khoa']) ? $hv['khoa'] : '');
			$ws->setCellValueExplicit('C'.$r, $hv['cccd'], PHPExcel_Cell_DataType::TYPE_STRING);
			$ws->setCellValue('D'.$r, $hv['ho_ten']);
			$ws->setCellValue('E'.$r, $namSinh);
			$ws->setCellValueExplicit('F'.$r, (int)round((float)$hv['dinh_muc']), PHPExcel_Cell_DataType::TYPE_NUMERIC);
			$ws->setCellValueExplicit('G'.$r, (int)round((float)$hv['so_tien_thanh_toan']), PHPExcel_Cell_DataType::TYPE_NUMERIC);
			$ws->setCellValue('H'.$r, $hv['nhom']);
			$tongDinhMuc += (float)$hv['dinh_muc'];
			$tongTt += (float)$hv['so_tien_thanh_toan'];
			$stt++; $r++;
		}
		$ws->setCellValue('A'.$r, 'Tổng cộng');
		$ws->mergeCells('A'.$r.':E'.$r);
		$ws->getStyle('A'.$r)->getFont()->setBold(true);
		$ws->getStyle('A'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$ws->setCellValueExplicit('F'.$r, (int)round($tongDinhMuc), PHPExcel_Cell_DataType::TYPE_NUMERIC);
		$ws->setCellValueExplicit('G'.$r, (int)round($tongTt), PHPExcel_Cell_DataType::TYPE_NUMERIC);
		$ws->getStyle('F'.$r.':G'.$r)->getFont()->setBold(true);
		$ws->getStyle('A'.$hvHeadRow.':H'.$r)->applyFromArray($tableBorder);
		$ws->getStyle('F'.($hvHeadRow + 1).':G'.$r)->getNumberFormat()->setFormatCode('#,##0');
		$ws->getStyle('A'.$hvHeadRow.':H'.$r)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$ws->getStyle('A'.$hvHeadRow.':B'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$ws->getStyle('C'.$hvHeadRow.':H'.$r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

		$r++;
		$ws->setCellValue('A'.$r, 'Số tiền thanh toán:');
		$ws->getStyle('A'.$r)->getFont()->setBold(true);
		$ws->setCellValue('C'.$r, xd_so_thanh_chu($tongTt).'.');
		$ws->mergeCells('C'.$r.':H'.$r);
		$ws->getStyle('C'.$r)->getFont()->setBold(true);
		$ws->getStyle('A'.$r.':H'.$r)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$ws->getStyle('C'.$r)->getAlignment()->setWrapText(true);

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
		$ws->getPageSetup()->setPrintArea('A1:H'.$r);
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

function xd_xuat_hoadon_excel()
{
	global $d, $func;
	$scope = isset($_REQUEST['scope']) && in_array($_REQUEST['scope'], array('all', 'paid', 'unpaid'), true) ? $_REQUEST['scope'] : 'all';
	$keyword = isset($_REQUEST['keyword']) ? trim((string)$_REQUEST['keyword']) : '';
	$fromDate = (isset($_REQUEST['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['from_date'])) ? $_REQUEST['from_date'] : '';
	$toDate = (isset($_REQUEST['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['to_date'])) ? $_REQUEST['to_date'] : '';
	$ky = isset($_REQUEST['ky']) ? trim((string)$_REQUEST['ky']) : '';
	$ktFrom = (isset($_REQUEST['kt_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['kt_from'])) ? $_REQUEST['kt_from'] : '';
	$ktTo = (isset($_REQUEST['kt_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['kt_to'])) ? $_REQUEST['kt_to'] : '';
	list($where, $params) = xd_hoadon_where_and_params($scope, $keyword, $fromDate, $toDate, $ky, $ktFrom, $ktTo);
	$rows = $d->rawQuery("select * from #_xd_hoadon where $where order by ngay_hoa_don desc, id desc", $params);
	$labels = array('all' => 'TẤT CẢ', 'paid' => 'ĐÃ QUYẾT TOÁN', 'unpaid' => 'CHƯA QUYẾT TOÁN');
	xd_xuat_danh_sach_excel('DANH SÁCH HÓA ĐƠN XĂNG DẦU - '.$labels[$scope], array('STT', 'Số HĐ', 'Ngày HĐ', 'Giáo viên', 'Biển số', 'Kỳ', 'Tổng tiền', 'Trạng thái'), array('A'=>6, 'B'=>18, 'C'=>14, 'D'=>28, 'E'=>15, 'F'=>14, 'G'=>18, 'H'=>20), $rows, function($row, $stt) {
		return array($stt, $row['ma_hoa_don'], !empty($row['ngay_hoa_don']) ? date('d/m/Y', strtotime($row['ngay_hoa_don'])) : '', $row['gv_hoten'], $row['bien_so'], $row['ky'], (float)$row['tong_tien'], (int)$row['da_quyettoan'] === 1 ? 'Đã quyết toán' : 'Chưa quyết toán');
	}, 7, 'hoa_don_xd_'.strtolower($scope));
}

function xd_xuat_hocvien_excel()
{
	global $d;
	$scope = isset($_REQUEST['scope']) && in_array($_REQUEST['scope'], array('all', 'paid', 'unpaid'), true) ? $_REQUEST['scope'] : 'all';
	$keyword = isset($_REQUEST['keyword']) ? trim((string)$_REQUEST['keyword']) : '';
	$nhom = isset($_REQUEST['nhom']) && in_array($_REQUEST['nhom'], array('BT', 'CK', 'DAT'), true) ? $_REQUEST['nhom'] : '';
	$ttFrom = (isset($_REQUEST['tt_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['tt_from'])) ? $_REQUEST['tt_from'] : '';
	$ttTo = (isset($_REQUEST['tt_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['tt_to'])) ? $_REQUEST['tt_to'] : '';
	list($where, $params) = xd_hocvien_where_and_params($scope, $keyword, $nhom, $ttFrom, $ttTo);
	$rows = $d->rawQuery("select * from #_xd_hocvien where $where order by id asc", $params);
	$labels = array('all' => 'TẤT CẢ', 'paid' => 'ĐÃ THANH TOÁN', 'unpaid' => 'CHƯA THANH TOÁN');
	xd_xuat_danh_sach_excel('DANH SÁCH HỌC VIÊN XĂNG DẦU - '.$labels[$scope], array('STT', 'Họ tên', 'CCCD', 'Khóa', 'Ngày sinh', 'Nhóm', 'GV phụ trách', 'Số tiền TT', 'Ngày TT', 'Trạng thái'), array('A'=>6, 'B'=>28, 'C'=>18, 'D'=>14, 'E'=>14, 'F'=>10, 'G'=>26, 'H'=>18, 'I'=>14, 'J'=>20), $rows, function($row, $stt) {
		return array($stt, $row['ho_ten'], $row['cccd'], $row['khoa'], $row['ngaysinh'], $row['nhom'], $row['gv_hoten'], (float)$row['so_tien_thanh_toan'], !empty($row['ngay_thanh_toan']) ? date('d/m/Y', strtotime($row['ngay_thanh_toan'])) : '', !empty($row['ngay_thanh_toan']) ? 'Đã thanh toán' : 'Chưa thanh toán');
	}, 8, 'hoc_vien_xd_'.strtolower($scope));
}

function xd_xuat_danh_sach_excel($title, $headers, $widths, $rows, $rowValues, $moneyColumn, $filenamePrefix)
{
	require_once LIBRARIES.'PHPExcel.php';
	$objPHPExcel = new PHPExcel();
	$ws = $objPHPExcel->getActiveSheet();
	$lastColumn = array_keys($widths); $lastColumn = end($lastColumn);
	$ws->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(11);
	$ws->setShowGridlines(false);
	foreach($widths as $column => $width) $ws->getColumnDimension($column)->setWidth($width);
	$ws->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
	$ws->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
	$ws->getPageSetup()->setFitToWidth(1)->setFitToHeight(0)->setFitToPage(true);
	$ws->getPageMargins()->setTop(0.5)->setRight(0.35)->setBottom(0.5)->setLeft(0.35);
	$ws->setCellValue('A1', 'TRUNG TÂM GIÁO DỤC NGHỀ NGHIỆP BÁCH VIỆT'); $ws->mergeCells('A1:'.$lastColumn.'1');
	$ws->setCellValue('A2', $title); $ws->mergeCells('A2:'.$lastColumn.'2');
	$ws->getStyle('A1:'.$lastColumn.'2')->getFont()->setBold(true);
	$ws->getStyle('A1:'.$lastColumn.'2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$ws->getStyle('A1')->getFont()->setSize(13); $ws->getStyle('A2')->getFont()->setSize(12);
	$rowNumber = 4; $column = 'A'; foreach($headers as $header) { $ws->setCellValue($column.$rowNumber, $header); $column++; }
	$ws->getStyle('A'.$rowNumber.':'.$lastColumn.$rowNumber)->getFont()->setBold(true);
	$ws->getStyle('A'.$rowNumber.':'.$lastColumn.$rowNumber)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$ws->getStyle('A'.$rowNumber.':'.$lastColumn.$rowNumber)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$rowNumber++; $stt = 1;
	foreach($rows as $row) { $column = 'A'; foreach(call_user_func($rowValues, $row, $stt) as $value) { $ws->setCellValue($column.$rowNumber, $value); $column++; } $stt++; $rowNumber++; }
	$lastRow = $rowNumber - 1;
	$border = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => '000000'))));
	$ws->getStyle('A4:'.$lastColumn.$lastRow)->applyFromArray($border);
	$moneyColumnLetter = PHPExcel_Cell::stringFromColumnIndex($moneyColumn - 1);
	$ws->getStyle($moneyColumnLetter.'5:'.$moneyColumnLetter.$lastRow)->getNumberFormat()->setFormatCode('#,##0');
	$ws->getStyle('A5:A'.$lastRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$ws->getPageSetup()->setPrintArea('A1:'.$lastColumn.$lastRow);
	$objPHPExcel->setActiveSheetIndex(0);
	while(ob_get_level() > 0) ob_end_clean();
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment; filename="'.$filenamePrefix.'_'.date('Ymd_His').'.xlsx"');
	header('Cache-Control: max-age=0');
	PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007')->save('php://output');
	exit;
}
