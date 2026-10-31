<?php
if(!defined('SOURCES')) die("Error");

require_once LIBRARIES.'daotao_lib.php';
require_once LIBRARIES.'daotao_gv_auth.php';

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
$dtGvLoggedIn = isset($_SESSION['dt_gv']);
if($dtAct !== '' && $dtAct !== 'login' && $dtAct !== 'resetpass' && !dt_gv_csrf_valid())
{
	$dtGvErr = 'Phiên thao tác đã hết hạn. Vui lòng tải lại trang rồi thử lại.';
	$dtAct = '';
}
if($dtAct === 'login')
{
	$cccd = dt_normalize_cccd($_POST['cccd'] ?? '');
	$pass = (string)($_POST['password'] ?? '');
	$lockedMinutes = dt_gv_attempt_locked('login', $cccd);
	$variants = dt_cccd_variants($cccd);
	$gv = null;
	if(!$lockedMinutes && !empty($variants))
	{
		$in = implode(',', array_fill(0, count($variants), '?'));
		$gv = $d->rawQueryOne("select * from #_dt_giaovien where cccd in ($in) limit 0,1", $variants);
	}
	if($lockedMinutes) $dtGvErr = 'Đăng nhập sai quá nhiều lần. Vui lòng thử lại sau '.$lockedMinutes.' phút.';
	elseif($gv && dt_gv_verify($pass, $gv['matkhau']))
	{
		dt_gv_attempt_clear('login', $cccd);
		if(dt_gv_needs_rehash($gv['matkhau'])) { $d->where('id', (int)$gv['id']); $d->update('dt_giaovien', array('matkhau' => dt_gv_hash($pass))); }
		session_regenerate_id(true);
		$_SESSION['dt_gv'] = array('id' => (int)$gv['id'], 'cccd' => $gv['cccd'], 'hoten' => $gv['hoten'], 'gv_key' => $gv['gv_key'],
			'must_change' => dt_gv_is_default_password($gv['cccd'], $gv['matkhau']) ? 1 : 0);
		unset($_SESSION['dt_gv_csrf']);
		$func->redirect('cong-giao-vien');
	}
	else
	{
		dt_gv_attempt_fail('login', $cccd);
		$dtGvErr = 'CCCD hoặc mật khẩu không đúng.';
	}
}
elseif($dtAct === 'resetpass')
{
	$cccd = dt_normalize_cccd($_POST['cccd'] ?? '');
	$dobIn = dt_gv_date_key($_POST['ngaysinh'] ?? '');
	$phoneIn = preg_replace('/\D+/', '', (string)($_POST['sdt'] ?? ''));
	$new = (string)($_POST['new_pass'] ?? '');
	$lockedMinutes = dt_gv_attempt_locked('reset', $cccd);
	$variants = dt_cccd_variants($cccd);
	$gv = null;
	if(!$lockedMinutes && !empty($variants))
	{
		$in = implode(',', array_fill(0, count($variants), '?'));
		$gv = $d->rawQueryOne("select * from #_dt_giaovien where cccd in ($in) limit 0,1", $variants);
	}
	$policy = dt_gv_password_policy($new, $cccd);
	$dobDb = $gv ? dt_gv_date_key($gv['ngaysinh']) : '';
	$phoneDb = $gv ? preg_replace('/\D+/', '', (string)$gv['sdt']) : '';
	if($lockedMinutes) $dtGvErr = 'Thử xác minh sai quá nhiều lần. Vui lòng thử lại sau '.$lockedMinutes.' phút hoặc liên hệ trung tâm.';
	elseif($policy !== '') $dtGvErr = $policy;
	/* Cần đủ cả ngày sinh và số điện thoại trong hồ sơ; thông báo chung để không lộ CCCD nào tồn tại. */
	elseif(!$gv || $dobDb === '' || $phoneDb === '' || $dobIn !== $dobDb || $phoneIn !== $phoneDb)
	{
		dt_gv_attempt_fail('reset', $cccd);
		$dtGvErr = 'Thông tin xác minh không khớp hoặc hồ sơ chưa đủ ngày sinh và số điện thoại. Vui lòng liên hệ trung tâm để được cấp lại mật khẩu.';
	}
	else
	{
		dt_gv_attempt_clear('reset', $cccd);
		$d->where('id', (int)$gv['id']); $d->update('dt_giaovien', array('matkhau' => dt_gv_hash($new)));
		$dtGvMsg = 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập bằng mật khẩu mới.';
	}
}
elseif($dtAct === 'logout')
{
	unset($_SESSION['dt_gv'], $_SESSION['dt_gv_csrf']);
	session_regenerate_id(true);
	$func->redirect('cong-giao-vien');
}
elseif($dtAct === 'changepass' && $dtGvLoggedIn)
{
	$old = (string)($_POST['old_pass'] ?? '');
	$new = (string)($_POST['new_pass'] ?? '');
	$gv = $d->rawQueryOne("select * from #_dt_giaovien where id = ? limit 0,1", array((int)$_SESSION['dt_gv']['id']));
	$policy = $gv ? dt_gv_password_policy($new, $gv['cccd']) : '';
	if(!$gv || !dt_gv_verify($old, $gv['matkhau'])) $dtGvErr = 'Mật khẩu hiện tại không đúng.';
	elseif($policy !== '') $dtGvErr = $policy;
	else
	{
		$d->where('id', (int)$gv['id']); $d->update('dt_giaovien', array('matkhau' => dt_gv_hash($new)));
		$_SESSION['dt_gv']['must_change'] = 0;
		$dtGvMsg = 'Đã đổi mật khẩu thành công.';
	}
}
elseif($dtAct === 'savehinh' && $dtGvLoggedIn && !empty($_SESSION['dt_gv']['must_change']))
{
	$dtGvErr = 'Vui lòng đổi mật khẩu mặc định trước khi nhập dữ liệu.';
}
elseif($dtAct === 'savehinh' && isset($_SESSION['dt_gv']))
{
	// GV tự nhập Giờ/Km thực hành trong hình — chỉ khi cấu hình cho phép và học viên thuộc GV.
	$cfg = dt_portal_conggv();
	if(empty($cfg['allow_hinh_input'])) $dtGvErr = 'Chức năng nhập thực hành trong hình đang bị khóa.';
	else
	{
		$cccd = dt_normalize_cccd($_POST['cccd'] ?? '');
		$idKhoa = (int)($_POST['id_khoa'] ?? 0);
		$gioRaw = str_replace(',', '.', trim((string)($_POST['gio'] ?? '')));
		$kmRaw = str_replace(',', '.', trim((string)($_POST['km'] ?? '')));
		if(!is_numeric($gioRaw) || !is_numeric($kmRaw) || !is_finite((float)$gioRaw) || !is_finite((float)$kmRaw) || (float)$gioRaw < 0 || (float)$kmRaw < 0)
		{
			$dtGvErr = 'Giờ và Km phải là số không âm.';
			$gioRaw = $kmRaw = '0';
		}
		$gio = (float)$gioRaw;
		$km = (float)$kmRaw;
		$gvKey = (string)($_SESSION['dt_gv']['gv_key'] ?? '');
		$hv = $d->rawQueryOne("select id, hoten, hang from #_dt_hocvien where cccd = ? and id_khoa = ? and gv_key = ? limit 0,1", array($cccd, $idKhoa, $gvKey));
		if($dtGvErr === '' && !$hv) $dtGvErr = 'Học viên không thuộc danh sách của bạn.';
		if($dtGvErr === '' && $hv)
		{
			$errors = dt_hinh_cap_warn($hv['hang'], $gio, $km);
			if($errors) $dtGvErr = 'Không thể lưu thực hành trong hình: '.implode('; ', $errors).'.';
		}
		if($dtGvErr === '' && $hv)
		{
			$exist = $d->rawQueryOne("select id from #_dt_thuchanh_hinh where id_khoa = ? and cccd = ? limit 0,1", array($idKhoa, $cccd));
			$data = array('id_khoa' => $idKhoa, 'cccd' => $cccd, 'gio' => $gio, 'km' => $km, 'dat_override' => null, 'nguoi_nhap' => 'GV '.(string)($_SESSION['dt_gv']['cccd'] ?? ''));
			if($exist && $exist['id']) { $d->where('id', (int)$exist['id']); $d->update('dt_thuchanh_hinh', $data); }
			else { $data['ngaytao'] = time(); $d->insert('dt_thuchanh_hinh', $data); }
			dt_portal_gv_log($gvKey, (string)($_SESSION['dt_gv']['cccd'] ?? ''), $cccd, $hv['hoten'], 'Cập nhật thực hành trong hình: '.rtrim(rtrim(number_format($gio, 2, '.', ''), '0'), '.').' giờ / '.rtrim(rtrim(number_format($km, 2, '.', ''), '0'), '.').' km');
			$dtGvMsg = 'Đã lưu thực hành trong hình cho '.$hv['hoten'].'.';
		}
	}
}

