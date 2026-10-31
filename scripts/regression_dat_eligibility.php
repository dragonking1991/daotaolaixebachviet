<?php
if(PHP_SAPI !== 'cli' || ($argv[1] ?? '') !== '--local-db')
	exit("Usage: php scripts/regression_dat_eligibility.php --local-db\n");
define('LIBRARIES', dirname(__DIR__).'/libraries/');
define('SOURCES', dirname(__DIR__).'/admin/sources/');
$_SERVER['SERVER_NAME'] = 'localhost';
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
require SOURCES.'daotao/config_daotao.php';
require SOURCES.'daotao/helpers.php';
require SOURCES.'daotao/khoa.php';
require SOURCES.'daotao/dat_eligibility.php';
function dt_req($key, $default = '') { return isset($_REQUEST[$key]) && $_REQUEST[$key] !== '' ? trim((string)$_REQUEST[$key]) : $default; }
function dt_can($section, $action = 'view') { return $action === 'export'; }
function dt_export_link($action, $keys) { return 'index.php?com=daotao&act='.$action; }
function dt_xlsx_simple($title, $filename, $headers, $rows, $textCols = array())
{
	$GLOBALS['entry_export'] = array('headers'=>$headers, 'rows'=>$rows, 'textCols'=>$textCols);
}
function entry_check($condition, $message) { if(!$condition) throw new RuntimeException($message); }
function entry_query($sql, $params = array())
{
	global $d;
	$rows = $d->rawQuery($sql, $params);
	if($d->getLastErrorCode() !== '00000') throw new RuntimeException('Eligibility test query failed: '.json_encode($d->getLastError()));
	return $rows;
}
$boundary = array_fill_keys(array_keys(dt_dat_entry_subjects()), array('tien_do'=>70,'diem_kt'=>5,'dat'=>0));
entry_check(dt_dat_entry_evaluate($boundary)['eligible'], 'Exact 70% and score 5 must pass');
foreach(array_keys($boundary) as $key)
{
	foreach(array(array('tien_do',69.99),array('diem_kt',4.99),array('diem_kt',null),array('tien_do','')) as $invalid)
	{
		$results = $boundary;
		$results[$key][$invalid[0]] = $invalid[1];
		entry_check(!dt_dat_entry_evaluate($results)['eligible'], 'All four subjects need both numeric thresholds: '.$key);
	}
	$results = $boundary;
	unset($results[$key]);
	entry_check(dt_dat_entry_evaluate($results)['passed'] === 3, 'Missing subject must not pass');
}
foreach(array('ky_thuat'=>20,'phan_1'=>25,'phan_2'=>40,'phan_3'=>25) as $key=>$denominator)
	entry_check(dt_lythuyet_denom($key) === $denominator, 'Existing import denominator changed');
