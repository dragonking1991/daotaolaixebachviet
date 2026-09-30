<?php
if(!defined('SOURCES')) die("Error");

require_once LIBRARIES.'daotao_lib.php';

function dt_gv_badge($ok)
{
	return '<span class="dt-gv-badge '.((int)$ok ? 'is-pass' : 'is-pending').'">'.((int)$ok ? 'Đạt' : 'Chưa').'</span>';
}

$seo->setSeo('h1', 'Cổng giáo viên');
$seo->setSeo('title', 'Cổng giáo viên');
$seo->setSeo('url', $func->getPageURL());
if(isset($title_crumb) && $title_crumb != '') $breadcr->setBreadCrumbs($com, $title_crumb);
$breadcrumbs = $breadcr->getBreadCrumbs();

$dtGvMsg = '';
$dtGvErr = '';
$dtAct = isset($_POST['dt_act']) ? $_POST['dt_act'] : '';

/* Chuẩn hóa ngày sinh về yyyymmdd để so khớp không phụ thuộc định dạng lưu trữ. */
if(!function_exists('dt_gv_date_key'))
{
	function dt_gv_date_key($v)
	{
		$v = trim((string)$v);
		if($v === '') return '';
		if(preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/', $v, $m)) return sprintf('%04d%02d%02d', $m[3], $m[2], $m[1]);
		if(preg_match('/^(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})$/', $v, $m)) return sprintf('%04d%02d%02d', $m[1], $m[2], $m[3]);
		return preg_replace('/\D+/', '', $v);
	}
}

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
		$func->redirect('cong-giao-vien');
	}
	$dtGvErr = 'CCCD hoặc mật khẩu không đúng.';
}
elseif($dtAct === 'resetpass')
{
	$cccd = dt_normalize_cccd($_POST['cccd'] ?? '');
	$dobIn = dt_gv_date_key($_POST['ngaysinh'] ?? '');
	$phoneIn = preg_replace('/\D+/', '', (string)($_POST['sdt'] ?? ''));
	$new = (string)($_POST['new_pass'] ?? '');
	$variants = dt_cccd_variants($cccd);
	$gv = null;
	if(!empty($variants))
	{
		$in = implode(',', array_fill(0, count($variants), '?'));
		$gv = $d->rawQueryOne("select * from #_dt_giaovien where cccd in ($in) limit 0,1", $variants);
	}
	if(!$gv) $dtGvErr = 'Không tìm thấy giáo viên với CCCD này.';
	elseif(strlen($new) < 4) $dtGvErr = 'Mật khẩu mới tối thiểu 4 ký tự.';
	else
	{
		$dobDb = dt_gv_date_key($gv['ngaysinh']);
		$phoneDb = preg_replace('/\D+/', '', (string)$gv['sdt']);
		$checks = 0; $passed = 0;
		if($dobDb !== '') { $checks++; if($dobIn !== '' && $dobIn === $dobDb) $passed++; }
		if($phoneDb !== '') { $checks++; if($phoneIn !== '' && $phoneIn === $phoneDb) $passed++; }
		if($checks === 0) $dtGvErr = 'Hồ sơ chưa có ngày sinh/số điện thoại để xác minh. Vui lòng liên hệ trung tâm để được cấp lại mật khẩu.';
		elseif($passed < $checks) $dtGvErr = 'Thông tin xác minh (ngày sinh/số điện thoại) không khớp hồ sơ.';
		else { $d->where('id', (int)$gv['id']); $d->update('dt_giaovien', array('matkhau' => dt_gv_hash($new))); $dtGvMsg = 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập bằng mật khẩu mới.'; }
	}
}
elseif($dtAct === 'logout')
{
	unset($_SESSION['dt_gv']);
	$func->redirect('cong-giao-vien');
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
