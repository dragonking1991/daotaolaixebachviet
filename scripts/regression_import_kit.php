<?php
/*
 * Kiểm thử luồng import/đồng bộ sau đợt rà soát: lỗi ghi DB, liên kết DAT, ngày không tồn tại, xem trước khớp import,
 * đọc hết file, kỳ hóa đơn khi đồng bộ lại; bộ import dùng chung (file mẫu, giữ file, lịch sử, tải lỗi, hoàn tác,
 * đồng bộ chạy thử, giao diện). Mọi ghi dữ liệu dùng bảng TEMPORARY; file sao lưu/tạm tạo ra được dọn sạch.
 */
if(PHP_SAPI !== 'cli' || ($argv[1] ?? '') !== '--local-db') exit("Usage: php scripts/regression_import_kit.php --local-db\n");
define('ROOT', dirname(__DIR__));
define('LIBRARIES', ROOT.'/libraries/');
define('SOURCES', ROOT.'/admin/sources/');
define('TEMPLATE', ROOT.'/admin/templates/');
define('LAYOUT', 'layout/');
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'POST';
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
require LIBRARIES.'PHPExcel.php';
require LIBRARIES.'admin_csrf.php';
require LIBRARIES.'import_kit.php';
require_once LIBRARIES.'course_options.php';
foreach(array('schema','config_daotao','helpers','ops','excel_open','khoa','hocvien','xe','giaovien','htkh','taichinh','dat','lythuyet','cabin_kq','thuchanh_hinh') as $module) require_once SOURCES.'daotao/'.$module.'.php';
foreach(array('classes','permissions','schema','daotao_sync','helpers','excel_cell','config','excel_convert','excel_stream','excel_open','hoadon_import','hocvien_crud','khoa','hocvien_import','hocvien_financial_import','import_kit') as $module) require_once SOURCES.'xangdau/'.$module.'.php';
function dt_p($section, $action = 'view') { return array('daotao_man'); }
function dt_can($section, $action = 'view') { return true; }

