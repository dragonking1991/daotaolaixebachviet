<?php
if(!defined('SOURCES')) die("Error");

function dt_khoa_list()
{
	global $d, $func, $curPage, $items, $paging;

	$where = "";
	if(isset($_REQUEST['keyword']) && $_REQUEST['keyword'] !== '')
	{
		$kw = $d->escape(htmlspecialchars($_REQUEST['keyword']));
		$where .= " and (k.ma_khoa like '%$kw%' or k.ten_khoa like '%$kw%' or k.hang like '%$kw%')";
	}

	$per_page = 20;
	$startpoint = ($curPage * $per_page) - $per_page;
	$sql = "select k.*, "
		. "(select count(*) from #_dt_hocvien h where h.id_khoa = k.id) as so_hoc_vien "
		. "from #_dt_khoa k where k.hienthi >= 0 $where order by k.ngay_khaigiang desc, k.id desc limit $startpoint,$per_page";
	$items = $d->rawQuery($sql);
	$count = $d->rawQueryOne("select count(*) as num from #_dt_khoa k where k.hienthi >= 0 $where");
	$paging = $func->pagination($count['num'], $per_page, $curPage, "index.php?com=daotao&act=khoa");
}

/* Danh sách tất cả khóa (dùng cho dropdown chọn khóa ở các module). */
function dt_khoa_options()
{
	global $d;
	return $d->rawQuery("select id, ma_khoa, ten_khoa, hang from #_dt_khoa where hienthi = 1 order by ngay_khaigiang desc, id desc");
}

function dt_khoa_form()
{
	global $d, $func, $item;
	$item = array();
	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	if($id)
	{
		$item = $d->rawQueryOne("select * from #_dt_khoa where id = ? limit 0,1", array($id));
		if(!$item || !$item['id']) $func->transfer("Dữ liệu không có thực", "index.php?com=daotao&act=khoa", false);
	}
}

function dt_khoa_save()
{
	global $d, $func, $login_admin;

	if(empty($_POST['data'])) $func->transfer("Không nhận được dữ liệu", "index.php?com=daotao&act=khoa", false);
	$p = $_POST['data'];

	$data = array(
		'ma_khoa' => trim(htmlspecialchars($p['ma_khoa'] ?? '')),
		'ten_khoa' => trim(htmlspecialchars($p['ten_khoa'] ?? '')),
		'hang' => dt_norm_hang($p['hang'] ?? ''),
		'ngay_khaigiang' => trim($p['ngay_khaigiang'] ?? ''),
		'ngay_manhoa' => trim($p['ngay_manhoa'] ?? ''),
		'he_daotao' => trim(htmlspecialchars($p['he_daotao'] ?? '')),
	);
	if($data['ngay_khaigiang'] === '') $data['ngay_khaigiang'] = null;
	if($data['ngay_manhoa'] === '') $data['ngay_manhoa'] = null;

	if($data['ma_khoa'] === '') $func->transfer("Vui lòng nhập mã khóa học", "index.php?com=daotao&act=khoa", false);

	if($data['ngay_khaigiang'] && $data['ngay_manhoa'] && strtotime($data['ngay_manhoa']) < strtotime($data['ngay_khaigiang']))
		$func->transfer("Ngày mãn khóa phải lớn hơn hoặc bằng ngày khai giảng", "index.php?com=daotao&act=khoa", false);

	$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

	$dup = $d->rawQueryOne("select id from #_dt_khoa where ma_khoa = ? and id <> ? limit 0,1", array($data['ma_khoa'], $id));
	if($dup && $dup['id']) $func->transfer("Mã khóa học đã tồn tại", "index.php?com=daotao&act=khoa", false);

	if($id)
	{
		$d->where('id', $id);
		if($d->update('dt_khoa', $data) !== false) $func->transfer("Cập nhật khóa thành công", "index.php?com=daotao&act=khoa");
		$func->transfer("Cập nhật khóa bị lỗi", "index.php?com=daotao&act=khoa", false);
	}
	else
	{
		$data['ngaytao'] = time();
		$data['user_tao'] = isset($_SESSION[$login_admin]['username']) ? $_SESSION[$login_admin]['username'] : '';
		$data['hienthi'] = 1;
		if($d->insert('dt_khoa', $data)) $func->transfer("Lưu khóa thành công", "index.php?com=daotao&act=khoa");
		$func->transfer("Lưu khóa bị lỗi", "index.php?com=daotao&act=khoa", false);
	}
}

function dt_khoa_delete()
{
	global $d, $func;
	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	if(!$id) $func->transfer("Không nhận được dữ liệu", "index.php?com=daotao&act=khoa", false);

	$d->rawQuery("delete from #_dt_khoa where id = ?", array($id));
	$d->rawQuery("delete from #_dt_hocvien where id_khoa = ?", array($id));
	$d->rawQuery("delete from #_dt_lythuyet where id_khoa = ?", array($id));
	$d->rawQuery("delete from #_dt_cabin_kq where id_khoa = ?", array($id));
	$d->rawQuery("delete from #_dt_dat_phien where id_khoa = ?", array($id));
	$d->rawQuery("delete from #_dt_thuchanh_hinh where id_khoa = ?", array($id));
	$func->transfer("Xóa khóa và dữ liệu liên quan thành công", "index.php?com=daotao&act=khoa");
}
