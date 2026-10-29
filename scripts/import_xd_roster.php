<?php
if(PHP_SAPI !== 'cli' || !in_array('--local-db', $argv, true))
	exit("Usage: php scripts/import_xd_roster.php --local-db [--apply] [--course=XD-course] [--group=BT] file.xls\n");
define('LIBRARIES', dirname(__DIR__).'/libraries/');
define('SOURCES', dirname(__DIR__).'/admin/sources/');
$_SERVER['SERVER_NAME'] = 'localhost';
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
foreach(array('helpers','classes','excel_cell','excel_stream','excel_convert','excel_open','schema','hocvien_financial_import') as $module)
	require SOURCES.'xangdau/'.$module.'.php';
class XdRosterCliFunctions
{
	public function transfer($message, $url, $success = true) { throw new RuntimeException($message); }
}
$func = new XdRosterCliFunctions();
$login_admin = 'xd_roster_cli';
$_SESSION[$login_admin]['username'] = 'local-roster-import';
$options = array(); $file = '';
foreach(array_slice($argv, 1) as $arg)
{
	if(strpos($arg, '--course=') === 0) $options['khoa'] = substr($arg, 9);
	elseif(strpos($arg, '--group=') === 0) $options['nhom'] = substr($arg, 8);
	elseif($arg !== '--local-db' && $arg !== '--apply')
	{
		if(strpos($arg, '--') === 0 || $file !== '') exit("Unknown or extra argument\n");
		$file = $arg;
	}
}
try
{
	if(!in_array(getenv('MYSQLHOST'), array('db','localhost','127.0.0.1'), true))
		throw new RuntimeException('CLI này chỉ chạy với MYSQLHOST local được chỉ định rõ.');
	if(!is_file($file)) throw new RuntimeException('Không tìm thấy file import.');
	$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
	if(!in_array($extension, array('xls','xlsx'), true)) throw new RuntimeException('Chỉ nhận XLS hoặc XLSX.');
	list($workbook, $sheet, $highestRow, $highestCol) = xd_open_upload_sheet(array('tmp_name'=>$file), $extension, '', array('hocvien'), true);
	list($header, $map) = xd_roster_header($sheet, $highestRow, $highestCol);
	if(!isset($map['ngay_nop_ho_so']) && !array_intersect_key($map, xd_student_financial_fields()))
		throw new RuntimeException('File không có cột tài chính nguồn hoặc Ngày nộp hồ sơ.');
	$rows = xd_roster_parse($sheet, $highestRow, $map, $header, $options);
	$plan = xd_roster_plan($rows);
	$summary = array('file'=>basename($file), 'mode'=>'preview', 'rows'=>count($rows), 'new'=>$plan['new'],
		'updated'=>$plan['updated'], 'group_changes'=>$plan['group_changes'],
		'missing_teachers'=>$plan['missing_teachers'], 'totals_vnd'=>$plan['totals']);
	if(in_array('--apply', $argv, true))
	{
		xd_ensure_student_financial_columns();
		$plan = xd_roster_save($rows);
		foreach($plan['rows'] as $row)
		{
			$variants = xd_cccd_variants($row['cccd']);
			$marks = implode(',', array_fill(0, count($variants), '?'));
			$stored = xd_roster_query("select * from #_xd_hocvien where cccd in ($marks)", $variants);
			if(count($stored) !== 1) throw new RuntimeException('Không xác minh được hồ sơ sau import; xem bản sao lưu.');
			if($stored[0]['nhom'] !== $row['nhom']) throw new RuntimeException('Nhóm lưu không khớp bản xem trước; xem bản sao lưu.');
			foreach($row['financial'] as $key => $amount)
				if((int)$stored[0][$key] !== $amount) throw new RuntimeException('Số tiền lưu không khớp file; xem bản sao lưu.');
			if(!empty($row['ngay_nop_ho_so']) && $stored[0]['ngay_nop_ho_so'] !== $row['ngay_nop_ho_so'])
				throw new RuntimeException('Ngày nộp hồ sơ lưu không khớp file; xem bản sao lưu.');
			if(empty($row['ngay_nop_ho_so']) && $row['existing'] &&
				$stored[0]['ngay_nop_ho_so'] !== $row['existing']['ngay_nop_ho_so'])
				throw new RuntimeException('Ngày nộp hồ sơ cũ thay đổi; xem bản sao lưu.');
			if($row['existing'])
			{
				foreach(array('id','cccd','khoa','user_tao','lan_import','thu_tu_file','dinh_muc','dinh_muc_ca_nhan',
					'da_dieu_chinh_tt','so_tien_thanh_toan','ngay_thanh_toan','id_bangke','quan_ly_duyet','ke_toan_kiem_tra') as $key)
					if($stored[0][$key] !== $row['existing'][$key]) throw new RuntimeException('Lịch sử hồ sơ thay đổi: '.$key.'; xem bản sao lưu.');
			}
		}
		$summary['mode'] = 'imported-and-verified';
		$summary['backup'] = $plan['backup'];
		$summary['payment_history_preserved'] = true;
	}
	echo json_encode($summary, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n";
}
catch(Throwable $error) { fwrite(STDERR, $error->getMessage()."\n"); exit(1); }
