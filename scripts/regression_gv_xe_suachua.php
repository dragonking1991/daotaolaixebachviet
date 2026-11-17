<?php
if(PHP_SAPI !== 'cli')
{
	http_response_code(403);
	exit('CLI only');
}

define('ROOT', dirname(__DIR__).'/libraries');
define('LIBRARIES', ROOT.'/');
define('SOURCES', dirname(__DIR__).'/sources/');

class PortalRepairDatabase
{
	public $mode;
	public $queries = array();
	public $writes = array();
	public $repair = array('id' => 31, 'id_xe' => 11, 'trang_thai' => 'dang_sua');
	public $commits = 0;
	public $rollbacks = 0;

	public function __construct($mode) { $this->mode = $mode; }
	public function getLastErrorCode() { return '00000'; }
	public function startTransaction() {}
	public function transactionFailed() { return false; }
	public function commit() { $this->commits++; return true; }
	public function rollback() { $this->rollbacks++; }

	public function rawQuery($sql, $params = array())
	{
		$this->queries[] = array($sql, $params);
		if(stripos($sql, 'update #_dt_xe_suachua set trang_thai') === 0)
		{
			$this->writes[] = array($sql, $params);
			$this->repair['trang_thai'] = $params[0];
		}
		return array();
	}

	public function rawQueryOne($sql, $params = array())
	{
		$this->queries[] = array($sql, $params);
		if(strpos($sql, 'information_schema.columns') !== false) return array('column_name' => 'anh_minh_chung');
		if(strpos($sql, 'select sval from #_dt_setting') === 0) return false;
		if(strpos($sql, 'select id, bien_so from #_dt_xe where id = ? and gv_key = ?') === 0)
		{
			if($this->mode === 'unassigned' || (int)$params[0] !== 11 || ($params[1] ?? '') !== 'teacher-a') return false;
			return array('id' => 11, 'bien_so' => '51A-TEST');
		}
		if(strpos($sql, 'select id, bien_so, nhan_hieu, hang_xe, loai_xe from #_dt_xe where id = ? and gv_key = ?') === 0)
		{
			if((int)$params[0] !== 11 || ($params[1] ?? '') !== 'teacher-a') return false;
			return array('id' => 11, 'bien_so' => '51A-TEST', 'nhan_hieu' => 'Toyota', 'hang_xe' => 'B1', 'loai_xe' => 'Tập lái');
		}
		if(strpos($sql, 'select id, trang_thai from #_dt_xe_suachua where id = ? and id_xe = ?') === 0)
		{
			if($this->mode === 'mismatch' || (int)$params[0] !== 31 || (int)$params[1] !== 11) return false;
			return $this->repair;
		}
		if(strpos($sql, 'select count(*) as tong') === 0)
			return array('tong' => 1, 'dang_sua' => 1, 'chi_phi' => 0, 'chua_chi_phi' => 1);
		return false;
	}

	public function rawQueryOneNotUsed() {}
	public function insert($table, $data)
	{
		$this->queries[] = array('insert '.$table, $data);
		if($table === 'dt_xe_suachua') $this->writes[] = array('insert '.$table, $data);
		return 1;
	}
}

class PortalRepairPageDependency
{
	public function setSeo($key, $value) {}
	public function getPageURL() { return 'cong-giao-vien'; }
	public function setBreadCrumbs($com, $title) {}
	public function getBreadCrumbs() { return array(); }
}

function portal_repair_check($condition, $message)
{
	if(!$condition) throw new RuntimeException($message);
}

$mode = $argv[1] ?? 'add';
if(!in_array($mode, array('add', 'status', 'missing-csrf', 'default-pass', 'unassigned', 'mismatch', 'delete-attempt'), true))
	throw new RuntimeException('Unknown regression mode.');

$d = new PortalRepairDatabase($mode);
$seo = new PortalRepairPageDependency();
$breadcr = new PortalRepairPageDependency();
$func = new PortalRepairPageDependency();
$com = 'cong-giao-vien';
$title_crumb = '';
$_SESSION = array(
	'dt_gv' => array('id' => 7, 'cccd' => '123456789012', 'hoten' => 'Giáo viên thử', 'gv_key' => 'teacher-a', 'must_change' => $mode === 'default-pass' ? 1 : 0),
	'dt_gv_csrf' => 'portal-token',
);
$_GET = array();
$_POST = array('dt_csrf' => $mode === 'missing-csrf' ? 'wrong-token' : 'portal-token', 'tab' => 'xe', 'id_xe' => 11);
if($mode === 'add')
{
	$_POST += array('dt_act' => 'addXeSuachua', 'ngay_sua' => '2026-11-17', 'noi_dung' => 'Kiểm tra tình trạng phanh');
}
elseif(in_array($mode, array('status', 'mismatch'), true))
{
	$_POST += array('dt_act' => 'updateXeSuachuaStatus', 'id_suachua' => 31, 'trang_thai' => 'hoan_tat');
}
elseif($mode === 'delete-attempt')
	$_POST += array('dt_act' => 'deleteXeSuachua', 'id_suachua' => 31);
else
{
	$_POST += array('dt_act' => 'addXeSuachua', 'ngay_sua' => '2026-11-17', 'noi_dung' => 'Kiểm tra tình trạng phanh');
}
$_REQUEST = $_POST;
$_FILES = array();
$_SERVER['REQUEST_METHOD'] = 'POST';

require SOURCES.'cong_giaovien.php';

if($mode === 'add')
{
	portal_repair_check($dtGvErr === '' && strpos($dtGvMsg, 'Đã thêm nhật ký') === 0, 'Valid assigned-vehicle repair entry should succeed.');
	portal_repair_check(count($d->writes) === 1 && $d->writes[0][1]['trang_thai'] === 'dang_sua', 'Teacher-created repair must start in progress.');
	portal_repair_check($d->writes[0][1]['anh_minh_chung'] === null, 'Photo attachment is optional.');
}
elseif($mode === 'status')
{
	portal_repair_check($dtGvErr === '' && strpos($dtGvMsg, 'Đã cập nhật trạng thái') === 0, 'Assigned teacher should update the repair status.');
	portal_repair_check(count($d->writes) === 1 && $d->repair['trang_thai'] === 'hoan_tat', 'Status update should persist.');
	portal_repair_check(strpos($d->writes[0][0], 'set trang_thai = ?, nguoi_capnhat = ?, ngaycapnhat = CURRENT_TIMESTAMP') !== false, 'Teacher update must write status and update metadata only.');
}
elseif($mode === 'missing-csrf')
	portal_repair_check($dtGvErr !== '' && !$d->writes && !$d->commits, 'Missing CSRF must block repair mutation.');
elseif($mode === 'default-pass')
	portal_repair_check($dtGvErr !== '' && !$d->writes && !$d->commits, 'Default password must block repair mutation.');
elseif($mode === 'unassigned')
	portal_repair_check(strpos($dtGvErr, 'không còn được phân công') !== false && !$d->writes && !$d->commits, 'Reassigned vehicle must reject repair entry.');
elseif($mode === 'mismatch')
	portal_repair_check(strpos($dtGvErr, 'Không tìm thấy nhật ký') !== false && !$d->writes && !$d->commits, 'Repair status update must reject a record outside the assigned vehicle.');
elseif($mode === 'delete-attempt')
	portal_repair_check(strpos($dtGvErr, 'không được phép xóa') !== false && !$d->writes && !$d->commits, 'Teacher delete attempt must be rejected without a write.');

echo 'PASS: teacher vehicle repair '.$mode."\n";
