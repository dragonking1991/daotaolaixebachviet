<?php
if(!defined('SOURCES')) die("Error");

require_once LIBRARIES.'daotao_lib.php';

$seo->setSeo('h1', 'Cổng giáo viên');
$seo->setSeo('title', 'Cổng giáo viên');
$seo->setSeo('url', $func->getPageURL());
if(isset($title_crumb) && $title_crumb != '') $breadcr->setBreadCrumbs($com, $title_crumb);
$breadcrumbs = $breadcr->getBreadCrumbs();

$dtGvMsg = '';
$dtGvErr = '';
$dtAct = isset($_POST['dt_act']) ? $_POST['dt_act'] : '';

/* ----- Xử lý hành động ----- */
if($dtAct === 'login')
{
	$cccd = dt_normalize_cccd($_POST['cccd'] ?? '');
	$pass = (string)($_POST['password'] ?? '');
	$variants = dt_cccd_variants($cccd);
	$gv = null;
	if(!empty($variants))
	{
		$in = implode(',', array_fill(0, count($variants), '?'));
		$gv = $d->rawQueryOne("select * from #_dt_giaovien where cccd in ($in) limit 0,1", $variants);
	}
	if($gv && $gv['matkhau'] !== '' && $gv['matkhau'] === dt_gv_hash($pass))
	{
		$_SESSION['dt_gv'] = array('id' => (int)$gv['id'], 'cccd' => $gv['cccd'], 'hoten' => $gv['hoten'], 'gv_key' => $gv['gv_key']);
		$func->redirect('index.php?com=cong-giao-vien');
	}
	$dtGvErr = 'CCCD hoặc mật khẩu không đúng.';
}
elseif($dtAct === 'logout')
{
	unset($_SESSION['dt_gv']);
	$func->redirect('index.php?com=cong-giao-vien');
}
elseif($dtAct === 'changepass' && isset($_SESSION['dt_gv']))
{
	$old = (string)($_POST['old_pass'] ?? '');
	$new = (string)($_POST['new_pass'] ?? '');
	$gv = $d->rawQueryOne("select * from #_dt_giaovien where id = ? limit 0,1", array((int)$_SESSION['dt_gv']['id']));
	if(!$gv || $gv['matkhau'] !== dt_gv_hash($old)) $dtGvErr = 'Mật khẩu hiện tại không đúng.';
	elseif(strlen($new) < 4) $dtGvErr = 'Mật khẩu mới tối thiểu 4 ký tự.';
	else { $d->where('id', (int)$gv['id']); $d->update('dt_giaovien', array('matkhau' => dt_gv_hash($new))); $dtGvMsg = 'Đã đổi mật khẩu thành công.'; }
}
elseif($dtAct === 'savehinh' && isset($_SESSION['dt_gv']))
{
	$idKhoa = (int)($_POST['id_khoa'] ?? 0);
	$cccd = dt_normalize_cccd($_POST['cccd'] ?? '');
	$hv = dt_find_hocvien_by_cccd($cccd, $idKhoa);
	if($hv && $hv['gv_key'] === $_SESSION['dt_gv']['gv_key'])
	{
		$data = array('id_khoa' => $idKhoa, 'cccd' => $hv['cccd'],
			'gio' => (float)str_replace(',', '.', $_POST['gio'] ?? 0),
			'km' => (float)str_replace(',', '.', $_POST['km'] ?? 0),
			'nguoi_nhap' => 'GV:'.$_SESSION['dt_gv']['cccd']);
		$exist = $d->rawQueryOne("select id from #_dt_thuchanh_hinh where id_khoa = ? and cccd = ? limit 0,1", array($idKhoa, $hv['cccd']));
		if($exist && $exist['id']) { $d->where('id', $exist['id']); $d->update('dt_thuchanh_hinh', $data); }
		else { $data['ngaytao'] = time(); $d->insert('dt_thuchanh_hinh', $data); }
		$dtGvMsg = 'Đã lưu thực hành trong hình cho '.$hv['hoten'].'.';
	}
	else $dtGvErr = 'Học viên không thuộc quyền quản lý của bạn.';
}

/* ----- Dữ liệu cho template ----- */
$dtGv = isset($_SESSION['dt_gv']) ? $_SESSION['dt_gv'] : null;
$dtStudents = array();
if($dtGv)
{
	$rows = $d->rawQuery("select h.*, k.ma_khoa, k.ten_khoa from #_dt_hocvien h left join #_dt_khoa k on k.id = h.id_khoa where h.gv_key = ? order by h.hoten asc", array($dtGv['gv_key']));
	foreach($rows as $hv)
	{
		$dtStudents[] = array('hv' => $hv, 'sum' => dt_student_summary($hv));
	}
}
