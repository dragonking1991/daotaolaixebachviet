<?php
function course_option_label($course)
{
	$code = trim((string)($course['ma_khoa'] ?? $course['khoa'] ?? ''));
	$name = trim((string)($course['ten_khoa'] ?? ''));
	$count = (int)($course['so_hoc_vien'] ?? $course['so'] ?? $course['total'] ?? 0);
	return ($code !== '' ? $code : 'Chưa có mã khóa').' — '.
		($name !== '' ? $name : 'Chưa có tên khóa').' · '.$count.' HV';
}
