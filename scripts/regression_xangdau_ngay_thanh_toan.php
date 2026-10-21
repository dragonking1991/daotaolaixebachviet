<?php
define('SOURCES', true);
require_once __DIR__.'/../admin/sources/xangdau/loc.php';

class RegressionXdDatabase
{
	public $queries = array();
	public $remainingStudents = 0;
	public $remainingInvoices = 0;
	public $committed = false;
	public $rolledBack = false;

	public function rawQuery($sql, $params = array())
	{
		$this->queries[] = array($sql, $params);
		if(strpos($sql, 'select distinct id_bangke from #_xd_hoadon') === 0) return array(array('id_bangke' => 41));
		if(strpos($sql, 'select distinct id_bangke from #_xd_hocvien') === 0) return array(array('id_bangke' => 41));
		if(strpos($sql, 'update #_xd_') === 0 || strpos($sql, 'delete from #_xd_bangke') === 0) return true;
		return false;
	}

	public function rawQueryOne($sql, $params = array())
	{
		$this->queries[] = array($sql, $params);
		if(strpos($sql, 'sum(so_tien_thanh_toan)') !== false)
			return array('total' => $this->remainingStudents, 'tong_tien' => 400000);
		if(strpos($sql, 'from #_xd_hoadon') !== false)
			return array('total' => $this->remainingInvoices);
		return false;
	}

	public function startTransaction() {}
	public function commit() { $this->committed = true; }
	public function rollback() { $this->rolledBack = true; }
}

class RegressionXdTransfer
{
	public $message = '';
	public $redirect = '';

	public function transfer($message, $redirect, $success = true)
	{
		$this->message = $message;
		$this->redirect = $redirect;
		throw new RuntimeException('transfer');
	}
}

function regression_xd_check($condition, $message)
{
	if(!$condition)
	{
		fwrite(STDERR, "FAIL: $message\n");
		exit(1);
	}
}

function regression_xd_cancel_day($remainingStudents, $remainingInvoices)
{
	global $d, $func;
	$d = new RegressionXdDatabase();
	$d->remainingStudents = $remainingStudents;
	$d->remainingInvoices = $remainingInvoices;
	$func = new RegressionXdTransfer();
	$_POST = array(
		'gv_key' => 'nguyen van a',
		'payment_date' => '2026-10-19',
		'keyword' => 'Nguyen',
		'paid_from' => '2026-10-01',
		'paid_to' => '2026-10-31'
	);
	$_REQUEST = $_POST;
	try { xd_huy_duyet_giao_vien(); }
	catch(RuntimeException $e) { if($e->getMessage() !== 'transfer') throw $e; }
	return array($d, $func);
}

list($db, $transfer) = regression_xd_cancel_day(1, 1);
$studentUpdate = null;
$invoiceUpdate = null;
$batchUpdate = null;
foreach($db->queries as $query)
{
	if(strpos($query[0], 'update #_xd_hocvien') === 0) $studentUpdate = $query;
	if(strpos($query[0], 'update #_xd_hoadon') === 0) $invoiceUpdate = $query;
	if(strpos($query[0], 'update #_xd_bangke') === 0) $batchUpdate = $query;
}
regression_xd_check($studentUpdate && strpos($studentUpdate[0], 'gv_key = ? and ngay_thanh_toan = ?') !== false, 'student rollback must be scoped to teacher and selected date');
regression_xd_check($invoiceUpdate && strpos($invoiceUpdate[0], 'gv_key = ? and ngay_thanh_toan = ?') !== false, 'invoice rollback must be scoped to teacher and selected date');
regression_xd_check($studentUpdate[1] === array('nguyen van a', '2026-10-19', 41), 'student update parameters must preserve the selected teacher/date/batch');
regression_xd_check($invoiceUpdate[1] === array('nguyen van a', '2026-10-19', 41), 'invoice update parameters must preserve the selected teacher/date/batch');
regression_xd_check($batchUpdate !== null && !$db->rolledBack && $db->committed, 'shared batch must be updated rather than deleted when other approved data remains');
regression_xd_check(strpos($transfer->redirect, 'paid_from=2026-10-01') !== false && strpos($transfer->redirect, 'paid_to=2026-10-31') !== false, 'redirect must retain the active payment-date filters');

$_GET = array('gv_key' => 'nguyen van a', 'payment_date' => '2026-10-19');
$_POST = array();
$_REQUEST = $_GET;
$db = new RegressionXdDatabase();
$d = $db;
$func = new RegressionXdTransfer();
try { xd_huy_duyet_giao_vien(); }
catch(RuntimeException $e) { if($e->getMessage() !== 'transfer') throw $e; }
regression_xd_check(empty($db->queries) && strpos($func->message, 'Không xác định được giáo viên') !== false, 'cancellation must not execute from a GET link without explicit POST selection');

list($db, $transfer) = regression_xd_cancel_day(0, 0);
$batchDelete = false;
foreach($db->queries as $query)
	if(strpos($query[0], 'delete from #_xd_bangke') === 0) $batchDelete = true;
regression_xd_check($batchDelete && $db->committed, 'empty batch must be deleted after its last approved records are reverted');

$_REQUEST = array('keyword' => 'Nguyen', 'paid_from' => '2026-10-01', 'paid_to' => '2026-10-31');
$db = new RegressionXdDatabase();
$d = $db;
xd_loc_da_thanh_toan();
$dailyListQuery = null;
foreach($db->queries as $query)
	if(strpos($query[0], 'select paid.gv_key') === 0) $dailyListQuery = $query;
regression_xd_check($dailyListQuery && strpos($dailyListQuery[0], 'union all') !== false && strpos($dailyListQuery[0], 'group by paid.gv_key, paid.ngay_thanh_toan') !== false, 'paid list must return one grouped row per teacher/payment date from invoices and students');
regression_xd_check(count($dailyListQuery[1]) === 8, 'name and date filters must apply to both invoice and student records');

echo "Xang dau selected-payment-date regression checks passed.\n";
