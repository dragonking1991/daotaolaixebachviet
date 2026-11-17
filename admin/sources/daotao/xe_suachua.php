<?php
if(!defined('SOURCES')) die("Error");
require_once LIBRARIES.'xe_suachua_lib.php';

function dt_xe_suachua_list_url($ret = '')
{
	$url = 'index.php?com=daotao&act=xe';
	$q = array();
	$ret = rawurldecode((string)$ret);
	$parts = explode('?', $ret, 2);
	if(count($parts) === 2 && $parts[0] === 'index.php')
	{
		parse_str($parts[1], $params);
		if(isset($params['com'], $params['act']) && $params['com'] === 'daotao' && $params['act'] === 'xe')
		{
			foreach(array('keyword','hang','loai_xe','nhan_hieu','tt_tl','expiry','p') as $key)
				if(isset($params[$key]) && is_scalar($params[$key]) && (string)$params[$key] !== '') $q[$key] = (string)$params[$key];
		}
	}
	return $url.($q ? '&'.http_build_query($q) : '');
}

function dt_xe_suachua_url($idXe, $ret = '', $params = array())
{
	$q = array('com' => 'daotao', 'act' => 'xeSuachua', 'id_xe' => (int)$idXe);
	if($ret !== '') $q['ret'] = dt_xe_suachua_list_url($ret);
	foreach($params as $key => $value) $q[$key] = $value;
	return 'index.php?'.http_build_query($q);
}

function dt_xe_suachua_lock_vehicles($ids)
{
	global $d;
	$ids = array_values(array_unique(array_filter(array_map('intval', (array)$ids), function($id) { return $id > 0; })));
	sort($ids, SORT_NUMERIC);
	if(!$ids) return array();
	$marks = implode(',', array_fill(0, count($ids), '?'));
	return $d->rawQuery("select id, bien_so from #_dt_xe where id in ($marks) order by id for update", $ids);
}

function dt_xe_suachua_deletion_blockers($ids)
{
	global $d;
	$ids = array_values(array_unique(array_filter(array_map('intval', (array)$ids), function($id) { return $id > 0; })));
	if(!$ids) return array();
	$marks = implode(',', array_fill(0, count($ids), '?'));
	return $d->rawQuery("select x.id, x.bien_so, count(r.id) as repairs from #_dt_xe x join #_dt_xe_suachua r on r.id_xe = x.id where x.id in ($marks) group by x.id, x.bien_so order by x.id", $ids);
}

function dt_xe_suachua_error_message($rows)
{
	$plates = array();
	foreach((array)$rows as $row) $plates[] = $row['bien_so'];
	return 'Không thể xóa xe có nhật ký sửa chữa ('.implode(', ', $plates).'). Hãy thanh lý xe để giữ lại lịch sử.';
}

function dt_xe_suachua_check_read($result, $back, $message)
{
	global $d, $func;
	$failed = method_exists($d, 'getLastErrorCode') ? $d->getLastErrorCode() !== '00000' : $result === false;
	if($failed)
		$func->transfer($message, $back, false);
	return $result;
}

function dt_xe_suachua_assert_vehicles_deletable($ids, $back)
{
	global $d, $func;
	$locked = dt_xe_suachua_lock_vehicles($ids);
	if($d->transactionFailed())
	{
		$d->rollback();
		$func->transfer('Không thể kiểm tra nhật ký sửa chữa; đã hủy thao tác xóa.', $back, false);
	}
	$blockers = dt_xe_suachua_deletion_blockers(array_column((array)$locked, 'id'));
	if($d->transactionFailed())
	{
		$d->rollback();
		$func->transfer('Không thể kiểm tra nhật ký sửa chữa; đã hủy thao tác xóa.', $back, false);
	}
	if($blockers)
	{
		$d->rollback();
		$func->transfer(dt_xe_suachua_error_message($blockers), $back, false);
	}
	return array_map('intval', array_column((array)$locked, 'id'));
}

