<?php
/*
 * Kiểm thử các bản sửa sau đợt rà soát Đào tạo/Xăng dầu:
 * escape trang thông báo, CSRF, chặn xóa toàn bộ, mật khẩu cổng GV, ngày sinh, giao diện Học viên XD,
 * duyệt thanh toán có hoàn tác, endpoint tra cứu công khai và chặn truy cập web file nhạy cảm.
 * Chỉ dùng bảng tạm (TEMPORARY) và khóa đếm ngẫu nhiên; dữ liệu thật không đổi.
 */
if(PHP_SAPI !== 'cli' || ($argv[1] ?? '') !== '--local-db') exit("Usage: php scripts/regression_security_audit.php --local-db\n");
define('ROOT', dirname(__DIR__));
define('LIBRARIES', ROOT.'/libraries/');
define('SOURCES', ROOT.'/admin/sources/');
$_SERVER['SERVER_NAME'] = 'localhost';
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
require LIBRARIES.'admin_csrf.php';
require LIBRARIES.'daotao_gv_auth.php';
require_once LIBRARIES.'course_options.php';
require SOURCES.'xangdau/khoa.php';
require SOURCES.'xangdau/permissions.php';
require SOURCES.'xangdau/helpers.php';
require SOURCES.'xangdau/hocvien_crud.php';
require SOURCES.'xangdau/algorithm.php';
require SOURCES.'xangdau/loc.php';

function sa_check($condition, $message) { if(!$condition) throw new RuntimeException($message); }
class SecurityAuditFunctions
{
	public function check_permission() { return false; }
	public function transfer($message, $url, $success = true) { throw new RuntimeException('TRANSFER: '.$message); }
	public function pagination($total, $perPage, $page, $url) { return ''; }
}
$func = new SecurityAuditFunctions();
$login_admin = 'test';
$_SESSION = array('test' => array('active' => true, 'username' => 'audit'));
set_exception_handler(function($error) { fwrite(STDERR, 'FAIL: '.$error->getMessage()."\n"); exit(1); });

/* 1. Trang thông báo escape nội dung và URL. */
function sa_transfer($message, $page)
{
	$basehref = 'http://localhost/'; $showtext = $message; $page_transfer = $page; $stt = false;
	ob_start(); include ROOT.'/admin/templates/layout/transfer.php'; return ob_get_clean();
}
$html = sa_transfer('Đã lưu cho <img src=x onerror=alert(1)><br><strong>A</strong> &amp; B <em>x</em>', 'index.php?com=daotao"><script>alert(1)</script>');
sa_check(strpos($html, '<img src=x') === false && strpos($html, '&lt;img src=x onerror=alert(1)&gt;') !== false, 'Message HTML must be escaped');
sa_check(strpos($html, '<br>') !== false && strpos($html, '<strong>A</strong>') !== false && strpos($html, '<em>x</em>') !== false, 'Safe formatting tags must stay');
sa_check(strpos($html, '&amp; B') !== false && strpos($html, '&amp;amp;') === false, 'Pre-escaped text must not be double escaped');
sa_check(strpos($html, '<script>alert') === false && strpos($html, '&quot;&gt;&lt;script&gt;') !== false, 'Redirect URL must be escaped');
foreach(array('javascript:alert(1)', 'http://evil.example/', "index.php?com=daotao\r\nX: y") as $bad)
{
	$html = sa_transfer('ok', $bad);
	sa_check(strpos($html, 'url=http://localhost/admin/index.php"') !== false && strpos($html, 'href="admin/index.php"') !== false, 'Unsafe redirect must fall back to index.php: '.$bad);
}
$html = sa_transfer('ok', 'index.php?com=xangdau&act=hocvien&khoa=K13#xd');
sa_check(strpos($html, 'href="admin/index.php?com=xangdau&amp;act=hocvien&amp;khoa=K13#xd"') !== false, 'Normal redirect must be kept');
echo "PASS transfer message/URL escaping and safe redirect fallback\n";

