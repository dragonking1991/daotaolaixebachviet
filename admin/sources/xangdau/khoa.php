<?php
if(!defined('SOURCES')) die('Error');

function xd_course_query($sql,$params=array())
{
	global $d;
	$rows=$d->rawQuery($sql,$params);
	if($d->getLastErrorCode()!=='00000')throw new RuntimeException('Không thể đọc/ghi dữ liệu khóa XD.');
	return $rows;
}

function xd_course_mutable($row)
{
	return $row['ngay_thanh_toan']===null && (int)$row['id_bangke']===0 && (float)$row['so_tien_thanh_toan']==0 && empty($row['quan_ly_duyet']) && empty($row['ke_toan_kiem_tra']);
}

function xd_courses_page()
{
	global $func,$curPage,$xd_courses,$xd_course_name,$xd_course_students,$xd_course_paging,$xd_course_total,$xd_course_targets,$xd_merge_log,$xd_course_names;
	if(empty($_SESSION['xd_course_csrf']))$_SESSION['xd_course_csrf']=bin2hex(random_bytes(24));
	$keyword=trim((string)($_GET['keyword']??''));
	$where="x.khoa<>''";$params=array();
	if($keyword!=='')
	{
		$where.=" and (x.khoa like ? or exists (
			select 1 from #_dt_khoa k
			left join #_dt_hocvien h on h.id_khoa=k.id and h.id=x.thu_tu_file and h.cccd=x.cccd and x.user_tao='Đồng bộ DAT'
			where (k.ma_khoa=x.khoa or k.ten_khoa=x.khoa or h.id is not null)
			and (k.ma_khoa like ? or k.ten_khoa like ?)))";
		$params=array_fill(0,3,'%'.$keyword.'%');
	}
	$xd_courses=xd_course_query("select x.khoa,count(*) as so,sum(case when x.user_tao='Đồng bộ DAT' then 1 else 0 end) as so_dong_bo,
		sum(case when x.ngay_thanh_toan is not null then 1 else 0 end) as paid,
		sum(case when x.ngay_thanh_toan is null and x.id_bangke=0 and x.so_tien_thanh_toan=0 and x.quan_ly_duyet=0 and x.ke_toan_kiem_tra=0 then 1 else 0 end) as editable,
		count(distinct nullif(x.gv_key,'')) as teachers,sum(x.so_tien_thanh_toan) as amount
		from #_xd_hocvien x where $where group by x.khoa order by x.khoa",$params);
	$courseNames=xd_course_query("select x.khoa,
		coalesce(nullif(max(case when h.id is not null and k.id=h.id_khoa then k.ten_khoa end),''),nullif(max(case when k.ma_khoa=x.khoa or k.ten_khoa=x.khoa then k.ten_khoa end),'')) as ten_khoa
		from #_xd_hocvien x
		left join #_dt_hocvien h on h.id=x.thu_tu_file and h.cccd=x.cccd and x.user_tao='Đồng bộ DAT'
		left join #_dt_khoa k on (k.id=h.id_khoa or k.ma_khoa=x.khoa or k.ten_khoa=x.khoa) and k.ten_khoa<>''
		where x.khoa<>'' group by x.khoa",array());
	$courseNameByCode=array();
	foreach($courseNames as $courseName)if(trim((string)$courseName['ten_khoa'])!=='')$courseNameByCode[$courseName['khoa']]=$courseName['ten_khoa'];
	foreach($xd_courses as &$course)$course['ten_khoa']=$courseNameByCode[$course['khoa']]??'';
	unset($course);
	xd_hocvien_attach_dat_counts($xd_courses);
	xd_course_merge_ensure();
	$xd_course_targets=xd_course_merge_targets();
	$xd_merge_log=xd_course_query('select * from #_xd_khoa_merge order by id desc limit 15');
	$xd_course_names=array();
	foreach(xd_course_query("select ma_khoa,ten_khoa from #_dt_khoa where ma_khoa<>'' and ten_khoa<>''") as $row)$xd_course_names[$row['ma_khoa']]=$row['ten_khoa'];
	$xd_course_name=trim((string)($_GET['khoa']??''));$xd_course_students=array();$xd_course_paging='';$xd_course_total=0;
	if($xd_course_name==='')return;
	$count=xd_course_query('select count(*) as num from #_xd_hocvien where khoa=?',array($xd_course_name));
	$xd_course_total=(int)$count[0]['num'];
	$page=max(1,(int)$curPage);$offset=($page-1)*30;
	$xd_course_students=xd_course_query('select * from #_xd_hocvien where khoa=? order by ho_ten,id limit '.$offset.',30',array($xd_course_name));
	$xd_course_paging=$func->pagination($xd_course_total,30,$page,'index.php?com=xangdau&act=khoa&khoa='.urlencode($xd_course_name));
}

function xd_course_change($oldName,$newName,$delete=false)
{
	global $d;
	$oldName=trim((string)$oldName);$newName=trim((string)$newName);
	if($oldName===''||(!$delete&&($newName===''||mb_strlen($newName,'UTF-8')>100||$newName===$oldName)))throw new RuntimeException('Nhập tên khóa mới, tối đa 100 ký tự và khác tên hiện tại.');
	$lock=xd_course_query("select get_lock('bachviet_sh_xd_course_change',10) as acquired");
	if((int)$lock[0]['acquired']!==1)throw new RuntimeException('Có lượt chỉnh sửa khóa đang chạy; vui lòng thử lại.');
	$transaction=false;
	try
	{
		$d->startTransaction();$transaction=true;
		$rows=xd_course_query('select * from #_xd_hocvien where khoa=? order by id for update',array($oldName));
		if(!$rows)throw new RuntimeException('Khóa không còn học viên.');
		if(!$delete&&xd_course_query('select id from #_xd_hocvien where khoa=? limit 1 for update',array($newName)))throw new RuntimeException('Tên khóa đích đã tồn tại; không tự gộp hai khóa.');
		$ids=array();foreach($rows as $row)if(xd_course_mutable($row)&&($delete||trim((string)$row['user_tao'])!=='Đồng bộ DAT'))$ids[]=(int)$row['id'];
		if(!$ids)throw new RuntimeException('Không có hồ sơ được phép thay đổi: giữ khóa gốc của học viên đồng bộ DAT và không thay đổi lịch sử thanh toán/duyệt/bảng kê.');
		$directory=dirname(dirname(dirname(__DIR__))).'/backups';
		if(!is_dir($directory)&&!mkdir($directory,0775,true))throw new RuntimeException('Không tạo được thư mục sao lưu.');
		$backup=$directory.'/xd_course_'.date('Ymd_His').'_'.bin2hex(random_bytes(4)).'.json';
		$json=json_encode(array('action'=>$delete?'delete':'rename','old_course'=>$oldName,'new_course'=>$newName,'rows'=>$rows),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
		if($json===false||file_put_contents($backup,$json,LOCK_EX)!==strlen($json))throw new RuntimeException('Không sao lưu được; chưa thay đổi khóa.');
		foreach(array_chunk($ids,500) as $chunk)
		{
			$marks=implode(',',array_fill(0,count($chunk),'?'));
			if($delete)xd_course_query('delete from #_xd_hocvien where id in ('.$marks.')',$chunk);
			else xd_course_query('update #_xd_hocvien set khoa=? where id in ('.$marks.')',array_merge(array($newName),$chunk));
		}
		if($d->commit()===false)throw new RuntimeException('Không thể hoàn tất cập nhật khóa.');
		$transaction=false;
		return array('changed'=>count($ids),'kept'=>count($rows)-count($ids),'backup'=>basename($backup));
	}
	catch(Exception $error){if($transaction)$d->rollback();throw $error;}
	finally{$d->rawQuery("select release_lock('bachviet_sh_xd_course_change')");}
}

function xd_course_merge_ensure()
{
	global $d;
	$d->rawQuery("CREATE TABLE IF NOT EXISTS #_xd_khoa_merge (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		khoa_nguon VARCHAR(100) NOT NULL,
		khoa_dich VARCHAR(100) NOT NULL,
		so_hv INT UNSIGNED NOT NULL DEFAULT 0,
		ids_json LONGTEXT NOT NULL,
		trang_thai VARCHAR(10) NOT NULL DEFAULT 'applied',
		user_tao VARCHAR(100) NOT NULL DEFAULT '',
		ngaytao INT UNSIGNED NOT NULL DEFAULT 0,
		user_hoantac VARCHAR(100) NOT NULL DEFAULT '',
		ngay_hoantac INT UNSIGNED NOT NULL DEFAULT 0,
		so_hoantac INT UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/* Danh sách khóa đích có thể gộp vào: khóa XD hiện có và khóa Đào tạo (value là giá trị lưu ở học viên XD). */
function xd_course_merge_targets()
{
	$targets=array();
	$names=array();
	foreach(xd_course_query("select ma_khoa,ten_khoa from #_dt_khoa where ten_khoa<>''") as $course)$names[$course['ma_khoa']]=$course['ten_khoa'];
	foreach(xd_course_query("select khoa,count(*) as so from #_xd_hocvien where khoa<>'' group by khoa order by khoa") as $row)
		$targets[$row['khoa']]=array('label'=>($names[$row['khoa']]??$row['khoa']).' · XD '.(int)$row['so'].' HV','xd'=>(int)$row['so']);
	foreach(xd_course_query("select k.ma_khoa,k.ten_khoa,(select count(*) from #_dt_hocvien h where h.id_khoa=k.id) as so_hoc_vien from #_dt_khoa k where k.ma_khoa<>'' order by k.ma_khoa") as $row)
		if(!isset($targets[$row['ma_khoa']]))$targets[$row['ma_khoa']]=array('label'=>(trim((string)$row['ten_khoa'])!==''?$row['ten_khoa']:$row['ma_khoa']).' · Đào tạo '.(int)$row['so_hoc_vien'].' HV · XD 0 HV','xd'=>0);
	return $targets;
}

/* Gộp toàn bộ học viên của khóa nguồn sang khóa đích; ghi nhật ký (danh sách id) để hoàn tác. */
function xd_course_merge($source,$target)
{
	global $d;
	xd_course_merge_ensure();
	$source=trim((string)$source);$target=trim((string)$target);
	if($source===''||$target===''||$source===$target||mb_strlen($target,'UTF-8')>100)throw new RuntimeException('Chọn khóa đích khác với khóa nguồn.');
	$lock=xd_course_query("select get_lock('bachviet_sh_xd_course_change',10) as acquired");
	if((int)$lock[0]['acquired']!==1)throw new RuntimeException('Có lượt chỉnh sửa khóa đang chạy; vui lòng thử lại.');
	$transaction=false;
	try
	{
		$known=xd_course_merge_targets();
		if(!isset($known[$target]))throw new RuntimeException('Khóa đích không tồn tại.');
		$d->startTransaction();$transaction=true;
		$rows=xd_course_query('select id from #_xd_hocvien where khoa=? order by id for update',array($source));
		if(!$rows)throw new RuntimeException('Khóa nguồn không còn học viên.');
		$before=xd_course_query('select count(*) as num from #_xd_hocvien where khoa=?',array($target));
		$ids=array_map('intval',array_column($rows,'id'));
		$logged=$d->insert('xd_khoa_merge',array('khoa_nguon'=>$source,'khoa_dich'=>$target,'so_hv'=>count($ids),'ids_json'=>json_encode($ids),'trang_thai'=>'applied','user_tao'=>xd_username(),'ngaytao'=>time(),'user_hoantac'=>'','ngay_hoantac'=>0,'so_hoantac'=>0));
		if($logged===false)throw new RuntimeException('Không ghi được lịch sử gộp khóa; chưa thay đổi dữ liệu.');
		$logId=(int)$d->getLastInsertId();
		foreach(array_chunk($ids,500) as $chunk)
			xd_course_query('update #_xd_hocvien set khoa=? where id in ('.implode(',',array_fill(0,count($chunk),'?')).')',array_merge(array($target),$chunk));
		if($d->commit()===false)throw new RuntimeException('Không thể hoàn tất gộp khóa.');
		$transaction=false;
		return array('changed'=>count($ids),'log_id'=>$logId,'target_before'=>(int)$before[0]['num'],'target_after'=>(int)$before[0]['num']+count($ids));
	}
	catch(Exception $error){if($transaction)$d->rollback();throw $error;}
	finally{$d->rawQuery("select release_lock('bachviet_sh_xd_course_change')");}
}

/* Hoàn tác một lần gộp: trả học viên về khóa nguồn (bỏ qua học viên đã được đổi khóa sau đó). */
function xd_course_merge_undo($logId)
{
	global $d;
	xd_course_merge_ensure();
	$lock=xd_course_query("select get_lock('bachviet_sh_xd_course_change',10) as acquired");
	if((int)$lock[0]['acquired']!==1)throw new RuntimeException('Có lượt chỉnh sửa khóa đang chạy; vui lòng thử lại.');
	$transaction=false;
	try
	{
		$d->startTransaction();$transaction=true;
		$log=xd_course_query('select * from #_xd_khoa_merge where id=? for update',array((int)$logId));
		if(!$log||$log[0]['trang_thai']==='undone')throw new RuntimeException('Lần gộp này không tồn tại hoặc đã được hoàn tác.');
		$log=$log[0];$ids=json_decode($log['ids_json'],true);
		if(!is_array($ids)||!$ids)throw new RuntimeException('Nhật ký gộp khóa không hợp lệ.');
		$restored=0;
		foreach(array_chunk(array_map('intval',$ids),500) as $chunk)
		{
			$marks=implode(',',array_fill(0,count($chunk),'?'));
			$params=array_merge(array($log['khoa_dich']),$chunk);
			$cnt=xd_course_query('select count(*) as num from #_xd_hocvien where khoa=? and id in ('.$marks.')',$params);
			$restored+=(int)$cnt[0]['num'];
			xd_course_query('update #_xd_hocvien set khoa=? where khoa=? and id in ('.$marks.')',array_merge(array($log['khoa_nguon']),$params));
		}
		$d->where('id',(int)$logId);
		if($d->update('xd_khoa_merge',array('trang_thai'=>'undone','user_hoantac'=>xd_username(),'ngay_hoantac'=>time(),'so_hoantac'=>$restored))===false)throw new RuntimeException('Không cập nhật được lịch sử.');
		if($d->commit()===false)throw new RuntimeException('Không thể hoàn tất hoàn tác.');
		$transaction=false;
		return array('restored'=>$restored,'total'=>count($ids),'source'=>$log['khoa_nguon']);
	}
	catch(Exception $error){if($transaction)$d->rollback();throw $error;}
	finally{$d->rawQuery("select release_lock('bachviet_sh_xd_course_change')");}
}

function xd_course_merge_action($undo=false)
{
	global $func;
	$back='index.php?com=xangdau&act=khoa';
	if(!xd_can_import())$func->transfer('Bạn không có quyền thực hiện thao tác này.',$back,false);
	if(empty($_SESSION['xd_course_csrf'])||!hash_equals($_SESSION['xd_course_csrf'],(string)($_POST['csrf']??'')))$func->transfer('Phiên thao tác không hợp lệ.',$back,false);
	try
	{
		if($undo)
		{
			$r=xd_course_merge_undo((int)($_POST['log_id']??0));
			$func->transfer('Đã hoàn tác: trả '.$r['restored'].'/'.$r['total'].' học viên về khóa "'.htmlspecialchars($r['source']).'".'.($r['restored']<$r['total']?'<br>Một số học viên đã đổi khóa sau lần gộp nên được giữ nguyên.':''),$back.'&khoa='.urlencode($r['source']));
		}
		$source=trim((string)($_POST['khoa']??''));$target=trim((string)($_POST['target']??''));
		$r=xd_course_merge($source,$target);
		$func->transfer('Đã gộp '.$r['changed'].' học viên từ "'.htmlspecialchars($source).'" sang "'.htmlspecialchars($target).'". Khóa đích XD: '.$r['target_before'].' → '.$r['target_after'].' HV. Có thể hoàn tác trong mục Lịch sử gộp khóa.',$back.'&khoa='.urlencode($target));
	}
	catch(Exception $error){$func->transfer($error->getMessage(),$back,false);}
}

function xd_course_save_action($delete=false)
{
	global $func;
	$oldName=trim((string)($_POST['khoa']??''));
	$back='index.php?com=xangdau&act=khoa&khoa='.urlencode($oldName);
	if(($delete&&!xd_can_xoa())||(!$delete&&!xd_can_import()))$func->transfer('Bạn không có quyền thực hiện thao tác này.',$back,false);
	if(empty($_SESSION['xd_course_csrf'])||!hash_equals($_SESSION['xd_course_csrf'],(string)($_POST['csrf']??'')))$func->transfer('Phiên thao tác không hợp lệ.',$back,false);
	try{$result=xd_course_change($oldName,$_POST['ten_khoa']??'',$delete);}
	catch(Exception $error){$func->transfer($error->getMessage(),$back,false);return;}
	$target=$delete?$oldName:trim((string)$_POST['ten_khoa']);
	$func->transfer(($delete?'Đã xóa ':'Đã đổi khóa cho ').$result['changed'].' học viên chưa thanh toán; giữ nguyên '.$result['kept'].' hồ sơ được bảo vệ (lịch sử thanh toán hoặc khóa gốc DAT). Sao lưu: '.$result['backup'],'index.php?com=xangdau&act=khoa&khoa='.urlencode($target));
}