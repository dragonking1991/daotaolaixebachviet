<?php
if(!defined('SOURCES')) die("Error");
require_once SOURCES.'daotao/htkh_documents.php';

/* ============================================================
 * Mô đun Số giấy HTKH (giấy chứng nhận hoàn thành khóa học).
 * Cập nhật số giấy theo học viên: nhập tay hoặc import Excel.
 * ============================================================ */

/* Quy đổi hạng khóa sang hạng in trên giấy HTKH (Update 6):
 * B11/B1 → B; C1 → C1; C → C; CE → CE; (D → CE). */
function dt_htkh_hang_quydoi($hang)
{
	$h = dt_norm_hang($hang);
	if($h === 'B11' || $h === 'B1') return 'B';
	if($h === 'C1') return 'C1';
	if($h === 'C') return 'C';
	if($h === 'CE') return 'CE';
	$u = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', (string)$hang));
	if(strpos($u, 'D') !== false) return 'CE';
	return $h !== '' ? $h : (string)$hang;
}

/* Số giấy tự sinh theo mẫu: "<Mã HV>-<hạng quy đổi>" (vd 79044-20260805-000014-CE).
 * Nối liền bằng dấu "-", không khoảng trắng; cho phép sửa tay sau đó. */
function dt_htkh_so_giay($maHv, $hang)
{
	$maHv = trim((string)$maHv);
	if($maHv === '') return '';
	return dt_htkh_norm_so_giay($maHv.'-'.dt_htkh_hang_quydoi($hang));
}

/* Các kiểu nâng hạng lên CE (học viên có thể nâng từ C / D / D2). */
function dt_htkh_ce_options()
{
	return array('c' => 'C lên CE', 'd' => 'D lên CE', 'd2' => 'D2 lên CE');
}
function dt_htkh_ce_key($raw)
{
	$raw = trim((string)$raw);
	return array_key_exists($raw, dt_htkh_ce_options()) ? $raw : '';
}
function dt_htkh_ce_text($ce)
{
	$o = dt_htkh_ce_options();
	$ce = trim((string)$ce);
	if(isset($o[$ce])) return $o[$ce];      // nhận key (c/d/d2)
	if(in_array($ce, $o, true)) return $ce; // nhận sẵn chuỗi mô tả
	return $o['c'];                         // mặc định C lên CE
}

/* Mô tả hạng đào tạo in trên giấy xác nhận (theo file mẫu "Thông tin giấy xác nhận").
 * $ce: với hạng CE, chọn kiểu nâng hạng (c/d/d2) do người cấp giấy chỉ định. */
function dt_htkh_hang_text($hang, $ce = '')
{
	$h = dt_norm_hang($hang);
	if($h === 'B11') return 'B - Học xe chuyển số tự động (bao gồm xe điện)';
	if($h === 'B1')  return 'B - Học xe chuyển số cơ khí (số sàn)';
	if($h === 'C1')  return 'C1';
	if($h === 'C')   return 'B lên C';
	if($h === 'CE')  return dt_htkh_ce_text($ce);
	return (string)$hang;
}

/* Chức danh người ký trên giấy (lưu key, in ra nhiều dòng). */
function dt_htkh_chucdanh_options()
{
	return array('kt_pgd' => "KT. GIÁM ĐỐC\nPHÓ GIÁM ĐỐC", 'gd' => 'GIÁM ĐỐC');
}
function dt_htkh_chucdanh_key($raw)
{
	return array_key_exists((string)$raw, dt_htkh_chucdanh_options()) ? (string)$raw : 'kt_pgd';
}
function dt_htkh_chucdanh_text($key)
{
	$o = dt_htkh_chucdanh_options();
	return isset($o[$key]) ? $o[$key] : $o['kt_pgd'];
}

