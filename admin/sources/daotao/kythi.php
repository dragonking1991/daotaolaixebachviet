<?php
if(!defined('SOURCES')) die("Error");

/* ============================================================
 * Mô đun Ngày kiểm tra (kỳ kiểm tra tại TTSH).
 * - Tạo kỳ kiểm tra, thêm học viên đủ điều kiện (khóa mới) và
 *   học viên vắng/rớt khóa cũ (qua tra cứu).
 * - Xuất Excel theo mẫu để import ở TTSH.
 * - Import kết quả từ TTSH về để lưu trữ + tra cứu.
 * ============================================================ */

/* Danh sách các kỳ kiểm tra kèm sĩ số + số có kết quả. */
function dt_kythi_list()
{
	global $d, $items, $total_items;
	$items = $d->rawQuery("select ky.*, (select count(*) from #_dt_ky_thi_hv h where h.id_ky = ky.id) as so_hv, (select count(*) from #_dt_ky_thi_hv h where h.id_ky = ky.id and h.co_ket_qua = 1) as so_kq from #_dt_ky_thi ky order by ky.ngay_kt desc, ky.id desc");
	$total_items = count($items);
}

/* Lưu (tạo/sửa) một kỳ kiểm tra. */
function dt_kythi_save()
{
	global $d, $func;
	$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
	$ten = trim($_POST['ten'] ?? '');
	$ngay = dt_parse_date($_POST['ngay_kt'] ?? '');
	$ghiChu = trim($_POST['ghi_chu'] ?? '');
	if($ten === '' && $ngay === null) $func->transfer("Nhập tên hoặc ngày kiểm tra", "index.php?com=daotao&act=kythi", false);
	$data = array('ten' => htmlspecialchars($ten, ENT_QUOTES, 'UTF-8'), 'ngay_kt' => $ngay, 'ghi_chu' => htmlspecialchars($ghiChu, ENT_QUOTES, 'UTF-8'));
	if($id)
	{
		$d->where('id', $id); $d->update('dt_ky_thi', $data);
	}
	else
	{
		$data['user_tao'] = dt_username(); $data['ngaytao'] = time();
		$id = $d->insert('dt_ky_thi', $data);
	}
	dt_audit('save', 'ky_thi', 1, 'id='.$id);
	$func->transfer("Đã lưu kỳ kiểm tra", "index.php?com=daotao&act=kythiDetail&id=".$id);
}

/* Xóa một kỳ kiểm tra (và học viên trong kỳ). */
function dt_kythi_delete()
{
	global $d, $func;
	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	if(!$id) $func->transfer("Thiếu dữ liệu", "index.php?com=daotao&act=kythi", false);
	dt_backup_tables(array('dt_ky_thi', 'dt_ky_thi_hv'), 'del_kythi');
	$d->rawQuery("delete from #_dt_ky_thi_hv where id_ky = ?", array($id));
	$d->rawQuery("delete from #_dt_ky_thi where id = ?", array($id));
	dt_audit('delete', 'ky_thi', 1, 'id='.$id);
	$func->transfer("Đã xóa kỳ kiểm tra", "index.php?com=daotao&act=kythi");
}

/* Chi tiết một kỳ: danh sách HV + bộ lọc khóa để thêm HV đủ điều kiện. */
function dt_kythi_stat_hang($hang)
{
	$compact = dt_norm_header($hang);
	if(in_array($compact, array('bstd', 'btd'), true)) return 'B11';
	return dt_norm_hang($hang);
}

function dt_kythi_detail()
{
	global $d, $func, $ky, $items, $ds_khoa, $total_items, $result_total, $result_filters, $result_stats, $tracuu_kq, $tracuu_kw, $kythi_counts;
	global $result_hangs, $result_rank_stats;
	$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
	$ky = $d->rawQueryOne("select * from #_dt_ky_thi where id = ? limit 0,1", array($id));
	if(!$ky || !$ky['id']) $func->transfer("Kỳ kiểm tra không tồn tại", "index.php?com=daotao&act=kythi", false);

	$ds_khoa = dt_khoa_options();
	$allItems = $d->rawQuery("select * from #_dt_ky_thi_hv where id_ky = ? order by substring_index(hoten, ' ', -1) asc, hoten asc", array($id));
	$result_total = count($allItems);
	$kythi_counts = array('ket_qua' => 0, 'thieu_khoa' => 0, 'htkh' => dt_kythi_htkh_count($id));
	foreach($allItems as $item)
	{
		if((int)$item['co_ket_qua'] === 1) $kythi_counts['ket_qua']++;
		if(trim((string)$item['ma_khoa']) === '') $kythi_counts['thieu_khoa']++;
	}
	$result_hangs = array(); $result_rank_stats = array();
	foreach($allItems as $item)
	{
		$hang = dt_kythi_stat_hang($item['hang']);
		$result_hangs[$hang] = $hang === '' ? 'Chưa có hạng' : $hang;
	}
	ksort($result_hangs);
	$result_filters = array('ma_khoa' => dt_req('ma_khoa'), 'ten_khoa' => dt_req('ten_khoa'), 'hang' => dt_req('hang'));
	$items = array(); $result_stats = array();
	foreach($allItems as $item)
	{
		$maKhoa = trim((string)$item['ma_khoa']); $tenKhoa = trim((string)$item['ten_khoa']);
		$hang = dt_kythi_stat_hang($item['hang']);
		if($result_filters['hang'] !== '' && ($hang === '' ? 'none' : $hang) !== $result_filters['hang']) continue;
		$matchMaKhoa = function_exists('mb_stripos') ? mb_stripos($maKhoa, $result_filters['ma_khoa'], 0, 'UTF-8') : stripos($maKhoa, $result_filters['ma_khoa']);
		$matchTenKhoa = function_exists('mb_stripos') ? mb_stripos($tenKhoa, $result_filters['ten_khoa'], 0, 'UTF-8') : stripos($tenKhoa, $result_filters['ten_khoa']);
		if($result_filters['ma_khoa'] !== '' && $matchMaKhoa === false) continue;
		if($result_filters['ten_khoa'] !== '' && $matchTenKhoa === false) continue;
		$items[] = $item;
		$key = json_encode(array($hang, $maKhoa, $tenKhoa), JSON_UNESCAPED_UNICODE);
		if(!isset($result_stats[$key])) $result_stats[$key] = array('hang' => $hang, 'ma_khoa' => $maKhoa, 'ten_khoa' => $tenKhoa, 'so_hv' => 0, 'so_dat' => 0);
		$result_stats[$key]['so_hv']++;
		$passed = (int)$item['co_ket_qua'] === 1 && dt_htkh_ket_qua_dat($item);
		if($passed) $result_stats[$key]['so_dat']++;
		if(!isset($result_rank_stats[$hang])) $result_rank_stats[$hang] = array('hang' => $hang, 'khoa' => array(), 'so_hv' => 0, 'so_dat' => 0, 'thieu_khoa' => 0);
		$courseKey = $maKhoa !== '' ? 'ma:'.$maKhoa : ((int)$item['id_khoa'] > 0 ? 'id:'.(int)$item['id_khoa'] : ($tenKhoa !== '' ? 'ten:'.$tenKhoa : ''));
		if($courseKey !== '') $result_rank_stats[$hang]['khoa'][$courseKey] = true;
		else $result_rank_stats[$hang]['thieu_khoa']++;
		$result_rank_stats[$hang]['so_hv']++;
		if($passed) $result_rank_stats[$hang]['so_dat']++;
	}
	ksort($result_rank_stats);
	$total_items = count($items);
	$result_stats = array_values($result_stats);
	usort($result_stats, function($a, $b) { return strcmp($a['hang'].' '.$a['ma_khoa'].' '.$a['ten_khoa'], $b['hang'].' '.$b['ma_khoa'].' '.$b['ten_khoa']); });

	// Tra cứu học viên đủ điều kiện (chưa thuộc kỳ) để thêm lẻ.
	$tracuu_kq = array(); $tracuu_kw = trim(dt_req('tc'));
	if($tracuu_kw !== '')
	{
		$like = '%'.$tracuu_kw.'%';
		$hvs = $d->rawQuery("select h.*, k.ma_khoa, k.ten_khoa from #_dt_hocvien h left join #_dt_khoa k on k.id = h.id_khoa where (h.cccd like ? or h.hoten like ? or h.ma_hv like ?) order by substring_index(h.hoten, ' ', -1) asc, h.hoten asc limit 0,50", array($like, $like, $like));
		$da = array();
		foreach($d->rawQuery("select cccd from #_dt_ky_thi_hv where id_ky = ?", array($id)) as $r) $da[$r['cccd']] = true;
		foreach($hvs as $hv)
		{
			$s = dt_student_summary($hv);
			$tracuu_kq[] = array('hv' => $hv, 'du_dieu_kien' => $s['du_dieu_kien'], 'da_them' => isset($da[$hv['cccd']]));
		}
	}
}

