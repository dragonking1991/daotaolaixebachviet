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
	global $d, $func, $ky, $items, $ds_khoa, $total_items, $tracuu_kq, $tracuu_kw;
	$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
	$ky = $d->rawQueryOne("select * from #_dt_ky_thi where id = ? limit 0,1", array($id));
	if(!$ky || !$ky['id']) $func->transfer("Kỳ kiểm tra không tồn tại", "index.php?com=daotao&act=kythi", false);

	$ds_khoa = dt_khoa_options();
	$items = $d->rawQuery("select * from #_dt_ky_thi_hv where id_ky = ? order by hoten asc", array($id));
	$total_items = count($items);

	// Tra cứu học viên đủ điều kiện (chưa thuộc kỳ) để thêm lẻ.
	$tracuu_kq = array(); $tracuu_kw = trim(dt_req('tc'));
	if($tracuu_kw !== '')
	{
		$like = '%'.$tracuu_kw.'%';
		$hvs = $d->rawQuery("select h.*, k.ma_khoa, k.ten_khoa from #_dt_hocvien h left join #_dt_khoa k on k.id = h.id_khoa where (h.cccd like ? or h.hoten like ? or h.ma_hv like ?) order by h.hoten asc limit 0,50", array($like, $like, $like));
		$da = array();
		foreach($d->rawQuery("select cccd from #_dt_ky_thi_hv where id_ky = ?", array($id)) as $r) $da[$r['cccd']] = true;
		foreach($hvs as $hv)
		{
			$s = dt_student_summary($hv);
			$tracuu_kq[] = array('hv' => $hv, 'du_dieu_kien' => $s['du_dieu_kien'], 'da_them' => isset($da[$hv['cccd']]));
		}
	}
}

/* Thêm toàn bộ học viên đủ điều kiện của (các) khóa được chọn vào kỳ. */
function dt_kythi_add_eligible()
{
	global $d, $func;
	$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
	$idKhoa = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$back = "index.php?com=daotao&act=kythiDetail&id=".$id.($idKhoa ? "&id_khoa=".$idKhoa : "");
	if(!$id) $func->transfer("Thiếu kỳ kiểm tra", "index.php?com=daotao&act=kythi", false);
	if(!$idKhoa) $func->transfer("Chọn khóa để thêm học viên", $back, false);

	$ky = $d->rawQueryOne("select id from #_dt_ky_thi where id = ? limit 0,1", array($id));
	if(!$ky) $func->transfer("Kỳ kiểm tra không tồn tại", "index.php?com=daotao&act=kythi", false);

	$hvs = $d->rawQuery("select h.*, k.ma_khoa, k.ten_khoa from #_dt_hocvien h left join #_dt_khoa k on k.id = h.id_khoa where h.id_khoa = ? order by h.hoten asc", array($idKhoa));
	$them = 0; $boQua = 0;
	foreach($hvs as $hv)
	{
		$s = dt_student_summary($hv);
		if(!$s['du_dieu_kien']) { $boQua++; continue; }
		if(dt_kythi_them_hv($id, $hv)) $them++;
	}
	dt_audit('add', 'ky_thi_hv', $them, 'id_ky='.$id.' id_khoa='.$idKhoa);
	$func->transfer("Đã thêm $them học viên đủ điều kiện (bỏ qua $boQua chưa đủ điều kiện)", $back);
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
	$d->insert('dt_ky_thi_hv', array(
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
	return true;
}

/* Xuất Excel theo mẫu TTSH (cột kết quả để trống cho TTSH điền). */
function dt_kythi_export()
{
	global $d, $func;
	$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
	$ky = $d->rawQueryOne("select * from #_dt_ky_thi where id = ? limit 0,1", array($id));
	if(!$ky) $func->transfer("Kỳ kiểm tra không tồn tại", "index.php?com=daotao&act=kythi", false);
	$rows = $d->rawQuery("select * from #_dt_ky_thi_hv where id_ky = ? order by hoten asc", array($id));

	$headers = array('Số TT','Họ và tên','Năm sinh','CCCD','Hạng','Mã khóa','Tên Khóa','Giáo viên',
		'Điểm LT','LT','Điểm MP','MP','Kết quả Tổng','Điểm Hình','Hình','Điểm Đường','Đường','Chữ chi','Điểm Chữ chi','Kết luận');
	$out = array(); $stt = 1;
	foreach($rows as $r)
	{
		$out[] = array($stt++, $r['hoten'], $r['nam_sinh'], $r['cccd'], $r['hang'], $r['ma_khoa'], $r['ten_khoa'], $r['giaovien'],
			$r['diem_lt'], $r['lt'], $r['diem_mp'], $r['mp'], $r['ket_qua_tong'], $r['diem_hinh'], $r['hinh'], $r['diem_duong'], $r['duong'], $r['chu_chi'], $r['diem_chu_chi'], $r['ket_luan']);
	}
	$fn = 'ky-kiem-tra-'.($ky['ngay_kt'] ? str_replace('-', '', $ky['ngay_kt']) : date('Ymd')).'.xlsx';
	dt_xlsx_simple('Ky kiem tra', $fn, $headers, $out, array(2, 3));
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
		'hang' => array('hang'),
		'ma_khoa' => array('makhoa'),
		'ten_khoa' => array('tenkhoa'),
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
		'ket_luan' => array('ketluan'),
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
			if($hoten === '' && ++$emptyStreak >= 30) break;
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
		$exist = $d->rawQueryOne("select id from #_dt_ky_thi_hv where id_ky = ? and cccd = ? limit 0,1", array($id, $cccd));
		if($exist && $exist['id'])
		{
			$d->where('id', $exist['id']); $d->update('dt_ky_thi_hv', $kq);
			$ok++;
		}
		else
		{
			// Khóa cũ: chèn mới để lưu trữ kết quả.
			$hv = dt_find_hocvien_by_cccd($cccd);
			$kq['id_ky'] = $id;
			$kq['id_khoa'] = $hv ? (int)$hv['id_khoa'] : 0;
			$kq['cccd'] = $cccd;
			$kq['ma_hv'] = $hv ? $hv['ma_hv'] : '';
			$kq['hoten'] = $hoten ?: ($hv ? $hv['hoten'] : '');
			$kq['nam_sinh'] = dt_val($row, $map, 'nam_sinh') ?: ($hv ? $hv['ngaysinh'] : '');
			$kq['hang'] = dt_val($row, $map, 'hang') ?: ($hv ? $hv['hang'] : '');
			$kq['ma_khoa'] = dt_val($row, $map, 'ma_khoa');
			$kq['ten_khoa'] = dt_val($row, $map, 'ten_khoa');
			$kq['giaovien'] = dt_val($row, $map, 'giaovien') ?: ($hv ? $hv['gv_hoten'] : '');
			$kq['ngaytao'] = time();
			$d->insert('dt_ky_thi_hv', $kq);
			$moi++;
		}
	}
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
	$items = $d->rawQuery("select h.*, ky.ten as ten_ky, ky.ngay_kt from #_dt_ky_thi_hv h left join #_dt_ky_thi ky on ky.id = h.id_ky where $where order by ky.ngay_kt desc, h.hoten asc limit 0,500", $params);
	$total_items = count($items);
}