function dt_xe_suachua_reminder($date, $today = null)
{
	$date = dt_xe_suachua_date_value($date);
	if(!$date) return 'none';
	$today = $today ? dt_xe_suachua_date_value($today) : date('Y-m-d');
	if(!$today) return 'none';
	if($date < $today) return 'overdue';
	if($date === $today) return 'today';
	$days = (int)((strtotime($date.' 00:00:00') - strtotime($today.' 00:00:00')) / 86400);
	return $days <= 30 ? 'soon' : 'later';
}

function dt_xe_suachua_page()
{
	global $d, $func, $xe_suachua_vehicle, $xe_suachua_rows, $xe_suachua_summary, $xe_suachua_latest,
		$xe_suachua_paging, $xe_suachua_page, $xe_suachua_total_pages, $xe_suachua_ret,
		$xe_suachua_edit, $xe_suachua_values, $xe_suachua_errors;
	$idXe = (int)($_GET['id_xe'] ?? 0);
	$xe_suachua_ret = dt_xe_suachua_list_url($_GET['ret'] ?? '');
	$xe_suachua_vehicle = dt_xe_suachua_check_read($d->rawQueryOne('select id, bien_so, nhan_hieu, gv_hoten, thanh_ly, ngay_thanh_ly from #_dt_xe where id = ? limit 0,1', array($idXe)), $xe_suachua_ret, 'Không thể tải thông tin xe.');
	if(!$idXe || !$xe_suachua_vehicle) $func->transfer('Xe không tồn tại. Không thể mở nhật ký sửa chữa.', $xe_suachua_ret, false);

	$xe_suachua_edit = (int)($_GET['edit'] ?? 0);
	$xe_suachua_values = array();
	$xe_suachua_errors = array();
	$pending = $_SESSION['dt_xe_suachua_form'] ?? null;
	if(is_array($pending) && (int)($pending['id_xe'] ?? 0) === $idXe && (int)($pending['id'] ?? 0) === $xe_suachua_edit)
	{
		$xe_suachua_values = $pending['values'];
		$xe_suachua_errors = $pending['errors'];
		unset($_SESSION['dt_xe_suachua_form']);
	}
	elseif($xe_suachua_edit)
	{
		$row = dt_xe_suachua_check_read($d->rawQueryOne('select * from #_dt_xe_suachua where id = ? and id_xe = ? limit 0,1', array($xe_suachua_edit, $idXe)), dt_xe_suachua_url($idXe, $xe_suachua_ret), 'Không thể tải lần sửa chữa cần cập nhật.');
		if(!$row) $func->transfer('Lần sửa chữa không tồn tại trong hồ sơ xe này.', dt_xe_suachua_url($idXe, $xe_suachua_ret), false);
		$xe_suachua_values = $row;
	}
	elseif(isset($_GET['new']))
	{
		$xe_suachua_values = array('ngay_sua' => date('Y-m-d'), 'trang_thai' => 'dang_sua');
	}

	$summary = dt_xe_suachua_check_read($d->rawQueryOne("select count(*) as tong, sum(case when trang_thai = 'dang_sua' then 1 else 0 end) as dang_sua, coalesce(sum(chi_phi), 0) as chi_phi, sum(case when chi_phi is null then 1 else 0 end) as chua_chi_phi from #_dt_xe_suachua where id_xe = ?", array($idXe)), dt_xe_suachua_url($idXe, $xe_suachua_ret), 'Không thể tải thống kê nhật ký sửa chữa.');
	$xe_suachua_summary = $summary ?: array('tong' => 0, 'dang_sua' => 0, 'chi_phi' => 0, 'chua_chi_phi' => 0);
	$xe_suachua_latest = dt_xe_suachua_check_read($d->rawQueryOne('select ngay_sua, ngay_baoduong, km_baoduong from #_dt_xe_suachua where id_xe = ? order by ngay_sua desc, id desc limit 0,1', array($idXe)), dt_xe_suachua_url($idXe, $xe_suachua_ret), 'Không thể tải lịch bảo dưỡng tiếp theo.');

	$countRow = dt_xe_suachua_check_read($d->rawQueryOne('select count(*) as num from #_dt_xe_suachua where id_xe = ?', array($idXe)), dt_xe_suachua_url($idXe, $xe_suachua_ret), 'Không thể tải số lượng nhật ký sửa chữa.');
	$total = (int)($countRow['num'] ?? 0);
	$perPage = 15;
	$xe_suachua_total_pages = max(1, (int)ceil($total / $perPage));
	$xe_suachua_page = min(max(1, (int)($_GET['p'] ?? 1)), $xe_suachua_total_pages);
	$offset = ($xe_suachua_page - 1) * $perPage;
	$xe_suachua_rows = dt_xe_suachua_check_read($d->rawQuery('select * from #_dt_xe_suachua where id_xe = ? order by ngay_sua desc, id desc limit '.$offset.','.$perPage, array($idXe)), dt_xe_suachua_url($idXe, $xe_suachua_ret), 'Không thể tải lịch sử sửa chữa.');
	$xe_suachua_paging = array();
	$pageNumbers = range(max(1, $xe_suachua_page - 2), min($xe_suachua_total_pages, $xe_suachua_page + 2));
	if($pageNumbers[0] > 1)
	{
		$xe_suachua_paging[] = array('page' => 1, 'url' => dt_xe_suachua_url($idXe, $xe_suachua_ret, array('p' => 1)));
		if($pageNumbers[0] > 2) $xe_suachua_paging[] = array('page' => '…', 'url' => '', 'disabled' => true);
	}
	if($xe_suachua_page > 1) $xe_suachua_paging[] = array('page' => 'Trước', 'url' => dt_xe_suachua_url($idXe, $xe_suachua_ret, array('p' => $xe_suachua_page - 1)));
	foreach($pageNumbers as $p)
		$xe_suachua_paging[] = array('page' => $p, 'url' => dt_xe_suachua_url($idXe, $xe_suachua_ret, array('p' => $p)), 'active' => $p === $xe_suachua_page);
	if($pageNumbers[count($pageNumbers) - 1] < $xe_suachua_total_pages - 1)
		$xe_suachua_paging[] = array('page' => '…', 'url' => '', 'disabled' => true);
	if($pageNumbers[count($pageNumbers) - 1] < $xe_suachua_total_pages)
		$xe_suachua_paging[] = array('page' => $xe_suachua_total_pages, 'url' => dt_xe_suachua_url($idXe, $xe_suachua_ret, array('p' => $xe_suachua_total_pages)));
	if($xe_suachua_page < $xe_suachua_total_pages) $xe_suachua_paging[] = array('page' => 'Sau', 'url' => dt_xe_suachua_url($idXe, $xe_suachua_ret, array('p' => $xe_suachua_page + 1)));
}

