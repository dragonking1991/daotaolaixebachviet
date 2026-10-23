<?php
if(!defined('SOURCES')) die("Error");

function dt_hinh_list()
{
	global $d, $func, $items, $ds_khoa, $id_khoa_sel, $total_items, $module_stats;

	$ds_khoa = dt_khoa_options();
	$id_khoa_sel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$items = array();
	$total_items = 0;
	$module_stats = array('dat' => 0, 'chua' => 0);
	$kw = dt_mb_lower(dt_req('keyword'));
	if(!$id_khoa_sel && $kw === '') return;

	if($id_khoa_sel)
	{
		$sql = "select h.id, h.cccd, h.hoten, h.hang, h.id_khoa, h.gv_hoten, th.id as data_id, th.gio, th.km, th.dat_override "
			. "from #_dt_hocvien h left join #_dt_thuchanh_hinh th on (th.id_khoa = h.id_khoa and th.cccd = h.cccd) "
			. "where h.id_khoa = ? order by substring_index(h.hoten, ' ', -1) asc, h.hoten asc";
		$rows = $d->rawQuery($sql, array($id_khoa_sel));
	}
	else
	{
		$like = '%'.dt_req('keyword').'%';
		$sql = "select h.id, h.cccd, h.hoten, h.hang, h.id_khoa, h.gv_hoten, th.id as data_id, th.gio, th.km, th.dat_override "
			. "from #_dt_hocvien h left join #_dt_thuchanh_hinh th on (th.id_khoa = h.id_khoa and th.cccd = h.cccd) "
			. "where (h.hoten like ? or h.cccd like ? or h.ma_hv like ?) order by substring_index(h.hoten, ' ', -1) asc, h.hoten asc limit 0,200";
		$rows = $d->rawQuery($sql, array($like, $like, $like));
	}
	$tt = dt_req('trang_thai');
	$gvFilter = dt_gv_key(dt_req('gv'));
	foreach($rows as $rw)
	{
		if($kw !== '' && strpos(dt_mb_lower($rw['hoten'].' '.$rw['cccd']), $kw) === false) continue;
		if($gvFilter !== '' && dt_gv_key($rw['gv_hoten']) !== $gvFilter) continue;
		$rw['dat'] = dt_hinh_dat($rw['hang'], $rw['gio'], $rw['km'], $rw['dat_override']);
		$module_stats[$rw['dat'] ? 'dat' : 'chua']++;
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
	if(!is_finite($gio) || !is_finite($km) || $gio < 0 || $km < 0)
		$func->transfer("Giờ và km phải là số không âm.", $back, false);
	$errors = dt_hinh_cap_warn($hv['hang'], $gio, $km);
	if($errors) $func->transfer("Không thể lưu thực hành trong hình: ".implode('; ', $errors), $back, false);

	$override = isset($_POST['dat_override']) && (string)$_POST['dat_override'] === '1' ? 1 : null;
	$data = array('id_khoa' => $idKhoa, 'cccd' => $hv['cccd'], 'gio' => $gio, 'km' => $km, 'dat_override' => $override, 'nguoi_nhap' => dt_username());
	$exist = $d->rawQueryOne("select id from #_dt_thuchanh_hinh where id_khoa = ? and cccd = ? limit 0,1", array($idKhoa, $hv['cccd']));
	if($exist && $exist['id']) { $d->where('id', $exist['id']); $d->update('dt_thuchanh_hinh', $data); }
	else { $data['ngaytao'] = time(); $d->insert('dt_thuchanh_hinh', $data); }
	dt_audit('update', 'hinh', 1, 'cccd='.$hv['cccd'].($override === 1 ? ' (xác nhận Đạt thủ công)' : ''));

	$msg = "Đã lưu thực hành trong hình cho ".$hv['hoten'];
	$func->transfer($msg, $back);
}

function dt_hinh_save_selected()
{
	global $d, $func;
	$idKhoa = (int)($_POST['id_khoa'] ?? 0);
	$back = 'index.php?com=daotao&act=hinh&id_khoa='.$idKhoa;
	$ret = rawurldecode((string)($_POST['ret'] ?? ''));
	if(strpos($ret, 'index.php?com=daotao&act=hinh') === 0) $back = $ret;
	$ids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['ids'] ?? array())))));
	$rows = $_POST['hinh'] ?? array();
	if(!$ids || !is_array($rows)) $func->transfer('Vui lòng chọn học viên cần lưu.', $back, false);
	$pending = array();
	foreach($ids as $id)
	{
		$hv = $d->rawQueryOne('select id, id_khoa, cccd, hoten, hang from #_dt_hocvien where id = ? limit 0,1', array($id));
		$row = $rows[$id] ?? null;
		if(!$hv || ($idKhoa && (int)$hv['id_khoa'] !== $idKhoa) || !is_array($row) || !isset($row['gio'], $row['km']) || trim((string)$row['gio']) === '' || trim((string)$row['km']) === '')
			$func->transfer('Thiếu hoặc sai dữ liệu học viên đã chọn. Chưa lưu dữ liệu.', $back, false);
		$gio = dt_parse_number($row['gio']);
		$km = dt_parse_number($row['km']);
		foreach(array('gio', 'km') as $field)
			if(!is_numeric(str_replace(',', '.', trim((string)$row[$field]))))
				$func->transfer('Giờ và km không hợp lệ: '.$hv['hoten'].'. Chưa lưu dữ liệu.', $back, false);
		if(!is_finite($gio) || !is_finite($km) || $gio < 0 || $km < 0)
			$func->transfer('Giờ và km phải là số không âm: '.$hv['hoten'], $back, false);
		$errors = dt_hinh_cap_warn($hv['hang'], $gio, $km);
		if($errors) $func->transfer($hv['hoten'].': '.implode('; ', $errors).'. Chưa lưu dữ liệu.', $back, false);
		$pending[] = array('id_khoa' => (int)$hv['id_khoa'], 'cccd' => $hv['cccd'], 'gio' => $gio, 'km' => $km,
			'dat_override' => isset($row['dat_override']) && (string)$row['dat_override'] === '1' ? 1 : null, 'nguoi_nhap' => dt_username());
	}
	$d->startTransaction();
	foreach($pending as $data)
	{
		$exist = $d->rawQueryOne('select id from #_dt_thuchanh_hinh where id_khoa = ? and cccd = ? limit 0,1', array($data['id_khoa'], $data['cccd']));
		if($exist && $exist['id']) { $d->where('id', $exist['id']); $saved = $d->update('dt_thuchanh_hinh', $data); }
		else { $data['ngaytao'] = time(); $saved = $d->insert('dt_thuchanh_hinh', $data); }
		if($saved === false)
		{
			$d->rollback();
			$func->transfer('Không thể lưu dữ liệu. Đã hủy toàn bộ lần lưu này.', $back, false);
		}
	}
	if($d->commit() === false)
	{
		$d->rollback();
		$func->transfer('Không thể hoàn tất lưu dữ liệu.', $back, false);
	}
	dt_audit('update', 'hinh', count($pending), 'Luu nhieu hoc vien, id_khoa='.$idKhoa);
	$func->transfer('Đã lưu thực hành trong hình cho '.count($pending).' học viên.', $back);
}

