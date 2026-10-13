<?php
if(!defined('SOURCES')) die("Error");

/* ================= Cấu hình ngưỡng đào tạo (tập trung để dễ chỉnh) ================= */

/* 7 môn lý thuyết, gồm mô phỏng: key nội bộ => nhãn hiển thị */
function dt_mon_lythuyet()
{
	return array(
		'cau_tao'   => 'Cấu tạo sửa chữa',
		'ky_thuat'  => 'Kỹ thuật lái xe',
		'phan_1'    => 'Pháp luật - Phần 1',
		'phan_2'    => 'Pháp luật - Phần 2',
		'phan_3'    => 'Pháp luật - Phần 3',
		'dao_duc'   => 'Đạo đức người lái xe',
		'mophong'   => 'Mô phỏng',
	);
}

/* 4 môn lý thuyết cho khóa nâng hạng (C = B lên C, CE): học trực tiếp, chỉ check Đạt. */
function dt_mon_lythuyet_nang_hang()
{
	return array(
		'nh_phap_luat'    => 'Pháp luật',
		'nh_kien_thuc_xe' => 'Kiến thức xe nâng hạng',
		'nh_dao_duc'      => 'Đạo đức, văn hóa GT, PCCC',
		'mophong'         => 'Mô phỏng',
	);
}

/* Khóa/hạng nâng hạng (C, CE) dùng chế độ 4 ô check thay vì import 7 môn. */
function dt_is_nang_hang($hang)
{
	$h = dt_norm_hang($hang);
	return ($h === 'C' || $h === 'CE');
}

/* Bộ môn lý thuyết áp dụng theo hạng: nâng hạng => 4 môn check; còn lại => 7 môn. */
function dt_mon_lythuyet_for_hang($hang)
{
	return dt_is_nang_hang($hang) ? dt_mon_lythuyet_nang_hang() : dt_mon_lythuyet();
}

/* Ngưỡng đạt lý thuyết mỗi môn. */
function dt_nguong_lythuyet()
{
	return array('tien_do' => 70, 'diem_kt' => 5); // >= 70 và >= 5
}

/**
 * Ngưỡng DAT theo hạng đã chuẩn hóa.
 * a: tổng giờ (A) tối thiểu; km: quãng đường tối thiểu; dem: giờ đêm tối thiểu.
 * c/d: điều kiện giờ tự động / số sàn tối thiểu (0 = không bắt buộc).
 */
function dt_nguong_dat()
{
	return array(
		'B11' => array('a' => 12, 'km' => 710, 'dem' => 1, 'c' => 0,  'd' => 0),
		'B1'  => array('a' => 20, 'km' => 810, 'dem' => 1, 'c' => 1,  'd' => 19),
		'C1'  => array('a' => 24, 'km' => 825, 'dem' => 1, 'c' => 1,  'd' => 23),
		'C'   => array('a' => 5,  'km' => 210, 'dem' => 1, 'c' => 0,  'd' => 0),
		'CE'  => array('a' => 5,  'km' => 210, 'dem' => 1, 'c' => 0,  'd' => 0),
	);
}

/**
 * Ngưỡng thực hành trong hình theo hạng đã chuẩn hóa.
 * km: quãng đường tối thiểu (>=); gio: thời gian tối thiểu (>).
 */
function dt_nguong_hinh()
{
	return array(
		'B11' => array('km' => 120, 'gio' => 34),
		'B1'  => array('km' => 120, 'gio' => 34),
		'C1'  => array('km' => 113, 'gio' => 35),
		'C'   => array('km' => 15,  'gio' => 7),
		'CE'  => array('km' => 15,  'gio' => 7),
	);
}
