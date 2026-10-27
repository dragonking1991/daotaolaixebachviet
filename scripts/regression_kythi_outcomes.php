<?php
if(PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
if(($argv[1] ?? '') !== '--local-db' || empty($argv[2])) exit("Usage: php scripts/regression_kythi_outcomes.php --local-db /path/to/result.xls\n");
define('LIBRARIES', dirname(__DIR__).'/libraries/');
define('SOURCES', dirname(__DIR__).'/admin/sources/');
$_SERVER['SERVER_NAME']='localhost';
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d=new PDODb($config['database']);
require SOURCES.'daotao/config_daotao.php';
require SOURCES.'daotao/helpers.php';
require SOURCES.'daotao/excel_open.php';
require SOURCES.'daotao/export.php';
require SOURCES.'daotao/khoa.php';
require SOURCES.'daotao/dat.php';
require SOURCES.'daotao/kythi.php';
require SOURCES.'daotao/htkh.php';

function dt_backup_tables($tables,$tag){return 'temporary-table-regression';}
function dt_audit($action,$entity,$affected,$detail){}
function dt_log_import($module,$filename,$rows,$errors){}
class KythiOutcomeNotice extends RuntimeException
{
	public $success;
	public function __construct($message,$success){parent::__construct($message);$this->success=$success;}
}
class KythiOutcomeFunctions
{
	public function transfer($message,$url,$success=true){throw new KythiOutcomeNotice($message,$success);}
}
function dt_import_notice($message,$url,$success=true){throw new KythiOutcomeNotice($message,$success);}
function kythi_outcome_check($condition,$message){if(!$condition)throw new RuntimeException($message);}
set_exception_handler(function($error){fwrite(STDERR,$error->getMessage()."\n".$error->getTraceAsString()."\n");exit(1);});

$_SESSION=array('kythi_outcome_test'=>array('username'=>'kythi_outcome_test'));
$login_admin='kythi_outcome_test';$func=new KythiOutcomeFunctions();$temporaryTables=array('dt_ky_thi_hv','dt_ky_thi','dt_hocvien','dt_khoa','dt_htkh');
foreach($temporaryTables as $table)
{
	$definition=$d->rawQuery('show create table table_'.$table);
	$sql=preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$definition[0]['Create Table']);
	$sql=preg_replace('/ AUTO_INCREMENT=\d+/','',$sql);
	$d->rawQuery($sql);
}

try
{
	$file=$argv[2];if(!is_file($file))throw new RuntimeException('Workbook not found: '.$file);
	$d->rawQuery("insert into #_dt_ky_thi (ten,ngay_kt) values ('B/C1 result regression','2026-10-21')");
	$id=(int)$d->getLastInsertId();$_REQUEST=array('id'=>$id);$_POST=array();
	$_FILES=array('file-excel'=>array('name'=>basename($file),'tmp_name'=>$file,'error'=>UPLOAD_ERR_OK));
	try{dt_kythi_import_excel();throw new RuntimeException('Importer returned without reporting a result.');}
	catch(KythiOutcomeNotice $notice){kythi_outcome_check($notice->success,$notice->getMessage());}
	$counts=$d->rawQuery('select count(*) as total,sum(ket_luan=?) as passed,sum(ket_luan=?) as failed,sum(ket_luan=?) as blank from #_dt_ky_thi_hv where id_ky=?',array('Đạt','Không đạt','',$id));
	kythi_outcome_check((int)$counts[0]['total']===166,'Expected 166 result rows, got '.$counts[0]['total']);
	kythi_outcome_check((int)$counts[0]['passed']===134 && (int)$counts[0]['failed']===32 && (int)$counts[0]['blank']===0,'Cached “Kết luận CSDT” formulas were not imported correctly: '.json_encode($counts[0],JSON_UNESCAPED_UNICODE));
	$passed=dt_htkh_ky_passed($id);
	kythi_outcome_check(count($passed)===134,'HTKH pass filter should return 134 B/C1 students, got '.count($passed));
	$previous=null;
	foreach($passed as $row)
	{
		$course=trim((string)$row['ma_khoa']);if($course==='')$course=trim((string)$row['ten_khoa']);
		$parts=preg_split('/\s+/u',trim((string)$row['hoten']));
		$current=array('course'=>dt_strip_diacritics($course),'id'=>(int)$row['id_khoa'],'given'=>dt_strip_diacritics(end($parts)),'name'=>dt_strip_diacritics($row['hoten']),'cccd'=>(string)$row['cccd']);
		if($previous)
		{
			$courseCompare=strcmp($previous['course'],$current['course']);
			if($previous['course']===''&&$current['course']==='')$courseCompare=$previous['id']<=>$current['id'];
			$ordered=$courseCompare<0||($courseCompare===0&&($previous['given']<$current['given']||($previous['given']===$current['given']&&($previous['name']<$current['name']||($previous['name']===$current['name']&&$previous['cccd']<=$current['cccd'])))));
			kythi_outcome_check($ordered,'HTKH passed list is not ordered by course then name A-Z at '.$row['hoten']);
		}
		$previous=$current;
	}
	$document=dt_htkh_build_document('HTKH-TEST','Student Test','01/01/2000','B1','','Course','2026-01-01','2026-02-01','27/10/2026','Signer','gd','QD-2026-001');
	$decisionRow=false;foreach(dt_htkh_document_rows($document) as $documentRow)if(strpos((string)($documentRow['text']??''),'Số Quyết định: QD-2026-001')!==false)$decisionRow=true;
	kythi_outcome_check($decisionRow,'Generated HTKH document omitted the decision number');
	$_REQUEST=array('id_ky'=>$id,'ky_khoa'=>'');$_POST=array('id_ky'=>$id,'ky_khoa'=>'','nguoi_ky'=>'Regression Signer','ngay_ky'=>'27/10/2026','chuc_danh'=>'gd','hang_ce'=>'','so_quyet_dinh'=>'QD-2026-001');
	try{dt_htkh_cap_giay_ky();throw new RuntimeException('HTKH issue flow did not finish.');}catch(KythiOutcomeNotice $notice){kythi_outcome_check($notice->success,$notice->getMessage());}
	$savedDecision=$d->rawQuery('select count(*) as num from #_dt_htkh where so_quyet_dinh=?',array('QD-2026-001'));
	kythi_outcome_check((int)$savedDecision[0]['num']===134,'Decision number was not persisted for every passed student');
	$trainingProfiles=$d->rawQuery('select count(*) as num from #_dt_hocvien');
	kythi_outcome_check((int)$trainingProfiles[0]['num']===0,'Exam result import must not create training profiles');
	echo "PASS real B/C1 result import: 166 rows, 134 passed, 32 failed; HTKH returns 134; no training profile created\n";
}
finally
{
	foreach($temporaryTables as $table)$d->rawQuery('drop temporary table if exists table_'.$table);
	echo "Temporary tables removed; permanent exam/training data unchanged\n";
}