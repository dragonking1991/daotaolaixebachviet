<?php
/*
 * Bộ khung import dùng chung cho Đào tạo và Xăng dầu:
 *  - đặc tả cột từng loại import (file mẫu + checklist), sinh file mẫu .xlsx;
 *  - giữ file đã xem trước (không phải chọn lại file khi xác nhận);
 *  - lịch sử import (thêm/cập nhật/lỗi, file sao lưu, hoàn tác) và danh sách lỗi tải về;
 *  - thông báo kết quả cố định (không tự chuyển trang).
 * Tiêu đề cột trong đặc tả phải khớp bộ nhận diện của từng importer (xem scripts/regression_import_kit.php).
 */

function ik_specs()
{
	return array(
		'daotao.khoa' => array(
			'title' => 'Khóa đào tạo', 'section' => 'khoa', 'upload' => 'index.php?com=daotao&act=uploadKhoa', 'list' => 'index.php?com=daotao&act=khoa',
			'required' => array('Mã khóa' => 'Mã khóa duy nhất, ví dụ 79044K261019'),
			'optional' => array('Tên khóa' => 'Ví dụ K125-B(STD)', 'Hạng' => 'B1, B11, C1, C, CE…', 'Ngày khai giảng' => 'dd/mm/yyyy', 'Ngày kết thúc' => 'dd/mm/yyyy (ngày mãn khóa)', 'Hệ đào tạo' => 'BT / CK / DAT'),
			'sample' => array('Mã khóa' => '79044K261019', 'Tên khóa' => 'K125-B(STD)', 'Hạng' => 'B11', 'Ngày khai giảng' => '01/09/2026', 'Ngày kết thúc' => '30/11/2026', 'Hệ đào tạo' => 'CK'),
			'notes' => array('Khóa đã có (trùng mã) sẽ được cập nhật, không tạo trùng.'),
		),
		'daotao.hocvien' => array(
			'title' => 'Học viên', 'section' => 'hocvien', 'upload' => 'index.php?com=daotao&act=uploadHocvien', 'list' => 'index.php?com=daotao&act=hocvien', 'preview' => true,
			'required' => array('Mã đăng ký' => 'Mã học viên trên hệ thống sát hạch', 'Số CCCD' => '12 số, giữ số 0 đầu (định dạng ô Text)'),
			'optional' => array('Họ và tên' => '', 'Ngày sinh' => 'dd/mm/yyyy', 'Hạng' => 'B1, B11, C1…', 'Khóa' => 'Mã khóa; để trống thì chọn khóa trên form', 'Giáo viên' => 'Họ tên giáo viên', 'Người nộp hồ sơ' => '', 'Hệ đào tạo' => 'BT / CK / DAT', 'Số tiền đã đóng' => 'VNĐ', 'Số tiền còn thiếu' => 'VNĐ'),
			'sample' => array('Mã đăng ký' => '79044-20260901-000001', 'Số CCCD' => '079200000001', 'Họ và tên' => 'NGUYỄN VĂN A', 'Ngày sinh' => '15/10/1999', 'Hạng' => 'B11', 'Khóa' => '79044K261019', 'Giáo viên' => 'Lưu Văn Huy', 'Người nộp hồ sơ' => 'Trần B', 'Hệ đào tạo' => 'CK', 'Số tiền đã đóng' => '5000000', 'Số tiền còn thiếu' => '0'),
			'notes' => array('Trùng (khóa, CCCD) thì cập nhật hồ sơ đã có.', 'Sau khi import, học viên đạt DAT được đồng bộ sang Xăng dầu.'),
		),
		'daotao.giaovien' => array(
			'title' => 'Giáo viên', 'section' => 'giaovien', 'upload' => 'index.php?com=daotao&act=uploadGiaovien', 'list' => 'index.php?com=daotao&act=giaovien', 'preview' => true,
			'required' => array('Số CCCD' => '12 số, định dạng ô Text'),
			'optional' => array('Họ tên đệm' => '', 'Tên GV' => '', 'Ngày sinh' => 'dd/mm/yyyy', 'Giới tính' => 'Nam / Nữ', 'Hạng GPLX' => '', 'Số GPLX' => '', 'Ngày HH GPLX' => 'dd/mm/yyyy', 'Số điện thoại' => '', 'Địa chỉ' => '', 'Hạng đào tạo được phép đào tạo' => '', 'Ghi chú' => ''),
			'sample' => array('Số CCCD' => '079080000001', 'Họ tên đệm' => 'Lưu Văn', 'Tên GV' => 'Huy', 'Ngày sinh' => '01/02/1980', 'Giới tính' => 'Nam', 'Hạng GPLX' => 'C', 'Số GPLX' => '790123456789', 'Ngày HH GPLX' => '01/02/2030', 'Số điện thoại' => '0900000000', 'Địa chỉ' => 'TP.HCM', 'Hạng đào tạo được phép đào tạo' => 'B1, B11', 'Ghi chú' => ''),
			'notes' => array('Giáo viên mới có mật khẩu cổng GV mặc định là CCCD và phải đổi ở lần đăng nhập đầu.'),
		),
		'daotao.xe' => array(
			'title' => 'Xe tập lái', 'section' => 'xe', 'upload' => 'index.php?com=daotao&act=uploadXe', 'list' => 'index.php?com=daotao&act=xe',
			'required' => array('Biển số' => 'Ví dụ 51A-123.45'),
			'optional' => array('Hạng xe' => '', 'Hạng đào tạo' => '', 'Số đăng ký' => '', 'Loại xe' => '', 'Nhãn hiệu' => '', 'Giáo viên' => '', 'Năm sản xuất' => '', 'Số giấy phép xe tập lái' => '', 'Ngày hết hạn giấy phép xe tập lái' => 'dd/mm/yyyy', 'Ngày hết hạn GCNKD' => 'dd/mm/yyyy'),
			'sample' => array('Biển số' => '51A-123.45', 'Hạng xe' => 'B', 'Hạng đào tạo' => 'B11', 'Số đăng ký' => '123456', 'Loại xe' => 'Ô tô con', 'Nhãn hiệu' => 'Toyota Vios', 'Giáo viên' => 'Lưu Văn Huy', 'Năm sản xuất' => '2022', 'Số giấy phép xe tập lái' => '79-0001', 'Ngày hết hạn giấy phép xe tập lái' => '31/12/2027', 'Ngày hết hạn GCNKD' => '30/06/2027'),
		),
		'daotao.htkh' => array(
			'title' => 'Số giấy HTKH', 'section' => 'htkh', 'upload' => 'index.php?com=daotao&act=uploadHtkh', 'list' => 'index.php?com=daotao&act=htkh',
			'required' => array('CCCD' => '12 số', 'Mã HV' => 'Mã đăng ký học viên', 'Số giấy HTKH' => ''),
			'optional' => array('Số quyết định' => '', 'Ngày cấp' => 'dd/mm/yyyy', 'Ghi chú' => ''),
			'sample' => array('CCCD' => '079200000001', 'Mã HV' => '79044-20260901-000001', 'Số giấy HTKH' => 'HTKH-0001', 'Số quyết định' => '123/QĐ', 'Ngày cấp' => '15/12/2026', 'Ghi chú' => ''),
		),
		'daotao.lythuyet' => array(
			'title' => 'Lý thuyết / Mô phỏng', 'section' => 'lythuyet', 'upload' => 'index.php?com=daotao&act=uploadLythuyet', 'list' => 'index.php?com=daotao&act=lythuyet', 'system_export' => 'phần mềm học lý thuyết trực tuyến',
			'required' => array('Mã đăng nhập / CCCD' => '', 'Tiến độ hoàn thành' => '%'),
			'optional' => array('Điểm kiểm tra' => 'Thang 10', 'Xem giáo trình / Ôn luyện / Kiểm tra (giờ)' => 'Cột thời lượng theo môn'),
			'notes' => array('Mỗi file một môn; hệ thống tự nhận môn theo tên file và cho chỉnh lại trước khi import.'),
		),
		'daotao.cabin' => array(
			'title' => 'Kết quả Cabin', 'section' => 'cabinkq', 'upload' => 'index.php?com=daotao&act=uploadCabin', 'list' => 'index.php?com=daotao&act=cabinkq', 'system_export' => 'phần mềm Cabin',
			'required' => array('Mã học viên' => ''),
			'optional' => array('Họ và tên' => '', 'Ngày sinh' => '', 'Khóa' => '', 'Tổng thời gian đào tạo' => '', 'Tổng số nội dung' => '', 'Ghi chú / Kết quả' => ''),
		),
		'daotao.dat' => array(
			'title' => 'DAT (phiên học trên đường)', 'section' => 'dat', 'upload' => 'index.php?com=daotao&act=uploadDat', 'list' => 'index.php?com=daotao&act=dat', 'system_export' => 'thiết bị DAT',
			'required' => array('Mã phiên học' => '', 'Mã học viên' => ''),
			'optional' => array('Thời gian bắt đầu / kết thúc phiên' => '', 'Thời gian thực hành (giờ)' => '', 'Quãng đường thực hành (km)' => '', 'Mã khóa học' => '', 'Họ và tên giáo viên' => '', 'Hạng đào tạo' => '', 'Biển số xe' => '', 'Trạng thái' => ''),
		),
		'daotao.kythi' => array(
			'title' => 'Kỳ kiểm tra', 'section' => 'kythi', 'list' => 'index.php?com=daotao&act=kythi',
			'required' => array('CCCD' => '', 'Họ và tên' => ''),
			'optional' => array('Mã HV' => '', 'Năm sinh' => '', 'Hạng' => '', 'Mã khóa' => '', 'Tên khóa' => '', 'Giáo viên' => ''),
		),
		'daotao.sathach' => array(
			'title' => 'Kết quả sát hạch', 'section' => 'sathach', 'upload' => 'index.php?com=daotao&act=uploadSathach', 'list' => 'index.php?com=daotao&act=sathach', 'system_export' => 'Sở GTVT / phần mềm sát hạch',
			'required' => array('Theo mẫu kết quả sát hạch của Sở' => 'Giữ nguyên tiêu đề, không đổi tên sheet'),
			'optional' => array(),
		),
		'xangdau.hocvien' => array(
			'title' => 'Học viên XD (mẫu BC1 có tài chính)', 'section' => 'xd_import', 'upload' => 'index.php?com=xangdau&act=uploadHocvien', 'list' => 'index.php?com=xangdau&act=hocvien', 'preview' => true,
			'required' => array('Họ và tên' => '', 'Số CMND/HC' => '9–12 số, giữ số 0 đầu', 'Hệ đào tạo' => 'BT / CK / DAT (hoặc chọn nhóm mặc định trên form)', 'Giáo viên' => 'Bắt buộc với học viên mới'),
			'optional' => array('Khóa' => 'Để trống: tra theo họ tên + CCCD bên Đào tạo', 'Ngày sinh' => 'dd/mm/yyyy', 'Người nộp hồ sơ' => '', 'Ngày nộp hồ sơ' => 'dd-mm-yyyy hoặc dd/mm/yyyy', 'Số tiền học phí' => 'VNĐ', 'Miễn giảm' => 'VNĐ', 'Số tiền đã đóng' => 'VNĐ', 'Số tiền còn thiếu' => 'VNĐ', 'Số tiền tạm ứng' => 'VNĐ', 'Thanh toán Lương' => 'VNĐ'),
			'sample' => array('Họ và tên' => 'NGUYỄN VĂN A', 'Số CMND/HC' => '079200000001', 'Hệ đào tạo' => 'CK', 'Giáo viên' => 'Lưu Văn Huy', 'Khóa' => '79044K261019', 'Ngày sinh' => '15/10/1999', 'Người nộp hồ sơ' => 'Trần B', 'Ngày nộp hồ sơ' => '10-07-2026', 'Số tiền học phí' => '15000000', 'Miễn giảm' => '0', 'Số tiền đã đóng' => '10000000', 'Số tiền còn thiếu' => '5000000', 'Số tiền tạm ứng' => '0', 'Thanh toán Lương' => '0'),
			'notes' => array('Luôn có bước xem trước → xác nhận.', 'Nhóm BT/CK/DAT theo Excel kể cả hồ sơ đã thanh toán; ô nhóm trống giữ nhóm cũ.', 'Tiền là nguyên đồng VNĐ, không nhân 1.000.', 'CCCD lặp trong file sẽ chặn toàn bộ file.'),
		),
		'xangdau.hoadon' => array(
			'title' => 'Hóa đơn xăng dầu', 'section' => 'xd_import', 'upload' => 'index.php?com=xangdau&act=uploadHoadon', 'list' => 'index.php?com=xangdau&act=hoadon',
			'required' => array('Số hóa đơn' => '', 'Số tiền HĐ' => 'Số trong ô (nghìn đồng) hoặc chuỗi đầy đủ "1.000.000"', 'GV' => 'Tên giáo viên'),
			'optional' => array('Ngày' => 'dd/mm/yyyy', 'Thông tin bán hàng' => '', 'Chi tiết' => '', 'Biển số xe' => '', 'Hợp lệ' => 'x = hợp lệ', 'Note1' => ''),
			'sample' => array('Số hóa đơn' => '0001234', 'Số tiền HĐ' => '608.75', 'GV' => 'Lưu Văn Huy', 'Ngày' => '05/10/2026', 'Thông tin bán hàng' => 'CH Xăng dầu số 1', 'Chi tiết' => 'RON95', 'Biển số xe' => '51A-123.45', 'Hợp lệ' => 'x', 'Note1' => ''),
			'sheet' => 'HĐơn',
			'numeric' => array('Số tiền HĐ'),
			'notes' => array('Đặt tên sheet chứa hóa đơn là "HĐơn" nếu file có nhiều sheet.', 'Kỳ đã import thì tích "Đồng bộ lại" để cập nhật cờ hợp lệ / kỳ của hóa đơn chưa quyết toán.'),
		),
	);
}

