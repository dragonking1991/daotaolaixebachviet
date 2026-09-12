<?php
/**
 * Xăng dầu config helper.
 * Đọc/ghi định mức thanh toán XD từ table_xd_config với fallback mặc định.
 */

if (!function_exists('getXdConfig')) {
	/**
	 * Trả về mảng định mức thanh toán XD.
	 * @param PDODb $d
	 * @return array ['dinh_muc'=>int, 'muc_bt'=>int, 'muc_ck'=>int, 'muc_dat'=>int]
	 */
	function getXdConfig($d)
	{
		$defaults = array(
			'xd_dinh_muc'    => 3500000,
			'xd_dinh_muc_ck'  => 3500000,
			'xd_dinh_muc_dat' => 3500000,
			'xd_muc_bt'   => 1200000,
			'xd_muc_ck'   => 3500000,
			'xd_muc_dat'  => 3500000,
			'xd_dinh_muc_ck_bss' => 0,
			'xd_dinh_muc_ck_btd' => 0,
			'xd_dinh_muc_ck_c1'  => 0,
			'xd_dinh_muc_ck_c'   => 0,
			'xd_dinh_muc_ck_ce'  => 0,
			'xd_dinh_muc_dat_bss' => 0,
			'xd_dinh_muc_dat_btd' => 0,
			'xd_dinh_muc_dat_c1'  => 0,
			'xd_dinh_muc_dat_c'   => 0,
			'xd_dinh_muc_dat_ce'  => 0,
		);

		$keys = array_keys($defaults);
		try {
			$rows = $d->rawQuery(
				"SELECT config_key, config_value FROM #_xd_config WHERE config_key IN ('".implode("','", array_map(function($k){ return str_replace("'", "\\'", $k); }, $keys))."')",
				array()
			);
			if (!empty($rows)) {
				foreach ($rows as $row) {
					$defaults[$row['config_key']] = (int)$row['config_value'];
				}
			}
		} catch (Exception $e) {
			// Bảng chưa tồn tại (migration chưa chạy) -> dùng mặc định
		}

		$dinhMuc = max(0, (int)$defaults['xd_dinh_muc']);
		$dinhMucCk  = max(0, (int)$defaults['xd_dinh_muc_ck']);
		$dinhMucDat = max(0, (int)$defaults['xd_dinh_muc_dat']);
		$dinhMucKhoa = array(
			'ck' => array(),
			'dat' => array(),
		);
		foreach (array('ck', 'dat') as $nhom) {
			foreach (array('bss', 'btd', 'c1', 'c', 'ce') as $hang) {
				$key = 'xd_dinh_muc_' . $nhom . '_' . $hang;
				$dinhMucKhoa[$nhom][$hang] = max(0, (int)($defaults[$key] ?? 0));
			}
		}
		return array(
			'dinh_muc'     => $dinhMuc,
			'dinh_muc_ck'  => $dinhMucCk > 0 ? $dinhMucCk : $dinhMuc,
			'dinh_muc_dat' => $dinhMucDat > 0 ? $dinhMucDat : $dinhMuc,
			'dinh_muc_khoa' => $dinhMucKhoa,
			'muc_bt'   => max(0, (int)$defaults['xd_muc_bt']),
			'muc_ck'   => max(0, (int)$defaults['xd_muc_ck']),
			'muc_dat'  => max(0, (int)$defaults['xd_muc_dat']),
		);
	}
}