/* 2. CSRF: mọi act ghi của hai module đều cần token; danh sách miễn token chỉ gồm act có thật và chỉ đọc. */
foreach(array('daotao', 'xangdau') as $com)
{
	preg_match_all('/case\s+[\'"]([A-Za-z0-9_]+)[\'"]\s*:/', file_get_contents(SOURCES.$com.'.php'), $matches);
	$routes = array_unique($matches[1]);
	$readOnly = admin_csrf_modules()[$com];
	foreach($readOnly as $act)
	{
		if($act === '') continue;
		sa_check(in_array($act, $routes, true), "Read-only act $com/$act is not a real route");
		sa_check(!preg_match('/delete|save|sync|Excel$|mark|toggle|duyet|kiemTra|reset|restore|thanhLy|DatAll|import|cap|remove|add|create|undo|pass|xoa|clear/i', $act) || preg_match('/^(export|xuat|upload[A-Z][a-z]+$|khoaAdd|kythiUpload|htkh(Print|PrintKy|ExcelKy|WordKy)|ceInvoiceTemplate|xemGiaoVien|locKiemTra|locDuyet|locDaThanhToan|backupDownload|sathachPending|uploadHtkh|uploadHocvien$)/', $act), "Suspicious read-only act $com/$act");
	}
	foreach($routes as $act) if(!in_array($act, $readOnly, true)) sa_check(admin_csrf_required($com, $act, 'GET'), "Write act $com/$act must need a token");
}
foreach(array('deleteAllHocvien', 'deleteAllHoadon', 'deleteHocvienByGv', 'updateHocvienStatus', 'syncTrainingDat', 'duyetTatCaGiaoVien') as $act)
	sa_check(admin_csrf_required('xangdau', $act, 'GET'), "xangdau/$act must need a token");
foreach(array('crudDeleteAll', 'crudDelete', 'backupRestore', 'gvResetPass', 'datDatAll') as $act)
	sa_check(admin_csrf_required('daotao', $act, 'GET'), "daotao/$act must need a token");
sa_check(!admin_csrf_required('xangdau', 'hocvien', 'GET') && admin_csrf_required('xangdau', 'hocvien', 'POST') && !admin_csrf_required('news', 'delete', 'GET') && admin_csrf_required('daotao', 'unknownNewAct', 'GET'), 'CSRF requirement matrix');
$token = admin_csrf_token();
sa_check(strlen($token) === 64 && admin_csrf_token() === $token, 'Token must be stable per session');
$_POST = array(); $_GET = array();
sa_check(!admin_csrf_valid(), 'Missing token must be rejected');
$_GET = array('_csrf' => 'x'.$token); sa_check(!admin_csrf_valid(), 'Wrong token must be rejected');
$_GET = array('_csrf' => $token); sa_check(admin_csrf_valid(), 'GET token');
$_GET = array(); $_POST = array('_csrf' => $token); sa_check(admin_csrf_valid(), 'POST token');
$_POST = array(); $_SERVER['HTTP_X_CSRF_TOKEN'] = $token; sa_check(admin_csrf_valid(), 'Header token'); unset($_SERVER['HTTP_X_CSRF_TOKEN']);
$_SERVER['REQUEST_METHOD'] = 'GET';
try { admin_csrf_guard('xangdau', 'deleteAllHoadon'); throw new LogicException('guard passed'); }
catch(RuntimeException $error) { sa_check(strpos($error->getMessage(), 'Phiên thao tác không hợp lệ') !== false, $error->getMessage()); }
admin_csrf_guard('xangdau', 'hocvien');
$_GET = array('_csrf' => $token); admin_csrf_guard('xangdau', 'deleteAllHoadon'); $_GET = array();
$_SERVER['REQUEST_METHOD'] = 'POST'; $_SERVER['CONTENT_LENGTH'] = '999999';
try { admin_csrf_guard('daotao', 'hocvien'); throw new LogicException('guard passed'); }
catch(RuntimeException $error) { sa_check(strpos($error->getMessage(), 'quá lớn') !== false, 'Oversized upload must explain the failure'); }
unset($_SERVER['CONTENT_LENGTH']); $_SERVER['REQUEST_METHOD'] = 'GET';
$router = file_get_contents(SOURCES.'daotao.php');
sa_check(strpos($router, "admin_csrf_guard('daotao', \$act);") < strpos($router, 'switch($act)') && strpos(file_get_contents(SOURCES.'xangdau.php'), "admin_csrf_guard('xangdau', \$act);") !== false, 'Both routers must call the guard before dispatch');
preg_match('/case "crudDeleteAll":[^\n]*/', $router, $deleteAllLine);
sa_check(isset($deleteAllLine[0]) && strpos($deleteAllLine[0], "array('khoa','all')") !== false && strpos($deleteAllLine[0], "\$_GET['entity']") !== false && strpos($deleteAllLine[0], '$_REQUEST') === false, 'crudDeleteAll must block khoa and all from the same input');
$index = file_get_contents(ROOT.'/admin/index.php');
sa_check(strpos($index, "require_once LIBRARIES.'admin_csrf.php'") !== false && strpos($index, 'admin-csrf.js') !== false && strpos($index, 'JSON_HEX_TAG') !== false, 'Admin layout must load the CSRF token script');
$js = file_get_contents(ROOT.'/admin/assets/js/admin-csrf.js');
sa_check(strpos($js, "parsed.origin !== window.location.origin") !== false && strpos($js, 'MutationObserver') !== false && strpos($js, 'formaction') !== false, 'CSRF script must stay same-origin and cover dynamic forms/formaction');
echo "PASS CSRF route matrix, token checks, guard wiring, delete-all block\n";

