<?php
if(!defined('SOURCES')) die("Error");

require_once LIBRARIES.'xangdau_config.php';
if(!function_exists('dt_dat_aggregate') || !function_exists('dt_dat_danhgia'))
	require_once LIBRARIES.'daotao_lib.php';
require_once SOURCES.'daotao/schema.php';
require_once SOURCES.'xangdau/schema.php';

function xd_sync_training_students($idKhoa = 0, $cccd = '')
{
	global $d;
	static $schemasReady = false;

	if(!$schemasReady)
	{
		dt_ensure_tables();
		xd_ensure_tables();
		$schemasReady = true;
	}

	$idKhoa = (int)$idKhoa;
	$cccd = function_exists('dt_normalize_cccd') ? dt_normalize_cccd($cccd) : preg_replace('/\D+/', '', (string)$cccd);
	$where = "h.cccd <> ''";
	$params = array();
	if($idKhoa > 0) { $where .= " and h.id_khoa = ?"; $params[] = $idKhoa; }
	if($cccd !== '') { $where .= " and h.cccd = ?"; $params[] = $cccd; }

	$rows = $d->rawQuery(
		"select h.id, h.id_khoa, h.ma_hv, h.cccd, h.hoten, h.ngaysinh, h.hang, h.gv_hoten, h.gv_key,
			k.ma_khoa, k.ten_khoa, k.hang as hang_khoa
		 from #_dt_hocvien h inner join #_dt_khoa k on k.id = h.id_khoa
		 where $where order by k.ma_khoa asc, h.id asc",
		$params
	);
	if($rows === false) return array('ok' => false, 'error' => 'Không đọc được danh sách học viên đào tạo.');

	$result = array('ok' => true, 'xd_added' => 0, 'ce_added' => 0, 'existing' => 0, 'not_passed' => 0, 'unsupported' => 0, 'errors' => array());
	$now = time();
	foreach($rows as $student)
	{
		$studentCccd = preg_replace('/\D+/', '', (string)$student['cccd']);
		if($studentCccd === '') continue;
		$hang = dt_norm_hang($student['hang'] !== '' ? $student['hang'] : $student['hang_khoa']);
		$isCe = $hang === 'CE';
		$classCodes = array('B1' => 'bss', 'B11' => 'btd', 'C1' => 'c1', 'C' => 'c');
		if(!$isCe && !isset($classCodes[$hang]))
		{
			$result['unsupported']++;
			continue;
		}

		$aggregate = dt_dat_aggregate($studentCccd, (int)$student['id_khoa']);
		list($datPassed) = dt_dat_danhgia($hang, $aggregate);
		$override = $d->rawQueryOne("select dat from #_dt_dat_override where id_khoa = ? and cccd = ? limit 0,1", array((int)$student['id_khoa'], $studentCccd));
		if($override && (int)$override['dat'] === 1) $datPassed = 1;
		if(!$datPassed)
		{
			$result['not_passed']++;
			continue;
		}

		$teacher = trim((string)$student['gv_hoten']);
		$teacherKey = function_exists('dt_gv_key') ? dt_gv_key($teacher) : '';
		$courseCode = trim((string)$student['ma_khoa']);
		$courseName = trim((string)$student['ten_khoa']);
		if($isCe)
		{
			$existing = $d->rawQueryOne("select id from #_xd_ce_hocvien where id_khoa = ? and cccd = ? limit 0,1", array((int)$student['id_khoa'], $studentCccd));
			if($existing && !empty($existing['id']))
			{
				$ok = $d->rawQuery(
					"update #_xd_ce_hocvien set ma_khoa = ?, ten_khoa = ?, ma_hv = ?, ho_ten = ?, ngaysinh = ?, gv_hoten = ?, gv_key = ? where id = ?",
					array($courseCode, $courseName, $student['ma_hv'], $student['hoten'], $student['ngaysinh'], $teacher, $teacherKey, (int)$existing['id'])
				);
				if($ok === false) $result['errors'][] = $courseCode.': không cập nhật được hồ sơ CE '.$studentCccd;
				else $result['existing']++;
				continue;
			}
			$ok = $d->rawQuery(
				"insert into #_xd_ce_hocvien (id_khoa, ma_khoa, ten_khoa, ma_hv, ho_ten, cccd, ngaysinh, gv_hoten, gv_key, ngay_dat, ngaytao, user_tao)
				 values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
				 on duplicate key update id = id",
				array((int)$student['id_khoa'], $courseCode, $courseName, $student['ma_hv'], $student['hoten'], $studentCccd, $student['ngaysinh'], $teacher, $teacherKey, date('Y-m-d'), $now, 'Đồng bộ DAT')
			);
			if($ok === false) $result['errors'][] = $courseCode.': không lưu được học viên CE '.$studentCccd;
			else $result['ce_added']++;
			continue;
		}

		$variants = function_exists('dt_cccd_variants') ? dt_cccd_variants($studentCccd) : array($studentCccd);
		$placeholders = implode(',', array_fill(0, count($variants), '?'));
		$existing = $d->rawQueryOne("select id from #_xd_hocvien where cccd in ($placeholders) limit 0,1", $variants);
		if($existing && !empty($existing['id']))
		{
			$result['existing']++;
			continue;
		}

		$ok = $d->rawQuery(
			"insert into #_xd_hocvien (lan_import, thu_tu_file, ho_ten, cccd, ngaysinh, khoa, nhom, nguoi_nop, gv_cccd, gv_hoten, gv_key, dinh_muc, dinh_muc_ca_nhan, da_dieu_chinh_tt, so_tien_thanh_toan, ngay_thanh_toan, id_bangke, ngaytao, user_tao)
			 values (?, ?, ?, ?, ?, ?, 'DAT', '', '', ?, ?, 0, 0, 0, 0, null, 0, ?, ?)
			 on duplicate key update id = id",
			array($now, (int)$student['id'], $student['hoten'], $studentCccd, $student['ngaysinh'], $classCodes[$hang], $teacher, $teacherKey, $now, 'Đồng bộ DAT')
		);
		if($ok === false) $result['errors'][] = $courseCode.': không lưu được học viên '.$studentCccd;
		else $result['xd_added']++;
	}

	if(!empty($result['errors'])) $result['ok'] = false;
	return $result;
}

function xd_sync_training_notice($result)
{
	if(empty($result['ok'])) return ' Đồng bộ gặp lỗi: '.implode('; ', $result['errors'] ?? array($result['error'] ?? 'lỗi không xác định')).'.';
	return ' Đã thêm '.$result['xd_added'].' học viên vào XD nhóm DAT và '.$result['ce_added'].' học viên vào danh sách CE riêng; giữ nguyên '.$result['existing'].' hồ sơ đã có.';
}
