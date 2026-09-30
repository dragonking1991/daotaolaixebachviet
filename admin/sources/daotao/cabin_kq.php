<?php
if(!defined('SOURCES')) die("Error");

function dt_cabin_upload_form()
{
	global $ds_khoa, $id_khoa_sel;
	$ds_khoa = dt_khoa_options();
	$id_khoa_sel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
}

function dt_cabin_list()
{
	global $d, $func, $curPage, $items, $paging, $ds_khoa, $id_khoa_sel, $total_items;

	$ds_khoa = dt_khoa_options();
	$id_khoa_sel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;

	list($where, $params) = dt_cabin_where();

	$per_page = 30;
	$startpoint = ($curPage * $per_page) - $per_page;
	$sql = "select c.*, h.hoten, h.cccd as hv_cccd, h.hang, k.ma_khoa, k.ten_khoa from #_dt_cabin_kq c left join #_dt_hocvien h on (h.id_khoa = c.id_khoa and h.ma_hv = c.ma_hv) left join #_dt_khoa k on k.id = c.id_khoa where 1 $where order by c.id desc limit $startpoint,$per_page";
	$items = $d->rawQuery($sql, $params);
	$count = $d->rawQueryOne("select count(*) as num from #_dt_cabin_kq c where 1 $where", $params);
	$total_items = (int)$count['num'];
	$url = "index.php?com=daotao&act=cabinkq".($id_khoa_sel ? "&id_khoa=".$id_khoa_sel : "");
	$paging = $func->pagination($count['num'], $per_page, $curPage, $url);
}

function dt_cabin_upload_excel()
{
	global $d, $func;

	$idKhoaSel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$back = "index.php?com=daotao&act=uploadCabin".($idKhoaSel ? "&id_khoa=".$idKhoaSel : "");
	if(!isset($_FILES['file-excel']) || $_FILES['file-excel']['error'] != 0)
		dt_import_notice("Vui lòng chọn file Excel", $back, false);

	$file = $_FILES['file-excel'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	list($sheet, $highestRow, $highestCol) = dt_open_upload_sheet($file, $ext, $back);

	$aliases = array(
		'ma_hv' => array('mahocvien','mahv'),
		'hoten' => array('hovaten','hoten'),
		'ngaysinh' => array('ngaysinh'),
		'khoa' => array('khoa','makhoahoc','makhoa'),
		'tong_thoigian' => array('tongthoigiandaotao','tongthoigian','tongthoigiandat'),
		'so_noidung' => array('tongsonoidung','sonoidung'),
		'ghi_chu' => array('ghichu','ketqua','danhgia'),
	);
	$contains = array(
		'ma_hv' => array('mahoc','mahv'),
		'hoten' => array('hovaten','hoten'),
		'khoa' => array('makhoa'),
		'tong_thoigian' => array('thoigian'),
		'so_noidung' => array('noidung'),
		'ghi_chu' => array('ghichu','ketqua'),
	);

	// Header nằm sâu trong file cabin -> quét tối đa 20 dòng đầu.
	list($headerRow, $map, $score) = dt_find_header_row($sheet, $highestRow, $highestCol, $aliases, $contains, 20);
	if(!isset($map['ma_hv']))
		dt_import_notice("Không nhận diện được cột 'Mã học viên' trong file cabin.", $back, false);

	dt_backup_tables(array('dt_cabin_kq'), 'imp_cabin');
	$hasKhoaCol = isset($map['khoa']);
	// Không có cột khóa & chưa chọn khóa -> lấy "Mã khóa học" ở phần tiêu đề file (nếu có)
	$metaKhoaId = 0;
	if(!$hasKhoaCol)
	{
		$meta = dt_extract_meta_khoa($sheet, $headerRow, $highestCol);
		if($meta['khoa'] !== '') $metaKhoaId = dt_khoa_ensure($meta['khoa'], $meta['khoa'], $meta['hang']);
	}
	$d->startTransaction();
	$ok = 0; $err = 0; $errMsgs = array(); $emptyStreak = 0;
	for($r = $headerRow + 1; $r <= $highestRow; $r++)
	{
		$row = dt_read_row($sheet, $r, $highestCol);
		$maHv = dt_val($row, $map, 'ma_hv');
		if($maHv === '') { if(++$emptyStreak >= 40) break; continue; }
		$emptyStreak = 0;

		$khoaCode = dt_val($row, $map, 'khoa');
		$rowKhoaId = ($hasKhoaCol && $khoaCode !== '') ? dt_khoa_ensure($khoaCode, $khoaCode) : 0;
		$hv = dt_find_hocvien_by_mahv($maHv, $idKhoaSel);
		if(!$hv)
		{
			$err++;
			if(count($errMsgs) < 12) $errMsgs[] = "Dòng $r: mã HV $maHv chưa có trong danh sách học viên";
			continue;
		}
		$idKhoa = $rowKhoaId ?: ($metaKhoaId ?: ((int)$hv['id_khoa'] ?: $idKhoaSel));

		$ghiChu = dt_val($row, $map, 'ghi_chu');
		$ghiNorm = dt_norm_header($ghiChu);
		$dat = (strpos($ghiNorm, 'dapung') !== false && strpos($ghiNorm, 'khong') === false) ? 1 : 0;
		$data = array(
			'id_khoa' => $idKhoa,
			'ma_hv' => $maHv,
			'cccd' => $hv['cccd'],
			'tong_thoigian' => dt_val($row, $map, 'tong_thoigian'),
			'so_noidung' => (int)dt_parse_number(dt_val($row, $map, 'so_noidung')),
			'ghi_chu' => $ghiChu,
			'dat' => $dat,
		);

		$exist = $d->rawQueryOne("select id from #_dt_cabin_kq where id_khoa = ? and ma_hv = ? limit 0,1", array($idKhoa, $maHv));
		if($exist && $exist['id']) { $d->where('id', $exist['id']); $d->update('dt_cabin_kq', $data); }
		else { $data['ngaytao'] = time(); $d->insert('dt_cabin_kq', $data); }
		$ok++;
	}

	$d->commit();
	dt_audit('import', 'cabin', $ok, $file['name']);
	dt_log_import('cabin', $file['name'], $ok, $err);
	$msg = "Import cabin: $ok học viên".($err ? ", $err lỗi" : "");
	if(!empty($errMsgs)) $msg .= " — ".implode('; ', $errMsgs);
	dt_import_notice($msg, "index.php?com=daotao&act=cabinkq".($idKhoaSel ? "&id_khoa=".$idKhoaSel : ""), ($ok > 0 || $err === 0));
}
