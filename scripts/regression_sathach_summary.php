<?php
if(PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
if(($argv[1] ?? '') !== '--local-db') exit("Usage: php scripts/regression_sathach_summary.php --local-db /path/to/summary.xlsx\n");
define('LIBRARIES', dirname(__DIR__).'/libraries/');
define('SOURCES', dirname(__DIR__).'/admin/sources/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
require SOURCES.'daotao/helpers.php';
require SOURCES.'daotao/config_daotao.php';
require SOURCES.'daotao/excel_open.php';
require SOURCES.'daotao/sathach.php';
require SOURCES.'daotao/khoa.php';
require SOURCES.'daotao/export.php';

function dt_backup_tables($tables, $tag) { return $GLOBALS['summaryBackupOk'] ? 'temporary-tables-test' : ''; }
function dt_audit($action, $entity, $affected, $detail) {}
function dt_log_import($module, $filename, $count, $errors) {}
class SummaryTestNotice extends RuntimeException
{
    public $success;
    public function __construct($message, $success) { parent::__construct($message); $this->success=$success; }
}
function dt_import_notice($message, $back, $success=true) { throw new SummaryTestNotice($message, $success); }
class SummaryTestFunctions
{
    public function transfer($message,$url,$success=true){throw new SummaryTestNotice($message,$success);}
}
$func=new SummaryTestFunctions();
function summary_check($condition, $message) { if(!$condition) throw new RuntimeException($message); }
function summary_import_test($path, $success=true)
{
    $_POST=array('import_mode'=>'summary','csrf'=>'summary-test');
    $_REQUEST=array();
    $_FILES=array('file-excel'=>array('name'=>basename($path),'tmp_name'=>$path,'error'=>0));
    try { dt_sathach_upload_excel(); throw new RuntimeException('Missing import outcome'); }
    catch(SummaryTestNotice $notice) { summary_check($notice->success===$success, $notice->getMessage()); return $notice->getMessage(); }
}
function summary_fixture($rows)
{
    $path=tempnam(sys_get_temp_dir(),'sh-summary-').'.xlsx';
    $book=new PHPExcel(); $sheet=$book->getActiveSheet(); $sheet->setTitle('FILE TỔNG');
    $headers=array('HỌ VÀ TÊN','Số CCCD/CC','NGÀY SINH','HẠNG','LT','MP','TH','Đ','Đậu/ Rớt','Ngày SH');
    foreach($headers as $column=>$value) $sheet->setCellValueByColumnAndRow($column,3,$value);
    foreach($rows as $offset=>$row) foreach($row as $column=>$value) $sheet->setCellValueExplicitByColumnAndRow($column,$offset+4,(string)$value,PHPExcel_Cell_DataType::TYPE_STRING);
    PHPExcel_IOFactory::createWriter($book,'Excel2007')->save($path);
    $book->disconnectWorksheets();
    return $path;
}
set_exception_handler(function($error) { fwrite(STDERR,$error->getMessage()."\n".$error->getTraceAsString()."\n"); exit(1); });
$login_admin='summary_test';
$tag='SH-SUMMARY-'.bin2hex(random_bytes(4));
$_SESSION=array($login_admin=>array('username'=>$tag),'dt_sh_csrf'=>'summary-test');
$summaryBackupOk=true; $fixtures=array();
foreach(array('dt_sh_ky','dt_sat_hach','dt_hocvien','dt_khoa','dt_sh_import_row','dt_taichinh') as $table)
{
    $definition=dt_sathach_summary_query('show create table #_'.$table);
    $sql=preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$definition[0]['Create Table']);
    $sql=preg_replace('/ AUTO_INCREMENT=\d+/','',$sql);
    dt_sathach_summary_query($sql);
}
$path=$argv[2] ?? '/tmp/bachviet-bc1-summary.xlsx';
try
{
    $parsed=dt_sathach_summary_read(array('name'=>'summary.xlsx','tmp_name'=>$path));
    $isAa=strpos(basename($path),'aa')!==false || strpos(basename($path),'AA')!==false;
    $expected=$isAa?12532:6114;$expectedPeriods=$isAa?22:32;
    summary_check(count($parsed['rows'])===$expected && count($parsed['groups'])===$expectedPeriods,'Real workbook totals mismatch');
    $identity=$parsed['rows'][0]['data']['cccd'];
    dt_sathach_summary_query("insert into #_dt_khoa (ma_khoa,ten_khoa,hang) values ('EXISTING-COURSE','Existing course','B1')");
    $existingCourse=(int)$d->getLastInsertId();
    dt_sathach_summary_query('insert into #_dt_hocvien (id_khoa,ma_hv,cccd,hoten,gv_hoten) values (?,?,?,?,?)',array($existingCourse,'OFFICIAL-MA',$identity,'Official roster name','Official teacher'));
    $trainingBefore=array('students'=>dt_sathach_summary_query('select count(*) as num from #_dt_hocvien'),'courses'=>dt_sathach_summary_query('select count(*) as num from #_dt_khoa'));
    $originalTables=array();foreach(array('dt_lythuyet','dt_dat_phien','dt_cabin_kq','dt_thuchanh_hinh','dt_taichinh','xd_hocvien') as $table)$originalTables[$table]=dt_sathach_summary_query('select count(*) as num from #_'.$table);
    $notice=summary_import_test($path);
    $counts=dt_sathach_summary_query('select count(*) as num,count(distinct id_ky) as periods,min(ngay_sh) as first_date,max(ngay_sh) as last_date from #_dt_sat_hach');
    summary_check((int)$counts[0]['num']===$expected && (int)$counts[0]['periods']===$expectedPeriods,'Incorrect real import totals');
    if(!$isAa)summary_check($counts[0]['first_date']==='2025-06-13' && $counts[0]['last_date']==='2026-10-16','Excel dates converted incorrectly');
    $profilesBefore=dt_sathach_summary_query('select count(*) as num from #_dt_hocvien');
    $profile=dt_sathach_summary_query('select * from #_dt_hocvien where cccd=?',array($identity));
    summary_check(count($profile)===1 && $profile[0]['hoten']==='Official roster name' && $profile[0]['ma_hv']==='OFFICIAL-MA','Existing roster must take priority');
	$firstExam=dt_sathach_summary_query('select * from #_dt_sat_hach where cccd=? limit 1',array($identity));
	summary_check(count($firstExam)===1 && (int)$firstExam[0]['id_khoa']===0 && $firstExam[0]['ma_hv']==='' && $firstExam[0]['giaovien']==='','Exam import must not link to or copy from a training profile');
	summary_check($trainingBefore['students']===dt_sathach_summary_query('select count(*) as num from #_dt_hocvien') && $trainingBefore['courses']===dt_sathach_summary_query('select count(*) as num from #_dt_khoa'),'Exam import changed training profiles or courses');
    if($isAa)
    {
        $aaSource=dt_sathach_summary_query('select * from #_dt_sh_import_row where filename=? and so_dong=4',array(basename($path)));
        summary_check(count($aaSource)===1 && $aaSource[0]['trang_thai']==='pending' && (int)$aaSource[0]['id_hocvien']===0 && (int)$aaSource[0]['id_ketqua']===0,'Unresolved AA row must remain only in the exam-source queue');
    }
    echo 'PASS real workbook: '.$expected.' records / '.$expectedPeriods.' dated periods, training data unchanged' ."\n";
    dt_sathach_summary_query("update #_dt_sat_hach set diem_lt='9',ghi_chu='KEEP-MANUAL-NOTE' where id=1");
    summary_import_test($path);
    $counts=dt_sathach_summary_query('select count(*) as num from #_dt_sat_hach');
    $record=dt_sathach_summary_query('select diem_lt,ghi_chu from #_dt_sat_hach where id=1');
    summary_check((int)$counts[0]['num']===$expected && $record[0]['diem_lt']==='9' && $record[0]['ghi_chu']==='KEEP-MANUAL-NOTE','Reimport duplicated rows or cleared missing score/manual note');
    summary_check($profilesBefore===dt_sathach_summary_query('select count(*) as num from #_dt_hocvien'),'Reimport duplicated roster profiles');
    summary_check($trainingBefore['courses']===dt_sathach_summary_query('select count(*) as num from #_dt_khoa'),'Reimport created a training course');
    echo "PASS idempotent reimport and preservation of existing scores/notes\n";
    $row=array('Test Student','991024700001','01/01/1990','B','Đạt','Đạt','Đạt','Đạt','Đạt sát hạch','2098-11-20');
    $invalid=$row; $invalid[1]='991024700002'; $invalid[9]='31/02/2026';
    $fixture=summary_fixture(array($row,$invalid)); $fixtures[]=$fixture;
    $partialFixture=$fixture;
    summary_import_test($fixture);
    $expected++;
    $duplicate=$row; $duplicate[1]='099102470001';
    $shortCccd=$duplicate; $shortCccd[1]=substr($duplicate[1],1);
    $fixture=summary_fixture(array($duplicate,$shortCccd)); $fixtures[]=$fixture;
    summary_import_test($fixture);
    $expected++;
    $counts=dt_sathach_summary_query('select count(*) as num from #_dt_sat_hach');
    summary_check((int)$counts[0]['num']===$expected,'Valid rows were lost or duplicates were inserted');
    $pending=dt_sathach_summary_query("select count(*) as num from #_dt_sh_import_row where trang_thai='pending'");
    summary_check((int)$pending[0]['num']>0,'Invalid rows must be retained for correction');
    echo "PASS valid rows imported, invalid rows retained, duplicate canonical CCCD merged\n";
    $fixture=summary_fixture(array($row)); $fixtures[]=$fixture;
    $summaryBackupOk=false; summary_import_test($fixture,false); $summaryBackupOk=true;
    dt_sathach_summary_query("insert into #_dt_sh_ky (ten,ngay,loai) values ('Manual 1','2098-11-20','oto'),('Manual 2','2098-11-20','oto')");
    summary_import_test($fixture,false);
    $counts=dt_sathach_summary_query('select count(*) as num from #_dt_sat_hach');
    summary_check((int)$counts[0]['num']===$expected,'Ambiguous period import modified results');
    dt_sathach_summary_query("delete r from #_dt_sat_hach r inner join #_dt_sh_ky k on k.id=r.id_ky where k.ngay='2098-11-20'");
    dt_sathach_summary_query("delete from #_dt_sh_ky where ngay='2098-11-20'");
    echo "PASS backup failure, ambiguous same-day periods and transaction rollback\n";
    $repeat=$row; $repeat[9]='2098-11-21';
    $moto=$row; $moto[1]='991024700003'; $moto[3]='A1';
    $fixture=summary_fixture(array($row,$repeat,$moto)); $fixtures[]=$fixture;
    summary_import_test($fixture);
    $attempts=dt_sathach_summary_query("select count(*) as num from #_dt_sat_hach where cccd='991024700001'");
    $motoRecord=dt_sathach_summary_query("select mp,duong from #_dt_sat_hach where cccd='991024700003'");
    summary_check((int)$attempts[0]['num']===2 && $motoRecord[0]['mp']==='' && $motoRecord[0]['duong']==='','Separate dates or motorcycle handling failed');
    $countBefore=dt_sathach_summary_query('select count(*) as num from #_dt_sat_hach');
    $_SESSION['dt_sh_csrf']='changed'; summary_import_test($fixture,false); $_SESSION['dt_sh_csrf']='summary-test';
    $countAfter=dt_sathach_summary_query('select count(*) as num from #_dt_sat_hach');
    summary_check($countBefore===$countAfter,'Invalid CSRF changed results');
    summary_check(dt_sathach_summary_date('1',true)==='1904-01-02','1904 calendar conversion failed');
    dt_sathach_summary_query("alter table #_dt_sat_hach add constraint chk_summary_test_name check (hoten <> 'REJECT-SUMMARY-ROW')");
    $writeFirst=$row; $writeFirst[9]='2098-12-01';
    $writeError=$row; $writeError[0]='REJECT-SUMMARY-ROW'; $writeError[1]='991024700009'; $writeError[9]='2098-12-02';
    $fixture=summary_fixture(array($writeFirst,$writeError)); $fixtures[]=$fixture;
    $periodCount=dt_sathach_summary_query('select count(*) as num from #_dt_sh_ky');
    summary_import_test($fixture,false);
    summary_check($countBefore===dt_sathach_summary_query('select count(*) as num from #_dt_sat_hach'),'SQL error left a partial result');
    summary_check($periodCount===dt_sathach_summary_query('select count(*) as num from #_dt_sh_ky'),'SQL error left a newly created period');
    echo "PASS actual SQL failure rolls back prior records and newly created periods\n";
    dt_sathach_summary_query("insert into #_dt_sh_ky (ten,ngay,loai) values ('Delete selected 1','2098-12-10','oto'),('Delete selected 2','2098-12-11','moto')");
    $deletePeriods=dt_sathach_summary_query("select id,ngay from #_dt_sh_ky where ngay in ('2098-12-10','2098-12-11') order by ngay");
    $deleteResultIds=array();$deleteSourceIds=array();
    foreach($deletePeriods as $index=>$period)
    {
        dt_sathach_summary_query('insert into #_dt_sat_hach (id_ky,id_khoa,cccd,hoten,ngay_sh) values (?,0,?,?,?)',array($period['id'],'99102470002'.($index+0),'Delete selected','2098-12-'.(10+$index)));
        $deleteResultIds[]=(int)$d->getLastInsertId();
        dt_sathach_summary_query('insert into #_dt_sh_import_row (file_hash,filename,so_dong,raw_json,data_json,trang_thai,id_hocvien,id_ketqua,nguoi_nhap,ngaytao,ngay_sua) values (?,?,?,?,?,?,?,?,?,?,?)',array(str_repeat('c',64),'delete-selected.xlsx',$index+1,'{}','{}','imported',0,$deleteResultIds[$index],'test',time(),time()));
        $deleteSourceIds[]=(int)$d->getLastInsertId();
    }
    $_POST=array('csrf'=>'summary-test','id_kys'=>array_column($deletePeriods,'id'));
    try{dt_sathach_delete_periods_selected();throw new RuntimeException('Missing selected-delete outcome');}catch(SummaryTestNotice $notice){summary_check($notice->success && strpos($notice->getMessage(),'Đã xóa 2 kỳ')!==false,$notice->getMessage());}
    summary_check(!dt_sathach_summary_query("select id from #_dt_sh_ky where ngay in ('2098-12-10','2098-12-11')"),'Selected periods were not removed');
    summary_check(!dt_sathach_summary_query('select id from #_dt_sat_hach where id in (?,?)',$deleteResultIds),'Selected period results were not removed');
    $sourceAfterDelete=dt_sathach_summary_query('select id,id_ketqua from #_dt_sh_import_row where id in (?,?)',$deleteSourceIds);
    summary_check(count($sourceAfterDelete)===2 && (int)$sourceAfterDelete[0]['id_ketqua']===0 && (int)$sourceAfterDelete[1]['id_ketqua']===0,'Selected deletion removed source rows or left stale result references');
    echo "PASS select-all period deletion removes selected results and retains import source rows\n";
    $free=dt_sathach_summary_query("select is_free_lock('bachviet_sh_summary_import') as free_lock");
    summary_check((int)$free[0]['free_lock']===1,'Import lock was not released');
    echo "PASS independent attempts, mixed vehicle types, CSRF and lock release\n";
    $entries=dt_sathach_summary_query("select * from #_dt_sh_import_row where trang_thai='pending' and data_json like ? limit 1",array('%991024700002%'));
    summary_check(count($entries)===1,'Missing reviewable source row');$entry=$entries[0];
    $corrected=json_decode($entry['data_json'],true);$corrected['ngay_sh']='2098-11-22';
    $_POST=array('id'=>$entry['id'],'csrf'=>'summary-test','data'=>$corrected);
    try{dt_sh_pending_save();throw new RuntimeException('Missing repair outcome');}catch(SummaryTestNotice $notice){summary_check($notice->success,$notice->getMessage());}
    $resolved=dt_sathach_summary_query('select * from #_dt_sh_import_row where id=?',array($entry['id']));
    summary_check($resolved[0]['trang_thai']==='resolved' && $resolved[0]['raw_json']===$entry['raw_json'],'Repair must preserve original source');
    summary_import_test($partialFixture);
    $again=dt_sathach_summary_query('select * from #_dt_sh_import_row where id=?',array($entry['id']));
    summary_check($again[0]['trang_thai']==='resolved' && $again[0]['data_json']===$resolved[0]['data_json'],'Reimport reset a reviewed correction');
    $resolvedResult=dt_sathach_summary_query('select * from #_dt_sat_hach where id=?',array($resolved[0]['id_ketqua']));
    $expectedFileLabel=pathinfo(basename($partialFixture),PATHINFO_FILENAME);
    summary_check(count($resolvedResult)===1 && (int)$resolvedResult[0]['id_khoa']===0 && $resolvedResult[0]['ma_hv']==='' && $resolvedResult[0]['ma_khoa']===$expectedFileLabel,'Repaired result must remain standalone with filename metadata only');
    summary_check((int)$resolved[0]['id_hocvien']===0,'Repaired source row must not link to the training roster');
    $standaloneAttempts=dt_sathach_summary_query('select id from #_dt_sat_hach where hoten=? and cccd=?',array('Test Student','991024700001'));
    summary_check(count($standaloneAttempts)===2,'Exam records must retain repeated attempts without a training-profile link');
    $sparseFixture=summary_fixture(array(0=>$row,21000=>$repeat));$fixtures[]=$sparseFixture;
    $sparse=dt_sathach_summary_read(array('name'=>'sparse.xlsx','tmp_name'=>$sparseFixture));
    summary_check(count($sparse['rows'])===2 && $sparse['rows'][1]['row']===21004,'Reader truncated real data beyond 20000 rows or a long empty gap');
    $item=array('id'=>1,'hang'=>'Am');$dt_crud_entity='hocvien';$dt_crud_config=array('back'=>'hocvien','title'=>'Student','fields'=>array('hang'=>'Rank'));
    ob_start();require dirname(__DIR__).'/admin/templates/daotao/crud_form_tpl.php';$rendered=ob_get_clean();
    summary_check(strpos($rendered,'value="Am" selected')!==false,'Profile editor would clear the imported motorcycle rank');
    echo "PASS pending correction and reviewed-source preservation without training links\n";
    echo "PASS standalone exam search, motorcycle rank retention and sparse data beyond row 20000\n";
    foreach($originalTables as $table=>$before)summary_check($before===dt_sathach_summary_query('select count(*) as num from #_'.$table),'Import created false training progress or financial data in '.$table);
    echo "PASS no fabricated LT/Cabin/Hinh/DAT/payment/XD records\n";
    echo 'Peak memory '.round(memory_get_peak_usage(true)/1048576,1)." MB\n";
}
finally
{
    foreach($fixtures as $fixture) { if(is_file($fixture)) unlink($fixture); $temporary=substr($fixture,0,-5); if(is_file($temporary)) unlink($temporary); }
    foreach(array('dt_sat_hach','dt_sh_ky','dt_hocvien','dt_khoa','dt_sh_import_row','dt_taichinh') as $table)$d->rawQuery('drop temporary table if exists table_'.$table);
    echo "Temporary tables removed; permanent exam data unchanged\n";
}