/* 3. Mật khẩu cổng giáo viên. */
$legacy = dt_gv_legacy_hash('012345678901');
sa_check(dt_gv_verify('012345678901', $legacy) && !dt_gv_verify('x', $legacy) && dt_gv_needs_rehash($legacy), 'Legacy MD5 passwords must still verify and be upgraded');
$modern = dt_gv_hash('Mat-khau-moi-2026');
sa_check(strpos($modern, '$2y$') === 0 && dt_gv_verify('Mat-khau-moi-2026', $modern) && !dt_gv_verify('Mat-khau-moi-2025', $modern) && !dt_gv_needs_rehash($modern), 'Bcrypt hashing');
sa_check(!dt_gv_verify('', '') && !dt_gv_verify('abc', ''), 'Empty stored password must never match');
sa_check(dt_gv_is_default_password('012345678901', $legacy) && dt_gv_is_default_password('012345678901', dt_gv_hash('12345678901')) && !dt_gv_is_default_password('012345678901', $modern), 'Default CCCD password detection');
sa_check(dt_gv_password_policy('1234567', '') !== '' && dt_gv_password_policy('012345678901', '012345678901') !== '' && dt_gv_password_policy('Mat-khau-moi', '012345678901') === '', 'Password policy');
$probe = '9'.str_pad((string)random_int(0, 99999999999), 11, '0', STR_PAD_LEFT);
$scope = 'audit'.bin2hex(random_bytes(3));
try
{
	for($attempt = 1; $attempt <= 4; $attempt++) { dt_gv_attempt_fail($scope, $probe); sa_check(dt_gv_attempt_locked($scope, $probe) === 0, 'Locked too early'); }
	dt_gv_attempt_fail($scope, $probe);
	sa_check(dt_gv_attempt_locked($scope, $probe) > 0, 'Five failed attempts must lock the CCCD');
	dt_gv_attempt_clear($scope, $probe);
	sa_check(dt_gv_attempt_locked($scope, $probe) === 0, 'Successful login must clear the CCCD lock');
}
finally
{
	foreach(array_keys(dt_gv_attempt_keys($scope, $probe)) as $key) $d->rawQuery('delete from #_dt_gv_attempt where akey = ?', array($key));
}
$portal = file_get_contents(ROOT.'/sources/cong_giaovien.php');
sa_check(strpos($portal, 'dt_gv_verify($pass') !== false && strpos($portal, 'session_regenerate_id(true)') !== false && strpos($portal, "\$dobDb === '' || \$phoneDb === ''") !== false && strpos($portal, '!dt_gv_csrf_valid()') !== false, 'Portal must verify, rotate session, require DOB+phone and check CSRF');
sa_check(strpos($portal, "=== dt_gv_hash(") === false && strpos($portal, "!== dt_gv_hash(") === false, 'Portal must not compare salted hashes directly');
$dtGv = array('id' => 1, 'cccd' => '012345678901', 'hoten' => 'GV <b>Test</b>', 'must_change' => 1);
$dtGvMsg = ''; $dtGvErr = ''; $dtCfg = array(); $dtHistory = array(); $dtStudents = array();
ob_start(); include ROOT.'/templates/daotao/giaovien_tpl.php'; $portalHtml = ob_get_clean();
sa_check(strpos($portalHtml, 'mật khẩu mặc định') !== false && strpos($portalHtml, 'dt-gv-filter') === false && strpos($portalHtml, 'Lịch sử cập nhật') === false, 'Default password must hide portal data until changed');
sa_check(substr_count($portalHtml, 'name="dt_csrf"') >= 2 && strpos($portalHtml, 'GV &lt;b&gt;Test&lt;/b&gt;') !== false, 'Portal forms need CSRF and escaped names');
echo "PASS teacher portal bcrypt upgrade, default-password lock, policy, rate limit and CSRF\n";

/* 4. Ngày hiển thị. */
foreach(array('2005-08-23' => '23/08/2005', '15/10/1989' => '15/10/1989', '5-3-2001' => '05/03/2001', '2001-03-05 00:00:00' => '05/03/2001', '' => '', '0000-00-00' => '', '31/02/2001' => '31/02/2001', 'abc' => 'abc') as $input => $expected)
	sa_check(xd_display_date($input) === $expected, 'Date display '.$input.' => '.xd_display_date($input));
