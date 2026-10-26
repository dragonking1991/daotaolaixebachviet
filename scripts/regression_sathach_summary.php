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
require SOURCES.'daotao/excel_open.php';
require SOURCES.'daotao/sathach.php';

function dt_backup_tables($tables, $tag) { return $GLOBALS['summaryBackupOk'] ? 'temporary-tables-test' : ''; }
function dt_audit($action, $entity, $affected, $detail) {}
function dt_log_import($module, $filename, $count, $errors) {}
class SummaryTestNotice extends RuntimeException
{
    public $success;
    public function __construct($message, $success) { parent::__construct($message); $this->success=$success; }
}
function dt_import_notice($message, $back, $success=true) { throw new SummaryTestNotice($message, $success); }
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
foreach(array('dt_sh_ky','dt_sat_hach') as $table)
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
    summary_check(count($parsed['rows'])===6114 && count($parsed['groups'])===32,'Real workbook must contain 6114 rows and 32 periods');
    $notice=summary_import_test($path);
    $counts=dt_sathach_summary_query('select count(*) as num,count(distinct id_ky) as periods,min(ngay_sh) as first_date,max(ngay_sh) as last_date from #_dt_sat_hach');
    summary_check((int)$counts[0]['num']===6114 && (int)$counts[0]['periods']===32,'Incorrect real import totals');
    summary_check($counts[0]['first_date']==='2025-06-13' && $counts[0]['last_date']==='2026-10-16','Excel dates converted incorrectly');
    echo "PASS real workbook: 6114 records / 32 dated periods\n";
    dt_sathach_summary_query("update #_dt_sat_hach set diem_lt='9',ghi_chu='KEEP-MANUAL-NOTE' where id=1");
    summary_import_test($path);
    $counts=dt_sathach_summary_query('select count(*) as num from #_dt_sat_hach');
    $record=dt_sathach_summary_query('select diem_lt,ghi_chu from #_dt_sat_hach where id=1');
    summary_check((int)$counts[0]['num']===6114 && $record[0]['diem_lt']==='9' && $record[0]['ghi_chu']==='KEEP-MANUAL-NOTE','Reimport duplicated rows or cleared missing score/manual note');
    echo "PASS idempotent reimport and preservation of existing scores/notes\n";
    $row=array('Test Student','991024700001','01/01/1990','B','Đạt','Đạt','Đạt','Đạt','Đạt sát hạch','2098-11-20');
    $invalid=$row; $invalid[1]='991024700002'; $invalid[9]='31/02/2026';
    $fixture=summary_fixture(array($row,$invalid)); $fixtures[]=$fixture;
    summary_import_test($fixture,false);
    $duplicate=$row; $duplicate[1]='099102470001';
    $shortCccd=$duplicate; $shortCccd[1]=substr($duplicate[1],1);
    $fixture=summary_fixture(array($duplicate,$shortCccd)); $fixtures[]=$fixture;
    summary_import_test($fixture,false);
    $counts=dt_sathach_summary_query('select count(*) as num from #_dt_sat_hach');
    summary_check((int)$counts[0]['num']===6114,'Invalid file partially imported');
    echo "PASS invalid dates and duplicate canonical CCCD reject entire file\n";
    $fixture=summary_fixture(array($row)); $fixtures[]=$fixture;
    $summaryBackupOk=false; summary_import_test($fixture,false); $summaryBackupOk=true;
    dt_sathach_summary_query("insert into #_dt_sh_ky (ten,ngay,loai) values ('Manual 1','2098-11-20','oto'),('Manual 2','2098-11-20','oto')");
    summary_import_test($fixture,false);
    $counts=dt_sathach_summary_query('select count(*) as num from #_dt_sat_hach');
    summary_check((int)$counts[0]['num']===6114,'Ambiguous period import modified results');
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
    $free=dt_sathach_summary_query("select is_free_lock('bachviet_sh_summary_import') as free_lock");
    summary_check((int)$free[0]['free_lock']===1,'Import lock was not released');
    echo "PASS independent attempts, mixed vehicle types, CSRF and lock release\n";
    echo 'Peak memory '.round(memory_get_peak_usage(true)/1048576,1)." MB\n";
}
finally
{
    foreach($fixtures as $fixture) { if(is_file($fixture)) unlink($fixture); $temporary=substr($fixture,0,-5); if(is_file($temporary)) unlink($temporary); }
    $d->rawQuery('drop temporary table if exists table_dt_sat_hach');
    $d->rawQuery('drop temporary table if exists table_dt_sh_ky');
    echo "Temporary tables removed; permanent exam data unchanged\n";
}