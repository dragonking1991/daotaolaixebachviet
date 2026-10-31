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
		"select h.id, h.id_khoa, h.ma_hv, h.cccd, h.hoten, h.ngaysinh, h.hang, h.gv_hoten, h.gv_key, h.he_daotao,
				k.ma_khoa, k.ten_khoa, k.hang as hang_khoa, k.he_daotao as he_khoa
		 from #_dt_hocvien h inner join #_dt_khoa k on k.id = h.id_khoa
		 where $where order by k.ma_khoa asc, h.id asc",
		$params
	);
	if($rows === false || $d->getLastErrorCode() !== '00000') return array('ok' => false, 'error' => 'Không đọc được danh sách học viên đào tạo.');

	$result = array('ok' => true, 'xd_added' => 0, 'xd_course_updated' => 0, 'xd_group_updated' => 0, 'xd_group_updated_bt' => 0, 'xd_group_updated_ck' => 0, 'ce_added' => 0, 'existing' => 0, 'not_passed' => 0, 'unsupported' => 0, 'errors' => array());
	$now = time();
	/* Toàn bộ lượt đồng bộ là một transaction: có lỗi thì hoàn tác, tránh danh sách XD đồng bộ dở dang. */
	$ownTransaction = !$d->pdo()->inTransaction();
	if($ownTransaction) $d->startTransaction();
	foreach($rows as $student)
	{
		$studentCccd = preg_replace('/\D+/', '', (string)$student['cccd']);
		if($studentCccd === '') continue;
		$hang = dt_norm_hang($student['hang'] !== '' ? $student['hang'] : $student['hang_khoa']);
		$classCodes = array('B1' => 'bss', 'B11' => 'btd', 'C1' => 'c1', 'C' => 'c', 'CE' => 'ce');
		if($hang === '')
		{
			$result['unsupported']++;
			continue;
		}
		$classCode = $classCodes[$hang] ?? strtolower($hang);

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
		if($courseCode === '') $courseCode = trim((string)$student['ten_khoa']);
		$system = trim((string)$student['he_daotao']);
		if($system === '') $system = trim((string)$student['he_khoa']);
		$groupBySystem = array('bt' => 'BT', 'botuc' => 'BT', 'ck' => 'CK', 'chinhkhoa' => 'CK');
		$xdGroup = 'DAT';
		if(in_array($hang, array('B1','B11','C1'), true))
			$xdGroup = $groupBySystem[dt_norm_header($system)] ?? 'DAT';

		$variants = function_exists('dt_cccd_variants') ? dt_cccd_variants($studentCccd) : array($studentCccd);
		$placeholders = implode(',', array_fill(0, count($variants), '?'));
		$existing = $d->rawQueryOne("select id, khoa, nhom, user_tao, thu_tu_file from #_xd_hocvien where cccd in ($placeholders) limit 0,1", $variants);
		if($d->getLastErrorCode() !== '00000')
		{
			$result['errors'][] = $courseCode.': không đọc được hồ sơ XD của học viên '.$studentCccd;
			continue;
		}
		if($existing && !empty($existing['id']))
		{
			$repairGroup = in_array($xdGroup, array('BT','CK'), true) && $existing['nhom'] === 'DAT'
				&& $existing['user_tao'] === 'Đồng bộ DAT' && (int)$existing['thu_tu_file'] === (int)$student['id'];
			if($repairGroup)
			{
				$d->rawQuery("update #_xd_hocvien set nhom = ? where id = ? and nhom = ? and user_tao = ? and thu_tu_file = ?",
					array($xdGroup, (int)$existing['id'], 'DAT', 'Đồng bộ DAT', (int)$student['id']));
				if($d->getLastErrorCode() !== '00000') $result['errors'][] = $courseCode.': không sửa được nhóm '.$xdGroup.' của học viên '.$studentCccd;
				else
				{
					$result['xd_group_updated']++;
					$result['xd_group_updated_'.strtolower($xdGroup)]++;
				}
			}
			if(trim((string)$existing['khoa']) !== $courseCode)
			{
				$d->rawQuery("update #_xd_hocvien set khoa = ? where id = ?", array($courseCode, (int)$existing['id']));
				if($d->getLastErrorCode() !== '00000') $result['errors'][] = $courseCode.': không cập nhật được khóa XD của học viên '.$studentCccd;
				else $result['xd_course_updated']++;
			}
			elseif(!$repairGroup) $result['existing']++;
			continue;
		}

		$ok = $d->rawQuery(
			"insert into #_xd_hocvien (lan_import, thu_tu_file, ho_ten, cccd, ngaysinh, khoa, nhom, nguoi_nop, gv_cccd, gv_hoten, gv_key, dinh_muc, dinh_muc_ca_nhan, da_dieu_chinh_tt, so_tien_thanh_toan, ngay_thanh_toan, id_bangke, ngaytao, user_tao)
			 values (?, ?, ?, ?, ?, ?, ?, '', '', ?, ?, 0, 0, 0, 0, null, 0, ?, ?)
			 on duplicate key update id = id",
				array($now, (int)$student['id'], $student['hoten'], $studentCccd, $student['ngaysinh'], $courseCode, $xdGroup, $teacher, $teacherKey, $now, 'Đồng bộ DAT')
		);
		if($ok === false || $d->getLastErrorCode() !== '00000') $result['errors'][] = $courseCode.': không lưu được học viên '.$studentCccd;
		else $result['xd_added']++;
	}

	if(!empty($result['errors']))
	{
		$result['ok'] = false;
		if($ownTransaction)
		{
			$d->rollback();
			$result['rolled_back'] = true;
		}
	}
	elseif($ownTransaction && $d->commit() === false)
	{
		$result['ok'] = false;
		$result['errors'][] = 'không thể hoàn tất transaction';
		$result['rolled_back'] = true;
	}
	return $result;
}

