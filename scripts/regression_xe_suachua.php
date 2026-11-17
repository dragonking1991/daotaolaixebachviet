<?php
if(PHP_SAPI !== 'cli')
{
	http_response_code(403);
	exit('CLI only');
}

define('SOURCES', dirname(__DIR__).'/admin/sources/');
define('LIBRARIES', dirname(__DIR__).'/libraries/');
define('ROOT', LIBRARIES);
// Same load order as admin/sources/daotao.php, so shared-library redeclarations fail here.
require SOURCES.'daotao/schema.php';
require SOURCES.'daotao/config_daotao.php';
require SOURCES.'daotao/helpers.php';
require SOURCES.'daotao/xe_suachua.php';
require SOURCES.'daotao/crud.php';
require dirname(__DIR__).'/libraries/admin_csrf.php';

class RepairTransfer extends RuntimeException
{
	public $success;
	public function __construct($message, $success = true)
	{
		parent::__construct($message);
		$this->success = $success;
	}
}

class RepairFunctions
{
	public function transfer($message, $back, $success = true)
	{
		throw new RepairTransfer($message, $success);
	}
}

class RepairDatabase
{
	public $vehicles = array(array('id' => 11, 'bien_so' => '51A-TEST'));
	public $hasRepairs = true;
	public $queries = array();
	public $rollbacks = 0;
	public $begins = 0;
	public $failed = false;

	public function rawQuery($sql, $params = array())
	{
		$this->queries[] = array($sql, $params);
		if(strpos($sql, 'select id from #_dt_xe order by id for update') === 0) return $this->vehicles;
		if(strpos($sql, 'select id, bien_so from #_dt_xe where id in') === 0)
		{
			$ids = array_map('intval', $params);
			return array_values(array_filter($this->vehicles, function($row) use ($ids) { return in_array((int)$row['id'], $ids, true); }));
		}
		if(strpos($sql, 'select x.id, x.bien_so, count(r.id)') === 0) return $this->hasRepairs ? $this->vehicles : array();
		if(stripos($sql, 'delete from #_dt_xe') === 0) return array();
		return array();
	}

	public function rawQueryOne($sql, $params = array()) { return array(); }
	public function startTransaction() { $this->begins++; }
	public function rollback() { $this->rollbacks++; }
	public function commit() { return true; }
	public function transactionFailed() { return $this->failed; }
}

class RepairPageDatabase
{
	public $idXe = 22;
	public $hasHistory = true;
	public $historyQueries = array();

	public function rawQueryOne($sql, $params = array())
	{
		if(strpos($sql, 'select id, bien_so, nhan_hieu, gv_hoten') === 0)
			return array('id' => $this->idXe, 'bien_so' => '<script>plate</script>', 'nhan_hieu' => 'Toyota', 'gv_hoten' => 'Nguyễn An', 'thanh_ly' => 0);
		if(strpos($sql, 'select count(*) as tong') === 0) return $this->hasHistory ? array('tong' => 16, 'dang_sua' => 2, 'chi_phi' => 250000, 'chua_chi_phi' => 1) : array('tong' => 0, 'dang_sua' => 0, 'chi_phi' => 0, 'chua_chi_phi' => 0);
		if(strpos($sql, 'select ngay_sua, ngay_baoduong') === 0) return $this->hasHistory ? array('ngay_sua' => '2026-01-01', 'ngay_baoduong' => date('Y-m-d', strtotime('-1 day')), 'km_baoduong' => '12500.50') : false;
		if(strpos($sql, 'select count(*) as num') === 0) return array('num' => $this->hasHistory ? 16 : 0);
		return array('id' => 9, 'id_xe' => $this->idXe);
	}

