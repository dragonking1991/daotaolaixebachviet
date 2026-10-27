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
	return $row['ngay_thanh_toan']===null && (int)$row['id_bangke']===0 && (float)$row['so_tien_thanh_toan']==0 && empty($row['quan_ly_duyet']);
}

function xd_courses_page()
{
	global $func,$curPage,$xd_courses,$xd_course_name,$xd_course_students,$xd_course_paging,$xd_course_total;
	if(empty($_SESSION['xd_course_csrf']))$_SESSION['xd_course_csrf']=bin2hex(random_bytes(24));
	$keyword=trim((string)($_GET['keyword']??''));
	$where="khoa<>''";$params=array();
	if($keyword!==''){$where.=' and khoa like ?';$params[]='%'.$keyword.'%';}
	$xd_courses=xd_course_query("select khoa,count(*) as so,sum(case when user_tao='Đồng bộ DAT' then 1 else 0 end) as so_dong_bo,
		sum(case when ngay_thanh_toan is not null then 1 else 0 end) as paid,
		sum(case when ngay_thanh_toan is null and id_bangke=0 and so_tien_thanh_toan=0 and quan_ly_duyet=0 then 1 else 0 end) as editable,
		count(distinct nullif(gv_key,'')) as teachers,sum(so_tien_thanh_toan) as amount
		from #_xd_hocvien where $where group by khoa order by khoa",$params);
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