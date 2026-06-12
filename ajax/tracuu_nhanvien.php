<?php
	include "ajax_config.php";

	function normalizeEmployeeLookupText($value)
	{
		$value = trim((string)$value);
		return preg_replace('/\s+/', ' ', $value);
	}

	function findEmployeeByKeyword($d, $lang, $keyword)
	{
		$keyword = normalizeEmployeeLookupText($keyword);
		if($keyword === '') return array();

		return $d->rawQueryOne("select p.id, p.type, p.ten$lang as ten, p.ngaysinh, p.hang, p.khoa, p.cccd, p.ma_tra_cuu, p.options2 from #_product p where p.type = ? and p.ma_tra_cuu = ? and p.hienthi = 1 limit 0,1", array('nhan-vien', $keyword));
	}

	function formatEmployeeDetailLabel($key)
	{
		$map = array(
			'matracuu' => 'Mã tra cứu',
			'stt' => 'STT',
			'cccd' => 'CCCD',
			'hovaten' => 'Họ và tên',
			'hoten' => 'Họ và tên',
			'ten' => 'Tên',
			'chucvu' => 'Chức vụ',
			'bophan' => 'Bộ phận',
			'phongban' => 'Phòng ban',
			'donvi' => 'Đơn vị',
			'ngaysinh' => 'Ngày sinh',
			'namsinh' => 'Năm sinh',
			'songaylamviec' => 'Số ngày làm việc',
			'luongchinh' => 'Lương chính',
			'thuongletet' => 'Thưởng lễ tết',
			'tiencom' => 'Tiền cơm',
			'phucapxangxe' => 'Phụ cấp xăng xe',
			'dayltsathach' => 'Dạy LT Sát hạch',
			'chieusinhtttn' => 'Chiêu sinh TTTN',
			'khacdtkhac' => 'Khác (DT - Khác)',
			'lamthemgio' => 'Làm thêm giờ',
			'dienthoai' => 'Điện thoại',
			'ienthoai' => 'Điện thoại',
			'tongthunhap' => 'Tổng thu nhập',
			'nldnopbhxh105' => 'NLD Nộp BHXH 10.5%',
			'ttnopbhxh215' => 'TT Nộp BHXH 21.5%',
			'thunhapchiuthue' => 'Thu nhập chịu thuế',
			'giamtrugiacanh' => 'Giảm trừ gia cảnh',
			'sonpt' => 'Số NPT',
			'nguoiphuthuoc' => 'Người phụ thuộc',
			'thunhaptinhthue' => 'Thu nhập tính thuế',
			'bac' => 'Bậc',
			'thuetncn' => 'Thuế TNCN',
			'luongthucnhan' => 'Lương thực nhận',
			'luongthycnhan' => 'Lương thực nhận',
			'nghiavugv' => 'Nghĩa vụ GV'
		);

		if(isset($map[$key])) return $map[$key];

		$key = str_replace(array('_', '-'), ' ', $key);
		return ucwords($key);
	}

	function buildEmployeeDetailDisplayList($detail)
	{
		if(!is_array($detail)) return array();

		$orderedKeys = array(
			'hovaten', 'chucvu', 'songaylamviec', 'luongchinh', 'thuongletet',
			'tiencom', 'phucapxangxe', 'dayltsathach', 'chieusinhtttn', 'khacdtkhac',
			'lamthemgio', 'dienthoai', 'ienthoai', 'tongthunhap', 'nldnopbhxh105',
			'ttnopbhxh215', 'thunhapchiuthue', 'giamtrugiacanh', 'sonpt', 'nguoiphuthuoc',
			'thunhaptinhthue', 'bac', 'thuetncn', 'luongthucnhan', 'luongthycnhan', 'nghiavugv'
		);

		$result = array();
		$seen = array();

		foreach($orderedKeys as $key)
		{
			if(!isset($detail[$key])) continue;
			$value = trim((string)$detail[$key]);
			if($value === '') continue;
			$result[] = array('key' => $key, 'value' => $value);
			$seen[$key] = true;
		}

		foreach($detail as $key => $value)
		{
			if($key === 'stt') continue;
			if(isset($seen[$key])) continue;
			$value = trim((string)$value);
			if($value === '') continue;
			$result[] = array('key' => $key, 'value' => $value);
		}

		return $result;
	}

	function issueEmployeeLookupToken($employeeId, $currentReference)
	{
		$token = md5(uniqid($employeeId.$currentReference, true));
		if(!isset($_SESSION['employee_lookup_tokens']) || !is_array($_SESSION['employee_lookup_tokens'])) $_SESSION['employee_lookup_tokens'] = array();

		$_SESSION['employee_lookup_tokens'][$employeeId] = array(
			'token' => $token,
			'ma_tra_cuu' => $currentReference,
			'issued_at' => time()
		);

		return $token;
	}

	function hasValidEmployeeLookupToken($employeeId, $token, $currentReference)
	{
		if(!isset($_SESSION['employee_lookup_tokens'][$employeeId])) return false;

		$tokenData = $_SESSION['employee_lookup_tokens'][$employeeId];
		if(empty($tokenData['token']) || !isset($tokenData['ma_tra_cuu'])) return false;
		if($tokenData['token'] !== $token) return false;
		if($tokenData['ma_tra_cuu'] !== $currentReference) return false;
		if(($tokenData['issued_at'] + 1800) < time()) return false;

		return true;
	}

	function syncEmployeeDetailReference($options2, $newReference)
	{
		if(!is_array($options2)) $options2 = array();
		if(!isset($options2['detail']) || !is_array($options2['detail'])) $options2['detail'] = array();
		$options2['detail']['matracuu'] = $newReference;

		return $options2;
	}

	function syncEmployeeDetailCccd($options2, $newCccd)
	{
		if(!is_array($options2)) $options2 = array();
		if(!isset($options2['detail']) || !is_array($options2['detail'])) $options2['detail'] = array();

		foreach(array('cccd', 'socccd', 'cancuoc', 'socancuoc') as $key)
		{
			if(isset($options2['detail'][$key])) $options2['detail'][$key] = $newCccd;
		}

		return $options2;
	}

	function renderEmployeeLookupResult($employee, $message = '', $messageType = 'success')
	{
		$options2 = (!empty($employee['options2'])) ? json_decode($employee['options2'], true) : array();
		$detail = (isset($options2['detail']) && is_array($options2['detail'])) ? $options2['detail'] : array();
		$detailList = buildEmployeeDetailDisplayList($detail);
		$lookupToken = issueEmployeeLookupToken($employee['id'], (string)$employee['ma_tra_cuu']);
		$messageColor = ($messageType === 'error') ? '#b42318' : '#027a48';
		$messageBg = ($messageType === 'error') ? '#fef3f2' : '#ecfdf3';
		ob_start();
		?>
		<div style="border:1px solid #d0d5dd; border-radius:16px; padding:18px; background:#ffffff;">
			<?php if($message != '') { ?>
				<div style="margin-bottom:14px; padding:10px 12px; border-radius:10px; background:<?=$messageBg?>; color:<?=$messageColor?>; font-size:14px;">
					<?=$message?>
				</div>
			<?php } ?>

			<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin-bottom:16px;">
				<div><strong>Họ và tên:</strong><br><?=htmlspecialchars($employee['ten'])?></div>
				<div><strong>Mã tra cứu:</strong><br><?=htmlspecialchars($employee['ma_tra_cuu'])?></div>
				<div><strong>CCCD:</strong><br><?=htmlspecialchars($employee['cccd'])?></div>
				<div><strong>Bộ phận:</strong><br><?=htmlspecialchars($employee['hang'])?></div>
				<div><strong>Chức vụ:</strong><br><?=htmlspecialchars($employee['khoa'])?></div>
				<?php if(!empty($employee['ngaysinh'])) { ?>
					<div><strong>Ngày sinh:</strong><br><?=htmlspecialchars($employee['ngaysinh'])?></div>
				<?php } ?>
			</div>

			<div style="margin-bottom:18px;">
				<h3 style="margin:0 0 10px 0; font-size:18px;">Chi tiết nhân viên</h3>
				<div style="display:grid; grid-template-columns:minmax(180px, 240px) 1fr; gap:8px 12px;">
					<?php foreach($detailList as $entry) { ?>
						<div style="font-weight:700; color:#1f2937;"><?=htmlspecialchars(formatEmployeeDetailLabel($entry['key']))?></div>
						<div style="color:#101828;"><?=htmlspecialchars($entry['value'])?></div>
					<?php } ?>
				</div>
			</div>

			<form id="employee-reference-update-form" method="post" style="border-top:1px solid #eaecf0; padding-top:16px;">
				<input type="hidden" name="employee_id" value="<?=$employee['id']?>">
				<input type="hidden" name="current_reference" value="<?=htmlspecialchars($employee['ma_tra_cuu'])?>">
				<input type="hidden" name="lookup_token" value="<?=$lookupToken?>">
				<label for="employee_new_reference" style="display:block; font-weight:600; margin-bottom:8px;">Đổi mã tra cứu</label>
				<div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
					<input type="text" id="employee_new_reference" name="new_reference" placeholder="Nhập mã tra cứu mới" value="<?=htmlspecialchars($employee['ma_tra_cuu'])?>" style="flex:1; min-width:220px; height:42px; padding:0 12px; border:1px solid #d0d5dd; border-radius:10px;">
					<button type="submit" style="height:42px; padding:0 18px; border:0; border-radius:10px; background:#0f766e; color:#fff; font-weight:600;">Cập nhật mã tra cứu</button>
				</div>
				<p style="margin:10px 0 0 0; font-size:13px; color:#475467;">Bảo vệ tối thiểu: chỉ cho phép cập nhật sau khi đã tra cứu thành công trong phiên hiện tại.</p>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	$action = (isset($_POST['action']) && $_POST['action'] != '') ? htmlspecialchars($_POST['action']) : 'lookup';
	$keyword = (isset($_POST['keyword']) && $_POST['keyword'] != '') ? normalizeEmployeeLookupText($_POST['keyword']) : '';

	if($action === 'lookup')
	{
		if($keyword === '')
		{
			echo '<p class="ktt">Vui lòng nhập mã tra cứu để tra cứu.</p>';
			exit;
		}

		$employee = findEmployeeByKeyword($d, $lang, $keyword);
		if(empty($employee))
		{
			echo '<p class="ktt">Không tìm thấy nhân viên từ dữ liệu hệ thống theo mã tra cứu đã nhập!</p>';
			exit;
		}

		echo renderEmployeeLookupResult($employee);
		exit;
	}

	if($action === 'update')
	{
		$employeeId = (isset($_POST['employee_id']) && $_POST['employee_id'] > 0) ? (int)$_POST['employee_id'] : 0;
		$currentReference = (isset($_POST['current_reference']) && $_POST['current_reference'] != '') ? normalizeEmployeeLookupText($_POST['current_reference']) : '';
		$newReference = (isset($_POST['new_reference']) && $_POST['new_reference'] != '') ? normalizeEmployeeLookupText($_POST['new_reference']) : '';
		$lookupToken = (isset($_POST['lookup_token']) && $_POST['lookup_token'] != '') ? htmlspecialchars($_POST['lookup_token']) : '';

		if($employeeId <= 0)
		{
			echo '<p class="ktt">Không nhận được dữ liệu nhân viên cần cập nhật.</p>';
			exit;
		}

		$employee = $d->rawQueryOne("select id, type, ten$lang as ten, ngaysinh, hang, khoa, cccd, ma_tra_cuu, options2 from #_product where id = ? and type = ? and hienthi = 1 limit 0,1", array($employeeId, 'nhan-vien'));
		if(empty($employee))
		{
			echo '<p class="ktt">Không tìm thấy nhân viên cần cập nhật.</p>';
			exit;
		}

		if(!hasValidEmployeeLookupToken($employeeId, $lookupToken, (string)$employee['ma_tra_cuu']))
		{
			echo renderEmployeeLookupResult($employee, 'Phiên xác thực tra cứu đã hết hạn. Vui lòng tra cứu lại trước khi cập nhật.', 'error');
			exit;
		}

		if($currentReference !== (string)$employee['ma_tra_cuu'])
		{
			echo renderEmployeeLookupResult($employee, 'Mã tra cứu hiện tại không còn khớp với dữ liệu hệ thống. Vui lòng tra cứu lại.', 'error');
			exit;
		}

		if($newReference === '')
		{
			echo renderEmployeeLookupResult($employee, 'Vui lòng nhập mã tra cứu mới.', 'error');
			exit;
		}

		if(!preg_match('/^[A-Za-z0-9\-_.]{3,80}$/', $newReference))
		{
			echo renderEmployeeLookupResult($employee, 'Mã tra cứu chỉ gồm chữ/số/ký tự - _ . và dài 3-80 ký tự.', 'error');
			exit;
		}

		if($newReference === $currentReference)
		{
			echo renderEmployeeLookupResult($employee, 'Mã tra cứu mới đang trùng mã tra cứu hiện tại.', 'error');
			exit;
		}

		$duplicateEmployee = $d->rawQueryOne("select id from #_product where id <> ? and type = ? and ma_tra_cuu = ? limit 0,1", array($employeeId, 'nhan-vien', $newReference));
		if(!empty($duplicateEmployee['id']))
		{
			echo renderEmployeeLookupResult($employee, 'Mã tra cứu mới đã tồn tại trong hệ thống cho một nhân viên khác.', 'error');
			exit;
		}

		$options2 = (!empty($employee['options2'])) ? json_decode($employee['options2'], true) : array();
		$options2 = syncEmployeeDetailReference($options2, $newReference);

		$data = array(
			'ma_tra_cuu' => htmlspecialchars($newReference),
			'options2' => json_encode($options2, JSON_UNESCAPED_UNICODE),
			'ngaysua' => time()
		);

		$d->where('id', $employeeId);
		$d->where('type', 'nhan-vien');
		if(!$d->update('product', $data))
		{
			echo renderEmployeeLookupResult($employee, 'Không thể cập nhật mã tra cứu mới. Vui lòng thử lại sau.', 'error');
			exit;
		}

		unset($_SESSION['employee_lookup_tokens'][$employeeId]);
		$employee['ma_tra_cuu'] = $newReference;
		$employee['options2'] = $data['options2'];

		echo renderEmployeeLookupResult($employee, 'Cập nhật mã tra cứu thành công. Từ lần sau có thể tra cứu bằng mã mới.');
		exit;
	}

	echo '<p class="ktt">Yêu cầu không hợp lệ.</p>';
?>