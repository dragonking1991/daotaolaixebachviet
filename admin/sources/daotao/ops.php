<?php
if(!defined('SOURCES')) die("Error");

/* ===== Hạ tầng dùng chung cho phân hệ đào tạo: audit, backup, migration ===== */

/* Ghi nhật ký thao tác quan trọng (xóa/import/…). Bảng tạo trong schema.php. */
function dt_audit($action, $entity, $affected = 0, $detail = '')
{
	global $d;
	$d->insert('dt_audit', array(
		'action' => (string)$action,
		'entity' => (string)$entity,
		'affected' => (int)$affected,
		'detail' => mb_substr((string)$detail, 0, 900),
		'user' => dt_username(),
		'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
		'ngaytao' => time(),
	));
}

/* Persist an import result across redirect so errors stay visible on the list page.
 * Khi import nhiều file qua dropzone (dt_ajax=1) thì trả JSON để JS gộp kết quả. */
function dt_import_notice($message, $url, $success = true)
{
	global $func;

	if(!empty($_POST['dt_ajax']))
	{
		if(!headers_sent()) header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('success' => (bool)$success, 'message' => (string)$message));
		exit;
	}

	$_SESSION['dt_import_notice'] = array(
		'message' => (string)$message,
		'success' => (bool)$success,
		'time' => time(),
	);
	$func->redirect($url);
}

/* Các bảng dữ liệu đào tạo được phép sao lưu (whitelist an toàn). */
function dt_backup_tables_list()
{
	return array('dt_khoa','dt_hocvien','dt_xe','dt_giaovien','dt_lythuyet','dt_cabin_kq','dt_dat_phien','dt_thuchanh_hinh');
}

/**
 * Sao lưu (PHP-dump) một số bảng dt_* ra thư mục backups/ trước thao tác phá hủy.
 * Chỉ nhận bảng nằm trong whitelist. Trả về đường dẫn file hoặc '' nếu bỏ qua.
 */
function dt_backup_tables($tables, $tag = 'daotao')
{
	global $d;
	$allow = dt_backup_tables_list();
	$tables = array_values(array_intersect($tables, $allow));
	if(empty($tables)) return '';

	$dir = dirname(dirname(dirname(__DIR__))).'/backups';
	if(!is_dir($dir)) @mkdir($dir, 0775, true);
	if(!is_writable($dir)) return '';

	$file = $dir.'/dt_'.preg_replace('/[^a-z0-9_]/i', '', $tag).'_'.date('Ymd_His').'.sql';
	$fh = @fopen($file, 'w');
	if(!$fh) return '';

	fwrite($fh, "-- Đào tạo auto-backup ".date('Y-m-d H:i:s')." (tag: $tag)\nSET NAMES utf8mb4;\n");
	foreach($tables as $t)
	{
		$real = 'table_'.$t;
		$rows = $d->rawQuery("select * from `$real`");
		if(!is_array($rows) || $d->getLastErrorCode() !== '00000') { fclose($fh); @unlink($file); return ''; }
		if(fwrite($fh, "\n-- $real (".count($rows)." dòng)\n") === false) { fclose($fh); @unlink($file); return ''; }
		if(!$rows) continue;
		$cols = array_keys($rows[0]);
		$colList = '`'.implode('`,`', $cols).'`';
		foreach($rows as $row)
		{
			$vals = array();
			foreach($cols as $c)
			{
				$v = $row[$c];
				$vals[] = ($v === null) ? 'NULL' : $d->escape($v);
			}
			if(fwrite($fh, "INSERT INTO `$real` ($colList) VALUES (".implode(',', $vals).");\n") === false) { fclose($fh); @unlink($file); return ''; }
		}
	}
	if(!fclose($fh)) { @unlink($file); return ''; }
	dt_backup_prune($dir);
	return $file;
}

/* Giữ tối đa 15 file backup gần nhất, xóa file cũ hơn. */
function dt_backup_prune($dir, $keep = 15)
{
	$files = glob($dir.'/dt_*.sql');
	if(!$files || count($files) <= $keep) return;
	usort($files, function($a, $b){ return filemtime($b) - filemtime($a); });
	foreach(array_slice($files, $keep) as $old) @unlink($old);
}

/* ===== Migration versioning: chạy 1 lần mỗi key ===== */

function dt_migration_applied($key)
{
	global $d;
	$r = $d->rawQueryOne("select id from table_dt_migration where mkey = ? limit 0,1", array($key));
	return $r && $r['id'];
}

