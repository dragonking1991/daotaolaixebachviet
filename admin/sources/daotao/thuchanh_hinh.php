<?php
if(!defined('SOURCES')) die("Error");

function dt_hinh_list()
{
	global $d, $func, $items, $ds_khoa, $id_khoa_sel, $total_items;

	$ds_khoa = dt_khoa_options();
	$id_khoa_sel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$items = array();
	$total_items = 0;
	$kw = dt_mb_lower(dt_req('keyword'));
	if(!$id_khoa_sel && $kw === '') return;

	if($id_khoa_sel)
	{
		$sql = "select h.id, h.cccd, h.hoten, h.hang, h.id_khoa, th.id as data_id, th.gio, th.km, th.dat_override "
			. "from #_dt_hocvien h left join #_dt_thuchanh_hinh th on (th.id_khoa = h.id_khoa and th.cccd = h.cccd) "
			. "where h.id_khoa = ? order by h.hoten asc";
		$rows = $d->rawQuery($sql, array($id_khoa_sel));
	}
	else
	{
		$like = '%'.dt_req('keyword').'%';
		$sql = "select h.id, h.cccd, h.hoten, h.hang, h.id_khoa, th.id as data_id, th.gio, th.km, th.dat_override "
			. "from #_dt_hocvien h left join #_dt_thuchanh_hinh th on (th.id_khoa = h.id_khoa and th.cccd = h.cccd) "
			. "where (h.hoten like ? or h.cccd like ? or h.ma_hv like ?) order by h.hoten asc limit 0,200";
		$rows = $d->rawQuery($sql, array($like, $like, $like));
	}
	$tt = dt_req('trang_thai');
	foreach($rows as $rw)
	{
		if($kw !== '' && strpos(dt_mb_lower($rw['hoten'].' '.$rw['cccd']), $kw) === false) continue;
		$rw['dat'] = dt_hinh_dat($rw['hang'], $rw['gio'], $rw['km'], $rw['dat_override']);
		if($tt === 'dat' && !$rw['dat']) continue;
		if($tt === 'chua' && $rw['dat']) continue;
		$items[] = $rw;
	}
	$total_items = count($items);
}

function dt_hinh_save()
{
	global $d, $func;

	$idKhoa = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$cccd = isset($_POST['cccd']) ? dt_normalize_cccd($_POST['cccd']) : '';
	$back = "index.php?com=daotao&act=hinh&id_khoa=".$idKhoa;
	if(!$idKhoa || $cccd === '') $func->transfer("Thiếu dữ liệu học viên", $back, false);

	$hv = dt_find_hocvien_by_cccd($cccd, $idKhoa);
	if(!$hv) $func->transfer("Học viên không thuộc khóa", $back, false);

	$gio = dt_parse_number($_POST['gio'] ?? 0);
	$km = dt_parse_number($_POST['km'] ?? 0);

	$override = isset($_POST['dat_override']) && (string)$_POST['dat_override'] === '1' ? 1 : null;
	$data = array('id_khoa' => $idKhoa, 'cccd' => $hv['cccd'], 'gio' => $gio, 'km' => $km, 'dat_override' => $override, 'nguoi_nhap' => dt_username());
	$exist = $d->rawQueryOne("select id from #_dt_thuchanh_hinh where id_khoa = ? and cccd = ? limit 0,1", array($idKhoa, $hv['cccd']));
	if($exist && $exist['id']) { $d->where('id', $exist['id']); $d->update('dt_thuchanh_hinh', $data); }
	else { $data['ngaytao'] = time(); $d->insert('dt_thuchanh_hinh', $data); }
	dt_audit('update', 'hinh', 1, 'cccd='.$hv['cccd'].($override === 1 ? ' (xác nhận Đạt thủ công)' : ''));

	$func->transfer("Đã lưu thực hành trong hình cho ".$hv['hoten'], $back);
}