class IkStop extends Exception {}
class IkFunctions
{
	public $last = null;
	public function check_permission() { return false; }
	public function transfer($message, $url = '', $success = true) { $this->last = array('message' => $message, 'url' => $url, 'success' => $success); throw new IkStop('transfer'); }
	public function redirect($url = '', $response = null) { $this->last = array('redirect' => $url) + (array)$this->last; throw new IkStop('redirect'); }
	public function pagination($total, $perPage, $page, $url) { return ''; }
}
$func = new IkFunctions();
$login_admin = 'ik_test';
$_SESSION = array('ik_test' => array('active' => true, 'username' => 'regression'));
function ik_check($condition, $message) { if(!$condition) throw new RuntimeException($message); }
function ik_run($callback)
{
	global $func;
	$func->last = null;
	try { $callback(); } catch(IkStop $stop) {}
	return $func->last;
}
function ik_reset_request($post = array(), $get = array())
{
	$_POST = $post; $_GET = $get; $_REQUEST = array_merge($get, $post); $_FILES = array();
	unset($GLOBALS['ik_history_id'], $GLOBALS['dt_import_backup'], $GLOBALS['ik_staged_token'], $_SESSION['dt_import_notice']);
}
function ik_xlsx($rows, $sheetTitle = 'Du lieu', $textCols = array())
{
	$excel = new PHPExcel();
	$sheet = $excel->getActiveSheet();
	$sheet->setTitle($sheetTitle);
	foreach($rows as $r => $row)
		foreach(array_values($row) as $c => $value)
			$sheet->setCellValueExplicitByColumnAndRow($c, $r + 1, (string)$value, PHPExcel_Cell_DataType::TYPE_STRING);
	$path = tempnam(sys_get_temp_dir(), 'ikx').'.xlsx';
	PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($path);
	$GLOBALS['ik_temp_files'][] = $path;
	return $path;
}
function ik_attach($path, $name)
{
	$_FILES = array('file-excel' => array('name' => $name, 'type' => '', 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'size' => filesize($path)));
}
function ik_temp_table($table)
{
	global $d;
	$definition = $d->rawQuery('show create table #_'.$table);
	ik_check($d->getLastErrorCode() === '00000' && $definition, 'Missing table '.$table);
	$sql = preg_replace('/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $definition[0]['Create Table']);
	$d->rawQuery(preg_replace('/ AUTO_INCREMENT=\d+/', '', $sql));
	ik_check($d->getLastErrorCode() === '00000', 'Cannot shadow '.$table);
}

set_exception_handler(function($error) { fwrite(STDERR, 'FAIL: '.$error->getMessage().' @'.$error->getFile().':'.$error->getLine()."\n"); ik_cleanup(); exit(1); });
/* Không để cơ chế giữ 15 bản sao lưu gần nhất xóa bản sao lưu thật trong lúc kiểm thử. */
$GLOBALS['dt_backup_keep'] = 0;
$backupDir = ROOT.'/backups';
$backupsBefore = glob($backupDir.'/*') ?: array();
$stageBefore = glob($backupDir.'/import_stage/*') ?: array();
$GLOBALS['ik_temp_files'] = array();
function ik_cleanup()
{
	global $backupDir, $backupsBefore, $stageBefore;
	foreach(array_diff(glob($backupDir.'/*') ?: array(), $backupsBefore) as $file) if(is_file($file)) @unlink($file);
	foreach(array_diff(glob($backupDir.'/import_stage/*') ?: array(), $stageBefore) as $file) @unlink($file);
	foreach($GLOBALS['ik_temp_files'] as $file) @unlink($file);
}

dt_ensure_tables();
xd_ensure_tables();
ik_ensure_table();
foreach(array('dt_khoa','dt_hocvien','dt_giaovien','dt_xe','dt_htkh','dt_import_log','dt_audit','dt_setting','xd_hocvien','xd_hoadon','xd_bangke','import_history') as $table) ik_temp_table($table);

/* 1. Lỗi ghi DB được phát hiện trong transaction (rawQuery trả mảng rỗng khi lỗi). */
$d->startTransaction();
$result = $d->rawQuery('update #_xd_hocvien set cot_khong_ton_tai = 1 where id = -1');
ik_check(is_array($result) && $result === array() && $d->transactionFailed(), 'Failed statement must be tracked in the transaction');
$d->rollback();
$d->startTransaction();
$d->rawQuery('select 1');
ik_check(!$d->transactionFailed(), 'A new transaction must start clean');
$d->rollback();
echo "PASS DB failures inside transactions are detected (rawQuery returns array on error)\n";

/* 2. File mẫu đúng tiêu đề, đọc lại được bằng importer thật. */
foreach(array('daotao.khoa','daotao.hocvien','daotao.giaovien','daotao.xe','daotao.htkh','xangdau.hocvien','xangdau.hoadon') as $key)
{
	ob_start(); ik_check(ik_template_output($key), 'Template output '.$key); $bytes = ob_get_clean();
	ik_check(substr($bytes, 0, 2) === 'PK' && strlen($bytes) > 3000, 'Template must be xlsx: '.$key);
	$path = sys_get_temp_dir().'/ik_tpl_'.str_replace('.', '_', $key).'.xlsx';
	file_put_contents($path, $bytes); $GLOBALS['ik_temp_files'][] = $path;
	$GLOBALS['ik_templates'][$key] = $path;
	$book = PHPExcel_IOFactory::load($path);
	$spec = ik_spec($key);
	$headers = array();
	foreach($book->getSheet(0)->getRowIterator(1, 1) as $row) foreach($row->getCellIterator() as $cell) if((string)$cell->getValue() !== '') $headers[] = (string)$cell->getValue();
	ik_check($headers === array_merge(array_keys($spec['required']), array_keys($spec['optional'])), 'Template headers '.$key);
	ik_check($book->getSheetCount() === 2 && $book->getSheet(1)->getTitle() === 'Huong dan', 'Template guide sheet '.$key);
}
ik_check(!ik_template_output('daotao.dat') && !ik_template_output('daotao.unknown'), 'System-export imports have no template');
echo "PASS templates download with exact headers, sample row and guide sheet\n";

/* 3. Import khóa từ file mẫu; ngày không tồn tại báo theo dòng; lịch sử + thẻ kết quả. */
$tpl = $GLOBALS['ik_templates']['daotao.khoa'];
ik_reset_request(); ik_attach($tpl, 'mau-khoa.xlsx'); $_GET['act'] = 'uploadKhoaExcel';
ik_run('dt_khoa_upload_excel');
$course = $d->rawQueryOne("select * from #_dt_khoa where ma_khoa = '79044K261019'");
ik_check($course && $course['ten_khoa'] === 'K125-B(STD)' && $course['ngay_khaigiang'] === '2026-09-01' && $course['ngay_manhoa'] === '2026-11-30', 'Course template imports all columns');
$history = $d->rawQueryOne("select * from table_import_history where scope = 'daotao' order by id desc limit 1");
ik_check($history && $history['module'] === 'khoa' && (int)$history['so_them'] === 1 && $history['status'] === 'done' && strpos($history['backup'], 'dt_imp_khoa_') === 0, 'Training import history row');
ik_check(!empty($_SESSION['dt_import_notice']['steps']) && $_SESSION['dt_import_notice']['success'] && strpos(json_encode($_SESSION['dt_import_notice']['links']), 'importHistory') !== false, 'Persistent result notice with history link');
$bad = ik_xlsx(array(array('Mã khóa', 'Ngày khai giảng'), array('K-BAD', '31/02/2026'), array('K-OK', '28/02/2026')));
ik_reset_request(); ik_attach($bad, 'ngay-sai.xlsx'); $_GET['act'] = 'uploadKhoaExcel';
ik_run('dt_khoa_upload_excel');
ik_check(!$d->rawQueryOne("select id from #_dt_khoa where ma_khoa = 'K-BAD'") && $d->rawQueryOne("select id from #_dt_khoa where ma_khoa = 'K-OK'"), 'Impossible date row must be rejected, valid row kept');
ik_check(strpos($_SESSION['dt_import_notice']['message'], "Dòng 2: ngày khai giảng '31/02/2026' không tồn tại") !== false, 'Row-level date error in notice');
$errHistory = $d->rawQueryOne("select * from table_import_history where scope = 'daotao' order by id desc limit 1");
ik_check(json_decode($errHistory['errors'], true) === array("Dòng 2: ngày khai giảng '31/02/2026' không tồn tại hoặc sai định dạng"), 'Error lines stored for CSV');
ob_start(); ik_errors_csv($errHistory); $csv = ob_get_clean();
ik_check(strpos($csv, "\xEF\xBB\xBF") === 0 && strpos($csv, '2,"ngày khai giảng') !== false, 'Error CSV download');
foreach(array('31/02/2026' => null, '29/02/2024' => '2024-02-29', '2026-02-31' => null, '05-03-2001' => '2001-03-05') as $raw => $expected)
	ik_check(dt_parse_date($raw) === $expected && xd_parse_date($raw) === $expected, 'Strict date '.$raw);
echo "PASS course template import, strict dates with row errors, history, error CSV\n";

/* 4. Đọc tới dòng dữ liệu cuối dù có 45 dòng trống ở giữa. */
$rows = array(array('Mã khóa', 'Tên khóa'), array('K-GAP-1', 'Đầu'));
for($i = 0; $i < 45; $i++) $rows[] = array('', '');
$rows[] = array('K-GAP-2', 'Sau dòng trống');
ik_reset_request(); ik_attach(ik_xlsx($rows), 'dong-trong.xlsx'); $_GET['act'] = 'uploadKhoaExcel';
ik_run('dt_khoa_upload_excel');
ik_check((bool)$d->rawQueryOne("select id from #_dt_khoa where ma_khoa = 'K-GAP-2'"), 'Rows after many blank rows must be imported');
echo "PASS import reads to the last data row (no early stop on blank rows)\n";

/* 5. Xem trước học viên khớp import thật; giữ file để xác nhận không cần chọn lại. */
$cccd = '079200000001';
$d->rawQuery('insert into #_dt_hocvien (id_khoa, ma_hv, cccd, hoten, ngaytao) values (?, ?, ?, ?, ?)', array((int)$course['id'], 'OLD', $cccd, 'Cũ', time()));
$tpl = $GLOBALS['ik_templates']['daotao.hocvien'];
ik_reset_request(array('preview' => '1')); ik_attach($tpl, 'mau-hocvien.xlsx'); $_GET['act'] = 'uploadHocvienExcel';
$template = ''; $dtPreview = null;
ik_run('dt_hocvien_upload_excel');
global $dtPreview, $template;
ik_check($template === 'daotao/import_preview' && $dtPreview['update'] === 1 && $dtPreview['insert'] === 0, 'Preview must report update for existing (course, CCCD) from file Khóa column');
$mapped = array(); foreach($dtPreview['mapping'] as $m) if($m['col'] !== '') $mapped[] = $m['label'];
ik_check(count($mapped) === 11 && $dtPreview['mapping'][0]['col'] === 'A' && $dtPreview['mapping'][0]['header'] === 'Mã đăng ký', 'Preview column mapping');
$token = $dtPreview['staged_token'];
ik_check(preg_match('/^[a-f0-9]{32}$/', $token) && is_file($_SESSION['ik_stage'][$token]['path']), 'Preview keeps the uploaded file');
$prev = $tpl;
$GLOBALS['dtPreview'] = $dtPreview;
ob_start(); include TEMPLATE.'daotao/import_preview_tpl.php'; $previewHtml = ob_get_clean();
ik_check(strpos($previewHtml, 'name="staged_token" value="'.$token.'"') !== false && strpos($previewHtml, 'Xác nhận import 1 dòng') !== false && strpos($previewHtml, 'data-ik-tabs="dt-preview-table"') !== false && strpos($previewHtml, 'class="ik-steps') !== false, 'Preview confirm without reselecting file + tabs + steps');
$fresh = ik_xlsx(array(array('Mã đăng ký', 'Số CCCD', 'Khóa'), array('NEW1', '079200000099', 'K-CHUA-CO')));
ik_reset_request(array('preview' => '1')); ik_attach($fresh, 'moi.xlsx'); $_GET['act'] = 'uploadHocvienExcel';
ik_run('dt_hocvien_upload_excel');
ik_check($GLOBALS['dtPreview']['insert'] === 1 && $GLOBALS['dtPreview']['rows'][0]['data']['khoa'] === 'K-CHUA-CO (khóa mới)', 'Preview marks new course codes');
ik_reset_request(array('staged_token' => $token));
ik_check(ik_restore_staged('daotao.hocvien') && $_FILES['file-excel']['name'] === 'mau-hocvien.xlsx' && !isset($_POST['preview']), 'Confirm restores the staged file');
ik_check(!ik_restore_staged('daotao.giaovien'), 'Staged file is bound to its import type');
$_GET['act'] = 'uploadHocvienExcel';
ik_run('dt_hocvien_upload_excel');
$student = $d->rawQueryOne('select * from #_dt_hocvien where cccd = ? and id_khoa = ?', array($cccd, (int)$course['id']));
ik_check($student['ma_hv'] === '79044-20260901-000001' && $student['hoten'] === 'NGUYỄN VĂN A', 'Confirm imports the staged file');
ik_check(empty($_SESSION['ik_stage'][$token]) && !is_file(ROOT.'/backups/import_stage/'.$token.'.upload'), 'Staged file removed after import');
echo "PASS training preview matches import, mapping, staged confirm without reselect\n";

/* 6. Hoàn tác lượt import Đào tạo gần nhất. */
$last = $d->rawQueryOne("select * from table_import_history where scope = 'daotao' and status = 'done' order by id desc limit 1");
ik_check($last['module'] === 'hocvien' && dt_import_undo_blocker($last) === '', 'Latest training import is undoable');
$older = $d->rawQueryOne("select * from table_import_history where scope = 'daotao' and module = 'khoa' order by id asc limit 1");
ik_check(dt_import_undo_blocker($older) !== '', 'Older imports are not undoable');
ik_reset_request(array('id' => (int)$last['id']));
ik_run('dt_import_undo');
$student = $d->rawQueryOne('select * from #_dt_hocvien where cccd = ? and id_khoa = ?', array($cccd, (int)$course['id']));
ik_check($student['ma_hv'] === 'OLD' && $student['hoten'] === 'Cũ', 'Undo restores pre-import data');
ik_check($d->rawQueryOne('select status from table_import_history where id = ?', array((int)$last['id']))['status'] === 'undone', 'History marked undone');
echo "PASS training undo restores the latest import only\n";

/* 7. Import mẫu cũ XD giữ liên kết DAT; ngày sinh sai báo theo dòng; lọc theo lượt import; hoàn tác + chặn khi đã thanh toán. */
$d->rawQuery("insert into #_xd_hocvien (lan_import, thu_tu_file, ho_ten, cccd, ngaysinh, khoa, nhom, gv_hoten, gv_key, ngaytao, user_tao) values (5, 777, 'HV DAT', '079300000001', '2000-01-01', '79044K261019', 'DAT', 'Thầy Gốc', 'thay goc', 1, 'Đồng bộ DAT')");
$legacy = ik_xlsx(array(array('STT', 'HỌ VÀ TÊN', 'KHÓA', 'Ngày tháng năm sinh', 'Số CCCD', 'NGƯỜI NỘP', 'PHÂN XE / GIÁO VIÊN', 'GHI CHÚ'),
	array('1', 'HV DAT', 'KHOA-FILE', '01/01/2000', '079300000001', 'A', 'Thầy Khác', 'CK'),
	array('2', 'HV MỚI', 'KHOA-FILE', '02/02/2001', '079300000002', 'B', 'Thầy Khác', 'BT')));
ik_reset_request(); ik_attach($legacy, 'mau-cu.xlsx');
ik_run('xd_upload_hocvien_excel');
$dat = $d->rawQueryOne("select * from #_xd_hocvien where cccd = '079300000001'");
ik_check((int)$dat['thu_tu_file'] === 777 && $dat['khoa'] === '79044K261019' && $dat['gv_hoten'] === 'Thầy Gốc' && (int)$dat['lan_import'] === 5 && $dat['nhom'] === 'CK', 'Legacy import keeps DAT link/course/teacher/order, updates group');
$xdHistory = $d->rawQueryOne("select * from table_import_history where scope = 'xangdau' order by id desc limit 1");
ik_check($xdHistory['module'] === 'hocvien' && (int)$xdHistory['so_them'] === 1 && (int)$xdHistory['so_capnhat'] === 1 && count(explode(',', $xdHistory['ids'])) === 2 && strpos($xdHistory['link'], 'import='.$xdHistory['id']) !== false, 'XD history with affected ids and link');
$_REQUEST = $_GET = array('import' => $xdHistory['id']); $curPage = 1;
xd_get_hocvien();
ik_check(count($items) === 2 && $xd_hocvien_total === 2, 'List filter shows exactly the imported rows');
ik_check(xd_import_undo_blocker($xdHistory) === '', 'Latest XD import is undoable');
$d->rawQuery("update #_xd_hocvien set ngay_thanh_toan = '2026-10-31' where cccd = '079300000002'");
ik_check(strpos(xd_import_undo_blocker($xdHistory), 'thanh toán') !== false, 'Payment after import blocks undo');
$d->rawQuery("update #_xd_hocvien set ngay_thanh_toan = null where cccd = '079300000002'");
ik_reset_request(array('id' => (int)$xdHistory['id']));
ik_run('xd_import_undo');
$dat = $d->rawQueryOne("select * from #_xd_hocvien where cccd = '079300000001'");
ik_check($dat['nhom'] === 'DAT' && !$d->rawQueryOne("select id from #_xd_hocvien where cccd = '079300000002'"), 'XD undo restores snapshot (group back, new row removed)');
$badDate = ik_xlsx(array(array('STT', 'HỌ VÀ TÊN', 'KHÓA', 'Ngày tháng năm sinh', 'Số CCCD', 'NGƯỜI NỘP', 'PHÂN XE / GIÁO VIÊN', 'GHI CHÚ'),
	array('1', 'HV NGÀY SAI', 'K', '31/02/2001', '079300000003', 'A', 'Thầy', 'BT')));
ik_reset_request(); ik_attach($badDate, 'ngay-sinh-sai.xlsx');
ik_run('xd_upload_hocvien_excel');
ik_check(!$d->rawQueryOne("select id from #_xd_hocvien where cccd = '079300000003'") && strpos($_SESSION['dt_import_notice']['message'], "Dòng 2: ngày sinh '31/02/2001' không tồn tại") !== false, 'Legacy roster rejects impossible birth date with row');
$failed = $d->rawQueryOne("select * from table_import_history where scope = 'xangdau' order by id desc limit 1");
ik_check($failed['status'] === 'failed' && $failed['filename'] === 'ngay-sinh-sai.xlsx', 'Failed XD import is recorded');
echo "PASS XD legacy import keeps DAT link, row date errors, import filter, undo with payment guard\n";

/* 8. BC1 từ file mẫu: xem trước có bảng cột; xác nhận ghi lịch sử. */
$tpl = $GLOBALS['ik_templates']['xangdau.hocvien'];
ik_reset_request(array('khoa_mac_dinh' => '', 'nhom_mac_dinh' => '')); ik_attach($tpl, 'mau-bc1.xlsx');
$preview = null; ik_run(function() use (&$preview) { $preview = xd_upload_hocvien_excel(); });
global $xd_roster_preview;
ik_check($preview === true && (int)$xd_roster_preview['new'] === 1, 'BC1 template previews one new student');
$mapped = array(); foreach($xd_roster_preview['mapping'] as $m) if($m['col'] !== '') $mapped[] = $m['label'];
ik_check(count($mapped) === 14, 'BC1 template maps all 14 columns');
ob_start(); include TEMPLATE.'xangdau/uploadHocvien/preview_tpl.php'; $bcHtml = ob_get_clean();
ik_check(strpos($bcHtml, 'data-ik-tabs="xd-roster-preview"') !== false && strpos($bcHtml, 'mau-bc1.xlsx') !== false && strpos($bcHtml, 'ik-mapping') !== false, 'BC1 preview UI');
ik_reset_request(array('confirm_roster' => $xd_roster_preview['token']));
ik_run('xd_upload_hocvien_excel');
$bcHistory = $d->rawQueryOne("select * from table_import_history where scope = 'xangdau' order by id desc limit 1");
ik_check($bcHistory['module'] === 'hocvien_bc1' && $bcHistory['filename'] === 'mau-bc1.xlsx' && (int)$bcHistory['so_them'] === 1 && $bcHistory['status'] === 'done', 'BC1 confirm recorded');
echo "PASS BC1 template preview mapping and confirm history\n";

/* 9. Hóa đơn: đồng bộ lại chuyển kỳ hóa đơn chưa quyết toán; nhiều file cùng lượt không bị chặn kỳ. */
$tpl = $GLOBALS['ik_templates']['xangdau.hoadon'];
ik_reset_request(array('ky' => 'T9', 'upload_session' => 'abc123')); ik_attach($tpl, 'hd1.xlsx');
ik_run('xd_upload_hoadon_excel');
$invoice = $d->rawQueryOne("select * from #_xd_hoadon where ma_hoa_don = '0001234'");
ik_check($invoice && $invoice['ky'] === 'T9' && (float)$invoice['tong_tien'] == 608750 && $invoice['ngay_hoa_don'] === '2026-10-05', 'Invoice template imports');
ik_reset_request(array('ky' => 'T9', 'upload_session' => 'abc123')); ik_attach($tpl, 'hd2.xlsx');
ik_run('xd_upload_hoadon_excel');
ik_check($func->last['success'] === true, 'Second file in the same upload session is not blocked by period');
ik_reset_request(array('ky' => 'T9')); ik_attach($tpl, 'hd3.xlsx');
ik_run('xd_upload_hoadon_excel');
ik_check($func->last['success'] === false && strpos($func->last['message'], 'đã được import') !== false, 'Different session still blocks duplicate period');
ik_reset_request(array('ky' => 'T10', 'resync' => '1')); ik_attach($tpl, 'hd4.xlsx');
ik_run('xd_upload_hoadon_excel');
ik_check($d->rawQueryOne("select ky from #_xd_hoadon where ma_hoa_don = '0001234'")['ky'] === 'T10' && strpos($func->last['message'], 'chuyển 1 hóa đơn sang kỳ T10') !== false, 'Resync moves unsettled invoice to the new period');
echo "PASS invoice resync updates period; multi-file batch allowed\n";

/* 10. Đồng bộ DAT chạy thử không thay đổi dữ liệu. */
$before = $d->rawQueryOne('select count(*) c, coalesce(sum(crc32(concat_ws("|", id, khoa, nhom, gv_key))), 0) s from #_xd_hocvien');
$dry = xd_sync_training_students(0, '', true);
$after = $d->rawQueryOne('select count(*) c, coalesce(sum(crc32(concat_ws("|", id, khoa, nhom, gv_key))), 0) s from #_xd_hocvien');
ik_check(!empty($dry['dry_run']) && $before == $after && !$d->pdo()->inTransaction(), 'Dry run must not change data');
$GLOBALS['xd_sync_preview'] = $dry; $GLOBALS['xd_sync_last'] = array('time' => time(), 'user' => 'u', 'ok' => true, 'added' => 2);
$xd_sync_preview = $dry; $xd_sync_last = $GLOBALS['xd_sync_last'];
ob_start(); include TEMPLATE.'xangdau/sync/preview_tpl.php'; $syncHtml = ob_get_clean();
ik_check(strpos($syncHtml, 'chưa có dữ liệu nào thay đổi') !== false && strpos($syncHtml, 'Đồng bộ gần nhất') !== false && substr_count($syncHtml, 'ik-sync-stat') >= 6, 'Sync preview page');
echo "PASS DAT sync dry run leaves data unchanged; preview page renders\n";

/* 11. Giao diện dùng chung. */
$ikNotice = array('message' => "Import xong\nDòng 3: lỗi A\nDòng 4: lỗi B", 'success' => false, 'steps' => true, 'title' => 'Import chưa thành công', 'links' => array(array('label' => 'Tải <b>lỗi</b>', 'url' => 'index.php?x=1&y=2', 'icon' => 'fa-file')));
ob_start(); include TEMPLATE.'layout/import_result.php'; $resultHtml = ob_get_clean();
ik_check(strpos($resultHtml, 'is-import') !== false && strpos($resultHtml, 'ik-steps is-failed') !== false && strpos($resultHtml, 'Tải &lt;b&gt;lỗi&lt;/b&gt;') !== false && strpos($resultHtml, 'x=1&amp;y=2') !== false && substr_count($resultHtml, '<li>') === 2, 'Result card escapes and lists lines');
$ikKey = 'daotao.hocvien'; $ikExtraHtml = '';
ob_start(); include TEMPLATE.'layout/import_guide.php'; $guideHtml = ob_get_clean();
ik_check(strpos($guideHtml, 'act=importTemplate&type=hocvien') !== false && strpos($guideHtml, 'Mã đăng ký · Số CCCD') !== false, 'Guide with template link');
$ikKey = 'daotao.dat';
ob_start(); include TEMPLATE.'layout/import_guide.php'; $guideHtml = ob_get_clean();
ik_check(strpos($guideHtml, 'importTemplate') === false && strpos($guideHtml, 'thiết bị DAT') !== false, 'System export guide without template');
xd_import_history_page();
global $ikHistoryRows, $ikScope, $ikUndoableId, $ikCanUndo, $ikModuleTitles, $ikUndoNote;
ob_start(); include TEMPLATE.'import/history_tpl.php'; $historyHtml = ob_get_clean();
ik_check(strpos($historyHtml, 'Đã hoàn tác') !== false && strpos($historyHtml, 'Thất bại') !== false && strpos($historyHtml, 'act=importErrors') !== false, 'History page statuses and error download');
foreach(glob(ROOT.'/admin/templates/daotao/*/upload_tpl.php') as $file)
	ik_check(strpos(file_get_contents($file), "import_steps.php") !== false && strpos(file_get_contents($file), "import_guide.php") !== false, 'Upload page has steps+guide: '.$file);
$js = file_get_contents(ROOT.'/admin/assets/js/daotao-import.js');
ik_check(strpos($js, 'fileProblems') !== false && strpos($js, 'IK_MAX_UPLOAD') !== false && strpos($js, 'file rỗng') !== false, 'Client-side file validation');
foreach(array('importTemplate', 'importHistory', 'importErrors') as $act)
	ik_check(!admin_csrf_required('daotao', $act, 'GET') && !admin_csrf_required('xangdau', $act, 'GET'), 'Read-only act '.$act);
ik_check(admin_csrf_required('daotao', 'importUndo', 'GET') && admin_csrf_required('xangdau', 'importUndo', 'POST') && admin_csrf_required('xangdau', 'syncTrainingDatPreview', 'GET'), 'Undo and sync preview need a token');
echo "PASS shared UI: result card, guide, history page, upload pages, client validation, CSRF\n";

ik_cleanup();
echo "ALL PASS import kit regression (temporary tables only; generated backups removed)\n";