	public function rawQuery($sql, $params = array())
	{
		$this->historyQueries[] = array($sql, $params);
		if(strpos($sql, 'select * from #_dt_xe_suachua where id_xe = ? order by ngay_sua desc, id desc limit') === 0 && $this->hasHistory)
			return array(array(
				'id' => 9, 'id_xe' => $this->idXe, 'ngay_sua' => '2026-01-01', 'noi_dung' => '<script>unsafe()</script>',
				'trang_thai' => 'dang_sua', 'gara' => 'Gara A', 'so_km' => '12000.50', 'chi_phi' => null,
				'ghi_chu' => '<img src=x>', 'ngay_baoduong' => '2026-02-01', 'km_baoduong' => '12500.50',
				'anh_minh_chung' => json_encode(array(str_repeat('a', 32).'.jpg')),
			));
		return array();
	}

	public function getLastErrorCode() { return '00000'; }
}

class RepairOwnershipDatabase
{
	public $queries = array();
	public $rollbacks = 0;
	public $commits = 0;

	public function rawQueryOne($sql, $params = array())
	{
		if(strpos($sql, 'select id, bien_so from #_dt_xe') === 0) return array('id' => 11, 'bien_so' => '51A-TEST');
		if(strpos($sql, 'select id from #_dt_xe_suachua where id = ? and id_xe = ?') === 0) return array();
		return array();
	}

	public function rawQuery($sql, $params = array())
	{
		$this->queries[] = array($sql, $params);
		return array();
	}

	public function where($key, $value) { return $this; }
	public function update($table, $data) { $this->queries[] = array('update '.$table, $data); return true; }
	public function insert($table, $data) { $this->queries[] = array('insert '.$table, $data); return 1; }
	public function startTransaction() {}
	public function rollback() { $this->rollbacks++; }
	public function commit() { $this->commits++; return true; }
	public function transactionFailed() { return false; }
}

class RepairWriteFailureDatabase
{
	public $failed = false;
	public $rollbacks = 0;
	public $commits = 0;
	public $writes = array();

	public function rawQueryOne($sql, $params = array())
	{
		if(strpos($sql, 'select id, bien_so from #_dt_xe') === 0) return array('id' => 11, 'bien_so' => '51A-TEST');
		if(strpos($sql, 'select id from #_dt_xe_suachua where id = ? and id_xe = ?') === 0) return array('id' => 99);
		return array();
	}

	public function rawQuery($sql, $params = array())
	{
		$this->writes[] = array($sql, $params);
		if(stripos($sql, 'delete from #_dt_xe_suachua') === 0) $this->failed = true;
		return array();
	}

	public function where($key, $value) { return $this; }
	public function update($table, $data) { $this->failed = true; return false; }
	public function insert($table, $data) { $this->failed = true; return false; }
	public function startTransaction() {}
	public function rollback() { $this->rollbacks++; }
	public function commit() { $this->commits++; return true; }
	public function transactionFailed() { return $this->failed; }
}

class RepairStoreDatabase
{
	public $record = null;
	public $nextId = 1;
	public $writeId = 0;
	public $rollbacks = 0;
	public $commits = 0;

	public function rawQueryOne($sql, $params = array())
	{
		if(strpos($sql, 'select id, bien_so from #_dt_xe') === 0) return array('id' => 11, 'bien_so' => '51A-TEST');
		if(strpos($sql, 'select id from #_dt_xe_suachua where id = ? and id_xe = ?') === 0)
			return $this->record && (int)$this->record['id'] === (int)$params[0] && (int)$this->record['id_xe'] === (int)$params[1] ? array('id' => $this->record['id']) : array();
		return array();
	}

	public function rawQuery($sql, $params = array())
	{
		if(stripos($sql, 'delete from #_dt_xe_suachua') === 0 && $this->record && (int)$this->record['id'] === (int)$params[0] && (int)$this->record['id_xe'] === (int)$params[1])
			$this->record = null;
		return array();
	}

