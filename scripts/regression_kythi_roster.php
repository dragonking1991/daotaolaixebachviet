<?php
if(PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
define('SOURCES', dirname(__DIR__).'/admin/sources/');
require SOURCES.'daotao/helpers.php';
require SOURCES.'daotao/kythi.php';

function roster_check($condition, $message)
{
	if(!$condition) throw new RuntimeException($message);
}
class RosterNotice extends RuntimeException
{
	public $success;
	public function __construct($message, $success) { parent::__construct($message); $this->success = $success; }
}
class RosterFunctions
{
	public function transfer($message, $back, $success = true) { throw new RosterNotice($message, $success); }
}
function dt_import_notice($message, $back, $success = true) { throw new RosterNotice($message, $success); }
function dt_backup_tables($tables, $tag) { return $GLOBALS['backup_ok']; }
function dt_audit($action, $entity, $affected, $detail) {}
function dt_log_import($entity, $file, $count, $errors) {}
function dt_open_upload_sheet($file, $ext, $back) { return array(null, count($GLOBALS['excel_rows']) + 1, 2); }
function dt_find_header_row($sheet, $highestRow, $highestCol, $aliases, $contains)
{
	return array(1, array('cccd' => 0, 'hoten' => 1), 2);
}
function dt_read_row($sheet, $row, $highestCol) { return $GLOBALS['excel_rows'][$row - 2]; }

class RosterDatabase
{
	public $rows = array();
	public $failInsert = false;
	public $failDelete = false;
	public $error = '';
	public $rolledBack = false;
	private $snapshot;
	private $whereId;
	public function rawQueryOne($sql, $params = array())
	{
		if(strpos($sql, 'from #_dt_hocvien') !== false) return null;
		if(strpos($sql, 'from #_dt_ky_thi where') !== false) return $params[0] === 7 ? array('id' => 7) : null;
		if(strpos($sql, 'from #_dt_ky_thi_hv') !== false)
		{
			foreach($this->rows as $row)
				if($row['id_ky'] === $params[0] && $row['cccd'] === $params[1]) return $row;
			return null;
		}
		throw new RuntimeException('Unexpected query: '.$sql);
	}
	public function rawQuery($sql, $params = array())
	{
		roster_check(strpos($sql, 'delete from #_dt_ky_thi_hv where ') === 0, 'Only roster rows can be deleted');
		$this->error = '';
		if($this->failDelete) { $this->error = 'delete failed'; return array(); }
		foreach($this->rows as $key => $row)
		{
			if($row['id_ky'] !== $params[0]) continue;
			if(strpos($sql, 'cccd not in') !== false && in_array($row['cccd'], array_slice($params, 1), true)) continue;
			if(strpos($sql, 'and id in') !== false && !in_array($row['id'], array_slice($params, 1), true)) continue;
			unset($this->rows[$key]);
		}
		return array();
	}
	public function insert($table, $data)
	{
		roster_check($table === 'dt_ky_thi_hv', 'Unexpected insert table');
		if($this->failInsert) return false;
		$data['id'] = count($this->rows) + 1000;
		$this->rows[$data['id']] = $data;
		return $data['id'];
	}
	public function where($field, $value) { $this->whereId = $value; }
	public function update($table, $data)
	{
		roster_check($table === 'dt_ky_thi_hv', 'Unexpected update table');
		$this->rows[$this->whereId] = array_merge($this->rows[$this->whereId], $data);
		return true;
	}
	public function getLastErrorCode() { return $this->error === '' ? '00000' : 'HY000'; }
	public function startTransaction() { $this->snapshot = $this->rows; }
	public function commit() { return true; }
	public function rollback() { $this->rows = $this->snapshot; $this->rolledBack = true; }
}

$func = new RosterFunctions();
foreach(array('replace', 'default', 'append', 'empty', 'invalid', 'duplicate', 'backup', 'insert_error', 'delete_error', 'selected', 'all', 'no_selection') as $scenario)
{
	$d = new RosterDatabase();
	$backup_ok = $scenario !== 'backup';
	$excel_rows = array();
	for($index = 1; $index <= 167; $index++)
	{
		$cccd = str_pad((string)$index, 12, '0', STR_PAD_LEFT);
		$d->rows[$index] = array('id' => $index, 'id_ky' => 7, 'cccd' => $cccd, 'hoten' => 'Old', 'co_ket_qua' => 1, 'ket_luan' => 'Dat');
		if($index <= 166) $excel_rows[] = array($cccd, 'New '.$index);
	}
	$d->rows[999] = array('id' => 999, 'id_ky' => 8, 'cccd' => '000000000999', 'hoten' => 'Other exam');
	$before = $d->rows;
	$_REQUEST = array('id' => 7);
	$_POST = array('id' => 7, 'roster_mode' => $scenario === 'append' ? 'append' : 'replace');
	$_FILES = array('file-excel' => array('name' => 'roster.xlsx', 'error' => 0));
	if($scenario === 'default') unset($_POST['roster_mode']);
	if($scenario === 'empty') $excel_rows = array();
	if($scenario === 'invalid') $excel_rows[100][0] = '123';
	if($scenario === 'duplicate') $excel_rows[] = $excel_rows[0];
	if($scenario === 'insert_error') { $excel_rows[] = array('000000000500', 'New student'); $d->failInsert = true; }
	if($scenario === 'delete_error') $d->failDelete = true;
	if($scenario === 'selected') $_POST['ids'] = array(1, 2, 999);
	if($scenario === 'all') $_POST['delete_all'] = '1';
	try
	{
		if(in_array($scenario, array('selected', 'all', 'no_selection'), true)) dt_kythi_remove_selected();
		else dt_kythi_import_roster();
		throw new RuntimeException('Missing outcome');
	}
	catch(RosterNotice $notice)
	{
		$success = in_array($scenario, array('replace', 'default', 'append', 'selected', 'all'), true);
		roster_check($notice->success === $success, 'Unexpected outcome: '.$scenario.' '.$notice->getMessage());
		roster_check($d->rows[999] === $before[999], 'Other exam modified: '.$scenario);
		if(!$success) roster_check($d->rows === $before, 'Failed operation changed roster: '.$scenario);
		elseif($scenario === 'selected') roster_check(count($d->rows) === 166 && !isset($d->rows[1], $d->rows[2]), 'Selected deletion failed');
		elseif($scenario === 'all') roster_check(count($d->rows) === 1, 'Delete all must only clear current exam');
		else
		{
			roster_check(count($d->rows) === ($scenario === 'append' ? 168 : 167), '167 to 166 replacement failed');
			roster_check($d->rows[1]['hoten'] === 'New 1' && $d->rows[1]['ket_luan'] === 'Dat', 'Identity update must preserve exam results');
		}
	}
	echo '[PASS] roster '.$scenario."\n";
}

function dt_can($section, $action) { return $GLOBALS['can_delete']; }
$ky = array('id' => 7, 'ten' => 'Test exam', 'ngay_kt' => '2026-10-23');
$ds_khoa = array(); $tracuu_kw = ''; $total_items = 1;
$items = array(array('id' => 1, 'hoten' => 'Student', 'cccd' => '000000000001', 'hang' => 'B1', 'ma_khoa' => 'K1', 'ten_khoa' => 'Course', 'giaovien' => 'Teacher', 'co_ket_qua' => 0));
foreach(array(true, false) as $can_delete)
{
	ob_start();
	require dirname(__DIR__).'/admin/templates/daotao/kythi/detail_tpl.php';
	$html = ob_get_clean();
	roster_check((strpos($html, 'id="dt-bulk-kythi-roster"') !== false) === $can_delete, 'Delete form permission gating');
	roster_check((strpos($html, 'name="delete_all"') !== false) === $can_delete, 'Delete-all permission gating');
	roster_check((strpos($html, 'class="dt-bulk-item" form="dt-bulk-kythi-roster" name="ids[]"') !== false) === $can_delete, 'Checkbox must submit to the matching form');
}
ob_start();
require dirname(__DIR__).'/admin/templates/daotao/kythi/roster_tpl.php';
$html = ob_get_clean();
roster_check(strpos($html, 'name="roster_mode" value="replace" checked') !== false, 'Replacement must be checked by default');
roster_check(strpos($html, 'type="hidden" name="roster_mode" value="append"') !== false, 'Unchecked replacement must submit append mode');
echo "[PASS] rendered selection forms, permissions and default replacement\n";