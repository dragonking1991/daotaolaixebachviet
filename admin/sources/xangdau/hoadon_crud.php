<?php
if(!defined('SOURCES')) die("Error");

/* ============================ Danh sách & xóa hóa đơn ============================ */

function xd_get_hoadon()
{
	global $d, $func, $curPage, $items, $paging, $xd_filter_keyword, $xd_filter_from, $xd_filter_to, $xd_filter_ky, $xd_filter_kt_from, $xd_filter_kt_to;

	$where = "";
	$params = array();
	$xd_filter_keyword = '';
	$xd_filter_from = '';
	$xd_filter_to = '';
	$xd_filter_ky = '';
	$xd_filter_kt_from = '';
	$xd_filter_kt_to = '';

	if(isset($_REQUEST['keyword']) && trim($_REQUEST['keyword']) !== '')
	{
		$xd_filter_keyword = trim($_REQUEST['keyword']);
		$where .= " and (ma_hoa_don like ? or gv_hoten like ?)";
		$params[] = '%'.$xd_filter_keyword.'%';
		$params[] = '%'.$xd_filter_keyword.'%';
	}
	if(isset($_REQUEST['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['from_date']))
	{
		$xd_filter_from = $_REQUEST['from_date'];
		$where .= " and ngay_hoa_don >= ?";
		$params[] = $xd_filter_from;
	}
	if(isset($_REQUEST['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['to_date']))
	{
		$xd_filter_to = $_REQUEST['to_date'];
		$where .= " and ngay_hoa_don <= ?";
		$params[] = $xd_filter_to;
	}
	if(isset($_REQUEST['ky']) && trim($_REQUEST['ky']) !== '')
	{
		$xd_filter_ky = trim($_REQUEST['ky']);
		$where .= " and ky = ?";
		$params[] = $xd_filter_ky;
	}
	if(isset($_REQUEST['kt_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['kt_from']))
	{
		$xd_filter_kt_from = $_REQUEST['kt_from'];
		$where .= " and ngay_kiem_tra >= ?";
		$params[] = $xd_filter_kt_from;
	}
	if(isset($_REQUEST['kt_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['kt_to']))
	{
		$xd_filter_kt_to = $_REQUEST['kt_to'];
		$where .= " and ngay_kiem_tra <= ?";
		$params[] = $xd_filter_kt_to;
	}

	$per_page = 20;
	$startpoint = ($curPage * $per_page) - $per_page;
	$sql = "select * from #_xd_hoadon where id > 0 $where order by ngay_hoa_don desc, id desc limit ".$startpoint.",".$per_page;
	$items = $d->rawQuery($sql, $params);

	$count = $d->rawQueryOne("select count(*) as num from #_xd_hoadon where id > 0 $where", $params);
	$total = isset($count['num']) ? (int)$count['num'] : 0;

	$url = "index.php?com=xangdau&act=hoadon";
	if($xd_filter_keyword !== '') $url .= '&keyword='.urlencode($xd_filter_keyword);
	if($xd_filter_from !== '') $url .= '&from_date='.urlencode($xd_filter_from);
	if($xd_filter_to !== '') $url .= '&to_date='.urlencode($xd_filter_to);
	if($xd_filter_ky !== '') $url .= '&ky='.urlencode($xd_filter_ky);
	if($xd_filter_kt_from !== '') $url .= '&kt_from='.urlencode($xd_filter_kt_from);
	if($xd_filter_kt_to !== '') $url .= '&kt_to='.urlencode($xd_filter_kt_to);
	$paging = $func->pagination($total, $per_page, $curPage, $url);
}

function xd_delete_hoadon()
{
	global $d, $func, $curPage;

	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	$redirect = "index.php?com=xangdau&act=hoadon&p=".$curPage;

	if($id > 0)
	{
		$row = $d->rawQueryOne("select da_quyettoan from #_xd_hoadon where id = ? limit 0,1", array($id));
		if($row && (int)$row['da_quyettoan'] === 1) $func->transfer("Hóa đơn đã quyết toán, không thể xóa", $redirect, false);
		$d->rawQuery("delete from #_xd_hoadon where id = ? and da_quyettoan = 0", array($id));
		$func->transfer("Xóa hóa đơn thành công", $redirect);
	}
	elseif(isset($_GET['listid']))
	{
		$listid = explode(",", $_GET['listid']);
		foreach($listid as $tid)
		{
			$tid = (int)$tid;
			if($tid > 0) $d->rawQuery("delete from #_xd_hoadon where id = ? and da_quyettoan = 0", array($tid));
		}
		$func->transfer("Xóa hóa đơn thành công (bỏ qua hóa đơn đã quyết toán)", $redirect);
	}
	else $func->transfer("Không nhận được dữ liệu", $redirect, false);
}

function xd_delete_all_hoadon()
{
	global $d, $func;
	$count = $d->rawQueryOne("select count(*) as num from #_xd_hoadon");
	$ok = $d->rawQuery("delete from #_xd_hoadon");
	$n = isset($count['num']) ? (int)$count['num'] : 0;
	if($ok === false) $func->transfer("Không thể xóa toàn bộ hóa đơn", "index.php?com=xangdau&act=hoadon", false);
	$func->transfer("Đã xóa toàn bộ $n hóa đơn.", "index.php?com=xangdau&act=hoadon");
}

function xd_toggle_kiem_tra_hoadon()
{
	global $d, $func, $curPage;
	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	if($id <= 0) $func->transfer("Không xác định được hóa đơn.", "index.php?com=xangdau&act=hoadon", false);
	$row = $d->rawQueryOne("select ke_toan_kiem_tra, ngay_kiem_tra from #_xd_hoadon where id = ? limit 0,1", array($id));
	if(empty($row)) $func->transfer("Không tìm thấy hóa đơn cần cập nhật.", "index.php?com=xangdau&act=hoadon", false);
	$checked = (int)$row['ke_toan_kiem_tra'] === 1;
	$today = date('Y-m-d');
	$d->rawQuery(
		$checked
			? "update #_xd_hoadon set ke_toan_kiem_tra = 0, ngay_kiem_tra = null where id = ? and ke_toan_kiem_tra = 1"
			: "update #_xd_hoadon set ke_toan_kiem_tra = 1, ngay_kiem_tra = ? where id = ?",
		$checked ? array($id) : array($today, $id)
	);
	$func->transfer($checked ? "Đã bỏ xác nhận kiểm tra hóa đơn." : "Đã xác nhận kiểm tra hóa đơn.", "index.php?com=xangdau&act=hoadon&p=".(int)$curPage, true);
}
