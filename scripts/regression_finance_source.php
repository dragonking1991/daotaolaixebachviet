<?php
if(PHP_SAPI !== 'cli' || ($argv[1] ?? '') !== '--local-db')
	exit("Usage: php scripts/regression_finance_source.php --local-db\n");
define('LIBRARIES', dirname(__DIR__).'/libraries/');
define('SOURCES', dirname(__DIR__).'/admin/sources/');
$_SERVER['SERVER_NAME'] = 'localhost';
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
require SOURCES.'daotao/taichinh.php';
function dt_req($key, $default = '') { return $_REQUEST[$key] ?? $default; }
function dt_khoa_options() { return array(); }
function dt_can($section, $action = 'view') { return $action === 'export'; }
function dt_export_link($action, $keys) { return 'index.php?com=daotao&act='.$action; }
function dt_xlsx_simple($title, $filename, $headers, $rows, $textCols = array())
{
	$GLOBALS['source_export'] = array('headers'=>$headers,'rows'=>$rows,'textCols'=>$textCols);
}
class FinanceSourceFunctions
{
	public function pagination($total, $perPage, $page, $url) { return htmlspecialchars($url).' page '.$page; }
}
function source_assert($condition, $message) { if(!$condition) throw new RuntimeException($message); }
$func = new FinanceSourceFunctions();
$failure = null;
$definition = xd_roster_query('show create table #_xd_hocvien');
$sql = preg_replace('/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $definition[0]['Create Table']);
xd_roster_query(preg_replace('/ AUTO_INCREMENT=\d+/', '', $sql));
$courseDefinition = xd_roster_query('show create table #_dt_khoa');
$courseSql = preg_replace('/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $courseDefinition[0]['Create Table']);
xd_roster_query(preg_replace('/ AUTO_INCREMENT=\d+/', '', $courseSql));
try
{
	xd_roster_query("insert into #_dt_khoa (ma_khoa,ten_khoa) values ('A','Khóa A'),('OTHER','A')");
	$columns = array_column(xd_roster_query('show columns from #_xd_hocvien'), 'Field');
	foreach(xd_student_financial_fields() as $key => $label)
		if(!in_array($key,$columns,true)) xd_roster_query("alter table #_xd_hocvien add `$key` decimal(18,2) not null default 0");
	for($i=1; $i<=32; $i++)
		xd_roster_query('insert into #_xd_hocvien (ho_ten,cccd,khoa,nhom,gv_hoten,hoc_phi,mien_giam,hoc_phi_da_dong,hoc_phi_con_thieu,tam_ung_nguon,thanh_toan_luong_nguon) values (?,?,?,?,?,?,?,?,?,?,?)',
			array('<Source> '.sprintf('%02d',$i),'99000000'.sprintf('%04d',$i),$i===32?'B':'A',
				array('BT','CK','DAT')[$i%3],'Teacher',17000000,1000,12000000,4999000,2000000,500000));
	$before = xd_roster_query('select * from #_xd_hocvien order by id');
	$historyBefore = xd_roster_query('select count(*) as total from #_dt_taichinh');
	$_REQUEST = array();
	dt_finance_list();
	source_assert($finance_source['total']===32 && count($finance_source['rows'])===30,'All-group source data or pagination missing');
	source_assert($finance_source_courses[0]['ten_khoa']==='Khóa A' && (int)$finance_source_courses[0]['total']===31,
		'Source course name missing or roster count inflated by multiple training course matches');
	source_assert(!$finance_students && !$finance_history,'Source display must not create transaction candidates');
	$_REQUEST = array('source_khoa'=>'A','p'=>2);
	dt_finance_list();
	source_assert($finance_source['total']===31 && count($finance_source['rows'])===1,'Source course filter/page did not preserve all data');
	source_assert(strpos($finance_source_paging,'source_khoa=A')!==false,'Source pagination lost course');
	ob_start(); require dirname(__DIR__).'/admin/templates/daotao/taichinh/items_tpl.php'; $html=ob_get_clean();
	foreach(xd_student_financial_fields() as $label)
		source_assert(strpos($html,$label)!==false,'Missing source column in training finance: '.$label);
	source_assert(strpos($html,'&lt;Source&gt;')!==false && strpos($html,'<Source>')===false,'Student name not escaped');
	source_assert(strpos($html,'17.000.000')!==false && strpos($html,'source_export')!==false,'Amounts/export missing');
	source_assert(strpos($html,'dt-bulk-item')===false,'Source rows must not be selectable for transactions');
	$_REQUEST = array('source_khoa'=>'A','source_export'=>'1','p'=>2);
	dt_finance_export();
	source_assert(count($source_export['rows'])===31 && count($source_export['headers'])===11,'Source export must contain every filtered row and all six amounts');
	source_assert($source_export['rows'][0][5]===17000000.0 && $source_export['rows'][0][10]===500000.0,'Source amounts changed in export');
	source_assert(in_array(1,$source_export['textCols'],true),'Export must keep CCCD text');
	$_REQUEST = array('source_khoa'=>'MISSING');
	dt_finance_list();
	source_assert($finance_source['total']===0 && !$finance_source['rows'],'Missing course must have an explicit empty result');
	ob_start(); require dirname(__DIR__).'/admin/templates/daotao/taichinh/items_tpl.php'; $empty=ob_get_clean();
	source_assert(strpos($empty,'Chưa có dữ liệu tiền nguồn')!==false,'Missing source empty-state message');
	source_assert(xd_roster_query('select * from #_xd_hocvien order by id')===$before,'Display/export changed source data');
	source_assert(xd_roster_query('select count(*) as total from #_dt_taichinh')===$historyBefore,'Display/export created transactions');
	echo "PASS finance source display/export, all BT/CK/DAT groups, unmatched XD students, six exact amounts, course filtering, pagination, escaping and no writes\n";
}
catch(Throwable $error) { $failure=$error; }
finally
{
	$d->rawQuery('drop temporary table if exists table_xd_hocvien');
	$d->rawQuery('drop temporary table if exists table_dt_khoa');
}
if($failure) { fwrite(STDERR,$failure->getMessage()."\n"); exit(1); }
