<?php
if(!defined('SOURCES')) die("Error");

/* ============================ Danh sách & xóa hóa đơn ============================ */

function xd_get_hoadon()
{
	global $d, $func, $curPage, $items, $paging, $xd_filter_keyword, $xd_filter_from, $xd_filter_to, $xd_filter_ky, $xd_filter_kt_from, $xd_filter_kt_to, $xd_filter_gv, $xd_hoadon_teachers, $xd_hoadon_kys;

	$where = "";
	$params = array();
	$xd_filter_keyword = '';
	$xd_filter_from = '';
	$xd_filter_to = '';
	$xd_filter_ky = '';
	$xd_filter_kt_from = '';
	$xd_filter_kt_to = '';
	$xd_filter_gv = isset($_REQUEST['gv']) ? trim((string)$_REQUEST['gv']) : '';

	if(isset($_REQUEST['keyword']) && trim($_REQUEST['keyword']) !== '')
	{
		$xd_filter_keyword = trim($_REQUEST['keyword']);
	}
	if(isset($_REQUEST['from_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['from_date']))
	{
		$xd_filter_from = $_REQUEST['from_date'];
	}
	if(isset($_REQUEST['to_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['to_date']))
	{
		$xd_filter_to = $_REQUEST['to_date'];
	}
	if(isset($_REQUEST['ky']) && trim($_REQUEST['ky']) !== '')
	{
		$xd_filter_ky = trim($_REQUEST['ky']);
	}
	if(isset($_REQUEST['kt_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['kt_from']))
	{
		$xd_filter_kt_from = $_REQUEST['kt_from'];
	}
	if(isset($_REQUEST['kt_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST['kt_to']))
	{
		$xd_filter_kt_to = $_REQUEST['kt_to'];
	}

	list($where, $params) = xd_hoadon_where_and_params('all', $xd_filter_keyword, $xd_filter_from, $xd_filter_to, $xd_filter_ky, $xd_filter_kt_from, $xd_filter_kt_to, $xd_filter_gv);
	$per_page = 20;
	$startpoint = ($curPage * $per_page) - $per_page;
	$sql = "select * from #_xd_hoadon where $where order by ngay_hoa_don desc, id desc limit ".$startpoint.",".$per_page;
	$items = $d->rawQuery($sql, $params);

	$count = $d->rawQueryOne("select count(*) as num from #_xd_hoadon where $where", $params);
	$total = isset($count['num']) ? (int)$count['num'] : 0;
	$xd_hoadon_teachers = $d->rawQuery("select gv_key, max(gv_hoten) as gv_hoten, count(*) as so from #_xd_hoadon where gv_key <> '' group by gv_key order by substring_index(max(gv_hoten), ' ', -1) asc, max(gv_hoten) asc", array());
	$xd_hoadon_kys = $d->rawQuery("select ky, count(*) as so from #_xd_hoadon where ky <> '' group by ky order by ky asc", array());

	$url = "index.php?com=xangdau&act=hoadon";
	if($xd_filter_keyword !== '') $url .= '&keyword='.urlencode($xd_filter_keyword);
	if($xd_filter_gv !== '') $url .= '&gv='.urlencode($xd_filter_gv);
	if($xd_filter_from !== '') $url .= '&from_date='.urlencode($xd_filter_from);
	if($xd_filter_to !== '') $url .= '&to_date='.urlencode($xd_filter_to);
	if($xd_filter_ky !== '') $url .= '&ky='.urlencode($xd_filter_ky);
	if($xd_filter_kt_from !== '') $url .= '&kt_from='.urlencode($xd_filter_kt_from);
	if($xd_filter_kt_to !== '') $url .= '&kt_to='.urlencode($xd_filter_kt_to);
	$paging = $func->pagination($total, $per_page, $curPage, $url);
}

function xd_hoadon_where_and_params($scope = 'all', $keyword = '', $fromDate = '', $toDate = '', $ky = '', $ktFrom = '', $ktTo = '', $gvKey = '')
{
	$where = 'id > 0';
	$params = array();

	if($scope === 'paid') { $where .= ' and da_quyettoan = 1'; }
	elseif($scope === 'unpaid') { $where .= ' and da_quyettoan = 0'; }
	if($keyword !== '') { $where .= ' and (ma_hoa_don like ? or gv_hoten like ?)'; $params[] = '%'.$keyword.'%'; $params[] = '%'.$keyword.'%'; }
	if($gvKey !== '') { $where .= ' and gv_key = ?'; $params[] = $gvKey; }
	if($fromDate !== '') { $where .= ' and ngay_hoa_don >= ?'; $params[] = $fromDate; }
	if($toDate !== '') { $where .= ' and ngay_hoa_don <= ?'; $params[] = $toDate; }
	if($ky !== '') { $where .= ' and ky = ?'; $params[] = $ky; }
	if($ktFrom !== '') { $where .= ' and ngay_kiem_tra >= ?'; $params[] = $ktFrom; }
	if($ktTo !== '') { $where .= ' and ngay_kiem_tra <= ?'; $params[] = $ktTo; }

	return array($where, $params);
}

function xd_toggle_hop_le_hoadon()
{
	global $d, $func, $curPage;
	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	$redirect = "index.php?com=xangdau&act=hoadon&p=".(int)$curPage;
	if($id <= 0) $func->transfer("Không xác định được hóa đơn.", $redirect, false);

	$row = $d->rawQueryOne("select da_quyettoan, hop_le from #_xd_hoadon where id = ? limit 0,1", array($id));
	if(empty($row)) $func->transfer("Không tìm thấy hóa đơn cần cập nhật.", $redirect, false);
	if((int)$row['da_quyettoan'] === 1) $func->transfer("Hóa đơn đã quyết toán, không được thay đổi trạng thái hợp lệ.", $redirect, false);

	$newState = ((int)$row['hop_le'] === 1) ? 0 : 1;
	$ok = $d->rawQuery("update #_xd_hoadon set hop_le = ? where id = ? and da_quyettoan = 0", array($newState, $id));
	if($ok === false) $func->transfer("Không thể cập nhật trạng thái hợp lệ của hóa đơn.", $redirect, false);
	$func->transfer($newState === 1 ? "Hóa đơn đã được đánh dấu hợp lệ." : "Hóa đơn đã được đánh dấu không hợp lệ.", $redirect, true);
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

/* Xóa các hóa đơn đã chọn; bỏ qua hóa đơn đã quyết toán. */
function xd_delete_hoadon_selected()
{
	global $d, $func;
	$ret = xd_safe_ret($_POST['ret'] ?? '', 'index.php?com=xangdau&act=hoadon');
	$ids = array_values(array_filter(array_map('intval', (array)($_POST['ids'] ?? array()))));
	if(!$ids) $func->transfer("Chưa chọn hóa đơn nào để xóa.", $ret, false);

	$in = implode(',', array_fill(0, count($ids), '?'));
	$row = $d->rawQueryOne("select sum(case when da_quyettoan = 0 then 1 else 0 end) as can_delete, count(*) as total from #_xd_hoadon where id in ($in)", $ids);
	$canDelete = (int)($row['can_delete'] ?? 0);
	$kept = (int)($row['total'] ?? 0) - $canDelete;
	if($canDelete === 0) $func->transfer("Các hóa đơn đã chọn đều đã quyết toán, không thể xóa.", $ret, false);

	$ok = $d->rawQuery("delete from #_xd_hoadon where id in ($in) and da_quyettoan = 0", $ids);
	if($ok === false) $func->transfer("Không thể xóa hóa đơn đã chọn.", $ret, false);
	$msg = "Đã xóa $canDelete hóa đơn.";
	if($kept > 0) $msg .= " Giữ lại $kept hóa đơn đã quyết toán.";
	$func->transfer($msg, $ret);
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