/* Thêm toàn bộ học viên của (các) khóa được chọn vào kỳ.
 * Mặc định chỉ thêm học viên ĐỦ ĐIỀU KIỆN; với all=1 thêm cả chưa đủ điều kiện
 * (để thông báo cho GV theo dõi danh sách không đủ điều kiện). */
function dt_kythi_add_eligible()
{
	global $d, $func;
	$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
	$idKhoa = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$all = !empty($_REQUEST['all']);
	$back = "index.php?com=daotao&act=kythiDetail&id=".$id.($idKhoa ? "&id_khoa=".$idKhoa : "");
	if(!$id) $func->transfer("Thiếu kỳ kiểm tra", "index.php?com=daotao&act=kythi", false);
	if(!$idKhoa) $func->transfer("Chọn khóa để thêm học viên", $back, false);

	$ky = $d->rawQueryOne("select id from #_dt_ky_thi where id = ? limit 0,1", array($id));
	if(!$ky) $func->transfer("Kỳ kiểm tra không tồn tại", "index.php?com=daotao&act=kythi", false);

	$hvs = $d->rawQuery("select h.*, k.ma_khoa, k.ten_khoa from #_dt_hocvien h left join #_dt_khoa k on k.id = h.id_khoa where h.id_khoa = ? order by substring_index(h.hoten, ' ', -1) asc, h.hoten asc", array($idKhoa));
	$them = 0; $boQua = 0;
	foreach($hvs as $hv)
	{
		if(!$all)
		{
			$s = dt_student_summary($hv);
			if(!$s['du_dieu_kien']) { $boQua++; continue; }
		}
		if(dt_kythi_them_hv($id, $hv)) $them++;
	}
	dt_audit('add', 'ky_thi_hv', $them, 'id_ky='.$id.' id_khoa='.$idKhoa.($all ? ' all' : ''));
	$msg = $all ? "Đã thêm $them học viên (toàn bộ khóa, gồm cả chưa đủ điều kiện)" : "Đã thêm $them học viên đủ điều kiện (bỏ qua $boQua chưa đủ điều kiện)";
	$func->transfer($msg, $back);
}

/* Thêm 1 học viên (từ tra cứu) vào kỳ. */
function dt_kythi_add_one()
{
	global $d, $func;
	$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
	$cccd = isset($_REQUEST['cccd']) ? dt_normalize_cccd($_REQUEST['cccd']) : '';
	$back = "index.php?com=daotao&act=kythiDetail&id=".$id."&tc=".urlencode(dt_req('tc'));
	if(!$id || $cccd === '') $func->transfer("Thiếu dữ liệu", $back, false);
	$hv = dt_find_hocvien_by_cccd($cccd);
	if(!$hv) $func->transfer("Không tìm thấy học viên", $back, false);
	$hv['ma_khoa'] = ''; $hv['ten_khoa'] = '';
	$k = $d->rawQueryOne("select ma_khoa, ten_khoa from #_dt_khoa where id = ? limit 0,1", array($hv['id_khoa']));
	if($k) { $hv['ma_khoa'] = $k['ma_khoa']; $hv['ten_khoa'] = $k['ten_khoa']; }
	dt_kythi_them_hv($id, $hv);
	dt_audit('add', 'ky_thi_hv', 1, 'id_ky='.$id.' cccd='.$cccd);
	$func->transfer("Đã thêm học viên vào kỳ kiểm tra", $back);
}

/* Nhập thủ công một học viên vào kỳ (khóa cũ chưa có dữ liệu trong hệ thống).
 * Nếu CCCD đã có trong hệ thống thì lấy kèm thông tin khóa/giáo viên. */
function dt_kythi_add_manual()
{
	global $d, $func;
	$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
	$back = "index.php?com=daotao&act=kythiDetail&id=".$id;
	if(!$id) $func->transfer("Thiếu kỳ kiểm tra", "index.php?com=daotao&act=kythi", false);
	$ky = $d->rawQueryOne("select id from #_dt_ky_thi where id = ? limit 0,1", array($id));
	if(!$ky) $func->transfer("Kỳ kiểm tra không tồn tại", "index.php?com=daotao&act=kythi", false);

	$cccd = dt_normalize_cccd($_POST['cccd'] ?? '');
	$hoten = trim($_POST['hoten'] ?? '');
	if($cccd === '' || $hoten === '') $func->transfer("Nhập tối thiểu Họ tên và CCCD", $back, false);

	// Hạng/mã khóa/tên khóa/giáo viên ưu tiên dữ liệu Đào tạo nếu CCCD đã có trong hệ thống.
	$sys = dt_kythi_sys_hocvien($cccd);
	$data = dt_kythi_identity($sys, array(
		'ma_hv' => trim($_POST['ma_hv'] ?? ''), 'hoten' => $hoten, 'nam_sinh' => trim($_POST['nam_sinh'] ?? ''), 'hang' => trim($_POST['hang'] ?? ''),
		'ma_khoa' => trim($_POST['ma_khoa'] ?? ''), 'ten_khoa' => trim($_POST['ten_khoa'] ?? ''), 'giaovien' => trim($_POST['giaovien'] ?? ''),
	));
	$exist = $d->rawQueryOne("select id from #_dt_ky_thi_hv where id_ky = ? and cccd = ? limit 0,1", array($id, $cccd));
	$them = !$exist && $d->insert('dt_ky_thi_hv', array_merge($data, array('id_ky' => $id, 'cccd' => $cccd, 'ngaytao' => time()))) !== false;
	dt_audit('add', 'ky_thi_hv', $them ? 1 : 0, 'manual id_ky='.$id.' cccd='.$cccd);
	$func->transfer($them ? "Đã thêm học viên vào kỳ kiểm tra" : "Học viên (CCCD $cccd) đã có trong kỳ", $back, $them ? true : false);
}