/* Danh sách học viên của 1 khóa kèm số giấy HTKH. */
function dt_htkh_list()
{
	global $d, $items, $ds_khoa, $id_khoa_sel, $total_items, $ds_ky, $htkh_ky_khoa, $htkh_ky_passed, $htkh_schedule;
	$ds_khoa = dt_khoa_options();
	$ds_ky = $d->rawQuery("select id, ten, ngay_kt, (select count(*) from #_dt_ky_thi_hv h where h.id_ky = ky.id and h.co_ket_qua = 1) as so_kq from #_dt_ky_thi ky order by ky.ngay_kt desc, ky.id desc");
	$idKy = (int)dt_req('id_ky', 0);
	$htkh_ky_khoa = $idKy ? dt_htkh_ky_khoa_options($idKy) : array();
	$htkh_ky_passed = array();
	$htkh_schedule=array('so_ngay'=>'','tu_ngay'=>'','den_ngay'=>'');
	if(empty($_SESSION['dt_htkh_schedule_csrf']))$_SESSION['dt_htkh_schedule_csrf']=bin2hex(random_bytes(24));
	if($idKy)
	{
		list($ky, $htkh_ky_passed) = dt_htkh_ky_selection();
		if(dt_req('ky_khoa')!=='')$htkh_schedule=dt_htkh_schedule_get($idKy,dt_req('ky_khoa'));
	}
	$id_khoa_sel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$items = array(); $total_items = 0;
	$kw = dt_mb_lower(dt_req('keyword'));
	if(!$id_khoa_sel && $kw === '') return;

	if($id_khoa_sel)
		$hvs = $d->rawQuery("select h.id, h.cccd, h.ma_hv, h.hoten, h.hang, h.id_khoa, h.gv_hoten from #_dt_hocvien h where h.id_khoa = ? order by substring_index(h.hoten, ' ', -1) asc, h.hoten asc", array($id_khoa_sel));
	else
	{
		$like = '%'.dt_req('keyword').'%';
		$hvs = $d->rawQuery("select h.id, h.cccd, h.ma_hv, h.hoten, h.hang, h.id_khoa, h.gv_hoten from #_dt_hocvien h where (h.hoten like ? or h.cccd like ? or h.ma_hv like ?) order by substring_index(h.hoten, ' ', -1) asc, h.hoten asc limit 0,200", array($like, $like, $like));
	}
	$ids = array();
	foreach($hvs as $hv) $ids[(int)$hv['id_khoa']] = true;
	$byCccd = array();
	if($ids)
	{
		$in = implode(',', array_map('intval', array_keys($ids)));
		foreach($d->rawQuery("select id_khoa, cccd, so_giay, so_quyet_dinh, ngay_cap, ghi_chu, hang_giay, nguoi_ky, ngay_ky from #_dt_htkh where id_khoa in ($in)") as $r)
			$byCccd[$r['id_khoa'].'|'.$r['cccd']] = $r;
	}
	foreach($hvs as $hv)
	{
		if($kw !== '' && strpos(dt_mb_lower($hv['hoten'].' '.$hv['cccd'].' '.$hv['ma_hv']), $kw) === false) continue;
		$hv['htkh'] = isset($byCccd[$hv['id_khoa'].'|'.$hv['cccd']]) ? $byCccd[$hv['id_khoa'].'|'.$hv['cccd']] : null;
		$items[] = $hv;
	}
	$total_items = count($items);
}

/* Lưu số giấy HTKH cho 1 học viên (nhập tay). */
function dt_htkh_save()
{
	global $d, $func;
	$idKhoa = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$cccd = isset($_POST['cccd']) ? dt_normalize_cccd($_POST['cccd']) : '';
	$back = "index.php?com=daotao&act=htkh&id_khoa=".$idKhoa;
	if(!$idKhoa || $cccd === '') $func->transfer("Thiếu dữ liệu học viên", $back, false);
	$hv = dt_find_hocvien_by_cccd($cccd, $idKhoa);
	if(!$hv) $func->transfer("Học viên không thuộc khóa", $back, false);

	$soGiay = dt_htkh_norm_so_giay($_POST['so_giay'] ?? '');
	if($soGiay === '') $soGiay = dt_htkh_so_giay($hv['ma_hv'], $hv['hang']);
	$soQuyetDinh=trim((string)($_POST['so_quyet_dinh']??''));
	if(mb_strlen($soQuyetDinh,'UTF-8')>100)$func->transfer('Số quyết định tối đa 100 ký tự.',$back,false);
	$data = array(
		'id_khoa' => $idKhoa, 'cccd' => $hv['cccd'], 'ma_hv' => $hv['ma_hv'],
		'so_giay' => htmlspecialchars($soGiay, ENT_QUOTES, 'UTF-8'),
		'so_quyet_dinh' => $soQuyetDinh,
		'hang_giay' => dt_htkh_hang_quydoi($hv['hang']),
		'ngay_cap' => htmlspecialchars(trim($_POST['ngay_cap'] ?? ''), ENT_QUOTES, 'UTF-8'),
		'ghi_chu' => htmlspecialchars(trim($_POST['ghi_chu'] ?? ''), ENT_QUOTES, 'UTF-8'),
		'nguoi_nhap' => dt_username(),
	);
	$exist = $d->rawQueryOne("select id from #_dt_htkh where id_khoa = ? and cccd = ? limit 0,1", array($idKhoa, $hv['cccd']));
	if($exist && $exist['id']) { $d->where('id', $exist['id']); $d->update('dt_htkh', $data); }
	else { $data['ngaytao'] = time(); $d->insert('dt_htkh', $data); }
	dt_audit('update', 'htkh', 1, 'cccd='.$hv['cccd']);
	$func->transfer("Đã lưu số giấy HTKH cho ".$hv['hoten'], $back);
}

