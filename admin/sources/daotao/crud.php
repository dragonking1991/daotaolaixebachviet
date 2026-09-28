<?php
if(!defined('SOURCES')) die("Error");

function dt_crud_configs()
{
	return array(
		'khoa' => array('title' => 'khóa đào tạo', 'table' => 'dt_khoa', 'back' => 'khoa', 'fields' => array()),
		'hocvien' => array('title' => 'Học viên', 'table' => 'dt_hocvien', 'back' => 'hocvien', 'fields' => array(
			'id_khoa' => 'Khóa', 'ma_hv' => 'Mã học viên', 'cccd' => 'CCCD', 'hoten' => 'Họ và tên', 'ngaysinh' => 'Ngày sinh', 'hang' => 'Hạng', 'gv_hoten' => 'Giáo viên', 'nguoi_gioithieu' => 'Người giới thiệu', 'he_daotao' => 'Hệ đào tạo'
		)),
		'xe' => array('title' => 'Xe tập lái', 'table' => 'dt_xe', 'back' => 'xe', 'fields' => array(
			'bien_so' => 'Biển số', 'hang_xe' => 'Hạng xe', 'hang_dt' => 'Hạng đào tạo', 'so_dangky' => 'Số đăng ký', 'so_khung' => 'Số khung', 'so_may' => 'Số máy', 'loai_xe' => 'Loại xe', 'nhan_hieu' => 'Nhãn hiệu', 'gv_hoten' => 'Giáo viên'
		)),
		'giaovien' => array('title' => 'Giáo viên', 'table' => 'dt_giaovien', 'back' => 'giaovien', 'fields' => array(
			'cccd' => 'CCCD', 'hoten' => 'Họ và tên', 'ngaysinh' => 'Ngày sinh', 'gioitinh' => 'Giới tính', 'hang_gplx' => 'Hạng GPLX', 'hang_daotao_phep' => 'Hạng đào tạo được phép', 'sdt' => 'Điện thoại', 'dia_chi' => 'Địa chỉ'
		)),
		'lythuyet' => array('title' => 'Lý thuyết', 'table' => 'dt_lythuyet', 'back' => 'lythuyet', 'fields' => array(
			'id_khoa' => 'Khóa', 'cccd' => 'CCCD', 'mon' => 'Môn', 'tien_do' => 'Tiến độ (%)', 'diem_kt' => 'Điểm kiểm tra'
		)),
		'cabinkq' => array('title' => 'Cabin kết quả', 'table' => 'dt_cabin_kq', 'back' => 'cabinkq', 'fields' => array(
			'id_khoa' => 'Khóa', 'ma_hv' => 'Mã học viên', 'cccd' => 'CCCD', 'tong_thoigian' => 'Tổng thời gian', 'so_noidung' => 'Số nội dung', 'ghi_chu' => 'Ghi chú'
		)),
		'dat' => array('title' => 'DAT', 'table' => 'dt_dat_phien', 'back' => 'dat', 'fields' => array(
			'id_khoa' => 'Khóa', 'ma_phien' => 'Mã phiên', 'ma_hv' => 'Mã học viên', 'cccd' => 'CCCD', 'hang' => 'Hạng', 'tg_batdau' => 'Bắt đầu', 'tg_ketthuc' => 'Kết thúc', 'gio_thuchanh' => 'Giờ thực hành', 'km' => 'Quãng đường (km)', 'bien_so' => 'Biển số', 'gv_hoten' => 'Giáo viên', 'trang_thai' => 'Trạng thái'
		)),
		'hinh' => array('title' => 'Thực hành trong hình', 'table' => 'dt_thuchanh_hinh', 'back' => 'hinh', 'fields' => array(
			'id_khoa' => 'Khóa', 'cccd' => 'CCCD', 'gio' => 'Giờ thực hành', 'km' => 'Quãng đường (km)', 'nguoi_nhap' => 'Người nhập'
		)),
	);
}

