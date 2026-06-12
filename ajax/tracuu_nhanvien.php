<?php
	include "ajax_config.php";

	require_once __DIR__.'/../libraries/payroll_config.php';

	function normalizeEmployeeLookupText($value)
	{
		$value = trim((string)$value);
		return preg_replace('/\s+/', ' ', $value);
	}

	function findEmployeeByKeyword($d, $lang, $keyword)
	{
		$keyword = normalizeEmployeeLookupText($keyword);
		if($keyword === '') return array();

		return $d->rawQueryOne(
			"select p.id, p.type, p.ten$lang as ten, p.ngaysinh, p.hang, p.khoa, p.cccd, p.ma_tra_cuu, p.options2,
			        p.payroll_department, p.payroll_td, p.payroll_ss, p.payroll_c1, p.payroll_ce,
			        p.payroll_luong_thuc_nhan, p.payroll_nghia_vu_gv, p.payroll_thuong_le_tet,
			        p.payroll_chieu_sinh_tttn, p.payroll_phu_cap_xang_xe,
			        p.payroll_nld_nop_bhxh_10_5, p.payroll_thue_tncn, p.payroll_nguoi_phu_thuoc,
			        p.payroll_luong_chinh, p.payroll_so_ngay_lam_viec,
			        p.payroll_lam_them_gio, p.payroll_day_lt_sat_hach,
			        p.payroll_tong_thu_nhap, p.payroll_tt_nop_bhxh_21_5,
			        p.payroll_thu_nhap_chiu_thue, p.payroll_giam_tru_gia_canh,
			        p.payroll_so_npt, p.payroll_thu_nhap_tinh_thue, p.payroll_bac,
			        p.payroll_tien_com, p.payroll_dien_thoai, p.payroll_khac_dt_khac
			 from #_product p where p.type = ? and p.ma_tra_cuu = ? and p.hienthi = 1 limit 0,1",
			array('nhan-vien', $keyword)
		);
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

	function parsePayrollNumericValue($raw)
	{
		$raw = trim((string)$raw);
		if($raw === '') return 0;

		$normalized = preg_replace('/[^0-9,.-]/u', '', $raw);
		if($normalized === '' || $normalized === '-' || $normalized === ',') return 0;

		if(preg_match('/^-?\d{1,3}(?:[.,]\d{3})+$/', $normalized)) return (float)str_replace(array(',', '.'), '', $normalized);
		if(substr_count($normalized, ',') > 1) return (float)str_replace(',', '', $normalized);
		if(substr_count($normalized, '.') > 1) return (float)str_replace('.', '', $normalized);

		if(strpos($normalized, ',') !== false && strpos($normalized, '.') === false) $normalized = str_replace(',', '.', $normalized);
		elseif(strpos($normalized, ',') !== false && strpos($normalized, '.') !== false)
		{
			$lastComma = strrpos($normalized, ',');
			$lastDot = strrpos($normalized, '.');
			if($lastComma > $lastDot)
			{
				$normalized = str_replace('.', '', $normalized);
				$normalized = str_replace(',', '.', $normalized);
			}
			else $normalized = str_replace(',', '', $normalized);
		}

		return (float)$normalized;
	}

	function fmtMoney($raw)
	{
		$raw = trim((string)$raw);
		if($raw === '' || $raw === '0') return '-';
		$num = parsePayrollNumericValue($raw);
		if($num == 0) return '-';
		return number_format($num, 0, ',', '.');
	}

	function fmtMoneyInt($val)
	{
		$val = (int)$val;
		if($val == 0) return '-';
		return number_format($val, 0, ',', '.');
	}

	function renderMessageBox($message, $messageType)
	{
		if($message === '') return '';
		$color = ($messageType === 'error') ? '#b42318' : '#027a48';
		$bg = ($messageType === 'error') ? '#fef3f2' : '#ecfdf3';
		return '<div style="margin-bottom:14px; padding:10px 12px; border-radius:10px; background:'.$bg.'; color:'.$color.'; font-size:14px;">'.htmlspecialchars($message).'</div>';
	}

	function renderReferenceUpdateForm($employee, $lookupToken)
	{
		ob_start();
		?>
		<form id="employee-reference-update-form" method="post" style="border-top:1px solid #eaecf0; padding-top:16px;">
			<input type="hidden" name="action" value="update_reference">
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
		<?php
		return ob_get_clean();
	}

	function renderBasicEmployeeDetail($employee, $message = '', $messageType = 'success')
	{
		$options2 = (!empty($employee['options2'])) ? json_decode($employee['options2'], true) : array();
		$detail = (isset($options2['detail']) && is_array($options2['detail'])) ? $options2['detail'] : array();
		$detailList = buildEmployeeDetailDisplayList($detail);
		$lookupToken = issueEmployeeLookupToken($employee['id'], (string)$employee['ma_tra_cuu']);
		$nguoiPhuThuoc = trim((string)($employee['payroll_so_npt'] ?? ''));
		if($nguoiPhuThuoc === '') $nguoiPhuThuoc = trim((string)($employee['payroll_nguoi_phu_thuoc'] ?? ''));
		if($nguoiPhuThuoc === '') $nguoiPhuThuoc = '-';
		$boPhanHienThi = trim((string)($employee['hang'] ?? ''));
		if($boPhanHienThi === '')
		{
			$payrollDepartment = trim((string)($employee['payroll_department'] ?? ''));
			if($payrollDepartment === 'van_phong') $boPhanHienThi = 'Bộ phận văn phòng';
			elseif($payrollDepartment === 'giao_vien') $boPhanHienThi = 'Bộ phận giáo viên';
		}
		if($boPhanHienThi === '') $boPhanHienThi = '-';

		ob_start();
		?>
		<div style="border:1px solid #d0d5dd; border-radius:16px; padding:18px; background:#ffffff;">
			<?=renderMessageBox($message, $messageType)?>
			<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin-bottom:16px;">
				<div><strong>Họ và tên:</strong><br><?=htmlspecialchars($employee['ten'])?></div>
				<div><strong>Mã tra cứu:</strong><br><?=htmlspecialchars($employee['ma_tra_cuu'])?></div>
				<div><strong>CCCD:</strong><br><?=htmlspecialchars($employee['cccd'])?></div>
				<div><strong>Bộ phận:</strong><br><?=htmlspecialchars($boPhanHienThi)?></div>
				<div><strong>Chức vụ:</strong><br><?=htmlspecialchars($employee['khoa'])?></div>
				<?php if(!empty($employee['ngaysinh'])) { ?><div><strong>Ngày sinh:</strong><br><?=htmlspecialchars($employee['ngaysinh'])?></div><?php } ?>
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
			<?=renderReferenceUpdateForm($employee, $lookupToken)?>
		</div>
		<?php
		return ob_get_clean();
	}

	function renderGiaoVienLookupResult($employee, $message = '', $messageType = 'success')
	{
		global $d;

		$rates = getPayrollRateConfig($d);
		$luongThucNhan = parsePayrollNumericValue($employee['payroll_luong_thuc_nhan'] ?? '');
		$nghiaVuGv = parsePayrollNumericValue($employee['payroll_nghia_vu_gv'] ?? '');
		$nhan = $luongThucNhan - $nghiaVuGv;

		$tdCount = (int)($employee['payroll_td'] ?? 0);
		$ssCount = (int)($employee['payroll_ss'] ?? 0);
		$c1Count = (int)($employee['payroll_c1'] ?? 0);
		$ceCount = (int)($employee['payroll_ce'] ?? 0);
		$khacDtKhac = parsePayrollNumericValue($employee['payroll_khac_dt_khac'] ?? '');

		$luongCe = $ceCount * $rates['ce'];
		$lTheoDsPhanXe = $khacDtKhac - $luongCe;
		$nguoiPhuThuoc = trim((string)($employee['payroll_so_npt'] ?? ''));
		if($nguoiPhuThuoc === '') $nguoiPhuThuoc = trim((string)($employee['payroll_nguoi_phu_thuoc'] ?? ''));
		if($nguoiPhuThuoc === '') $nguoiPhuThuoc = '-';
		$lookupToken = issueEmployeeLookupToken($employee['id'], (string)$employee['ma_tra_cuu']);

		$rowStyle = 'display:grid; grid-template-columns:minmax(200px,260px) 1fr; gap:6px 12px; padding:5px 0; border-bottom:1px solid #f3f4f6;';
		$labelStyle = 'font-weight:600; color:#374151;';
		$valStyle = 'color:#111827; text-align:right;';

		ob_start();
		?>
		<div style="border:1px solid #d0d5dd; border-radius:16px; padding:18px; background:#ffffff;">
			<?=renderMessageBox($message, $messageType)?>
			<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px,1fr)); gap:10px; margin-bottom:16px;">
				<div><strong>Họ và tên:</strong><br><?=htmlspecialchars($employee['ten'])?></div>
				<div><strong>Mã tra cứu:</strong><br><?=htmlspecialchars($employee['ma_tra_cuu'])?></div>
				<div><strong>Chức vụ:</strong><br><?=htmlspecialchars($employee['khoa'])?></div>
				<?php if(!empty($employee['ngaysinh'])) { ?><div><strong>Ngày sinh:</strong><br><?=htmlspecialchars($employee['ngaysinh'])?></div><?php } ?>
			</div>
			<div style="margin-bottom:18px;">
				<h3 style="margin:0 0 10px 0; font-size:17px; color:#0f766e;">Phiếu lương giáo viên</h3>
				<div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">Lương thực nhận</div><div style="<?=$valStyle?>"><?=fmtMoney($employee['payroll_luong_thuc_nhan'])?></div></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">Khoản phải nộp</div><div style="<?=$valStyle?>"><?=fmtMoney($employee['payroll_nghia_vu_gv'])?></div></div>
					<div style="<?=$rowStyle?> font-weight:700;"><div style="<?=$labelStyle?>">Nhận</div><div style="<?=$valStyle?> color:#0f766e;"><?=($nhan > 0 ? number_format($nhan, 0, ',', '.') : '-')?></div></div>
					<div style="height:10px;"></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">Lương CE</div><div style="<?=$valStyle?>"><?=fmtMoneyInt($luongCe)?></div></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">L theo DS phân xe</div><div style="<?=$valStyle?>"><?=fmtMoneyInt($lTheoDsPhanXe)?></div></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">B(TĐ)</div><div style="<?=$valStyle?>"><?=($tdCount > 0 ? $tdCount : '-')?></div></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">B(SS)</div><div style="<?=$valStyle?>"><?=($ssCount > 0 ? $ssCount : '-')?></div></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">C1</div><div style="<?=$valStyle?>"><?=($c1Count > 0 ? $c1Count : '-')?></div></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">Thưởng lễ</div><div style="<?=$valStyle?>"><?=fmtMoney($employee['payroll_thuong_le_tet'])?></div></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">Thanh toán TN + CP chiêu sinh</div><div style="<?=$valStyle?>"><?=fmtMoney($employee['payroll_chieu_sinh_tttn'])?></div></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">Phụ cấp thêm</div><div style="<?=$valStyle?>"><?=fmtMoney($employee['payroll_phu_cap_xang_xe'])?></div></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">BHXH</div><div style="<?=$valStyle?>"><?=fmtMoney($employee['payroll_nld_nop_bhxh_10_5'])?></div></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">Thuế TNCN</div><div style="<?=$valStyle?>"><?=fmtMoney($employee['payroll_thue_tncn'])?></div></div>
					<div style="<?=$rowStyle?> font-weight:700;"><div style="<?=$labelStyle?>">Nhận</div><div style="<?=$valStyle?> color:#0f766e;"><?=($nhan > 0 ? number_format($nhan, 0, ',', '.') : '-')?></div></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">Người phụ thuộc</div><div style="<?=$valStyle?>"><?=htmlspecialchars($nguoiPhuThuoc)?></div></div>
				</div>
			</div>
			<?=renderReferenceUpdateForm($employee, $lookupToken)?>
		</div>
		<?php
		return ob_get_clean();
	}

	function renderVanPhongLookupResult($employee, $message = '', $messageType = 'success')
	{
		$lookupToken = issueEmployeeLookupToken($employee['id'], (string)$employee['ma_tra_cuu']);
		$nguoiPhuThuoc = trim((string)($employee['payroll_so_npt'] ?? ''));
		if($nguoiPhuThuoc === '') $nguoiPhuThuoc = trim((string)($employee['payroll_nguoi_phu_thuoc'] ?? ''));
		if($nguoiPhuThuoc === '') $nguoiPhuThuoc = '-';
		$boPhanHienThi = trim((string)($employee['hang'] ?? ''));
		if($boPhanHienThi === '')
		{
			$payrollDepartment = trim((string)($employee['payroll_department'] ?? ''));
			if($payrollDepartment === 'van_phong') $boPhanHienThi = 'Bộ phận văn phòng';
			elseif($payrollDepartment === 'giao_vien') $boPhanHienThi = 'Bộ phận giáo viên';
		}
		if($boPhanHienThi === '') $boPhanHienThi = '-';
		$luongThucNhan = parsePayrollNumericValue($employee['payroll_luong_thuc_nhan'] ?? '');
		$bhxh = parsePayrollNumericValue($employee['payroll_nld_nop_bhxh_10_5'] ?? '');
		$thueTncn = parsePayrollNumericValue($employee['payroll_thue_tncn'] ?? '');
		$phuCapKpi = parsePayrollNumericValue($employee['payroll_khac_dt_khac'] ?? '');
		$phuCapComXang = parsePayrollNumericValue($employee['payroll_tien_com'] ?? '') + parsePayrollNumericValue($employee['payroll_phu_cap_xang_xe'] ?? '');
		$thanhToanChieuSinh = parsePayrollNumericValue($employee['payroll_chieu_sinh_tttn'] ?? '');
		$phuCapDienThoai = parsePayrollNumericValue($employee['payroll_dien_thoai'] ?? '');
		$lamThemGio = parsePayrollNumericValue($employee['payroll_lam_them_gio'] ?? '');
		$dayLtSh = parsePayrollNumericValue($employee['payroll_day_lt_sat_hach'] ?? '');
		$thuongLe = parsePayrollNumericValue($employee['payroll_thuong_le_tet'] ?? '');
		$luongCanBan = $luongThucNhan - ($phuCapKpi + $thanhToanChieuSinh + $phuCapComXang + $phuCapDienThoai + $lamThemGio + $dayLtSh + $thuongLe) + $bhxh + $thueTncn;

		$rowStyle = 'display:grid; grid-template-columns:minmax(200px,260px) 1fr; gap:6px 12px; padding:5px 0; border-bottom:1px solid #f3f4f6;';
		$labelStyle = 'font-weight:600; color:#374151;';
		$valStyle = 'color:#111827; text-align:right;';

		ob_start();
		?>
		<div style="border:1px solid #d0d5dd; border-radius:16px; padding:18px; background:#ffffff;">
			<?=renderMessageBox($message, $messageType)?>
			<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px,1fr)); gap:10px; margin-bottom:16px;">
				<div><strong>Họ và tên:</strong><br><?=htmlspecialchars($employee['ten'])?></div>
				<div><strong>Mã tra cứu:</strong><br><?=htmlspecialchars($employee['ma_tra_cuu'])?></div>
				<div><strong>CCCD:</strong><br><?=htmlspecialchars($employee['cccd'])?></div>
				<div><strong>Bộ phận:</strong><br><?=htmlspecialchars($boPhanHienThi)?></div>
				<div><strong>Chức vụ:</strong><br><?=htmlspecialchars($employee['khoa'])?></div>
				<?php if(!empty($employee['ngaysinh'])) { ?><div><strong>Ngày sinh:</strong><br><?=htmlspecialchars($employee['ngaysinh'])?></div><?php } ?>
			</div>
			<div style="margin-bottom:18px;">
				<h3 style="margin:0 0 10px 0; font-size:17px; color:#1d4ed8;">Phiếu lương văn phòng</h3>
				<div>
					<div style="<?=$rowStyle?> font-weight:700;"><div style="<?=$labelStyle?>">TT chuyển</div><div style="<?=$valStyle?> color:#1d4ed8;"><?=fmtMoney($employee['payroll_luong_thuc_nhan'])?></div></div>
					<div style="height:6px;"></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">L căn bản</div><div style="<?=$valStyle?>"><?=fmtMoneyInt($luongCanBan)?></div></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">Phụ cấp TN</div><div style="<?=$valStyle?>">-</div></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">Phụ cấp chuyên cần + KPI</div><div style="<?=$valStyle?>"><?=fmtMoneyInt($phuCapKpi)?></div></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">Thanh toán TN + CP chiêu sinh</div><div style="<?=$valStyle?>"><?=fmtMoney($employee['payroll_chieu_sinh_tttn'])?></div></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">Phụ cấp cơm + xăng</div><div style="<?=$valStyle?>"><?=fmtMoneyInt($phuCapComXang)?></div></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">Phụ cấp điện thoại</div><div style="<?=$valStyle?>"><?=fmtMoney($employee['payroll_dien_thoai'])?></div></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">L làm thêm giờ</div><div style="<?=$valStyle?>"><?=fmtMoney($employee['payroll_lam_them_gio'])?></div></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">Dạy LT + SH</div><div style="<?=$valStyle?>"><?=fmtMoney($employee['payroll_day_lt_sat_hach'])?></div></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">Thưởng lễ</div><div style="<?=$valStyle?>"><?=fmtMoney($employee['payroll_thuong_le_tet'])?></div></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">BHXH 10.5%</div><div style="<?=$valStyle?>"><?=fmtMoney($employee['payroll_nld_nop_bhxh_10_5'])?></div></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">Thuế TNCN</div><div style="<?=$valStyle?>"><?=fmtMoney($employee['payroll_thue_tncn'])?></div></div>
					<div style="<?=$rowStyle?> font-weight:700;"><div style="<?=$labelStyle?>">Nhận</div><div style="<?=$valStyle?> color:#1d4ed8;"><?=fmtMoney($employee['payroll_luong_thuc_nhan'])?></div></div>
					<div style="<?=$rowStyle?>"><div style="<?=$labelStyle?>">Người phụ thuộc</div><div style="<?=$valStyle?>"><?=htmlspecialchars($nguoiPhuThuoc)?></div></div>
				</div>
			</div>
			<?=renderReferenceUpdateForm($employee, $lookupToken)?>
		</div>
		<?php
		return ob_get_clean();
	}

	function renderPayrollLookupResult($employee, $message = '', $messageType = 'success')
	{
		$isGiaoVien = (isset($employee['payroll_department']) && $employee['payroll_department'] === 'giao_vien');
		if($isGiaoVien) return renderGiaoVienLookupResult($employee, $message, $messageType);
		if(isset($employee['payroll_department']) && $employee['payroll_department'] !== '') return renderVanPhongLookupResult($employee, $message, $messageType);
		return renderBasicEmployeeDetail($employee, $message, $messageType);
	}

	$action = (isset($_POST['action']) && $_POST['action'] != '') ? htmlspecialchars($_POST['action']) : 'lookup';
	$keyword = (isset($_POST['keyword']) && $_POST['keyword'] != '') ? normalizeEmployeeLookupText($_POST['keyword']) : '';

	if($action === 'lookup')
	{
		if($keyword === '')
		{
			echo '<p class="ktt">Vui lòng nhập mã tra cứu.</p>';
			exit;
		}

		$employee = findEmployeeByKeyword($d, $lang, $keyword);
		if(empty($employee['id']))
		{
			echo '<p class="ktt">Không tìm thấy nhân viên phù hợp với mã tra cứu đã nhập.</p>';
			exit;
		}

		echo renderPayrollLookupResult($employee);
		exit;
	}

	if($action === 'update_reference')
	{
		$employeeId = (isset($_POST['employee_id']) && $_POST['employee_id'] > 0) ? (int)$_POST['employee_id'] : 0;
		$currentReference = (isset($_POST['current_reference']) && $_POST['current_reference'] != '') ? normalizeEmployeeLookupText($_POST['current_reference']) : '';
		$newReference = (isset($_POST['new_reference']) && $_POST['new_reference'] != '') ? normalizeEmployeeLookupText($_POST['new_reference']) : '';
		$lookupToken = (isset($_POST['lookup_token']) && $_POST['lookup_token'] != '') ? trim((string)$_POST['lookup_token']) : '';

		if($employeeId <= 0)
		{
			echo '<p class="ktt">Không xác định được nhân viên cần cập nhật.</p>';
			exit;
		}

		$employee = $d->rawQueryOne("select * from #_product where id = ? and type = ? and hienthi = 1 limit 0,1", array($employeeId, 'nhan-vien'));
		if(empty($employee['id']))
		{
			echo '<p class="ktt">Nhân viên không tồn tại hoặc đã bị ẩn.</p>';
			exit;
		}

		if(!hasValidEmployeeLookupToken($employeeId, $lookupToken, (string)$employee['ma_tra_cuu']))
		{
			echo renderPayrollLookupResult($employee, 'Phiên xác thực tra cứu đã hết hạn. Vui lòng tra cứu lại trước khi cập nhật.', 'error');
			exit;
		}

		if($currentReference !== (string)$employee['ma_tra_cuu'])
		{
			echo renderPayrollLookupResult($employee, 'Mã tra cứu hiện tại không còn khớp với dữ liệu hệ thống. Vui lòng tra cứu lại.', 'error');
			exit;
		}

		if($newReference === '')
		{
			echo renderPayrollLookupResult($employee, 'Vui lòng nhập mã tra cứu mới.', 'error');
			exit;
		}

		if(!preg_match('/^[A-Za-z0-9\-_.]{3,80}$/', $newReference))
		{
			echo renderPayrollLookupResult($employee, 'Mã tra cứu chỉ gồm chữ/số/ký tự - _ . và dài 3-80 ký tự.', 'error');
			exit;
		}

		if($newReference === $currentReference)
		{
			echo renderPayrollLookupResult($employee, 'Mã tra cứu mới đang trùng mã tra cứu hiện tại.', 'error');
			exit;
		}

		$duplicateEmployee = $d->rawQueryOne("select id from #_product where id <> ? and type = ? and ma_tra_cuu = ? limit 0,1", array($employeeId, 'nhan-vien', $newReference));
		if(!empty($duplicateEmployee['id']))
		{
			echo renderPayrollLookupResult($employee, 'Mã tra cứu mới đã tồn tại trong hệ thống cho một nhân viên khác.', 'error');
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
			echo renderPayrollLookupResult($employee, 'Không thể cập nhật mã tra cứu mới. Vui lòng thử lại sau.', 'error');
			exit;
		}

		unset($_SESSION['employee_lookup_tokens'][$employeeId]);
		$employee['ma_tra_cuu'] = $newReference;
		$employee['options2'] = $data['options2'];

		echo renderPayrollLookupResult($employee, 'Cập nhật mã tra cứu thành công. Từ lần sau có thể tra cứu bằng mã mới.');
		exit;
	}

	echo '<p class="ktt">Yêu cầu không hợp lệ.</p>';
?>