/* Cấp giấy HTKH hàng loạt: sinh số giấy + ghi người ký/ngày ký cho HV đủ điều kiện (hoặc tất cả). */
function dt_htkh_cap_giay()
{
	global $d, $func;
	$idKhoa = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$back = "index.php?com=daotao&act=htkh&id_khoa=".$idKhoa;
	if(!$idKhoa) $func->transfer("Vui lòng chọn khóa", $back, false);
	$nguoiKy = trim($_POST['nguoi_ky'] ?? '');
	$ngayKy = trim($_POST['ngay_ky'] ?? '');
	if($ngayKy === '') $ngayKy = date('d/m/Y');
	$chiDat = empty($_POST['tat_ca']);
	$chucDanh = dt_htkh_chucdanh_key($_POST['chuc_danh'] ?? '');
	$hangCe = dt_htkh_ce_key($_POST['hang_ce'] ?? '');
	$soQuyetDinh=trim((string)($_POST['so_quyet_dinh']??''));
	if(mb_strlen($soQuyetDinh,'UTF-8')>100)$func->transfer('Số quyết định tối đa 100 ký tự.',$back,false);
	$hvs = $d->rawQuery("select * from #_dt_hocvien where id_khoa = ? order by substring_index(hoten, ' ', -1) asc, hoten asc", array($idKhoa));
	$existing = array();
	foreach($d->rawQuery("select cccd, so_giay, so_quyet_dinh from #_dt_htkh where id_khoa = ?", array($idKhoa)) as $r) $existing[$r['cccd']] = $r;
	$d->startTransaction();
	$n = 0;
	foreach($hvs as $hv)
	{
		if($chiDat) { $s = dt_student_summary($hv); if(empty($s['du_dieu_kien'])) continue; }
		$ex = isset($existing[$hv['cccd']]) ? $existing[$hv['cccd']] : null;
		$soGiay = ($ex && trim((string)$ex['so_giay']) !== '') ? $ex['so_giay'] : dt_htkh_so_giay($hv['ma_hv'], $hv['hang']);
		$data = array(
			'id_khoa' => $idKhoa, 'cccd' => $hv['cccd'], 'ma_hv' => $hv['ma_hv'],
			'so_giay' => $soGiay, 'so_quyet_dinh' => $soQuyetDinh!==''?$soQuyetDinh:(string)($ex['so_quyet_dinh']??''), 'hang_giay' => dt_htkh_hang_quydoi($hv['hang']), 'hang_ce' => $hangCe,
			'ngay_cap' => $ngayKy, 'ngay_ky' => $ngayKy, 'nguoi_ky' => $nguoiKy, 'chuc_danh' => $chucDanh, 'nguoi_nhap' => dt_username(),
		);
		$row = $d->rawQueryOne("select id from #_dt_htkh where id_khoa = ? and cccd = ? limit 0,1", array($idKhoa, $hv['cccd']));
		if($row && $row['id']) { $d->where('id', $row['id']); $d->update('dt_htkh', $data); }
		else { $data['ngaytao'] = time(); $d->insert('dt_htkh', $data); }
		$n++;
	}
	$d->commit();
	dt_audit('capgiay', 'htkh', $n, 'khoa='.$idKhoa.' nguoi_ky='.$nguoiKy);
	$func->transfer("Đã cấp giấy HTKH cho $n học viên".($chiDat ? " đủ điều kiện" : ""), $back);
}

/* Học viên THI ĐẠT trong 1 kỳ kiểm tra (ngày kiểm tra), gồm nhiều khóa + dữ liệu cũ nhập tay. */
function dt_htkh_khoa_key($row)
{
	if((int)$row['id_khoa'] > 0) return 'id:'.(int)$row['id_khoa'];
	return 'cu:'.hash('sha256', json_encode(array(trim((string)$row['ma_khoa']), trim((string)$row['ten_khoa']))));
}