function dt_crud_context($entity)
{
	$configs = dt_crud_configs();
	return isset($configs[$entity]) ? $configs[$entity] : null;
}

function dt_crud_back($config, $query = '')
{
	return 'index.php?com=daotao&act='.$config['back'].$query;
}

function dt_crud_form()
{
	global $d, $func, $item, $ds_khoa, $dt_crud_entity, $dt_crud_config;
	$dt_crud_entity = preg_replace('/[^a-z]/', '', isset($_GET['entity']) ? $_GET['entity'] : '');
	$dt_crud_config = dt_crud_context($dt_crud_entity);
	if(!$dt_crud_config) $func->transfer('Mục đào tạo không hợp lệ', 'index.php?com=daotao&act=khoa', false);
	$ds_khoa = dt_khoa_options();
	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	$item = $id ? $d->rawQueryOne('select * from #_'.$dt_crud_config['table'].' where id = ? limit 0,1', array($id)) : array();
	if(!$id && isset($_GET['id_khoa'])) $item['id_khoa'] = (int)$_GET['id_khoa'];
	if($id && (!$item || empty($item['id']))) $func->transfer('Dữ liệu không tồn tại', dt_crud_back($dt_crud_config), false);
}

function dt_crud_save()
{
	global $d, $func;
	$entity = preg_replace('/[^a-z]/', '', isset($_POST['entity']) ? $_POST['entity'] : '');
	$config = dt_crud_context($entity);
	if(!$config) $func->transfer('Mục đào tạo không hợp lệ', 'index.php?com=daotao&act=khoa', false);
	$p = isset($_POST['data']) && is_array($_POST['data']) ? $_POST['data'] : array();
	$data = array();
	foreach($config['fields'] as $field => $label)
	{
		if(!array_key_exists($field, $p)) continue;
		$value = is_array($p[$field]) ? '' : trim((string)$p[$field]);
		if($field === 'id_khoa') $value = (int)$value;
		elseif($field === 'cccd') $value = dt_normalize_cccd($value);
		elseif(in_array($field, array('hang','hang_xe','hang_gplx','hang_daotao_phep'), true)) $value = dt_norm_hang($value);
		elseif(in_array($field, array('tien_do','diem_kt','gio_thuchanh','km','gio','so_noidung'), true)) $value = dt_parse_number($value);
		else $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
		$data[$field] = $value;
	}
	if($entity === 'lythuyet') $data['dat'] = dt_lythuyet_dat($data['tien_do'] ?? 0, $data['diem_kt'] ?? 0);
	if($entity === 'cabinkq') $data['dat'] = (strpos(dt_norm_header($data['ghi_chu'] ?? ''), 'dapung') !== false && strpos(dt_norm_header($data['ghi_chu'] ?? ''), 'khong') === false) ? 1 : 0;
	if(isset($data['gv_hoten'])) $data['gv_key'] = dt_gv_key($data['gv_hoten']);
	if($entity === 'giaovien' && isset($data['hoten'])) $data['gv_key'] = dt_gv_key($data['hoten']);
	if(in_array($entity, array('lythuyet', 'cabinkq', 'dat'), true) && empty($data['cccd']) && !empty($data['ma_hv']) && !empty($data['id_khoa']))
	{
		$hv = dt_find_hocvien_by_mahv($data['ma_hv'], (int)$data['id_khoa']);
		if($hv) $data['cccd'] = $hv['cccd'];
	}
	if($entity === 'dat')
	{
		$data['la_xe_tudong'] = !empty($data['bien_so']) && dt_xe_la_tudong($data['bien_so']) ? 1 : 0;
		$data['gio_dem'] = dt_night_hours($data['tg_batdau'] ?? null, $data['tg_ketthuc'] ?? null);
		$data['ngay_hoc'] = !empty($data['tg_batdau']) ? substr($data['tg_batdau'], 0, 10) : null;
	}
	$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
	if($entity === 'hocvien' && empty($data['id_khoa'])) $func->transfer('Vui lòng chọn khóa học', dt_crud_back($config), false);
	if($id)
	{
		$d->where('id', $id);
		$ok = $d->update($config['table'], $data);
		$msg = 'Cập nhật '.$config['title'].' '.($ok !== false ? 'thành công' : 'bị lỗi');
	}
	else
	{
		$data['ngaytao'] = time();
		if($entity === 'giaovien' && !empty($data['cccd'])) $data['matkhau'] = dt_gv_hash($data['cccd']);
		$ok = $d->insert($config['table'], $data);
		$msg = 'Thêm '.$config['title'].' '.($ok ? 'thành công' : 'bị lỗi');
	}
	$func->transfer($msg, dt_crud_back($config), (bool)$ok);
}

