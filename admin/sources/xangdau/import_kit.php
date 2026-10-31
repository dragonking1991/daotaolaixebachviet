<?php
if(!defined('SOURCES')) die("Error");

/* Xăng dầu × bộ import dùng chung: thẻ kết quả, lịch sử, hoàn tác, đồng bộ DAT chạy thử. */

/*
 * Bọc $func trong các act import: transfer() không còn hiện trang tự chuyển 1 giây mà lưu thẻ kết quả
 * cố định (ghi lịch sử nếu thất bại) rồi chuyển về trang đích; khi upload nhiều file (dt_ajax) trả JSON.
 */
class XdImportResultFunctions
{
	private $inner;
	private $module;

	public function __construct($inner, $module)
	{
		$this->inner = $inner;
		$this->module = $module;
	}

	public function __call($name, $arguments)
	{
		return call_user_func_array(array($this->inner, $name), $arguments);
	}

	public function transfer($message = '', $page = '', $success = true)
	{
		xd_import_record_failure($this->module, $message, $success);
		if(!empty($_POST['dt_ajax']))
		{
			if(!headers_sent()) header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('success' => (bool)$success,
				'message' => trim(html_entity_decode(strip_tags(str_ireplace(array('<br>', '<br/>', '<br />'), ' · ', (string)$message)), ENT_QUOTES, 'UTF-8'))));
			exit;
		}
		ik_notice('xangdau', $message, $success);
		$this->inner->redirect($page !== '' ? $page : 'index.php?com=xangdau');
	}
}

function xd_import_record_failure($module, $message, $success)
{
	if($success || (int)($GLOBALS['ik_history_id'] ?? 0) > 0) return;
	if(empty($_FILES['file-excel']['name']) && !isset($_POST['confirm_roster'])) return;
	$lines = ik_error_lines($message);
	ik_history_add('xangdau', $module, array('filename' => (string)($_FILES['file-excel']['name'] ?? ($_SESSION['xd_roster_pending']['filename'] ?? '')),
		'status' => 'failed', 'so_loi' => max(1, count($lines)), 'message' => mb_substr(trim(strip_tags(str_ireplace('<br>', "\n", (string)$message))), 0, 5000),
		'errors' => $lines ?: array('Dòng 0: '.trim(strip_tags(str_ireplace('<br>', ' ', (string)$message))))));
}

/* Ghi lịch sử một lượt import XD thành công (kèm ảnh chụp bảng trước import để hoàn tác). */
function xd_import_history_done($module, $data)
{
	return ik_history_add('xangdau', $module, $data);
}

function xd_import_module_title($module)
{
	$titles = array('hocvien' => 'Học viên XD', 'hocvien_bc1' => 'Học viên XD (BC1 tài chính)', 'hoadon' => 'Hóa đơn xăng dầu', 'ce' => 'Hóa đơn CE');
	return $titles[$module] ?? $module;
}

function xd_import_history_page()
{
	global $ikHistoryRows, $ikScope, $ikUndoableId, $ikCanUndo, $ikModuleTitles, $ikUndoNote;
	$ikScope = 'xangdau';
	$ikHistoryRows = ik_history_list('xangdau', 150);
	$ikCanUndo = xd_can_xoa() && xd_can_import();
	$ikUndoableId = 0;
	foreach($ikHistoryRows as $row)
		if(xd_import_undo_blocker($row) === '') { $ikUndoableId = (int)$row['id']; break; }
	$ikModuleTitles = array();
	foreach($ikHistoryRows as $row) $ikModuleTitles[$row['module']] = xd_import_module_title($row['module']);
	$ikUndoNote = 'Không hoàn tác được nếu sau lượt import đã có thanh toán, kiểm tra, duyệt hoặc bảng kê.';
}

function xd_import_errors_download()
{
	global $func;
	$row = ik_history_get((int)($_GET['id'] ?? 0), 'xangdau');
	if(!$row) $func->transfer('Không tìm thấy lượt import.', 'index.php?com=xangdau&act=importHistory', false);
	ik_errors_csv($row);
	exit;
}

function xd_import_table_for($module)
{
	if($module === 'hocvien' || $module === 'hocvien_bc1') return 'xd_hocvien';
	if($module === 'hoadon') return 'xd_hoadon';
	return '';
}

function xd_import_snapshot_path($row)
{
	$name = basename((string)($row['backup'] ?? ''));
	if(!preg_match('/^xd_imp_[a-z_]+_\d{8}_\d{6}_[a-f0-9]{8}\.json$/', $name)) return '';
	$path = dirname(dirname(dirname(__DIR__))).'/backups/'.$name;
	return is_file($path) ? $path : '';
}

/* Trường thanh toán/kiểm tra: đã đổi sau import thì không hoàn tác tự động. */
function xd_import_guard_fields($table)
{
	return $table === 'xd_hoadon'
		? array('da_quyettoan', 'ke_toan_kiem_tra', 'id_bangke', 'quan_ly_duyet', 'ngay_thanh_toan')
		: array('ngay_thanh_toan', 'id_bangke', 'so_tien_thanh_toan', 'quan_ly_duyet', 'ke_toan_kiem_tra');
}

