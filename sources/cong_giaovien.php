<?php
if(!defined('SOURCES')) die("Error");

require_once LIBRARIES.'daotao_lib.php';
require_once LIBRARIES.'daotao_gv_auth.php';
require_once LIBRARIES.'payroll_monthly.php';

function dt_gv_badge($ok)
{
	return '<span class="dt-gv-badge '.((int)$ok ? 'is-pass' : 'is-pending').'">'.((int)$ok ? 'Đạt' : 'Chưa').'</span>';
}

function dt_gv_xe_repair_query_failed($result)
{
	global $d;
	return method_exists($d, 'getLastErrorCode') ? $d->getLastErrorCode() !== '00000' : $result === false;
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
if(isset($_GET['repair_image']))
{
	$gvKey = trim((string)($_SESSION['dt_gv']['gv_key'] ?? ''));
	$idXe = (int)($_GET['id_xe'] ?? 0);
	$idRepair = (int)($_GET['id_suachua'] ?? 0);
	$image = is_scalar($_GET['repair_image']) ? (string)$_GET['repair_image'] : '';
	$authorized = false;
	if($dtGvLoggedIn && $gvKey !== '' && $idXe && $idRepair)
	{
		try
		{
			dt_xe_suachua_ensure_table();
			$authorized = dt_xe_suachua_image_authorized($idXe, $idRepair, $image, $gvKey);
		}
		catch(Exception $e) { $authorized = false; }
	}
	if(!$authorized)
	{
		http_response_code(404);
		exit;
	}
	$imagePath = dt_xe_suachua_image_path($image);
	if(!$imagePath)
	{
		http_response_code(404);
		exit;
	}
	header('Content-Type: image/jpeg');
	header('Content-Length: '.filesize($imagePath));
	header('Cache-Control: private, no-store');
	header('X-Content-Type-Options: nosniff');
	readfile($imagePath);
	exit;
}
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
		$func->redirect('cong-giao-vien', 303);
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
	$func->redirect('cong-giao-vien', 303);
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
elseif(in_array($dtAct, array('addXeSuachua', 'updateXeSuachuaStatus'), true) && isset($_SESSION['dt_gv']))
{
	$dtTab = 'xe';
	$gvKey = trim((string)($_SESSION['dt_gv']['gv_key'] ?? ''));
	$dtXeRepairVehicleId = (int)($_POST['id_xe'] ?? 0);
	$dtXeRepairSelectedId = $dtXeRepairVehicleId;
	if(!empty($_SESSION['dt_gv']['must_change']))
		$dtGvErr = 'Vui lòng đổi mật khẩu mặc định trước khi cập nhật nhật ký xe.';
	elseif($gvKey === '' || !$dtXeRepairVehicleId)
		$dtGvErr = 'Không xác định được xe được phân công.';
	else
	{
		try { dt_xe_suachua_ensure_table(); }
		catch(Exception $e) { $dtGvErr = 'Chưa thể mở nhật ký sửa chữa. Vui lòng thử lại sau.'; }
	}

	if($dtGvErr === '' && $dtAct === 'addXeSuachua')
	{
		list($repairData, $repairErrors, $repairValues) = dt_xe_suachua_validate($_POST);
		$repairData['trang_thai'] = 'dang_sua';
		if($repairErrors)
		{
			$dtGvErr = 'Vui lòng kiểm tra các thông tin được đánh dấu.';
			$dtXeRepairFormOpen = true;
			$dtXeRepairFormValues = $repairValues;
			$dtXeRepairFormErrors = $repairErrors;
		}
		else
		{
			$d->startTransaction();
			$vehicle = $d->rawQueryOne('select id, bien_so from #_dt_xe where id = ? and gv_key = ? limit 0,1 for update', array($dtXeRepairVehicleId, $gvKey));
			if($d->transactionFailed() || !$vehicle)
			{
				$d->rollback();
				$dtGvErr = 'Xe không còn được phân công cho tài khoản của bạn.';
			}
			else
			{
				$uploadError = '';
				$repairImages = dt_xe_suachua_upload_images($_FILES['anh_minh_chung'] ?? null, $uploadError);
				if($repairImages === false)
				{
					$d->rollback();
					$dtGvErr = $uploadError;
					$dtXeRepairFormOpen = true;
					$dtXeRepairFormValues = $repairValues;
					$dtXeRepairFormErrors = array();
				}
				else
				{
					$repairData['id_xe'] = $dtXeRepairVehicleId;
					$repairData['anh_minh_chung'] = $repairImages ? json_encode($repairImages, JSON_UNESCAPED_SLASHES) : null;
					$repairData['nguoi_tao'] = 'GV '.(string)($_SESSION['dt_gv']['cccd'] ?? '');
					$repairData['nguoi_capnhat'] = $repairData['nguoi_tao'];
					$written = $d->insert('dt_xe_suachua', $repairData);
					if($written === false || $d->transactionFailed() || $d->commit() === false)
					{
						$d->rollback();
						dt_xe_suachua_remove_images($repairImages);
						$dtGvErr = 'Không thể lưu nhật ký sửa chữa. Vui lòng giữ lại thông tin và thử lại.';
						$dtXeRepairFormOpen = true;
						$dtXeRepairFormValues = $repairValues;
						$dtXeRepairFormErrors = array();
					}
					else
					{
						dt_portal_gv_log($gvKey, (string)($_SESSION['dt_gv']['cccd'] ?? ''), '', $vehicle['bien_so'], 'Thêm nhật ký sửa xe: '.$repairValues['noi_dung']);
						$dtGvMsg = 'Đã thêm nhật ký sửa chữa cho xe '.$vehicle['bien_so'].'.';
					}
				}
			}
		}
	}
	elseif($dtGvErr === '' && $dtAct === 'updateXeSuachuaStatus')
	{
		$status = is_scalar($_POST['trang_thai'] ?? null) ? (string)$_POST['trang_thai'] : '';
		$repairId = (int)($_POST['id_suachua'] ?? 0);
		if(!$repairId || !in_array($status, array('dang_sua', 'hoan_tat'), true))
			$dtGvErr = 'Trạng thái cập nhật không hợp lệ.';
		else
		{
			$d->startTransaction();
			$vehicle = $d->rawQueryOne('select id, bien_so from #_dt_xe where id = ? and gv_key = ? limit 0,1 for update', array($dtXeRepairVehicleId, $gvKey));
			$repair = $vehicle ? $d->rawQueryOne('select id, trang_thai from #_dt_xe_suachua where id = ? and id_xe = ? limit 0,1 for update', array($repairId, $dtXeRepairVehicleId)) : false;
			if($d->transactionFailed())
			{
				$d->rollback();
				$dtGvErr = 'Không thể kiểm tra quyền quản lý xe. Vui lòng thử lại.';
			}
			elseif(!$vehicle || !$repair)
			{
				$d->rollback();
				$dtGvErr = 'Không tìm thấy nhật ký trong danh sách xe bạn đang quản lý.';
			}
			elseif($repair['trang_thai'] === $status)
			{
				$d->rollback();
				$dtGvMsg = 'Nhật ký này đã ở trạng thái đã chọn.';
			}
			else
			{
				$d->rawQuery('update #_dt_xe_suachua set trang_thai = ?, nguoi_capnhat = ?, ngaycapnhat = CURRENT_TIMESTAMP where id = ? and id_xe = ?', array(
					$status, 'GV '.(string)($_SESSION['dt_gv']['cccd'] ?? ''), $repairId, $dtXeRepairVehicleId
				));
				if($d->transactionFailed() || $d->commit() === false)
				{
					$d->rollback();
					$dtGvErr = 'Không thể cập nhật trạng thái. Vui lòng thử lại.';
				}
				else
				{
					$label = $status === 'hoan_tat' ? 'Hoàn tất' : 'Đang sửa';
					dt_portal_gv_log($gvKey, (string)($_SESSION['dt_gv']['cccd'] ?? ''), '', $vehicle['bien_so'], 'Cập nhật trạng thái nhật ký sửa xe #'.$repairId.': '.$label);
					$dtGvMsg = 'Đã cập nhật trạng thái nhật ký của xe '.$vehicle['bien_so'].'.';
				}
			}
		}
	}
}
elseif($dtAct === 'deleteXeSuachua' && isset($_SESSION['dt_gv']))
{
	$dtTab = 'xe';
	$dtGvErr = 'Giáo viên không được phép xóa nhật ký sửa chữa xe.';
}

/* ----- Dữ liệu cho template ----- */
$dtGv = isset($_SESSION['dt_gv']) ? $_SESSION['dt_gv'] : null;
$dtCfg = dt_portal_conggv();
$dtStudents = array();
$dtExamResults = array();
$dtKhoaList = array();
$dtHistory = array();
$dtPayroll = null;
$dtRequestedTab = $_REQUEST['tab'] ?? '';
$dtTab = in_array($dtRequestedTab, array('luong', 'xe'), true) ? $dtRequestedTab : 'daotao';
$dtFilterKhoa = isset($_REQUEST['khoa']) ? (int)$_REQUEST['khoa'] : 0;
$dtFilterKw = isset($_REQUEST['kw']) ? trim((string)$_REQUEST['kw']) : '';
$dtFilterHinhThieu = isset($_REQUEST['hinh_thieu']) && (string)$_REQUEST['hinh_thieu'] === '1' ? 1 : 0;
$dtXeRepairVehicles = array();
$dtXeRepairSelectedVehicle = null;
$dtXeRepairRows = array();
$dtXeRepairSummary = array('tong' => 0, 'dang_sua' => 0, 'chi_phi' => 0, 'chua_chi_phi' => 0);
$dtXeRepairFormValues = isset($dtXeRepairFormValues) && is_array($dtXeRepairFormValues) ? $dtXeRepairFormValues : array();
$dtXeRepairFormErrors = isset($dtXeRepairFormErrors) && is_array($dtXeRepairFormErrors) ? $dtXeRepairFormErrors : array();
$dtXeRepairFormOpen = !empty($dtXeRepairFormOpen) || isset($_REQUEST['new_log']);
$dtXeRepairSelectedId = isset($dtXeRepairSelectedId) ? (int)$dtXeRepairSelectedId : (int)($_REQUEST['id_xe'] ?? 0);
$dtXeRepairKeyword = is_scalar($_REQUEST['xe_kw'] ?? '') ? trim((string)($_REQUEST['xe_kw'] ?? '')) : '';
if($dtGv && $dtTab === 'luong')
{
	if(empty($dtGv['must_change'])) $dtPayroll = payrollMonthlyFindByCccd($d, $dtGv['cccd'], isset($_GET['thang']) ? trim((string)$_GET['thang']) : '', array('GVTH', 'CTV'));
}
elseif($dtGv && $dtTab === 'xe')
{
	$gvKey = trim((string)($dtGv['gv_key'] ?? ''));
	if($gvKey !== '')
	{
		try { dt_xe_suachua_ensure_table(); }
		catch(Exception $e) { $dtGvErr = 'Chưa thể tải nhật ký sửa xe. Vui lòng thử lại sau.'; }
		if($dtGvErr === '')
		{
			$vehicleSql = "select x.id, x.bien_so, x.nhan_hieu, x.hang_xe, x.loai_xe,
				(select count(*) from #_dt_xe_suachua r where r.id_xe = x.id) as so_lan_sua,
				(select r.trang_thai from #_dt_xe_suachua r where r.id_xe = x.id order by r.ngay_sua desc, r.id desc limit 0,1) as trang_thai_cuoi,
				(select r.ngay_sua from #_dt_xe_suachua r where r.id_xe = x.id order by r.ngay_sua desc, r.id desc limit 0,1) as ngay_sua_cuoi
				from #_dt_xe x where x.gv_key = ?";
			$vehicleParams = array($gvKey);
			if($dtXeRepairKeyword !== '')
			{
				$vehicleSql .= ' and (x.bien_so like ? or x.nhan_hieu like ?)';
				$like = '%'.$dtXeRepairKeyword.'%';
				$vehicleParams[] = $like;
				$vehicleParams[] = $like;
			}
			$vehicleSql .= ' order by x.bien_so asc';
			$dtXeRepairVehicles = $d->rawQuery($vehicleSql, $vehicleParams);
			if(dt_gv_xe_repair_query_failed($dtXeRepairVehicles))
			{
				$dtGvErr = 'Không thể tải danh sách xe được phân công.';
				$dtXeRepairVehicles = array();
			}

			if($dtGvErr === '' && $dtXeRepairSelectedId)
			{
				$dtXeRepairSelectedVehicle = $d->rawQueryOne('select id, bien_so, nhan_hieu, hang_xe, loai_xe from #_dt_xe where id = ? and gv_key = ? limit 0,1', array($dtXeRepairSelectedId, $gvKey));
				if(dt_gv_xe_repair_query_failed($dtXeRepairSelectedVehicle))
					$dtGvErr = 'Không thể tải thông tin xe được chọn.';
				elseif(!$dtXeRepairSelectedVehicle)
				{
					$dtGvErr = 'Xe không còn được phân công cho tài khoản của bạn.';
					$dtXeRepairSelectedId = 0;
				}
				else
				{
					$summary = $d->rawQueryOne("select count(*) as tong, sum(case when trang_thai = 'dang_sua' then 1 else 0 end) as dang_sua,
						coalesce(sum(chi_phi), 0) as chi_phi, sum(case when chi_phi is null then 1 else 0 end) as chua_chi_phi
						from #_dt_xe_suachua where id_xe = ?", array($dtXeRepairSelectedId));
					if(dt_gv_xe_repair_query_failed($summary) || !$summary)
						$dtGvErr = 'Không thể tải thống kê nhật ký sửa chữa.';
					else
						$dtXeRepairSummary = $summary;
					$dtXeRepairRows = $d->rawQuery('select * from #_dt_xe_suachua where id_xe = ? order by ngay_sua desc, id desc limit 0,30', array($dtXeRepairSelectedId));
					if(dt_gv_xe_repair_query_failed($dtXeRepairRows))
					{
						$dtGvErr = 'Không thể tải lịch sử sửa chữa.';
						$dtXeRepairRows = array();
					}
				}
			}
		}
	}
}
elseif($dtGv)
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
		$summary = dt_student_summary($hv);
		if($dtFilterHinhThieu && !dt_hinh_thieu_du_lieu($summary['hinh']['gio'], $summary['hinh']['km'])) continue;
		$dtStudents[] = array('hv' => $hv, 'sum' => $summary);
	}
	if(!empty($dtCfg['show_sathach'])) $dtExamResults = dt_portal_sathach_latest_map($dtStudents);
}