/* Màn import danh sách học viên vào kỳ (file mẫu danh sách — không phải kết quả). */
function dt_kythi_roster_form()
{
	global $d, $func, $ky;
	$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
	$ky = $d->rawQueryOne("select * from #_dt_ky_thi where id = ? limit 0,1", array($id));
	if(!$ky) $func->transfer("Kỳ kiểm tra không tồn tại", "index.php?com=daotao&act=kythi", false);
}

/* Import danh sách học viên vào kỳ từ file mẫu (áp dụng khóa cũ chưa có dữ liệu). */
function dt_kythi_import_roster()
{
	global $d, $func;
	$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
	$back = "index.php?com=daotao&act=kythiRoster&id=".$id;
	$ky = $d->rawQueryOne("select id from #_dt_ky_thi where id = ? limit 0,1", array($id));
	if(!$ky) $func->transfer("Kỳ kiểm tra không tồn tại", "index.php?com=daotao&act=kythi", false);
	if(!isset($_FILES['file-excel']) || $_FILES['file-excel']['error'] != 0)
		dt_import_notice("Vui lòng chọn file Excel", $back, false);
	$file = $_FILES['file-excel'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	list($sheet, $highestRow, $highestCol) = dt_open_upload_sheet($file, $ext, $back);

	$aliases = array_intersect_key(dt_kythi_import_aliases(), array_flip(array('ma_hv','hoten','nam_sinh','cccd','hang','ma_khoa','ten_khoa','giaovien')));
	$aliases['ma_hv'][] = 'maso';
	$aliases['nam_sinh'][] = 'nts';
	$contains = array(
		'cccd' => array('cccd','cmt','cmnd'),
		'hoten' => array('hovaten'),
		'giaovien' => array('giaovien'),
		'ma_hv' => array('mahv','mahoc'),
	);
	list($headerRow, $map, $score) = dt_find_header_row($sheet, $highestRow, $highestCol, $aliases, $contains);
	if($score <= 0 || !isset($map['cccd']) || !isset($map['hoten']))
		dt_import_notice("Không nhận diện được cột 'Họ và tên' và 'CCCD' trong file danh sách.", $back, false);

	$replace = !isset($_POST['roster_mode']) || $_POST['roster_mode'] !== 'append';
	$pending = array();
	for($r = $headerRow + 1; $r <= $highestRow; $r++)
	{
		$row = dt_read_row($sheet, $r, $highestCol);
		$cccd = dt_normalize_cccd(dt_val($row, $map, 'cccd'));
		$hoten = dt_val($row, $map, 'hoten');
		if(strlen($cccd) < 9)
		{
			if($hoten !== '' || $cccd !== '')
				dt_import_notice("Dòng $r thiếu hoặc sai CCCD. Chưa thay đổi danh sách.", $back, false);
			continue;
		}
		$fileRow = array(
			'ma_hv' => dt_val($row, $map, 'ma_hv'), 'hoten' => $hoten, 'nam_sinh' => dt_kythi_cell_date(dt_val($row, $map, 'nam_sinh')),
			'hang' => dt_val($row, $map, 'hang'), 'ma_khoa' => dt_val($row, $map, 'ma_khoa'), 'ten_khoa' => dt_val($row, $map, 'ten_khoa'), 'giaovien' => dt_val($row, $map, 'giaovien'),
		);
		$sys = dt_kythi_sys_hocvien($cccd);
		if(dt_kythi_first($hoten, $sys['hoten'] ?? '') === '') dt_import_notice("Dòng $r thiếu họ tên. Chưa thay đổi danh sách.", $back, false);
		if(isset($pending[$cccd])) dt_import_notice("Dòng $r trùng CCCD $cccd. Chưa thay đổi danh sách.", $back, false);
		$pending[$cccd] = array('sys' => $sys, 'file' => $fileRow);
	}
	if(!$pending) dt_import_notice('File không có học viên hợp lệ. Giữ nguyên danh sách hiện tại.', $back, false);
	if(!dt_backup_tables(array('dt_ky_thi_hv'), 'imp_kythi_roster'))
		dt_import_notice('Không thể sao lưu. Chưa thay đổi danh sách.', $back, false);
	$them = 0; $capnhat = 0; $coKhoa = 0;
	$d->startTransaction();
	try
	{
		$d->rawQueryOne('select id from #_dt_ky_thi where id = ? for update', array($id));
		foreach($pending as $cccd => $p)
		{
			$exist = $d->rawQueryOne('select * from #_dt_ky_thi_hv where id_ky = ? and cccd = ? limit 0,1', array($id, (string)$cccd));
			$data = dt_kythi_identity($p['sys'], $p['file'], $exist);
			if($data['ma_khoa'] !== '') $coKhoa++;
			if($exist)
			{
				$d->where('id', $exist['id']);
				if($d->update('dt_ky_thi_hv', $data) === false) throw new RuntimeException('Không thể cập nhật học viên.');
				$capnhat++;
			}
			else
			{
				if($d->insert('dt_ky_thi_hv', array_merge($data, array('id_ky' => $id, 'cccd' => (string)$cccd, 'ngaytao' => time()))) === false) throw new RuntimeException('Không thể thêm học viên.');
				$them++;
			}
		}
		if($replace)
		{
			$placeholders = implode(',', array_fill(0, count($pending), '?'));
			$d->rawQuery("delete from #_dt_ky_thi_hv where id_ky = ? and cccd not in ($placeholders)", array_merge(array($id), array_map('strval', array_keys($pending))));
			if($d->getLastErrorCode() !== '00000') throw new RuntimeException('Không thể thay thế danh sách.');
		}
		if($d->commit() === false) throw new RuntimeException('Không thể hoàn tất import.');
	}
	catch(Exception $error)
	{
		$d->rollback();
		dt_import_notice('Import thất bại. Giữ nguyên danh sách trước đó.', $back, false);
	}
	dt_audit('import', 'ky_thi_hv', count($pending), ($replace ? 'replace ' : 'append ').$file['name'].' id_ky='.$id);
	dt_log_import('ky_thi_roster', $file['name'], count($pending), 0);
	$thieuKhoa = count($pending) - $coKhoa;
	dt_import_notice(($replace ? 'Đã thay thế danh sách: '.count($pending).' học viên. ' : '')."Thêm $them, cập nhật $capnhat học viên. Có mã khóa: $coKhoa".($thieuKhoa ? " ($thieuKhoa học viên chưa có trong hệ thống Đào tạo và file không có mã khóa)" : '').'.', "index.php?com=daotao&act=kythiDetail&id=".$id, true);
}

function dt_kythi_remove_selected()
{
	global $d, $func;
	$id = (int)($_POST['id'] ?? 0);
	$back = 'index.php?com=daotao&act=kythiDetail&id='.$id;
	$all = ($_POST['delete_all'] ?? '') === '1';
	$ids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['ids'] ?? array())))));
	if(!$id || (!$all && !$ids)) $func->transfer('Vui lòng chọn học viên cần xóa.', $back, false);
	$ky = $d->rawQueryOne('select id from #_dt_ky_thi where id = ? limit 0,1', array($id));
	if(!$ky) $func->transfer('Kỳ kiểm tra không tồn tại.', 'index.php?com=daotao&act=kythi', false);
	if(!dt_backup_tables(array('dt_ky_thi_hv'), 'del_kythi_roster')) $func->transfer('Không thể sao lưu. Chưa xóa danh sách.', $back, false);
	$params = array($id);
	$where = 'id_ky = ?';
	if(!$all) { $where .= ' and id in ('.implode(',', array_fill(0, count($ids), '?')).')'; $params = array_merge($params, $ids); }
	$d->startTransaction();
	try
	{
		$d->rawQueryOne('select id from #_dt_ky_thi where id = ? for update', array($id));
		$d->rawQuery('delete from #_dt_ky_thi_hv where '.$where, $params);
		if($d->getLastErrorCode() !== '00000' || $d->commit() === false) throw new RuntimeException('Không thể xóa danh sách.');
	}
	catch(Exception $error)
	{
		$d->rollback();
		$func->transfer('Không thể xóa. Danh sách được giữ nguyên.', $back, false);
	}
	dt_audit('delete', 'ky_thi_hv', $all ? 0 : count($ids), 'id_ky='.$id.($all ? ' all' : ' ids='.implode(',', $ids)));
	$func->transfer($all ? 'Đã xóa toàn bộ danh sách học viên trong kỳ.' : 'Đã xóa các học viên đã chọn khỏi kỳ.', $back);
}

