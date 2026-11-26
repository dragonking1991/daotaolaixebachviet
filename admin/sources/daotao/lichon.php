<?php
if(!defined('SOURCES')) die("Error");
require_once LIBRARIES.'lichon_lib.php';

/* ===== Lịch ôn sát hạch: đợt ôn → cấu hình ngày tập/xe → đăng ký GV → xếp lịch → công bố/xuất ===== */

function lo_url($idDot = 0, $tab = '', $extra = array())
{
	$q = array('com' => 'daotao', 'act' => $idDot ? 'lichonDetail' : 'lichon');
	if($idDot) $q['id'] = (int)$idDot;
	if($tab !== '') $q['tab'] = $tab;
	foreach($extra as $k => $v) if($v !== '' && $v !== null) $q[$k] = $v;
	return 'index.php?'.http_build_query($q);
}

function lo_post_str($key, $max = 1000)
{
	$v = isset($_POST[$key]) && is_scalar($_POST[$key]) ? trim((string)$_POST[$key]) : '';
	return mb_substr($v, 0, $max);
}

function lo_post_date($key)
{
	$v = lo_post_str($key, 20);
	if($v === '') return '';
	$dt = DateTime::createFromFormat('Y-m-d', $v);
	return ($dt && $dt->format('Y-m-d') === $v) ? $v : false;
}

function lo_post_hour($key)
{
	$v = lo_post_str($key, 5);
	if($v === '') return null;
	if(!ctype_digit($v) || (int)$v > 24) return false;
	return (int)$v;
}

