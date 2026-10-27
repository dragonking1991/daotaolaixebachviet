<?php
if(!defined('SOURCES')) die('Error');

function dt_sh_identity($value)
{
	$value=dt_normalize_cccd($value);
	return strlen($value)===11?'0'.$value:$value;
}

function dt_sh_json($value)
{
	$json=json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
	if($json===false)throw new RuntimeException('Không thể lưu dữ liệu nguồn UTF-8.');
	return $json;
}

function dt_sh_save_source_rows($parsed,$filename,$hash,$results)
{
	foreach(array_chunk($parsed['records'],500) as $chunk)
	{
		$placeholders=array();$params=array();
		foreach($chunk as $record)
		{
			$mapped=$record['data'];$mapped['_date1904']=!empty($record['date1904']);
			$link=$results[$record['identity']]??array('student'=>0,'result'=>0);
			$placeholders[]='(?,?,?,?,?,?,?,?,?,?,?,?)';
			$params=array_merge($params,array($hash,mb_substr($filename,0,500),$record['row'],dt_sh_json($record['raw']),dt_sh_json($mapped),$record['reason'],$record['status'],$link['student'],$link['result'],dt_username(),time(),time()));
		}
		$updates=array();
		foreach(array('raw_json','data_json','ly_do','id_hocvien','id_ketqua','ngay_sua') as $field)$updates[]="$field=if(trang_thai='resolved',$field,values($field))";
		$updates[]="trang_thai=if(trang_thai='resolved',trang_thai,values(trang_thai))";
		dt_sathach_summary_query('insert into #_dt_sh_import_row (file_hash,filename,so_dong,raw_json,data_json,ly_do,trang_thai,id_hocvien,id_ketqua,nguoi_nhap,ngaytao,ngay_sua) values '.implode(',',$placeholders).' on duplicate key update '.implode(',',$updates),$params);
	}
}

