<?php
if(!defined('SOURCES')) die("Error");
require_once SOURCES.'daotao/sathach_summary.php';

function dt_sathach_pass_label($value)
{
	$value = dt_strip_diacritics(trim((string)$value));
	if(preg_match('/rot|truot|vang|khong/', $value)) return false;
	return $value === 'd' || strpos($value, 'dat') !== false;
}

/* ============================================================
 * Mô đun Kết quả sát hạch (kết quả thi GPLX chính thức).
 * Import kết quả sát hạch từ TTSH về để lưu trữ + tra cứu.
 * ============================================================ */

/* Danh sách kết quả sát hạch theo khóa / từ khóa. */
function dt_sathach_list()
{
	global $d, $items, $ds_khoa, $id_khoa_sel, $total_items, $sh_periods, $sh_period_sel;
	dt_sathach_period_options();
	$ds_khoa = dt_khoa_options();
	$id_khoa_sel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$items = array(); $total_items = 0;
	$kw = dt_mb_lower(dt_req('keyword'));
	$date = dt_req('ngay_sh');
	if($sh_period_sel || $date !== '')
	{
		$where = '1'; $params = array();
		if($sh_period_sel) { $where .= ' and id_ky=?'; $params[] = $sh_period_sel; }
		if($date !== '') { $where .= ' and (ngay_sh=? or ngay_sh=?)'; $params[] = $date; $params[] = date('d/m/Y', strtotime($date)); }
		if($id_khoa_sel) { $where .= ' and id_khoa=?'; $params[] = $id_khoa_sel; }
		if($kw !== '') { $where .= ' and (hoten like ? or cccd like ? or ma_hv like ?)'; $like='%'.dt_req('keyword').'%'; $params[]=$like; $params[]=$like; $params[]=$like; }
		$items = $d->rawQuery("select * from #_dt_sat_hach where $where order by hoten asc", $params);
		$total_items = count($items);
		return;
	}
	if(!$id_khoa_sel && $kw === '') return;

	if($id_khoa_sel)
		$items = $d->rawQuery("select * from #_dt_sat_hach where id_khoa = ? order by substring_index(hoten, ' ', -1) asc, hoten asc", array($id_khoa_sel));
	else
	{
		$like = '%'.dt_req('keyword').'%';
		$items = $d->rawQuery("select * from #_dt_sat_hach where (hoten like ? or cccd like ? or ma_hv like ? or so_gplx like ?) order by substring_index(hoten, ' ', -1) asc, hoten asc limit 0,300", array($like, $like, $like, $like));
	}
	$total_items = count($items);
}

function dt_sathach_period_options()
{
	global $d, $sh_periods, $sh_period_sel;
	$sh_periods = $d->rawQuery('select * from #_dt_sh_ky order by ngay desc,id desc');
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
	$name = trim((string)($_POST['ten'] ?? ''));
	if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !checkdate((int)($parts[1] ?? 0),(int)($parts[2] ?? 0),(int)($parts[0] ?? 0)) || !in_array($kind,array('moto','oto'),true) || $name==='' || mb_strlen($name)>255)
		$func->transfer('Nhập tên, ngày sát hạch và loại xe hợp lệ.', $back, false);
	$d->rawQuery('insert into #_dt_sh_ky (ten,ngay,loai,nguoi_nhap,ngaytao) values (?,?,?,?,?)', array($name,$date,$kind,dt_username(),time()));
	if($d->getLastErrorCode() !== '00000') $func->transfer('Không thể tạo kỳ sát hạch.', $back, false);
	$id = (int)$d->getLastInsertId();
	dt_audit('create','sh_ky',1,'id='.$id.' '.$date.' '.$kind);
	$func->transfer('Đã tạo kỳ sát hạch.', $back.'&id_ky='.$id);
}

/* Màn import. */
function dt_sathach_upload_form()
{
	global $d, $ds_khoa, $id_khoa_sel;
	dt_sathach_period_options();
	$ds_khoa = dt_khoa_options();
	$id_khoa_sel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
}