echo "PASS consistent dd/mm/yyyy date display\n";

/* 5. Giao diện Học viên XD trên bảng tạm. */
function sa_temp_copy($table)
{
	global $d;
	$definition = $d->rawQuery('show create table #_'.$table);
	$sql = preg_replace('/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $definition[0]['Create Table']);
	$d->rawQuery(preg_replace('/ AUTO_INCREMENT=\d+/', '', $sql));
	sa_check((string)$d->getLastErrorCode() === '00000', 'Cannot create temporary '.$table);
}
foreach(array('xd_hocvien', 'dt_khoa', 'dt_hocvien', 'xd_bangke', 'xd_hoadon') as $table) sa_temp_copy($table);
$d->rawQuery("insert into #_dt_khoa (ma_khoa, ten_khoa) values ('79044K26C1005', 'K13-C1')");
for($n = 1; $n <= 25; $n++)
	$d->rawQuery('insert into #_xd_hocvien (ho_ten, cccd, ngaysinh, khoa, nhom, gv_hoten, gv_key, lan_import, thu_tu_file) values (?, ?, ?, ?, ?, ?, ?, 1, ?)',
		array('Học Viên '.chr(64 + $n), '99100000'.str_pad((string)$n, 4, '0', STR_PAD_LEFT), $n % 2 ? '2001-03-05' : '05/03/2001', '79044K26C1005', 'DAT', 'Thầy A', 'thay a', $n));
function sa_render_list($request, $page)
{
	global $d, $func, $curPage, $items, $paging, $xd_filter_keyword, $xd_filter_nhom, $xd_filter_trangthai, $xd_filter_tt_from, $xd_filter_tt_to, $xd_hocvien_teachers, $xd_filter_khoa, $xd_filter_gv, $xd_hocvien_khoas, $xd_hocvien_khoa_groups, $xd_hocvien_total, $xd_hocvien_offset, $xd_hocvien_all_total;
	$_REQUEST = $_GET = $request; $curPage = $page;
	xd_get_hocvien();
	ob_start(); require ROOT.'/admin/templates/xangdau/hocvien/items_tpl.php'; return ob_get_clean();
}
$list = sa_render_list(array('khoa' => '79044K26C1005'), 2);
sa_check(preg_match('/<td class="text-muted">21<\/td>/', $list) && strpos($list, '<td class="text-muted">1</td>') === false, 'STT must continue across pages');
sa_check(strpos($list, 'Hiển thị <strong>21–25</strong> / <strong>25</strong> học viên') !== false, 'Result range count');
sa_check(strpos($list, '<strong>K13-C1</strong><small>79044K26C1005</small>') !== false, 'Course column must show name with code below');
sa_check(substr_count($list, '05/03/2001') >= 5 && strpos($list, '2001-03-05') === false, 'Birth dates must render consistently');
sa_check(strpos($list, 'Khóa: K13-C1 (79044K26C1005)') !== false && strpos($list, 'class="xd-hv-chip"') !== false, 'Active filter chips');
sa_check(strpos($list, 'id="xd-hv-selected"') !== false && strpos($list, 'Đạt DAT toàn khóa') !== false && strpos($list, 'Xóa toàn khóa') !== false && strpos($list, 'mọi trang, bỏ qua bộ lọc khác') !== false, 'Bulk bar must group selected and whole-course actions with scope');
sa_check(strpos($list, 'id="xd-delete-all-btn"') !== false && strpos($list, 'XOA TOAN BO') !== false && strpos($list, 'Xóa TOÀN BỘ 25 học viên XD') !== false, 'Delete all must require typed confirmation with count');
sa_check(strpos($list, 'Ngày thanh toán (từ – đến)') !== false && strpos($list, 'Ngày TT đến') === false && strpos($list, 'Ngày nộp hồ sơ') !== false, 'Date range filter and submission column');
sa_check(strpos($list, 'Quản trị dữ liệu') !== false && strpos($list, 'Không phụ thuộc bộ lọc') !== false, 'Danger actions grouped separately');
$dom = new DOMDocument(); libxml_use_internal_errors(true); $dom->loadHTML('<?xml encoding="utf-8"?>'.$list); libxml_clear_errors();
sa_check((new DOMXPath($dom))->query('//form[@id="xd-bulk-hocvien"]')->length === 1 && (new DOMXPath($dom))->query('//input[@class="xd-bulk-item"]')->length === 5, 'Bulk form/items');
$empty = sa_render_list(array('keyword' => 'khong-ton-tai'), 1);
sa_check(strpos($empty, 'Không có học viên khớp bộ lọc') !== false && strpos($empty, 'Import học viên ngay') === false, 'Filtered empty state must offer reset');
$d->rawQuery('delete from #_xd_hocvien');
$none = sa_render_list(array(), 1);
sa_check(strpos($none, 'Chưa có học viên xăng dầu nào') !== false && strpos($none, 'class="xd-hv-chip"') === false, 'Unfiltered empty state');
echo "PASS XD student list layout, paging STT, course names, chips, bulk scope and empty states\n";

/* 6. Duyệt thanh toán: lỗi giữa chừng thì hoàn tác toàn bộ. */
$d->rawQuery("insert into #_xd_hocvien (ho_ten, cccd, khoa, nhom, gv_hoten, gv_key, lan_import, thu_tu_file) values ('HV Duyet', '991999999999', 'K', 'DAT', 'Thầy B', 'thay b', 1, 1)");
$student = $d->rawQueryOne("select id from #_xd_hocvien where cccd = '991999999999'");
$selected = array(array('id' => (int)$student['id'], 'dinh_muc' => 10, 'so_tien_thanh_toan' => 250000, 'gv_key' => 'thay b'));
$d->rawQuery('drop temporary table #_xd_hoadon');
$d->rawQuery('create temporary table #_xd_hoadon (id int primary key)');
sa_check(!xd_loc_approve_teacher('thay b', $selected, '', '', ''), 'Broken invoice update must fail');
$after = $d->rawQueryOne('select ngay_thanh_toan, id_bangke from #_xd_hocvien where id = ?', array((int)$student['id']));
$batches = $d->rawQueryOne('select count(*) as c from #_xd_bangke');
sa_check($after['ngay_thanh_toan'] === null && (int)$after['id_bangke'] === 0 && (int)$batches['c'] === 0, 'Failed approval must roll back batch and students');
$d->rawQuery('drop temporary table #_xd_hoadon');
sa_temp_copy('xd_hoadon');
sa_check(xd_loc_approve_teacher('thay b', $selected, '', '', ''), 'Valid approval must succeed');
$after = $d->rawQueryOne('select ngay_thanh_toan, id_bangke, so_tien_thanh_toan from #_xd_hocvien where id = ?', array((int)$student['id']));
sa_check($after['ngay_thanh_toan'] !== null && (int)$after['id_bangke'] > 0 && (int)$after['so_tien_thanh_toan'] === 250000, 'Approval must record payment');
foreach(array('xd_hocvien', 'dt_khoa', 'dt_hocvien', 'xd_bangke', 'xd_hoadon') as $table) $d->rawQuery('drop temporary table if exists #_'.$table);
echo "PASS payment approval commits atomically and rolls back on failure\n";

/* 7. HTTP: tra cứu công khai và chặn file nhạy cảm (đọc, không ghi). */
function sa_http($path)
{
	$context = stream_context_create(array('http' => array('ignore_errors' => true, 'timeout' => 15)));
	$body = @file_get_contents('http://localhost'.$path, false, $context);
	$status = 0;
	foreach(($http_response_header ?? array()) as $header) if(preg_match('#^HTTP/\S+\s+(\d+)#', $header, $m)) $status = (int)$m[1];
	$type = '';
	foreach(($http_response_header ?? array()) as $header) if(stripos($header, 'Content-Type:') === 0) $type = $header;
	return array($status, (string)$body, $type);
}
foreach(array('/ajax/tracuu_xangdau_excel.php', '/ajax/tracuu_xangdau_excel.php?cccd=', '/ajax/tracuu_xangdau_excel.php?cccd=%20') as $path)
{
	list($status, $body, $type) = sa_http($path);
	sa_check(stripos($type, 'spreadsheet') === false && strpos($body, 'PK') !== 0, 'Empty lookup must not export Excel: '.$path);
}
foreach(array('/backups/', '/backups/test.sql', '/scripts/regression_training_menu.php', '/daotaola6686_db.sql', '/migration_xangdau.sql', '/xd-import-tai-chinh-va-ten-khoa.zip', '/README-DAOTAO-XANGDAU.md', '/openspec/config.yaml', '/start.sh') as $path)
{
	list($status) = sa_http($path);
	sa_check($status === 403, 'Sensitive path must be forbidden: '.$path.' => '.$status);
}
foreach(array('/', '/admin/index.php', '/robots.txt') as $path)
{
	list($status) = sa_http($path);
	sa_check($status === 200, 'Normal path must still work: '.$path.' => '.$status);
}
echo "PASS public lookup refuses empty input; backups/scripts/dumps blocked; site still reachable\n";
echo "ALL PASS security audit regression\n";
