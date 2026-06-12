<?php
	if(!defined('SOURCES')) die("Error");

	require_once LIBRARIES.'payroll_config.php';

	switch($act)
	{
		case "capnhat":
			get_payroll_config();
			$template = "payroll_config/man/item_edit";
			break;
		case "save":
			save_payroll_config();
			break;
		default:
			get_payroll_config();
			$template = "payroll_config/man/item_edit";
	}

	function get_payroll_config()
	{
		global $d, $item;
		$item = getPayrollRateConfig($d);
	}

	function save_payroll_config()
	{
		global $d, $func;

		if(empty($_POST)) $func->transfer("Không nhận được dữ liệu", "index.php?com=payroll_config&act=capnhat", false);

		$keys = array('payroll_rate_td', 'payroll_rate_ss', 'payroll_rate_c1', 'payroll_rate_ce');
		$data = isset($_POST['data']) ? $_POST['data'] : array();

		foreach($keys as $key)
		{
			$rawVal = isset($data[$key]) ? $data[$key] : '';
			// Loại bỏ dấu chấm ngăn cách hàng ngàn rồi chuyển sang int
			$rawVal = str_replace(array('.', ','), '', trim($rawVal));
			$value = (is_numeric($rawVal) && (int)$rawVal > 0) ? (int)$rawVal : 0;
			savePayrollRateConfig($d, $key, $value);
		}

		$func->transfer("Cập nhật đơn giá thành công", "index.php?com=payroll_config&act=capnhat");
	}
