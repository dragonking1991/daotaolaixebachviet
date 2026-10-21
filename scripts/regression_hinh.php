<?php
if(PHP_SAPI !== 'cli')
{
	http_response_code(403);
	exit('CLI only');
}
$mode = $argv[1] ?? '';
if(!in_array($mode, array('admin', 'portal'), true)) exit("Usage: php scripts/regression_hinh.php admin|portal\n");
define('SOURCES', dirname(__DIR__).'/admin/sources/');
define('LIBRARIES', dirname(__DIR__).'/libraries/');
require LIBRARIES.'class/class.PDODb.php';
if($mode === 'admin')
{
	require SOURCES.'daotao/config_daotao.php';
	require SOURCES.'daotao/helpers.php';
	require SOURCES.'daotao/export.php';
	require SOURCES.'daotao/dat.php';
	require SOURCES.'daotao/thuchanh_hinh.php';
	require SOURCES.'daotao/tonghop.php';
	require SOURCES.'daotao/crud.php';
}
else require LIBRARIES.'daotao_lib.php';

function hinh_check($ok, $message)
{
	if(!$ok) throw new RuntimeException($message);
}

/* Exercise the real normalization that caused NULL to become 0, without a DB connection. */
class HinhNormalizer extends PDODb
{
	public function __construct() {}
	public function getAllSchema($table = null)
	{
		return array('dat_override' => array('COLUMN_KEY' => '', 'DATA_TYPE' => 'tinyint'));
	}
}

class HinhDatabase
{
	public $record;
	public $student = array('id' => 1, 'id_khoa' => 7, 'cccd' => '000000000001', 'hoten' => 'Hoc vien thu', 'ma_hv' => 'TEST', 'hang' => 'B11', 'gv_hoten' => 'GV Thu', 'gv_key' => 'gv thu');
	public function rawQueryOne($sql, $params = array())
	{
		if(strpos($sql, '#_dt_hocvien') !== false) return $this->student;
		if(strpos($sql, '#_dt_thuchanh_hinh') !== false) return $this->record;
		if(strpos($sql, '#_dt_cabin_kq') !== false) return array('dat' => 1);
		if(strpos($sql, '#_dt_lythuyet') !== false) return array('c' => 7);
		if(strpos($sql, '#_dt_mophong') !== false) return null;
		if(strpos($sql, '#_dt_dat_override') !== false) return null;
		throw new RuntimeException('Unexpected query: '.$sql);
	}
	public function rawQuery($sql, $params = array())
	{
		if(strpos($sql, 'CREATE TABLE IF NOT EXISTS table_dt_gv_log') === 0) return array();
		if(strpos($sql, '#_dt_gv_log') !== false) return array();
		if(strpos($sql, '#_dt_dat_phien') !== false)
			return array(array('gio_thuchanh' => 30, 'la_xe_tudong' => 1, 'gio_dem' => 2, 'km' => 900));
		if(strpos($sql, '#_dt_lythuyet') !== false)
			return array_map(function($mon) { return array('mon' => $mon, 'dat' => 1); }, array_keys(dt_mon_lythuyet()));
		if(strpos($sql, 'th.id as data_id') !== false)
			return array(array_merge($this->student, $this->record ?? array('gio' => null, 'km' => null, 'dat_override' => null)));
		if(strpos($sql, 'select h.*') === 0) return array($this->student);
		throw new RuntimeException('Unexpected query: '.$sql);
	}
	public function where($field, $value) {}
	public function insert($table, $data)
	{
		if($table === 'dt_gv_log') return 1;
		return $this->update($table, $data);
	}
	public function update($table, $data)
	{
		hinh_check($table === 'dt_thuchanh_hinh', 'Unexpected write: '.$table);
		$normalizer = new HinhNormalizer();
		$normalizer->fillTable($table, $data);
		$this->record = array_merge($this->record ?? array(), $data, array('id' => 1));
		return true;
	}
}

class HinhTransfer extends RuntimeException
{
	public $success;
	public function __construct($message, $success)
	{
		parent::__construct($message);
		$this->success = $success;
	}
}
class HinhFunctions
{
	public function getPageURL() { return 'cong-giao-vien'; }
	public function transfer($message, $back, $success = true) { throw new HinhTransfer($message, $success); }
}
class HinhSeo
{
	public function setSeo($key, $value) {}
}
class HinhBreadcrumbs
{
	public function getBreadCrumbs() { return ''; }
}
function dt_khoa_options() { return array(); }
function dt_audit($action, $module, $count, $detail) {}