/* Đánh dấu Đạt (override) toàn bộ học viên trong khóa. */
function dt_hinh_dat_all()
{
	global $d, $func;

	$idKhoa = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$back = "index.php?com=daotao&act=hinh&id_khoa=".$idKhoa;
	if(!$idKhoa) $func->transfer("Thiếu khóa học", $back, false);

	$hvs = $d->rawQuery("select cccd from #_dt_hocvien where id_khoa = ?", array($idKhoa));
	dt_backup_tables(array('dt_thuchanh_hinh'), 'hinh_datall');
	$d->startTransaction();
	foreach($hvs as $hv)
	{
		$data = array('dat_override' => 1, 'nguoi_nhap' => dt_username());
		$exist = $d->rawQueryOne("select id from #_dt_thuchanh_hinh where id_khoa = ? and cccd = ? limit 0,1", array($idKhoa, $hv['cccd']));
		if($exist && $exist['id']) { $d->where('id', $exist['id']); $d->update('dt_thuchanh_hinh', $data); }
		else { $ins = array('id_khoa' => $idKhoa, 'cccd' => $hv['cccd'], 'gio' => 0, 'km' => 0, 'dat_override' => 1, 'nguoi_nhap' => dt_username(), 'ngaytao' => time()); $d->insert('dt_thuchanh_hinh', $ins); }
	}
	$d->commit();
	dt_audit('update', 'hinh_dat_all', count($hvs), 'id_khoa='.$idKhoa);
	$func->transfer("Đã đánh dấu Đạt thực hành hình cho ".count($hvs)." học viên", $back);
}