<?php
if(PHP_SAPI !== 'cli')
{
	http_response_code(403);
	exit('CLI only');
}

define('SOURCES', dirname(__DIR__).'/admin/sources/');
define('LIBRARIES', dirname(__DIR__).'/libraries/');
require SOURCES.'daotao/config_daotao.php';
require SOURCES.'daotao/helpers.php';

function dt_backup_tables($tables, $tag = 'daotao') { return 'backup.sql'; }
function dt_audit($action, $entity, $affected = 0, $detail = '') {}
function xd_sync_training_students($idKhoa) { return array('ok' => true); }
function xd_sync_training_notice($result) { return 'sync ok'; }

class BulkTransfer extends RuntimeException
{
	public $success;
	public function __construct($message, $success = true)
	{
		parent::__construct($message);
		$this->success = $success;
	}
}

class BulkFunctions
{
	public function transfer($message, $back, $success = true)
	{
		throw new BulkTransfer($message, $success);
	}
}

class BulkDatabase
{
	public $students = array();
	public $writes = array();
	public $commits = 0;

	public function rawQuery($sql, $params = array())
	{
		if(strpos($sql, 'select id, id_khoa, ma_hv, cccd, hang from #_dt_hocvien') === 0)
		{
			$hasCourseFilter = strpos($sql, ' and id_khoa = ?') !== false;
			$idCount = substr_count($sql, '?') - ($hasCourseFilter ? 1 : 0);
			$ids = array_slice($params, 0, $idCount);
			$idKhoa = $hasCourseFilter ? (int)end($params) : 0;
			$out = array();
			foreach($this->students as $student)
				if(in_array((int)$student['id'], $ids, true) && (!$idKhoa || (int)$student['id_khoa'] === $idKhoa)) $out[] = $student;
			return $out;
		}
		$this->writes[] = array($sql, $params);
		return array();
	}

	public function rawQueryOne($sql, $params = array())
	{
		if(strpos($sql, 'select count(*) as c') === 0) return array('c' => 1);
		throw new RuntimeException('Unexpected query: '.$sql);
	}

	public function startTransaction() {}
	public function commit() { $this->commits++; }
}

function bulk_check($condition, $message)
{
	if(!$condition) throw new RuntimeException($message);
}

require SOURCES.'daotao/crud.php';
$d = new BulkDatabase();
$func = new BulkFunctions();
$login_admin = 'admin_user';
$_SESSION = array('admin_user' => array('username' => 'regression'));
$_SERVER['REQUEST_METHOD'] = 'POST';
$d->students = array(
	array('id' => 1, 'id_khoa' => 7, 'ma_hv' => 'HV001', 'cccd' => '000000000001', 'hang' => 'B11'),
	array('id' => 2, 'id_khoa' => 7, 'ma_hv' => 'HV002', 'cccd' => '000000000002', 'hang' => 'C'),
);

foreach(array('lythuyet', 'mophong', 'cabinkq', 'hinh', 'dat') as $entity)
{
	$d->writes = array();
	$_REQUEST = array('entity' => $entity, 'ret' => rawurlencode('index.php?com=daotao&act='.$entity.'&id_khoa=7'));
	$_POST = array('entity' => $entity, 'id_khoa' => 7, 'ids' => array(1, 2));
	try { dt_module_pass_selected(); }
	catch(BulkTransfer $e) { bulk_check($e->success && strpos($e->getMessage(), '2 học viên') !== false, 'Unexpected pass result for '.$entity); }

	$writtenSql = implode("\n", array_map(function($entry) { return $entry[0]; }, $d->writes));
	$expected = array(
		'lythuyet' => '#_dt_lythuyet',
		'mophong' => '#_dt_mophong',
		'cabinkq' => '#_dt_cabin_kq',
		'hinh' => '#_dt_thuchanh_hinh',
		'dat' => '#_dt_dat_override',
	);
	bulk_check(strpos($writtenSql, $expected[$entity]) !== false, 'No expected result write for '.$entity);
	if($entity === 'mophong' || $entity === 'lythuyet')
		bulk_check(strpos($writtenSql, '#_dt_lythuyet') !== false && strpos($writtenSql, '#_dt_mophong') !== false, 'Simulation result was not synchronized with theory');
}

$d->writes = array();
$_REQUEST = array('entity' => 'hinh', 'ret' => rawurlencode('index.php?com=daotao&act=hinh&id_khoa=7'));
$_POST = array('entity' => 'hinh', 'id_khoa' => 7, 'ids' => array(1, 2));
try { dt_module_delete_selected(); }
catch(BulkTransfer $e) { bulk_check($e->success && strpos($e->getMessage(), 'xóa 2 kết quả') !== false, 'Unexpected bulk-delete result'); }
bulk_check(strpos(implode("\n", array_map(function($entry) { return $entry[0]; }, $d->writes)), 'delete from #_dt_thuchanh_hinh') !== false, 'Bulk delete did not remove selected result rows');

$d->writes = array();
$_REQUEST = array('entity' => 'hinh', 'ret' => rawurlencode('index.php?com=daotao&act=hinh&id_khoa=8'));
$_POST = array('entity' => 'hinh', 'id_khoa' => 8, 'ids' => array(1));
try { dt_module_pass_selected(); }
catch(BulkTransfer $e) { bulk_check(!$e->success && strpos($e->getMessage(), 'không thuộc') !== false, 'Cross-course selection was not rejected'); }
bulk_check(empty($d->writes), 'Invalid cross-course selection modified data');

echo "PASS: selected pass/delete across modules, simulation sync, and course-bound selection\n";
