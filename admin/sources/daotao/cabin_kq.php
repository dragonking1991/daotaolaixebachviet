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
	global $d, $func, $curPage, $items, $paging, $ds_khoa, $id_khoa_sel;

	$ds_khoa = dt_khoa_options();
	$id_khoa_sel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;

	$where = ""; $params = array();
	if($id_khoa_sel) { $where .= " and c.id_khoa = ?"; $params[] = $id_khoa_sel; }

	$per_page = 30;
	$startpoint = ($curPage * $per_page) - $per_page;
	$sql = "select c.*, h.hoten, h.cccd as hv_cccd from #_dt_cabin_kq c left join #_dt_hocvien h on (h.id_khoa = c.id_khoa and h.ma_hv = c.ma_hv) where 1 $where order by c.id desc limit $startpoint,$per_page";
	$items = $d->rawQuery($sql, $params);
	$count = $d->rawQueryOne("select count(*) as num from #_dt_cabin_kq c where 1 $where", $params);
	$url = "index.php?com=daotao&act=cabinkq".($id_khoa_sel ? "&id_khoa=".$id_khoa_sel : "");
	$paging = $func->pagination($count['num'], $per_page, $curPage, $url);
}

function dt_cabin_upload_excel()
{
	global $d, $func;

	$idKhoa = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$back = "index.php?com=daotao&act=uploadCabin&id_khoa=".$idKhoa;
	if(!$idKhoa) $func->transfer("Vui lòng chọn khóa học", $back, false);
	if(!isset($_FILES['file-excel']) || $_FILES['file-excel']['error'] != 0)
		$func->transfer("Vui lòng chọn file Excel", $back, false);

	$file = $_FILES['file-excel'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	list($sheet, $highestRow, $highestCol) = dt_open_upload_sheet($file, $ext, $back);

	$aliases = array(
		'ma_hv' => array('mahocvien','mahv'),
		'hoten' => array('hovaten','hoten'),
		'ngaysinh' => array('ngaysinh'),
		'tong_thoigian' => array('tongthoigiandaotao','tongthoigian','tongthoigiandat'),
		'so_noidung' => array('tongsonoidung','sonoidung'),
		'ghi_chu' => array('ghichu','ketqua','danhgia'),
	);
	$contains = array(
		'ma_hv' => array('mahoc','mahv'),
		'hoten' => array('hovaten','hoten'),
		'tong_thoigian' => array('thoigian'),
		'so_noidung' => array('noidung'),
		'ghi_chu' => array('ghichu','ketqua'),
	);

	// Header nằm sâu trong file cabin -> quét tối đa 20 dòng đầu.
	list($headerRow, $map, $score) = dt_find_header_row($sheet, $highestRow, $highestCol, $aliases, $contains, 20);
	if(!isset($map['ma_hv']))
		$func->transfer("Không nhận diện được cột 'Mã học viên' trong file cabin.", $back, false);

	$ok = 0; $err = 0; $errMsgs = array(); $emptyStreak = 0;
	for($r = $headerRow + 1; $r <= $highestRow; $r++)
	{
		$row = dt_read_row($sheet, $r, $highestCol);
		$maHv = dt_val($row, $map, 'ma_hv');
		if($maHv === '') { if(++$emptyStreak >= 40) break; continue; }
		$emptyStreak = 0;

		$hv = dt_find_hocvien_by_mahv($maHv, $idKhoa);
		if(!$hv)
		{
			$err++;
			if(count($errMsgs) < 12) $errMsgs[] = "Dòng $r: mã HV $maHv không thuộc khóa";
			continue;
		}

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

	dt_log_import('cabin', $file['name'], $ok, $err);
	$msg = "Import cabin: $ok học viên".($err ? ", $err lỗi" : "");
	if(!empty($errMsgs)) $msg .= " — ".implode('; ', $errMsgs);
	$func->transfer($msg, "index.php?com=daotao&act=cabinkq&id_khoa=".$idKhoa, $err === 0);
}
