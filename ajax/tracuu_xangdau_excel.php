<?php
include "ajax_config.php";
require_once LIBRARIES.'xangdau_config.php';
require_once LIBRARIES.'PHPExcel.php';

function xd_excel_norm_cccd($value)
{
	return preg_replace('/\D+/', '', (string)$value);
}

function xd_excel_gv_key($name)
{
	$name = function_exists('mb_strtolower') ? mb_strtolower(trim((string)$name), 'UTF-8') : strtolower(trim((string)$name));
	$search = array('à','á','ả','ã','ạ','ă','ằ','ắ','ẳ','ẵ','ặ','â','ầ','ấ','ẩ','ẫ','ậ','đ','è','é','ẻ','ẽ','ẹ','ê','ề','ế','ể','ễ','ệ','ì','í','ỉ','ĩ','ị','ò','ó','ỏ','õ','ọ','ô','ồ','ố','ổ','ỗ','ộ','ơ','ờ','ớ','ở','ỡ','ợ','ù','ú','ủ','ũ','ụ','ư','ừ','ứ','ử','ữ','ự','ỳ','ý','ỷ','ỹ','ỵ');
	$replace = array('a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y');
	$name = str_replace($search, $replace, $name);
	$name = preg_replace('/[^a-z0-9\s]+/', ' ', $name);
	$name = preg_replace('/\s+/', ' ', trim($name));
	return trim(preg_replace('/^(thay|co)\s+/', '', $name));
}

$rawInput = isset($_GET['cccd']) ? trim((string)$_GET['cccd']) : '';
$cccd = xd_excel_norm_cccd($rawInput);
$variants = array($cccd);
if(strlen($cccd) === 11) $variants[] = '0'.$cccd;
if(strlen($cccd) === 12 && substr($cccd, 0, 1) === '0') $variants[] = substr($cccd, 1);
$variants = array_values(array_unique(array_filter($variants, function($value){ return $value !== ''; })));
$whereCccd = !empty($variants) ? 'cccd IN ('.implode(',', array_fill(0, count($variants), '?')).') OR ' : '';
$params = $variants; $params[] = $rawInput;
$emp = $d->rawQueryOne("select ten$lang as ten from #_product where type = 'nhan-vien' and hienthi = 1 and ($whereCccd ma_tra_cuu = ?) limit 0,1", $params);
if(empty($emp['ten'])) exit('Không tìm thấy giáo viên.');
$gvName = $emp['ten']; $gvKey = xd_excel_gv_key($gvName);
$fromDate = (isset($_GET['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from_date'])) ? $_GET['from_date'] : '';
$toDate = (isset($_GET['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to_date'])) ? $_GET['to_date'] : '';

$hdWhere = ''; $hdParams = array($gvKey);
if($fromDate !== '') { $hdWhere .= ' and ngay_hoa_don >= ?'; $hdParams[] = $fromDate; }
if($toDate !== '') { $hdWhere .= ' and ngay_hoa_don <= ?'; $hdParams[] = $toDate; }
$hoadons = $d->rawQuery("select * from #_xd_hoadon where gv_key = ? $hdWhere order by ngay_hoa_don desc, id desc", $hdParams);

$hvWhere = ' and ngay_thanh_toan is not null'; $hvParams = array($gvKey);
if($fromDate !== '') { $hvWhere .= ' and ngay_thanh_toan >= ?'; $hvParams[] = $fromDate; }
if($toDate !== '') { $hvWhere .= ' and ngay_thanh_toan <= ?'; $hvParams[] = $toDate; }
$hocviens = $d->rawQuery("select * from #_xd_hocvien where gv_key = ? $hvWhere order by ngay_thanh_toan desc, id asc", $hvParams);
$config = getXdConfig($d);
foreach($hocviens as &$hocvien)
{
	$hocvien['dinh_muc'] = (int)$config['dinh_muc'];
	$hocvien['so_tien_thanh_toan'] = (int)xdMucTheoNhom($config, $hocvien['nhom']);
}
unset($hocvien);

