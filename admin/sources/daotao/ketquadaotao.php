<?php
if(!defined('SOURCES')) die("Error");

/* ============================================================
 * Mô đun Thông tin kết quả đào tạo.
 * Kết hợp dữ liệu đào tạo (Thực hành hình giờ/km, DAT giờ/km)
 * với kết quả kỳ kiểm tra (điểm LT, MP, Hình, Đường, Chữ chi).
 * ============================================================ */

/* Lấy kết quả kỳ kiểm tra mới nhất (có kết quả) của 1 CCCD. */
function dt_ketqua_latest_exam($cccd)
{
	global $d;
	return $d->rawQueryOne("select h.*, ky.ngay_kt, ky.ten as ten_ky from #_dt_ky_thi_hv h left join #_dt_ky_thi ky on ky.id = h.id_ky where h.cccd = ? and h.co_ket_qua = 1 order by ky.ngay_kt desc, h.id desc limit 0,1", array($cccd));
}

function dt_ketqua_filters()
{
	return array(
		'id_khoa' => isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0,
		'cccd' => isset($_REQUEST['cccd']) ? dt_normalize_cccd($_REQUEST['cccd']) : '',
		'hoten' => isset($_REQUEST['hoten']) ? trim($_REQUEST['hoten']) : '',
		'ma_khoa' => isset($_REQUEST['ma_khoa']) ? trim($_REQUEST['ma_khoa']) : '',
	);
}

/* Lấy danh sách học viên theo bộ lọc, gắn dữ liệu đào tạo + kết quả kiểm tra. */
function dt_ketqua_rows($f)
{
	global $d;
	$where = "1"; $params = array();
	if($f['id_khoa']) { $where .= " and h.id_khoa = ?"; $params[] = $f['id_khoa']; }
	if($f['cccd'] !== '') { $where .= " and h.cccd like ?"; $params[] = '%'.$f['cccd'].'%'; }
	if($f['hoten'] !== '') { $where .= " and h.hoten like ?"; $params[] = '%'.$f['hoten'].'%'; }
	if($f['ma_khoa'] !== '') { $where .= " and k.ma_khoa like ?"; $params[] = '%'.$f['ma_khoa'].'%'; }

	$hvs = $d->rawQuery("select h.*, k.ma_khoa, k.ten_khoa from #_dt_hocvien h left join #_dt_khoa k on k.id = h.id_khoa where $where order by h.hoten asc limit 0,1000", $params);
	$out = array();
	foreach($hvs as $hv)
	{
		$s = dt_student_summary($hv);
		$ex = dt_ketqua_latest_exam($hv['cccd']);
		$out[] = array('hv' => $hv, 'sum' => $s, 'exam' => $ex);
	}
	return $out;
}

function dt_ketqua_list()
{
	global $items, $ds_khoa, $filters, $total_items;
	$ds_khoa = dt_khoa_options();
	$filters = dt_ketqua_filters();
	$has = ($filters['id_khoa'] || $filters['cccd'] !== '' || $filters['hoten'] !== '' || $filters['ma_khoa'] !== '');
	$items = $has ? dt_ketqua_rows($filters) : array();
	$total_items = count($items);
}

/* Xuất Excel kết quả đào tạo (đào tạo + kết quả kiểm tra). */
function dt_ketqua_export()
{
	global $func;
	$f = dt_ketqua_filters();
	$rows = dt_ketqua_rows($f);

	$headers = array('STT','Mã HV','Họ và tên','Năm sinh','CCCD','Hạng','Mã khóa','Tên khóa','Giáo viên',
		'Hình (giờ)','Hình (km)','DAT (giờ)','DAT (km)',
		'Ngày KT','Điểm LT','LT','Điểm MP','Điểm Hình','Hình','Điểm Đường','Đường','Chữ chi','Điểm Chữ chi','Kết luận');
	$out = array(); $stt = 1;
	foreach($rows as $it)
	{
		$hv = $it['hv']; $s = $it['sum']; $ex = $it['exam']; $agg = $s['dat']['agg'];
		$out[] = array($stt++, $hv['ma_hv'], $hv['hoten'], isset($hv['ngaysinh']) ? $hv['ngaysinh'] : '', $hv['cccd'], $hv['hang'],
			isset($hv['ma_khoa']) ? $hv['ma_khoa'] : '', isset($hv['ten_khoa']) ? $hv['ten_khoa'] : '', isset($hv['gv_hoten']) ? $hv['gv_hoten'] : '',
			$s['hinh']['gio'], $s['hinh']['km'], $agg['a'], $agg['e'],
			$ex ? $ex['ngay_kt'] : '', $ex ? $ex['diem_lt'] : '', $ex ? $ex['lt'] : '', $ex ? (isset($ex['diem_mp']) ? $ex['diem_mp'] : '') : '',
			$ex ? $ex['diem_hinh'] : '', $ex ? $ex['hinh'] : '', $ex ? $ex['diem_duong'] : '', $ex ? $ex['duong'] : '', $ex ? $ex['chu_chi'] : '', $ex ? $ex['diem_chu_chi'] : '', $ex ? $ex['ket_luan'] : '');
	}
	dt_xlsx_simple('Ket qua dao tao', 'ket-qua-dao-tao-'.date('Ymd_His').'.xlsx', $headers, $out, array(1, 4));
}
