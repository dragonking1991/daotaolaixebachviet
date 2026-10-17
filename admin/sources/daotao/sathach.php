<?php
if(!defined('SOURCES')) die("Error");

/* ============================================================
 * Mô đun Kết quả sát hạch (kết quả thi GPLX chính thức).
 * Import kết quả sát hạch từ TTSH về để lưu trữ + tra cứu.
 * ============================================================ */

/* Danh sách kết quả sát hạch theo khóa / từ khóa. */
function dt_sathach_list()
{
	global $d, $items, $ds_khoa, $id_khoa_sel, $total_items;
	$ds_khoa = dt_khoa_options();
	$id_khoa_sel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$items = array(); $total_items = 0;
	$kw = dt_mb_lower(dt_req('keyword'));
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

/* Màn import. */
function dt_sathach_upload_form()
{
	global $d, $ds_khoa, $id_khoa_sel;
	$ds_khoa = dt_khoa_options();
	$id_khoa_sel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
}

/* Import kết quả sát hạch từ Excel (ghép theo CCCD). */
function dt_sathach_upload_excel()
{
	global $d, $func;
	$idKhoaSel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$back = "index.php?com=daotao&act=uploadSathach".($idKhoaSel ? "&id_khoa=".$idKhoaSel : "");
	if(!isset($_FILES['file-excel']) || $_FILES['file-excel']['error'] != 0)
		dt_import_notice("Vui lòng chọn file Excel", $back, false);
	$file = $_FILES['file-excel'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	list($sheet, $highestRow, $highestCol) = dt_open_upload_sheet($file, $ext, $back);

	$aliases = array(
		'ma_hv' => array('mahv','mahocvien'),
		'hoten' => array('hovaten','hoten'),
		'nam_sinh' => array('namsinh','ngaysinh'),
		'cccd' => array('cccd','socccd','socmt','socmnd'),
		'hang' => array('hang'),
		'ma_khoa' => array('makhoa'),
		'ten_khoa' => array('tenkhoa'),
		'giaovien' => array('giaovien','gv'),
		'ngay_sh' => array('ngaysathach','ngaysh','ngaythi','ngaykiemtra'),
		'so_gplx' => array('sogplx','sogiaypheplaixe','soglpx','sogplxduoccap'),
		'diem_lt' => array('diemlt','diemlythuyet'),
		'lt' => array('lt','ketqualt','ketqualythuyet'),
		'diem_mp' => array('diemmp','diemmophong'),
		'mp' => array('mp','ketquamp','ketquamophong'),
		'ket_qua_tong' => array('ketquatong'),
		'diem_hinh' => array('diemhinh'),
		'hinh' => array('hinh','ketquahinh'),
		'diem_duong' => array('diemduong'),
		'duong' => array('duong','ketquaduong'),
		'chu_chi' => array('chuchi','ketquachuchi'),
		'diem_chu_chi' => array('diemchuchi'),
		'ket_luan' => array('ketluan'),
		'ket_qua' => array('ketqua'),
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

	$ngaySHChung = isset($_POST['ngay_sh_chung']) ? trim((string)$_POST['ngay_sh_chung']) : '';

	dt_backup_tables(array('dt_sat_hach'), 'imp_sathach');
	$d->startTransaction();
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
		$ngaySH = dt_val($row, $map, 'ngay_sh');
		if($ngaySH === '') $ngaySH = $ngaySHChung;
		$data = array(
			'id_khoa' => $idKhoa, 'cccd' => $cccd, 'ma_hv' => dt_val($row, $map, 'ma_hv') ?: ($hv ? $hv['ma_hv'] : ''),
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
		$exist = $d->rawQueryOne("select id from #_dt_sat_hach where id_khoa = ? and cccd = ? limit 0,1", array($idKhoa, $cccd));
		if($exist && $exist['id']) { $d->where('id', $exist['id']); $d->update('dt_sat_hach', $data); $ok++; }
		else { $data['ngaytao'] = time(); $d->insert('dt_sat_hach', $data); $moi++; }
	}
	$d->commit();
	dt_audit('import', 'sat_hach', $ok + $moi, $file['name']);
	dt_log_import('sat_hach', $file['name'], $ok + $moi, 0);
	dt_import_notice("Import kết quả sát hạch: cập nhật $ok, thêm mới $moi", "index.php?com=daotao&act=sathach".($idKhoaSel ? "&id_khoa=".$idKhoaSel : ""), true);
}

/* Xuất Excel kết quả sát hạch. */
function dt_sathach_export()
{
	global $d, $func;
	$idKhoa = (int)dt_req('id_khoa', 0);
	$kw = dt_req('keyword');
	if(!$idKhoa && $kw === '') $func->transfer('Chọn khóa hoặc nhập từ khóa để xuất', 'index.php?com=daotao&act=sathach', false);
	if($idKhoa)
		$rows = $d->rawQuery("select * from #_dt_sat_hach where id_khoa = ? order by substring_index(hoten, ' ', -1) asc, hoten asc", array($idKhoa));
	else
	{
		$like = '%'.$kw.'%';
		$rows = $d->rawQuery("select * from #_dt_sat_hach where (hoten like ? or cccd like ? or ma_hv like ? or so_gplx like ?) order by substring_index(hoten, ' ', -1) asc, hoten asc", array($like, $like, $like, $like));
	}
	$headers = array('STT','Mã HV','Họ và tên','Năm sinh','CCCD','Hạng','Mã khóa','Giáo viên','Ngày sát hạch','Điểm LT','LT','Điểm MP','MP','Điểm Hình','Hình','Điểm Đường','Đường','Chữ chi','Điểm Chữ chi','Kết luận','Số GPLX','Ghi chú');
	$out = array(); $stt = 1;
	foreach($rows as $r)
		$out[] = array($stt++, $r['ma_hv'], $r['hoten'], $r['nam_sinh'], $r['cccd'], $r['hang'], $r['ma_khoa'], isset($r['giaovien']) ? $r['giaovien'] : '', $r['ngay_sh'],
			isset($r['diem_lt'])?$r['diem_lt']:'', isset($r['lt'])?$r['lt']:'', isset($r['diem_mp'])?$r['diem_mp']:'', isset($r['mp'])?$r['mp']:'',
			isset($r['diem_hinh'])?$r['diem_hinh']:'', isset($r['hinh'])?$r['hinh']:'', isset($r['diem_duong'])?$r['diem_duong']:'', isset($r['duong'])?$r['duong']:'',
			isset($r['chu_chi'])?$r['chu_chi']:'', isset($r['diem_chu_chi'])?$r['diem_chu_chi']:'', (isset($r['ket_luan'])&&$r['ket_luan']!=='')?$r['ket_luan']:$r['ket_qua'], $r['so_gplx'], $r['ghi_chu']);
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
	);
	$items = array(); $total_items = 0;
	$has = ($tc_filters['cccd'] !== '' || $tc_filters['hoten'] !== '' || $tc_filters['so_gplx'] !== '' || $tc_filters['ma_khoa'] !== '');
	if(!$has) return;
	$where = "1"; $params = array();
	if($tc_filters['cccd'] !== '') { $where .= " and cccd like ?"; $params[] = '%'.dt_normalize_cccd($tc_filters['cccd']).'%'; }
	if($tc_filters['hoten'] !== '') { $where .= " and hoten like ?"; $params[] = '%'.$tc_filters['hoten'].'%'; }
	if($tc_filters['so_gplx'] !== '') { $where .= " and so_gplx like ?"; $params[] = '%'.$tc_filters['so_gplx'].'%'; }
	if($tc_filters['ma_khoa'] !== '') { $where .= " and ma_khoa like ?"; $params[] = '%'.$tc_filters['ma_khoa'].'%'; }
	$items = $d->rawQuery("select * from #_dt_sat_hach where $where order by substring_index(hoten, ' ', -1) asc, hoten asc limit 0,500", $params);
	$total_items = count($items);
}
