<?php
if(PHP_SAPI!=='cli'||($argv[1]??'')!=='--local-db')exit("Usage: php scripts/regression_xd_courses.php --local-db\n");
define('LIBRARIES',dirname(__DIR__).'/libraries/');define('SOURCES',dirname(__DIR__).'/admin/sources/');
$_SERVER['SERVER_NAME']='localhost';require LIBRARIES.'config.php';require LIBRARIES.'autoload.php';new AutoLoad();$d=new PDODb($config['database']);
require SOURCES.'xangdau/khoa.php';require SOURCES.'xangdau/permissions.php';require SOURCES.'xangdau/helpers.php';require SOURCES.'xangdau/hocvien_crud.php';
require SOURCES.'xangdau/hocvien_financial_import.php';
function xd_course_test($condition,$message){if(!$condition)throw new RuntimeException($message);}
class XdCourseTestFunctions{public function check_permission(){return true;} public function transfer($message,$url,$success=true){throw new RuntimeException($message);} public function pagination($total,$perPage,$page,$url){return '';}}
$func=new XdCourseTestFunctions();$backups=array();$login_admin='test';$_SESSION=array('test'=>array('active'=>true),'list_quyen'=>array('xangdau_view'));
set_exception_handler(function($error){fwrite(STDERR,$error->getMessage()."\n");exit(1);});
$definition=xd_course_query('show create table #_xd_hocvien');
$sql=preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$definition[0]['Create Table']);$sql=preg_replace('/ AUTO_INCREMENT=\d+/','',$sql);xd_course_query($sql);
$courseDefinition=xd_course_query('show create table #_dt_khoa');
$courseSql=preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$courseDefinition[0]['Create Table']);$courseSql=preg_replace('/ AUTO_INCREMENT=\d+/','',$courseSql);xd_course_query($courseSql);
$studentDefinition=xd_course_query('show create table #_dt_hocvien');
$studentSql=preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$studentDefinition[0]['Create Table']);$studentSql=preg_replace('/ AUTO_INCREMENT=\d+/','',$studentSql);xd_course_query($studentSql);
try
{
	xd_course_query("insert into #_xd_hocvien (ho_ten,cccd,khoa,lan_import,thu_tu_file) values
		('Trần Bình','990000000101','A',1,1),
		('Nguyễn An','990000000102','Z',2,2),
		('Lê An','990000000103','Y',3,3)");
	$curPage=1;$_REQUEST=array();xd_get_hocvien();
	xd_course_test(array_column($items,'ho_ten')===array('Lê An','Nguyễn An','Trần Bình'),'Student list must sort given name then full name, not course/import order');
	$originalOrder=xd_course_query('select ho_ten from #_xd_hocvien order by '.xd_hocvien_order_clause());
	xd_course_test(array_column($originalOrder,'ho_ten')===array('Trần Bình','Lê An','Nguyễn An'),'Display sorting changed settlement priority');
	xd_course_query('delete from #_xd_hocvien');
	xd_course_test(xd_permission_denied('saveKhoa')&&xd_permission_denied('deleteKhoa')&&!xd_permission_denied('khoa'),'View-only course permissions failed');
	$_SESSION['list_quyen']=array('xangdau_import');xd_course_test(!xd_permission_denied('saveKhoa')&&xd_permission_denied('deleteKhoa'),'Import-only course permissions failed');
	$_SESSION['list_quyen']=array('xangdau_xoa');xd_course_test(xd_permission_denied('saveKhoa')&&!xd_permission_denied('deleteKhoa'),'Delete-only course permissions failed');
	$_POST=array('khoa'=>'SOURCE','csrf'=>'invalid');
	try{xd_course_save_action(true);throw new RuntimeException('Invalid CSRF accepted');}catch(RuntimeException $error){xd_course_test(strpos($error->getMessage(),'Phiên thao tác')!==false,$error->getMessage());}
	foreach(array('OPEN','PAID','BATCH','APPROVED','AMOUNT') as $index=>$kind)
	{
		xd_course_query('insert into #_xd_hocvien (ho_ten,cccd,khoa,ngay_thanh_toan,id_bangke,quan_ly_duyet,so_tien_thanh_toan) values (?,?,?,?,?,?,?)',array($kind,'99000000000'.$index,'SOURCE',$kind==='PAID'?'2026-10-27':null,$kind==='BATCH'?42:0,$kind==='APPROVED'?1:0,$kind==='AMOUNT'?1200000:0));
	}
	$protected=xd_course_query("select * from #_xd_hocvien where ho_ten<>'OPEN' order by id");
	$curPage=1;$_GET=array('khoa'=>'SOURCE');$_SESSION['list_quyen']=array('xangdau_view');xd_courses_page();
	xd_course_test($xd_course_total===5&&count($xd_course_students)===5&&count($xd_courses)===1,'Course list/detail counts mismatch');
	ob_start();require dirname(__DIR__).'/admin/templates/xangdau/khoa/items_tpl.php';$view=ob_get_clean();
	xd_course_test(strpos($view,'PAID')!==false&&strpos($view,'saveKhoa')===false&&strpos($view,'deleteKhoa')===false,'Read-only detail leaked edit/delete controls');
	$result=xd_course_change('SOURCE','RENAMED');$backups[]=$result['backup'];
	xd_course_test($result['changed']===1&&$result['kept']===4,'Rename scope mismatch');
	xd_course_test($protected===xd_course_query("select * from #_xd_hocvien where ho_ten<>'OPEN' order by id"),'Rename changed protected history');
	$renamed=xd_course_query("select khoa from #_xd_hocvien where ho_ten='OPEN'");xd_course_test($renamed[0]['khoa']==='RENAMED','Unpaid student was not renamed');
	try{xd_course_change('RENAMED','SOURCE');throw new RuntimeException('Existing target accepted');}catch(RuntimeException $error){xd_course_test(strpos($error->getMessage(),'đã tồn tại')!==false,$error->getMessage());}
	$result=xd_course_change('RENAMED','',true);$backups[]=$result['backup'];xd_course_test($result['changed']===1&&$result['kept']===0,'Delete scope mismatch');
	xd_course_test($protected===xd_course_query('select * from #_xd_hocvien order by id'),'Deletion changed paid/batched/approved history');
	try{xd_course_change('SOURCE','',true);throw new RuntimeException('Protected-only course deleted');}catch(RuntimeException $error){xd_course_test(strpos($error->getMessage(),'không thay đổi lịch sử')!==false,$error->getMessage());}
	xd_course_query('insert into #_xd_hocvien (ho_ten,cccd,khoa,user_tao) values (?,?,?,?)',array('DAT student','990000000009','K122-B(TD)','Đồng bộ DAT'));
	$datBefore=xd_course_query("select * from #_xd_hocvien where user_tao='Đồng bộ DAT'");
	try{xd_course_change('K122-B(TD)','NEW COURSE');throw new RuntimeException('DAT course renamed');}catch(RuntimeException $error){xd_course_test(strpos($error->getMessage(),'giữ khóa gốc')!==false,$error->getMessage());}
	xd_course_test($datBefore===xd_course_query("select * from #_xd_hocvien where user_tao='Đồng bộ DAT'"),'DAT training course was changed');
	xd_course_query('insert into #_xd_hocvien (ho_ten,cccd,khoa,user_tao) values (?,?,?,?)',array('Imported student','990000000008','K122-B(TD)','ketoanxd'));
	$mixed=xd_course_change('K122-B(TD)','IMPORTED ONLY');$backups[]=$mixed['backup'];
	xd_course_test($mixed['changed']===1&&$mixed['kept']===1&&$datBefore===xd_course_query("select * from #_xd_hocvien where user_tao='Đồng bộ DAT'"),'Mixed-course rename changed a DAT-origin row');
	xd_course_query("insert into #_dt_khoa (ma_khoa,ten_khoa,hang) values ('79044K261016','Khóa đồng bộ mã 16','B11')");
	xd_course_query("insert into #_xd_hocvien (ho_ten,cccd,khoa,user_tao) values (?,?,?,?)",array('Synced course student','990000000007','79044K261016','Đồng bộ DAT'));
	$curPage=1;$_REQUEST=array();xd_get_hocvien();$syncedCourse=null;
	foreach($xd_hocvien_khoas as $course)if($course['khoa']==='79044K261016')$syncedCourse=$course;
	xd_course_test($syncedCourse&&$syncedCourse['ten_khoa']==='Khóa đồng bộ mã 16','Training course name was not joined to the synchronized XD group: '.json_encode($syncedCourse,JSON_UNESCAPED_UNICODE).' SQL='.json_encode($d->getLastError(),JSON_UNESCAPED_UNICODE));
	xd_course_test(xd_hocvien_course_group($syncedCourse)==='synced'&&xd_hocvien_course_label($syncedCourse)==='79044K261016 — Khóa đồng bộ mã 16 · 1 HV','Synchronized course label should display both course code and training name');
	$_GET=array();xd_courses_page();$courseSummary=null;foreach($xd_courses as $course)if($course['khoa']==='79044K261016')$courseSummary=$course;
	xd_course_test($courseSummary&&$courseSummary['ten_khoa']==='Khóa đồng bộ mã 16'&&xd_hocvien_course_label($courseSummary)==='79044K261016 — Khóa đồng bộ mã 16 · 1 HV','Course management list should resolve and display the training course name');
	ob_start();require dirname(__DIR__).'/admin/templates/xangdau/hocvien/items_tpl.php';$listHtml=ob_get_clean();
	foreach(xd_student_financial_fields() as $label)xd_course_test(strpos($listHtml,$label)===false,'Source financial column should be shown in training finance, not XD: '.$label);
	xd_course_test(strpos($listHtml,'Ngày nộp hồ sơ')!==false,'Missing submission date column in student list');
	$groupMarkup=strpos($listHtml,'<optgroup label="Đồng bộ từ Đào tạo (')!==false;$codeMarkup=strpos($listHtml,'value="79044K261016"')!==false;$nameMarkup=strpos($listHtml,'Khóa đồng bộ mã 16 · 1 HV')!==false;
	xd_course_test($groupMarkup&&$codeMarkup&&$nameMarkup,'Course dropdown grouping or code value is incorrect: '.json_encode(array('group'=>$groupMarkup,'code'=>$codeMarkup,'name'=>$nameMarkup,'groups'=>$xd_hocvien_khoa_groups),JSON_UNESCAPED_UNICODE));
	$rankCourse=array('khoa'=>'c1','so'=>96,'so_dong_bo'=>46);
	xd_course_test(xd_hocvien_course_group($rankCourse)==='rank'&&xd_hocvien_course_label($rankCourse)==='c1 — C1 · 96 HV','Rank aggregation must display stored value, rank label and count');
	$mixedSyncedCourse=array('khoa'=>'79044K261016','ten_khoa'=>'Tên khóa đào tạo K16','so'=>5,'so_dong_bo'=>2);
	xd_course_test(xd_hocvien_course_group($mixedSyncedCourse)==='mixed'&&xd_hocvien_course_label($mixedSyncedCourse)==='79044K261016 — Tên khóa đào tạo K16 · 5 HV','Mixed XD group should display course code/name without misleading source counts');
	xd_course_query("insert into #_dt_khoa (ma_khoa,ten_khoa,hang) values ('79044K261017','Khóa nguồn K17','C1')");$linkedCourseId=(int)$d->getLastInsertId();
	xd_course_query("insert into #_dt_hocvien (id_khoa,ma_hv,cccd,hoten,hang) values (?, 'LINKED-STUDENT','990000000006','Linked Student','C1')",array($linkedCourseId));$linkedStudentId=(int)$d->getLastInsertId();
	xd_course_query('insert into #_xd_hocvien (thu_tu_file,ho_ten,cccd,khoa,user_tao) values (?,?,?,?,?)',array($linkedStudentId,'Linked student','990000000006','STALE-COURSE-CODE','Đồng bộ DAT'));
	$curPage=1;$_REQUEST=array();xd_get_hocvien();$legacyCourse=null;foreach($xd_hocvien_khoas as $course)if($course['khoa']==='STALE-COURSE-CODE')$legacyCourse=$course;
	xd_course_test($legacyCourse&&$legacyCourse['ten_khoa']==='Khóa nguồn K17'&&xd_hocvien_course_label($legacyCourse)==='STALE-COURSE-CODE — Khóa nguồn K17 · 1 HV','A legacy XD course code should resolve its name through the linked DAT training profile');
	$_GET=array();xd_courses_page();$legacySummary=null;foreach($xd_courses as $course)if($course['khoa']==='STALE-COURSE-CODE')$legacySummary=$course;
	xd_course_test($legacySummary&&$legacySummary['ten_khoa']==='Khóa nguồn K17','Course management list should resolve legacy names through linked DAT training profiles');
	foreach(array('khóa đồng bộ mã 16'=>'79044K261016','79044K261016'=>'79044K261016','khóa nguồn k17'=>'STALE-COURSE-CODE') as $keyword=>$expectedCode)
	{
		$_GET=array('keyword'=>$keyword);xd_courses_page();
		xd_course_test(array_column($xd_courses,'khoa')===array($expectedCode),'Course search must match code or training name, including linked legacy codes: '.$keyword);
		xd_course_test((int)$xd_courses[0]['so']===1,'Name search multiplied student count');
	}
	$_GET=array('keyword'=>'no-matching-course');xd_courses_page();
	xd_course_test(!$xd_courses,'Unmatched course search returned unrelated courses');
	$_GET=array();
	$storedCourse=array('khoa'=>'98B-C','so'=>8,'so_dong_bo'=>0);
	xd_course_test(xd_hocvien_course_group($storedCourse)==='stored'&&xd_hocvien_course_label($storedCourse)==='98B-C — Chưa có tên khóa · 8 HV','XD-only course label must explicitly report missing training name');
	foreach(array('dt_dat_phien','dt_dat_override') as $table)
	{
		$definition=xd_course_query('show create table #_'.$table);
		$temporarySql=preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$definition[0]['Create Table']);
		xd_course_query(preg_replace('/ AUTO_INCREMENT=\d+/','',$temporarySql));
	}
	xd_course_query("insert into #_dt_khoa (id,ma_khoa,ten_khoa,hang) values (90001,'DAT-COUNT','Test DAT count','C1')");
	foreach(array('990000001001','990000001002','990000001003') as $identity)
		xd_course_query("insert into #_dt_hocvien (id_khoa,cccd,hoten,hang) values (90001,?,'DAT count student','C1')",array($identity));
	xd_course_query("insert into #_dt_dat_phien (id_khoa,cccd,ma_phien,gio_thuchanh,la_xe_tudong,gio_dem,km)
		values (90001,'990000001001','count-auto',1,1,0,30),(90001,'990000001001','count-manual',25,0,4,900)");
	xd_course_query("insert into #_dt_dat_override (id_khoa,cccd,dat) values (90001,'990000001002',1)");
	$datCourses=array(array('khoa'=>'DAT-COUNT','ten_khoa'=>'Test DAT count','so'=>3,'so_dong_bo'=>1),$storedCourse);
	xd_hocvien_attach_dat_counts($datCourses);
	xd_course_test($datCourses[0]['so_dat']===2&&xd_hocvien_course_label($datCourses[0])==='DAT-COUNT — Test DAT count · 3 HV · 2 đạt DAT','DAT count must include actual passing and manual override, not source count');
	xd_course_test(!isset($datCourses[1]['so_dat']),'Unmatched course must not claim zero DAT passes');
	echo "PASS actual DAT counts, manual overrides, non-passed exclusion and source-independent labels\n";
	for($index=0;$index<45;$index++)
		xd_course_query("insert into #_xd_hocvien (ho_ten,cccd,khoa,user_tao) values (?,?,?,?)",
			array('Course bulk '.$index,'980000'.sprintf('%06d',$index),'BULK-ALL','Đồng bộ DAT'));
	xd_course_query("insert into #_xd_hocvien (ho_ten,cccd,khoa,ke_toan_kiem_tra) values ('Checked','980000009999','BULK-ALL',1)");
	$beforeBulk=xd_course_query("select * from #_xd_hocvien order by id");
	$_POST=array('delete_course'=>'1','khoa'=>'BULK-ALL','csrf'=>'bad');
	$_SESSION['xd_dat_csrf']='test';
	$_SESSION['list_quyen']=array('xangdau_xoa');
	try{xd_delete_hocvien_selected();throw new RuntimeException('Invalid course delete CSRF accepted');}
	catch(RuntimeException $error){xd_course_test(strpos($error->getMessage(),'phiên thao tác')!==false,$error->getMessage());}
	$_POST['csrf']='test';$_SESSION['list_quyen']=array('xangdau_view');
	try{xd_delete_hocvien_selected();throw new RuntimeException('View-only course deletion accepted');}
	catch(RuntimeException $error){xd_course_test(strpos($error->getMessage(),'phiên thao tác')!==false,$error->getMessage());}
	xd_course_test($beforeBulk===xd_course_query('select * from #_xd_hocvien order by id'),'Rejected course deletion changed data');
	$_SESSION['list_quyen']=array('xangdau_xoa');$_POST['khoa']='';
	try{xd_delete_hocvien_selected();throw new RuntimeException('Empty course deletion accepted');}
	catch(RuntimeException $error){xd_course_test(strpos($error->getMessage(),'Chọn một khóa')!==false,$error->getMessage());}
	$_POST['khoa']='BULK-ALL';
	$protectedBulk=xd_course_query("select * from #_xd_hocvien where khoa<>'BULK-ALL' or ke_toan_kiem_tra=1 order by id");
	$bulk=xd_course_change('BULK-ALL','',true);$backups[]=$bulk['backup'];
	xd_course_test($bulk['changed']===45&&$bulk['kept']===1,'Whole-course deletion did not cover all pages or preserve checked student');
	xd_course_test($protectedBulk===xd_course_query('select * from #_xd_hocvien order by id'),'Whole-course deletion changed protected/other-course data');
	echo "PASS whole-course deletion across 45 rows, source-linked records, checked protection, scope, CSRF and permissions\n";
	echo "PASS course rename/delete, protected history, collision rejection, CSRF and permission matrix\n";
}
finally
{
	$d->rawQuery('drop temporary table if exists table_xd_hocvien');
	$d->rawQuery('drop temporary table if exists table_dt_khoa');
	foreach($backups as $backup){$path=dirname(__DIR__).'/backups/'.$backup;if(is_file($path))unlink($path);}
	echo "Temporary XD table and test backups removed; real data unchanged\n";
}