	public function where($key, $value) { $this->writeId = (int)$value; return $this; }
	public function update($table, $data) { $this->record = array_merge($this->record, $data); return true; }
	public function insert($table, $data) { $this->record = array_merge(array('id' => $this->nextId++), $data); return $this->record['id']; }
	public function startTransaction() {}
	public function rollback() { $this->rollbacks++; }
	public function commit() { $this->commits++; return true; }
	public function transactionFailed() { return false; }
}

class RepairImageAccessDatabase
{
	public $queries = array();
	public $record = array('anh_minh_chung' => '');
	public function rawQueryOne($sql, $params = array())
	{
		$this->queries[] = array($sql, $params);
		if(strpos($sql, 'r.id = ? and x.id = ?') === false || (int)$params[0] !== 41 || (int)$params[1] !== 11) return false;
		if(strpos($sql, 'x.gv_key = ?') !== false && ($params[2] ?? '') !== 'teacher-a') return false;
		return $this->record;
	}
}

function dt_can($section, $action = 'view')
{
	global $repairPermissions;
	return !empty($repairPermissions[$action]);
}

function dt_backup_tables_list() { return array('dt_xe', 'dt_xe_suachua'); }
function dt_backup_tables($tables, $tag = '') { return 'backup.sql'; }
function dt_audit($action, $entity, $affected = 0, $detail = '')
{
	global $repairAudit;
	$repairAudit[] = array($action, $entity, $affected, $detail);
}

function repair_check($condition, $message)
{
	if(!$condition) throw new RuntimeException($message);
}

function repair_expect_errors($input, $keys)
{
	list($data, $errors) = dt_xe_suachua_validate($input);
	foreach($keys as $key) repair_check(isset($errors[$key]), 'Expected validation error: '.$key);
	return array($data, $errors);
}

$base = array('ngay_sua' => '2026-11-17', 'noi_dung' => 'Thay dầu động cơ', 'trang_thai' => 'hoan_tat');
repair_check(dt_xe_suachua_decode_images(json_encode(array(str_repeat('a', 32).'.jpg', '../bad.jpg', str_repeat('b', 32).'.png'))) === array(str_repeat('a', 32).'.jpg'), 'Repair image references must allow only safe optimized JPEG filenames');
$uploadError = '';
repair_check(dt_xe_suachua_upload_images(array('error' => UPLOAD_ERR_NO_FILE), $uploadError) === array(), 'No selected images should be optional');
$tooManyImages = dt_xe_suachua_upload_images(array('error' => array_fill(0, 11, UPLOAD_ERR_OK)), $uploadError);
repair_check($tooManyImages === false && strpos($uploadError, '10 ảnh') !== false, 'Repair attachments must cap selections at 10');
$imageName = str_repeat('a', 32).'.jpg';
$imageDb = new RepairImageAccessDatabase();
$imageDb->record['anh_minh_chung'] = json_encode(array($imageName));
$d = $imageDb;
repair_check(dt_xe_suachua_image_authorized(11, 41, $imageName, 'teacher-a'), 'Assigned teacher should access an image attached to the repair record');
repair_check(!dt_xe_suachua_image_authorized(11, 41, $imageName, 'teacher-b'), 'A different teacher must not access the repair image');
repair_check(!dt_xe_suachua_image_authorized(12, 41, $imageName, 'teacher-a'), 'Image access must remain scoped to the assigned vehicle');
repair_check(dt_xe_suachua_image_authorized(11, 41, $imageName), 'Admin image route should verify the image belongs to the repair record');
list($optionalData, $optionalErrors) = dt_xe_suachua_validate($base);
repair_check(!$optionalErrors, 'Optional fields should allow blank values');
repair_check($optionalData['chi_phi'] === null && $optionalData['so_km'] === null, 'Blank cost and odometer must remain unknown');

$complete = $base + array('so_km' => '12000.50', 'chi_phi' => '0', 'ngay_baoduong' => '2026-12-17', 'km_baoduong' => '12500');
list($completeData, $completeErrors) = dt_xe_suachua_validate($complete);
repair_check(!$completeErrors && $completeData['chi_phi'] === '0' && $completeData['km_baoduong'] === '12500', 'Valid zero cost and maintenance targets should be retained');