function ik_spec($key)
{
	$specs = ik_specs();
	return isset($specs[$key]) ? $specs[$key] : null;
}

function ik_esc($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* Thư mục riêng tư (backups/ bị chặn truy cập web bằng .htaccess). */
function ik_private_dir($sub)
{
	$dir = dirname(__DIR__).'/backups/'.$sub;
	if(!is_dir($dir)) @mkdir($dir, 0700, true);
	return is_dir($dir) && is_writable($dir) ? $dir : '';
}

/* ===================== File mẫu ===================== */

function ik_template_output($key)
{
	$spec = ik_spec($key);
	if(!$spec || empty($spec['sample'])) return false;
	@ini_set('memory_limit', '512M');
	require_once __DIR__.'/PHPExcel.php';
	$headers = array_merge(array_keys($spec['required']), array_keys($spec['optional']));
	$excel = new PHPExcel();
	$sheet = $excel->getActiveSheet();
	$sheet->setTitle(!empty($spec['sheet']) ? $spec['sheet'] : 'Du lieu');
	foreach($headers as $col => $header)
	{
		$letter = PHPExcel_Cell::stringFromColumnIndex($col);
		$sheet->setCellValueExplicit($letter.'1', $header, PHPExcel_Cell_DataType::TYPE_STRING);
		$sample = (string)($spec['sample'][$header] ?? '');
		/* Cột số (vd tiền hóa đơn theo nghìn đồng) giữ kiểu số như file thật; còn lại là Text để giữ số 0 đầu. */
		if(in_array($header, $spec['numeric'] ?? array(), true) && is_numeric($sample))
			$sheet->setCellValueExplicit($letter.'2', (float)$sample, PHPExcel_Cell_DataType::TYPE_NUMERIC);
		else
		{
			$sheet->setCellValueExplicit($letter.'2', $sample, PHPExcel_Cell_DataType::TYPE_STRING);
			$sheet->getStyle($letter.'2:'.$letter.'500')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
		}
		$sheet->getColumnDimension($letter)->setAutoSize(true);
		$fill = isset($spec['required'][$header]) ? 'FFFDE68A' : 'FFE5E7EB';
		$sheet->getStyle($letter.'1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB($fill);
		$note = isset($spec['required'][$header]) ? $spec['required'][$header] : $spec['optional'][$header];
		if($note !== '') $sheet->getComment($letter.'1')->getText()->createTextRun($note);
	}
	$last = PHPExcel_Cell::stringFromColumnIndex(count($headers) - 1);
	$sheet->getStyle('A1:'.$last.'1')->getFont()->setBold(true);
	$sheet->freezePane('A2');

	$guide = $excel->createSheet();
	$guide->setTitle('Huong dan');
	$lines = array('Hướng dẫn: '.$spec['title'], '', 'Dòng 2 ở sheet dữ liệu là ví dụ — xóa hoặc thay bằng dữ liệu thật trước khi import.', 'Cột tô vàng là bắt buộc; cột xám là tùy chọn (có thể xóa cột không dùng).', 'Giữ nguyên tiêu đề cột ở dòng 1; thứ tự cột có thể thay đổi.', 'Ngày theo dd/mm/yyyy; CCCD để định dạng Text để giữ số 0 đầu.');
	foreach(($spec['notes'] ?? array()) as $note) $lines[] = $note;
	$lines[] = '';
	$lines[] = 'Cột bắt buộc:';
	foreach($spec['required'] as $header => $desc) $lines[] = ' • '.$header.($desc !== '' ? ' — '.$desc : '');
	if($spec['optional']) { $lines[] = 'Cột tùy chọn:'; foreach($spec['optional'] as $header => $desc) $lines[] = ' • '.$header.($desc !== '' ? ' — '.$desc : ''); }
	foreach($lines as $i => $line) $guide->setCellValueExplicit('A'.($i + 1), $line, PHPExcel_Cell_DataType::TYPE_STRING);
	$guide->getStyle('A1')->getFont()->setBold(true)->setSize(13);
	$guide->getColumnDimension('A')->setWidth(110);
	$excel->setActiveSheetIndex(0);

	$filename = 'mau-import-'.preg_replace('/[^a-z0-9]+/', '-', strtolower($key)).'.xlsx';
	if(!headers_sent())
	{
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Cache-Control: max-age=0');
	}
	PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save('php://output');
	return true;
}

/* ===================== Giữ file sau xem trước ===================== */

function ik_stage_upload($key)
{
	if(empty($_FILES['file-excel']['tmp_name']) || (int)($_FILES['file-excel']['error'] ?? 1) !== UPLOAD_ERR_OK) return '';
	$dir = ik_private_dir('import_stage');
	if($dir === '') return '';
	foreach(glob($dir.'/*.upload') ?: array() as $old) if(filemtime($old) < time() - 7200) @unlink($old);
	$ext = strtolower(pathinfo((string)$_FILES['file-excel']['name'], PATHINFO_EXTENSION));
	if(!in_array($ext, array('xlsx', 'xls', 'xlsm'), true)) return '';
	$token = bin2hex(random_bytes(16));
	$path = $dir.'/'.$token.'.upload';
	if(!@copy($_FILES['file-excel']['tmp_name'], $path)) return '';
	@chmod($path, 0600);
	$fields = array();
	foreach($_POST as $name => $value)
		if(is_scalar($value) && !in_array($name, array('preview', '_csrf', 'dt_ajax', 'staged_token'), true)) $fields[$name] = (string)$value;
	if(!isset($_SESSION['ik_stage']) || !is_array($_SESSION['ik_stage'])) $_SESSION['ik_stage'] = array();
	foreach($_SESSION['ik_stage'] as $oldToken => $info) if(($info['time'] ?? 0) < time() - 7200) unset($_SESSION['ik_stage'][$oldToken]);
	$_SESSION['ik_stage'][$token] = array('key' => $key, 'path' => $path, 'name' => (string)$_FILES['file-excel']['name'],
		'size' => (int)$_FILES['file-excel']['size'], 'fields' => $fields, 'time' => time());
	return $token;
}

/* Khi người dùng bấm "Xác nhận import" ở trang xem trước: nạp lại file đã giữ như vừa upload. */
function ik_restore_staged($key)
{
	$token = isset($_POST['staged_token']) ? preg_replace('/[^a-f0-9]/', '', (string)$_POST['staged_token']) : '';
	if($token === '' || empty($_SESSION['ik_stage'][$token])) return false;
	$info = $_SESSION['ik_stage'][$token];
	if($info['key'] !== $key || !is_file($info['path'])) return false;
	$_FILES['file-excel'] = array('name' => $info['name'], 'type' => '', 'tmp_name' => $info['path'], 'error' => UPLOAD_ERR_OK, 'size' => filesize($info['path']));
	foreach($info['fields'] as $name => $value) if(!isset($_POST[$name])) { $_POST[$name] = $value; $_REQUEST[$name] = $value; }
	unset($_POST['preview'], $_REQUEST['preview']);
	$GLOBALS['ik_staged_token'] = $token;
	return true;
}

function ik_clear_staged()
{
	$token = $GLOBALS['ik_staged_token'] ?? '';
	if($token === '' || empty($_SESSION['ik_stage'][$token])) return;
	@unlink($_SESSION['ik_stage'][$token]['path']);
	unset($_SESSION['ik_stage'][$token]);
}

/* ===================== Lịch sử import ===================== */

function ik_ensure_table()
{
	global $d;
	static $ready = false;
	if($ready) return;
	$d->rawQuery("CREATE TABLE IF NOT EXISTS table_import_history (
		id INT AUTO_INCREMENT PRIMARY KEY,
		scope VARCHAR(20) NOT NULL DEFAULT '',
		module VARCHAR(60) NOT NULL DEFAULT '',
		filename VARCHAR(255) NOT NULL DEFAULT '',
		username VARCHAR(255) NOT NULL DEFAULT '',
		created INT NOT NULL DEFAULT 0,
		so_them INT NOT NULL DEFAULT 0,
		so_capnhat INT NOT NULL DEFAULT 0,
		so_loi INT NOT NULL DEFAULT 0,
		status VARCHAR(20) NOT NULL DEFAULT 'done',
		message TEXT NULL,
		errors MEDIUMTEXT NULL,
		backup VARCHAR(255) NOT NULL DEFAULT '',
		batch VARCHAR(60) NOT NULL DEFAULT '',
		link VARCHAR(500) NOT NULL DEFAULT '',
		ids MEDIUMTEXT NULL,
		undone_at INT NOT NULL DEFAULT 0,
		undone_by VARCHAR(255) NOT NULL DEFAULT '',
		KEY idx_scope_created (scope, created)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	$ready = true;
}

function ik_username()
{
	global $login_admin;
	return isset($_SESSION[$login_admin]['username']) ? (string)$_SESSION[$login_admin]['username'] : '';
}

/* Tách các dòng lỗi kiểu "Dòng 15: ..." từ thông báo import. */
function ik_error_lines($message)
{
	$text = html_entity_decode(strip_tags(str_ireplace(array('<br>', '<br/>', '<br />'), "\n", (string)$message)), ENT_QUOTES, 'UTF-8');
	$parts = preg_split('/\n|;\s*(?=Dòng\s+\d)/u', $text);
	$lines = array();
	foreach($parts as $part)
	{
		$part = trim($part);
		if(preg_match('/Dòng\s+\d+/u', $part)) $lines[] = preg_replace('/^.*?(Dòng\s+\d+)/u', '$1', $part);
	}
	return array_values(array_unique($lines));
}

function ik_history_add($scope, $module, $data)
{
	global $d;
	ik_ensure_table();
	$row = array_merge(array('scope' => $scope, 'module' => $module, 'filename' => '', 'username' => ik_username(), 'created' => time(),
		'so_them' => 0, 'so_capnhat' => 0, 'so_loi' => 0, 'status' => 'done', 'message' => '', 'errors' => '[]', 'backup' => '', 'batch' => '', 'link' => ''), $data);
	$row['filename'] = mb_substr((string)$row['filename'], 0, 255);
	$row['backup'] = basename((string)$row['backup']);
	if(is_array($row['errors'])) $row['errors'] = json_encode(array_slice(array_values($row['errors']), 0, 2000), JSON_UNESCAPED_UNICODE);
	if(isset($row['ids']) && is_array($row['ids'])) $row['ids'] = implode(',', array_map('intval', $row['ids']));
	$id = $d->insert('import_history', $row);
	$GLOBALS['ik_history_id'] = $id ? (int)$id : 0;
	return $GLOBALS['ik_history_id'];
}

function ik_history_update($id, $data)
{
	global $d;
	if((int)$id <= 0) return;
	ik_ensure_table();
	if(isset($data['errors']) && is_array($data['errors'])) $data['errors'] = json_encode(array_slice(array_values($data['errors']), 0, 2000), JSON_UNESCAPED_UNICODE);
	$d->where('id', (int)$id);
	$d->update('import_history', $data);
}

function ik_history_get($id, $scope)
{
	global $d;
	ik_ensure_table();
	$row = $d->rawQueryOne('select * from table_import_history where id = ? and scope = ? limit 0,1', array((int)$id, $scope));
	return $row ?: null;
}

function ik_history_list($scope, $limit = 100)
{
	global $d;
	ik_ensure_table();
	$rows = $d->rawQuery('select * from table_import_history where scope = ? order by id desc limit 0,'.(int)$limit, array($scope));
	return is_array($rows) ? $rows : array();
}

/* Lượt import gần nhất còn hiệu lực của phạm vi (chỉ lượt này được hoàn tác, tránh ghi đè thay đổi của lượt sau). */
function ik_latest_done_id($scope, $modules = null)
{
	global $d;
	ik_ensure_table();
	$sql = "select id from table_import_history where scope = ? and status = 'done'";
	$params = array($scope);
	if(is_array($modules) && $modules)
	{
		$sql .= ' and module in ('.implode(',', array_fill(0, count($modules), '?')).')';
		$params = array_merge($params, array_values($modules));
	}
	$row = $d->rawQueryOne($sql.' order by id desc limit 0,1', $params);
	return $row ? (int)$row['id'] : 0;
}

function ik_errors_csv($row)
{
	$errors = json_decode((string)$row['errors'], true);
	if(!is_array($errors)) $errors = array();
	$filename = 'loi-import-'.(int)$row['id'].'.csv';
	if(!headers_sent())
	{
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
	}
	$out = fopen('php://output', 'w');
	fwrite($out, "\xEF\xBB\xBF");
	fputcsv($out, array('Dòng', 'Nội dung lỗi', 'File', 'Thời gian'));
	foreach($errors as $line)
	{
		$rowNo = preg_match('/Dòng\s+(\d+)/u', $line, $m) ? $m[1] : '';
		$text = trim(preg_replace('/^Dòng\s+\d+\s*[:\-–]?\s*/u', '', $line));
		/* Chặn công thức khi mở bằng Excel. */
		if($text !== '' && strpos('=+-@', $text[0]) !== false) $text = "'".$text;
		fputcsv($out, array($rowNo, $text, $row['filename'], date('d/m/Y H:i', (int)$row['created'])));
	}
	fclose($out);
}

/* ===================== Thông báo kết quả cố định ===================== */

/* Lưu thông báo hiển thị ở đầu trang tiếp theo, kèm số liệu và liên kết (xem dữ liệu vừa import, tải lỗi, lịch sử). */
function ik_notice($scope, $message, $success, $extra = array())
{
	$plain = html_entity_decode(strip_tags(str_ireplace(array('<br>', '<br/>', '<br />'), "\n", (string)$message)), ENT_QUOTES, 'UTF-8');
	$historyId = (int)($extra['history_id'] ?? ($GLOBALS['ik_history_id'] ?? 0));
	$links = isset($extra['links']) && is_array($extra['links']) ? $extra['links'] : array();
	if($historyId > 0)
	{
		$row = ik_history_get($historyId, $scope);
		if($row)
		{
			if(!empty($row['link']) && $success) array_unshift($links, array('label' => 'Xem dữ liệu vừa import', 'url' => $row['link'], 'icon' => 'fa-eye', 'primary' => true));
			$errors = json_decode((string)$row['errors'], true);
			if(is_array($errors) && $errors) $links[] = array('label' => 'Tải danh sách lỗi ('.count($errors).')', 'url' => 'index.php?com='.$scope.'&act=importErrors&id='.$historyId, 'icon' => 'fa-file-csv');
		}
	}
	$links[] = array('label' => 'Lịch sử import', 'url' => 'index.php?com='.$scope.'&act=importHistory', 'icon' => 'fa-history');
	$_SESSION['dt_import_notice'] = array('message' => $plain, 'success' => (bool)$success, 'time' => time(),
		'title' => $extra['title'] ?? ($success ? 'Import hoàn tất' : 'Import chưa thành công'),
		'stats' => $extra['stats'] ?? array(), 'links' => $links, 'steps' => !isset($extra['steps']) || $extra['steps']);
}

/* Dung lượng upload tối đa thực tế (nhỏ nhất giữa upload_max_filesize và post_max_size). */
function ik_upload_limit_bytes()
{
	$toBytes = function($value) {
		$value = trim((string)$value);
		if($value === '') return 0;
		$unit = strtolower(substr($value, -1));
		$number = (float)$value;
		if($unit === 'g') $number *= 1024 * 1024 * 1024;
		elseif($unit === 'm') $number *= 1024 * 1024;
		elseif($unit === 'k') $number *= 1024;
		return (int)$number;
	};
	$limits = array_filter(array($toBytes(ini_get('upload_max_filesize')), $toBytes(ini_get('post_max_size'))));
	return $limits ? min($limits) : 0;
}