function lo_require_post($back)
{
	global $func;
	if(strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') $func->transfer('Yêu cầu không hợp lệ.', $back, false);
}

function lo_load_dot_or_fail($id, $back = '')
{
	global $func;
	$dot = $id ? lo_dot_get($id) : null;
	if(!$dot) $func->transfer('Đợt ôn không tồn tại hoặc đã bị xóa.', $back !== '' ? $back : lo_url(), false);
	return $dot;
}

/* Khóa dòng đợt ôn trong 1 giao dịch để các thao tác ghi lịch/đăng ký/cấu hình (admin + cổng GV) chạy tuần tự. */
function lo_lock_dot($idDot)
{
	global $d;
	$d->startTransaction();
	$d->rawQuery('select id from #_dt_lo_dot where id = ? for update', array((int)$idDot));
}

function lo_commit_or_fail($back, $message)
{
	global $d, $func;
	if($d->transactionFailed() || $d->commit() === false)
	{
		$d->rollback();
		$func->transfer($message, $back, false);
	}
}

function lo_rollback_transfer($message, $back)
{
	global $d, $func;
	$d->rollback();
	$func->transfer($message, $back, false);
}

function lo_touch_dot($idDot)
{
	global $d;
	$d->rawQuery('update #_dt_lo_dot set ngaycapnhat = ? where id = ?', array(time(), (int)$idDot));
}

/* ---------- Danh sách đợt ---------- */
function dt_lichon_list()
{
	global $d, $lo_dots, $lo_sh_ky, $lo_filter;
	lo_ensure_tables();
	$lo_filter = isset($_GET['tt']) && is_scalar($_GET['tt']) && isset(lo_status_list()[(string)$_GET['tt']]) ? (string)$_GET['tt'] : '';
	$sql = "select o.*,
		(select count(distinct n.ngay) from #_dt_lo_ngay n where n.id_dot = o.id) as so_ngay,
		(select count(distinct dk.id_gv) from #_dt_lo_dangky dk where dk.id_dot = o.id) as so_gv,
		(select coalesce(sum(dk.so_gio), 0) from #_dt_lo_dangky dk where dk.id_dot = o.id) as gio_dk,
		(select count(*) from #_dt_lo_lich l where l.id_dot = o.id) as gio_xep
		from #_dt_lo_dot o";
	$params = array();
	if($lo_filter !== '') { $sql .= ' where o.trang_thai = ?'; $params[] = $lo_filter; }
	$sql .= ' order by o.ngay_sh desc, o.id desc limit 0,200';
	$lo_dots = $d->rawQuery($sql, $params);
	foreach($lo_dots as &$o)
	{
		$o['gio_san'] = 0;
		foreach(lo_ngay_rows($o['id']) as $n) $o['gio_san'] += $n['tong_gio'];
	}
	unset($o);
	$lo_sh_ky = lo_sh_ky_options();
}

function lo_sh_ky_options()
{
	global $d;
	$exists = $d->rawQueryOne("select table_name from information_schema.tables where table_schema = database() and table_name = 'table_dt_sh_ky' limit 0,1");
	if(!$exists) return array();
	return $d->rawQuery('select id, ten, ngay from #_dt_sh_ky order by ngay desc, id desc limit 0,100');
}

function dt_lichon_save()
{
	global $d, $func;
	lo_ensure_tables();
	$id = (int)($_POST['id'] ?? 0);
	$back = $id ? lo_url($id) : lo_url();
	lo_require_post($back);
	$ngay = lo_post_date('ngay_sh');
	$san = lo_post_str('san', 100);
	$ghiChu = lo_post_str('ghi_chu', 1000);
	$maxPar = (int)lo_post_str('max_song_song', 2);
	$idKy = (int)lo_post_str('id_sh_ky', 11);
	if(!$ngay) $func->transfer('Vui lòng chọn ngày sát hạch hợp lệ.', $back, false);
	if($maxPar < 0 || $maxPar > 6) $func->transfer('Số giờ xếp song song tối đa phải từ 0 đến 6.', $back, false);
	$data = array('ngay_sh' => $ngay, 'san' => $san, 'ghi_chu' => $ghiChu, 'max_song_song' => $maxPar, 'id_sh_ky' => max(0, $idKy), 'ngaycapnhat' => time());
	if($id)
	{
		lo_load_dot_or_fail($id);
		$d->where('id', $id);
		$d->update('dt_lo_dot', $data);
		dt_audit('update', 'lichon', 1, 'id='.$id.' ngay_sh='.$ngay.' san='.$san);
		$func->transfer('Đã cập nhật thông tin đợt ôn.', lo_url($id, isset($_POST['tab']) ? lo_post_str('tab', 20) : ''));
	}
	$data['trang_thai'] = 'nhap';
	$data['nguoi_tao'] = dt_username();
	$data['ngaytao'] = time();
	$newId = $d->insert('dt_lo_dot', $data);
	if(!$newId) $func->transfer('Không thể tạo đợt ôn. Vui lòng thử lại.', lo_url(), false);
	dt_audit('create', 'lichon', 1, 'id='.$newId.' ngay_sh='.$ngay.' san='.$san);
	$func->transfer('Đã tạo đợt ôn. Bước tiếp theo: thêm ngày tập, hạng và số xe.', lo_url($newId, 'cauhinh'));
}

function dt_lichon_delete()
{
	global $d, $func;
	lo_ensure_tables();
	lo_require_post(lo_url());
	$id = (int)($_POST['id'] ?? 0);
	$dot = lo_load_dot_or_fail($id);
	$d->startTransaction();
	$d->rawQuery('delete from #_dt_lo_lich where id_dot = ?', array($id));
	$d->rawQuery('delete from #_dt_lo_dangky where id_dot = ?', array($id));
	$d->rawQuery('delete from #_dt_lo_ngay where id_dot = ?', array($id));
	$d->rawQuery('delete from #_dt_lo_dot where id = ?', array($id));
	if($d->transactionFailed() || $d->commit() === false)
	{
		$d->rollback();
		$func->transfer('Không thể xóa đợt ôn do lỗi cơ sở dữ liệu.', lo_url($id), false);
	}
	dt_audit('delete', 'lichon', 1, 'id='.$id.' '.lo_dot_title($dot));
	$func->transfer('Đã xóa đợt ôn '.lo_dot_title($dot).'.', lo_url());
}

function dt_lichon_status()
{
	global $d, $func;
	lo_ensure_tables();
	$id = (int)($_POST['id'] ?? 0);
	lo_require_post(lo_url($id));
	$dot = lo_load_dot_or_fail($id);
	$st = lo_post_str('trang_thai', 20);
	$list = lo_status_list();
	if(!isset($list[$st])) $func->transfer('Trạng thái không hợp lệ.', lo_url($id), false);
	if($st !== 'nhap' && !lo_ngay_rows($id)) $func->transfer('Cần thêm ít nhất một ngày tập (hạng, số xe) trước khi mở đăng ký.', lo_url($id, 'cauhinh'), false);
	$d->rawQuery('update #_dt_lo_dot set trang_thai = ?, ngaycapnhat = ? where id = ?', array($st, time(), $id));
	dt_audit('status', 'lichon', 1, 'id='.$id.' '.$dot['trang_thai'].' -> '.$st);
	$msg = array(
		'nhap' => 'Đã chuyển về trạng thái chuẩn bị. Giáo viên không còn thấy đợt này.',
		'mo_dk' => 'Đã mở đăng ký. Hãy thông báo giáo viên đăng nhập Cổng GV → tab "Lịch ôn" để đăng ký giờ.',
		'khoa_dk' => 'Đã khóa đăng ký. Có thể tiến hành xếp lịch.',
		'cong_bo' => 'Đã công bố lịch. Giáo viên xem được lịch của mình trên Cổng GV.',
	);
	$tab = $st === 'khoa_dk' ? 'xeplich' : ($st === 'mo_dk' ? 'dangky' : '');
	$func->transfer($msg[$st], lo_url($id, $tab));
}

/* ---------- Chi tiết đợt ---------- */
function dt_lichon_detail()
{
	global $d, $lo_dot, $lo_tab, $lo_ngay, $lo_cap, $lo_dk, $lo_gv_options, $lo_sh_ky, $lo_hang_tabs, $lo_hang,
		$lo_grid, $lo_dk_map, $lo_parallel, $lo_edit_ngay, $lo_edit_dk, $lo_blocks;
	lo_ensure_tables();
	$id = (int)($_GET['id'] ?? 0);
	$lo_dot = lo_load_dot_or_fail($id);
	$tabs = array('cauhinh', 'dangky', 'xeplich');
	$lo_tab = isset($_GET['tab']) && in_array($_GET['tab'], $tabs, true) ? $_GET['tab'] : '';
	$lo_ngay = lo_ngay_rows($id);
	if($lo_tab === '') $lo_tab = !$lo_ngay ? 'cauhinh' : ($lo_dot['trang_thai'] === 'mo_dk' || $lo_dot['trang_thai'] === 'nhap' ? 'dangky' : 'xeplich');
	$lo_cap = lo_capacity($id);
	$lo_dk = lo_dangky_rows($id);
	$lo_sh_ky = lo_sh_ky_options();

	$lo_dk_map = array();
	foreach($lo_dk as $r) $lo_dk_map[$r['id']] = $r;

	$lo_edit_ngay = null;
	if(isset($_GET['edit_ngay']))
		foreach($lo_ngay as $n) if((int)$n['id'] === (int)$_GET['edit_ngay']) $lo_edit_ngay = $n;
	$lo_edit_dk = null;
	if(isset($_GET['edit_dk']) && isset($lo_dk_map[(int)$_GET['edit_dk']])) $lo_edit_dk = $lo_dk_map[(int)$_GET['edit_dk']];

	$lo_gv_options = $d->rawQuery("select id, hoten, cccd, sdt, hang_daotao_phep from #_dt_giaovien where coalesce(thanh_ly, 0) = 0 order by substring_index(hoten, ' ', -1) asc, hoten asc");
	if(!is_array($lo_gv_options)) $lo_gv_options = array();

	$lo_hang_tabs = array();
	foreach($lo_ngay as $n) $lo_hang_tabs[$n['hang']] = true;
	$lo_hang_tabs = array_keys($lo_cap ? array_intersect_key($lo_cap, $lo_hang_tabs) : $lo_hang_tabs);
	$lo_hang = isset($_GET['hang']) && in_array($_GET['hang'], $lo_hang_tabs, true) ? $_GET['hang'] : ($lo_hang_tabs ? $lo_hang_tabs[0] : '');

	/* Lưới: [id_ngay][so_xe][gio] => id_dangky; đánh dấu ô song song (GV có 2 xe cùng giờ). */
	$lo_grid = array();
	$lo_parallel = array();
	$lo_blocks = array();
	$slots = $d->rawQuery('select l.*, n.ngay, n.hang, dk.id_gv from #_dt_lo_lich l join #_dt_lo_ngay n on n.id = l.id_ngay left join #_dt_lo_dangky dk on dk.id = l.id_dangky where l.id_dot = ?', array($id));
	$busy = array();
	$byDk = array();
	foreach($slots as $s)
	{
		$lo_grid[$s['id_ngay']][$s['so_xe']][(int)$s['gio']] = array('dk' => (int)$s['id_dangky'], 'nguon' => $s['nguon']);
		$busy[(int)$s['id_gv']][$s['ngay']][(int)$s['gio']][] = $s;
		$byDk[(int)$s['id_gv']][] = $s;
	}
	foreach($busy as $gvDays) foreach($gvDays as $hours) foreach($hours as $list)
		if(count($list) > 1) foreach($list as $s) $lo_parallel[$s['id_ngay'].'|'.$s['so_xe'].'|'.(int)$s['gio']] = true;
	foreach($byDk as $gv => $list) $lo_blocks[$gv] = lo_gv_blocks($list);
}

/* ---------- Cấu hình ngày tập ---------- */
function dt_lichon_ngay_save()
{
	global $d, $func;
	lo_ensure_tables();
	$idDot = (int)($_POST['id_dot'] ?? 0);
	$back = lo_url($idDot, 'cauhinh');
	lo_require_post($back);
	lo_load_dot_or_fail($idDot);
	$idNgay = (int)($_POST['id'] ?? 0);
	$hang = lo_post_str('hang', 30);
	$bd = lo_post_hour('gio_bd');
	$kt = lo_post_hour('gio_kt');
	$xeErr = '';
	$xe = lo_parse_xe_list(lo_post_str('xe_list', 1000), $xeErr);
	if(!in_array($hang, lo_hang_list(), true)) $func->transfer('Vui lòng chọn hạng xe.', $back, false);
	if($bd === null || $kt === null || $bd === false || $kt === false || $bd >= $kt) $func->transfer('Khung giờ không hợp lệ: giờ kết thúc phải sau giờ bắt đầu.', $back, false);
	if($xe === false) $func->transfer($xeErr, $back, false);
	if(!$xe) $func->transfer('Vui lòng nhập ít nhất một số xe (ví dụ: 21, 23-25).', $back, false);
	$xeList = implode(', ', $xe);

	if($idNgay)
	{
		lo_lock_dot($idDot);
		$row = $d->rawQueryOne('select * from #_dt_lo_ngay where id = ? and id_dot = ? limit 0,1', array($idNgay, $idDot));
		if(!$row) lo_rollback_transfer('Ngày tập không tồn tại.', $back);
		$dup = $d->rawQueryOne('select id from #_dt_lo_ngay where id_dot = ? and ngay = ? and hang = ? and id <> ? limit 0,1', array($idDot, $row['ngay'], $hang, $idNgay));
		if($dup) lo_rollback_transfer('Ngày '.lo_date_vn($row['ngay']).' đã có cấu hình hạng '.$hang.'.', $back);
		$d->rawQuery('update #_dt_lo_ngay set hang = ?, gio_bd = ?, gio_kt = ?, xe_list = ? where id = ?', array($hang, $bd, $kt, $xeList, $idNgay));
		/* Bỏ các ô lịch nằm ngoài cấu hình mới (xe đã bỏ / ngoài khung giờ / đổi hạng) để trả giờ về cho GV. */
		$marks = implode(',', array_fill(0, count($xe), '?'));
		$params = array_merge(array($idNgay, $bd, $kt), $xe);
		$removed = $d->rawQueryOne("select count(*) as n from #_dt_lo_lich where id_ngay = ? and (gio < ? or gio >= ? or so_xe not in ($marks))", $params);
		$d->rawQuery("delete from #_dt_lo_lich where id_ngay = ? and (gio < ? or gio >= ? or so_xe not in ($marks))", $params);
		$removedHang = 0;
		if($hang !== $row['hang'])
		{
			$cnt = $d->rawQueryOne('select count(*) as n from #_dt_lo_lich where id_ngay = ?', array($idNgay));
			$removedHang = (int)$cnt['n'];
			$d->rawQuery('delete from #_dt_lo_lich where id_ngay = ?', array($idNgay));
		}
		if($d->transactionFailed() || $d->commit() === false)
		{
			$d->rollback();
			$func->transfer('Không thể lưu cấu hình ngày tập.', $back, false);
		}
		$n = (int)$removed['n'] + $removedHang;
		lo_touch_dot($idDot);
		dt_audit('update', 'lichon_ngay', 1, 'id_dot='.$idDot.' ngay='.$row['ngay'].' hang='.$hang.' xe='.$xeList.' bo_o='.$n);
		$func->transfer('Đã cập nhật ngày '.lo_date_vn($row['ngay']).' – '.$hang.'.'.($n ? ' '.$n.' giờ đã xếp nằm ngoài cấu hình mới được trả lại cho giáo viên.' : ''), $back);
	}

	$tu = lo_post_date('ngay_tu');
	$den = lo_post_date('ngay_den');
	if(!$tu) $func->transfer('Vui lòng chọn ngày tập.', $back, false);
	if($den === false || ($den && $den < $tu)) $func->transfer('Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.', $back, false);
	if(!$den) $den = $tu;
	$dates = array();
	for($ts = strtotime($tu); $ts <= strtotime($den); $ts = strtotime('+1 day', $ts)) $dates[] = date('Y-m-d', $ts);
	if(count($dates) > 31) $func->transfer('Chỉ thêm tối đa 31 ngày mỗi lần.', $back, false);
	$added = 0; $skipped = array();
	foreach($dates as $day)
	{
		$dup = $d->rawQueryOne('select id from #_dt_lo_ngay where id_dot = ? and ngay = ? and hang = ? limit 0,1', array($idDot, $day, $hang));
		if($dup) { $skipped[] = lo_date_vn($day); continue; }
		$d->insert('dt_lo_ngay', array('id_dot' => $idDot, 'ngay' => $day, 'hang' => $hang, 'gio_bd' => $bd, 'gio_kt' => $kt, 'xe_list' => $xeList));
		$added++;
	}
	lo_touch_dot($idDot);
	dt_audit('create', 'lichon_ngay', $added, 'id_dot='.$idDot.' '.$tu.'..'.$den.' hang='.$hang.' xe='.$xeList);
	$msg = 'Đã thêm '.$added.' ngày tập hạng '.$hang.' ('.count($xe).' xe × '.($kt - $bd).' giờ = '.(count($xe) * ($kt - $bd)).' giờ/ngày).';
	if($skipped) $msg .= ' Bỏ qua ngày đã có cấu hình hạng này: '.implode(', ', $skipped).'.';
	$func->transfer($msg, $back, $added > 0);
}

function dt_lichon_ngay_delete()
{
	global $d, $func;
	lo_ensure_tables();
	$idDot = (int)($_POST['id_dot'] ?? 0);
	$back = lo_url($idDot, 'cauhinh');
	lo_require_post($back);
	$idNgay = (int)($_POST['id'] ?? 0);
	lo_lock_dot($idDot);
	$row = $d->rawQueryOne('select * from #_dt_lo_ngay where id = ? and id_dot = ? limit 0,1', array($idNgay, $idDot));
	if(!$row) lo_rollback_transfer('Ngày tập không tồn tại.', $back);
	$cnt = $d->rawQueryOne('select count(*) as n from #_dt_lo_lich where id_ngay = ?', array($idNgay));
	$d->rawQuery('delete from #_dt_lo_lich where id_ngay = ?', array($idNgay));
	$d->rawQuery('delete from #_dt_lo_ngay where id = ?', array($idNgay));
	lo_commit_or_fail($back, 'Không thể xóa ngày tập do lỗi cơ sở dữ liệu.');
	lo_touch_dot($idDot);
	dt_audit('delete', 'lichon_ngay', 1, 'id_dot='.$idDot.' ngay='.$row['ngay'].' hang='.$row['hang']);
	$func->transfer('Đã xóa ngày '.lo_date_vn($row['ngay']).' – '.$row['hang'].'.'.((int)$cnt['n'] ? ' '.(int)$cnt['n'].' giờ đã xếp được trả lại cho giáo viên.' : ''), $back);
}

/* ---------- Đăng ký của giáo viên (admin nhập hộ / chỉnh sửa / đặt ưu tiên) ---------- */
function dt_lichon_dk_save()
{
	global $d, $func;
	lo_ensure_tables();
	$idDot = (int)($_POST['id_dot'] ?? 0);
	$back = lo_url($idDot, 'dangky');
	lo_require_post($back);
	lo_load_dot_or_fail($idDot);
	$id = (int)($_POST['id'] ?? 0);
	$soGio = lo_post_str('so_gio', 5);
	$hang = lo_post_str('hang', 30);
	$uuNgay = lo_post_date('uu_tien_ngay');
	$uuBd = lo_post_hour('uu_tien_gio_bd');
	$uuKt = lo_post_hour('uu_tien_gio_kt');
	$tenLich = lo_post_str('ten_lich', 50);
	$xacNhan = lo_post_str('xac_nhan', 20);
	if($xacNhan !== '' && !isset(lo_xac_nhan_list()[$xacNhan])) $func->transfer('Cách xử lý khi vượt '.lo_gio_canh_bao().' giờ không hợp lệ.', $back, false);
	if(!ctype_digit($soGio) || (int)$soGio < 1 || (int)$soGio > 500) $func->transfer('Số giờ đăng ký phải là số nguyên từ 1 đến 500.', $back, false);
	if(!in_array($hang, lo_hang_list(), true)) $func->transfer('Vui lòng chọn hạng xe.', $back, false);
	if($uuNgay === false || $uuBd === false || $uuKt === false) $func->transfer('Ưu tiên ngày/giờ không hợp lệ.', $back, false);
	if($uuBd !== null && $uuKt !== null && $uuBd >= $uuKt) $func->transfer('Khung giờ ưu tiên: giờ kết thúc phải sau giờ bắt đầu.', $back, false);
	if($uuBd === null && $uuKt !== null) $uuBd = 0;
	$data = array(
		'hang' => $hang, 'so_gio' => (int)$soGio, 'ghi_chu' => lo_post_str('ghi_chu', 1000),
		'uu_tien_ngay' => $uuNgay ?: null, 'uu_tien_gio_bd' => $uuBd, 'uu_tien_gio_kt' => $uuKt,
		'admin_ghi_chu' => lo_post_str('admin_ghi_chu', 500), 'ngaycapnhat' => time(),
	);
	if($id)
	{
		lo_lock_dot($idDot);
		$row = $d->rawQueryOne('select * from #_dt_lo_dangky where id = ? and id_dot = ? limit 0,1', array($id, $idDot));
		if(!$row) lo_rollback_transfer('Đăng ký không tồn tại.', $back);
		$dup = $d->rawQueryOne('select id from #_dt_lo_dangky where id_dot = ? and id_gv = ? and hang = ? and id <> ? limit 0,1', array($idDot, $row['id_gv'], $hang, $id));
		if($dup) lo_rollback_transfer('Giáo viên đã có đăng ký hạng '.$hang.' trong đợt này; hãy sửa đăng ký đó.', $back);
		$xep = $d->rawQueryOne('select count(*) as n from #_dt_lo_lich where id_dangky = ?', array($id));
		if($hang !== $row['hang'] && (int)$xep['n'] > 0) lo_rollback_transfer('Không thể đổi hạng khi đã xếp '.(int)$xep['n'].' giờ. Hãy bỏ các giờ đã xếp trước.', $back);
		$d->where('id', $id);
		$d->update('dt_lo_dangky', $data);
		if($tenLich !== '' && $tenLich !== $row['ten_lich'])
			$d->rawQuery('update #_dt_lo_dangky set ten_lich = ? where id_dot = ? and id_gv = ?', array($tenLich, $idDot, $row['id_gv']));
		if((int)$row['id_gv'] < 0)
		{
			$ext = lo_post_external_gv($back, true);
			$d->rawQuery('update #_dt_lo_dangky set gv_hoten = ?, gv_cccd = ?, gv_sdt = ? where id_dot = ? and id_gv = ?', array($ext['hoten'], $ext['cccd'], $ext['sdt'], $idDot, $row['id_gv']));
		}
		lo_set_xac_nhan($idDot, $row['id_gv'], $xacNhan);
		lo_commit_or_fail($back, 'Không thể lưu đăng ký do lỗi cơ sở dữ liệu.');
		lo_touch_dot($idDot);
		dt_audit('update', 'lichon_dk', 1, 'id='.$id.' gv='.$row['gv_hoten'].' hang='.$hang.' so_gio='.$soGio.($xacNhan !== '' ? ' xac_nhan='.$xacNhan : ''));
		$warn = (int)$xep['n'] > (int)$soGio ? ' Lưu ý: đã xếp '.(int)$xep['n'].' giờ, nhiều hơn số giờ đăng ký.' : '';
		$func->transfer('Đã cập nhật đăng ký của '.$row['gv_hoten'].'.'.$warn.lo_over_note($idDot, $row['id_gv']), $back);
	}
	if(lo_post_str('gv_ngoai', 1) === '1')
	{
		$ext = lo_post_external_gv($back, false);
		$gv = $ext['cccd'] !== '' ? $d->rawQueryOne('select id, hoten, cccd from #_dt_giaovien where cccd = ? limit 0,1', array($ext['cccd'])) : null;
		if($gv) $idGv = (int)$gv['id'];
		else
		{
			lo_lock_dot($idDot);
			$idGv = lo_external_gv_id($idDot, $ext['hoten'], $ext['cccd']);
			$gv = array('id' => $idGv, 'hoten' => $ext['hoten'], 'cccd' => $ext['cccd'], 'sdt' => $ext['sdt']);
		}
	}
	else
	{
		$idGv = (int)($_POST['id_gv'] ?? 0);
		$gv = $idGv ? $d->rawQueryOne('select id, hoten, cccd from #_dt_giaovien where id = ? limit 0,1', array($idGv)) : null;
		if(!$gv) $func->transfer('Vui lòng chọn giáo viên, hoặc chọn "GV ngoài hệ thống" để nhập thông tin.', $back, false);
	}
	if($idGv > 0) lo_lock_dot($idDot);
	$dup = $d->rawQueryOne('select id from #_dt_lo_dangky where id_dot = ? and id_gv = ? and hang = ? limit 0,1', array($idDot, $idGv, $hang));
	if($dup) lo_rollback_transfer($gv['hoten'].' đã đăng ký hạng '.$hang.' trong đợt này; hãy sửa đăng ký đó.', lo_url($idDot, 'dangky', array('edit_dk' => $dup['id'])).'#lo-dk-form');
	$data += array(
		'id_dot' => $idDot, 'id_gv' => $idGv, 'gv_hoten' => $gv['hoten'], 'gv_cccd' => $gv['cccd'], 'gv_sdt' => $gv['sdt'] ?? '',
		'ten_lich' => $tenLich !== '' ? $tenLich : lo_pick_ten_lich($idDot, $idGv, $gv['hoten']),
		'nguon' => 'admin', 'ngaytao' => time(),
	);
	if(!lo_insert_dangky($data)) lo_rollback_transfer('Không thể lưu đăng ký do lỗi cơ sở dữ liệu.', $back);
	if($tenLich !== '') $d->rawQuery('update #_dt_lo_dangky set ten_lich = ? where id_dot = ? and id_gv = ?', array($tenLich, $idDot, $idGv));
	lo_set_xac_nhan($idDot, $idGv, $xacNhan);
	lo_commit_or_fail($back, 'Không thể lưu đăng ký do lỗi cơ sở dữ liệu.');
	lo_touch_dot($idDot);
	dt_audit('create', 'lichon_dk', 1, 'id_dot='.$idDot.' gv='.$gv['hoten'].($idGv < 0 ? ' (ngoai he thong)' : '').' hang='.$hang.' so_gio='.$soGio.($xacNhan !== '' ? ' xac_nhan='.$xacNhan : ''));
	$func->transfer('Đã thêm đăng ký '.$soGio.' giờ '.$hang.' cho '.$gv['hoten'].($idGv < 0 ? ' (GV ngoài hệ thống)' : '').'.'.lo_over_note($idDot, $idGv), $back);
}

/* Thông tin GV ngoài hệ thống từ form nhập hộ. */
function lo_post_external_gv($back, $inTransaction)
{
	$hoten = preg_replace('/\s+/u', ' ', lo_post_str('gv_hoten_ngoai', 255));
	$cccd = preg_replace('/\s+/', '', lo_post_str('gv_cccd_ngoai', 20));
	$sdt = preg_replace('/[\s.\-]+/', '', lo_post_str('gv_sdt_ngoai', 20));
	$err = '';
	if(mb_strlen($hoten) < 2) $err = 'Vui lòng nhập họ tên giáo viên ngoài hệ thống.';
	elseif($cccd !== '' && !preg_match('/^\d{9,12}$/', $cccd)) $err = 'CCCD phải gồm 9–12 chữ số.';
	elseif($sdt !== '' && !preg_match('/^\+?\d{8,15}$/', $sdt)) $err = 'Số điện thoại không hợp lệ.';
	if($err !== '')
	{
		if($inTransaction) lo_rollback_transfer($err, $back);
		global $func;
		$func->transfer($err, $back, false);
	}
	return array('hoten' => $hoten, 'cccd' => $cccd, 'sdt' => $sdt);
}

/* Nhắc bộ phận khi tổng giờ của GV vượt ngưỡng mà chưa chọn cách xử lý. */
function lo_over_note($idDot, $idGv)
{
	global $d;
	$total = lo_gv_total_hours($idDot, $idGv);
	if($total <= lo_gio_canh_bao()) return '';
	$row = $d->rawQueryOne('select xac_nhan from #_dt_lo_dangky where id_dot = ? and id_gv = ? limit 0,1', array((int)$idDot, (int)$idGv));
	$list = lo_xac_nhan_list();
	$xn = $row ? (string)$row['xac_nhan'] : '';
	return ' Tổng '.$total.' giờ (> '.lo_gio_canh_bao().'h): '.(isset($list[$xn]) ? $list[$xn]['label'] : 'chưa chọn cách xử lý (song song 2 xe / 2 ngày)').'.';
}

function dt_lichon_dk_delete()
{
	global $d, $func;
	lo_ensure_tables();
	$idDot = (int)($_POST['id_dot'] ?? 0);
	$back = lo_url($idDot, 'dangky');
	lo_require_post($back);
	$id = (int)($_POST['id'] ?? 0);
	lo_lock_dot($idDot);
	$row = $d->rawQueryOne('select * from #_dt_lo_dangky where id = ? and id_dot = ? limit 0,1', array($id, $idDot));
	if(!$row) lo_rollback_transfer('Đăng ký không tồn tại.', $back);
	$d->rawQuery('delete from #_dt_lo_lich where id_dangky = ?', array($id));
	$d->rawQuery('delete from #_dt_lo_dangky where id = ?', array($id));
	lo_set_xac_nhan($idDot, $row['id_gv'], $row['xac_nhan'] ?? '');
	lo_commit_or_fail($back, 'Không thể xóa đăng ký do lỗi cơ sở dữ liệu.');
	lo_touch_dot($idDot);
	dt_audit('delete', 'lichon_dk', 1, 'id='.$id.' gv='.$row['gv_hoten'].' hang='.$row['hang']);
	$func->transfer('Đã xóa đăng ký '.$row['hang'].' của '.$row['gv_hoten'].'.', $back);
}

/* ---------- Xếp lịch ---------- */
function dt_lichon_auto()
{
	global $d, $func;
	lo_ensure_tables();
	$idDot = (int)($_POST['id_dot'] ?? 0);
	$back = lo_url($idDot, 'xeplich');
	lo_require_post($back);
	$dot = lo_load_dot_or_fail($idDot);
	$mode = lo_post_str('mode', 10) === 'reset' ? 'reset' : 'fill';
	lo_lock_dot($idDot);
	$configs = lo_ngay_rows($idDot);
	if(!$configs) lo_rollback_transfer('Chưa có ngày tập để xếp lịch.', lo_url($idDot, 'cauhinh'));
	$regs = $d->rawQuery('select * from #_dt_lo_dangky where id_dot = ?', array($idDot));
	if(!$regs) lo_rollback_transfer('Chưa có giáo viên đăng ký giờ.', lo_url($idDot, 'dangky'));
	if($mode === 'reset') $d->rawQuery("delete from #_dt_lo_lich where id_dot = ? and nguon = 'auto'", array($idDot));
	$slots = $d->rawQuery('select id_ngay, so_xe, gio, id_dangky from #_dt_lo_lich where id_dot = ?', array($idDot));
	$res = lo_schedule($configs, $regs, $slots, (int)$dot['max_song_song']);
	$now = time();
	foreach($res['new'] as $s)
		$d->insert('dt_lo_lich', array('id_dot' => $idDot, 'id_ngay' => $s['id_ngay'], 'so_xe' => $s['so_xe'], 'gio' => $s['gio'], 'id_dangky' => $s['id_dangky'], 'nguon' => 'auto', 'ngaytao' => $now));
	if($d->transactionFailed() || $d->commit() === false)
	{
		$d->rollback();
		$func->transfer('Không thể lưu kết quả xếp lịch. Không có thay đổi nào được ghi.', $back, false);
	}
	lo_touch_dot($idDot);
	$gvNew = array();
	foreach($res['new'] as $s) $gvNew[$s['id_dangky']] = true;
	$missing = array_sum($res['unplaced']);
	dt_audit('schedule', 'lichon', count($res['new']), 'id_dot='.$idDot.' mode='.$mode.' xep='.count($res['new']).' thieu='.$missing);
	$msg = 'Đã xếp '.count($res['new']).' giờ cho '.count($gvNew).' đăng ký.';
	$msg .= $missing ? ' Còn '.$missing.' giờ của '.count($res['unplaced']).' đăng ký chưa xếp được — xem cột "Còn thiếu" để điều chỉnh thủ công.' : ' Tất cả giáo viên đã được xếp đủ giờ.';
	$func->transfer($msg, $back.($missing ? '&only_missing=1' : ''), true);
}

function dt_lichon_clear()
{
	global $d, $func;
	lo_ensure_tables();
	$idDot = (int)($_POST['id_dot'] ?? 0);
	$back = lo_url($idDot, 'xeplich');
	lo_require_post($back);
	lo_load_dot_or_fail($idDot);
	$mode = lo_post_str('mode', 10);
	if(!in_array($mode, array('auto', 'all', 'gv'), true)) $func->transfer('Kiểu xóa lịch không hợp lệ.', $back, false);
	$hang = lo_post_str('hang', 30);
	if($hang !== '') $back = lo_url($idDot, 'xeplich', array('hang' => $hang));
	$where = 'id_dot = ?';
	$params = array($idDot);
	$names = array();
	if($mode === 'auto') $where .= " and nguon = 'auto'";
	elseif($mode === 'gv')
	{
		$ids = array();
		foreach((array)($_POST['dk'] ?? array()) as $v) if(is_scalar($v) && ctype_digit((string)$v)) $ids[(int)$v] = true;
		$ids = array_keys($ids);
		if(!$ids) $func->transfer('Vui lòng tích chọn giáo viên ở bảng bên phải trước khi xóa lịch.', $back, false);
		$marks = implode(',', array_fill(0, count($ids), '?'));
		foreach($d->rawQuery("select ten_lich from #_dt_lo_dangky where id_dot = ? and id in ($marks)", array_merge(array($idDot), $ids)) as $r) $names[] = $r['ten_lich'];
		$where .= " and id_dangky in ($marks)";
		$params = array_merge($params, $ids);
	}
	lo_lock_dot($idDot);
	$cnt = $d->rawQueryOne('select count(*) as n from #_dt_lo_lich where '.$where, $params);
	$d->rawQuery('delete from #_dt_lo_lich where '.$where, $params);
	lo_commit_or_fail($back, 'Không thể xóa lịch do lỗi cơ sở dữ liệu.');
	lo_touch_dot($idDot);
	dt_audit('clear', 'lichon', (int)$cnt['n'], 'id_dot='.$idDot.' mode='.$mode.($names ? ' gv='.implode(', ', $names) : ''));
	$scope = array('auto' => 'do hệ thống tự xếp (giữ nguyên các ô xếp tay)', 'all' => 'trên toàn bộ lịch', 'gv' => 'của '.count($names).' giáo viên đã chọn ('.implode(', ', $names).')');
	$func->transfer('Đã xóa '.(int)$cnt['n'].' giờ '.$scope[$mode].'. Số giờ được trả lại cho giáo viên.', $back);
}

function lo_json($data)
{
	if(ob_get_length()) ob_end_clean();
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store');
	echo json_encode($data, JSON_UNESCAPED_UNICODE);
	exit;
}

/* Xếp/bỏ 1 ô trên lưới (AJAX). id_dangky = 0 để bỏ ô. */
function dt_lichon_cell()
{
	global $d;
	lo_ensure_tables();
	if(strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') lo_json(array('ok' => false, 'msg' => 'Yêu cầu không hợp lệ.'));
	$idDot = (int)($_POST['id_dot'] ?? 0);
	$idNgay = (int)($_POST['id_ngay'] ?? 0);
	$xe = lo_post_str('so_xe', 20);
	$gio = (int)($_POST['gio'] ?? -1);
	$idDk = (int)($_POST['id_dangky'] ?? 0);
	$cfg = $d->rawQueryOne('select * from #_dt_lo_ngay where id = ? and id_dot = ? limit 0,1', array($idNgay, $idDot));
	if(!$cfg) lo_json(array('ok' => false, 'msg' => 'Ngày tập không tồn tại.'));
	if(!in_array($xe, lo_xe_array($cfg['xe_list']), true) || $gio < (int)$cfg['gio_bd'] || $gio >= (int)$cfg['gio_kt'])
		lo_json(array('ok' => false, 'msg' => 'Ô lịch nằm ngoài cấu hình xe/khung giờ.'));

	$d->startTransaction();
	$d->rawQuery('select id from #_dt_lo_dot where id = ? for update', array($idDot));
	$old = $d->rawQueryOne('select * from #_dt_lo_lich where id_ngay = ? and so_xe = ? and gio = ? limit 0,1', array($idNgay, $xe, $gio));
	$changed = array();
	if($old) $changed[(int)$old['id_dangky']] = true;
	$warn = '';
	if($idDk)
	{
		$dk = $d->rawQueryOne('select * from #_dt_lo_dangky where id = ? and id_dot = ? limit 0,1', array($idDk, $idDot));
		if(!$dk) { $d->rollback(); lo_json(array('ok' => false, 'msg' => 'Đăng ký không tồn tại.')); }
		if($dk['hang'] !== $cfg['hang']) { $d->rollback(); lo_json(array('ok' => false, 'msg' => $dk['ten_lich'].' đăng ký hạng '.$dk['hang'].', không thể xếp vào xe hạng '.$cfg['hang'].'.')); }
		if($old && (int)$old['id_dangky'] === $idDk) { $d->rollback(); lo_json(array('ok' => true, 'noop' => true)); }
		$xep = $d->rawQueryOne('select count(*) as n from #_dt_lo_lich where id_dangky = ?', array($idDk));
		if((int)$xep['n'] >= (int)$dk['so_gio']) { $d->rollback(); lo_json(array('ok' => false, 'msg' => $dk['ten_lich'].' đã được xếp đủ '.(int)$dk['so_gio'].' giờ '.$dk['hang'].'. Tăng số giờ đăng ký nếu cần xếp thêm.')); }
		$same = $d->rawQuery('select l.so_xe, n.hang from #_dt_lo_lich l join #_dt_lo_ngay n on n.id = l.id_ngay join #_dt_lo_dangky dk on dk.id = l.id_dangky
			where l.id_dot = ? and n.ngay = ? and l.gio = ? and dk.id_gv = ? and not (l.id_ngay = ? and l.so_xe = ?)', array($idDot, $cfg['ngay'], $gio, $dk['id_gv'], $idNgay, $xe));
		if($same)
		{
			$xs = array();
			foreach($same as $s) $xs[] = 'xe '.$s['so_xe'].' ('.$s['hang'].')';
			$warn = $dk['ten_lich'].' đang có lịch '.implode(', ', $xs).' cùng giờ '.lo_hour_label($gio).' – xếp song song.';
		}
		if($old) $d->rawQuery("update #_dt_lo_lich set id_dangky = ?, nguon = 'tay', ngaytao = ? where id = ?", array($idDk, time(), $old['id']));
		else $d->insert('dt_lo_lich', array('id_dot' => $idDot, 'id_ngay' => $idNgay, 'so_xe' => $xe, 'gio' => $gio, 'id_dangky' => $idDk, 'nguon' => 'tay', 'ngaytao' => time()));
		$changed[$idDk] = true;
	}
	elseif($old) $d->rawQuery('delete from #_dt_lo_lich where id = ?', array($old['id']));
	if($d->transactionFailed() || $d->commit() === false)
	{
		$d->rollback();
		lo_json(array('ok' => false, 'msg' => 'Không thể lưu ô lịch. Vui lòng thử lại.'));
	}
	lo_touch_dot($idDot);
	$counts = array();
	foreach(array_keys($changed) as $id)
	{
		$c = $d->rawQueryOne('select dk.so_gio, (select count(*) from #_dt_lo_lich l where l.id_dangky = dk.id) as da_xep from #_dt_lo_dangky dk where dk.id = ?', array($id));
		if($c) $counts[$id] = array('so_gio' => (int)$c['so_gio'], 'da_xep' => (int)$c['da_xep']);
	}
	dt_audit($idDk ? 'assign' : 'unassign', 'lichon_o', 1, 'id_dot='.$idDot.' ngay='.$cfg['ngay'].' xe='.$xe.' gio='.$gio.' dk='.$idDk.($old ? ' cu='.$old['id_dangky'] : ''));
	lo_json(array('ok' => true, 'warn' => $warn, 'counts' => $counts));
}

/* ---------- Xuất Excel ---------- */
function dt_lichon_export_dk()
{
	global $d;
	lo_ensure_tables();
	$dot = lo_load_dot_or_fail((int)($_GET['id'] ?? 0));
	$rows = lo_dangky_rows($dot['id']);
	$gvInfo = array();
	foreach($d->rawQuery('select g.id, g.sdt, g.hang_daotao_phep from #_dt_giaovien g join #_dt_lo_dangky dk on dk.id_gv = g.id where dk.id_dot = ?', array($dot['id'])) as $g) $gvInfo[$g['id']] = $g;
	$headers = array('STT', 'Họ tên giáo viên', 'Tên trên lịch', 'CCCD', 'Số điện thoại', 'Hạng xe', 'Số giờ đăng ký', 'Đã xếp', 'Còn thiếu', 'Xác nhận khi > '.lo_gio_canh_bao().'h', 'Ghi chú của GV', 'Ưu tiên ngày', 'Ưu tiên khung giờ', 'Ghi chú bộ phận');
	$xnList = lo_xac_nhan_list();
	$out = array(); $i = 0;
	foreach($rows as $r)
	{
		$win = $r['uu_tien_gio_bd'] !== null ? lo_range_label($r['uu_tien_gio_bd'], $r['uu_tien_gio_kt'] !== null ? $r['uu_tien_gio_kt'] : 24) : '';
		$sdt = (int)$r['id_gv'] < 0 ? ($r['gv_sdt'] ?? '') : ($gvInfo[$r['id_gv']]['sdt'] ?? '');
		$out[] = array(++$i, $r['gv_hoten'].((int)$r['id_gv'] < 0 ? ' (ngoài hệ thống)' : ''), $r['ten_lich'], $r['gv_cccd'], $sdt, $r['hang'], (int)$r['so_gio'], $r['da_xep'], max(0, $r['con_lai']),
			$xnList[$r['xac_nhan'] ?? '']['label'] ?? '', $r['ghi_chu'], $r['uu_tien_ngay'] ? lo_date_vn($r['uu_tien_ngay']) : '', $win, $r['admin_ghi_chu']);
	}
	$file = 'dang-ky-gio-on-'.date('Ymd', strtotime($dot['ngay_sh'])).'.xlsx';
	dt_xlsx_simple('Đăng ký giờ ôn', $file, $headers, $out, array(3, 4));
}

/* Lịch ôn dạng lưới theo mẫu: mỗi hạng 1 sheet, mỗi ngày 1 khối (hàng = số xe, cột = khung giờ). */
function dt_lichon_export_grid()
{
	global $d;
	lo_ensure_tables();
	$dot = lo_load_dot_or_fail((int)($_GET['id'] ?? 0));
	require_once LIBRARIES.'PHPExcel.php';
	$configs = lo_ngay_rows($dot['id']);
	$names = array();
	foreach($d->rawQuery('select id, ten_lich from #_dt_lo_dangky where id_dot = ?', array($dot['id'])) as $r) $names[$r['id']] = $r['ten_lich'];
	$grid = array();
	foreach($d->rawQuery('select id_ngay, so_xe, gio, id_dangky from #_dt_lo_lich where id_dot = ?', array($dot['id'])) as $s)
		$grid[$s['id_ngay']][$s['so_xe']][(int)$s['gio']] = $names[$s['id_dangky']] ?? '';
	$byHang = array();
	foreach($configs as $c) $byHang[$c['hang']][] = $c;
	$onlyHang = isset($_GET['hang']) && is_scalar($_GET['hang']) ? (string)$_GET['hang'] : '';
	if($onlyHang !== '' && isset($byHang[$onlyHang])) $byHang = array($onlyHang => $byHang[$onlyHang]);

	$xls = new PHPExcel();
	$xls->removeSheetByIndex(0);
	$thin = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => '000000'))));
	$center = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, 'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER, 'wrap' => true));
	$col = function($i) { return PHPExcel_Cell::stringFromColumnIndex($i); };
	$used = array();
	foreach($byHang as $hang => $days)
	{
		$sheet = $xls->createSheet();
		$title = preg_replace('/[\\\\\/\?\*\[\]:]/', '', $hang);
		$title = mb_substr($title, 0, 28);
		while(isset($used[$title])) $title .= '_';
		$used[$title] = true;
		$sheet->setTitle($title);
		$maxCols = 1;
		foreach($days as $c) $maxCols = max($maxCols, 1 + $c['so_gio_xe']);
		$last = $col($maxCols - 1);
		$sheet->setCellValue('A1', 'LỊCH ÔN XE THIẾT BỊ HẠNG '.mb_strtoupper($hang).' THI SÁT HẠCH NGÀY '.lo_date_vn($dot['ngay_sh']).($dot['san'] !== '' ? ' ('.mb_strtoupper($dot['san']).')' : ''));
		$sheet->mergeCells('A1:'.$last.'1');
		$sheet->getStyle('A1')->applyFromArray($center);
		$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
		$sheet->getRowDimension(1)->setRowHeight(26);
		$r = 3;
		foreach($days as $c)
		{
			$hours = range($c['gio_bd'], $c['gio_kt'] - 1);
			$lastC = $col(count($hours));
			$sheet->setCellValue('A'.$r, 'Số xe');
			$sheet->mergeCells('A'.$r.':A'.($r + 1));
			$sheet->setCellValue('B'.$r, lo_weekday($c['ngay']).' ngày '.lo_date_vn($c['ngay']));
			$sheet->mergeCells('B'.$r.':'.$lastC.$r);
			$sheet->getStyle('B'.$r)->getFont()->setBold(true)->setSize(12);
			foreach($hours as $i => $h) $sheet->setCellValue($col($i + 1).($r + 1), lo_hour_label($h).' - '.lo_hour_label($h + 1));
			$sheet->getStyle('A'.$r.':'.$lastC.($r + 1))->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2F9');
			$sheet->getStyle('A'.$r.':'.$lastC.($r + 1))->getFont()->setBold(true);
			$row = $r + 2;
			foreach($c['xe'] as $xe)
			{
				$sheet->setCellValueExplicit('A'.$row, (string)$xe, PHPExcel_Cell_DataType::TYPE_STRING);
				$sheet->getStyle('A'.$row)->getFont()->setBold(true);
				foreach($hours as $i => $h) $sheet->setCellValue($col($i + 1).$row, $grid[$c['id']][$xe][$h] ?? '');
				$row++;
			}
			foreach($hours as $i => $h) $sheet->setCellValue($col($i + 1).$row, $i + 1);
			$sheet->getStyle('A'.$r.':'.$lastC.$row)->applyFromArray($thin + $center);
			$sheet->getStyle('B'.$row.':'.$lastC.$row)->getFont()->setItalic(true)->getColor()->setRGB('666666');
			$r = $row + 2;
		}
		$sheet->getColumnDimension('A')->setWidth(10);
		for($i = 1; $i < $maxCols; $i++) $sheet->getColumnDimension($col($i))->setWidth(11);
		$sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1)->setFitToHeight(0);
	}
	if(!$byHang)
	{
		$sheet = $xls->createSheet();
		$sheet->setTitle('Lịch ôn');
		$sheet->setCellValue('A1', 'Chưa có ngày tập nào được cấu hình.');
	}
	$xls->setActiveSheetIndex(0);
	if(ob_get_length()) ob_end_clean();
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment;filename="lich-on-xe-'.date('Ymd', strtotime($dot['ngay_sh'])).'.xlsx"');
	header('Cache-Control: max-age=0');
	PHPExcel_IOFactory::createWriter($xls, 'Excel2007')->save('php://output');
	exit;
}
