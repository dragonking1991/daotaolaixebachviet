<?php
if(!defined('SOURCES')) die("Error");

function dt_khoa_list()
{
	global $d, $func, $curPage, $items, $paging;

	$where = "";
	if(isset($_REQUEST['keyword']) && $_REQUEST['keyword'] !== '')
	{
		$kw = $d->escape(htmlspecialchars($_REQUEST['keyword']));
		$where .= " and (k.ma_khoa like '%$kw%' or k.ten_khoa like '%$kw%' or k.hang like '%$kw%')";
	}

	$per_page = 20;
	$startpoint = ($curPage * $per_page) - $per_page;
	$sql = "select k.*, "
		. "(select count(*) from #_dt_hocvien h where h.id_khoa = k.id) as so_hoc_vien "
		. "from #_dt_khoa k where k.hienthi >= 0 $where order by k.ngay_khaigiang desc, k.id desc limit $startpoint,$per_page";
	$items = $d->rawQuery($sql);
	$count = $d->rawQueryOne("select count(*) as num from #_dt_khoa k where k.hienthi >= 0 $where");
	$paging = $func->pagination($count['num'], $per_page, $curPage, "index.php?com=daotao&act=khoa");
}

/* Danh sách tất cả khóa (dùng cho dropdown chọn khóa ở các module). */
function dt_khoa_options()
{
	global $d;
	return $d->rawQuery("select id, ma_khoa, ten_khoa, hang from #_dt_khoa where hienthi = 1 order by ngay_khaigiang desc, id desc");
}

function dt_khoa_form()
{
	global $d, $func, $item;
	$item = array();
	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	if($id)
	{
		$item = $d->rawQueryOne("select * from #_dt_khoa where id = ? limit 0,1", array($id));
		if(!$item || !$item['id']) $func->transfer("Dữ liệu không có thực", "index.php?com=daotao&act=khoa", false);
	}
}

function dt_khoa_save()
{
	global $d, $func, $login_admin;

	if(empty($_POST['data'])) $func->transfer("Không nhận được dữ liệu", "index.php?com=daotao&act=khoa", false);
	$p = $_POST['data'];

	$data = array(
		'ma_khoa' => trim(htmlspecialchars($p['ma_khoa'] ?? '')),
		'ten_khoa' => trim(htmlspecialchars($p['ten_khoa'] ?? '')),
		'hang' => dt_norm_hang($p['hang'] ?? ''),
		'ngay_khaigiang' => trim($p['ngay_khaigiang'] ?? ''),
		'ngay_manhoa' => trim($p['ngay_manhoa'] ?? ''),
		'he_daotao' => trim(htmlspecialchars($p['he_daotao'] ?? '')),
	);
	if($data['ngay_khaigiang'] === '') $data['ngay_khaigiang'] = null;
	if($data['ngay_manhoa'] === '') $data['ngay_manhoa'] = null;

	if($data['ma_khoa'] === '') $func->transfer("Vui lòng nhập mã khóa học", "index.php?com=daotao&act=khoa", false);

	if($data['ngay_khaigiang'] && $data['ngay_manhoa'] && strtotime($data['ngay_manhoa']) < strtotime($data['ngay_khaigiang']))
		$func->transfer("Ngày mãn khóa phải lớn hơn hoặc bằng ngày khai giảng", "index.php?com=daotao&act=khoa", false);

	$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

	$dup = $d->rawQueryOne("select id from #_dt_khoa where ma_khoa = ? and id <> ? limit 0,1", array($data['ma_khoa'], $id));
	if($dup && $dup['id']) $func->transfer("Mã khóa học đã tồn tại", "index.php?com=daotao&act=khoa", false);

	if($id)
	{
		$d->where('id', $id);
		if($d->update('dt_khoa', $data) !== false) $func->transfer("Cập nhật khóa thành công", "index.php?com=daotao&act=khoa");
		$func->transfer("Cập nhật khóa bị lỗi", "index.php?com=daotao&act=khoa", false);
	}
	else
	{
		$data['ngaytao'] = time();
		$data['user_tao'] = isset($_SESSION[$login_admin]['username']) ? $_SESSION[$login_admin]['username'] : '';
		$data['hienthi'] = 1;
		if($d->insert('dt_khoa', $data)) $func->transfer("Lưu khóa thành công", "index.php?com=daotao&act=khoa");
		$func->transfer("Lưu khóa bị lỗi", "index.php?com=daotao&act=khoa", false);
	}
}

