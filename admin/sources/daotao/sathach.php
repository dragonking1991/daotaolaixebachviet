<?php
if(!defined('SOURCES')) die("Error");
require_once SOURCES.'daotao/sathach_summary.php';

function dt_sathach_pass_label($value)
{
	$value = dt_strip_diacritics(trim((string)$value));
	if(preg_match('/rot|truot|vang|khong/', $value)) return false;
	return $value === 'd' || strpos($value, 'dat') !== false;
}

function dt_sathach_date_key($value)
{
	$value = trim((string)$value);
	if($value === '') return '';
	if(is_numeric($value)) require_once LIBRARIES.'PHPExcel.php';
	try { return dt_sathach_summary_date($value); }
	catch(InvalidArgumentException $error) { return ''; }
}

function dt_sathach_display_date($value)
{
	$date = dt_sathach_date_key($value);
	if($date === '') return '—';
	return implode('-', array_reverse(explode('-', $date)));
}

function dt_sathach_query_rows($where, $params)
{
	global $d;
	return $d->rawQuery("select s.*,s.giaovien as gv_roster from #_dt_sat_hach s where $where", $params);
}

function dt_sathach_sort_by_name(&$items)
{
	foreach($items as &$item)
	{
		$item['_sort_date'] = dt_sathach_date_key($item['ngay_sh'] ?? '') ?: '9999-12-31';
		$name = trim((string)$item['hoten']);
		$parts = preg_split('/\s+/u', $name);
		$item['_sort_given'] = dt_strip_diacritics(end($parts));
		$item['_sort_full'] = dt_strip_diacritics($name);
	}
	unset($item);
	usort($items, function($left, $right) {
		$compare = strcmp($left['_sort_date'], $right['_sort_date']);
		if($compare === 0) $compare = strcmp($left['_sort_given'], $right['_sort_given']);
		if($compare === 0) $compare = strcmp($left['_sort_full'], $right['_sort_full']);
		if($compare === 0) $compare = (int)($left['id'] ?? 0) <=> (int)($right['id'] ?? 0);
		return $compare;
	});
	foreach($items as &$item) { unset($item['_sort_date'], $item['_sort_given'], $item['_sort_full']); }
	unset($item);
}

/* ============================================================
 * Mô đun Kết quả sát hạch (kết quả thi GPLX chính thức).
 * Import kết quả sát hạch từ TTSH về để lưu trữ + tra cứu.
 * ============================================================ */

/* Danh sách kết quả sát hạch theo khóa / từ khóa. */
function dt_sathach_list()
{
	global $items, $total_items, $sh_periods, $sh_period_sel;
	dt_sathach_period_options();
	$items = array(); $total_items = 0;
	$name = trim(dt_req('keyword'));
	$date = dt_req('ngay_sh');
	if(!$sh_period_sel && $date === '' && $name === '') return;
	$where = '1'; $params = array();
	if($sh_period_sel) { $where .= ' and s.id_ky=?'; $params[] = $sh_period_sel; }
	if($date !== '') { $where .= ' and (s.ngay_sh=? or s.ngay_sh=?)'; $params[] = $date; $params[] = date('d/m/Y', strtotime($date)); }
	if($name !== '') { $where .= ' and s.hoten=?'; $params[] = $name; }
	$items = dt_sathach_query_rows($where, $params);
	dt_sathach_sort_by_name($items);
	$total_items = count($items);
}

function dt_sathach_period_options()
{
	global $d, $sh_periods, $sh_period_sel;
	$sh_periods = $d->rawQuery('select k.*,(select count(*) from #_dt_sat_hach r where r.id_ky=k.id) as so_hoc_vien from #_dt_sh_ky k order by k.ngay asc,k.id asc');
	$sh_period_sel = (int)dt_req('id_ky', 0);
	if(empty($_SESSION['dt_sh_csrf'])) $_SESSION['dt_sh_csrf'] = bin2hex(random_bytes(24));
}

function dt_sathach_create_period()
{
	global $d, $func;
	$back = 'index.php?com=daotao&act=sathach';
	if(empty($_SESSION['dt_sh_csrf']) || !hash_equals($_SESSION['dt_sh_csrf'], (string)($_POST['csrf'] ?? '')))
		$func->transfer('Phiên thao tác không hợp lệ.', $back, false);
	$date = (string)($_POST['ngay'] ?? '');
	$parts = explode('-', $date);
	$kind = (string)($_POST['loai'] ?? '');
	if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !checkdate((int)($parts[1] ?? 0),(int)($parts[2] ?? 0),(int)($parts[0] ?? 0)) || !in_array($kind,array('moto','oto'),true))
		$func->transfer('Chọn ngày sát hạch và loại xe hợp lệ.', $back, false);
	$exist = $d->rawQueryOne('select id from #_dt_sh_ky where ngay=? and loai=? limit 1', array($date,$kind));
	if($exist) $func->transfer('Ngày sát hạch này đã có kỳ '.$kind.'.', $back.'&id_ky='.(int)$exist['id'], false);
	$name = ($kind === 'moto' ? 'Mô tô' : 'Ô tô').' - '.$date;
	$d->rawQuery('insert into #_dt_sh_ky (ten,ngay,loai,nguoi_nhap,ngaytao) values (?,?,?,?,?)', array($name,$date,$kind,dt_username(),time()));
	if($d->getLastErrorCode() !== '00000') $func->transfer('Không thể tạo kỳ sát hạch.', $back, false);
	$id = (int)$d->getLastInsertId();
	dt_audit('create','sh_ky',1,'id='.$id.' '.$date.' '.$kind);
	$func->transfer('Đã tạo kỳ sát hạch.', $back.'&id_ky='.$id);
}

