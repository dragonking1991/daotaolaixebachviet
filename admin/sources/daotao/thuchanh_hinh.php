<?php
if(!defined('SOURCES')) die("Error");

function dt_hinh_list()
{
	global $d, $func, $items, $ds_khoa, $id_khoa_sel;

	$ds_khoa = dt_khoa_options();
	$id_khoa_sel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$items = array();
	if(!$id_khoa_sel) return;

	$sql = "select h.id, h.cccd, h.hoten, h.hang, th.gio, th.km "
		. "from #_dt_hocvien h left join #_dt_thuchanh_hinh th on (th.id_khoa = h.id_khoa and th.cccd = h.cccd) "
		. "where h.id_khoa = ? order by h.hoten asc";
	$rows = $d->rawQuery($sql, array($id_khoa_sel));
	foreach($rows as $rw)
	{
		$rw['dat'] = dt_hinh_dat($rw['hang'], $rw['gio'], $rw['km']);
		$items[] = $rw;
	}
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

	$data = array('id_khoa' => $idKhoa, 'cccd' => $hv['cccd'], 'gio' => $gio, 'km' => $km, 'nguoi_nhap' => dt_username());
	$exist = $d->rawQueryOne("select id from #_dt_thuchanh_hinh where id_khoa = ? and cccd = ? limit 0,1", array($idKhoa, $hv['cccd']));
	if($exist && $exist['id']) { $d->where('id', $exist['id']); $d->update('dt_thuchanh_hinh', $data); }
	else { $data['ngaytao'] = time(); $d->insert('dt_thuchanh_hinh', $data); }

	$func->transfer("Đã lưu thực hành trong hình cho ".$hv['hoten'], $back);
}