function dt_sh_summary_store($parsed,$filename,$hash)
{
	global $d;
	$locked=false;$transaction=false;
	$counts=array('added'=>0,'updated'=>0,'periods_created'=>0,'pending'=>0,'duplicates'=>$parsed['duplicates']??0,'periods'=>0,'last_date'=>'');
	try
	{
		$lock=dt_sathach_summary_query("select get_lock('bachviet_sh_summary_import',10) as acquired");
		if((int)($lock[0]['acquired']??0)!==1)throw new RuntimeException('Có lượt import khác đang chạy; vui lòng thử lại.');
		$locked=true;
		if(!dt_backup_tables(array('dt_sat_hach','dt_sh_ky','dt_sh_import_row'),'imp_sh_summary'))throw new RuntimeException('Không sao lưu được dữ liệu trước import.');
		$d->startTransaction();$transaction=true;
		if(!empty($parsed['resolve_id']))
		{
			$pending=dt_sathach_summary_query('select trang_thai from #_dt_sh_import_row where id=? for update',array((int)$parsed['resolve_id']));
			if(!$pending || $pending[0]['trang_thai']!=='pending')throw new RuntimeException('Dòng này đã được xử lý; vui lòng tải lại danh sách.');
		}
		$periodIds=array();
		foreach($parsed['groups'] as $key=>$group)
		{
			$periods=dt_sathach_summary_query('select id from #_dt_sh_ky where ngay=? and loai=? order by id limit 2 for update',array($group['ngay'],$group['loai']));
			if(count($periods)>1)throw new RuntimeException('Ngày '.$group['ngay'].' có nhiều kỳ cùng loại xe; cần xác định kỳ trước khi nhập.');
			if($periods)$periodIds[$key]=(int)$periods[0]['id'];
			else
			{
				$name=($group['loai']==='moto'?'Mô tô':'Ô tô').' - '.$group['ngay'];
				dt_sathach_summary_query('insert into #_dt_sh_ky (ten,ngay,loai,nguoi_nhap,ngaytao) values (?,?,?,?,?)',array($name,$group['ngay'],$group['loai'],dt_username(),time()));
				$periodIds[$key]=(int)$d->getLastInsertId();$counts['periods_created']++;
			}
		}
		$existing=array();$ids=array_values($periodIds);
		if($ids)$existing=dt_sathach_summary_query('select id,id_ky,cccd from #_dt_sat_hach where id_ky in ('.implode(',',array_fill(0,count($ids),'?')).') for update',$ids);
		$byIdentity=array();
		foreach($existing as $record)
		{
			$key=$record['id_ky'].'|'.dt_sh_identity($record['cccd']);
			if(isset($byIdentity[$key]))throw new RuntimeException('Kỳ '.$record['id_ky'].' có nhiều kết quả cùng định danh.');
			$byIdentity[$key]=(int)$record['id'];
		}
		$results=array();
		foreach(array_chunk($parsed['rows'],500) as $batch)foreach($batch as $row)
		{
			$data=$row['data'];$data['id_ky']=$periodIds[$row['group']];$data['id_khoa']=0;$data['ma_hv']='';
			$data['nguoi_nhap']=dt_username();
			if(trim((string)($data['ma_khoa']??''))==='')$data['ma_khoa']=mb_substr(pathinfo(basename($filename),PATHINFO_FILENAME),0,100);
			$key=$data['id_ky'].'|'.$data['cccd'];
			if(isset($byIdentity[$key]))
			{
				$assignments=array();foreach(array_keys($data) as $field)$assignments[]='`'.$field.'`=?';
				dt_sathach_summary_query('update #_dt_sat_hach set '.implode(',',$assignments).' where id=?',array_merge(array_values($data),array($byIdentity[$key])));
				$resultId=$byIdentity[$key];$counts['updated']++;
			}
			else
			{
				$data['ngaytao']=time();
				dt_sathach_summary_query('insert into #_dt_sat_hach (`'.implode('`,`',array_keys($data)).'`) values ('.implode(',',array_fill(0,count($data),'?')).')',array_values($data));
				$resultId=(int)$d->getLastInsertId();$counts['added']++;
			}
			$results[$row['group'].'|'.$data['cccd']]=array('student'=>0,'result'=>$resultId);
		}
		dt_sh_save_source_rows($parsed,$filename,$hash,$results);
		$pending=dt_sathach_summary_query("select count(*) as num from #_dt_sh_import_row where file_hash=? and trang_thai='pending'",array($hash));
		$counts['pending']=(int)$pending[0]['num'];$counts['periods']=count($periodIds);
		if($parsed['groups'])$counts['last_date']=max(array_column($parsed['groups'],'ngay'));
		if($d->commit()===false)throw new RuntimeException('Không thể hoàn tất import.');
		$transaction=false;
	}
	catch(Exception $error){if($transaction)$d->rollback();throw $error;}
	finally{if($locked)$d->rawQuery("select release_lock('bachviet_sh_summary_import')");}
	return $counts;
}