$d = new HinhDatabase();
$func = new HinhFunctions();
$seo = new HinhSeo();
$breadcr = new HinhBreadcrumbs();
$_SESSION = array('dt_gv' => array('id' => 1, 'cccd' => '000000000002', 'hoten' => 'GV thu', 'gv_key' => 'gv thu'));
$_REQUEST = array('id_khoa' => 7);
$_POST = array('dt_act' => 'savehinh', 'id_khoa' => 7, 'cccd' => $d->student['cccd']);

if($mode === 'portal')
{
	/* Keep all other portal behavior real while supplying the enabled setting. */
	$d = new class extends HinhDatabase {
		public function rawQueryOne($sql, $params = array())
		{
			if(strpos($sql, '#_dt_setting') !== false) return array('sval' => '{"allow_hinh_input":1}');
			return parent::rawQueryOne($sql, $params);
		}
		public function rawQuery($sql, $params = array())
		{
			if(strpos($sql, 'select distinct k.id') === 0) return array();
			return parent::rawQuery($sql, $params);
		}
	};
}

if($mode === 'portal') $d->student['hang'] = $argv[5] ?? 'B11';
$samples = $mode === 'admin' ? array(array(36, 127), array(35, 121)) : array(array((float)($argv[2] ?? 36), (float)($argv[3] ?? 127)));
foreach($samples as $values)
{
	$d->record = ($mode === 'portal' && ($argv[4] ?? '') === 'update') ? array('id' => 1, 'dat_override' => 1, 'gio' => 0, 'km' => 0) : null;
	$_POST['gio'] = str_replace('.', ',', number_format($values[0], 2, '.', ''));
	$_POST['km'] = str_replace('.', ',', number_format($values[1], 2, '.', ''));
	$operations = $mode === 'admin' ? array('insert', 'update') : array($d->record ? 'update' : 'insert');
	foreach($operations as $operation)
	{
		if($mode === 'portal')
		{
			$before = $d->record;
			require dirname(__DIR__).'/sources/cong_giaovien.php';
			$limit = dt_nguong_hinh()[$d->student['hang']];
			$overCap = $values[0] > $limit['gio_max'] || $values[1] > $limit['km_max'];
			if($overCap)
			{
				hinh_check(strpos($dtGvErr, 'vượt trần') !== false && $dtGvMsg === '', 'Teacher must see a blocking limit error');
				hinh_check($d->record === $before, 'Rejected teacher save must not insert or modify practice data');
			}
			else
			{
				hinh_check($dtGvErr === '' && $dtGvMsg !== '', 'Teacher save must succeed');
				hinh_check($dtStudents[0]['sum']['hinh']['dat'] === 1, 'Teacher portal must show automatic pass after '.$operation);
			}
		}
		else
		{
			try { dt_hinh_save(); }
			catch(HinhTransfer $e) {}
			dt_hinh_list();
			hinh_check($items[0]['dat'] === 1, 'Admin practice list must automatically pass after '.$operation);
			$rows = dt_tonghop_rows(dt_tonghop_filters());
			hinh_check($rows[0]['sum']['hinh']['dat'] === 1 && $rows[0]['sum']['du_dieu_kien'] === 1, 'Admin summary must update both practice and eligibility');
			$_REQUEST['trang_thai'] = 'dat';
			dt_hinh_list();
			hinh_check(count($items) === 1, 'Passed filter must include automatically passed student');
			$_REQUEST['trang_thai'] = 'chua';
			dt_hinh_list();
			hinh_check(count($items) === 0, 'Pending filter must exclude automatically passed student');
			unset($_REQUEST['trang_thai']);
			$_REQUEST['gv'] = 'GV Thu';
			dt_hinh_list();
			hinh_check(count($items) === 1, 'Teacher filter must keep the matching teacher');
			$_REQUEST['gv'] = 'Nguoi Khac';
			dt_hinh_list();
			hinh_check(count($items) === 0, 'Teacher filter must exclude other teachers');
			unset($_REQUEST['gv']);
		}
		if($mode === 'admin' || !$overCap)
			hinh_check($d->record['dat_override'] === 0, 'Regression must exercise real PDODb NULL-to-zero conversion');
	}
}

