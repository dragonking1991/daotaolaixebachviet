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
		if(empty($rows)) continue;
		fwrite($fh, "\n-- $real (".count($rows)." dòng)\n");
		$cols = array_keys($rows[0]);
		$colList = '`'.implode('`,`', $cols).'`';
		foreach($rows as $row)
		{
			$vals = array();
			foreach($cols as $c)
			{
				$v = $row[$c];
				$vals[] = ($v === null) ? 'NULL' : "'".$d->escape($v)."'";
			}
			fwrite($fh, "INSERT INTO `$real` ($colList) VALUES (".implode(',', $vals).");\n");
		}
	}
	fclose($fh);
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
