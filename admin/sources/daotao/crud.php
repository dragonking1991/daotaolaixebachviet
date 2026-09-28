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
			'ma_csdt' => 'Mã CSĐT', 'cccd' => 'CCCD', 'hoten' => 'Họ và tên', 'ngaysinh' => 'Ngày sinh', 'gioitinh' => 'Giới tính', 'sdt' => 'Điện thoại', 'dia_chi' => 'Địa chỉ',
			'hang_gplx' => 'Hạng GPLX', 'so_gplx' => 'Số GPLX', 'ngay_cap_gplx' => 'Ngày cấp GPLX', 'ngay_hh_gplx' => 'Ngày hết hạn GPLX',
			'hang_daotao_phep' => 'Hạng đào tạo được phép', 'loai_hinh_dt' => 'Loại hình đào tạo (LT/TH/AL)', 'hinh_thuc_td' => 'Hình thức tuyển dụng', 'tuyen_dung' => 'Ngày tuyển dụng',
			'so_qd_gcn' => 'Số QĐ GCN', 'ngay_qd_gcn' => 'Ngày QĐ GCN', 'noi_cap_gcn' => 'Nơi cấp GCN', 'noi_ct' => 'Nơi công tác',
			'trinh_do' => 'Trình độ', 'chuyen_mon' => 'Chuyên môn', 'su_pham' => 'Sư phạm', 'ghi_chu' => 'Ghi chú'
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
		elseif(in_array($field, array('hang','hang_xe'), true)) $value = dt_norm_hang($value);
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

	// Lưu bản ghi để hoàn tác (chỉ với mục không xóa dây chuyền)
	$cascade = in_array($entity, array('khoa','hocvien'), true);
	if(!$cascade)
	{
		$row = $d->rawQueryOne('select * from #_'.$config['table'].' where id = ? limit 0,1', array($id));
		if($row) $_SESSION['dt_undo'] = array('entity' => $entity, 'row' => $row, 'time' => time());
	}
	dt_crud_delete_id($entity, $id);
	dt_audit('delete', $entity, 1, 'id='.$id);
	$back = dt_crud_back($config, dt_crud_ret_query().($cascade ? '' : '&undo=1'));
	$func->transfer('Đã xóa '.$config['title'], $back);
}

/* Hoàn tác xóa: chèn lại bản ghi vừa xóa (chỉ mục không dây chuyền). */
function dt_crud_undo()
{
	global $d, $func;
	$u = isset($_SESSION['dt_undo']) ? $_SESSION['dt_undo'] : null;
	if(!$u || (time() - (int)$u['time']) > 300) $func->transfer('Không có thao tác để hoàn tác', 'index.php?com=daotao&act=khoa', false);
	$config = dt_crud_context($u['entity']);
	if($config)
	{
		$d->rawQuery('delete from #_'.$config['table'].' where id = ?', array((int)$u['row']['id']));
		$d->insert($config['table'], $u['row']);
		dt_audit('undo', $u['entity'], 1, 'id='.$u['row']['id']);
	}
	unset($_SESSION['dt_undo']);
	$func->transfer('Đã hoàn tác', dt_crud_back($config));
}

/* Giữ lại tham số lọc/phân trang để quay về đúng chỗ. */
function dt_crud_ret_query()
{
	$q = array();
	foreach(array('id_khoa','keyword','hang','p') as $k)
		if(isset($_GET[$k]) && $_GET[$k] !== '') $q[$k] = $_GET[$k];
	return empty($q) ? '' : '&'.http_build_query($q);
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
		dt_backup_tables(dt_backup_tables_list(), 'all');
		$affected = 0;
		foreach(array('dt_lythuyet','dt_cabin_kq','dt_dat_phien','dt_thuchanh_hinh','dt_hocvien','dt_xe','dt_giaovien','dt_khoa','dt_import_log') as $table)
		{
			$c = $d->rawQueryOne("select count(*) as c from #_$table");
			$affected += (int)($c ? $c['c'] : 0);
			$d->rawQuery('delete from #_'.$table);
		}
		dt_audit('delete_all', 'all', $affected, 'Xóa toàn bộ dữ liệu đào tạo');
		$func->transfer('Đã xóa toàn bộ dữ liệu đào tạo ('.$affected.' bản ghi)', 'index.php?com=daotao&act=tonghop');
	}
	$config = dt_crud_context($entity);
	if(!$config) $func->transfer('Mục đào tạo không hợp lệ', 'index.php?com=daotao&act=khoa', false);

	$cnt = $d->rawQueryOne("select count(*) as c from #_".$config['table']);
	$affected = (int)($cnt ? $cnt['c'] : 0);
	$backupTables = array_merge(array($config['table']), in_array($entity, array('khoa','hocvien'), true) ? array('dt_lythuyet','dt_cabin_kq','dt_dat_phien','dt_thuchanh_hinh') : array());
	dt_backup_tables($backupTables, $entity);

	if($entity === 'khoa') foreach($d->rawQuery('select id from #_dt_khoa') as $row) dt_khoa_delete_id((int)$row['id']);
	elseif($entity === 'hocvien') foreach(array('dt_lythuyet','dt_cabin_kq','dt_dat_phien','dt_thuchanh_hinh') as $table) $d->rawQuery('delete from #_'.$table);
	$d->rawQuery('delete from #_'.$config['table']);
	dt_audit('delete_all', $entity, $affected, 'Xóa toàn bộ '.$config['title']);
	$func->transfer('Đã xóa toàn bộ '.$config['title'].' ('.$affected.' bản ghi)', dt_crud_back($config));
}