if (!function_exists('saveXdConfig')) {
	/**
	 * Lưu một giá trị định mức vào DB.
	 * @param PDODb  $d
	 * @param string $key   'xd_dinh_muc' | 'xd_muc_bt' | 'xd_muc_ck' | 'xd_muc_dat'
	 * @param int    $value
	 * @return bool
	 */
	function saveXdConfig($d, $key, $value)
	{
		$allowed = array(
			'xd_dinh_muc',
			'xd_dinh_muc_ck', 'xd_dinh_muc_dat',
			'xd_dinh_muc_ck_bss', 'xd_dinh_muc_ck_btd', 'xd_dinh_muc_ck_c1', 'xd_dinh_muc_ck_c', 'xd_dinh_muc_ck_ce',
			'xd_dinh_muc_dat_bss', 'xd_dinh_muc_dat_btd', 'xd_dinh_muc_dat_c1', 'xd_dinh_muc_dat_c', 'xd_dinh_muc_dat_ce',
			'xd_muc_bt', 'xd_muc_ck', 'xd_muc_dat'
		);
		if (!in_array($key, $allowed, true)) return false;
		$value = max(0, (int)$value);

		$exists = $d->rawQueryOne(
			"SELECT id FROM #_xd_config WHERE config_key = ? LIMIT 0,1",
			array($key)
		);

		if ($exists && isset($exists['id']) && (int)$exists['id'] > 0) {
			return $d->rawQuery(
				"UPDATE #_xd_config SET config_value = ? WHERE config_key = ?",
				array((string)$value, $key)
			) !== false;
		}

		return $d->rawQuery(
			"INSERT INTO #_xd_config (config_key, config_value) VALUES (?, ?)",
			array($key, (string)$value)
		) !== false;
	}
}

if (!function_exists('xdHangKhoa')) {
	/**
	 * Chuẩn hóa hạng khóa từ cột khoa của học viên.
	 * Ưu tiên chuỗi dài để tránh nhầm lẫn c / c1 / ce.
	 */
	function xdHangKhoa($khoa)
	{
		$normalized = strtolower(trim((string)$khoa));
		$normalized = preg_replace('/[\s\-_\.\/\\]+/', '', $normalized);
		if ($normalized === '') return '';
		$patterns = array('bss', 'btd', 'ce', 'c1', 'c');
		foreach ($patterns as $pattern) {
			if (strpos($normalized, $pattern) !== false) return $pattern;
		}
		return '';
	}
}

if (!function_exists('xdMucTheoNhom')) {
	/**
	 * Trả về mức thanh toán cho một nhóm học viên theo config hiện hành.
	 * @param array  $config kết quả getXdConfig()
	 * @param string $nhom   'BT' | 'CK' | 'DAT'
	 * @return int
	 */
	function xdMucTheoNhom($config, $nhom)
	{
		$nhom = strtoupper(trim((string)$nhom));
		if ($nhom === 'BT')  return (int)$config['muc_bt'];
		if ($nhom === 'CK')  return (int)$config['muc_ck'];
		if ($nhom === 'DAT') return (int)$config['muc_dat'];
		return 0;
	}
}

if (!function_exists('xdDinhMucTheoNhom')) {
	/**
	 * Trả về định mức XD (dùng để chia số hóa đơn ra số học viên) theo nhóm.
	 * BT dùng định mức chung; CK/DAT có thể cấu hình riêng theo hạng khóa.
	 * @param array  $config kết quả getXdConfig()
	 * @param string $nhom   'BT' | 'CK' | 'DAT'
	 * @param string $khoa   'bss' | 'btd' | 'c1' | 'c' | 'ce'
	 * @return int
	 */
	function xdDinhMucTheoNhom($config, $nhom, $khoa = '')
	{
		$nhom = strtoupper(trim((string)$nhom));
		if (in_array($nhom, array('CK', 'DAT'), true)) {
			$hang = xdHangKhoa($khoa);
			$nhomKey = strtolower($nhom);
			if ($hang !== '' && isset($config['dinh_muc_khoa'][$nhomKey][$hang]) && (int)$config['dinh_muc_khoa'][$nhomKey][$hang] > 0) {
				return max(1, (int)$config['dinh_muc_khoa'][$nhomKey][$hang]);
			}
			if ($nhom === 'CK')  return max(1, (int)$config['dinh_muc_ck']);
			if ($nhom === 'DAT') return max(1, (int)$config['dinh_muc_dat']);
		}
		return max(1, (int)$config['dinh_muc']);
	}
}

if (!function_exists('xdDinhMucHocVien')) {
	function xdDinhMucHocVien($config, $hocvien)
	{
		if ((int)($hocvien['da_dieu_chinh_tt'] ?? 0) === 1 || (float)($hocvien['dinh_muc_ca_nhan'] ?? 0) != 0)
			return max(0, (float)($hocvien['dinh_muc_ca_nhan'] ?? 0));
		return (float)xdDinhMucTheoNhom($config, $hocvien['nhom'] ?? '', $hocvien['khoa'] ?? '');
	}
}
