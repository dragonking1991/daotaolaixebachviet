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
