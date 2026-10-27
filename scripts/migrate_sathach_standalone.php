<?php
if(PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
define('LIBRARIES', dirname(__DIR__).'/libraries/');
define('SOURCES', dirname(__DIR__).'/admin/sources/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
require SOURCES.'daotao/helpers.php';
require SOURCES.'daotao/ops.php';

function sh_migration_query($sql, $params=array())
{
	global $d;
	$rows=$d->rawQuery($sql,$params);
	if($d->getLastErrorCode()!=='00000')throw new RuntimeException('SQL error: '.json_encode($d->getLastError()));
	return $rows;
}

function sh_migration_targets()
{
	$courses=sh_migration_query("select id,ma_khoa,ten_khoa from #_dt_khoa where lower(ma_khoa) like 'file%' order by id");
	$targets=array();
	foreach($courses as $course)
	{
		$code=function_exists('mb_strtolower')?mb_strtolower($course['ma_khoa'],'UTF-8'):strtolower($course['ma_khoa']);
		if(strpos($code,'file ')!==0)continue;
		if(substr($code,-2)==='aa')$key='aa';
		elseif(substr($code,-4)==='b,c1')$key='b,c1';
		else continue;
		if(isset($targets[$key]))throw new RuntimeException('Có nhiều khóa trùng tên file '.$key.'; dừng để đối chiếu thủ công.');
		$targets[$key]=$course;
	}
	if(count($targets)>2)throw new RuntimeException('Có quá hai khóa tên file cần đối chiếu; không thay đổi dữ liệu.');
	$sourceCodes=sh_migration_query("select distinct json_unquote(json_extract(data_json,'$.ma_khoa')) as course_code from #_dt_sh_import_row where filename like ? and json_unquote(json_extract(data_json,'$.ma_khoa'))<>''",array('%AA.xlsx'));
	$auditRows=sh_migration_query("select ngaytao from #_dt_audit where action='import' and entity='sat_hach' and detail like ? and detail like ?",array('%AA.xlsx%','%new_profiles=%'));
	foreach($sourceCodes as $sourceCode)
	{
		$code=trim((string)$sourceCode['course_code']);if($code==='')continue;
		$matches=sh_migration_query('select id,ma_khoa,ten_khoa,ngaytao from #_dt_khoa where ma_khoa=? order by id limit 2',array($code));
		if(count($matches)!==1)continue;
		$course=$matches[0];$id=(int)$course['id'];
		if(isset(array_column($targets,null,'id')[$id]))continue;
		$profiles=sh_migration_query('select count(*) as total,sum(h.ma_hv<>\'\') as with_code,sum(h.gv_hoten<>\'\') as with_teacher from #_dt_hocvien h where h.id_khoa=?',array($id));
		$profile=$profiles[0];$auditDistance=PHP_INT_MAX;
		foreach($auditRows as $audit)$auditDistance=min($auditDistance,abs((int)$audit['ngaytao']-(int)$course['ngaytao']));
		if((int)$profile['total']>0&&(int)$profile['with_code']===0&&(int)$profile['with_teacher']===0&&$auditDistance<=10)$targets[]=$course;
	}
	if(!$targets)throw new RuntimeException('Không tìm thấy khóa/hồ sơ được tạo từ import kết quả; không thay đổi dữ liệu.');
	return array_values($targets);
}

$apply=in_array('--apply',$argv,true);
$locked=false;$transaction=false;
try
{
	if($apply)
	{
		$lock=sh_migration_query("select get_lock('bachviet_sh_summary_import',10) as acquired");
		if((int)$lock[0]['acquired']!==1)throw new RuntimeException('Có lượt import sát hạch đang chạy; thử lại sau.');
		$locked=true;
	}
	$targets=sh_migration_targets();
	$ids=array_map(function($course){return (int)$course['id'];},$targets);
	$marks=implode(',',array_fill(0,count($ids),'?'));
	$students=sh_migration_query('select id,cccd from #_dt_hocvien where id_khoa in ('.$marks.') order by id',$ids);
	$sideTables=array('dt_lythuyet','dt_mophong','dt_cabin_kq','dt_dat_phien','dt_dat_override','dt_thuchanh_hinh','dt_taichinh');
	$sideCounts=array();
	foreach($sideTables as $table)
	{
		$sql=$table==='dt_taichinh'
			?'select count(*) as num from #_dt_taichinh t inner join #_dt_hocvien h on h.id=t.id_hocvien where h.id_khoa in ('.$marks.')'
			:'select count(*) as num from #_'.$table.' where id_khoa in ('.$marks.')';
		$count=sh_migration_query($sql,$ids);
		$sideCounts[$table]=(int)$count[0]['num'];
		if($sideCounts[$table]>0)throw new RuntimeException('Có dữ liệu '.$table.' phụ thuộc hai khóa file; không xóa tự động.');
	}
	$duplicateResults=sh_migration_query('select id_ky,cccd,count(*) as num from #_dt_sat_hach where id_khoa>0 group by id_ky,cccd having count(*)>1');
	$collisions=sh_migration_query('select s.id from #_dt_sat_hach s inner join #_dt_sat_hach x on x.id_ky=s.id_ky and x.cccd=s.cccd and x.id_khoa=0 where s.id_khoa>0');
	if($duplicateResults||$collisions)throw new RuntimeException('Không thể tách mã khóa khỏi kết quả do trùng kỳ/CCCD; dữ liệu chưa thay đổi.');
	$examResults=sh_migration_query('select count(*) as num from #_dt_sat_hach where id_khoa>0');
	$sourceRows=sh_migration_query('select count(*) as num from #_dt_sh_import_row where id_hocvien<>0');
	$cccds=array_values(array_unique(array_column($students,'cccd')));
	$xdCount=0;
	foreach(array_chunk($cccds,500) as $chunk)
	{
		$xd=sh_migration_query('select count(*) as num from #_xd_hocvien where cccd in ('.implode(',',array_fill(0,count($chunk),'?')).')',$chunk);
		$xdCount+=(int)$xd[0]['num'];
	}
	$summary=array('training_artifacts_to_remove'=>$targets,'training_profiles_to_remove'=>count($students),'exam_results_to_detach'=>(int)$examResults[0]['num'],'staging_profile_links_to_clear'=>(int)$sourceRows[0]['num'],'xd_profiles_kept'=>$xdCount,'dependent_training_finance'=>$sideCounts);
	if(!$apply){echo json_encode($summary,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\nDry run only; rerun with --apply to detach imported exam data from training.\n";exit(0);}
	$backup=dt_backup_tables(array('dt_khoa','dt_hocvien','dt_sat_hach','dt_sh_import_row'),'detach_sh_training');
	if(!$backup)throw new RuntimeException('Không sao lưu được; chưa tách dữ liệu.');
	$d->startTransaction();$transaction=true;
	$linkedResults=sh_migration_query('select id,id_khoa,ma_khoa from #_dt_sat_hach where id_khoa>0 order by id for update');
	$lockedStudents=sh_migration_query('select id from #_dt_hocvien where id_khoa in ('.$marks.') order by id for update',$ids);
	$studentIds=array_map(function($student){return (int)$student['id'];},$lockedStudents);
	sh_migration_query('update #_dt_sh_import_row set id_hocvien=0 where id_hocvien<>0');
	sh_migration_query("update #_dt_sat_hach s left join (select id_ketqua,max(nullif(json_unquote(json_extract(data_json,'$.ma_khoa')),'')) as source_course,max(nullif(json_unquote(json_extract(data_json,'$.giaovien')),'')) as source_teacher,max(substring_index(filename,'.',1)) as file_label from #_dt_sh_import_row where id_ketqua>0 group by id_ketqua) q on q.id_ketqua=s.id set s.id_khoa=0,s.ma_hv='',s.ma_khoa=coalesce(q.source_course,q.file_label,s.ma_khoa),s.giaovien=coalesce(q.source_teacher,'') where s.id_khoa>0");
	sh_migration_query('delete from #_dt_hocvien where id_khoa in ('.$marks.')',$ids);
	sh_migration_query('delete from #_dt_khoa where id in ('.$marks.')',$ids);
	if($d->commit()===false)throw new RuntimeException('Không thể hoàn tất tách dữ liệu.');
	$transaction=false;
	$summary['backup']=$backup;
	$summary['results_still_linked_to_training']=(int)sh_migration_query('select count(*) as num from #_dt_sat_hach where id_khoa>0')[0]['num'];
	$summary['source_rows_linked_to_training']=(int)sh_migration_query('select count(*) as num from #_dt_sh_import_row where id_hocvien<>0')[0]['num'];
	echo json_encode($summary,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\nLegacy exam data is now standalone; exam results and source rows were preserved.\n";
}
catch(Exception $error)
{
	if($transaction)$d->rollback();
	fwrite(STDERR,$error->getMessage()."\n");
	exit(1);
}
finally
{
	if($locked)$d->rawQuery("select release_lock('bachviet_sh_summary_import')");
}