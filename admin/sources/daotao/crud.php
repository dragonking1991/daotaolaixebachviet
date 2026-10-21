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
			'bien_so' => 'Biển số', 'hang_xe' => 'Hạng xe', 'hang_dt' => 'Hạng đào tạo', 'so_dangky' => 'Số đăng ký', 'so_khung' => 'Số khung', 'so_may' => 'Số máy', 'loai_xe' => 'Loại xe', 'nhan_hieu' => 'Nhãn hiệu', 'gv_hoten' => 'Giáo viên', 'ngay_hethan' => 'Ngày hết hạn GPLX xe tập lái',
			'ma_csdt' => 'Mã CSĐT', 'nam_sx' => 'Năm sản xuất', 'so_gpxtl' => 'Số GP xe tập lái', 'ngay_cap_gpxtl' => 'Ngày cấp GPXTL', 'ngay_cap_gcnkd' => 'Ngày cấp GCNKĐ', 'ngay_hethan_gcnkd' => 'Ngày hết hạn GCNKĐ', 'so_huu' => 'Sở hữu (1/0)', 'so_imei' => 'Số IMEI', 'so_seri' => 'Số Seri', 'ghi_chu' => 'Ghi chú'
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
		'mophong' => array('title' => 'Mô phỏng', 'table' => 'dt_mophong', 'back' => 'mophong', 'fields' => array()),
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

/* URL quay lại an toàn (nội bộ module đào tạo) từ tham số ret — để sửa/hủy/xóa trở về đúng màn trước. */
function dt_crud_ret_url()
{
	$ret = isset($_REQUEST['ret']) ? trim((string)$_REQUEST['ret']) : '';
	if($ret === '') return '';
	$ret = rawurldecode($ret);
	if(strpos($ret, "\n") !== false || strpos($ret, "\r") !== false) return '';
	if(strpos($ret, 'index.php?com=daotao') !== 0) return '';
	return $ret;
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
	if(!$id)
	{
		if(isset($_GET['id_khoa'])) $item['id_khoa'] = (int)$_GET['id_khoa'];
		if($dt_crud_entity === 'lythuyet')
		{
			if(isset($_GET['cccd'])) $item['cccd'] = dt_normalize_cccd($_GET['cccd']);
			$mon = isset($_GET['mon']) ? preg_replace('/[^a-z0-9_]/', '', $_GET['mon']) : '';
			$monOptions = dt_mon_lythuyet();
			if(isset($monOptions[$mon])) $item['mon'] = $mon;
		}
	}
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
	if($entity === 'lythuyet') $data['dat'] = dt_lythuyet_dat($data['tien_do'] ?? 0, $data['diem_kt'] ?? 0, $data['mon'] ?? '');
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
	if($entity === 'hinh')
	{
		$back = dt_crud_ret_url() ?: dt_crud_back($config);
		$hv = dt_find_hocvien_by_cccd($data['cccd'] ?? '', (int)($data['id_khoa'] ?? 0));
		if(!$hv || (int)$hv['id_khoa'] !== (int)($data['id_khoa'] ?? 0))
			$func->transfer('Học viên không thuộc khóa', $back, false);
		$gio = $data['gio'] ?? 0;
		$km = $data['km'] ?? 0;
		if(!is_finite($gio) || !is_finite($km) || $gio < 0 || $km < 0)
			$func->transfer('Giờ và km phải là số không âm.', $back, false);
		$errors = dt_hinh_cap_warn($hv['hang'], $gio, $km);
		if($errors) $func->transfer('Không thể lưu thực hành trong hình: '.implode('; ', $errors), $back, false);
	}
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
	$success = (bool)$ok;
	if($success && $entity === 'dat')
	{
		$syncResult = xd_sync_training_students((int)($data['id_khoa'] ?? 0), $data['cccd'] ?? '');
		$msg .= '.'.xd_sync_training_notice($syncResult);
		$success = !empty($syncResult['ok']);
	}
	$func->transfer($msg, dt_crud_ret_url() ?: dt_crud_back($config), $success);
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
	$ret = dt_crud_ret_url();
	if($ret !== '') $back = $ret.($cascade ? '' : (strpos($ret, '?') !== false ? '&' : '?').'undo=1');
	else $back = dt_crud_back($config, dt_crud_ret_query().($cascade ? '' : '&undo=1'));
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
	foreach(array('id_khoa','keyword','hang','loai_xe','nhan_hieu','gioitinh','hang_gplx','gv','he','p') as $k)
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
		if($hv) foreach(array('dt_lythuyet','dt_mophong','dt_cabin_kq','dt_dat_phien','dt_dat_override','dt_thuchanh_hinh') as $table) $d->rawQuery('delete from #_'.$table.' where id_khoa = ? and cccd = ?', array($hv['id_khoa'], $hv['cccd']));
	}
	$d->rawQuery('delete from #_'.$config['table'].' where id = ?', array($id));
}