function dt_htkh_ky_khoa_options($idKy)
{
	global $d;
	$options = array();
	foreach($d->rawQuery("select id_khoa, ma_khoa, ten_khoa,count(*) as so_hoc_vien from #_dt_ky_thi_hv
		where id_ky = ? group by id_khoa,ma_khoa,ten_khoa order by ma_khoa,ten_khoa", array((int)$idKy)) as $row)
	{
		$options[dt_htkh_khoa_key($row)] = course_option_label($row);
	}
	return $options;
}

function dt_htkh_ket_qua_dat($row)
{
	$text = dt_strip_diacritics(trim((string)($row['ket_qua_tong'] ?? '').' '.(string)($row['ket_luan'] ?? '')));
	if($text === '' || preg_match('/(^|[^a-z])(rot|truot|vang|khong|chua)([^a-z]|$)/', $text)) return false;
	return (bool)preg_match('/(^|[^a-z])(dat|dau)([^a-z]|$)/', $text);
}

function dt_htkh_ky_passed($idKy, $khoa = '')
{
	global $d;
	$rows = $d->rawQuery("select id_khoa, cccd, ma_hv, hoten, nam_sinh, hang, ma_khoa, ten_khoa, ket_qua_tong, ket_luan from #_dt_ky_thi_hv where id_ky = ? and co_ket_qua = 1", array((int)$idKy));
	$out = array();
	foreach($rows as $r)
	{
		if($khoa !== '' && dt_htkh_khoa_key($r) !== $khoa) continue;
		if(!dt_htkh_ket_qua_dat($r)) continue;
		$out[] = $r;
	}
	usort($out,function($left,$right){
		$leftCourse=trim((string)$left['ma_khoa']);if($leftCourse==='')$leftCourse=trim((string)$left['ten_khoa']);
		$rightCourse=trim((string)$right['ma_khoa']);if($rightCourse==='')$rightCourse=trim((string)$right['ten_khoa']);
		if($leftCourse===''&&$rightCourse===''){$courseCompare=(int)$left['id_khoa']<=>(int)$right['id_khoa'];}
		else $courseCompare=strcmp(dt_strip_diacritics($leftCourse),dt_strip_diacritics($rightCourse));
		if($courseCompare!==0)return $courseCompare;
		$leftName=preg_split('/\s+/u',trim((string)$left['hoten']));$rightName=preg_split('/\s+/u',trim((string)$right['hoten']));
		$givenCompare=strcmp(dt_strip_diacritics(end($leftName)),dt_strip_diacritics(end($rightName)));
		if($givenCompare!==0)return $givenCompare;
		$fullCompare=strcmp(dt_strip_diacritics($left['hoten']),dt_strip_diacritics($right['hoten']));
		if($fullCompare!==0)return $fullCompare;
		return strcmp((string)$left['cccd'],(string)$right['cccd']);
	});
	return $out;
}

function dt_htkh_ky_back()
{
	return 'index.php?'.http_build_query(array('com' => 'daotao', 'act' => 'htkh', 'id_ky' => (int)dt_req('id_ky', 0), 'ky_khoa' => dt_req('ky_khoa')));
}

function dt_htkh_schedule_key($idKy,$courseKey)
{
	return 'htkh_schedule_'.(int)$idKy.'_'.hash('sha256',(string)$courseKey);
}

function dt_htkh_schedule_get($idKy,$courseKey)
{
	global $d;
	$row=$d->rawQueryOne('select sval from #_dt_setting where skey=? limit 1',array(dt_htkh_schedule_key($idKy,$courseKey)));
	if($d->getLastErrorCode()!=='00000')throw new RuntimeException('Không đọc được thời gian học HTKH.');
	$data=$row?json_decode($row['sval'],true):array();
	return array_merge(array('so_ngay'=>'','tu_ngay'=>'','den_ngay'=>''),is_array($data)?$data:array());
}

function dt_htkh_schedule_validate($input)
{
	$days=trim((string)($input['so_ngay']??''));
	if(!preg_match('/^[1-9][0-9]{0,4}$/',$days)||(int)$days>10000)throw new InvalidArgumentException('Số ngày học phải là số nguyên từ 1 đến 10000.');
	$values=array('so_ngay'=>(int)$days);
	foreach(array('tu_ngay','den_ngay') as $field)
	{
		$value=trim((string)($input[$field]??''));$date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);
		if(!$date||$date->format('Y-m-d')!==$value)throw new InvalidArgumentException('Nhập đủ từ ngày/đến ngày hợp lệ.');
		$values[$field]=$value;
	}
	if($values['den_ngay']<$values['tu_ngay'])throw new InvalidArgumentException('Đến ngày không được trước từ ngày.');
	return $values;
}

function dt_htkh_schedule_save()
{
	global $d,$func;
	$back=dt_htkh_ky_back();
	if(empty($_SESSION['dt_htkh_schedule_csrf'])||!hash_equals($_SESSION['dt_htkh_schedule_csrf'],(string)($_POST['csrf']??'')))$func->transfer('Phiên thao tác không hợp lệ.',$back,false);
	list($ky)=dt_htkh_ky_selection();$courseKey=dt_req('ky_khoa');
	if($courseKey==='')$func->transfer('Chọn một khóa trong kỳ để cập nhật thời gian học.',$back,false);
	try{$data=dt_htkh_schedule_validate($_POST);}
	catch(InvalidArgumentException $error){$func->transfer($error->getMessage(),$back,false);return;}
	$d->rawQuery('insert into #_dt_setting (skey,sval) values (?,?) on duplicate key update sval=values(sval)',array(dt_htkh_schedule_key($ky['id'],$courseKey),json_encode($data)));
	if($d->getLastErrorCode()!=='00000')$func->transfer('Không lưu được thời gian học; vui lòng thử lại.',$back,false);
	dt_audit('update','htkh_schedule',1,'ky='.(int)$ky['id'].' course='.$courseKey.' '.json_encode($data));
	$func->transfer('Đã lưu thời gian học cho khóa trong kỳ. Giấy xuất/in sẽ dùng thông tin này.',$back);
}

function dt_htkh_ky_selection()
{
	global $d, $func;
	$idKy = (int)dt_req('id_ky', 0);
	$back = 'index.php?com=daotao&act=htkh';
	if(!$idKy) $func->transfer('Vui lòng chọn ngày kiểm tra', $back, false);
	$ky = $d->rawQueryOne("select id, ten, ngay_kt from #_dt_ky_thi where id = ? limit 0,1", array($idKy));
	if(!$ky) $func->transfer('Kỳ kiểm tra không tồn tại', $back, false);
	$khoa = dt_req('ky_khoa');
	if($khoa !== '' && !array_key_exists($khoa, dt_htkh_ky_khoa_options($idKy)))
		$func->transfer('Khóa đã chọn không thuộc kỳ kiểm tra', $back.'&id_ky='.$idKy, false);
	return array($ky, dt_htkh_ky_passed($idKy, $khoa));
}

/* Cấp giấy HTKH theo NGÀY KIỂM TRA (kỳ): cho toàn bộ HV thi đạt trong kỳ (nhiều khóa + dữ liệu cũ). */
function dt_htkh_cap_giay_ky()
{
	global $d, $func;
	list($ky, $passed) = dt_htkh_ky_selection();
	$idKy = (int)$ky['id'];
	$back = dt_htkh_ky_back();
	$nguoiKy = trim($_POST['nguoi_ky'] ?? '');
	$ngayKy = trim($_POST['ngay_ky'] ?? '');
	$date = DateTime::createFromFormat('!d/m/Y', $ngayKy);
	if($nguoiKy === '' || mb_strlen($nguoiKy, 'UTF-8') > 255 || !$date || $date->format('d/m/Y') !== $ngayKy)
		$func->transfer('Vui lòng nhập người ký và ngày ký hợp lệ (dd/mm/yyyy)', $back, false);
	$chucDanh = dt_htkh_chucdanh_key($_POST['chuc_danh'] ?? '');
	$hangCe = dt_htkh_ce_key($_POST['hang_ce'] ?? '');
	$soQuyetDinh=trim((string)($_POST['so_quyet_dinh']??''));
	if(mb_strlen($soQuyetDinh,'UTF-8')>100)$func->transfer('Số quyết định tối đa 100 ký tự.',$back,false);

	if(!$passed) $func->transfer("Bộ lọc này chưa có học viên thi đạt (đã import kết quả).", $back, false);

	$d->startTransaction();
	$n = 0;
	foreach($passed as $r)
	{
		$idKhoa = (int)$r['id_khoa'];
		$cccd = $r['cccd'];
		$base = trim((string)$r['ma_hv']) !== '' ? $r['ma_hv'] : $cccd;
		$exist = $d->rawQueryOne("select id, so_giay, so_quyet_dinh from #_dt_htkh where id_khoa = ? and cccd = ? limit 0,1", array($idKhoa, $cccd));
		$soGiay = ($exist && trim((string)$exist['so_giay']) !== '') ? $exist['so_giay'] : dt_htkh_so_giay($base, $r['hang']);
		$data = array(
			'id_khoa' => $idKhoa, 'cccd' => $cccd, 'ma_hv' => (string)$r['ma_hv'],
			'so_giay' => $soGiay, 'so_quyet_dinh' => $soQuyetDinh!==''?$soQuyetDinh:(string)($exist['so_quyet_dinh']??''), 'hang_giay' => dt_htkh_hang_quydoi($r['hang']), 'hang_ce' => $hangCe,
			'ngay_cap' => $ngayKy, 'ngay_ky' => $ngayKy, 'nguoi_ky' => $nguoiKy, 'chuc_danh' => $chucDanh, 'nguoi_nhap' => dt_username(),
		);
		if($exist && $exist['id']) { $d->where('id', $exist['id']); $saved = $d->update('dt_htkh', $data); }
		else { $data['ngaytao'] = time(); $saved = $d->insert('dt_htkh', $data); }
		if(!$saved)
		{
			$d->rollback();
			$func->transfer('Không thể lưu giấy HTKH; chưa cấp giấy cho danh sách đã chọn.', $back, false);
		}
		$n++;
	}
	if(!$d->commit()) $func->transfer('Không thể hoàn tất cấp giấy HTKH.', $back, false);
	dt_audit('capgiay_ky', 'htkh', $n, 'ky='.$idKy.' khoa='.dt_req('ky_khoa').' nguoi_ky='.$nguoiKy);
	$func->transfer("Đã cấp giấy HTKH cho $n học viên thi đạt trong kỳ kiểm tra", $back);
}

/* In giấy HTKH đồng loạt theo NGÀY KIỂM TRA (kỳ) cho HV thi đạt đã cấp giấy. */
function dt_htkh_print_ky()
{
	list($ky, $documents) = dt_htkh_ky_documents();
	if(ob_get_length()) ob_end_clean();
	header('Content-Type: text/html; charset=utf-8');
	echo dt_htkh_documents_html($documents, dt_htkh_ky_back());
	exit();
}

/* Đổi ngày (Y-m-d hoặc d/m/Y) sang "ngày DD tháng MM năm YYYY"; rỗng nếu không hợp lệ. */
function dt_htkh_ngay_text($value)
{
	$t = dt_htkh_parse_date($value);
	return $t === null ? '' : 'ngày '.date('d', $t).' tháng '.date('m', $t).' năm '.date('Y', $t);
}

/* Phân tích ngày từ Y-m-d hoặc d/m/Y (loại 0000-00-00); trả timestamp hoặc null. */
function dt_htkh_parse_date($value)
{
	$value = trim((string)$value);
	if($value === '' || strpos($value, '0000-00-00') === 0) return null;
	foreach(array('Y-m-d', 'd/m/Y') as $fmt)
	{
		$dt = DateTime::createFromFormat('!'.$fmt, $value);
		if($dt && $dt->format($fmt) === $value) return $dt->getTimestamp();
	}
	$ts = strtotime($value);
	return $ts === false ? null : $ts;
}

/* Số ngày học tính từ khai giảng đến mãn khóa (tính cả hai đầu); rỗng nếu thiếu ngày. */
function dt_htkh_so_ngay($ngayKhaiGiang, $ngayManHoa)
{
	$a = dt_htkh_parse_date($ngayKhaiGiang);
	$b = dt_htkh_parse_date($ngayManHoa);
	if($a === null || $b === null || $b < $a) return '';
	return (string)(int)(floor(($b - $a) / 86400) + 1).' ngày';
}

/* Gom dữ liệu 1 giấy xác nhận theo đúng các trường cố định của mẫu (thông tư). */
function dt_htkh_birth_date($value)
{
	$value=trim((string)$value);
	if($value===''||$value==='0000-00-00')return '';
	if(preg_match('/^\d{4}$/',$value)&&(int)$value>=1900&&(int)$value<=2100)return $value;
	if(is_numeric($value))
	{
		$serial=(float)$value;
		if($serial<1||$serial>73050||(int)$serial===60)return '';
		$base=new DateTimeImmutable($serial<60?'1899-12-31':'1899-12-30',new DateTimeZone('UTC'));
		return $base->modify('+'.(int)$serial.' days')->format('d/m/Y');
	}
	if(preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/',$value,$parts))
	{
		$year=(int)$parts[1];$month=(int)$parts[2];$day=(int)$parts[3];
	}
	elseif(preg_match('/^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{4})$/',$value,$parts))
	{
		$day=(int)$parts[1];$month=(int)$parts[2];$year=(int)$parts[3];
	}
	else return '';
	return checkdate($month,$day,$year)?sprintf('%02d/%02d/%04d',$day,$month,$year):'';
}

function dt_htkh_build_document($so_giay, $hoten, $ngaysinh, $hang, $hang_ce, $khoa, $ngay_khaigiang, $ngay_manhoa, $ngay_ky, $nguoi_ky, $chuc_danh, $so_quyet_dinh='', $so_ngay='')
{
	$tuNgay = dt_htkh_ngay_text($ngay_khaigiang);
	$denNgay = dt_htkh_ngay_text($ngay_manhoa);
	$tuDen = ($tuNgay !== '' && $denNgay !== '') ? 'Từ '.$tuNgay.' đến '.$denNgay : '';
	$ngayKyText = dt_htkh_ngay_text($ngay_ky);
	return array(
		'so_giay' => (string)$so_giay,
		'so_quyet_dinh' => html_entity_decode((string)$so_quyet_dinh,ENT_QUOTES,'UTF-8'),
		'hoten' => (string)$hoten,
		'ngaysinh' => dt_htkh_birth_date($ngaysinh),
		'hang' => dt_htkh_hang_text($hang, $hang_ce),
		'khoa' => (string)$khoa,
		'so_ngay' => $so_ngay!==''?(string)(int)$so_ngay.' ngày':dt_htkh_so_ngay($ngay_khaigiang, $ngay_manhoa),
		'tu_den' => $tuDen,
		'ngay_ky_text' => 'Tp.Hồ Chí Minh, '.($ngayKyText !== '' ? $ngayKyText : 'ngày ... tháng ... năm ...'),
		'nguoi_ky' => html_entity_decode((string)$nguoi_ky, ENT_QUOTES, 'UTF-8'),
		'chuc_danh' => dt_htkh_chucdanh_text($chuc_danh),
	);
}

function dt_htkh_ky_documents()
{
	global $d, $func;
	list($ky, $passed) = dt_htkh_ky_selection();
	$documents = array();
	$khoaCache = array();
	$scheduleCache=array();
	foreach($passed as $r)
	{
		$idKhoa = (int)$r['id_khoa'];
		$h = $d->rawQueryOne("select so_giay, so_quyet_dinh, hang_giay, hang_ce, ngay_ky, nguoi_ky, ngay_cap, chuc_danh from #_dt_htkh where id_khoa = ? and cccd = ? limit 0,1", array($idKhoa, $r['cccd']));
		if(!$h || trim((string)$h['so_giay']) === '') continue; // chỉ in HV đã cấp giấy
		$tenKhoa = trim((string)($r['ten_khoa'] !== '' ? $r['ten_khoa'] : $r['ma_khoa']));
		$ngayKhaiGiang = ''; $ngayManHoa = '';
		if($idKhoa > 0)
		{
			if(!isset($khoaCache[$idKhoa])) $khoaCache[$idKhoa] = $d->rawQueryOne("select ma_khoa, ten_khoa, ngay_khaigiang, ngay_manhoa from #_dt_khoa where id = ? limit 0,1", array($idKhoa));
			$k = $khoaCache[$idKhoa];
			if($k)
			{
				if($tenKhoa === '') $tenKhoa = trim((string)($k['ten_khoa'] !== '' ? $k['ten_khoa'] : $k['ma_khoa']));
				$ngayKhaiGiang = $k['ngay_khaigiang']; $ngayManHoa = $k['ngay_manhoa'];
			}
		}
		$ngayKy = trim((string)$h['ngay_ky']) !== '' ? $h['ngay_ky'] : $h['ngay_cap'];
		$courseKey=dt_htkh_khoa_key($r);
		if(!isset($scheduleCache[$courseKey]))$scheduleCache[$courseKey]=dt_htkh_schedule_get($ky['id'],$courseKey);
		$schedule=$scheduleCache[$courseKey];$manualDays='';
		if($schedule['so_ngay']!==''){$ngayKhaiGiang=$schedule['tu_ngay'];$ngayManHoa=$schedule['den_ngay'];$manualDays=$schedule['so_ngay'];}
		$documents[] = dt_htkh_build_document($h['so_giay'], $r['hoten'], $r['nam_sinh'], $r['hang'], $h['hang_ce'], $tenKhoa, $ngayKhaiGiang, $ngayManHoa, $ngayKy, $h['nguoi_ky'], $h['chuc_danh'], $h['so_quyet_dinh'],$manualDays);
	}
	if(!$documents) $func->transfer('Bộ lọc này chưa có học viên đạt được cấp giấy. Hãy tạo giấy trước khi xuất hoặc in.', dt_htkh_ky_back(), false);
	return array($ky, $documents);
}

function dt_htkh_export_ky($format)
{
	list($ky, $documents) = dt_htkh_ky_documents();
	$filename = 'giay-xac-nhan-htkh-ky-'.(int)$ky['id'].'-'.date('Ymd_His').'.'.$format;
	if($format === 'xlsx')
	{
		$book = dt_htkh_documents_xlsx($documents);
		if(ob_get_length()) ob_end_clean();
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		PHPExcel_IOFactory::createWriter($book, 'Excel2007')->save('php://output');
	}
	else
	{
		dt_htkh_documents_docx_download($documents, $filename);
	}
	exit();
}

/* Màn import Excel. */
function dt_htkh_upload_form()
{
	global $d, $ds_khoa, $id_khoa_sel;
	$ds_khoa = dt_khoa_options();
	$id_khoa_sel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
}

/* Import số giấy HTKH từ Excel (ghép theo CCCD hoặc Mã HV). */
function dt_htkh_upload_excel()
{
	global $d, $func;
	$idKhoaSel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$back = "index.php?com=daotao&act=uploadHtkh".($idKhoaSel ? "&id_khoa=".$idKhoaSel : "");
	if(!isset($_FILES['file-excel']) || $_FILES['file-excel']['error'] != 0)
		dt_import_notice("Vui lòng chọn file Excel", $back, false);
	$file = $_FILES['file-excel'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	list($sheet, $highestRow, $highestCol) = dt_open_upload_sheet($file, $ext, $back);

	$aliases = array(
		'cccd' => array('cccd','socccd','socmt','socmnd'),
		'ma_hv' => array('mahv','mahocvien'),
		'so_giay' => array('sogiayhtkh','sogiay','sohtkh','sochungnhan','sogcn','sogiaychungnhan'),
		'so_quyet_dinh' => array('soquyetdinh','soqd','soquyetdinhcapgiay'),
		'ngay_cap' => array('ngaycap','ngaycapgiay'),
		'ghi_chu' => array('ghichu'),
	);
	$contains = array(
		'cccd' => array('cccd','cmt'),
		'so_giay' => array('htkh','chungnhan','sogiay'),
		'so_quyet_dinh' => array('quyetdinh','soqd'),
		'ma_hv' => array('mahv','mahocvien'),
	);
	list($headerRow, $map, $score) = dt_find_header_row($sheet, $highestRow, $highestCol, $aliases, $contains);
	if($score <= 0 || !isset($map['so_giay']) || (!isset($map['cccd']) && !isset($map['ma_hv'])))
		dt_import_notice("Không nhận diện được cột 'Số giấy HTKH' và 'CCCD/Mã HV'.", $back, false);

	dt_backup_tables(array('dt_htkh'), 'imp_htkh');
	$d->startTransaction();
	$ok = 0; $err = 0; $errMsgs = array(); $emptyStreak = 0;
	for($r = $headerRow + 1; $r <= $highestRow; $r++)
	{
		$row = dt_read_row($sheet, $r, $highestCol);
		$cccd = dt_normalize_cccd(dt_val($row, $map, 'cccd'));
		$maHv = dt_val($row, $map, 'ma_hv');
		$soGiay = dt_htkh_norm_so_giay(dt_val($row, $map, 'so_giay'));
		if($cccd === '' && $maHv === '') { continue; }
		$emptyStreak = 0;
		if($soGiay === '') continue;

		$hv = $cccd !== '' ? dt_find_hocvien_by_cccd($cccd, $idKhoaSel) : dt_find_hocvien_by_mahv($maHv, $idKhoaSel);
		if(!$hv)
		{
			$err++;
			if(count($errMsgs) < 12) $errMsgs[] = "Dòng $r: ".($cccd ?: $maHv)." chưa có trong danh sách học viên";
			continue;
		}
		$data = array(
			'id_khoa' => (int)$hv['id_khoa'], 'cccd' => $hv['cccd'], 'ma_hv' => $hv['ma_hv'],
			'so_giay' => $soGiay, 'ngay_cap' => dt_val($row, $map, 'ngay_cap'), 'ghi_chu' => dt_val($row, $map, 'ghi_chu'),
			'nguoi_nhap' => dt_username(),
		);
		if(isset($map['so_quyet_dinh']))$data['so_quyet_dinh']=dt_val($row,$map,'so_quyet_dinh');
		$exist = $d->rawQueryOne("select id from #_dt_htkh where id_khoa = ? and cccd = ? limit 0,1", array($hv['id_khoa'], $hv['cccd']));
		if($exist && $exist['id']) { $d->where('id', $exist['id']); $d->update('dt_htkh', $data); }
		else { $data['ngaytao'] = time(); $d->insert('dt_htkh', $data); }
		$ok++;
	}
	if($d->transactionFailed()) { $d->rollback(); dt_import_notice('Import thất bại khi ghi cơ sở dữ liệu; đã hoàn tác toàn bộ file, chưa lưu dòng nào. Vui lòng kiểm tra dữ liệu (độ dài, định dạng) rồi thử lại.', $back, false); }
	$d->commit();
	dt_audit('import', 'htkh', $ok, $file['name']);
	dt_log_import('htkh', $file['name'], $ok, $err);
	$msg = "Import số giấy HTKH: $ok học viên".($err ? ", $err lỗi" : "");
	if(!empty($errMsgs)) $msg .= " — ".implode('; ', $errMsgs);
	dt_import_notice($msg, "index.php?com=daotao&act=htkh".($idKhoaSel ? "&id_khoa=".$idKhoaSel : ""), ($ok > 0 || $err === 0));
}

/* Xuất Excel số giấy HTKH theo khóa. */
function dt_htkh_export()
{
	global $d, $func;
	$idKhoa = (int)dt_req('id_khoa', 0);
	if(!$idKhoa) $func->transfer('Vui lòng chọn khóa để xuất', 'index.php?com=daotao&act=htkh', false);
	$hvs = $d->rawQuery("select h.cccd, h.ma_hv, h.hoten, h.hang, h.gv_hoten from #_dt_hocvien h where h.id_khoa = ? order by substring_index(h.hoten, ' ', -1) asc, h.hoten asc", array($idKhoa));
	$byCccd = array();
	foreach($d->rawQuery("select cccd, so_giay, so_quyet_dinh, ngay_cap, ghi_chu from #_dt_htkh where id_khoa = ?", array($idKhoa)) as $r) $byCccd[$r['cccd']] = $r;
	$headers = array('STT','Mã HV','Họ và tên','CCCD','Hạng','Giáo viên','Số giấy HTKH','Số quyết định','Ngày cấp','Ghi chú');
	$out = array(); $stt = 1;
	foreach($hvs as $hv)
	{
		$h = isset($byCccd[$hv['cccd']]) ? $byCccd[$hv['cccd']] : null;
		$out[] = array($stt++, $hv['ma_hv'], $hv['hoten'], $hv['cccd'], $hv['hang'], $hv['gv_hoten'],
			$h ? $h['so_giay'] : '', $h ? $h['so_quyet_dinh'] : '', $h ? $h['ngay_cap'] : '', $h ? $h['ghi_chu'] : '');
	}
	dt_xlsx_simple('So giay HTKH', 'so-giay-htkh-'.date('Ymd_His').'.xlsx', $headers, $out, array(1, 3));
}

/* In giấy xác nhận HTKH hàng loạt (HTML, mỗi học viên 1 trang) — chỉ in HV đã được cấp giấy. */
function dt_htkh_print()
{
	global $d, $func;
	$idKhoa = (int)dt_req('id_khoa', 0);
	if(!$idKhoa) $func->transfer('Vui lòng chọn khóa để in giấy', 'index.php?com=daotao&act=htkh', false);
	$khoa = $d->rawQueryOne("select * from #_dt_khoa where id = ? limit 0,1", array($idKhoa));
	if(!$khoa) $func->transfer('Khóa không tồn tại', 'index.php?com=daotao&act=htkh', false);
	$hvs = $d->rawQuery("select h.cccd, h.ma_hv, h.hoten, h.hang, h.ngaysinh from #_dt_hocvien h where h.id_khoa = ? order by substring_index(h.hoten, ' ', -1) asc, h.hoten asc", array($idKhoa));
	$byCccd = array();
	foreach($d->rawQuery("select cccd, so_giay, so_quyet_dinh, hang_giay, hang_ce, ngay_ky, nguoi_ky, ngay_cap, chuc_danh from #_dt_htkh where id_khoa = ?", array($idKhoa)) as $r) $byCccd[$r['cccd']] = $r;

	$tenKhoa = trim((string)($khoa['ten_khoa'] !== '' ? $khoa['ten_khoa'] : $khoa['ma_khoa']));
	$documents = array();
	foreach($hvs as $hv)
	{
		$h = isset($byCccd[$hv['cccd']]) ? $byCccd[$hv['cccd']] : null;
		if(!$h || trim((string)$h['so_giay']) === '') continue;
		$ngayKy = trim((string)$h['ngay_ky']) !== '' ? $h['ngay_ky'] : $h['ngay_cap'];
		$documents[] = dt_htkh_build_document($h['so_giay'], $hv['hoten'], $hv['ngaysinh'], $hv['hang'], $h['hang_ce'], $tenKhoa, $khoa['ngay_khaigiang'], $khoa['ngay_manhoa'], $ngayKy, $h['nguoi_ky'], $h['chuc_danh'], $h['so_quyet_dinh']);
	}
	if(!$documents) $func->transfer('Chưa có học viên nào được cấp giấy. Hãy bấm "Cấp giấy" trước khi in.', 'index.php?com=daotao&act=htkh&id_khoa='.$idKhoa, false);

	if(ob_get_length()) ob_end_clean();
	header('Content-Type: text/html; charset=utf-8');
	echo dt_htkh_documents_html($documents, 'index.php?com=daotao&act=htkh&id_khoa='.(int)$idKhoa);
	exit();
}