function xd_import_undo_blocker($row, &$snapshot = null)
{
	global $d;
	if(($row['status'] ?? '') !== 'done') return 'Chỉ hoàn tác lượt import đã ghi thành công.';
	$table = xd_import_table_for($row['module']);
	if($table === '') return 'Loại import này chưa hỗ trợ hoàn tác.';
	$modules = $table === 'xd_hocvien' ? array('hocvien', 'hocvien_bc1') : array('hoadon');
	if((int)$row['id'] !== ik_latest_done_id('xangdau', $modules)) return 'Chỉ hoàn tác được lượt import gần nhất của bảng này.';
	if((int)$row['created'] < time() - 7 * 86400) return 'Đã quá 7 ngày kể từ lúc import.';
	$path = xd_import_snapshot_path($row);
	if($path === '') return 'Không còn ảnh chụp dữ liệu trước import.';
	$batch = $d->rawQueryOne('select count(*) as c from #_xd_bangke where ngaytao >= ?', array((int)$row['created']));
	if($batch && (int)$batch['c'] > 0) return 'Đã có bảng kê thanh toán sau lượt import.';
	if($snapshot === null)
	{
		$data = json_decode((string)file_get_contents($path), true);
		if(!is_array($data) || ($data['table'] ?? '') !== $table || !isset($data['rows']) || !is_array($data['rows'])) return 'Ảnh chụp dữ liệu không hợp lệ.';
		$snapshot = $data['rows'];
	}
	$before = array();
	foreach($snapshot as $old) $before[(int)$old['id']] = $old;
	$fields = xd_import_guard_fields($table);
	foreach($d->rawQuery('select id, '.implode(', ', $fields).' from #_'.$table) as $current)
	{
		$old = $before[(int)$current['id']] ?? null;
		foreach($fields as $field)
		{
			$was = $old ? trim((string)($old[$field] ?? '')) : '';
			$now = trim((string)($current[$field] ?? ''));
			$empty = function($value) { return $value === '' || (is_numeric($value) && (float)$value == 0); };
			if($empty($was) && $empty($now)) continue;
			if(is_numeric($was) && is_numeric($now) && (float)$was == (float)$now) continue;
			if($was !== $now) return 'Đã có thanh toán/kiểm tra/duyệt thay đổi sau lượt import (ID '.(int)$current['id'].').';
		}
	}
	return '';
}

function xd_import_undo()
{
	global $d, $func;
	$back = 'index.php?com=xangdau&act=importHistory';
	if(strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') { ik_notice('xangdau', 'Vui lòng hoàn tác từ trang Lịch sử import.', false, array('steps' => false)); $func->redirect($back); }
	$row = ik_history_get((int)($_POST['id'] ?? 0), 'xangdau');
	if(!$row) { ik_notice('xangdau', 'Không tìm thấy lượt import.', false, array('steps' => false)); $func->redirect($back); }
	$snapshot = null;
	$blocker = xd_import_undo_blocker($row, $snapshot);
	if($blocker !== '') { ik_notice('xangdau', 'Không thể hoàn tác: '.$blocker, false, array('title' => 'Hoàn tác import', 'steps' => false)); $func->redirect($back); }
	$table = xd_import_table_for($row['module']);
	$preUndo = xd_backup_table_json($table, 'pre_undo');
	if($preUndo === '') { ik_notice('xangdau', 'Không sao lưu được hiện trạng; đã hủy hoàn tác.', false, array('steps' => false)); $func->redirect($back); }
	$d->startTransaction();
	$d->rawQuery('delete from #_'.$table);
	foreach($snapshot as $old)
	{
		$columns = array_keys($old);
		$d->rawQuery('insert into #_'.$table.' (`'.implode('`,`', $columns).'`) values ('.implode(',', array_fill(0, count($columns), '?')).')', array_values($old));
	}
	if($d->transactionFailed())
	{
		$d->rollback();
		ik_notice('xangdau', 'Hoàn tác thất bại khi ghi dữ liệu; dữ liệu hiện tại được giữ nguyên.', false, array('title' => 'Hoàn tác import', 'steps' => false));
		$func->redirect($back);
	}
	$d->commit();
	ik_history_update((int)$row['id'], array('status' => 'undone', 'undone_at' => time(), 'undone_by' => xd_username()));
	ik_notice('xangdau', 'Đã hoàn tác lượt import "'.$row['filename'].'": khôi phục '.count($snapshot).' bản ghi về trạng thái trước import.'
		."\nSao lưu hiện trạng trước khi hoàn tác: ".$preUndo, true, array('title' => 'Hoàn tác import', 'steps' => false));
	$func->redirect($back);
}

/* ===== Đồng bộ DAT: chạy thử + lần đồng bộ gần nhất ===== */

function xd_sync_last()
{
	if(!function_exists('dt_setting_get')) return null;
	$raw = dt_setting_get('xd_last_sync', '');
	$data = $raw ? json_decode($raw, true) : null;
	return is_array($data) ? $data : null;
}

function xd_sync_remember($result)
{
	if(!function_exists('dt_setting_set')) return;
	dt_setting_set('xd_last_sync', json_encode(array('time' => time(), 'user' => xd_username(), 'ok' => !empty($result['ok']),
		'added' => (int)($result['xd_added'] ?? 0), 'course' => (int)($result['xd_course_updated'] ?? 0),
		'group' => (int)($result['xd_group_updated'] ?? 0)), JSON_UNESCAPED_UNICODE));
}

function xd_sync_preview_page()
{
	global $xd_sync_preview, $xd_sync_last;
	$xd_sync_preview = xd_sync_training_students(0, '', true);
	$xd_sync_last = xd_sync_last();
}