repair_expect_errors(array_merge($base, array('ngay_sua' => '2026-02-30')), array('ngay_sua'));
repair_expect_errors(array_merge($base, array('chi_phi' => '-1')), array('chi_phi'));
repair_expect_errors(array_merge($base, array('chi_phi' => '12.5')), array('chi_phi'));
repair_expect_errors(array_merge($base, array('trang_thai' => 'done')), array('trang_thai'));
repair_expect_errors(array_merge($base, array('ngay_baoduong' => '2026-11-16')), array('ngay_baoduong'));
repair_expect_errors(array_merge($base, array('so_km' => '100', 'km_baoduong' => '100')), array('km_baoduong'));
repair_expect_errors(array_merge($base, array('noi_dung' => array('unexpected'))), array('noi_dung'));

$max = array_merge($base, array('so_km' => '99999999.98', 'km_baoduong' => '99999999.99', 'chi_phi' => '99999999999999'));
list($maxData, $maxErrors) = dt_xe_suachua_validate($max);
repair_check(!$maxErrors, 'Schema numeric upper bounds should be accepted');
repair_expect_errors(array_merge($base, array('chi_phi' => array('unexpected'))), array('chi_phi'));

repair_check(dt_xe_suachua_reminder('2026-01-01', '2026-01-02') === 'overdue', 'Past date must be overdue');
repair_check(dt_xe_suachua_reminder('2026-01-02', '2026-01-02') === 'today', 'Current date must be due today');
repair_check(dt_xe_suachua_reminder('2026-02-01', '2026-01-02') === 'soon', '30-day boundary must be upcoming');
repair_check(dt_xe_suachua_reminder('2026-02-02', '2026-01-02') === 'later', '31-day boundary must be later');
repair_check(dt_xe_suachua_reminder('', '2026-01-02') === 'none', 'Missing target must not revive an older schedule');
repair_check(admin_csrf_required('daotao', 'xeSuachua', 'GET') === false, 'History page GET should be CSRF exempt');
repair_check(admin_csrf_required('daotao', 'xeSuachuaSave', 'POST') === true, 'Repair mutations must require CSRF');
$func = new RepairFunctions();
$_SESSION = array();
$_POST = array();
$_SERVER['REQUEST_METHOD'] = 'POST';
try { admin_csrf_guard('daotao', 'xeSuachuaSave'); }
catch(RepairTransfer $e) { repair_check(!$e->success && strpos($e->getMessage(), 'không hợp lệ') !== false, 'Mutation without CSRF token must be denied'); }
$_SERVER['REQUEST_METHOD'] = 'GET';
admin_csrf_guard('daotao', 'xeSuachua');

$d = new RepairPageDatabase();
$_GET = array('id_xe' => 0);
try { dt_xe_suachua_page(); }
catch(RepairTransfer $e) { repair_check(!$e->success && strpos($e->getMessage(), 'Xe không tồn tại') !== false, 'Missing vehicle must be rejected before viewing history'); }

$db = new RepairDatabase();
$func = new RepairFunctions();
$d = $db;
$deletePaths = array(
	array('single', function() { $_GET = array('entity' => 'xe', 'id' => 11); dt_crud_delete(); }),
	array('selected', function() { $_REQUEST = array('entity' => 'xe'); $_POST = array('ids' => array(11)); dt_crud_delete_selected(); }),
	array('delete-all xe', function() { $_GET = array('entity' => 'xe'); dt_crud_delete_all(); }),
	array('delete-all training', function() { $_GET = array('entity' => 'all'); dt_crud_delete_all(); }),
);
foreach($deletePaths as $path)
{
	$db->queries = array();
	$db->rollbacks = 0;
	try { $path[1](); }
	catch(RepairTransfer $e)
	{
		repair_check(!$e->success && strpos($e->getMessage(), 'nhật ký sửa chữa') !== false, 'Deletion should be blocked for '.$path[0]);
	}
	$writes = array_filter($db->queries, function($query) { return stripos($query[0], 'delete from #_dt_xe') === 0; });
	repair_check(!$writes && $db->rollbacks === 1, 'Blocked deletion must rollback before writing: '.$path[0]);
}