function dt_sathach_delete_periods_action($ids)
{
	global $d;
	$lock=dt_sathach_summary_query("select get_lock('bachviet_sh_summary_import',10) as acquired");
	if((int)($lock[0]['acquired']??0)!==1)throw new RuntimeException('Đang có lượt import sát hạch; vui lòng thử lại.');
	try
	{
		$periods=dt_sathach_summary_query('select id,ngay,loai from #_dt_sh_ky where id in ('.implode(',',array_fill(0,count($ids),'?')).') order by id for update',$ids);
		if(count($periods)!==count($ids))throw new RuntimeException('Một hoặc nhiều kỳ không còn tồn tại; chưa xóa dữ liệu.');
		if(!dt_backup_tables(array('dt_sat_hach','dt_sh_ky','dt_sh_import_row'),'del_sh_period'))throw new RuntimeException('Không thể sao lưu; chưa xóa kỳ sát hạch.');
		$d->startTransaction();
		try
		{
			foreach(array_chunk($ids,500) as $chunk)
			{
				$marks=implode(',',array_fill(0,count($chunk),'?'));
				$results=dt_sathach_summary_query('select id from #_dt_sat_hach where id_ky in ('.$marks.') for update',$chunk);
				$resultIds=array_map(function($row){return (int)$row['id'];},$results);
				foreach(array_chunk($resultIds,500) as $resultChunk)
				{
					$resultMarks=implode(',',array_fill(0,count($resultChunk),'?'));
					dt_sathach_summary_query('update #_dt_sh_import_row set id_ketqua=0 where id_ketqua in ('.$resultMarks.')',$resultChunk);
				}
				dt_sathach_summary_query('delete from #_dt_sat_hach where id_ky in ('.$marks.')',$chunk);
				dt_sathach_summary_query('delete from #_dt_sh_ky where id in ('.$marks.')',$chunk);
			}
			if($d->commit()===false)throw new RuntimeException('Không thể hoàn tất xóa kỳ sát hạch.');
		}
		catch(Exception $error){$d->rollback();throw $error;}
		return $periods;
	}
	finally{$d->rawQuery("select release_lock('bachviet_sh_summary_import')");}
}

function dt_sathach_delete_period()
{
	global $func;
	$back='index.php?com=daotao&act=sathach';
	if(empty($_SESSION['dt_sh_csrf']) || !hash_equals($_SESSION['dt_sh_csrf'],(string)($_POST['csrf'] ?? '')))$func->transfer('Phiên thao tác không hợp lệ.',$back,false);
	$id=(int)($_POST['id_ky'] ?? 0);
	if($id<=0)$func->transfer('Ngày sát hạch không tồn tại.',$back,false);
	try{$periods=dt_sathach_delete_periods_action(array($id));}
	catch(Exception $error){$func->transfer($error->getMessage(),$back,false);}
	$period=$periods[0];dt_audit('delete','sh_ky',1,'id='.$id.' '.$period['ngay'].' '.$period['loai']);
	$func->transfer('Đã xóa ngày sát hạch và toàn bộ kết quả đi kèm.',$back);
}

function dt_sathach_delete_periods_selected()
{
	global $func;
	$back='index.php?com=daotao&act=sathach';
	if(empty($_SESSION['dt_sh_csrf']) || !hash_equals($_SESSION['dt_sh_csrf'],(string)($_POST['csrf'] ?? '')))$func->transfer('Phiên thao tác không hợp lệ.',$back,false);
	$ids=array_values(array_unique(array_filter(array_map('intval',(array)($_POST['id_kys']??array())),function($id){return $id>0;})));
	if(!$ids)$func->transfer('Chọn ít nhất một kỳ để xóa.',$back,false);
	try{$periods=dt_sathach_delete_periods_action($ids);}
	catch(Exception $error){$func->transfer($error->getMessage(),$back,false);}
	$labels=array();foreach($periods as $period)$labels[]=$period['ngay'].'/'.$period['loai'];
	dt_audit('delete','sh_ky',count($periods),'ids='.implode(',', $ids));
	$func->transfer('Đã xóa '.count($periods).' kỳ sát hạch và toàn bộ kết quả đi kèm. Dữ liệu nguồn import được giữ, liên kết kết quả đã xóa được gỡ.',$back);
}