$expectedThresholds = array(
	'B11' => array('km' => 120, 'gio' => 34, 'km_max' => 130, 'gio_max' => 36),
	'B1' => array('km' => 120, 'gio' => 34, 'km_max' => 130, 'gio_max' => 36),
	'C1' => array('km' => 115, 'gio' => 35, 'km_max' => 120, 'gio_max' => 37),
	'C' => array('km' => 15, 'gio' => 7, 'km_max' => 20, 'gio_max' => 8),
	'CE' => array('km' => 15, 'gio' => 7, 'km_max' => 20, 'gio_max' => 8),
);
hinh_check(dt_nguong_hinh() === $expectedThresholds, 'Category thresholds must match the prescribed hour/km limits');
foreach($expectedThresholds as $hang => $threshold)
{
	foreach(array(null, '', 0, '0') as $override)
	{
		hinh_check(dt_hinh_dat($hang, $threshold['gio'] + 0.01, $threshold['km'], $override) === 1, 'Automatic pass must ignore unset/zero override: '.$hang);
		hinh_check(dt_hinh_dat($hang, $threshold['gio'], $threshold['km'], $override) === 0, 'Keep strict hour threshold: '.$hang);
		hinh_check(dt_hinh_dat($hang, $threshold['gio'] + 1, $threshold['km'] - 0.01, $override) === 0, 'Insufficient km must not pass: '.$hang);
	}
	hinh_check(dt_hinh_dat($hang, 0, 0, '1') === 1, 'Manual pass must remain supported: '.$hang);
	hinh_check(dt_hinh_cap_warn($hang, $threshold['gio_max'], $threshold['km_max']) === array(), 'Exact maximum must be allowed: '.$hang);
	hinh_check(count(dt_hinh_cap_warn($hang, $threshold['gio_max'] + 0.01, $threshold['km_max'])) === 1, 'Exceeding hours alone must be rejected: '.$hang);
	hinh_check(count(dt_hinh_cap_warn($hang, $threshold['gio_max'], $threshold['km_max'] + 0.01)) === 1, 'Exceeding km alone must be rejected: '.$hang);
	if($mode === 'admin')
	{
		$d->student['hang'] = $hang;
		foreach(array(0, 1) as $id)
		foreach(array('quick', 'detail') as $form)
		foreach(array(
			array($threshold['gio_max'], $threshold['km_max'], true),
			array($threshold['gio_max'] + 0.01, $threshold['km_max'], false),
			array($threshold['gio_max'], $threshold['km_max'] + 0.01, false),
			array(0, 0, true),
		) as $case)
		{
			$d->record = $id ? array('id' => 1, 'dat_override' => 1, 'gio' => 1, 'km' => 1) : null;
			$before = $d->record;
			$_POST = array('id' => $id, 'entity' => 'hinh', 'cccd' => $d->student['cccd'], 'gio' => $case[0], 'km' => $case[1], 'dat_override' => '1');
			$_POST['data'] = array('id_khoa' => 7, 'cccd' => $d->student['cccd'], 'gio' => (string)$case[0], 'km' => (string)$case[1]);
			try
			{
				if($form === 'quick') dt_hinh_save();
				else dt_crud_save();
				throw new RuntimeException('Save must report an outcome');
			}
			catch(HinhTransfer $e)
			{
				hinh_check($e->success === $case[2], 'Admin '.$form.' must enforce caps for '.$hang);
				if(!$case[2])
				{
					hinh_check(strpos($e->getMessage(), 'vượt trần') !== false, 'Admin must see explicit limit error');
					hinh_check($d->record === $before, 'Rejected admin save must preserve existing data');
				}
				elseif($form === 'quick' && $case[0] === 0)
					hinh_check($d->record['dat_override'] === 1, 'Admin manual pass remains available below minimum');
			}
		}
	}
}
hinh_check(dt_hinh_dat('unknown', 100, 1000, 0) === 0, 'Unknown category must not pass automatically');
$d->student['hang'] = 'B11';
$d->record = array('dat_override' => 1, 'gio' => 0, 'km' => 0);
$d->record['gio'] = $d->record['km'] = 0;
$summary = dt_student_summary($d->student);
hinh_check($summary['hinh']['dat'] === 1, 'Summary must honor manual pass consistently');
$d->record['dat_override'] = 0;
$d->record['gio'] = 34;
$d->record['km'] = 127;
hinh_check(dt_student_summary($d->student)['du_dieu_kien'] === 0, 'Insufficient practice hours must block eligibility');
$d->record['gio'] = 36;
$d->record['km'] = 119;
hinh_check(dt_student_summary($d->student)['hinh']['dat'] === 0, 'Insufficient practice km must remain pending in summary');
$d->record = null;
hinh_check(dt_student_summary($d->student)['hinh']['dat'] === 0, 'Missing practice data must not pass');
echo "[PASS] ".$mode.": save ".implode('/', $operations).", PDODb normalization, automatic/manual pass, per-category caps and summary\n";
