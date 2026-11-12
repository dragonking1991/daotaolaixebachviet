<?php
/**
 * Lương nhân viên theo tháng — file lương 3 sheet VP / GVTH / CTV.
 * Chỉ lấy các cột tô vàng của từng sheet (cấu hình trong payrollMonthlySheetConfig()).
 */

if (!function_exists('payrollMonthlySheetConfig')) {
	/**
	 * Cột được lấy theo từng sheet. Mỗi field: key, label, aliases (tiêu đề đã chuẩn hóa),
	 * type money|int, occurrence (lần xuất hiện thứ n của tiêu đề), after (cột ngay sau cột có key này).
	 */
	function payrollMonthlySheetConfig()
	{
		return array(
			'VP' => array(
				'department' => 'van_phong',
				'label' => 'Văn phòng',
				'fields' => array(
					array('key' => 'luong_cb_pctn', 'label' => 'Lương cơ bản + PC trách nhiệm', 'aliases' => array('luongcobanpctrachnhiem'), 'type' => 'money'),
					array('key' => 'tien_com', 'label' => 'Tiền cơm', 'aliases' => array('tiencom'), 'type' => 'money'),
					array('key' => 'kpi', 'label' => 'KPI', 'aliases' => array('kpi'), 'type' => 'money'),
					array('key' => 'them_gio', 'label' => 'Thêm giờ', 'aliases' => array('themgio'), 'type' => 'money'),
					array('key' => 'phuc_vu_sh_ngoai_gio', 'label' => 'Phục vụ SH ngoài giờ', 'aliases' => array('phucvushngoaigio'), 'type' => 'money'),
					array('key' => 'hoa_hong_chieu_sinh', 'label' => 'Hoa hồng chiêu sinh', 'aliases' => array('hoahongchieusinh'), 'type' => 'money'),
					array('key' => 'thuong_sinh_nhat', 'label' => 'Thưởng sinh nhật', 'aliases' => array('thuongsinhnhat'), 'type' => 'money'),
					array('key' => 'dien_thoai', 'label' => 'Điện thoại', 'aliases' => array('dienthoai'), 'type' => 'money'),
					array('key' => 'xang_xe', 'label' => 'Xăng xe', 'aliases' => array('xangxe'), 'type' => 'money'),
					array('key' => 'thu_nhap_danh_nghia', 'label' => 'Thu nhập danh nghĩa', 'aliases' => array('thunhapdanhnghia'), 'type' => 'money'),
					array('key' => 'bhxh', 'label' => 'BHXH', 'aliases' => array('bhxh'), 'type' => 'money'),
					array('key' => 'thue_tncn', 'label' => 'Thuế TNCN', 'aliases' => array('thuetncn'), 'type' => 'money'),
					array('key' => 'npt', 'label' => 'Giảm trừ người phụ thuộc', 'aliases' => array('npt'), 'type' => 'money'),
					array('key' => 'tntt', 'label' => 'Thu nhập tính thuế', 'aliases' => array('tntt'), 'type' => 'money'),
					array('key' => 'thuc_linh', 'label' => 'Thực lĩnh', 'aliases' => array('thuclinh'), 'type' => 'money'),
				),
			),
			'GVTH' => array(
				'department' => 'giao_vien',
				'label' => 'Giáo viên thực hành',
				'fields' => array(
					// 2 cột tô đỏ (Lương cơ bản + PC trách nhiệm, Xăng xe) gộp thành Lương căn bản
					array('key' => 'luong_can_ban', 'label' => 'Lương căn bản', 'sum' => array('luongcobanpctrachnhiem', 'xangxe'), 'type' => 'money'),
					array('key' => 'luong_tn', 'label' => 'Lương TN', 'aliases' => array('luongtn'), 'type' => 'money'),
					array('key' => 'luong_hv', 'label' => 'Lương HV', 'aliases' => array('luonghv'), 'type' => 'money'),
					array('key' => 'btd', 'label' => 'B(TĐ)', 'aliases' => array('btd'), 'occurrence' => 1, 'type' => 'int'),
					array('key' => 'bss', 'label' => 'B(SS)', 'aliases' => array('bss'), 'occurrence' => 1, 'type' => 'int'),
					array('key' => 'c1', 'label' => 'C1', 'aliases' => array('c1'), 'occurrence' => 1, 'type' => 'int'),
					array('key' => 'ce', 'label' => 'CE', 'aliases' => array('ce'), 'occurrence' => 1, 'type' => 'int'),
					array('key' => 'thuong_sinh_nhat', 'label' => 'Thưởng sinh nhật', 'aliases' => array('thuongsinhnhat'), 'type' => 'money'),
					array('key' => 'hoa_hong_chieu_sinh', 'label' => 'Hoa hồng chiêu sinh', 'aliases' => array('hoahongchieusinh'), 'type' => 'money'),
					array('key' => 'tien_com', 'label' => 'Phụ cấp thêm', 'aliases' => array('tiencom'), 'type' => 'money'),
					array('key' => 'tntt', 'label' => 'Thu nhập tính thuế', 'aliases' => array('tntt'), 'type' => 'money'),
					array('key' => 'bhxh', 'label' => 'BHXH', 'aliases' => array('bhxh'), 'type' => 'money'),
					array('key' => 'thue_tncn', 'label' => 'Thuế TNCN', 'aliases' => array('thuetncn'), 'type' => 'money'),
					array('key' => 'npt', 'label' => 'Người phụ thuộc', 'aliases' => array('sl'), 'type' => 'int'),
					array('key' => 'thuc_linh', 'label' => 'Thực lĩnh', 'aliases' => array('thuclinh'), 'type' => 'money'),
					array('key' => 'ghi_chu', 'label' => 'Ghi chú', 'aliases' => array('ghichu'), 'type' => 'text'),
				),
			),
			'CTV' => array(
				'department' => 'cong_tac_vien',
				'label' => 'Cộng tác viên',
				'fields' => array(
					array('key' => 'luong', 'label' => 'Lương', 'aliases' => array('luong'), 'type' => 'money'),
					array('key' => 'luong_hv', 'label' => 'Lương HV', 'aliases' => array('luonghv'), 'type' => 'money'),
					array('key' => 'btd', 'label' => 'B(TĐ)', 'aliases' => array('btd'), 'occurrence' => 1, 'type' => 'int'),
					array('key' => 'bss', 'label' => 'B(SS)', 'aliases' => array('bss'), 'occurrence' => 1, 'type' => 'int'),
					array('key' => 'c1', 'label' => 'C1', 'aliases' => array('c1'), 'occurrence' => 1, 'type' => 'int'),
					array('key' => 'ce', 'label' => 'CE', 'aliases' => array('ce'), 'occurrence' => 1, 'type' => 'int'),
					array('key' => 'thuong_sinh_nhat', 'label' => 'Thưởng sinh nhật', 'aliases' => array('thuongsinhnhat'), 'type' => 'money'),
					array('key' => 'hoa_hong_chieu_sinh', 'label' => 'Hoa hồng chiêu sinh', 'aliases' => array('hoahongchieusinh'), 'type' => 'money'),
					array('key' => 'thue_tncn', 'label' => 'Thuế TNCN', 'aliases' => array('thuetncn'), 'type' => 'money'),
					array('key' => 'npt', 'label' => 'Người phụ thuộc', 'aliases' => array('sl'), 'type' => 'int'),
					array('key' => 'thuc_linh', 'label' => 'Thực lĩnh', 'aliases' => array('thuclinh'), 'type' => 'money'),
					array('key' => 'ghi_chu', 'label' => 'Ghi chú', 'aliases' => array('ghichu'), 'type' => 'text'),
				),
			),
		);
	}
}