/* Xóa 1 học viên khỏi kỳ. */
function dt_kythi_remove_hv()
{
	global $d, $func;
	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	$hvid = isset($_GET['hvid']) ? (int)$_GET['hvid'] : 0;
	if($id && $hvid) $d->rawQuery("delete from #_dt_ky_thi_hv where id = ? and id_ky = ?", array($hvid, $id));
	$func->transfer("Đã xóa học viên khỏi kỳ", "index.php?com=daotao&act=kythiDetail&id=".$id);
}

/* Lấy lại Mã HV/Hạng/Mã khóa/Tên khóa/Giáo viên từ dữ liệu Đào tạo cho mọi học viên trong kỳ. */
function dt_kythi_sync_khoa()
{
	global $d, $func;
	$ky = dt_kythi_load($_POST['id'] ?? 0);
	$back = 'index.php?com=daotao&act=kythiDetail&id='.$ky['id'];
	$rows = $d->rawQuery("select * from #_dt_ky_thi_hv where id_ky = ?", array($ky['id']));
	if(!dt_backup_tables(array('dt_ky_thi_hv'), 'sync_kythi_khoa')) $func->transfer('Không thể sao lưu. Chưa cập nhật.', $back, false);
	$capnhat = 0; $khongCo = 0;
	$d->startTransaction();
	foreach($rows as $r)
	{
		$sys = dt_kythi_sys_hocvien($r['cccd'], (int)$r['id_khoa']);
		if(!$sys) { $khongCo++; continue; }
		$data = dt_kythi_identity($sys, array(), $r);
		unset($data['hoten'], $data['nam_sinh']);
		$changed = false;
		foreach($data as $k => $v) if((string)$r[$k] !== (string)$v) { $changed = true; break; }
		if(!$changed) continue;
		$d->where('id', $r['id']); $d->update('dt_ky_thi_hv', $data);
		$capnhat++;
	}
	if($d->transactionFailed()) { $d->rollback(); $func->transfer('Cập nhật thất bại, dữ liệu được giữ nguyên.', $back, false); }
	$d->commit();
	dt_audit('sync', 'ky_thi_hv', $capnhat, 'id_ky='.$ky['id']);
	$func->transfer("Đã cập nhật thông tin khóa cho $capnhat học viên".($khongCo ? ". $khongCo học viên chưa có trong hệ thống Đào tạo (giữ nguyên)" : ''), $back);
}

/* Xóa kết quả kiểm tra trong kỳ (giữ danh sách học viên). */
function dt_kythi_clear_results()
{
	global $d, $func;
	$ky = dt_kythi_load($_POST['id'] ?? 0);
	$back = 'index.php?com=daotao&act=kythiDetail&id='.$ky['id'];
	if(!dt_backup_tables(array('dt_ky_thi_hv'), 'clear_kythi_kq')) $func->transfer('Không thể sao lưu. Chưa xóa kết quả.', $back, false);
	$n = (int)($d->rawQueryOne("select count(*) as c from #_dt_ky_thi_hv where id_ky = ? and co_ket_qua = 1", array($ky['id']))['c'] ?? 0);
	$set = array();
	foreach(array_merge(dt_kythi_result_fields(), array('ket_qua_tong')) as $f) $set[] = "`$f` = ''";
	$d->rawQuery("update #_dt_ky_thi_hv set ".implode(', ', $set).", co_ket_qua = 0 where id_ky = ?", array($ky['id']));
	if($d->getLastErrorCode() !== '00000') $func->transfer('Không thể xóa kết quả.', $back, false);
	dt_audit('delete', 'ky_thi_ketqua', $n, 'id_ky='.$ky['id']);
	$func->transfer("Đã xóa kết quả kiểm tra của $n học viên. Danh sách học viên được giữ nguyên.", $back);
}

/* Số giấy xác nhận (HTKH) đã cấp cho học viên trong kỳ. */
function dt_kythi_htkh_where()
{
	return "from #_dt_htkh g where exists (select 1 from #_dt_ky_thi_hv e where e.id_ky = ? and e.cccd = g.cccd and (g.id_khoa = e.id_khoa or g.id_khoa = 0))";
}

