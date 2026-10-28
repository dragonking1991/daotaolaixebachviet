<?php
if(PHP_SAPI!=='cli'||($argv[1]??'')!=='--local-db')exit("Usage: php scripts/regression_xd_courses.php --local-db\n");
define('LIBRARIES',dirname(__DIR__).'/libraries/');define('SOURCES',dirname(__DIR__).'/admin/sources/');
$_SERVER['SERVER_NAME']='localhost';require LIBRARIES.'config.php';require LIBRARIES.'autoload.php';new AutoLoad();$d=new PDODb($config['database']);
require SOURCES.'xangdau/khoa.php';require SOURCES.'xangdau/permissions.php';require SOURCES.'xangdau/helpers.php';require SOURCES.'xangdau/hocvien_crud.php';
function xd_course_test($condition,$message){if(!$condition)throw new RuntimeException($message);}
class XdCourseTestFunctions{public function check_permission(){return true;} public function transfer($message,$url,$success=true){throw new RuntimeException($message);} public function pagination($total,$perPage,$page,$url){return '';}}
$func=new XdCourseTestFunctions();$backups=array();$login_admin='test';$_SESSION=array('test'=>array('active'=>true),'list_quyen'=>array('xangdau_view'));
set_exception_handler(function($error){fwrite(STDERR,$error->getMessage()."\n");exit(1);});
$definition=xd_course_query('show create table #_xd_hocvien');
$sql=preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$definition[0]['Create Table']);$sql=preg_replace('/ AUTO_INCREMENT=\d+/','',$sql);xd_course_query($sql);
$courseDefinition=xd_course_query('show create table #_dt_khoa');
$courseSql=preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$courseDefinition[0]['Create Table']);$courseSql=preg_replace('/ AUTO_INCREMENT=\d+/','',$courseSql);xd_course_query($courseSql);
try
{
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
	xd_course_test($syncedCourse&&$syncedCourse['ten_khoa']==='Khóa đồng bộ mã 16','Training course name was not joined to the synchronized XD group');
	xd_course_test(xd_hocvien_course_group($syncedCourse)==='synced'&&xd_hocvien_course_label($syncedCourse)==='Khóa đồng bộ mã 16 · 1 HV','Synchronized course label should be concise and display the training name');
	ob_start();require dirname(__DIR__).'/admin/templates/xangdau/hocvien/items_tpl.php';$listHtml=ob_get_clean();
	$groupMarkup=strpos($listHtml,'<optgroup label="Đồng bộ từ Đào tạo (')!==false;$codeMarkup=strpos($listHtml,'value="79044K261016"')!==false;$nameMarkup=strpos($listHtml,'Khóa đồng bộ mã 16 · 1 HV')!==false;
	xd_course_test($groupMarkup&&$codeMarkup&&$nameMarkup,'Course dropdown grouping or code value is incorrect: '.json_encode(array('group'=>$groupMarkup,'code'=>$codeMarkup,'name'=>$nameMarkup,'groups'=>$xd_hocvien_khoa_groups),JSON_UNESCAPED_UNICODE));
	$rankCourse=array('khoa'=>'c1','so'=>96,'so_dong_bo'=>46);
	xd_course_test(xd_hocvien_course_group($rankCourse)==='rank'&&xd_hocvien_course_label($rankCourse)==='C1 · 96 HV (46 DAT + 50 XD)','Mixed rank aggregation label is unclear or inaccurate');
	$storedCourse=array('khoa'=>'98B-C','so'=>8,'so_dong_bo'=>0);
	xd_course_test(xd_hocvien_course_group($storedCourse)==='stored'&&xd_hocvien_course_label($storedCourse)==='98B-C · 8 HV','XD-only course label is incorrect');
	echo "PASS course rename/delete, protected history, collision rejection, CSRF and permission matrix\n";
}
finally
{
	$d->rawQuery('drop temporary table if exists table_xd_hocvien');
	$d->rawQuery('drop temporary table if exists table_dt_khoa');
	foreach($backups as $backup){$path=dirname(__DIR__).'/backups/'.$backup;if(is_file($path))unlink($path);}
	echo "Temporary XD table and test backups removed; real data unchanged\n";
}