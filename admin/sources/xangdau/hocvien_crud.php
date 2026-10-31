<?php
if(!defined('SOURCES')) die("Error");

/* ============================ Danh sách & xóa học viên ============================ */

function xd_get_hocvien()
{
	global $d, $func, $curPage, $items, $paging, $xd_filter_keyword, $xd_filter_nhom, $xd_filter_trangthai, $xd_filter_tt_from, $xd_filter_tt_to, $xd_hocvien_teachers, $xd_filter_khoa, $xd_filter_gv, $xd_hocvien_khoas, $xd_hocvien_khoa_groups, $xd_hocvien_total, $xd_hocvien_offset, $xd_hocvien_all_total;

	$where = "";
	$params = array();
	$xd_filter_keyword = '';
	$xd_filter_nhom = '';
	$xd_filter_trangthai = '';
	$xd_filter_tt_from = '';
	$xd_filter_tt_to = '';
	$xd_filter_khoa = isset($_REQUEST['khoa']) ? trim((string)$_REQUEST['khoa']) : '';
	$xd_filter_gv = isset($_REQUEST['gv']) ? trim((string)$_REQUEST['gv']) : '';

	if(isset($_REQUEST['keyword']) && trim($_REQUEST['keyword']) !== '')
	{
		$xd_filter_keyword = trim($_REQUEST['keyword']);
	}
	if(isset($_REQUEST['nhom']) && in_array($_REQUEST['nhom'], array('BT', 'CK', 'DAT'), true))
	{
		$xd_filter_nhom = $_REQUEST['nhom'];
	}
	if(isset($_REQUEST['trangthai']) && in_array($_REQUEST['trangthai'], array('da', 'chua'), true))
	{
		$xd_filter_trangthai = $_REQUEST['trangthai'];
	}
	if(isset($_REQUEST['tt_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['tt_from']))
	{
		$xd_filter_tt_from = $_REQUEST['tt_from'];
	}
	if(isset($_REQUEST['tt_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['tt_to']))
	{
		$xd_filter_tt_to = $_REQUEST['tt_to'];
	}

	$scope = ($xd_filter_trangthai === 'da') ? 'paid' : (($xd_filter_trangthai === 'chua') ? 'unpaid' : 'all');
	list($where, $params) = xd_hocvien_where_and_params($scope, $xd_filter_keyword, $xd_filter_nhom, $xd_filter_tt_from, $xd_filter_tt_to, $xd_filter_khoa, $xd_filter_gv);
	$per_page = 20;
	$startpoint = ($curPage * $per_page) - $per_page;
	$sql = "select * from #_xd_hocvien where $where order by ".xd_hocvien_order_clause(true)." limit ".$startpoint.",".$per_page;
	$items = $d->rawQuery($sql, $params);

	$count = $d->rawQueryOne("select count(*) as num from #_xd_hocvien where $where", $params);
	$total = isset($count['num']) ? (int)$count['num'] : 0;
	$xd_hocvien_total = $total;
	$xd_hocvien_offset = max(0, $startpoint);
	$allCount = $d->rawQueryOne("select count(*) as num from #_xd_hocvien");
	$xd_hocvien_all_total = (int)($allCount['num'] ?? 0);
	$xd_hocvien_teachers = $d->rawQuery(
		"select gv_key, max(gv_hoten) as gv_hoten,
			 sum(case when ngay_thanh_toan is null and id_bangke = 0 then 1 else 0 end) as can_delete,
			 sum(case when ngay_thanh_toan is not null or id_bangke > 0 then 1 else 0 end) as protected_count
		 from #_xd_hocvien where gv_key <> '' group by gv_key order by substring_index(max(gv_hoten), ' ', -1) asc, max(gv_hoten) asc",
		array()
	);
	$xd_hocvien_khoas = $d->rawQuery("select x.khoa, count(*) as so, sum(case when x.user_tao='Đồng bộ DAT' then 1 else 0 end) as so_dong_bo,
		coalesce(nullif(max(case when hd.id is not null and k.id=hd.id_khoa then k.ten_khoa end),''),nullif(max(case when k.ma_khoa=x.khoa or k.ten_khoa=x.khoa then k.ten_khoa end),'')) as ten_khoa
		from #_xd_hocvien x
		left join #_dt_hocvien hd on hd.id=x.thu_tu_file and hd.cccd=x.cccd and x.user_tao='Đồng bộ DAT'
		left join #_dt_khoa k on (k.id=hd.id_khoa or k.ma_khoa=x.khoa or k.ten_khoa=x.khoa) and k.ten_khoa<>''
		where x.khoa <> '' group by x.khoa order by x.khoa asc", array());
	xd_hocvien_attach_dat_counts($xd_hocvien_khoas);
	$xd_hocvien_khoa_groups=array('synced'=>array(),'mixed'=>array(),'stored'=>array(),'rank'=>array());
	foreach($xd_hocvien_khoas as $course)
	{
		$group=xd_hocvien_course_group($course);
		$xd_hocvien_khoa_groups[$group][]=$course;
	}

	$url = "index.php?com=xangdau&act=hocvien";
	if($xd_filter_keyword !== '') $url .= '&keyword='.urlencode($xd_filter_keyword);
	if($xd_filter_khoa !== '') $url .= '&khoa='.urlencode($xd_filter_khoa);
	if($xd_filter_gv !== '') $url .= '&gv='.urlencode($xd_filter_gv);
	if($xd_filter_nhom !== '') $url .= '&nhom='.urlencode($xd_filter_nhom);
	if($xd_filter_trangthai !== '') $url .= '&trangthai='.urlencode($xd_filter_trangthai);
	if($xd_filter_tt_from !== '') $url .= '&tt_from='.urlencode($xd_filter_tt_from);
	if($xd_filter_tt_to !== '') $url .= '&tt_to='.urlencode($xd_filter_tt_to);
	$paging = $func->pagination($total, $per_page, $curPage, $url);
}

function xd_hocvien_attach_dat_counts(&$courses)
{
	global $d;
	if(!$courses) return;
	require_once LIBRARIES.'daotao_lib.php';
	$names = array_column($courses, 'khoa');
	$marks = implode(',', array_fill(0, count($names), '?'));
	$students = $d->rawQuery("select h.id, h.hang, k.ma_khoa, k.ten_khoa, coalesce(o.dat,0) as override_dat,
		coalesce(p.c,0) as c, coalesce(p.d,0) as d, coalesce(p.b,0) as b, coalesce(p.e,0) as e
		from #_dt_hocvien h inner join #_dt_khoa k on k.id=h.id_khoa
		left join (select id_khoa,cccd,
			sum(case when la_xe_tudong=1 then gio_thuchanh else 0 end) as c,
			sum(case when la_xe_tudong=1 then 0 else gio_thuchanh end) as d,
			sum(gio_dem) as b,sum(km) as e
			from #_dt_dat_phien group by id_khoa,cccd) p on p.id_khoa=h.id_khoa and p.cccd=h.cccd
		left join #_dt_dat_override o on o.id_khoa=h.id_khoa and o.cccd=h.cccd
		where k.ma_khoa in ($marks) or k.ten_khoa in ($marks)", array_merge($names,$names));
	if($d->getLastErrorCode() !== '00000') throw new RuntimeException('Không đọc được số học viên đạt DAT theo khóa.');
	$counts = array();
	foreach($students as $student)
	{
		$agg = array('a'=>round((float)$student['c']+(float)$student['d'],4));
		foreach(array('b','c','d','e') as $key) $agg[$key] = round((float)$student[$key],4);
		list($passed) = dt_dat_danhgia($student['hang'], $agg);
		foreach(array_unique(array($student['ma_khoa'],$student['ten_khoa'])) as $name)
		{
			if(!isset($counts[$name])) $counts[$name] = 0;
			if($passed || (int)$student['override_dat'] === 1) $counts[$name]++;
		}
	}
	foreach($courses as &$course)
		if(array_key_exists($course['khoa'], $counts)) $course['so_dat'] = $counts[$course['khoa']];
	unset($course);
}

function xd_hocvien_course_group($course)
{
	$name=trim((string)$course['khoa']);
	$total=(int)$course['so'];$synced=(int)($course['so_dong_bo']??0);
	$ranks=array('bss'=>'B số sàn','btd'=>'B tự động','c1'=>'C1','c'=>'C','ce'=>'CE');
	$key=strtolower($name);
	if($synced>0&&isset($ranks[$key]))return 'rank';
	if($synced===$total&&$total>0)return 'synced';
	if($synced>0)return 'mixed';
	return 'stored';
}

function xd_hocvien_course_label($course)
{
	$name=trim((string)$course['khoa']);
	$total=(int)$course['so'];$synced=(int)($course['so_dong_bo']??0);
	$trainingName=trim((string)($course['ten_khoa']??''));
	$ranks=array('bss'=>'B số sàn','btd'=>'B tự động','c1'=>'C1','c'=>'C','ce'=>'CE');
	$key=strtolower($name);
	$group=xd_hocvien_course_group($course);
	if($group==='rank')
		return course_option_label(array('khoa'=>$name,'ten_khoa'=>$ranks[$key],'so'=>$total));
	$label=course_option_label($course);
	if(isset($course['so_dat'])) $label.=' · '.(int)$course['so_dat'].' đạt DAT';
	return $label;
}

function xd_hocvien_where_and_params($scope = 'all', $keyword = '', $nhom = '', $ttFrom = '', $ttTo = '', $khoa = '', $gvKey = '')
{
	$where = 'id > 0';
	$params = array();
	if($scope === 'paid') { $where .= ' and ngay_thanh_toan is not null'; }
	elseif($scope === 'unpaid') { $where .= ' and ngay_thanh_toan is null'; }
	if($keyword !== '') { $where .= ' and (ho_ten like ? or cccd like ? or gv_hoten like ?)'; $params[] = '%'.$keyword.'%'; $params[] = '%'.$keyword.'%'; $params[] = '%'.$keyword.'%'; }
	if($nhom !== '') { $where .= ' and nhom = ?'; $params[] = $nhom; }
	if($khoa !== '') { $where .= ' and khoa = ?'; $params[] = $khoa; }
	if($gvKey !== '') { $where .= ' and gv_key = ?'; $params[] = $gvKey; }
	if($ttFrom !== '') { $where .= ' and ngay_thanh_toan >= ?'; $params[] = $ttFrom; }
	if($ttTo !== '') { $where .= ' and ngay_thanh_toan <= ?'; $params[] = $ttTo; }
	return array($where, $params);
}

function xd_delete_hocvien()
{
	global $d, $func, $curPage;

	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	$redirect = "index.php?com=xangdau&act=hocvien&p=".$curPage;

	if($id > 0)
	{
		$d->rawQuery("delete from #_xd_hocvien where id = ?", array($id));
		$func->transfer("Xóa học viên thành công", $redirect);
	}
	elseif(isset($_GET['listid']))
	{
		$listid = explode(",", $_GET['listid']);
		foreach($listid as $tid)
		{
			$tid = (int)$tid;
			if($tid > 0) $d->rawQuery("delete from #_xd_hocvien where id = ?", array($tid));
		}
		$func->transfer("Xóa học viên thành công", $redirect);
	}
	else $func->transfer("Không nhận được dữ liệu", $redirect, false);
}

/* Xóa các học viên đã chọn; giữ lại học viên đã thanh toán hoặc thuộc bảng kê. */
function xd_delete_hocvien_selected()
{
	global $d, $func;
	$ret = xd_safe_ret($_POST['ret'] ?? '', 'index.php?com=xangdau&act=hocvien');
	if(($_POST['delete_course'] ?? '') === '1')
	{
		if(!xd_can_xoa() || empty($_SESSION['xd_dat_csrf']) ||
			!hash_equals((string)$_SESSION['xd_dat_csrf'], (string)($_POST['csrf'] ?? '')))
			$func->transfer('Không có quyền hoặc phiên thao tác không hợp lệ.', $ret, false);
		$course = trim((string)($_POST['khoa'] ?? ''));
		if($course === '') $func->transfer('Chọn một khóa XD trước khi xóa toàn khóa.', $ret, false);
		try { $result = xd_course_change($course, '', true); }
		catch(RuntimeException $error) { $func->transfer($error->getMessage(), $ret, false); return; }
		$func->transfer('Đã xóa '.$result['changed'].' học viên trong toàn khóa '.$course.
			'; giữ lại '.$result['kept'].' hồ sơ có thanh toán/bảng kê/duyệt/kiểm tra. Sao lưu: '.$result['backup'].
			'. Có thể bấm Đồng bộ HV đạt DAT để tạo lại hồ sơ đủ điều kiện; các khoản tiền nguồn cần import lại Excel.', $ret);
		return;
	}
	$ids = array_values(array_filter(array_map('intval', (array)($_POST['ids'] ?? array()))));
	if(!$ids) $func->transfer("Chưa chọn học viên nào để xóa.", $ret, false);

	$in = implode(',', array_fill(0, count($ids), '?'));
	$row = $d->rawQueryOne("select sum(case when ngay_thanh_toan is null and id_bangke = 0 then 1 else 0 end) as can_delete, count(*) as total from #_xd_hocvien where id in ($in)", $ids);
	$canDelete = (int)($row['can_delete'] ?? 0);
	$kept = (int)($row['total'] ?? 0) - $canDelete;
	if($canDelete === 0) $func->transfer("Các học viên đã chọn đều đã thanh toán hoặc thuộc bảng kê, không thể xóa.", $ret, false);

	$ok = $d->rawQuery("delete from #_xd_hocvien where id in ($in) and ngay_thanh_toan is null and id_bangke = 0", $ids);
	if($ok === false) $func->transfer("Không thể xóa học viên đã chọn.", $ret, false);
	$msg = "Đã xóa $canDelete học viên.";
	if($kept > 0) $msg .= " Giữ lại $kept học viên đã thanh toán hoặc thuộc bảng kê.";
	$func->transfer($msg, $ret);
}

function xd_safe_ret($raw, $default)
{
	$ret = rawurldecode(trim((string)$raw));
	if($ret === '' || strpos($ret, 'index.php?com=xangdau') !== 0 || strpos($ret, "\n") !== false || strpos($ret, "\r") !== false) return $default;
	return $ret;
}

function xd_delete_hocvien_by_gv()
{
	global $d, $func;

	$gvKey = isset($_GET['gv_key']) ? trim((string)$_GET['gv_key']) : '';
	$redirect = "index.php?com=xangdau&act=hocvien";
	if($gvKey === '') $func->transfer("Không xác định được giáo viên.", $redirect, false);

	$summary = $d->rawQueryOne(
		"select max(gv_hoten) as gv_hoten,
			sum(case when ngay_thanh_toan is null and id_bangke = 0 then 1 else 0 end) as can_delete,
			sum(case when ngay_thanh_toan is not null or id_bangke > 0 then 1 else 0 end) as protected_count
		 from #_xd_hocvien where gv_key = ?",
		array($gvKey)
	);
	if(empty($summary) || (int)($summary['can_delete'] ?? 0) === 0)
		$func->transfer("Không có học viên chưa thanh toán để xóa cho giáo viên này.", $redirect, false);

	$backup = xd_backup_table_json('xd_hocvien', 'gv');
	if($backup === '') $func->transfer("Không thể sao lưu dữ liệu; đã hủy thao tác xóa theo giáo viên.", $redirect, false);
	$d->startTransaction();
	$ok = $d->rawQuery("delete from #_xd_hocvien where gv_key = ? and ngay_thanh_toan is null and id_bangke = 0", array($gvKey));
	if($ok === false)
	{
		$d->rollback();
		$func->transfer("Không thể xóa học viên theo giáo viên.", $redirect, false);
	}
	$d->commit();

	$deleted = (int)($summary['can_delete'] ?? 0);
	$protected = (int)($summary['protected_count'] ?? 0);
	$name = trim((string)($summary['gv_hoten'] ?? $gvKey));
	$message = "Đã xóa $deleted học viên chưa thanh toán của giáo viên $name.";
	if($protected > 0) $message .= " Giữ lại $protected học viên đã thanh toán hoặc thuộc bảng kê.";
	$message .= " Sao lưu: $backup";
	$func->transfer($message, $redirect);
}

function xd_update_hocvien_status()
{
	global $d, $func, $curPage;

	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	$status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
	$redirect = "index.php?com=xangdau&act=hocvien&p=".(int)$curPage;
	if($id <= 0 || !in_array($status, array('da', 'chua'), true))
		$func->transfer("Trạng thái học viên không hợp lệ", $redirect, false);

	$row = $d->rawQueryOne("select id, nhom, khoa, dinh_muc_ca_nhan, da_dieu_chinh_tt, ngay_thanh_toan from #_xd_hocvien where id = ? limit 0,1", array($id));
	if(empty($row)) $func->transfer("Không tìm thấy học viên", $redirect, false);

	if($status === 'da')
	{
		if($row['ngay_thanh_toan'] !== null)
			$func->transfer("Học viên này đã được cập nhật thanh toán trước đó, không thể cập nhật trùng.", $redirect, false);
		$config = getXdConfig($d);
		$dinhMuc = (int)xdDinhMucHocVien($config, $row);
		$soTien = (int)xdMucTheoNhom($config, $row['nhom']);
		$ok = $d->rawQuery(
			"update #_xd_hocvien set ngay_thanh_toan = ?, dinh_muc = ?, so_tien_thanh_toan = ?, id_bangke = 0 where id = ? and ngay_thanh_toan is null",
			array(date('Y-m-d'), $dinhMuc, $soTien, $id)
		);
		$message = "Đã cập nhật học viên thành đã thanh toán";
	}
	else
	{
		$ok = $d->rawQuery(
			"update #_xd_hocvien set ngay_thanh_toan = NULL, dinh_muc = 0, so_tien_thanh_toan = 0, id_bangke = 0 where id = ?",
			array($id)
		);
		$message = "Đã cập nhật học viên thành chưa thanh toán";
	}

	if($ok === false) $func->transfer("Không thể cập nhật trạng thái học viên", $redirect, false);
	$func->transfer($message, $redirect);
}

/* Sửa giáo viên / nhóm cho 1 học viên XD (chỉ khi chưa thanh toán); quay về màn trước (giữ bộ lọc). */
function xd_update_hocvien_info()
{
	global $d, $func, $curPage;
	$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
	$nhom = isset($_POST['nhom']) ? trim((string)$_POST['nhom']) : '';
	$gv = isset($_POST['gv_hoten']) ? trim((string)$_POST['gv_hoten']) : '';
	$ret = isset($_POST['ret']) ? rawurldecode(trim((string)$_POST['ret'])) : '';
	if($ret === '' || strpos($ret, 'index.php?com=xangdau') !== 0 || strpos($ret, "\n") !== false || strpos($ret, "\r") !== false)
		$ret = "index.php?com=xangdau&act=hocvien&p=".(int)$curPage;

	if($id <= 0) $func->transfer("Học viên không hợp lệ", $ret, false);
	if(!in_array($nhom, array('BT', 'CK', 'DAT'), true)) $func->transfer("Nhóm không hợp lệ (BT/CK/DAT)", $ret, false);
	$row = $d->rawQueryOne("select id, ngay_thanh_toan from #_xd_hocvien where id = ? limit 0,1", array($id));
	if(empty($row)) $func->transfer("Không tìm thấy học viên", $ret, false);
	if($row['ngay_thanh_toan'] !== null) $func->transfer("Học viên đã thanh toán — không thể sửa giáo viên/nhóm.", $ret, false);

	$data = array('nhom' => $nhom, 'gv_hoten' => htmlspecialchars($gv, ENT_QUOTES, 'UTF-8'), 'gv_key' => xd_gv_key($gv));
	$d->where('id', $id);
	$ok = $d->update('xd_hocvien', $data);
	$func->transfer($ok !== false ? "Đã cập nhật giáo viên / nhóm học viên" : "Không thể cập nhật học viên", $ret);
}

function xd_delete_all_hocvien()
{
	global $d, $func;
	$backup = xd_backup_table_json('xd_hocvien', 'all_hocvien');
	if($backup === '') $func->transfer("Không thể sao lưu dữ liệu; đã hủy thao tác xóa toàn bộ học viên.", "index.php?com=xangdau&act=hocvien", false);
	$count = $d->rawQueryOne("select count(*) as num from #_xd_hocvien");
	$ok = $d->rawQuery("delete from #_xd_hocvien");
	$n = isset($count['num']) ? (int)$count['num'] : 0;
	if($ok === false) $func->transfer("Không thể xóa toàn bộ học viên", "index.php?com=xangdau&act=hocvien", false);
	$func->transfer("Đã xóa toàn bộ $n học viên. Sao lưu: $backup", "index.php?com=xangdau&act=hocvien");
}