if (!function_exists('payrollMonthlyNorm')) {
	/** Chuẩn hóa tiêu đề/họ tên: chữ thường (multibyte), bỏ dấu, chỉ giữ a-z0-9. */
	function payrollMonthlyNorm($value)
	{
		$value = mb_strtolower(trim((string)$value), 'UTF-8');
		$search = array('à','á','ả','ã','ạ','ă','ằ','ắ','ẳ','ẵ','ặ','â','ầ','ấ','ẩ','ẫ','ậ','đ','è','é','ẻ','ẽ','ẹ','ê','ề','ế','ể','ễ','ệ','ì','í','ỉ','ĩ','ị','ò','ó','ỏ','õ','ọ','ô','ồ','ố','ổ','ỗ','ộ','ơ','ờ','ớ','ở','ỡ','ợ','ù','ú','ủ','ũ','ụ','ư','ừ','ứ','ử','ữ','ự','ỳ','ý','ỷ','ỹ','ỵ');
		$replace = array('a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','d','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y');
		$value = str_replace($search, $replace, $value);
		return preg_replace('/[^a-z0-9]+/', '', $value);
	}
}

if (!function_exists('payrollMonthlyEnsureTable')) {
	function payrollMonthlyEnsureTable($d)
	{
		static $done = false;
		if ($done) return;
		$d->rawQuery("CREATE TABLE IF NOT EXISTS `table_nhanvien_luong` (
			`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`id_product` INT UNSIGNED NOT NULL DEFAULT 0,
			`thang` CHAR(7) NOT NULL,
			`sheet` VARCHAR(20) NOT NULL DEFAULT '',
			`bo_phan` VARCHAR(40) NOT NULL DEFAULT '',
			`hoten` VARCHAR(255) NOT NULL DEFAULT '',
			`cccd` VARCHAR(30) NOT NULL DEFAULT '',
			`chuc_vu` VARCHAR(255) NOT NULL DEFAULT '',
			`du_lieu` MEDIUMTEXT NULL,
			`file_name` VARCHAR(255) NOT NULL DEFAULT '',
			`ngaytao` INT NOT NULL DEFAULT 0,
			PRIMARY KEY (`id`),
			UNIQUE KEY `uniq_cccd_thang` (`cccd`, `thang`),
			KEY `idx_product` (`id_product`),
			KEY `idx_thang` (`thang`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
		// Bản đầu tiên khóa theo id_product; giáo viên không có id_product nên chuyển sang khóa CCCD.
		$oldKey = $d->rawQueryOne("SHOW INDEX FROM `table_nhanvien_luong` WHERE Key_name = 'uniq_nv_thang'");
		if (!empty($oldKey)) {
			$d->rawQuery("ALTER TABLE `table_nhanvien_luong` MODIFY `id_product` INT UNSIGNED NOT NULL DEFAULT 0, DROP INDEX `uniq_nv_thang`, ADD UNIQUE KEY `uniq_cccd_thang` (`cccd`, `thang`), ADD KEY `idx_product` (`id_product`)");
		}
		$done = true;
	}
}

if (!function_exists('payrollMonthlyCccd')) {
	/** CCCD chỉ giữ chữ số; 11 số (mất số 0 đầu do Excel) thì thêm lại số 0. */
	function payrollMonthlyCccd($raw)
	{
		if (is_int($raw) || is_float($raw)) $raw = number_format($raw, 0, '', '');
		$digits = preg_replace('/\D+/', '', (string)$raw);
		if (strlen($digits) == 11) $digits = '0'.$digits;

		return $digits;
	}
}

if (!function_exists('payrollMonthlyFindByCccd')) {
	/** Lương theo tháng của 1 CCCD: ['months' => [...], 'record' => dòng của tháng chọn (mặc định mới nhất)] hoặc null. */
	function payrollMonthlyFindByCccd($d, $cccd, $requestedMonth = '', $sheets = array())
	{
		$cccd = payrollMonthlyCccd($cccd);
		if ($cccd === '') return null;
		try {
			payrollMonthlyEnsureTable($d);
			$where = "cccd = ?";
			$params = array($cccd);
			if (!empty($sheets)) {
				$where .= " and sheet in (".implode(',', array_fill(0, count($sheets), '?')).")";
				$params = array_merge($params, array_values($sheets));
			}
			$rows = $d->rawQuery("select thang from #_nhanvien_luong where $where order by thang desc", $params);
		} catch (Exception $e) {
			return null;
		}
		if (empty($rows)) return null;

		$months = array();
		foreach ($rows as $row) $months[] = $row['thang'];
		$thang = in_array($requestedMonth, $months, true) ? $requestedMonth : $months[0];
		$record = $d->rawQueryOne("select * from #_nhanvien_luong where $where and thang = ? limit 0,1", array_merge($params, array($thang)));
		if (empty($record['id'])) return null;

		return array('months' => $months, 'record' => $record, 'fields' => payrollMonthlyRecordFields($record));
	}
}

if (!function_exists('payrollMonthlyRecordFields')) {
	/** Các khoản đã lưu, lọc + sắp xếp + đặt tên theo cấu hình hiện tại để dữ liệu import cũ hiển thị đúng mẫu mới. */
	function payrollMonthlyRecordFields($record)
	{
		$fields = json_decode((string)(isset($record['du_lieu']) ? $record['du_lieu'] : ''), true);
		if (!is_array($fields)) return array();

		$config = payrollMonthlySheetConfig();
		$sheet = isset($record['sheet']) ? $record['sheet'] : '';
		if (!isset($config[$sheet])) return $fields;

		$stored = array();
		foreach ($fields as $field) if (isset($field['key'])) $stored[$field['key']] = $field;
		// Bản import đầu tiên lưu cột W (= Lương cơ bản + Xăng xe) dưới key cũ
		if (!isset($stored['luong_can_ban']) && isset($stored['luong_cb_xang_xe'])) $stored['luong_can_ban'] = $stored['luong_cb_xang_xe'];

		$result = array();
		foreach ($config[$sheet]['fields'] as $def) {
			if (!isset($stored[$def['key']])) continue;
			$result[] = array('key' => $def['key'], 'label' => $def['label'], 'type' => $def['type'], 'value' => isset($stored[$def['key']]['value']) ? $stored[$def['key']]['value'] : null);
		}
		return $result;
	}
}

if (!function_exists('payrollMonthlyFieldValue')) {
	function payrollMonthlyFieldValue($fields, $key)
	{
		foreach ($fields as $field) {
			if (isset($field['key']) && $field['key'] === $key) return isset($field['value']) ? $field['value'] : null;
		}
		return null;
	}
}

if (!function_exists('payrollMonthlySlip')) {
	/**
	 * Bố cục phiếu lương giáo viên theo mẫu cũ: TT chuyển khoản / Lương căn bản / Nhận,
	 * rồi các khoản chi tiết, BHXH, Thuế TNCN, Nhận, Người phụ thuộc, Ghi chú.
	 */
	function payrollMonthlySlip($fields)
	{
		$byKey = array();
		foreach ($fields as $field) if (isset($field['key'])) $byKey[$field['key']] = $field;
		$row = function ($label, $value, $type = 'money', $strong = false) {
			return array('label' => $label, 'value' => payrollMonthlyFormatValue($value, $type), 'strong' => $strong);
		};

		$thucLinh = isset($byKey['thuc_linh']['value']) && is_numeric($byKey['thuc_linh']['value']) ? (float)$byKey['thuc_linh']['value'] : null;
		$canBan = isset($byKey['luong_can_ban']['value']) && is_numeric($byKey['luong_can_ban']['value']) ? (float)$byKey['luong_can_ban']['value'] : null;
		$nhan = ($thucLinh !== null) ? $thucLinh - (float)$canBan : null;

		$top = array($row('TT chuyển khoản', $thucLinh));
		if (isset($byKey['luong_can_ban'])) {
			$top[] = $row($byKey['luong_can_ban']['label'], $canBan);
			$top[] = $row('Nhận', $nhan, 'money', true);
		}

		$tailKeys = array('bhxh', 'thue_tncn');
		$skip = array('thuc_linh', 'luong_can_ban', 'ghi_chu', 'nguoi_phu_thuoc', 'npt', 'bhxh', 'thue_tncn');
		$detail = array();
		foreach ($fields as $field) {
			if (!isset($field['key']) || in_array($field['key'], $skip, true)) continue;
			$detail[] = $row($field['label'], isset($field['value']) ? $field['value'] : null, isset($field['type']) ? $field['type'] : 'money');
		}
		foreach ($tailKeys as $key) {
			if (isset($byKey[$key])) $detail[] = $row($byKey[$key]['label'], $byKey[$key]['value'], $byKey[$key]['type']);
		}
		$detail[] = $row('Nhận', $nhan, 'money', true);
		foreach (array('nguoi_phu_thuoc', 'npt') as $key) {
			if (isset($byKey[$key])) $detail[] = $row($byKey[$key]['label'], $byKey[$key]['value'], $byKey[$key]['type']);
		}

		$note = isset($byKey['ghi_chu']['value']) ? trim((string)$byKey['ghi_chu']['value']) : '';
		return array('top' => $top, 'detail' => $detail, 'note' => $note);
	}
}

if (!function_exists('payrollMonthlyDepartmentLabel')) {
	function payrollMonthlyDepartmentLabel($department)
	{
		$labels = array('van_phong' => 'Văn phòng', 'giao_vien' => 'Giáo viên thực hành', 'cong_tac_vien' => 'Cộng tác viên');
		return isset($labels[$department]) ? $labels[$department] : '';
	}
}

if (!function_exists('payrollMonthlyLabel')) {
	/** '2026-10' => 'Tháng 10/2026' */
	function payrollMonthlyLabel($thang)
	{
		if (!preg_match('/^(\d{4})-(\d{2})$/', (string)$thang, $m)) return (string)$thang;
		return 'Tháng '.(int)$m[2].'/'.$m[1];
	}
}

if (!function_exists('payrollMonthlyFormatValue')) {
	function payrollMonthlyFormatValue($value, $type)
	{
		if ($value === null || $value === '') return '-';
		if ($type === 'text') return (string)$value;
		if (!is_numeric($value)) return (string)$value;
		if ($type === 'int') return ((int)round((float)$value) === 0) ? '-' : (string)(int)round((float)$value);
		$num = round((float)$value);
		if ($num == 0) return '-';
		return number_format($num, 0, ',', '.');
	}
}