function dt_migration_mark($key)
{
	global $d;
	$d->insert('dt_migration', array('mkey' => (string)$key, 'ngaytao' => time()));
}

/* ===== Nhật ký thao tác (audit viewer) ===== */

/* Danh sách nhật ký, có lọc theo hành động/đối tượng/từ khóa. Trả về mảng dòng. */
function dt_audit_list($limit = 200)
{
	global $d;
	$where = array('1=1');
	$params = array();
	$act = isset($_GET['flt_action']) ? trim($_GET['flt_action']) : '';
	$ent = isset($_GET['flt_entity']) ? trim($_GET['flt_entity']) : '';
	$kw  = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
	if($act !== ''){ $where[] = 'action = ?'; $params[] = $act; }
	if($ent !== ''){ $where[] = 'entity = ?'; $params[] = $ent; }
	if($kw !== ''){ $where[] = '(detail like ? or user like ?)'; $params[] = '%'.$kw.'%'; $params[] = '%'.$kw.'%'; }
	$limit = max(20, min(1000, (int)$limit));
	$sql = "select * from table_dt_audit where ".implode(' and ', $where)." order by id desc limit 0,".$limit;
	$rows = $d->rawQuery($sql, $params);
	return is_array($rows) ? $rows : array();
}

/* Các giá trị hành động/đối tượng có trong log (để dựng dropdown lọc). */
function dt_audit_distinct($col)
{
	global $d;
	$col = ($col === 'entity') ? 'entity' : 'action';
	$rows = $d->rawQuery("select distinct $col as v from table_dt_audit where $col <> '' order by $col");
	$out = array();
	if(is_array($rows)) foreach($rows as $r) $out[] = $r['v'];
	return $out;
}

/* ===== Quản lý sao lưu (backup manager) ===== */

function dt_backup_dir()
{
	return dirname(dirname(dirname(__DIR__))).'/backups';
}

/* Liệt kê file sao lưu dt_*.sql (mới nhất trước) kèm dung lượng & thời gian. */
function dt_backup_files()
{
	$dir = dt_backup_dir();
	$files = glob($dir.'/dt_*.sql');
	if(!$files) return array();
	usort($files, function($a, $b){ return filemtime($b) - filemtime($a); });
	$out = array();
	foreach($files as $f)
	{
		$out[] = array(
			'name' => basename($f),
			'size' => filesize($f),
			'time' => filemtime($f),
		);
	}
	return $out;
}

/* Xác thực tên file backup do người dùng gửi lên (tránh path traversal). */
function dt_backup_safe_path($name)
{
	$name = basename((string)$name);
	if(!preg_match('/^dt_[a-z0-9_]+_\d{8}_\d{6}\.sql$/i', $name)) return '';
	$path = dt_backup_dir().'/'.$name;
	return is_file($path) ? $path : '';
}

/**
 * Khôi phục dữ liệu từ 1 file backup: sao lưu hiện trạng trước, TRUNCATE các bảng
 * xuất hiện trong file rồi nạp lại INSERT. Trả về [ok(bool), message].
 */