function dt_sathach_note_selected()
{
	global $d,$func;
	$idKy=(int)($_POST['id_ky'] ?? 0);
	$back='index.php?com=daotao&act=sathach'.($idKy?'&id_ky='.$idKy:'');
	if(empty($_SESSION['dt_sh_csrf']) || !hash_equals($_SESSION['dt_sh_csrf'],(string)($_POST['csrf'] ?? '')))
		$func->transfer('Phiên thao tác không hợp lệ.',$back,false);
	$ids=array_values(array_unique(array_filter(array_map('intval',(array)($_POST['ids'] ?? array())))));
	$note=trim((string)($_POST['ghi_chu'] ?? ''));
	if(!$ids || $note==='' || mb_strlen($note,'UTF-8')>255) $func->transfer('Chọn học viên và nhập ghi chú (tối đa 255 ký tự).',$back,false);
	if(!dt_backup_tables(array('dt_sat_hach'),'note_sh_rows')) $func->transfer('Không thể sao lưu. Chưa cập nhật ghi chú.',$back,false);
	$params=array_merge(array($note),$ids);$where='id in ('.implode(',',array_fill(0,count($ids),'?')).')';
	if($idKy){$where.=' and id_ky=?';$params[]=$idKy;}
	$d->rawQuery('update #_dt_sat_hach set ghi_chu=? where '.$where,$params);
	$ok=$d->getLastErrorCode()==='00000';
	if(!$ok) $func->transfer('Không thể cập nhật ghi chú.',$back,false);
	dt_audit('update','sat_hach_note',count($ids),'id_ky='.$idKy);
	$func->transfer('Đã ghi chú cho '.count($ids).' học viên.',$back);
}

/* Màn import. */
function dt_sathach_upload_form()
{
	global $d;
	dt_sathach_period_options();
}

/* Chỉ giữ một luồng import: file tổng hợp, tự chia theo ngày và loại xe. */
function dt_sathach_upload_excel()
{
	dt_sathach_summary_import();
}

/* Xuất Excel kết quả sát hạch. */
function dt_sathach_export()
{
	global $d, $func, $items;
	if(!dt_req('id_ky') && !dt_req('keyword') && !dt_req('ngay_sh')) $func->transfer('Chọn kỳ/ngày sát hạch hoặc học viên để xuất.', 'index.php?com=daotao&act=sathach', false);
	dt_sathach_list();
	$rows = $items;
	$headers = array('STT','Mã HV','Họ và tên','Năm sinh','CCCD','Hạng','Mã khóa','Giáo viên','Ngày sát hạch','Điểm LT','LT','Điểm MP','MP','Điểm Hình','Hình','Điểm Đường','Đường','Chữ chi','Điểm Chữ chi','Kết luận','Ghi chú');
	$out = array(); $stt = 1;
	foreach($rows as $r)
		$out[] = array($stt++, $r['ma_hv'], $r['hoten'], $r['nam_sinh'], $r['cccd'], $r['hang'], $r['ma_khoa'], isset($r['gv_roster']) ? $r['gv_roster'] : '', dt_sathach_display_date($r['ngay_sh']),
			isset($r['diem_lt'])?$r['diem_lt']:'', isset($r['lt'])?$r['lt']:'', isset($r['diem_mp'])?$r['diem_mp']:'', isset($r['mp'])?$r['mp']:'',
			isset($r['diem_hinh'])?$r['diem_hinh']:'', isset($r['hinh'])?$r['hinh']:'', isset($r['diem_duong'])?$r['diem_duong']:'', isset($r['duong'])?$r['duong']:'',
			isset($r['chu_chi'])?$r['chu_chi']:'', isset($r['diem_chu_chi'])?$r['diem_chu_chi']:'', (isset($r['ket_luan'])&&$r['ket_luan']!=='')?$r['ket_luan']:$r['ket_qua'], $r['ghi_chu']);
	dt_xlsx_simple('Ket qua sat hach', 'ket-qua-sat-hach-'.date('Ymd_His').'.xlsx', $headers, $out, array(1, 4));
}

/* Tra cứu theo ngày sát hạch hoặc họ tên chính xác. */
function dt_sathach_tracuu()
{
	global $d, $items, $total_items, $tc_filters;
	$tc_filters = array(
		'hoten' => trim(dt_req('hoten')),
		'ngay_sh' => dt_req('ngay_sh'),
	);
	$items = array(); $total_items = 0;
	$has = ($tc_filters['hoten'] !== '' || $tc_filters['ngay_sh'] !== '');
	if(!$has) return;
	$where = "1"; $params = array();
	if($tc_filters['ngay_sh'] !== '') { $where .= ' and (ngay_sh=? or ngay_sh=?)'; $params[]=$tc_filters['ngay_sh']; $params[]=date('d/m/Y',strtotime($tc_filters['ngay_sh'])); }
	if($tc_filters['hoten'] !== '') { $where .= " and s.hoten = ?"; $params[] = $tc_filters['hoten']; }
	$items = dt_sathach_query_rows($where, $params);
	dt_sathach_sort_by_name($items);
	$items = array_slice($items, 0, 500);
	$total_items = count($items);
}