function dt_crud_delete()
{
	global $d, $func;
	$entity = preg_replace('/[^a-z]/', '', isset($_GET['entity']) ? $_GET['entity'] : '');
	$config = dt_crud_context($entity);
	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	if(!$config || !$id) $func->transfer('Dữ liệu không hợp lệ', 'index.php?com=daotao&act=khoa', false);
	dt_crud_delete_id($entity, $id);
	$func->transfer('Đã xóa '.$config['title'], dt_crud_back($config));
}

function dt_crud_delete_id($entity, $id)
{
	global $d;
	$config = dt_crud_context($entity);
	if(!$config) return;
	if($entity === 'khoa') { dt_khoa_delete_id($id); return; }
	if($entity === 'hocvien')
	{
		$hv = $d->rawQueryOne('select id_khoa, cccd from #_dt_hocvien where id = ? limit 0,1', array($id));
		if($hv) foreach(array('dt_lythuyet','dt_cabin_kq','dt_dat_phien','dt_thuchanh_hinh') as $table) $d->rawQuery('delete from #_'.$table.' where id_khoa = ? and cccd = ?', array($hv['id_khoa'], $hv['cccd']));
	}
	$d->rawQuery('delete from #_'.$config['table'].' where id = ?', array($id));
}

function dt_khoa_delete_id($id)
{
	global $d;
	foreach(array('dt_hocvien','dt_lythuyet','dt_cabin_kq','dt_dat_phien','dt_thuchanh_hinh') as $table) $d->rawQuery('delete from #_'.$table.' where id_khoa = ?', array($id));
	$d->rawQuery('delete from #_dt_khoa where id = ?', array($id));
}

function dt_crud_delete_all()
{
	global $d, $func;
	$entity = preg_replace('/[^a-z]/', '', isset($_GET['entity']) ? $_GET['entity'] : '');
	if($entity === 'all')
	{
		foreach(array('dt_lythuyet','dt_cabin_kq','dt_dat_phien','dt_thuchanh_hinh','dt_hocvien','dt_xe','dt_giaovien','dt_khoa','dt_import_log') as $table) $d->rawQuery('delete from #_'.$table);
		$func->transfer('Đã xóa toàn bộ dữ liệu đào tạo', 'index.php?com=daotao&act=tonghop');
	}
	$config = dt_crud_context($entity);
	if(!$config) $func->transfer('Mục đào tạo không hợp lệ', 'index.php?com=daotao&act=khoa', false);
	if($entity === 'khoa') foreach($d->rawQuery('select id from #_dt_khoa') as $row) dt_khoa_delete_id((int)$row['id']);
	elseif($entity === 'hocvien') foreach(array('dt_lythuyet','dt_cabin_kq','dt_dat_phien','dt_thuchanh_hinh') as $table) $d->rawQuery('delete from #_'.$table);
	$d->rawQuery('delete from #_'.$config['table']);
	$func->transfer('Đã xóa toàn bộ '.$config['title'], dt_crud_back($config));
}