$setting = $d->rawQueryOne("select tenvi from #_setting limit 0,1");
$companyName = !empty($setting['tenvi']) ? $setting['tenvi'] : 'TRUNG TÂM GIÁO DỤC NGHỀ NGHIỆP BÁCH VIỆT';
$excel = new PHPExcel(); $excel->removeSheetByIndex(0);
$sheet = new PHPExcel_Worksheet($excel, 'Tra cuu'); $excel->addSheet($sheet, 0);
$sheet->setCellValue('A1', $companyName); $sheet->mergeCells('A1:H1');
$sheet->setCellValue('A2', 'TỔNG HỢP CHI PHÍ XĂNG DẦU'); $sheet->mergeCells('A2:H2');
$sheet->setCellValue('A3', 'Giáo viên: '.$gvName); $sheet->mergeCells('A3:H3');
$range = ($fromDate !== '' || $toDate !== '') ? 'Khoảng ngày: '.($fromDate !== '' ? date('d/m/Y', strtotime($fromDate)) : '...').' - '.($toDate !== '' ? date('d/m/Y', strtotime($toDate)) : '...') : 'Toàn bộ thời gian';
$sheet->setCellValue('A4', $range); $sheet->mergeCells('A4:H4');
$sheet->getStyle('A1:H4')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1:H4')->getFont()->setBold(true);
$row = 6; $sheet->setCellValue('A'.$row, 'NỘI DUNG HÓA ĐƠN'); $sheet->mergeCells('A'.$row.':H'.$row); $row++;
$headers = array('STT','Số hóa đơn','Ngày','Thông tin bán hàng','Chi tiết','Số tiền HĐ','Biển số xe','Kỳ');
foreach($headers as $col => $header) $sheet->setCellValueByColumnAndRow($col, $row, $header);
$sheet->getStyle('A'.$row.':H'.$row)->getFont()->setBold(true); $sheet->getStyle('A'.$row.':H'.$row)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN); $row++;
$totalInvoice = 0; $i = 0;
foreach($hoadons as $invoice)
{
	$i++; $totalInvoice += (float)$invoice['tong_tien'];
	$values = array($i, $invoice['ma_hoa_don'], $invoice['ngay_hoa_don'] ? date('d/m/Y', strtotime($invoice['ngay_hoa_don'])) : '', $invoice['thong_tin_ban_hang'], $invoice['chi_tiet'], (float)$invoice['tong_tien'], $invoice['bien_so'], $invoice['ky']);
	foreach($values as $col => $value) $sheet->setCellValueByColumnAndRow($col, $row, $value);
	$row++;
}
$sheet->setCellValue('E'.$row, 'Tổng cộng'); $sheet->setCellValue('F'.$row, $totalInvoice); $row += 2;
$sheet->setCellValue('A'.$row, 'DANH SÁCH HỌC VIÊN ĐÃ THANH TOÁN'); $sheet->mergeCells('A'.$row.':H'.$row); $row++;
$studentHeaders = array('STT','Khóa','Họ tên học viên','CCCD/CC','Ngày sinh','Nhóm','Định mức XD','Số tiền thanh toán','Ngày thanh toán');
foreach($studentHeaders as $col => $header) $sheet->setCellValueByColumnAndRow($col, $row, $header);
$sheet->getStyle('A'.$row.':I'.$row)->getFont()->setBold(true); $sheet->getStyle('A'.$row.':I'.$row)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN); $row++;
$totalStudent = 0; $i = 0;
foreach($hocviens as $student)
{
	$i++; $totalStudent += (float)$student['so_tien_thanh_toan'];
	$values = array($i, $student['khoa'], $student['ho_ten'], $student['cccd'], $student['ngaysinh'], $student['nhom'], (float)$student['dinh_muc'], (float)$student['so_tien_thanh_toan'], $student['ngay_thanh_toan'] ? date('d/m/Y', strtotime($student['ngay_thanh_toan'])) : '');
	foreach($values as $col => $value) $sheet->setCellValueByColumnAndRow($col, $row, $value);
	$row++;
}
$sheet->setCellValue('G'.$row, 'Tổng cộng'); $sheet->setCellValue('H'.$row, $totalStudent);
foreach(range('A', 'I') as $column) $sheet->getColumnDimension($column)->setAutoSize(true);
$sheet->getStyle('A1:I'.$row)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
$sheet->getStyle('F7:F'.$row)->getNumberFormat()->setFormatCode('#,##0');
$sheet->getStyle('G1:H'.$row)->getNumberFormat()->setFormatCode('#,##0');
$filename = 'tra-cuu-xang-dau-'.preg_replace('/[^a-zA-Z0-9_-]+/', '-', $gvKey).'.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$filename.'"'); header('Cache-Control: max-age=0');
$writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007'); $writer->save('php://output'); exit;