/* Import kết quả sát hạch từ Excel (ghép theo CCCD). */
function dt_sathach_upload_excel()
{
	global $d, $func;
	if(($_POST['import_mode'] ?? '') === 'summary') { dt_sathach_summary_import(); return; }
	$idKhoaSel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$idKy = (int)($_POST['id_ky'] ?? 0);
	$back = "index.php?com=daotao&act=uploadSathach".($idKhoaSel ? "&id_khoa=".$idKhoaSel : "");
	$period = $d->rawQueryOne('select * from #_dt_sh_ky where id=?', array($idKy));
	if(!$period) dt_import_notice('Tạo và chọn kỳ sát hạch trước khi import.', $back, false);
	if(!isset($_FILES['file-excel']) || $_FILES['file-excel']['error'] != 0)
		dt_import_notice("Vui lòng chọn file Excel", $back, false);
	$file = $_FILES['file-excel'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	list($sheet, $highestRow, $highestCol) = dt_open_upload_sheet($file, $ext, $back, array('sheet1'));

	$aliases = array(
		'ma_hv' => array('mahv','mahocvien'),
		'hoten' => array('hovaten','hoten'),
		'nam_sinh' => array('namsinh','ngaysinh'),
		'cccd' => array('cccd','socccd','socmt','socmnd','sochungminh'),
		'hang' => array('hang','hanggplx'),
		'noi_dung_sh' => array('noidungsh','noidungsathach'),
		'ma_khoa' => array('makhoa'),
		'ten_khoa' => array('tenkhoa'),
		'giaovien' => array('giaovien','gv'),
		'ngay_sh' => array('ngaysathach','ngaysh','ngaythi','ngaykiemtra'),
		'so_gplx' => array('sogplx','sogiaypheplaixe','soglpx','sogplxduoccap'),
		'diem_lt' => array('diemlt','diemlythuyet','sodiemdatlythuyet'),
		'lt' => array('lt','ketqualt','ketqualythuyet','ketquamonlythuyet'),
		'diem_mp' => array('diemmp','diemmophong','sodiemdatmophong'),
		'mp' => array('mp','ketquamp','ketquamophong','ketquamonmophong'),
		'ket_qua_tong' => array('ketquatong'),
		'diem_hinh' => array('diemhinh','sodiemdathinh'),
		'hinh' => array('hinh','ketquahinh','ketquamonhinh'),
		'diem_duong' => array('diemduong','sodiemdatduong'),
		'duong' => array('duong','ketquaduong','ketquamonduong'),
		'chu_chi' => array('chuchi','ketquachuchi'),
		'diem_chu_chi' => array('diemchuchi'),
		'ket_luan' => array('ketluan'),
		'ket_qua' => array('ketqua','ketquasathach'),
		'ghi_chu' => array('ghichu'),
	);
	$contains = array(
		'cccd' => array('cccd','cmt'),
		'hoten' => array('hovaten'),
		'so_gplx' => array('gplx'),
	);
	list($headerRow, $map, $score) = dt_find_header_row($sheet, $highestRow, $highestCol, $aliases, $contains);
	if($score <= 0 || !isset($map['cccd']))
		dt_import_notice("Không nhận diện được cột 'CCCD' trong file kết quả sát hạch.", $back, false);

	if(!isset($map['hoten'],$map['diem_lt'],$map['lt'],$map['diem_hinh'],$map['hinh'],$map['ket_qua']))
		dt_import_notice('File thiếu cột họ tên hoặc điểm/kết quả LT, Hình, kết quả sát hạch.', $back, false);
	if($period['loai']==='oto' && !isset($map['diem_duong'],$map['duong'])) dt_import_notice('Mẫu ô tô phải có điểm/kết quả Đường.', $back, false);
	$ngaySHChung = $period['ngay'];

	if(!dt_backup_tables(array('dt_sat_hach'), 'imp_sathach')) dt_import_notice('Không sao lưu được kết quả hiện tại.', $back, false);
	$d->startTransaction();
	try
	{
	$d->rawQueryOne('select id from #_dt_sh_ky where id=? for update', array($idKy));
	$ok = 0; $moi = 0; $emptyStreak = 0;
	for($r = $headerRow + 1; $r <= $highestRow; $r++)
	{
		$row = dt_read_row($sheet, $r, $highestCol);
		$cccd = dt_normalize_cccd(dt_val($row, $map, 'cccd'));
		$hoten = dt_val($row, $map, 'hoten');
		if(strlen($cccd) < 9) { if($hoten === '' && ++$emptyStreak >= 30) break; continue; }
		$emptyStreak = 0;

		$hv = dt_find_hocvien_by_cccd($cccd, $idKhoaSel);
		$idKhoa = $hv ? (int)$hv['id_khoa'] : $idKhoaSel;
		$ketLuan = dt_val($row, $map, 'ket_luan');
		$ketQua = dt_val($row, $map, 'ket_qua');
		if($ketQua === '') $ketQua = $ketLuan;
		$ngaySH = $ngaySHChung;
		$hangRaw = dt_val($row,$map,'hang');
		$isMoto = strpos(strtoupper(trim($hangRaw)), 'A') === 0;
		if($hangRaw !== '' && $isMoto !== ($period['loai']==='moto')) throw new RuntimeException('Hạng xe trong file không khớp loại kỳ sát hạch.');
		$data = array(
			'id_khoa' => $idKhoa, 'cccd' => $cccd, 'ma_hv' => dt_val($row, $map, 'ma_hv') ?: ($hv ? $hv['ma_hv'] : ''),
			'id_ky' => $idKy, 'noi_dung_sh' => dt_val($row,$map,'noi_dung_sh'),
			'hoten' => $hoten ?: ($hv ? $hv['hoten'] : ''),
			'nam_sinh' => dt_val($row, $map, 'nam_sinh') ?: ($hv ? $hv['ngaysinh'] : ''),
			'hang' => dt_val($row, $map, 'hang') ?: ($hv ? $hv['hang'] : ''),
			'ma_khoa' => dt_val($row, $map, 'ma_khoa'),
			'ten_khoa' => dt_val($row, $map, 'ten_khoa'),
			'giaovien' => dt_val($row, $map, 'giaovien') ?: ($hv ? $hv['gv_hoten'] : ''),
			'ngay_sh' => $ngaySH,
			'so_gplx' => dt_val($row, $map, 'so_gplx'),
			'diem_lt' => dt_val($row, $map, 'diem_lt'), 'lt' => dt_val($row, $map, 'lt'),
			'diem_mp' => dt_val($row, $map, 'diem_mp'), 'mp' => dt_val($row, $map, 'mp'),
			'ket_qua_tong' => dt_val($row, $map, 'ket_qua_tong'),
			'diem_hinh' => dt_val($row, $map, 'diem_hinh'), 'hinh' => dt_val($row, $map, 'hinh'),
			'diem_duong' => dt_val($row, $map, 'diem_duong'), 'duong' => dt_val($row, $map, 'duong'),
			'chu_chi' => dt_val($row, $map, 'chu_chi'), 'diem_chu_chi' => dt_val($row, $map, 'diem_chu_chi'),
			'ket_luan' => $ketLuan,
			'ket_qua' => $ketQua,
			'ghi_chu' => dt_val($row, $map, 'ghi_chu'),
			'nguoi_nhap' => dt_username(),
		);
		if($period['loai']==='moto') foreach(array('diem_mp','mp','diem_duong','duong') as $field) $data[$field]='';
		$exist = $d->rawQueryOne("select id from #_dt_sat_hach where id_ky = ? and cccd = ? limit 0,1", array($idKy, $cccd));
		if($exist && $exist['id']) { $d->where('id', $exist['id']); $saved=$d->update('dt_sat_hach', $data); $ok++; }
		else { $data['ngaytao'] = time(); $saved=$d->insert('dt_sat_hach', $data); $moi++; }
		if($saved === false) throw new RuntimeException('Không thể lưu kết quả sát hạch.');
	}
	if(!$ok && !$moi) throw new RuntimeException('File không có học viên hợp lệ.');
	if($d->commit() === false) throw new RuntimeException('Không thể hoàn tất import.');
	}
	catch(Exception $error) { $d->rollback(); dt_import_notice($error->getMessage().' Chưa thay đổi kết quả.', $back.'&id_ky='.$idKy, false); }
	dt_audit('import', 'sat_hach', $ok + $moi, $file['name']);
	dt_log_import('sat_hach', $file['name'], $ok + $moi, 0);
	dt_import_notice("Import kết quả sát hạch: cập nhật $ok, thêm mới $moi", "index.php?com=daotao&act=sathach&id_ky=".$idKy, true);
}

/* Xuất Excel kết quả sát hạch. */
function dt_sathach_export()
{
	global $d, $func, $items;
	if(!dt_req('id_khoa') && !dt_req('id_ky') && !dt_req('keyword') && !dt_req('ngay_sh')) $func->transfer('Chọn kỳ/ngày sát hạch hoặc học viên để xuất.', 'index.php?com=daotao&act=sathach', false);
	dt_sathach_list();
	$rows = $items;
	$headers = array('STT','Mã HV','Họ và tên','Năm sinh','CCCD','Hạng','Mã khóa','Giáo viên','Ngày sát hạch','Điểm LT','LT','Điểm MP','MP','Điểm Hình','Hình','Điểm Đường','Đường','Chữ chi','Điểm Chữ chi','Kết luận','Số GPLX','Ghi chú','Nội dung SH');
	$out = array(); $stt = 1;
	foreach($rows as $r)
		$out[] = array($stt++, $r['ma_hv'], $r['hoten'], $r['nam_sinh'], $r['cccd'], $r['hang'], $r['ma_khoa'], isset($r['giaovien']) ? $r['giaovien'] : '', $r['ngay_sh'],
			isset($r['diem_lt'])?$r['diem_lt']:'', isset($r['lt'])?$r['lt']:'', isset($r['diem_mp'])?$r['diem_mp']:'', isset($r['mp'])?$r['mp']:'',
			isset($r['diem_hinh'])?$r['diem_hinh']:'', isset($r['hinh'])?$r['hinh']:'', isset($r['diem_duong'])?$r['diem_duong']:'', isset($r['duong'])?$r['duong']:'',
			isset($r['chu_chi'])?$r['chu_chi']:'', isset($r['diem_chu_chi'])?$r['diem_chu_chi']:'', (isset($r['ket_luan'])&&$r['ket_luan']!=='')?$r['ket_luan']:$r['ket_qua'], $r['so_gplx'], $r['ghi_chu'], $r['noi_dung_sh'] ?? '');
	dt_xlsx_simple('Ket qua sat hach', 'ket-qua-sat-hach-'.date('Ymd_His').'.xlsx', $headers, $out, array(1, 4));
}

/* Tra cứu kết quả sát hạch theo CCCD / họ tên / số GPLX / mã khóa. */
function dt_sathach_tracuu()
{
	global $d, $items, $total_items, $tc_filters;
	$tc_filters = array(
		'cccd' => dt_req('cccd'),
		'hoten' => dt_req('hoten'),
		'so_gplx' => dt_req('so_gplx'),
		'ma_khoa' => dt_req('ma_khoa'),
		'ngay_sh' => dt_req('ngay_sh'),
	);
	$items = array(); $total_items = 0;
	$has = ($tc_filters['cccd'] !== '' || $tc_filters['hoten'] !== '' || $tc_filters['so_gplx'] !== '' || $tc_filters['ma_khoa'] !== '' || $tc_filters['ngay_sh'] !== '');
	if(!$has) return;
	$where = "1"; $params = array();
	if($tc_filters['ngay_sh'] !== '') { $where .= ' and (ngay_sh=? or ngay_sh=?)'; $params[]=$tc_filters['ngay_sh']; $params[]=date('d/m/Y',strtotime($tc_filters['ngay_sh'])); }
	if($tc_filters['cccd'] !== '') { $where .= " and cccd like ?"; $params[] = '%'.dt_normalize_cccd($tc_filters['cccd']).'%'; }
	if($tc_filters['hoten'] !== '') { $where .= " and hoten like ?"; $params[] = '%'.$tc_filters['hoten'].'%'; }
	if($tc_filters['so_gplx'] !== '') { $where .= " and so_gplx like ?"; $params[] = '%'.$tc_filters['so_gplx'].'%'; }
	if($tc_filters['ma_khoa'] !== '') { $where .= " and ma_khoa like ?"; $params[] = '%'.$tc_filters['ma_khoa'].'%'; }
	$items = $d->rawQuery("select * from #_dt_sat_hach where $where order by substring_index(hoten, ' ', -1) asc, hoten asc limit 0,500", $params);
	$total_items = count($items);
}