function dt_khoa_delete_id($id)
{
	global $d;
	foreach(array('dt_hocvien','dt_lythuyet','dt_mophong','dt_cabin_kq','dt_dat_phien','dt_dat_override','dt_thuchanh_hinh') as $table) $d->rawQuery('delete from #_'.$table.' where id_khoa = ?', array($id));
	$d->rawQuery('delete from #_dt_khoa where id = ?', array($id));
}

function dt_khoa_delete_selected()
{
	global $d, $func;
	$ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_values(array_unique(array_filter(array_map('intval', $_POST['ids'])))) : array();
	if(!$ids) $func->transfer('Vui lòng chọn ít nhất một khóa', 'index.php?com=daotao&act=khoa', false);

	$placeholders = implode(',', array_fill(0, count($ids), '?'));
	$rows = $d->rawQuery("select id from #_dt_khoa where id in ($placeholders)", $ids);
	$ids = array_map('intval', array_column($rows, 'id'));
	if(!$ids) $func->transfer('Không tìm thấy khóa đã chọn', 'index.php?com=daotao&act=khoa', false);
	if(dt_backup_tables(array('dt_khoa','dt_hocvien','dt_lythuyet','dt_mophong','dt_cabin_kq','dt_dat_phien','dt_dat_override','dt_thuchanh_hinh'), 'del_khoa') === '')
		$func->transfer('Không thể sao lưu dữ liệu; đã hủy thao tác xóa.', 'index.php?com=daotao&act=khoa', false);

	$affected = 0;
	$d->startTransaction();
	foreach($ids as $id)
	{
		foreach(array('dt_khoa','dt_hocvien','dt_lythuyet','dt_mophong','dt_cabin_kq','dt_dat_phien','dt_dat_override','dt_thuchanh_hinh') as $table)
		{
			$count = $d->rawQueryOne('select count(*) as c from #_'.$table.' where id'.($table === 'dt_khoa' ? '' : '_khoa').' = ?', array($id));
			$affected += (int)($count ? $count['c'] : 0);
		}
		dt_khoa_delete_id($id);
	}
	$d->commit();
	dt_audit('delete_selected', 'khoa', $affected, 'Khóa: '.implode(',', $ids));
	$func->transfer('Đã xóa '.count($ids).' khóa và dữ liệu liên quan ('.$affected.' bản ghi)', 'index.php?com=daotao&act=khoa');
}

