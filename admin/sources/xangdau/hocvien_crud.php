<?php
if(!defined('SOURCES')) die("Error");

/* ============================ Danh sách & xóa học viên ============================ */

function xd_get_hocvien()
{
	global $d, $func, $curPage, $items, $paging, $xd_filter_keyword, $xd_filter_nhom, $xd_filter_trangthai, $xd_filter_tt_from, $xd_filter_tt_to;

	$where = "";
	$params = array();
	$xd_filter_keyword = '';
	$xd_filter_nhom = '';
	$xd_filter_trangthai = '';
	$xd_filter_tt_from = '';
	$xd_filter_tt_to = '';

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
	list($where, $params) = xd_hocvien_where_and_params($scope, $xd_filter_keyword, $xd_filter_nhom, $xd_filter_tt_from, $xd_filter_tt_to);
	$per_page = 20;
	$startpoint = ($curPage * $per_page) - $per_page;
	$sql = "select * from #_xd_hocvien where $where order by id asc limit ".$startpoint.",".$per_page;
	$items = $d->rawQuery($sql, $params);

	$count = $d->rawQueryOne("select count(*) as num from #_xd_hocvien where $where", $params);
	$total = isset($count['num']) ? (int)$count['num'] : 0;

	$url = "index.php?com=xangdau&act=hocvien";
	if($xd_filter_keyword !== '') $url .= '&keyword='.urlencode($xd_filter_keyword);
	if($xd_filter_nhom !== '') $url .= '&nhom='.urlencode($xd_filter_nhom);
	if($xd_filter_trangthai !== '') $url .= '&trangthai='.urlencode($xd_filter_trangthai);
	if($xd_filter_tt_from !== '') $url .= '&tt_from='.urlencode($xd_filter_tt_from);
	if($xd_filter_tt_to !== '') $url .= '&tt_to='.urlencode($xd_filter_tt_to);
	$paging = $func->pagination($total, $per_page, $curPage, $url);
}

function xd_hocvien_where_and_params($scope = 'all', $keyword = '', $nhom = '', $ttFrom = '', $ttTo = '')
{
	$where = 'id > 0';
	$params = array();
	if($scope === 'paid') { $where .= ' and ngay_thanh_toan is not null'; }
	elseif($scope === 'unpaid') { $where .= ' and ngay_thanh_toan is null'; }
	if($keyword !== '') { $where .= ' and (ho_ten like ? or cccd like ? or gv_hoten like ?)'; $params[] = '%'.$keyword.'%'; $params[] = '%'.$keyword.'%'; $params[] = '%'.$keyword.'%'; }
	if($nhom !== '') { $where .= ' and nhom = ?'; $params[] = $nhom; }
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

function xd_update_hocvien_status()
{
	global $d, $func, $curPage;

	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	$status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
	$redirect = "index.php?com=xangdau&act=hocvien&p=".(int)$curPage;
	if($id <= 0 || !in_array($status, array('da', 'chua'), true))
		$func->transfer("Trạng thái học viên không hợp lệ", $redirect, false);

	$row = $d->rawQueryOne("select id, nhom, khoa, ngay_thanh_toan from #_xd_hocvien where id = ? limit 0,1", array($id));
	if(empty($row)) $func->transfer("Không tìm thấy học viên", $redirect, false);

	if($status === 'da')
	{
		if($row['ngay_thanh_toan'] !== null)
			$func->transfer("Học viên này đã được cập nhật thanh toán trước đó, không thể cập nhật trùng.", $redirect, false);
		$config = getXdConfig($d);
		$dinhMuc = (int)xdDinhMucTheoNhom($config, $row['nhom'], isset($row['khoa']) ? $row['khoa'] : '');
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

function xd_delete_all_hocvien()
{
	global $d, $func;
	$count = $d->rawQueryOne("select count(*) as num from #_xd_hocvien");
	$ok = $d->rawQuery("delete from #_xd_hocvien");
	$n = isset($count['num']) ? (int)$count['num'] : 0;
	if($ok === false) $func->transfer("Không thể xóa toàn bộ học viên", "index.php?com=xangdau&act=hocvien", false);
	$func->transfer("Đã xóa toàn bộ $n học viên.", "index.php?com=xangdau&act=hocvien");
}