$d = new RepairOwnershipDatabase();
$func = new RepairFunctions();
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = array('id_xe' => 11, 'id' => 99, 'ret' => '', 'ngay_sua' => '2026-11-17', 'noi_dung' => 'Sửa phanh', 'trang_thai' => 'dang_sua');
try { dt_xe_suachua_save(); }
catch(RepairTransfer $e) { repair_check(!$e->success && strpos($e->getMessage(), 'không thuộc xe này') !== false, 'Cross-vehicle repair edit must be rejected'); }
repair_check(!$d->queries && $d->rollbacks === 1 && !$d->commits, 'Rejected cross-vehicle edit must not write or commit');

$d = new RepairOwnershipDatabase();
$_POST = array('id_xe' => 11, 'id' => 99, 'ret' => '');
try { dt_xe_suachua_delete(); }
catch(RepairTransfer $e) { repair_check(!$e->success && strpos($e->getMessage(), 'không thuộc xe này') !== false, 'Cross-vehicle repair deletion must be rejected'); }
repair_check(!$d->queries && $d->rollbacks === 1 && !$d->commits, 'Rejected cross-vehicle deletion must not write or commit');

$login_admin = 'admin_user';
$_SESSION = array('admin_user' => array('username' => 'regression'));
$d = new RepairWriteFailureDatabase();
$_POST = array('id_xe' => 11, 'id' => 0, 'ret' => '', 'ngay_sua' => '2026-11-17', 'noi_dung' => 'Sửa phanh', 'trang_thai' => 'dang_sua');
try { dt_xe_suachua_save(); }
catch(RepairTransfer $e) { repair_check(!$e->success && strpos($e->getMessage(), 'Không thể lưu nhật ký') !== false, 'Failed repair save must report failure'); }
repair_check($d->rollbacks === 1 && !$d->commits, 'Failed repair save must rollback without reporting success');

$d = new RepairWriteFailureDatabase();
$_POST = array('id_xe' => 11, 'id' => 99, 'ret' => '');
try { dt_xe_suachua_delete(); }
catch(RepairTransfer $e) { repair_check(!$e->success && strpos($e->getMessage(), 'Không thể xóa lần sửa chữa') !== false, 'Failed repair deletion must report failure'); }
repair_check($d->rollbacks === 1 && !$d->commits, 'Failed repair deletion must rollback without reporting success');

$repairAudit = array();
$d = new RepairStoreDatabase();
$_POST = array('id_xe' => 11, 'id' => 0, 'ret' => '', 'ngay_sua' => '2026-11-17', 'noi_dung' => 'Sửa phanh', 'trang_thai' => 'dang_sua', 'chi_phi' => '250000');
try { dt_xe_suachua_save(); }
catch(RepairTransfer $e) { repair_check($e->success, 'Valid repair creation should report success'); }
$createdId = (int)$d->record['id'];
repair_check($createdId > 0 && (int)$d->record['id_xe'] === 11 && $d->commits === 1, 'Repair creation should persist against its vehicle');
repair_check($repairAudit[0][1] === 'xe_suachua' && strpos($repairAudit[0][3], 'id_xe=11') !== false, 'Creation audit should identify the vehicle');

