<?php
/**
 * Payroll rate config helper.
 * Đọc đơn giá học viên từ table_payroll_config với fallback mặc định.
 */

if (!function_exists('getPayrollRateConfig')) {
	/**
	 * Trả về mảng đơn giá học viên.
	 * @param PDODb $d
	 * @return array ['td'=>int, 'ss'=>int, 'c1'=>int, 'ce'=>int]
	 */
	function getPayrollRateConfig($d)
	{
		$defaults = array(
			'payroll_rate_td' => 1000000,
			'payroll_rate_ss' => 2000000,
			'payroll_rate_c1' => 2000000,
			'payroll_rate_ce' => 1100000,
		);

		try {
			$rows = $d->rawQuery(
				"SELECT config_key, config_value FROM table_payroll_config WHERE config_key IN ('payroll_rate_td','payroll_rate_ss','payroll_rate_c1','payroll_rate_ce')",
				array()
			);
			if (!empty($rows)) {
				foreach ($rows as $row) {
					$defaults[$row['config_key']] = (int)$row['config_value'];
				}
			}
		} catch (Exception $e) {
			// Nếu bảng chưa có (ví dụ migration chưa chạy) dùng mặc định
		}

		return array(
			'td' => $defaults['payroll_rate_td'],
			'ss' => $defaults['payroll_rate_ss'],
			'c1' => $defaults['payroll_rate_c1'],
			'ce' => $defaults['payroll_rate_ce'],
		);
	}
}

if (!function_exists('savePayrollRateConfig')) {
	/**
	 * Lưu một giá trị đơn giá vào DB.
	 * @param PDODb  $d
	 * @param string $key  'payroll_rate_td' | 'payroll_rate_ss' | 'payroll_rate_c1' | 'payroll_rate_ce'
	 * @param int    $value
	 * @return bool
	 */
	function savePayrollRateConfig($d, $key, $value)
	{
		$allowed = array('payroll_rate_td', 'payroll_rate_ss', 'payroll_rate_c1', 'payroll_rate_ce');
		if (!in_array($key, $allowed, true)) return false;
		$value = max(0, (int)$value);

		$exists = $d->rawQueryOne(
			"SELECT config_key FROM table_payroll_config WHERE config_key = ? LIMIT 0,1",
			array($key)
		);

		if (!empty($exists['config_key'])) {
			$d->rawQuery(
				"UPDATE table_payroll_config SET config_value = ? WHERE config_key = ?",
				array($value, $key)
			);
		} else {
			$labels = array(
				'payroll_rate_td' => 'Đơn giá TĐ (đ/học viên)',
				'payroll_rate_ss' => 'Đơn giá SS (đ/học viên)',
				'payroll_rate_c1' => 'Đơn giá C1 (đ/học viên)',
				'payroll_rate_ce' => 'Đơn giá CE (đ/học viên)',
			);
			$d->rawQuery(
				"INSERT INTO table_payroll_config (config_key, config_value, label) VALUES (?, ?, ?)",
				array($key, $value, $labels[$key] ?? $key)
			);
		}

		return true;
	}
}