function dt_khoa_delete()
{
	global $d, $func;
	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	if(!$id) $func->transfer("Không nhận được dữ liệu", "index.php?com=daotao&act=khoa", false);

	$d->rawQuery("delete from #_dt_khoa where id = ?", array($id));
	$d->rawQuery("delete from #_dt_hocvien where id_khoa = ?", array($id));
	$d->rawQuery("delete from #_dt_lythuyet where id_khoa = ?", array($id));
	$d->rawQuery("delete from #_dt_cabin_kq where id_khoa = ?", array($id));
	$d->rawQuery("delete from #_dt_dat_phien where id_khoa = ?", array($id));
	$d->rawQuery("delete from #_dt_thuchanh_hinh where id_khoa = ?", array($id));
	if(function_exists('dt_audit')) dt_audit('delete', 'khoa', 1, 'id='.$id.' (+dữ liệu liên quan)');
	$func->transfer("Xóa khóa và dữ liệu liên quan thành công", "index.php?com=daotao&act=khoa");
}

/**
 * Tìm khóa theo mã; nếu chưa có thì tự tạo. Trả về id_khoa (0 nếu mã rỗng).
 * Dùng chung cho các importer để "khóa thiếu thì tự thêm".
 */
function dt_khoa_ensure($maKhoa, $tenKhoa = '', $hang = '')
{
	global $d, $login_admin;
	static $cache = array();
	$maKhoa = trim(preg_replace('/\s+/', ' ', (string)$maKhoa));
	if($maKhoa === '') return 0;
	$key = function_exists('mb_strtolower') ? mb_strtolower($maKhoa, 'UTF-8') : strtolower($maKhoa);
	if(isset($cache[$key])) return $cache[$key];

	$row = $d->rawQueryOne("select id, hang, ten_khoa from #_dt_khoa where ma_khoa = ? limit 0,1", array($maKhoa));
	if($row && $row['id'])
	{
		$upd = array();
		if($hang !== '' && (string)$row['hang'] === '') $upd['hang'] = dt_norm_hang($hang);
		if($tenKhoa !== '' && (string)$row['ten_khoa'] === '') $upd['ten_khoa'] = $tenKhoa;
		if($upd) { $d->where('id', (int)$row['id']); $d->update('dt_khoa', $upd); }
		return $cache[$key] = (int)$row['id'];
	}

	$id = $d->insert('dt_khoa', array(
		'ma_khoa' => $maKhoa,
		'ten_khoa' => $tenKhoa !== '' ? $tenKhoa : $maKhoa,
		'hang' => $hang !== '' ? dt_norm_hang($hang) : '',
		'ngay_khaigiang' => null,
		'ngay_manhoa' => null,
		'ngaytao' => time(),
		'user_tao' => isset($_SESSION[$login_admin]['username']) ? $_SESSION[$login_admin]['username'] : '',
		'hienthi' => 1,
	));
	return $cache[$key] = (int)$id;
}

/* Hạng của khóa theo id (cache). */
function dt_khoa_hang($idKhoa)
{
	global $d;
	static $cache = array();
	$idKhoa = (int)$idKhoa;
	if(!$idKhoa) return '';
	if(isset($cache[$idKhoa])) return $cache[$idKhoa];
	$row = $d->rawQueryOne("select hang from #_dt_khoa where id = ? limit 0,1", array($idKhoa));
	return $cache[$idKhoa] = ($row ? (string)$row['hang'] : '');
}

function dt_khoa_upload_form() { /* template tĩnh */ }