/* ----- Dữ liệu cho template ----- */
$dtGv = isset($_SESSION['dt_gv']) ? $_SESSION['dt_gv'] : null;
$dtCfg = dt_portal_conggv();
$dtStudents = array();
$dtKhoaList = array();
$dtHistory = array();
$dtFilterKhoa = isset($_REQUEST['khoa']) ? (int)$_REQUEST['khoa'] : 0;
$dtFilterKw = isset($_REQUEST['kw']) ? trim((string)$_REQUEST['kw']) : '';
if($dtGv)
{
	// Nhật ký cập nhật của giáo viên trong 30 ngày gần đây.
	$dtHistory = dt_portal_gv_history($dtGv['gv_key'], 30, 50);
	// Danh sách khóa mà giáo viên phụ trách (cho bộ lọc).
	$dtKhoaList = $d->rawQuery("select distinct k.id, k.ma_khoa, k.ten_khoa from #_dt_hocvien h left join #_dt_khoa k on k.id = h.id_khoa where h.gv_key = ? and k.id is not null order by k.ma_khoa asc", array($dtGv['gv_key']));

	$where = "h.gv_key = ?"; $params = array($dtGv['gv_key']);
	if($dtFilterKhoa) { $where .= " and h.id_khoa = ?"; $params[] = $dtFilterKhoa; }
	if($dtFilterKw !== '')
	{
		$where .= " and (h.hoten like ? or h.cccd like ? or h.ma_hv like ?)";
		$like = '%'.$dtFilterKw.'%'; $params[] = $like; $params[] = $like; $params[] = $like;
	}
	$rows = $d->rawQuery("select h.*, k.ma_khoa, k.ten_khoa from #_dt_hocvien h left join #_dt_khoa k on k.id = h.id_khoa where $where order by k.ma_khoa asc, substring_index(h.hoten, ' ', -1) asc, h.hoten asc", $params);
	foreach($rows as $hv)
	{
		$dtStudents[] = array('hv' => $hv, 'sum' => dt_student_summary($hv));
	}
}