function dt_backup_restore($name)
{
	global $d;
	$path = dt_backup_safe_path($name);
	if($path === '') return array(false, 'File sao lưu không hợp lệ hoặc không tồn tại.');

	$lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	if($lines === false) return array(false, 'Không đọc được file sao lưu.');

	$allow = dt_backup_tables_list();
	$allowReal = array();
	foreach($allow as $t) $allowReal['table_'.$t] = true;

	$inserts = array();
	$tables = array();
	foreach($lines as $ln)
	{
		if(preg_match('/^-- (table_[a-z0-9_]+) \(\d+ dòng\)$/iu', $ln, $header))
		{
			if(!empty($allowReal[$header[1]])) $tables[$header[1]] = true;
			continue;
		}
		if(strncmp($ln, 'INSERT INTO', 11) !== 0) continue;
		if(!preg_match('/^INSERT INTO `([a-z0-9_]+)`/i', $ln, $m)) continue;
		$tbl = $m[1];
		if(empty($allowReal[$tbl])) return array(false, 'File sao lưu chứa bảng không hợp lệ.');
		$tables[$tbl] = true;
		$inserts[] = $ln;
	}
	if(empty($tables)) return array(false, 'File sao lưu không chứa bảng hợp lệ.');

	$snapshotTables = array();
	foreach(array_keys($tables) as $tbl) $snapshotTables[] = substr($tbl, 6);
	if(dt_backup_tables($snapshotTables, 'pre_restore') === '')
		return array(false, 'Không sao lưu được dữ liệu hiện tại; đã hủy khôi phục.');

	try
	{
		$d->startTransaction();
		foreach(array_keys($tables) as $tbl)
		{
			$d->rawQuery("DELETE FROM `$tbl`");
			if($d->getLastErrorCode() !== '00000') throw new RuntimeException('Lỗi xóa dữ liệu bảng '.$tbl);
		}
		foreach($inserts as $sql)
		{
			$d->rawQuery($sql);
			if($d->getLastErrorCode() !== '00000') throw new RuntimeException('Lỗi nạp bản sao lưu.');
		}
		if(!$d->commit()) throw new RuntimeException('Không thể lưu giao dịch khôi phục.');
	}
	catch(Throwable $e)
	{
		try { $d->rollback(); } catch(Throwable $ignored) {}
		return array(false, $e->getMessage().' Dữ liệu hiện tại được giữ nguyên.');
	}

	$n = count($inserts);
	dt_audit('restore', 'backup', $n, 'Khôi phục từ '.basename($path).' ('.count($tables).' bảng, '.$n.' dòng)');
	return array(true, 'Đã khôi phục '.$n.' dòng từ '.count($tables).' bảng. (Đã tự sao lưu hiện trạng trước khi khôi phục.)');
}

/* ===== Số liệu tổng quan cho bảng điều khiển đào tạo ===== */

function dt_dashboard_stats()
{
	global $d;
	$one = function($sql) use ($d){ $r = $d->rawQueryValue($sql); if(is_array($r)) $r = reset($r); return (int)$r; };
	$stats = array(
		'khoa' => $one("select count(*) from table_dt_khoa"),
		'hocvien' => $one("select count(*) from table_dt_hocvien"),
		'giaovien' => $one("select count(*) from table_dt_giaovien"),
		'xe' => $one("select count(*) from table_dt_xe"),
	);

	// Khóa sắp mãn hạn trong 30 ngày tới (còn hiệu lực).
	$soon = $d->rawQuery("select id, ma_khoa, ten_khoa, hang, ngay_manhoa from table_dt_khoa
		where ngay_manhoa is not null and ngay_manhoa >= curdate() and ngay_manhoa <= date_add(curdate(), interval 30 day)
		order by ngay_manhoa asc limit 0,8");
	$stats['khoa_sap_manhoa'] = is_array($soon) ? $soon : array();

	// Nhật ký gần đây.
	$recent = $d->rawQuery("select action, entity, affected, user, ngaytao from table_dt_audit order by id desc limit 0,8");
	$stats['audit_recent'] = is_array($recent) ? $recent : array();

	return $stats;
}

/* ===== Handlers cho route (được gọi từ daotao.php) ===== */

function dt_dashboard_page()
{
	global $dt_stats;
	$dt_stats = dt_dashboard_stats();
}

function dt_audit_page()
{
	global $dt_audit_rows, $dt_audit_actions, $dt_audit_entities;
	$dt_audit_rows = dt_audit_list(300);
	$dt_audit_actions = dt_audit_distinct('action');
	$dt_audit_entities = dt_audit_distinct('entity');
}

function dt_backup_page()
{
	global $dt_backup_rows;
	$dt_backup_rows = dt_backup_files();
}

/* Khôi phục 1 backup rồi quay lại danh sách kèm thông báo. */
function dt_backup_restore_action()
{
	$name = isset($_POST['file']) ? $_POST['file'] : (isset($_GET['file']) ? $_GET['file'] : '');
	list($ok, $msg) = dt_backup_restore($name);
	dt_import_notice($msg, 'index.php?com=daotao&act=backup', $ok);
}

/* Tải file backup về máy (chỉ admin). */
function dt_backup_download_action()
{
	$name = isset($_GET['file']) ? $_GET['file'] : '';
	$path = dt_backup_safe_path($name);
	if($path === '')
	{
		dt_import_notice('File sao lưu không hợp lệ.', 'index.php?com=daotao&act=backup', false);
		return;
	}
	if(!headers_sent())
	{
		header('Content-Type: application/sql; charset=utf-8');
		header('Content-Disposition: attachment; filename="'.basename($path).'"');
		header('Content-Length: '.filesize($path));
	}
	readfile($path);
	exit;
}