/* Import danh sách khóa từ Excel (mã khóa + tên khóa + hạng + ngày...). */
function dt_khoa_upload_excel()
{
	global $d, $func;

	$back = "index.php?com=daotao&act=uploadKhoa";
	if(!isset($_FILES['file-excel']) || $_FILES['file-excel']['error'] != 0)
		dt_import_notice("Vui lòng chọn file Excel", $back, false);
	$file = $_FILES['file-excel'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

	list($sheet, $highestRow, $highestCol) = dt_open_upload_sheet($file, $ext, $back);

	$aliases = array(
		'ma_khoa' => array('makhoa','makhoahoc','makh','khoa','khoahoc'),
		'ten_khoa' => array('tenkhoa','tenkhoahoc'),
		'hang' => array('hang','hangdaotao'),
		'ngay_khaigiang' => array('ngaykhaigiang','khaigiang','ngaykhaigiangkhoa'),
		'ngay_manhoa' => array('ngaymanhoa','manhoa','ngayketthuc','ngayketthuckhoa'),
		'he_daotao' => array('hedaotao','he'),
	);
	$contains = array(
		'ma_khoa' => array('makhoa','makhoahoc'),
		'ten_khoa' => array('tenkhoa'),
		'hang' => array('hang'),
		'ngay_khaigiang' => array('khaigiang'),
		'ngay_manhoa' => array('manhoa','ketthuc'),
	);

	list($headerRow, $map, $score) = dt_find_header_row($sheet, $highestRow, $highestCol, $aliases, $contains);
	if($score <= 0 || !isset($map['ma_khoa']))
		dt_import_notice("Không nhận diện được cột 'Mã khóa' (hoặc 'Khóa') trong file.", $back, false);

	dt_backup_tables(array('dt_khoa'), 'imp_khoa');
	$d->startTransaction();
	$them = 0; $capnhat = 0; $err = 0; $errMsgs = array(); $emptyStreak = 0;
	for($r = $headerRow + 1; $r <= $highestRow; $r++)
	{
		$row = dt_read_row($sheet, $r, $highestCol);
		$maKhoa = trim(preg_replace('/\s+/', ' ', dt_val($row, $map, 'ma_khoa')));
		if($maKhoa === '') { if(++$emptyStreak >= 30) break; continue; }
		$emptyStreak = 0;

		$tenKhoa = dt_val($row, $map, 'ten_khoa');
		$hang = dt_val($row, $map, 'hang');
		$data = array(
			'ten_khoa' => $tenKhoa !== '' ? $tenKhoa : $maKhoa,
			'hang' => $hang !== '' ? dt_norm_hang($hang) : '',
			'he_daotao' => dt_val($row, $map, 'he_daotao'),
			'ngay_khaigiang' => null,
			'ngay_manhoa' => null,
		);
		$kg = dt_parse_date(dt_val($row, $map, 'ngay_khaigiang'));
		$mh = dt_parse_date(dt_val($row, $map, 'ngay_manhoa'));
		if($kg) $data['ngay_khaigiang'] = $kg;
		if($mh) $data['ngay_manhoa'] = $mh;

		$exist = $d->rawQueryOne("select id from #_dt_khoa where ma_khoa = ? limit 0,1", array($maKhoa));
		if($exist && $exist['id']) { $d->where('id', $exist['id']); $d->update('dt_khoa', $data); $capnhat++; }
		else
		{
			$data['ma_khoa'] = $maKhoa; $data['ngaytao'] = time(); $data['hienthi'] = 1;
			$data['user_tao'] = dt_username();
			$d->insert('dt_khoa', $data); $them++;
		}
	}
	$d->commit();
	dt_audit('import', 'khoa', $them + $capnhat, $file['name']);
	dt_log_import('khoa', $file['name'], $them + $capnhat, $err);
	dt_import_notice("Import khóa: thêm mới $them, cập nhật $capnhat".($err ? ", $err lỗi" : ""), "index.php?com=daotao&act=khoa", $err === 0);
}