$_POST = array('id_xe' => 11, 'id' => $createdId, 'ret' => '', 'ngay_sua' => '2026-11-17', 'noi_dung' => 'Sửa phanh', 'trang_thai' => 'hoan_tat', 'chi_phi' => '250000');
try { dt_xe_suachua_save(); }
catch(RepairTransfer $e) { repair_check($e->success, 'Valid repair update should report success'); }
repair_check($d->record['trang_thai'] === 'hoan_tat' && (int)$d->record['id_xe'] === 11, 'Updating work status must preserve vehicle ownership');

$_POST = array('id_xe' => 11, 'id' => $createdId, 'ret' => '');
try { dt_xe_suachua_delete(); }
catch(RepairTransfer $e) { repair_check($e->success, 'Valid repair deletion should report success'); }
repair_check($d->record === null && $d->commits === 3 && count($repairAudit) === 3, 'Repair deletion should persist and audit after commit');

$d = new RepairPageDatabase();
$repairPermissions = array('view' => true, 'edit' => true, 'delete' => true);
$func = new RepairFunctions();
$_GET = array('id_xe' => 22, 'p' => 2, 'new' => 1, 'ret' => rawurlencode('index.php?com=daotao&act=xe&hang=B1&p=3'));
$_SESSION = array();
dt_xe_suachua_page();
repair_check($xe_suachua_vehicle['id'] === 22 && $d->historyQueries[0][1] === array(22), 'History query must be scoped to the requested vehicle');
repair_check(count($xe_suachua_paging) <= 7 && $xe_suachua_total_pages === 2, 'Pagination should remain compact and count the full history');
ob_start();
require dirname(__DIR__).'/admin/templates/daotao/xe/suachua_tpl.php';
$rendered = ob_get_clean();
repair_check(strpos($rendered, '<script>plate</script>') === false && strpos($rendered, '&lt;script&gt;plate&lt;/script&gt;') !== false, 'Vehicle identity must be escaped');
repair_check(strpos($rendered, '<script>unsafe()</script>') === false && strpos($rendered, '&lt;script&gt;unsafe()&lt;/script&gt;') !== false, 'Repair descriptions must be escaped');
repair_check(strpos($rendered, 'Chưa nhập') !== false && strpos($rendered, 'Quá hạn') !== false, 'Unknown costs and maintenance status should be visible');
repair_check(strpos($rendered, '12.500,5 km') !== false, 'Maintenance odometer should preserve fractional kilometers');
repair_check(strpos($rendered, 'xeSuachuaImage') !== false && strpos($rendered, str_repeat('a', 32).'.jpg') !== false, 'Admin history should render attached photo links through the authorized image route');
repair_check(strpos($rendered, 'form-row') !== false, 'Responsive form layout should render');
repair_check(strpos($rendered, 'index.php?com=daotao&amp;act=xe&amp;hang=B1&amp;p=3') !== false, 'Vehicle list filters should be preserved on return');

$repairPermissions = array('view' => true, 'edit' => false, 'delete' => false);
ob_start();
require dirname(__DIR__).'/admin/templates/daotao/xe/suachua_tpl.php';
$viewOnly = ob_get_clean();
repair_check(strpos($viewOnly, 'xeSuachuaSave') === false && strpos($viewOnly, 'xeSuachuaDelete') === false && strpos($viewOnly, 'Thêm lần sửa chữa') === false, 'View-only users must not see mutation controls');

$d->hasHistory = false;
$repairPermissions = array('view' => true, 'edit' => true, 'delete' => true);
$_GET = array('id_xe' => 22);
dt_xe_suachua_page();
ob_start();
require dirname(__DIR__).'/admin/templates/daotao/xe/suachua_tpl.php';
$emptyState = ob_get_clean();
repair_check(strpos($emptyState, 'Xe chưa có nhật ký sửa chữa') !== false && strpos($emptyState, 'Ghi lần sửa đầu tiên') !== false, 'Empty history should offer a first-entry action');

echo "PASS: repair validation, create/update/delete, persistence failures, reminders, CSRF, view rendering, and protected vehicle deletion paths\n";