function dt_kythi_htkh_count($idKy)
{
	global $d;
	return (int)($d->rawQueryOne("select count(*) as c ".dt_kythi_htkh_where(), array((int)$idKy))['c'] ?? 0);
}

/* Xóa toàn bộ số giấy xác nhận đã cấp cho học viên trong kỳ. */
function dt_kythi_clear_htkh()
{
	global $d, $func;
	$ky = dt_kythi_load($_POST['id'] ?? 0);
	$back = 'index.php?com=daotao&act=kythiDetail&id='.$ky['id'];
	$n = dt_kythi_htkh_count($ky['id']);
	if(!$n) $func->transfer('Kỳ này chưa có số giấy xác nhận nào được cấp.', $back, false);
	if(!dt_backup_tables(array('dt_htkh'), 'clear_kythi_htkh')) $func->transfer('Không thể sao lưu. Chưa xóa số giấy.', $back, false);
	$d->rawQuery("delete g ".dt_kythi_htkh_where(), array($ky['id']));
	if($d->getLastErrorCode() !== '00000') $func->transfer('Không thể xóa số giấy xác nhận.', $back, false);
	dt_audit('delete', 'htkh', $n, 'id_ky='.$ky['id']);
	$func->transfer("Đã xóa $n số giấy xác nhận đã cấp trong kỳ.", $back);
}

/* Helper: chèn học viên vào kỳ nếu chưa có (theo id_ky + cccd). */
function dt_kythi_them_hv($idKy, $hv)
{
	global $d;
	$cccd = $hv['cccd'];
	$exist = $d->rawQueryOne("select id from #_dt_ky_thi_hv where id_ky = ? and cccd = ? limit 0,1", array($idKy, $cccd));
	if($exist && $exist['id']) return false;
	$inserted = $d->insert('dt_ky_thi_hv', array(
		'id_ky' => $idKy,
		'id_khoa' => (int)$hv['id_khoa'],
		'cccd' => $cccd,
		'ma_hv' => isset($hv['ma_hv']) ? $hv['ma_hv'] : '',
		'hoten' => isset($hv['hoten']) ? $hv['hoten'] : '',
		'nam_sinh' => isset($hv['ngaysinh']) ? $hv['ngaysinh'] : '',
		'hang' => isset($hv['hang']) ? $hv['hang'] : '',
		'ma_khoa' => isset($hv['ma_khoa']) ? $hv['ma_khoa'] : '',
		'ten_khoa' => isset($hv['ten_khoa']) ? $hv['ten_khoa'] : '',
		'giaovien' => isset($hv['gv_hoten']) ? $hv['gv_hoten'] : '',
		'ngaytao' => time(),
	));
	return $inserted !== false;
}

/* Ưu tiên thông tin khóa trong Đào tạo; nếu hồ sơ khóa thiếu thì giữ giá trị từ kết quả import. */
function dt_kythi_course_values($row, $hv)
{
	global $d;
	$course = ($hv && !empty($hv['id_khoa'])) ? $d->rawQueryOne("select ma_khoa, ten_khoa from #_dt_khoa where id = ? limit 0,1", array((int)$hv['id_khoa'])) : null;
	return array(
		'ma_hv' => ($hv && trim((string)$hv['ma_hv']) !== '') ? $hv['ma_hv'] : $row['ma_hv'],
		'ma_khoa' => ($course && trim((string)$course['ma_khoa']) !== '') ? $course['ma_khoa'] : $row['ma_khoa'],
		'ten_khoa' => ($course && trim((string)$course['ten_khoa']) !== '') ? $course['ten_khoa'] : $row['ten_khoa'],
	);
}

/* ============================ Mẫu TTSH (import = xuất) ============================ */

/* Tiêu đề cột mẫu TTSH (KQ_DaoTao_LT_MP_ddmmyyyy): dòng 1 tiêu đề kỳ, dòng 2 header, dữ liệu từ dòng 3. */
function dt_kythi_ttsh_headers()
{
	return array('STT','Mã HV','Họ và tên','Năm sinh','CCCD','Hạng','Mã khóa','Tên khóa','Giáo viên',
		'Lý thuyết','Cabin','Th.hành hình','DAT','Tổng giờ H','Tổng km H','Tổng giờ Đ','Tổng km Đ',
		'Điểm LT','KQ LT','Ngày đạt LT','Điểm MP','KQ MP','Ngày đạt MP',
		'Điểm KT hình','KQ KT hình','Ngày đạt hình','Điểm KT đường','KQ KT đường','Ngày đạt đường',
		'Điểm KT chữ chi','KQ KT chữ chi','Ngày đạt chữ chi','Kết luận CSĐT','TG bắt đầu','TG kết thúc','Ghi chú');
}

/* Cột kết quả (theo thứ tự mẫu, từ cột "Điểm LT"). */
function dt_kythi_result_fields()
{
	return array('diem_lt','lt','ngay_dat_lt','diem_mp','mp','ngay_dat_mp','diem_hinh','hinh','ngay_dat_hinh',
		'diem_duong','duong','ngay_dat_duong','diem_chu_chi','chu_chi','ngay_dat_chu_chi','ket_luan','tg_bat_dau','tg_ket_thuc','ghi_chu');
}

/* Cột quá trình đào tạo trong file (lưu lại cho học viên khóa cũ chưa có trong hệ thống). */
function dt_kythi_progress_fields()
{
	return array('p_lt','p_cabin','p_hinh','p_dat','gio_h','km_h','gio_d','km_d');
}

/* Alias tiêu đề nhận diện cả mẫu TTSH lẫn mẫu cũ (Điểm LT|LT|...|Kết luận). */
function dt_kythi_import_aliases()
{
	return array(
		'hoten' => array('hovaten','hoten'),
		'nam_sinh' => array('namsinh','ngaysinh'),
		'cccd' => array('cccd','socccd','socmt','cmnd'),
		'ma_hv' => array('mahv','mahocvien'),
		'hang' => array('hang'),
		'ma_khoa' => array('makhoa','makhoahoc'),
		'ten_khoa' => array('tenkhoa','khoahang','khoa'),
		'giaovien' => array('giaovien','gv'),
		'p_lt' => array('lythuyet'),
		'p_cabin' => array('cabin'),
		'p_hinh' => array('thhanhhinh','thuchanhhinh'),
		'p_dat' => array('dat'),
		'gio_h' => array('tonggioh'),
		'km_h' => array('tongkmh'),
		'gio_d' => array('tonggiod'),
		'km_d' => array('tongkmd'),
		'diem_lt' => array('diemlt'),
		'lt' => array('lt','kqlt'),
		'ngay_dat_lt' => array('ngaydatlt'),
		'diem_mp' => array('diemmp'),
		'mp' => array('mp','kqmp'),
		'ngay_dat_mp' => array('ngaydatmp'),
		'ket_qua_tong' => array('ketquatong'),
		'diem_hinh' => array('diemhinh','diemkthinh'),
		'hinh' => array('hinh','kqhinh','kqkthinh'),
		'ngay_dat_hinh' => array('ngaydathinh'),
		'diem_duong' => array('diemduong','diemktduong'),
		'duong' => array('duong','kqduong','kqktduong'),
		'ngay_dat_duong' => array('ngaydatduong'),
		'chu_chi' => array('chuchi','kqchuchi','kqktchuchi'),
		'diem_chu_chi' => array('diemchuchi','diemktchuchi'),
		'ngay_dat_chu_chi' => array('ngaydatchuchi'),
		'ket_luan' => array('ketluan','ketluancsdt'),
		'tg_bat_dau' => array('tgbatdau','thoigianbatdau'),
		'tg_ket_thuc' => array('tgketthuc','thoigianketthuc'),
		'ghi_chu' => array('ghichu'),
	);
}

