<?php
define('SOURCES', true);

require_once __DIR__.'/../admin/sources/xangdau/helpers.php';
require_once __DIR__.'/../admin/sources/xangdau/excel_cell.php';
require_once __DIR__.'/../admin/sources/daotao/helpers.php';

class RegressionCeCell
{
	private $value;
	public function __construct($value) { $this->value = $value; }
	public function getValue() { return $this->value; }
	public function getCalculatedValue() { return $this->value; }
	public function getFormattedValue() { return (string)$this->value; }
}

class RegressionCeSheet
{
	private $rows;
	public function __construct($rows) { $this->rows = $rows; }
	public function getHighestRow() { return count($this->rows); }
	public function getCellByColumnAndRow($column, $row)
	{
		return new RegressionCeCell(isset($this->rows[$row][$column]) ? $this->rows[$row][$column] : '');
	}
}

function xd_open_upload_sheet($file, $ext, $backUrl, $sheetHints = array())
{
	global $regressionCeSheet;
	return array(null, $regressionCeSheet, $regressionCeSheet->getHighestRow(), 6);
}

require_once __DIR__.'/../admin/sources/xangdau/ce.php';

class RegressionCeDatabase
{
	public $queries = array();
	public $inserted = array();
	public $existing = array();
	public $failInsertAt = 0;
	public $committed = false;
	public $rolledBack = false;
	private $snapshot = array();

	public function rawQuery($sql, $params = array())
	{
		$this->queries[] = array($sql, $params);
		if(strpos($sql, 'select id, ma_khoa, hang from #_dt_khoa') === 0)
			return array(
				array('id' => 12, 'ma_khoa' => 'CE-101', 'hang' => 'CE'),
				array('id' => 13, 'ma_khoa' => 'B1-101', 'hang' => 'B1')
			);
		if(strpos($sql, 'insert into #_xd_ce_hoadon') === 0)
		{
			if($this->failInsertAt > 0 && count($this->inserted) + 1 === $this->failInsertAt) return false;
			$this->inserted[] = $params;
			return true;
		}
		return false;
	}

	public function rawQueryOne($sql, $params = array())
	{
		$this->queries[] = array($sql, $params);
		if(strpos($sql, 'from #_dt_khoa where id =') !== false)
		{
			if((int)$params[0] === 12) return array('id' => 12, 'ma_khoa' => 'CE-101', 'hang' => 'CE');
			return array('id' => 13, 'ma_khoa' => 'B1-101', 'hang' => 'B1');
		}
		if(strpos($sql, 'from #_xd_ce_hoadon') !== false)
		{
			$key = implode('|', $params);
			return isset($this->existing[$key]) ? array('id' => 91) : false;
		}
		return false;
	}

	public function startTransaction() { $this->snapshot = $this->inserted; }
	public function commit() { $this->committed = true; }
	public function rollback() { $this->inserted = $this->snapshot; $this->rolledBack = true; }
}

class RegressionCeTransfer
{
	public $message = '';
	public $success = null;
	public function transfer($message, $redirect, $success = true)
	{
		$this->message = $message;
		$this->success = $success;
		throw new RuntimeException('transfer');
	}
}

function regression_ce_check($condition, $message)
{
	if(!$condition)
	{
		fwrite(STDERR, "FAIL: $message\n");
		exit(1);
	}
}

function regression_ce_run($dataRows, $existing = array(), $failInsertAt = 0)
{
	global $d, $func, $regressionCeSheet, $login_admin;
	$rows = array(
		1 => array('Mã khóa CE', 'Số hóa đơn', 'Ngày hóa đơn', 'Số tiền', 'Giáo viên', 'Kỳ')
	);
	foreach($dataRows as $row) $rows[] = $row;
	$regressionCeSheet = new RegressionCeSheet($rows);
	$d = new RegressionCeDatabase();
	$d->existing = $existing;
	$d->failInsertAt = $failInsertAt;
	$func = new RegressionCeTransfer();
	$login_admin = 'admin';
	$_SESSION = array('admin' => array('username' => 'regression'));
	$_FILES = array('file-excel' => array('error' => UPLOAD_ERR_OK, 'name' => 'ce.xlsx', 'tmp_name' => '/tmp/ce.xlsx'));
	try { xd_ce_upload_invoices(); }
	catch(RuntimeException $e) { if($e->getMessage() !== 'transfer') throw $e; }
	return array($d, $func);
}

function regression_ce_save($post)
{
	global $d, $func, $login_admin;
	$d = new RegressionCeDatabase();
	$func = new RegressionCeTransfer();
	$login_admin = 'admin';
	$_SESSION = array('admin' => array('username' => 'regression'));
	$_POST = $post;
	try { xd_ce_save_invoice(); }
	catch(RuntimeException $e) { if($e->getMessage() !== 'transfer') throw $e; }
	return array($d, $func);
}

$valid = array('CE-101', 'CE-HD-01', '2026-10-20', 608.75, 'Nguyễn Văn A', '2026-10');
list($db, $transfer) = regression_ce_run(array($valid));
regression_ce_check($transfer->success === true && count($db->inserted) === 1 && $db->committed, 'a valid invoice row should be imported and committed');
regression_ce_check($db->inserted[0][4] === 608750.0, 'numeric Excel amounts below 100,000 must use the thousands convention');

$manual = array('id_khoa' => 12, 'ma_hoa_don' => 'CE-MANUAL-01', 'ngay_hoa_don' => '2026-10-20', 'tong_tien' => '608750', 'gv_hoten' => 'Nguyễn Văn A', 'ky' => '2026-10');
list($db, $transfer) = regression_ce_save($manual);
regression_ce_check($transfer->success === true && count($db->inserted) === 1 && $db->inserted[0][4] === 608750.0, 'a valid manual invoice should be saved in full VND units');

$manual['id_khoa'] = 13;
list($db, $transfer) = regression_ce_save($manual);
regression_ce_check($transfer->success === false && count($db->inserted) === 0, 'manual invoice entry must reject a non-CE course');

$manual['id_khoa'] = 12;
$manual['tong_tien'] = '608.75';
list($db, $transfer) = regression_ce_save($manual);
regression_ce_check($transfer->success === false && count($db->inserted) === 0, 'manual invoice entry must reject non-integer VND amounts');

list($db, $transfer) = regression_ce_run(array($valid, $valid));
regression_ce_check($transfer->success === false && count($db->inserted) === 0 && !$db->committed, 'a duplicate inside the file must reject the entire import before writing');

list($db, $transfer) = regression_ce_run(array($valid, array('B1-101', 'INVALID', '2026-10-20', 100, 'Nguyễn Văn B', '2026-10')));
regression_ce_check($transfer->success === false && count($db->inserted) === 0 && !$db->committed, 'a non-CE course must reject the entire import before writing');

list($db, $transfer) = regression_ce_run(array($valid), array('12|CE-HD-01|2026-10-20' => true));
regression_ce_check($transfer->success === false && count($db->inserted) === 0, 'an invoice already present in the database must be rejected');

$second = array('CE-101', 'CE-HD-02', '2026-10-21', 250, 'Nguyễn Văn B', '2026-10');
list($db, $transfer) = regression_ce_run(array($valid, $second), array(), 2);
regression_ce_check($transfer->success === false && $db->rolledBack && count($db->inserted) === 0, 'a database failure during insertion must roll back earlier rows');

echo "Xang dau CE invoice regression checks passed.\n";
