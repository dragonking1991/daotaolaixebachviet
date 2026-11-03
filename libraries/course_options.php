<?php
function course_option_label($course)
{
	$code = trim((string)($course['ma_khoa'] ?? $course['khoa'] ?? ''));
	$name = trim((string)($course['ten_khoa'] ?? ''));
	$count = (int)($course['so_hoc_vien'] ?? $course['so'] ?? $course['total'] ?? 0);
	if($name === '' || ($code !== '' && $name === $code)) $label = $code !== '' ? $code : 'Chưa có tên khóa';
	else $label = ($code !== '' ? $code.' — ' : '').$name;
	return $label.' · '.$count.' HV';
}