/* Ô ngày Excel (serial) -> dd/mm/yyyy [HH:ii]; chuỗi giữ nguyên. */
function dt_kythi_cell_date($value)
{
	$value = trim((string)$value);
	if($value === '' || !is_numeric($value)) return $value;
	$serial = (float)$value;
	if($serial < 20000 || $serial > 80000) return $value;
	$ts = PHPExcel_Shared_Date::ExcelToPHP($serial);
	return gmdate(abs($serial - floor($serial)) > 0.00001 ? 'd/m/Y H:i' : 'd/m/Y', $ts);
}

/* Giá trị không rỗng đầu tiên. */
function dt_kythi_first()
{
	foreach(func_get_args() as $v) { $v = trim((string)$v); if($v !== '') return $v; }
	return '';
}

/* Học viên trong Đào tạo theo CCCD (ưu tiên đúng khóa đang gắn), kèm mã/tên khóa. */
function dt_kythi_sys_hocvien($cccd, $idKhoa = 0)
{
	global $d;
	$variants = dt_cccd_variants($cccd);
	if(!$variants) return null;
	$in = implode(',', array_fill(0, count($variants), '?'));
	return $d->rawQueryOne("select h.*, coalesce(k.ma_khoa, '') as ma_khoa, coalesce(k.ten_khoa, '') as ten_khoa from #_dt_hocvien h left join #_dt_khoa k on k.id = h.id_khoa where h.cccd in ($in) order by (h.id_khoa = ?) desc, h.id desc limit 0,1", array_merge($variants, array((int)$idKhoa))) ?: null;
}

/* Thông tin định danh: Hạng/Mã khóa/Tên khóa/Giáo viên/Mã HV ưu tiên dữ liệu Đào tạo, sau đó file, sau đó giá trị đang lưu. */
function dt_kythi_identity($sys, $file, $exist = null)
{
	return array(
		'id_khoa' => $sys ? (int)$sys['id_khoa'] : (int)($exist['id_khoa'] ?? 0),
		'ma_hv' => dt_kythi_first($sys['ma_hv'] ?? '', $file['ma_hv'] ?? '', $exist['ma_hv'] ?? ''),
		'hoten' => dt_kythi_first($file['hoten'] ?? '', $sys['hoten'] ?? '', $exist['hoten'] ?? ''),
		'nam_sinh' => dt_kythi_first($file['nam_sinh'] ?? '', $sys['ngaysinh'] ?? '', $exist['nam_sinh'] ?? ''),
		'hang' => dt_kythi_first($sys['hang'] ?? '', $file['hang'] ?? '', $exist['hang'] ?? ''),
		'ma_khoa' => dt_kythi_first($sys['ma_khoa'] ?? '', $file['ma_khoa'] ?? '', $exist['ma_khoa'] ?? ''),
		'ten_khoa' => dt_kythi_first($sys['ten_khoa'] ?? '', $file['ten_khoa'] ?? '', $exist['ten_khoa'] ?? ''),
		'giaovien' => dt_kythi_first($sys['gv_hoten'] ?? '', $file['giaovien'] ?? '', $exist['giaovien'] ?? ''),
	);
}

/* Ô kết quả không đạt (để tô đỏ). */
function dt_kythi_is_fail($value)
{
	$t = dt_strip_diacritics((string)$value);
	return $t !== '' && (bool)preg_match('/(^|[^a-z])(khong|vang|rot|truot|chua)([^a-z]|$)/', $t);
}

/* Một dòng theo mẫu TTSH (chưa có STT). $du trả về true/false/null (không có dữ liệu Đào tạo). */
function dt_kythi_ttsh_line($r, &$du, &$red)
{
	$sys = dt_kythi_sys_hocvien($r['cccd'], (int)$r['id_khoa']);
	$id = dt_kythi_identity($sys, array(), $r);
	$red = array(); $du = null;
	if($sys)
	{
		$s = dt_student_summary($sys);
		$du = !empty($s['du_dieu_kien']);
		$agg = $s['dat']['agg'];
		$prog = array(
			dt_lythuyet_cell_text($sys, $s['ly_thuyet']['dat']),
			$s['cabin']['dat'] ? 'Đạt' : (empty($s['cabin']['yeu_cau']) ? 'Không yêu cầu' : 'Chưa đạt'),
			$s['hinh']['dat'] ? 'Đạt' : 'Chưa đạt',
			dt_dat_cell_text($s),
			$s['hinh']['gio'], $s['hinh']['km'], $agg['a'], $agg['e'],
		);
		if(!$s['ly_thuyet']['dat']) $red[9] = 1;
		if(!dt_cabin_pass($s['cabin'])) $red[10] = 1;
		if(!$s['hinh']['dat']) $red[11] = 1;
		if(!$s['dat']['dat']) $red[12] = 1;
	}
	else
	{
		$snap = json_decode((string)($r['dl_daotao'] ?? ''), true);
		if(!is_array($snap)) $snap = array();
		$prog = array();
		foreach(dt_kythi_progress_fields() as $f) $prog[] = $snap[$f] ?? '';
	}
	$line = array($id['ma_hv'], $id['hoten'], $id['nam_sinh'], $r['cccd'], $id['hang'], $id['ma_khoa'], $id['ten_khoa'], $id['giaovien']);
	$line = array_merge($line, $prog);
	foreach(dt_kythi_result_fields() as $f) $line[] = (string)($r[$f] ?? '');
	// Chỉ số cột (có STT ở cột 0) của các ô KQ + Kết luận.
	foreach(array(18, 21, 24, 27, 30, 32) as $c) if(dt_kythi_is_fail($line[$c - 1])) $red[$c] = 1;
	return $line;
}