function dt_sh_pending_page()
{
	global $d,$func,$curPage,$sh_pending_rows,$sh_pending_detail,$sh_pending_data,$sh_pending_hash,$sh_pending_count,$sh_pending_paging;
	$sh_pending_hash=trim((string)($_GET['file_hash']??''));
	if($sh_pending_hash!==''&&!preg_match('/^[a-f0-9]{64}$/',$sh_pending_hash))$func->transfer('Mã file không hợp lệ.','index.php?com=daotao&act=sathachPending',false);
	$sh_pending_detail=null;$sh_pending_data=array();$sh_pending_rows=array();$sh_pending_count=0;$sh_pending_paging='';
	$id=(int)($_GET['id']??0);
	if($id)
	{
		$rows=dt_sathach_summary_query('select * from #_dt_sh_import_row where id=?',array($id));
		if(!$rows)$func->transfer('Không tìm thấy dòng import.','index.php?com=daotao&act=sathachPending',false);
		$sh_pending_detail=$rows[0];$sh_pending_data=json_decode($rows[0]['data_json'],true)?:array();
		require_once LIBRARIES.'PHPExcel.php';
		try{$sh_pending_data['ngay_sh']=dt_sathach_summary_date($sh_pending_data['ngay_sh']??'',!empty($sh_pending_data['_date1904']));}catch(InvalidArgumentException $error){$sh_pending_data['ngay_sh']='';}
		return;
	}
	$where="trang_thai='pending'";$params=array();
	if($sh_pending_hash!==''){$where.=' and file_hash=?';$params[]=$sh_pending_hash;}
	$count=dt_sathach_summary_query('select count(*) as num from #_dt_sh_import_row where '.$where,$params);$sh_pending_count=(int)$count[0]['num'];
	$start=max(0,((int)$curPage-1)*30);
	$sh_pending_rows=dt_sathach_summary_query('select * from #_dt_sh_import_row where '.$where.' order by id desc limit '.$start.',30',$params);
	$url='index.php?com=daotao&act=sathachPending'.($sh_pending_hash!==''?'&file_hash='.$sh_pending_hash:'');
	$sh_pending_paging=$func->pagination($sh_pending_count,30,max(1,(int)$curPage),$url);
}

function dt_sh_pending_save()
{
	global $func;
	$id=(int)($_POST['id']??0);$back='index.php?com=daotao&act=sathachPending&id='.$id;
	if(empty($_SESSION['dt_sh_csrf'])||!hash_equals($_SESSION['dt_sh_csrf'],(string)($_POST['csrf']??'')))$func->transfer('Phiên thao tác không hợp lệ.',$back,false);
	$entries=dt_sathach_summary_query('select * from #_dt_sh_import_row where id=?',array($id));
	if(!$entries||$entries[0]['trang_thai']!=='pending')$func->transfer('Dòng này không còn chờ bổ sung.','index.php?com=daotao&act=sathachPending',false);
	$entry=$entries[0];$mapped=json_decode($entry['data_json'],true)?:array();$input=(array)($_POST['data']??array());
	foreach(array('hoten','cccd','nam_sinh','hang','ngay_sh','lt','mp','hinh','duong','ket_qua','ma_khoa','giaovien','so_gplx','ghi_chu','noi_dung_sh') as $field)
		if(isset($input[$field])&&!is_array($input[$field]))$mapped[$field]=trim((string)$input[$field]);
	try
	{
		$data=dt_sathach_summary_validate($mapped,!empty($mapped['_date1904']));
		$kind=stripos($data['hang'],'A')===0?'moto':'oto';$group=$data['ngay_sh'].'|'.$kind;$identity=$group.'|'.$data['cccd'];
		$parsed=array('resolve_id'=>$id,'rows'=>array(array('row'=>(int)$entry['so_dong'],'group'=>$group,'data'=>$data)),
			'groups'=>array($group=>array('ngay'=>$data['ngay_sh'],'loai'=>$kind)),'duplicates'=>0,
			'records'=>array(array('row'=>(int)$entry['so_dong'],'raw'=>json_decode($entry['raw_json'],true)?:array(),'data'=>$mapped,
			'date1904'=>!empty($mapped['_date1904']),'status'=>'resolved','reason'=>'','identity'=>$identity)));
		dt_sh_summary_store($parsed,$entry['filename'],$entry['file_hash']);
	}
	catch(Exception $error){$func->transfer($error->getMessage().' Dòng gốc được giữ nguyên.',$back,false);}
	dt_audit('update','sh_pending',1,'source_row='.$id);
	dt_import_notice('Đã bổ sung và lưu kết quả trong mục sát hạch.','index.php?com=daotao&act=sathachPending&file_hash='.$entry['file_hash'],true);
}