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
function dt_kythi_detail()
{
	global $d, $func, $ky, $items, $ds_khoa, $total_items, $result_total, $result_filters, $result_stats, $tracuu_kq, $tracuu_kw;
	$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
	$ky = $d->rawQueryOne("select * from #_dt_ky_thi where id = ? limit 0,1", array($id));
	if(!$ky || !$ky['id']) $func->transfer("Kỳ kiểm tra không tồn tại", "index.php?com=daotao&act=kythi", false);

	$ds_khoa = dt_khoa_options();
	$allItems = $d->rawQuery("select * from #_dt_ky_thi_hv where id_ky = ? order by substring_index(hoten, ' ', -1) asc, hoten asc", array($id));
	$result_total = count($allItems);
	$result_filters = array('ma_khoa' => dt_req('ma_khoa'), 'ten_khoa' => dt_req('ten_khoa'));
	$items = array(); $result_stats = array();
	foreach($allItems as $item)
	{
		$maKhoa = trim((string)$item['ma_khoa']); $tenKhoa = trim((string)$item['ten_khoa']);
		$matchMaKhoa = function_exists('mb_stripos') ? mb_stripos($maKhoa, $result_filters['ma_khoa'], 0, 'UTF-8') : stripos($maKhoa, $result_filters['ma_khoa']);
		$matchTenKhoa = function_exists('mb_stripos') ? mb_stripos($tenKhoa, $result_filters['ten_khoa'], 0, 'UTF-8') : stripos($tenKhoa, $result_filters['ten_khoa']);
		if($result_filters['ma_khoa'] !== '' && $matchMaKhoa === false) continue;
		if($result_filters['ten_khoa'] !== '' && $matchTenKhoa === false) continue;
		$items[] = $item;
		$key = json_encode(array($maKhoa, $tenKhoa), JSON_UNESCAPED_UNICODE);
		if(!isset($result_stats[$key])) $result_stats[$key] = array('ma_khoa' => $maKhoa, 'ten_khoa' => $tenKhoa, 'so_hv' => 0, 'so_dat' => 0);
		$result_stats[$key]['so_hv']++;
		if((int)$item['co_ket_qua'] === 1 && dt_htkh_ket_qua_dat($item)) $result_stats[$key]['so_dat']++;
	}
	$total_items = count($items);
	$result_stats = array_values($result_stats);
	usort($result_stats, function($a, $b) { return strcmp($a['ma_khoa'].' '.$a['ten_khoa'], $b['ma_khoa'].' '.$b['ten_khoa']); });

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

	// Ưu tiên dữ liệu nhập tay; bổ sung từ hệ thống nếu CCCD đã tồn tại.
	$sys = dt_find_hocvien_by_cccd($cccd);
	$hv = array(
		'id_khoa'  => $sys ? (int)$sys['id_khoa'] : 0,
		'cccd'     => $cccd,
		'ma_hv'    => trim($_POST['ma_hv'] ?? '') ?: ($sys ? $sys['ma_hv'] : ''),
		'hoten'    => $hoten,
		'ngaysinh' => trim($_POST['nam_sinh'] ?? '') ?: ($sys ? $sys['ngaysinh'] : ''),
		'hang'     => trim($_POST['hang'] ?? '') ?: ($sys ? $sys['hang'] : ''),
		'ma_khoa'  => trim($_POST['ma_khoa'] ?? ''),
		'ten_khoa' => trim($_POST['ten_khoa'] ?? ''),
		'gv_hoten' => trim($_POST['giaovien'] ?? '') ?: ($sys ? ($sys['gv_hoten'] ?? '') : ''),
	);
	if($hv['ma_khoa'] === '' && $sys)
	{
		$k = $d->rawQueryOne("select ma_khoa, ten_khoa from #_dt_khoa where id = ? limit 0,1", array($sys['id_khoa']));
		if($k) { $hv['ma_khoa'] = $k['ma_khoa']; if($hv['ten_khoa'] === '') $hv['ten_khoa'] = $k['ten_khoa']; }
	}
	$them = dt_kythi_them_hv($id, $hv);
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

	$aliases = array(
		'ma_hv' => array('mahv','mahocvien','maso'),
		'hoten' => array('hovaten','hoten'),
		'nam_sinh' => array('namsinh','ngaysinh','nts'),
		'cccd' => array('cccd','socccd','socmt','cmnd'),
		'hang' => array('hang'),
		'ma_khoa' => array('makhoa','makhoahoc'),
		'ten_khoa' => array('tenkhoa'),
		'giaovien' => array('giaovien','gv'),
	);
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
	$pending = array(); $emptyStreak = 0;
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
		$emptyStreak = 0;
		$sys = dt_find_hocvien_by_cccd($cccd);
		$hv = array(
			'id_khoa'  => $sys ? (int)$sys['id_khoa'] : 0,
			'cccd'     => $cccd,
			'ma_hv'    => dt_val($row, $map, 'ma_hv') ?: ($sys ? $sys['ma_hv'] : ''),
			'hoten'    => $hoten ?: ($sys ? $sys['hoten'] : ''),
			'ngaysinh' => dt_val($row, $map, 'nam_sinh') ?: ($sys ? $sys['ngaysinh'] : ''),
			'hang'     => dt_val($row, $map, 'hang') ?: ($sys ? $sys['hang'] : ''),
			'ma_khoa'  => dt_val($row, $map, 'ma_khoa'),
			'ten_khoa' => dt_val($row, $map, 'ten_khoa'),
			'gv_hoten' => dt_val($row, $map, 'giaovien') ?: ($sys ? ($sys['gv_hoten'] ?? '') : ''),
		);
		if($hv['hoten'] === '') dt_import_notice("Dòng $r thiếu họ tên. Chưa thay đổi danh sách.", $back, false);
		if(isset($pending[$cccd])) dt_import_notice("Dòng $r trùng CCCD $cccd. Chưa thay đổi danh sách.", $back, false);
		$pending[$cccd] = $hv;
	}
	if(!$pending) dt_import_notice('File không có học viên hợp lệ. Giữ nguyên danh sách hiện tại.', $back, false);
	if(!dt_backup_tables(array('dt_ky_thi_hv'), 'imp_kythi_roster'))
		dt_import_notice('Không thể sao lưu. Chưa thay đổi danh sách.', $back, false);
	$them = 0; $capnhat = 0;
	$d->startTransaction();
	try
	{
		$d->rawQueryOne('select id from #_dt_ky_thi where id = ? for update', array($id));
		foreach($pending as $cccd => $hv)
		{
			$exist = $d->rawQueryOne('select id from #_dt_ky_thi_hv where id_ky = ? and cccd = ? limit 0,1', array($id, $hv['cccd']));
			if($exist)
			{
				$data = $hv;
				$data['nam_sinh'] = $data['ngaysinh']; unset($data['ngaysinh']);
				$data['giaovien'] = $data['gv_hoten']; unset($data['gv_hoten']);
				$d->where('id', $exist['id']);
				if($d->update('dt_ky_thi_hv', $data) === false) throw new RuntimeException('Không thể cập nhật học viên.');
				$capnhat++;
			}
			else
			{
				if(!dt_kythi_them_hv($id, $hv)) throw new RuntimeException('Không thể thêm học viên.');
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
	dt_import_notice(($replace ? 'Đã thay thế danh sách: '.count($pending).' học viên. ' : '')."Thêm $them, cập nhật $capnhat học viên.", "index.php?com=daotao&act=kythiDetail&id=".$id, true);
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

/* Xuất Excel kỳ kiểm tra theo mẫu tổng hợp để kiểm tra (gồm quá trình đào tạo).
 * Mô phỏng gộp chung trong nội dung Lý thuyết. Lọc theo đủ/không đủ điều kiện qua dk=du|chua|all. */
function dt_kythi_export()
{
	global $d, $func;
	$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
	$dk = dt_req('dk'); // '', 'du', 'chua', 'all'
	$ky = $d->rawQueryOne("select * from #_dt_ky_thi where id = ? limit 0,1", array($id));
	if(!$ky) $func->transfer("Kỳ kiểm tra không tồn tại", "index.php?com=daotao&act=kythi", false);
	$rows = $d->rawQuery("select * from #_dt_ky_thi_hv where id_ky = ? order by substring_index(hoten, ' ', -1) asc, hoten asc", array($id));

	$headers = array('STT','Mã HV','Họ và tên','Năm sinh','CCCD','Hạng','Mã khóa','Tên khóa','Giáo viên',
		'Lý thuyết','Cabin','Th.hành hình','DAT','Tổng giờ H','Tổng km H','Tổng giờ Đ','Tổng km Đ','A','B','C','D','Kết luận');
	$out = array(); $redCells = array(); $stt = 1; $ri = 0;
	foreach($rows as $r)
	{
		// Lấy dữ liệu đào tạo trực tiếp từ hồ sơ học viên (nếu có trong hệ thống).
		$hv = ($r['cccd'] !== '') ? dt_find_hocvien_by_cccd($r['cccd']) : null;
		if($hv)
		{
			$courseValues = dt_kythi_course_values($r, $hv);
			$hv['ma_hv'] = $courseValues['ma_hv'];
			$hv['ma_khoa'] = $courseValues['ma_khoa'];
			$hv['ten_khoa'] = $courseValues['ten_khoa'];
			$s = dt_student_summary($hv);
			$du = $s['du_dieu_kien'];
			if($dk === 'du' && !$du) continue;
			if($dk === 'chua' && $du) continue;
			$agg = $s['dat']['agg'];
			$ltText = dt_lythuyet_cell_text($hv, $s['ly_thuyet']['dat']);
			$cabinText = $s['cabin']['dat'] ? 'Đạt' : (empty($s['cabin']['yeu_cau']) ? 'Không yêu cầu' : 'Chưa đạt');
			$hinhText = $s['hinh']['dat'] ? 'Đạt' : 'Chưa đạt';
			$datText = dt_dat_cell_text($s);
			$out[] = array($stt, $hv['ma_hv'], $hv['hoten'], isset($hv['ngaysinh']) ? $hv['ngaysinh'] : $r['nam_sinh'], $r['cccd'], $hv['hang'],
				$hv['ma_khoa'], $hv['ten_khoa'], isset($hv['gv_hoten']) ? $hv['gv_hoten'] : $r['giaovien'],
				$ltText, $cabinText, $hinhText, $datText,
				$s['hinh']['gio'], $s['hinh']['km'], $agg['a'], $agg['e'], $agg['a'], $agg['b'], $agg['c'], $agg['d'],
				$du ? 'Đủ điều kiện' : 'Chưa đủ');
			if(!$s['ly_thuyet']['dat']) $redCells[$ri.',9'] = 1;
			if(!dt_cabin_pass($s['cabin'])) $redCells[$ri.',10'] = 1;
			if(!$s['hinh']['dat']) $redCells[$ri.',11'] = 1;
			if(!$s['dat']['dat']) $redCells[$ri.',12'] = 1;
			if(!$du) $redCells[$ri.',21'] = 1;
		}
		else
		{
			// Khóa cũ chưa có dữ liệu trong hệ thống: chỉ xuất thông tin định danh.
			if($dk === 'du') continue; // không xác định được => coi như chưa đủ
			$out[] = array($stt, $r['ma_hv'], $r['hoten'], $r['nam_sinh'], $r['cccd'], $r['hang'],
				$r['ma_khoa'], $r['ten_khoa'], $r['giaovien'],
				'Chưa có dữ liệu', '', '', 'Chưa có dữ liệu', '', '', '', '', '', '', '', '', 'Chưa đủ');
			$redCells[$ri.',21'] = 1;
		}
		$stt++; $ri++;
	}
	$suffix = ($dk === 'du') ? '-du-dk' : (($dk === 'chua') ? '-chua-du-dk' : '');
	$fn = 'ky-kiem-tra'.$suffix.'-'.($ky['ngay_kt'] ? str_replace('-', '', $ky['ngay_kt']) : date('Ymd')).'.xlsx';
	dt_xlsx_simple('Ky kiem tra', $fn, $headers, $out, array(1, 3, 4), $redCells);
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

	$aliases = array(
		'hoten' => array('hovaten','hoten'),
		'nam_sinh' => array('namsinh','ngaysinh'),
		'cccd' => array('cccd','socccd','socmt'),
		'ma_hv' => array('mahv','mahocvien'),
		'hang' => array('hang'),
		'ma_khoa' => array('makhoa','makhoahoc'),
		'ten_khoa' => array('tenkhoa','khoahang','khoa'),
		'giaovien' => array('giaovien','gv'),
		'diem_lt' => array('diemlt'),
		'lt' => array('lt'),
		'diem_mp' => array('diemmp'),
		'mp' => array('mp'),
		'ket_qua_tong' => array('ketquatong'),
		'diem_hinh' => array('diemhinh'),
		'hinh' => array('hinh'),
		'diem_duong' => array('diemduong'),
		'duong' => array('duong'),
		'chu_chi' => array('chuchi'),
		'diem_chu_chi' => array('diemchuchi'),
		'ket_luan' => array('ketluan','ketluancsdt'),
	);
	$contains = array(
		'cccd' => array('cccd','cmt'),
		'hoten' => array('hovaten'),
		'giaovien' => array('giaovien'),
	);
	list($headerRow, $map, $score) = dt_find_header_row($sheet, $highestRow, $highestCol, $aliases, $contains);
	if($score <= 0 || !isset($map['cccd']) || !isset($map['hoten']))
		dt_import_notice("Không nhận diện được cột 'Họ và tên' và 'CCCD' trong file kết quả.", $back, false);

	dt_backup_tables(array('dt_ky_thi_hv'), 'imp_kythi');
	$d->startTransaction();
	$ok = 0; $moi = 0; $emptyStreak = 0;
	for($r = $headerRow + 1; $r <= $highestRow; $r++)
	{
		$row = dt_read_row($sheet, $r, $highestCol);
		$cccd = dt_normalize_cccd(dt_val($row, $map, 'cccd'));
		$hoten = dt_val($row, $map, 'hoten');
		if(strlen($cccd) < 9)
		{
			continue;
		}
		$emptyStreak = 0;

		$kq = array(
			'diem_lt' => dt_val($row, $map, 'diem_lt'),
			'lt' => dt_val($row, $map, 'lt'),
			'diem_mp' => dt_val($row, $map, 'diem_mp'),
			'mp' => dt_val($row, $map, 'mp'),
			'ket_qua_tong' => dt_val($row, $map, 'ket_qua_tong'),
			'diem_hinh' => dt_val($row, $map, 'diem_hinh'),
			'hinh' => dt_val($row, $map, 'hinh'),
			'diem_duong' => dt_val($row, $map, 'diem_duong'),
			'duong' => dt_val($row, $map, 'duong'),
			'chu_chi' => dt_val($row, $map, 'chu_chi'),
			'diem_chu_chi' => dt_val($row, $map, 'diem_chu_chi'),
			'ket_luan' => dt_val($row, $map, 'ket_luan'),
			'co_ket_qua' => 1,
		);
		$exist = $d->rawQueryOne("select * from #_dt_ky_thi_hv where id_ky = ? and cccd = ? limit 0,1", array($id, $cccd));
		$cccdVariants = dt_cccd_variants($cccd);
		$hv = $cccdVariants ? $d->rawQueryOne("select h.*, k.ma_khoa as sys_ma_khoa, k.ten_khoa as sys_ten_khoa from #_dt_hocvien h left join #_dt_khoa k on k.id = h.id_khoa where h.cccd in (".implode(',', array_fill(0, count($cccdVariants), '?')).") limit 0,1", $cccdVariants) : null;
		$khoaDaoTao = $hv ? array('ma_khoa' => $hv['sys_ma_khoa'], 'ten_khoa' => $hv['sys_ten_khoa']) : null;
		$identity = array(
			'ma_hv' => ($hv && trim((string)$hv['ma_hv']) !== '') ? $hv['ma_hv'] : (dt_val($row, $map, 'ma_hv') ?: ($exist ? $exist['ma_hv'] : '')),
			'ma_khoa' => ($khoaDaoTao && trim((string)$khoaDaoTao['ma_khoa']) !== '') ? $khoaDaoTao['ma_khoa'] : (dt_val($row, $map, 'ma_khoa') ?: ($exist ? $exist['ma_khoa'] : '')),
			'ten_khoa' => ($khoaDaoTao && trim((string)$khoaDaoTao['ten_khoa']) !== '') ? $khoaDaoTao['ten_khoa'] : (dt_val($row, $map, 'ten_khoa') ?: ($exist ? $exist['ten_khoa'] : '')),
		);
		if($exist && $exist['id'])
		{
			$d->where('id', $exist['id']); $d->update('dt_ky_thi_hv', array_merge($kq, $identity));
			$ok++;
		}
		else
		{
			// Khóa cũ: chèn mới để lưu trữ kết quả.
			$kq['id_ky'] = $id;
			$kq['id_khoa'] = $hv ? (int)$hv['id_khoa'] : 0;
			$kq['cccd'] = $cccd;
			$kq['ma_hv'] = $identity['ma_hv'];
			$kq['hoten'] = $hoten ?: ($hv ? $hv['hoten'] : '');
			$kq['nam_sinh'] = dt_val($row, $map, 'nam_sinh') ?: ($hv ? $hv['ngaysinh'] : '');
			$kq['hang'] = dt_val($row, $map, 'hang') ?: ($hv ? $hv['hang'] : '');
			$kq['ma_khoa'] = $identity['ma_khoa'];
			$kq['ten_khoa'] = $identity['ten_khoa'];
			$kq['giaovien'] = dt_val($row, $map, 'giaovien') ?: ($hv ? $hv['gv_hoten'] : '');
			$kq['ngaytao'] = time();
			$d->insert('dt_ky_thi_hv', $kq);
			$moi++;
		}
	}
	if($d->transactionFailed()) { $d->rollback(); dt_import_notice('Import thất bại khi ghi cơ sở dữ liệu; đã hoàn tác toàn bộ file, chưa lưu dòng nào. Vui lòng kiểm tra dữ liệu (độ dài, định dạng) rồi thử lại.', $back, false); }
	$d->commit();
	dt_audit('import', 'ky_thi_hv', $ok + $moi, $file['name']);
	dt_log_import('ky_thi', $file['name'], $ok + $moi, 0);
	dt_import_notice("Import kết quả: cập nhật $ok, thêm mới $moi học viên", "index.php?com=daotao&act=kythiDetail&id=".$id, true);
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
