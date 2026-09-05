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
			'xd_dinh_muc' => 3500000,
			'xd_muc_bt'   => 1200000,
			'xd_muc_ck'   => 3500000,
			'xd_muc_dat'  => 3500000,
		);

		try {
			$rows = $d->rawQuery(
				"SELECT config_key, config_value FROM #_xd_config WHERE config_key IN ('xd_dinh_muc','xd_muc_bt','xd_muc_ck','xd_muc_dat')",
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
		return array(
			'dinh_muc' => $dinhMuc,
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
		$allowed = array('xd_dinh_muc', 'xd_muc_bt', 'xd_muc_ck', 'xd_muc_dat');
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
