<?php
if(!defined('SOURCES')) die("Error");

/* ============================ Danh sách & xóa học viên ============================ */

function xd_get_hocvien()
{
	global $d, $func, $curPage, $items, $paging, $xd_filter_keyword, $xd_filter_nhom, $xd_filter_trangthai;

	$where = "";
	$params = array();
	$xd_filter_keyword = '';
	$xd_filter_nhom = '';
	$xd_filter_trangthai = '';

	if(isset($_REQUEST['keyword']) && trim($_REQUEST['keyword']) !== '')
	{
		$xd_filter_keyword = trim($_REQUEST['keyword']);
		$where .= " and (ho_ten like ? or cccd like ? or gv_hoten like ?)";
		$params[] = '%'.$xd_filter_keyword.'%';
		$params[] = '%'.$xd_filter_keyword.'%';
		$params[] = '%'.$xd_filter_keyword.'%';
	}
	if(isset($_REQUEST['nhom']) && in_array($_REQUEST['nhom'], array('BT', 'CK', 'DAT'), true))
	{
		$xd_filter_nhom = $_REQUEST['nhom'];
		$where .= " and nhom = ?";
		$params[] = $xd_filter_nhom;
	}
	if(isset($_REQUEST['trangthai']) && in_array($_REQUEST['trangthai'], array('da', 'chua'), true))
	{
		$xd_filter_trangthai = $_REQUEST['trangthai'];
		$where .= ($xd_filter_trangthai === 'da') ? " and ngay_thanh_toan is not null" : " and ngay_thanh_toan is null";
	}

	$per_page = 20;
	$startpoint = ($curPage * $per_page) - $per_page;
	$sql = "select * from #_xd_hocvien where id > 0 $where order by id asc limit ".$startpoint.",".$per_page;
	$items = $d->rawQuery($sql, $params);

	$count = $d->rawQueryOne("select count(*) as num from #_xd_hocvien where id > 0 $where", $params);
	$total = isset($count['num']) ? (int)$count['num'] : 0;

	$url = "index.php?com=xangdau&act=hocvien";
	if($xd_filter_keyword !== '') $url .= '&keyword='.urlencode($xd_filter_keyword);
	if($xd_filter_nhom !== '') $url .= '&nhom='.urlencode($xd_filter_nhom);
	if($xd_filter_trangthai !== '') $url .= '&trangthai='.urlencode($xd_filter_trangthai);
	$paging = $func->pagination($total, $per_page, $curPage, $url);
}

function xd_delete_hocvien()
{
	global $d, $func, $curPage;

	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	$redirect = "index.php?com=xangdau&act=hocvien&p=".$curPage;

	if($id > 0)
	{
		$row = $d->rawQueryOne("select ngay_thanh_toan from #_xd_hocvien where id = ? limit 0,1", array($id));
		if($row && $row['ngay_thanh_toan'] !== null) $func->transfer("Học viên đã thanh toán, không thể xóa", $redirect, false);
		$d->rawQuery("delete from #_xd_hocvien where id = ? and ngay_thanh_toan is null", array($id));
		$func->transfer("Xóa học viên thành công", $redirect);
	}
	elseif(isset($_GET['listid']))
	{
		$listid = explode(",", $_GET['listid']);
		foreach($listid as $tid)
		{
			$tid = (int)$tid;
			if($tid > 0) $d->rawQuery("delete from #_xd_hocvien where id = ? and ngay_thanh_toan is null", array($tid));
		}
		$func->transfer("Xóa học viên thành công (bỏ qua học viên đã thanh toán)", $redirect);
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

	$row = $d->rawQueryOne("select id, nhom from #_xd_hocvien where id = ? limit 0,1", array($id));
	if(empty($row)) $func->transfer("Không tìm thấy học viên", $redirect, false);

	if($status === 'da')
	{
		$config = getXdConfig($d);
		$dinhMuc = (int)$config['dinh_muc'];
		$soTien = (int)xdMucTheoNhom($config, $row['nhom']);
		$ok = $d->rawQuery(
			"update #_xd_hocvien set ngay_thanh_toan = ?, dinh_muc = ?, so_tien_thanh_toan = ?, id_bangke = 0 where id = ?",
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