function dt_crud_delete_all()
{
	global $d, $func;
	$entity = preg_replace('/[^a-z]/', '', isset($_GET['entity']) ? $_GET['entity'] : '');
	if($entity === 'all')
	{
		if(dt_backup_tables(dt_backup_tables_list(), 'all') === '')
			$func->transfer('Không thể sao lưu dữ liệu; đã hủy thao tác xóa.', 'index.php?com=daotao&act=tonghop', false);
		$affected = 0;
		foreach(array('dt_lythuyet','dt_mophong','dt_cabin_kq','dt_dat_phien','dt_dat_override','dt_thuchanh_hinh','dt_hocvien','dt_xe','dt_giaovien','dt_khoa','dt_import_log') as $table)
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
	$backupTables = array_merge(array($config['table']), in_array($entity, array('khoa','hocvien'), true) ? array('dt_lythuyet','dt_mophong','dt_cabin_kq','dt_dat_phien','dt_dat_override','dt_thuchanh_hinh') : array());
	if(dt_backup_tables($backupTables, $entity) === '')
		$func->transfer('Không thể sao lưu dữ liệu; đã hủy thao tác xóa.', dt_crud_back($config), false);

	if($entity === 'khoa') foreach($d->rawQuery('select id from #_dt_khoa') as $row) dt_khoa_delete_id((int)$row['id']);
	elseif($entity === 'hocvien') foreach(array('dt_lythuyet','dt_mophong','dt_cabin_kq','dt_dat_phien','dt_dat_override','dt_thuchanh_hinh') as $table) $d->rawQuery('delete from #_'.$table);
	$d->rawQuery('delete from #_'.$config['table']);
	dt_audit('delete_all', $entity, $affected, 'Xóa toàn bộ '.$config['title']);
	$func->transfer('Đã xóa toàn bộ '.$config['title'].' ('.$affected.' bản ghi)', dt_crud_back($config));
}

/* Xóa các mục đã chọn (bulk select) — thay cho "Xóa toàn bộ". */
function dt_crud_delete_selected()
{
	global $d, $func;
	$entity = preg_replace('/[^a-z]/', '', isset($_REQUEST['entity']) ? $_REQUEST['entity'] : '');
	$config = dt_crud_context($entity);
	if(!$config) $func->transfer('Mục đào tạo không hợp lệ', 'index.php?com=daotao&act=khoa', false);
	if($entity === 'khoa') $func->transfer('Chức năng xóa khóa đào tạo đã được tắt', 'index.php?com=daotao&act=khoa', false);
	$back = dt_crud_back($config, dt_crud_ret_query());
	$ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_values(array_unique(array_filter(array_map('intval', $_POST['ids'])))) : array();
	if(!$ids) $func->transfer('Vui lòng chọn ít nhất một mục để xóa', $back, false);

	$backupTables = array_merge(array($config['table']), $entity === 'hocvien' ? array('dt_lythuyet','dt_mophong','dt_cabin_kq','dt_dat_phien','dt_dat_override','dt_thuchanh_hinh') : array());
	if(dt_backup_tables($backupTables, 'sel_'.$entity) === '')
		$func->transfer('Không thể sao lưu dữ liệu; đã hủy thao tác xóa.', $back, false);

	$d->startTransaction();
	foreach($ids as $id) dt_crud_delete_id($entity, $id);
	$d->commit();
	dt_audit('delete_selected', $entity, count($ids), 'id: '.implode(',', $ids));
	$func->transfer('Đã xóa '.count($ids).' mục đã chọn khỏi '.$config['title'], $back);
}

function dt_module_selected_back($entity)
{
	$ret = dt_crud_ret_url();
	if($ret !== '') return $ret;
	$act = array('lythuyet' => 'lythuyet', 'mophong' => 'mophong', 'cabinkq' => 'cabinkq', 'hinh' => 'hinh', 'dat' => 'dat');
	$query = dt_crud_ret_query();
	return 'index.php?com=daotao&act='.(isset($act[$entity]) ? $act[$entity] : 'tonghop').$query;
}

function dt_module_selected_students($entity, $back)
{
	global $d, $func;
	$allowed = array('lythuyet', 'mophong', 'cabinkq', 'hinh', 'dat');
	if(!in_array($entity, $allowed, true))
		$func->transfer('Mục đào tạo không hợp lệ', 'index.php?com=daotao&act=tonghop', false);
	if(isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST')
		$func->transfer('Yêu cầu không hợp lệ', $back, false);

	$ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_values(array_unique(array_filter(array_map('intval', $_POST['ids']), function($id) { return $id > 0; }))) : array();
	if(!$ids) $func->transfer('Vui lòng chọn ít nhất một học viên', $back, false);
	$idKhoa = isset($_POST['id_khoa']) ? (int)$_POST['id_khoa'] : 0;
	$placeholders = implode(',', array_fill(0, count($ids), '?'));
	$params = $ids;
	$sql = "select id, id_khoa, ma_hv, cccd, hang from #_dt_hocvien where id in ($placeholders)";
	if($idKhoa) { $sql .= ' and id_khoa = ?'; $params[] = $idKhoa; }
	$students = $d->rawQuery($sql, $params);
	if(count($students) !== count($ids))
		$func->transfer('Có học viên không thuộc danh sách/khóa đang thao tác; không có dữ liệu nào được cập nhật', $back, false);
	return $students;
}

function dt_module_pass_selected()
{
	global $d, $func;
	$entity = preg_replace('/[^a-z]/', '', isset($_POST['entity']) ? $_POST['entity'] : '');
	$back = dt_module_selected_back($entity);
	$students = dt_module_selected_students($entity, $back);
	$tableByEntity = array(
		'lythuyet' => array('dt_lythuyet', 'dt_mophong'),
		'mophong' => array('dt_mophong', 'dt_lythuyet'),
		'cabinkq' => array('dt_cabin_kq'),
		'hinh' => array('dt_thuchanh_hinh'),
		'dat' => array('dt_dat_override'),
	);
	if(dt_backup_tables($tableByEntity[$entity], 'pass_sel_'.$entity) === '')
		$func->transfer('Không thể sao lưu dữ liệu; đã hủy thao tác đánh dấu Đạt.', $back, false);

	$d->startTransaction();
	foreach($students as $hv)
	{
		$idKhoaHv = (int)$hv['id_khoa'];
		$cccd = $hv['cccd'];
		if($entity === 'lythuyet')
		{
			foreach(dt_mon_lythuyet_for_hang($hv['hang']) as $mon => $label)
			{
				$diem = $mon === 'mophong' ? 0 : 5;
				$d->rawQuery(
					"insert into #_dt_lythuyet (id_khoa, cccd, mon, tien_do, diem_kt, dat, ngaytao) values (?, ?, ?, 100, ?, 1, ?) on duplicate key update tien_do = 100, diem_kt = values(diem_kt), dat = 1",
					array($idKhoaHv, $cccd, $mon, $diem, time())
				);
				if($mon === 'mophong')
					$d->rawQuery("insert into #_dt_mophong (id_khoa, cccd, tien_do, dat, ngaytao) values (?, ?, 100, 1, ?) on duplicate key update tien_do = 100, dat = 1", array($idKhoaHv, $cccd, time()));
			}
		}
		elseif($entity === 'mophong')
		{
			$d->rawQuery(
				"insert into #_dt_mophong (id_khoa, cccd, tien_do, dat, ngaytao) values (?, ?, 100, 1, ?) on duplicate key update tien_do = 100, dat = 1",
				array($idKhoaHv, $cccd, time())
			);
			$d->rawQuery(
				"insert into #_dt_lythuyet (id_khoa, cccd, mon, tien_do, diem_kt, dat, ngaytao) values (?, ?, 'mophong', 100, 0, 1, ?) on duplicate key update tien_do = 100, diem_kt = 0, dat = 1",
				array($idKhoaHv, $cccd, time())
			);
		}
		elseif($entity === 'cabinkq')
		{
			$d->rawQuery(
				"insert into #_dt_cabin_kq (id_khoa, ma_hv, cccd, dat, ghi_chu, ngaytao) values (?, ?, ?, 1, 'Đáp ứng quy định', ?) on duplicate key update cccd = values(cccd), dat = 1, ghi_chu = 'Đáp ứng quy định'",
				array($idKhoaHv, $hv['ma_hv'], $cccd, time())
			);
		}
		elseif($entity === 'hinh')
		{
			$d->rawQuery(
				"insert into #_dt_thuchanh_hinh (id_khoa, cccd, gio, km, dat_override, nguoi_nhap, ngaytao) values (?, ?, 0, 0, 1, ?, ?) on duplicate key update dat_override = 1, nguoi_nhap = values(nguoi_nhap)",
				array($idKhoaHv, $cccd, dt_username(), time())
			);
		}
		else
		{
			$d->rawQuery(
				"insert into #_dt_dat_override (id_khoa, cccd, dat, nguoi_nhap, ngaytao) values (?, ?, 1, ?, ?) on duplicate key update dat = 1, nguoi_nhap = values(nguoi_nhap)",
				array($idKhoaHv, $cccd, dt_username(), time())
			);
		}
	}
	$d->commit();
	dt_audit('pass_selected', $entity, count($students), 'hocvien_ids='.implode(',', array_column($students, 'id')));

	$msg = 'Đã đánh dấu Đạt '.$entity.' cho '.count($students).' học viên.';
	if($entity === 'dat')
	{
		$khoaIds = array();
		foreach($students as $hv) $khoaIds[(int)$hv['id_khoa']] = true;
		foreach(array_keys($khoaIds) as $syncKhoaId)
		{
			$syncResult = xd_sync_training_students($syncKhoaId);
			if(empty($syncResult['ok']))
				$func->transfer($msg.' Tuy nhiên đồng bộ sang Xăng dầu chưa hoàn tất: '.xd_sync_training_notice($syncResult), $back, false);
		}
		$msg .= ' Đồng bộ sang Xăng dầu đã hoàn tất.';
	}
	$func->transfer($msg, $back);
}

function dt_module_delete_selected()
{
	global $d, $func;
	$entity = preg_replace('/[^a-z]/', '', isset($_POST['entity']) ? $_POST['entity'] : '');
	$back = dt_module_selected_back($entity);
	if(!in_array($entity, array('lythuyet', 'mophong', 'cabinkq', 'hinh'), true))
		$func->transfer('Mục này không hỗ trợ xóa theo học viên đã chọn', $back, false);
	$students = dt_module_selected_students($entity, $back);
	$targetsByEntity = array(
		'lythuyet' => array(
			array('table' => 'dt_lythuyet', 'where' => 'id_khoa = ? and cccd = ?', 'params' => array('id_khoa', 'cccd')),
			array('table' => 'dt_mophong', 'where' => 'id_khoa = ? and cccd = ?', 'params' => array('id_khoa', 'cccd')),
		),
		'mophong' => array(
			array('table' => 'dt_mophong', 'where' => 'id_khoa = ? and cccd = ?', 'params' => array('id_khoa', 'cccd')),
			array('table' => 'dt_lythuyet', 'where' => 'id_khoa = ? and cccd = ? and mon = ?', 'params' => array('id_khoa', 'cccd'), 'mon' => 'mophong'),
		),
		'cabinkq' => array(array('table' => 'dt_cabin_kq', 'where' => 'id_khoa = ? and (cccd = ? or ma_hv = ?)', 'params' => array('id_khoa', 'cccd', 'ma_hv'))),
		'hinh' => array(array('table' => 'dt_thuchanh_hinh', 'where' => 'id_khoa = ? and cccd = ?', 'params' => array('id_khoa', 'cccd'))),
	);
	$targets = $targetsByEntity[$entity];
	if(dt_backup_tables(array_column($targets, 'table'), 'del_sel_'.$entity) === '')
		$func->transfer('Không thể sao lưu dữ liệu; đã hủy thao tác xóa.', $back, false);

	$affected = 0;
	$d->startTransaction();
	foreach($students as $hv)
	{
		foreach($targets as $target)
		{
			$params = array();
			foreach($target['params'] as $field) $params[] = $field === 'id_khoa' ? (int)$hv['id_khoa'] : $hv[$field];
			if(isset($target['mon'])) $params[] = $target['mon'];
			$count = $d->rawQueryOne("select count(*) as c from #_".$target['table']." where ".$target['where'], $params);
			$affected += (int)($count ? $count['c'] : 0);
			$d->rawQuery("delete from #_".$target['table']." where ".$target['where'], $params);
		}
	}
	$d->commit();
	dt_audit('delete_selected_students', $entity, $affected, 'hocvien_ids='.implode(',', array_column($students, 'id')));
	$func->transfer('Đã xóa '.$affected.' kết quả '.$entity.' của '.count($students).' học viên đã chọn', $back);
}