/* Dựng workbook đúng mẫu TTSH cho các dòng của kỳ. */
function dt_kythi_ttsh_build($ky, $rows, $dk = '')
{
	require_once LIBRARIES.'PHPExcel.php';
	$headers = dt_kythi_ttsh_headers();
	$ngay = $ky['ngay_kt'] ? date('d/m/Y', strtotime($ky['ngay_kt'])) : date('d/m/Y');
	$xls = new PHPExcel();
	$sheet = $xls->getActiveSheet();
	$sheet->setTitle('DaoTao');
	$lastCol = PHPExcel_Cell::stringFromColumnIndex(count($headers) - 1);
	$sheet->setCellValue('A1', 'Tốt nghiệp '.$ngay);
	$sheet->mergeCells('A1:'.$lastCol.'1');
	$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
	$sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	foreach($headers as $c => $h)
	{
		$cell = PHPExcel_Cell::stringFromColumnIndex($c).'2';
		$sheet->setCellValue($cell, $h);
	}
	$headStyle = $sheet->getStyle('A2:'.$lastCol.'2');
	$headStyle->getFont()->setBold(true);
	$headStyle->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2F9');
	$headStyle->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

	$text = array(1 => 1, 3 => 1, 4 => 1, 6 => 1);
	$rowNum = 3; $stt = 1;
	foreach($rows as $r)
	{
		$line = dt_kythi_ttsh_line($r, $du, $red);
		if($dk === 'du' && $du !== true) continue;
		if($dk === 'chua' && $du === true) continue;
		array_unshift($line, $stt++);
		foreach($line as $c => $val)
		{
			$cell = PHPExcel_Cell::stringFromColumnIndex($c).$rowNum;
			if(isset($text[$c])) $sheet->setCellValueExplicit($cell, (string)$val, PHPExcel_Cell_DataType::TYPE_STRING);
			else $sheet->setCellValue($cell, $val);
			if(!empty($red[$c]))
			{
				$sheet->getStyle($cell)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('F8D7DA');
				$sheet->getStyle($cell)->getFont()->getColor()->setRGB('C0392B');
			}
		}
		$rowNum++;
	}
	$widths = array(5, 24, 26, 11, 14, 8, 15, 12, 22, 16, 12, 12, 16);
	for($c = 0; $c < count($headers); $c++)
		$sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($c))->setWidth($widths[$c] ?? 11);
	$sheet->freezePane('C3');
	return $xls;
}

/* Xuất file .xls đúng mẫu TTSH rồi kết thúc. */
function dt_kythi_ttsh_output($ky, $rows, $dk = '', $suffix = '')
{
	$xls = dt_kythi_ttsh_build($ky, $rows, $dk);
	$fn = 'KQ_DaoTao_LT_MP_'.($ky['ngay_kt'] ? date('dmY', strtotime($ky['ngay_kt'])) : date('dmY')).$suffix.'.xls';
	if(ob_get_length()) ob_end_clean();
	header('Content-Type: application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="'.$fn.'"');
	header('Cache-Control: max-age=0');
	PHPExcel_IOFactory::createWriter($xls, 'Excel5')->save('php://output');
	exit;
}

function dt_kythi_load($id)
{
	global $d, $func;
	$ky = $d->rawQueryOne("select * from #_dt_ky_thi where id = ? limit 0,1", array((int)$id));
	if(!$ky) $func->transfer("Kỳ kiểm tra không tồn tại", "index.php?com=daotao&act=kythi", false);
	return $ky;
}

/* Xuất danh sách kỳ theo mẫu TTSH (dùng nộp TTSH trước ngày kiểm tra); dk=all|du|chua. */
function dt_kythi_export()
{
	global $d;
	$ky = dt_kythi_load($_REQUEST['id'] ?? 0);
	$dk = dt_req('dk');
	$rows = $d->rawQuery("select * from #_dt_ky_thi_hv where id_ky = ? order by substring_index(hoten, ' ', -1) asc, hoten asc", array($ky['id']));
	dt_kythi_ttsh_output($ky, $rows, $dk, $dk === 'du' ? '_du-dk' : ($dk === 'chua' ? '_chua-du-dk' : ''));
}

/* Phân loại kết quả kiểm tra: dat | vang | rot | chua. */
function dt_kythi_result_status($row)
{
	if((int)($row['co_ket_qua'] ?? 0) !== 1) return 'chua';
	$text = dt_strip_diacritics(trim((string)($row['ket_qua_tong'] ?? '').' '.(string)($row['ket_luan'] ?? '').' '.(string)($row['lt'] ?? '')));
	if(preg_match('/(^|[^a-z])vang([^a-z]|$)/', $text)) return 'vang';
	return dt_htkh_ket_qua_dat($row) ? 'dat' : 'rot';
}

/* Xuất kết quả kiểm tra của kỳ theo cùng mẫu TTSH; kq=all|dat|vang|rot, ids[] = mục đã chọn. */
function dt_kythi_export_results()
{
	global $d;
	$ky = dt_kythi_load($_REQUEST['id'] ?? 0);
	$kq = dt_req('kq');
	if(!in_array($kq, array('all', 'dat', 'vang', 'rot'), true)) $kq = 'all';
	$pick = array_flip(array_filter(array_map('intval', (array)($_GET['ids'] ?? array()))));
	// Mục đã chọn: xuất đúng các dòng được chọn (kể cả chưa có kết quả).
	$rows = $d->rawQuery("select * from #_dt_ky_thi_hv where id_ky = ?".($pick ? "" : " and co_ket_qua = 1")." order by substring_index(hoten, ' ', -1) asc, hoten asc", array($ky['id']));
	$rows = array_values(array_filter($rows, function($r) use ($pick, $kq) {
		if($pick && !isset($pick[(int)$r['id']])) return false;
		return $kq === 'all' || dt_kythi_result_status($r) === $kq;
	}));
	dt_kythi_ttsh_output($ky, $rows, '', $pick ? '_chon' : ($kq === 'all' ? '' : '_'.$kq));
}

/* Màn import kết quả TTSH. */
function dt_kythi_upload_form()
{
	global $d, $func, $ky;
	$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
	$ky = $d->rawQueryOne("select * from #_dt_ky_thi where id = ? limit 0,1", array($id));
	if(!$ky) $func->transfer("Kỳ kiểm tra không tồn tại", "index.php?com=daotao&act=kythi", false);
}