function xd_sync_training_notice($result)
{
	if(empty($result['ok'])) return ' Đồng bộ gặp lỗi: '.implode('; ', array_slice($result['errors'] ?? array($result['error'] ?? 'lỗi không xác định'), 0, 10)).'.'.(!empty($result['rolled_back']) ? ' Toàn bộ thay đổi của lượt đồng bộ này đã được hoàn tác; dữ liệu XD giữ nguyên như trước.' : '');
	return ' Đã thêm '.$result['xd_added'].' học viên đạt DAT vào XD; cập nhật mã khóa cho '.$result['xd_course_updated'].' hồ sơ XD đã có; sửa nhóm DAT → BT cho '.$result['xd_group_updated_bt'].' hồ sơ, DAT → CK cho '.$result['xd_group_updated_ck'].' hồ sơ đồng bộ cũ; '.$result['existing'].' hồ sơ không cần cập nhật được giữ nguyên.';
}

function xd_mark_imported_dat()
{
	global $d, $func;
	$back = 'index.php?com=xangdau&act=hocvien';
	if(!xd_can_import() || !hash_equals((string)($_SESSION['xd_dat_csrf'] ?? ''), (string)($_POST['csrf'] ?? '')) || empty($_SESSION['xd_dat_csrf']))
		$func->transfer('Không có quyền hoặc phiên thao tác không hợp lệ.', $back, false);
	$khoa = trim((string)($_POST['khoa'] ?? ''));
	$ids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['ids'] ?? array())))));
	$all = ($_POST['all'] ?? '') === '1';
	if(($all && $khoa === '') || (!$all && !$ids)) $func->transfer('Chọn khóa hoặc học viên cần xác nhận DAT.', $back, false);
	$where = 'ngay_thanh_toan is null and coalesce(so_tien_thanh_toan,0)=0 and coalesce(id_bangke,0)=0';
	$params = array();
	if($khoa !== '') { $where .= ' and khoa = ?'; $params[] = $khoa; }
	if(!$all) { $where .= ' and id in ('.implode(',', array_fill(0, count($ids), '?')).')'; $params = array_merge($params, $ids); }
	$d->rawQuery("update #_xd_hocvien set nhom='DAT' where $where", $params);
	$ok = $d->getLastErrorCode() === '00000';
	$func->transfer($ok ? 'Đã xác nhận DAT; hồ sơ đã thanh toán/thuộc bảng kê được giữ nguyên.' : 'Không thể xác nhận DAT.', $back.($khoa !== '' ? '&khoa='.urlencode($khoa) : ''), $ok);
}