function dt_xe_suachua_image()
{
	global $d;
	$idXe = (int)($_GET['id_xe'] ?? 0);
	$id = (int)($_GET['id'] ?? 0);
	$filename = is_scalar($_GET['image'] ?? null) ? (string)$_GET['image'] : '';
	if(!dt_xe_suachua_image_authorized($idXe, $id, $filename))
	{
		http_response_code(404);
		exit;
	}
	$path = dt_xe_suachua_image_path($filename);
	if(!$path)
	{
		http_response_code(404);
		exit;
	}
	header('Content-Type: image/jpeg');
	header('Content-Length: '.filesize($path));
	header('Cache-Control: private, no-store');
	header('X-Content-Type-Options: nosniff');
	readfile($path);
	exit;
}

function dt_xe_suachua_save()
{
	global $d, $func;
	$idXe = (int)($_POST['id_xe'] ?? 0);
	$id = (int)($_POST['id'] ?? 0);
	$ret = dt_xe_suachua_list_url($_POST['ret'] ?? '');
	$back = dt_xe_suachua_url($idXe, $ret, $id ? array('edit' => $id) : array('new' => 1));
	if(strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !$idXe)
		$func->transfer('Yêu cầu không hợp lệ.', $back, false);

	list($data, $errors, $values) = dt_xe_suachua_validate($_POST);
	if($errors)
	{
		$_SESSION['dt_xe_suachua_form'] = array('id_xe' => $idXe, 'id' => $id, 'values' => $values, 'errors' => $errors);
		$func->transfer('Vui lòng kiểm tra các thông tin được đánh dấu.', $back, false);
	}

	$d->startTransaction();
	$vehicle = $d->rawQueryOne('select id, bien_so from #_dt_xe where id = ? limit 0,1 for update', array($idXe));
	if(!$vehicle)
	{
		$d->rollback();
		$func->transfer('Xe không tồn tại. Không thể lưu nhật ký.', $ret, false);
	}
	if($id)
	{
		$existing = $d->rawQueryOne('select id from #_dt_xe_suachua where id = ? and id_xe = ? limit 0,1 for update', array($id, $idXe));
		if(!$existing)
		{
			$d->rollback();
			$func->transfer('Lần sửa chữa không thuộc xe này. Không có thay đổi nào được lưu.', $back, false);
		}
		$data['nguoi_capnhat'] = dt_username();
		$d->where('id', $id);
		$ok = $d->update('dt_xe_suachua', $data);
	}
	else
	{
		$data['id_xe'] = $idXe;
		$data['nguoi_tao'] = dt_username();
		$data['nguoi_capnhat'] = dt_username();
		$ok = $d->insert('dt_xe_suachua', $data);
	}
	if($ok === false || $d->transactionFailed() || $d->commit() === false)
	{
		if(method_exists($d, 'rollback')) $d->rollback();
		$_SESSION['dt_xe_suachua_form'] = array('id_xe' => $idXe, 'id' => $id, 'values' => $values, 'errors' => array());
		$func->transfer('Không thể lưu nhật ký sửa chữa do lỗi cơ sở dữ liệu. Vui lòng giữ lại thông tin và thử lại.', $back, false);
	}
	dt_audit($id ? 'update' : 'create', 'xe_suachua', 1, 'id_xe='.$idXe.' id='.$id.' bien_so='.$vehicle['bien_so']);
	$func->transfer($id ? 'Đã cập nhật lần sửa chữa.' : 'Đã thêm lần sửa chữa.', dt_xe_suachua_url($idXe, $ret));
}