entry_check(dt_lythuyet_dat(70,0,'ky_thuat') === 1, 'Existing theory progress-only rule must remain unchanged');
foreach(array('dt_khoa','dt_hocvien','dt_lythuyet') as $table)
{
	$definition = entry_query('show create table #_'.$table)[0]['Create Table'];
	entry_query(preg_replace('/ AUTO_INCREMENT=\d+/', '', preg_replace('/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $definition)));
}
entry_query("insert into #_dt_khoa (id,ma_khoa,ten_khoa,hang,hienthi) values (1,'CODE-A','K13-C1','C1',1),(2,'CODE-B','Other','CE',1)");
foreach(array(1,2,3,4,5) as $id)
{
	entry_query('insert into #_dt_hocvien (id,id_khoa,ma_hv,cccd,hoten,hang,gv_hoten,gv_key) values (?,?,?,?,?,?,?,?)',
		array($id,$id===5?2:1,'HV-'.$id,'00000000000'.$id,$id===1?'<Student> An':'Student '.$id,'C1','Teacher',dt_gv_key('Teacher')));
	foreach($boundary as $key=>$result)
	{
		if($id===4 || ($id===3 && $key==='phan_3')) continue;
		$score = ($id===2 && $key==='ky_thuat') ? 4.99 : 5;
		entry_query('insert into #_dt_lythuyet (id_khoa,cccd,mon,tien_do,diem_kt,dat) values (?,?,?,?,?,?)',
			array($id===5?2:1,'00000000000'.$id,$key,70,$score,1));
	}
}
$before = array();
foreach(array('dt_hocvien','dt_lythuyet') as $table) $before[$table] = entry_query('select * from #_'.$table.' order by id');
$_REQUEST = array('id_khoa'=>1);
dt_dat_entry_list();
entry_check(count($dat_entry)===4 && $dat_entry_stats===array('eligible'=>1,'pending'=>3), 'List must include missing results and exclude other course');
$_REQUEST['trang_thai'] = 'dat';
dt_dat_entry_list();
entry_check(count($dat_entry)===1 && (int)$dat_entry[0]['student']['id']===1 &&
	$dat_entry_stats===array('eligible'=>1,'pending'=>3), 'Status filter must retain comparable stats');
dt_dat_entry_export();
entry_check(count($entry_export['rows'])===1 && count($entry_export['headers'])===21 &&
	count($entry_export['rows'][0])===21 && $entry_export['rows'][0][3]==='000000000001' &&
	in_array(3,$entry_export['textCols'],true), 'Export must match filters and preserve CCCD text');
$_REQUEST['trang_thai'] = 'chua';
dt_dat_entry_list();
entry_check(count($dat_entry)===3, 'Pending filter incorrect');
$_REQUEST = array('id_khoa'=>1,'gv'=>'Other teacher');
dt_dat_entry_list();
entry_check(!$dat_entry, 'Teacher filter ignored');
$_REQUEST = array('keyword'=>'HV-5');
dt_dat_entry_list();
entry_check(count($dat_entry)===1 && (int)$dat_entry[0]['student']['id']===5, 'Cross-course identity search failed');
entry_query("update #_dt_hocvien set cccd='000000000001' where id=5");
$_REQUEST = array('id_khoa'=>2);
dt_dat_entry_list();
entry_check(count($dat_entry)===1 && !$dat_entry[0]['evaluation']['eligible'], 'Theory from another course leaked across identical CCCD');
entry_query("update #_dt_hocvien set cccd='000000000005' where id=5");
$_REQUEST = array('id_khoa'=>1);
dt_dat_entry_list();
ob_start();
require dirname(__DIR__).'/admin/templates/daotao/dat_eligibility/items_tpl.php';
$html = ob_get_clean();
entry_check(strpos($html,'&lt;Student&gt;')!==false && strpos($html,'<Student>')===false, 'Student content must be escaped');
entry_check(strpos($html,'70%')!==false && strpos($html,'≥ 5')!==false &&
	strpos($html,'Đủ điều kiện học DAT')!==false, 'Eligibility requirements missing from UI');
entry_check(strpos($html,'modulePassSelected')===false && strpos($html,'syncTrainingDat')===false, 'Read-only page must not expose pass/sync actions');
foreach($before as $table=>$rows) entry_check(entry_query('select * from #_'.$table.' order by id')===$rows, 'Eligibility list/export modified '.$table);
$router = file_get_contents(SOURCES.'daotao.php');
entry_check(strpos($router,"case 'dieukienDat': dt_guard(dt_p('lythuyet','view'))")!==false &&
	strpos($router,"case 'exportDieukienDat': dt_guard(dt_p('lythuyet','export'))")!==false, 'Routes missing theory permission guards');
$menu = file_get_contents(dirname(__DIR__).'/admin/templates/layout/daotao_menu.php');
entry_check(strpos($menu,"'dieukienDat' => 'Đủ điều kiện học DAT'")!==false &&
	strpos($menu,"'dieukienDat' => 'lythuyet'")!==false, 'Menu must use same theory permission as route');
$_REQUEST = array();
dt_dat_entry_list();
entry_check(!$dat_entry && $dat_entry_stats===array('eligible'=>0,'pending'=>0), 'Initial page must request a course or identity search');
echo "PASS DAT entry: exact 70/5, four subjects, missing results, old theory unchanged, filters/stats, course isolation, escaped UI, export, permission wiring and no writes; temporary tables only\n";
