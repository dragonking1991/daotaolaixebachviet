<?php
if(!defined('SOURCES')) die("Error");

/* Trang cấu hình cổng giáo viên: chọn cột hiển thị, chi tiết nội dung thiếu, cho GV tự nhập hình. */
function dt_conggv_config_page()
{
	global $cfg;
	$cfg = dt_conggv_config();
}

function dt_conggv_config_save()
{
	global $func;
	$keys = array_keys(dt_conggv_defaults());
	$out = array();
	foreach($keys as $k) $out[$k] = (isset($_POST[$k]) && (string)$_POST[$k] === '1') ? 1 : 0;
	dt_setting_set('cong_gv', json_encode($out));
	if(function_exists('dt_audit')) dt_audit('config', 'cong_gv', 1, 'Cập nhật cấu hình cổng giáo viên');
	$func->transfer('Đã lưu cấu hình cổng giáo viên', 'index.php?com=daotao&act=congGvConfig');
}