function dt_xe_suachua_delete()
{
	global $d, $func;
	$idXe = (int)($_POST['id_xe'] ?? 0);
	$id = (int)($_POST['id'] ?? 0);
	$ret = dt_xe_suachua_list_url($_POST['ret'] ?? '');
	$back = dt_xe_suachua_url($idXe, $ret);
	if(strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !$idXe || !$id)
		$func->transfer('Yêu cầu xóa không hợp lệ.', $back, false);

	$d->startTransaction();
	$vehicle = $d->rawQueryOne('select id, bien_so from #_dt_xe where id = ? limit 0,1 for update', array($idXe));
	$row = $d->rawQueryOne('select id from #_dt_xe_suachua where id = ? and id_xe = ? limit 0,1 for update', array($id, $idXe));
	if($d->transactionFailed())
	{
		$d->rollback();
		$func->transfer('Không thể kiểm tra dữ liệu sửa chữa do lỗi cơ sở dữ liệu.', $back, false);
	}
	if(!$vehicle || !$row)
	{
		$d->rollback();
		$func->transfer('Lần sửa chữa không thuộc xe này hoặc không còn tồn tại.', $back, false);
	}
	$d->rawQuery('delete from #_dt_xe_suachua where id = ? and id_xe = ?', array($id, $idXe));
	if($d->transactionFailed() || $d->commit() === false)
	{
		if(method_exists($d, 'rollback')) $d->rollback();
		$func->transfer('Không thể xóa lần sửa chữa do lỗi cơ sở dữ liệu.', $back, false);
	}
	dt_audit('delete', 'xe_suachua', 1, 'id_xe='.$idXe.' id='.$id.' bien_so='.$vehicle['bien_so']);
	$func->transfer('Đã xóa lần sửa chữa.', $back);
}
