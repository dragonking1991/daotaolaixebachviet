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
function dt_can($section,$action='view'){return $action!=='edit'||($GLOBALS['scheduleTestEditable']??true);}
function kythi_outcome_check($condition,$message){if(!$condition)throw new RuntimeException($message);}
set_exception_handler(function($error){fwrite(STDERR,$error->getMessage()."\n".$error->getTraceAsString()."\n");exit(1);});

$_SESSION=array('kythi_outcome_test'=>array('username'=>'kythi_outcome_test'));
$login_admin='kythi_outcome_test';$func=new KythiOutcomeFunctions();$temporaryTables=array('dt_ky_thi_hv','dt_ky_thi','dt_hocvien','dt_khoa','dt_htkh','dt_setting');
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
	$isMakhoaWorkbook=basename($file)==='KQ_DaoTao_LT_MP_26102026-V1-Makhoa.xls';
	if($isMakhoaWorkbook)$d->rawQuery("insert into #_dt_ky_thi_hv (id_ky,cccd,hoten,co_ket_qua) values (?,?,'Existing exam student',0)",array($id,'046193000084'));
	if($isMakhoaWorkbook)
	{
		$d->rawQuery("insert into #_dt_khoa (ma_khoa,ten_khoa,hang) values ('SYS-COURSE','System Course','C1')");$systemCourseId=(int)$d->getLastInsertId();
		$d->rawQuery("insert into #_dt_hocvien (id_khoa,ma_hv,cccd,hoten,hang) values (?, 'SYS-STUDENT','079199006703','System Student','C1')",array($systemCourseId));
	}
	$_FILES=array('file-excel'=>array('name'=>basename($file),'tmp_name'=>$file,'error'=>UPLOAD_ERR_OK));
	try{dt_kythi_import_excel();throw new RuntimeException('Importer returned without reporting a result.');}
	catch(KythiOutcomeNotice $notice){kythi_outcome_check($notice->success,$notice->getMessage());}
	$counts=$d->rawQuery('select count(*) as total,sum(ket_luan=?) as passed,sum(ket_luan=?) as failed,sum(ket_luan=?) as blank from #_dt_ky_thi_hv where id_ky=?',array('Đạt','Không đạt','',$id));
	kythi_outcome_check((int)$counts[0]['total']===166,'Expected 166 result rows, got '.$counts[0]['total']);
	kythi_outcome_check((int)$counts[0]['passed']===134 && (int)$counts[0]['failed']===32 && (int)$counts[0]['blank']===0,'Cached “Kết luận CSDT” formulas were not imported correctly: '.json_encode($counts[0],JSON_UNESCAPED_UNICODE));
	if($isMakhoaWorkbook)
	{
		$identity=$d->rawQueryOne('select ma_hv,ma_khoa,ten_khoa from #_dt_ky_thi_hv where id_ky=? and cccd=?',array($id,'046193000084'));
		kythi_outcome_check($identity['ma_hv']==='79044-20260806-000022'&&$identity['ma_khoa']==='79044K261011'&&$identity['ten_khoa']==='122B(TĐ)','Import did not retain Mã học viên, Mã khóa học and Khóa hạng from the workbook on an existing row');
		$systemIdentity=$d->rawQueryOne('select ma_hv,ma_khoa,ten_khoa from #_dt_ky_thi_hv where id_ky=? and cccd=?',array($id,'079199006703'));
		kythi_outcome_check($systemIdentity['ma_hv']==='SYS-STUDENT'&&$systemIdentity['ma_khoa']==='SYS-COURSE'&&$systemIdentity['ten_khoa']==='System Course','Training profile data must take priority over workbook identity');
		$systemRow=$d->rawQueryOne('select * from #_dt_ky_thi_hv where id_ky=? and cccd=?',array($id,'079199006703'));
		$systemStudent=dt_find_hocvien_by_cccd('079199006703');
		$exportIdentity=dt_kythi_course_values($systemRow,$systemStudent);
		kythi_outcome_check($exportIdentity['ma_hv']==='SYS-STUDENT'&&$exportIdentity['ma_khoa']==='SYS-COURSE'&&$exportIdentity['ten_khoa']==='System Course','Export identity did not use training course fields');
		$d->rawQuery("update #_dt_khoa set ma_khoa='',ten_khoa='' where id=?",array($systemCourseId));
		$fallbackRow=$systemRow;$fallbackRow['ma_khoa']='79044K261010';$fallbackRow['ten_khoa']='122B(TĐ)';
		$exportFallback=dt_kythi_course_values($fallbackRow,$systemStudent);
		kythi_outcome_check($exportFallback['ma_hv']==='SYS-STUDENT'&&$exportFallback['ma_khoa']==='79044K261010'&&$exportFallback['ten_khoa']==='122B(TĐ)','Export did not fall back to workbook course identity when training fields were blank');
		$d->rawQuery("update #_dt_khoa set ma_khoa='SYS-COURSE',ten_khoa='System Course' where id=?",array($systemCourseId));
		$_REQUEST=array('id'=>$id,'ma_khoa'=>'79044K261011','ten_khoa'=>'122B');
		dt_kythi_detail();
		kythi_outcome_check(count($items)>0&&count($result_stats)===1&&$result_stats[0]['ma_khoa']==='79044K261011'&&$result_stats[0]['ten_khoa']==='122B(TĐ)'&&$result_stats[0]['so_dat']>0,'Course filters or passed-count grouping failed: items='.count($items).' groups='.json_encode($result_stats,JSON_UNESCAPED_UNICODE).' filters='.json_encode($result_filters,JSON_UNESCAPED_UNICODE));
		ob_start();require dirname(__DIR__).'/admin/templates/daotao/kythi/detail_tpl.php';$detailHtml=ob_get_clean();
		kythi_outcome_check(strpos($detailHtml,'<th>Mã HV</th>')!==false&&strpos($detailHtml,'<th>Mã khóa</th>')!==false&&strpos($detailHtml,'<th>Khóa</th>')!==false&&strpos($detailHtml,'79044K261011')!==false,'Course detail does not split student/course fields into separate columns');
	}
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
	$decisionRow=false;foreach(dt_htkh_document_rows($document) as $documentRow)if(is_string($documentRow['text']??null)&&strpos($documentRow['text'],'QD-2026-001')!==false)$decisionRow=true;
	kythi_outcome_check(!$decisionRow,'Decision number must not be printed on the certificate');
	foreach(array('35773'=>'09/12/1997','1997-12-09'=>'09/12/1997','9/12/1997'=>'09/12/1997','09-12-1997'=>'09/12/1997','1997'=>'1997','31/02/1997'=>'','0000-00-00'=>'',''=>'') as $birthInput=>$birthExpected)
	{
		$birthDocument=dt_htkh_build_document('TEST','Student',$birthInput,'B1','','Course','','','','','gd');
		kythi_outcome_check($birthDocument['ngaysinh']===$birthExpected,'Incorrect certificate birth date for '.$birthInput.': '.$birthDocument['ngaysinh']);
	}
	$exportDocument=dt_htkh_build_document('TEST','Student','35773','B1','','Course','','','','','gd','QD-2026-001');
	$html=dt_htkh_documents_html(array($exportDocument),'index.php');
	$word=dt_htkh_documents_docx_body(array($exportDocument),'');
	$excel=dt_htkh_documents_xlsx(array($exportDocument));
	$excelText=json_encode($excel->getActiveSheet()->toArray(),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
	foreach(array('HTML'=>$html,'Word'=>$word,'Excel'=>$excelText) as $format=>$content)
		kythi_outcome_check(strpos($content,'09/12/1997')!==false&&strpos($content,'QD-2026-001')===false,$format.' certificate has incorrect birth-date or decision-number output');
	$excel->disconnectWorksheets();
	$_REQUEST=array('id_ky'=>$id,'ky_khoa'=>'');$_POST=array('id_ky'=>$id,'ky_khoa'=>'','nguoi_ky'=>'Regression Signer','ngay_ky'=>'27/10/2026','chuc_danh'=>'gd','hang_ce'=>'','so_quyet_dinh'=>'QD-2026-001');
	try{dt_htkh_cap_giay_ky();throw new RuntimeException('HTKH issue flow did not finish.');}catch(KythiOutcomeNotice $notice){kythi_outcome_check($notice->success,$notice->getMessage());}
	$savedDecision=$d->rawQuery('select count(*) as num from #_dt_htkh where so_quyet_dinh=?',array('QD-2026-001'));
	kythi_outcome_check((int)$savedDecision[0]['num']===134,'Decision number was not persisted for every passed student');
	$courseKey=dt_htkh_khoa_key($passed[0]);
	$_SESSION['dt_htkh_schedule_csrf']='schedule-test';$_REQUEST=array('id_ky'=>$id,'ky_khoa'=>$courseKey);
	$_POST=array('csrf'=>'schedule-test','so_ngay'=>'90','tu_ngay'=>'2026-01-01','den_ngay'=>'2026-05-31');
	try{dt_htkh_schedule_save();throw new RuntimeException('Missing schedule save result');}catch(KythiOutcomeNotice $notice){kythi_outcome_check($notice->success,$notice->getMessage());}
	$schedule=dt_htkh_schedule_get($id,$courseKey);
	kythi_outcome_check($schedule===array('so_ngay'=>90,'tu_ngay'=>'2026-01-01','den_ngay'=>'2026-05-31'),'Manual duration was not saved');
	list($selectedPeriod,$selectedDocuments)=dt_htkh_ky_documents();
	kythi_outcome_check(count($selectedDocuments)>0,'No old-course certificates generated');
	foreach($selectedDocuments as $savedDocument)kythi_outcome_check($savedDocument['so_ngay']==='90 ngày'&&strpos($savedDocument['tu_den'],'ngày 01 tháng 01 năm 2026')!==false&&strpos($savedDocument['tu_den'],'ngày 31 tháng 05 năm 2026')!==false,'Old-course certificate omitted manual schedule');
	$scheduleExcel=dt_htkh_documents_xlsx(array($selectedDocuments[0]));
	$scheduleOutputs=array(dt_htkh_documents_html(array($selectedDocuments[0]),'index.php'),dt_htkh_documents_docx_body(array($selectedDocuments[0]),''),json_encode($scheduleExcel->getActiveSheet()->toArray(),JSON_UNESCAPED_UNICODE));
	foreach($scheduleOutputs as $output)kythi_outcome_check(strpos($output,'90 ngày')!==false&&strpos($output,'ngày 31 tháng 05 năm 2026')!==false,'Manual schedule missing from a certificate export format');
	$scheduleExcel->disconnectWorksheets();
	dt_htkh_list();ob_start();require dirname(__DIR__).'/admin/templates/daotao/htkh/items_tpl.php';$scheduleForm=ob_get_clean();
	kythi_outcome_check(strpos($scheduleForm,'act=htkhScheduleSave')!==false&&strpos($scheduleForm,'value="90"')!==false&&strpos($scheduleForm,'value="2026-01-01"')!==false,'Schedule editor did not reload saved values');
	$scheduleTestEditable=false;ob_start();require dirname(__DIR__).'/admin/templates/daotao/htkh/items_tpl.php';$readOnlyForm=ob_get_clean();$scheduleTestEditable=true;
	kythi_outcome_check(strpos($readOnlyForm,'Lưu thời gian học')===false,'Read-only account can see schedule save button');
	$_REQUEST=array('id_ky'=>$id,'ky_khoa'=>'');list($allPeriod,$allDocuments)=dt_htkh_ky_documents();
	$manualCount=0;foreach($allDocuments as $savedDocument)if($savedDocument['so_ngay']==='90 ngày')$manualCount++;
	kythi_outcome_check($manualCount===count(dt_htkh_ky_passed($id,$courseKey)),'Schedule leaked into other courses in all-period export');
	kythi_outcome_check(dt_htkh_schedule_get($id+1,$courseKey)['so_ngay']==='','Schedule leaked into another exam period');
	$_REQUEST=array('id_ky'=>$id,'ky_khoa'=>$courseKey);$_POST['den_ngay']='2025-01-01';
	try{dt_htkh_schedule_save();throw new RuntimeException('Reverse dates accepted');}catch(KythiOutcomeNotice $notice){kythi_outcome_check(!$notice->success,'Reverse dates accepted');}
	kythi_outcome_check(dt_htkh_schedule_get($id,$courseKey)===$schedule,'Rejected dates changed saved schedule');
	$_POST['den_ngay']='2026-05-31';$_POST['csrf']='wrong';
	try{dt_htkh_schedule_save();throw new RuntimeException('Invalid CSRF accepted');}catch(KythiOutcomeNotice $notice){kythi_outcome_check(!$notice->success,'Invalid schedule CSRF accepted');}
	echo "PASS manual course schedule save, certificate override, course/period isolation, invalid dates and CSRF\n";
	$trainingProfiles=$d->rawQuery('select count(*) as num from #_dt_hocvien');
	kythi_outcome_check((int)$trainingProfiles[0]['num']===($isMakhoaWorkbook ? 1 : 0),'Exam result import must not create training profiles');
	echo "PASS real B/C1 result import: 166 rows, 134 passed, 32 failed; HTKH returns 134; training profiles unchanged\n";
}
finally
{
	foreach($temporaryTables as $table)$d->rawQuery('drop temporary table if exists table_'.$table);
	echo "Temporary tables removed; permanent exam/training data unchanged\n";
}