/* Import file kết quả từ TTSH về kỳ kiểm tra (cập nhật/chèn theo CCCD). */
function dt_kythi_import_excel()
{
	global $d, $func;
	$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
	$back = "index.php?com=daotao&act=kythiUpload&id=".$id;
	$ky = $d->rawQueryOne("select id from #_dt_ky_thi where id = ? limit 0,1", array($id));
	if(!$ky) $func->transfer("Kỳ kiểm tra không tồn tại", "index.php?com=daotao&act=kythi", false);
	if(!isset($_FILES['file-excel']) || $_FILES['file-excel']['error'] != 0)
		dt_import_notice("Vui lòng chọn file Excel", $back, false);
	$file = $_FILES['file-excel'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	list($sheet, $highestRow, $highestCol) = dt_open_upload_sheet($file, $ext, $back);

	$aliases = dt_kythi_import_aliases();
	$contains = array(
		'cccd' => array('cccd','cmt'),
		'hoten' => array('hovaten'),
		'giaovien' => array('giaovien'),
	);
	list($headerRow, $map, $score) = dt_find_header_row($sheet, $highestRow, $highestCol, $aliases, $contains);
	if($score <= 0 || !isset($map['cccd']) || !isset($map['hoten']))
		dt_import_notice("Không nhận diện được cột 'Họ và tên' và 'CCCD' trong file kết quả.", $back, false);

	$resultFields = array_merge(dt_kythi_result_fields(), array('ket_qua_tong'));
	$dateFields = array('ngay_dat_lt' => 1, 'ngay_dat_mp' => 1, 'ngay_dat_hinh' => 1, 'ngay_dat_duong' => 1, 'ngay_dat_chu_chi' => 1, 'tg_bat_dau' => 1, 'tg_ket_thuc' => 1);
	$progressInFile = array_values(array_filter(dt_kythi_progress_fields(), function($f) use ($map) { return isset($map[$f]); }));

	dt_backup_tables(array('dt_ky_thi_hv'), 'imp_kythi');
	$d->startTransaction();
	$ok = 0; $moi = 0; $coKq = 0; $seen = array();
	for($r = $headerRow + 1; $r <= $highestRow; $r++)
	{
		$row = dt_read_row($sheet, $r, $highestCol);
		$cccd = dt_normalize_cccd(dt_val($row, $map, 'cccd'));
		if(strlen($cccd) < 9 || isset($seen[$cccd])) continue;
		$seen[$cccd] = true;

		// Chỉ ghi cột có trong file; dòng chưa có điểm/kết quả không bị đánh dấu "đã có kết quả".
		$kq = array(); $hasResult = false;
		foreach($resultFields as $f)
		{
			if(!isset($map[$f])) continue;
			$v = dt_val($row, $map, $f);
			if(isset($dateFields[$f])) $v = dt_kythi_cell_date($v);
			$kq[$f] = mb_substr($v, 0, $f === 'ghi_chu' ? 255 : (in_array($f, array('ket_luan','ket_qua_tong','tg_bat_dau','tg_ket_thuc'), true) ? 30 : 20), 'UTF-8');
			if($v !== '' && $f !== 'ghi_chu' && $f !== 'tg_bat_dau' && $f !== 'tg_ket_thuc') $hasResult = true;
		}
		if($hasResult) { $kq['co_ket_qua'] = 1; $coKq++; }

		$exist = $d->rawQueryOne("select * from #_dt_ky_thi_hv where id_ky = ? and cccd in (".implode(',', array_fill(0, count(dt_cccd_variants($cccd)), '?')).") limit 0,1", array_merge(array($id), dt_cccd_variants($cccd)));
		if($progressInFile)
		{
			$snap = json_decode((string)($exist['dl_daotao'] ?? ''), true);
			if(!is_array($snap)) $snap = array();
			foreach($progressInFile as $f) $snap[$f] = dt_val($row, $map, $f);
			$kq['dl_daotao'] = json_encode($snap, JSON_UNESCAPED_UNICODE);
		}
		$fileRow = array(
			'ma_hv' => dt_val($row, $map, 'ma_hv'), 'hoten' => dt_val($row, $map, 'hoten'),
			'nam_sinh' => dt_kythi_cell_date(dt_val($row, $map, 'nam_sinh')), 'hang' => dt_val($row, $map, 'hang'),
			'ma_khoa' => dt_val($row, $map, 'ma_khoa'), 'ten_khoa' => dt_val($row, $map, 'ten_khoa'), 'giaovien' => dt_val($row, $map, 'giaovien'),
		);
		$identity = dt_kythi_identity(dt_kythi_sys_hocvien($cccd, (int)($exist['id_khoa'] ?? 0)), $fileRow, $exist);
		if($exist && $exist['id'])
		{
			$d->where('id', $exist['id']); $d->update('dt_ky_thi_hv', array_merge($kq, $identity));
			$ok++;
		}
		else
		{
			// Khóa cũ/học viên chưa có trong kỳ: chèn mới để lưu trữ kết quả.
			$d->insert('dt_ky_thi_hv', array_merge($kq, $identity, array('id_ky' => $id, 'cccd' => $cccd, 'ngaytao' => time())));
			$moi++;
		}
	}
	if($d->transactionFailed()) { $d->rollback(); dt_import_notice('Import thất bại khi ghi cơ sở dữ liệu; đã hoàn tác toàn bộ file, chưa lưu dòng nào. Vui lòng kiểm tra dữ liệu (độ dài, định dạng) rồi thử lại.', $back, false); }
	$d->commit();
	dt_audit('import', 'ky_thi_hv', $ok + $moi, $file['name']);
	dt_log_import('ky_thi', $file['name'], $ok + $moi, 0);
	dt_import_notice("Import kết quả: cập nhật $ok, thêm mới $moi học viên ($coKq dòng có điểm/kết quả).", "index.php?com=daotao&act=kythiDetail&id=".$id, true);
}

/* Tra cứu kết quả kỳ kiểm tra theo ngày / CCCD / họ tên / mã khóa. */
function dt_kythi_tracuu()
{
	global $d, $items, $total_items, $tc_filters;
	$tc_filters = array(
		'ngay' => dt_req('ngay'),
		'cccd' => dt_req('cccd'),
		'hoten' => dt_req('hoten'),
		'ma_khoa' => dt_req('ma_khoa'),
	);
	$items = array(); $total_items = 0;
	$has = ($tc_filters['ngay'] !== '' || $tc_filters['cccd'] !== '' || $tc_filters['hoten'] !== '' || $tc_filters['ma_khoa'] !== '');
	if(!$has) return;

	$where = "1"; $params = array();
	if($tc_filters['ngay'] !== '') { $where .= " and ky.ngay_kt = ?"; $params[] = dt_parse_date($tc_filters['ngay']); }
	if($tc_filters['cccd'] !== '') { $where .= " and h.cccd like ?"; $params[] = '%'.dt_normalize_cccd($tc_filters['cccd']).'%'; }
	if($tc_filters['hoten'] !== '') { $where .= " and h.hoten like ?"; $params[] = '%'.$tc_filters['hoten'].'%'; }
	if($tc_filters['ma_khoa'] !== '') { $where .= " and h.ma_khoa like ?"; $params[] = '%'.$tc_filters['ma_khoa'].'%'; }
	$items = $d->rawQuery("select h.*, ky.ten as ten_ky, ky.ngay_kt from #_dt_ky_thi_hv h left join #_dt_ky_thi ky on ky.id = h.id_ky where $where order by ky.ngay_kt desc, substring_index(h.hoten, ' ', -1) asc, h.hoten asc limit 0,500", $params);
	$total_items = count($items);
}
