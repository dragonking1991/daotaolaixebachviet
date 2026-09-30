<?php
if(!defined('SOURCES')) die("Error");

/* Băm mật khẩu giáo viên cho cổng đăng nhập. */
function dt_gv_hash($plain)
{
	return md5('dt_gv_'.$plain.'_bachviet');
}

function dt_giaovien_list()
{
	global $d, $func, $curPage, $items, $paging;

	list($where, $params) = dt_giaovien_where();

	$per_page = 30;
	$startpoint = ($curPage * $per_page) - $per_page;
	$items = $d->rawQuery("select * from #_dt_giaovien where 1 $where order by hoten asc limit $startpoint,$per_page", $params);
	$count = $d->rawQueryOne("select count(*) as num from #_dt_giaovien where 1 $where", $params);
	$paging = $func->pagination($count['num'], $per_page, $curPage, "index.php?com=daotao&act=giaovien");
}

function dt_giaovien_upload_excel()
{
	global $d, $func;

	$back = "index.php?com=daotao&act=uploadGiaovien";
	if(!isset($_FILES['file-excel']) || $_FILES['file-excel']['error'] != 0)
		dt_import_notice("Vui lòng chọn file Excel", $back, false);
	$file = $_FILES['file-excel'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

	list($sheet, $highestRow, $highestCol) = dt_open_upload_sheet($file, $ext, $back, array('giaovien'));

	$aliases = array(
		'ma_csdt' => array('macsdt','macosdt'),
		'hotendem' => array('hotendem'),
		'tengv' => array('tengv','ten'),
		'cccd' => array('socmt','socccd','cccd','socmnd'),
		'ngaysinh' => array('ngaysinh'),
		'gioitinh' => array('gioitinh'),
		'noi_ct' => array('noict'),
		'hinh_thuc_td' => array('hinhthuctuyendung','hinhthuctd'),
		'hang_gplx' => array('hanggplx'),
		'ngay_cap_gplx' => array('ngaycapgplx'),
		'so_qd_gcn' => array('soqdgcn'),
		'ngay_qd_gcn' => array('ngayqdgcn'),
		'loai_hinh_dt' => array('loaihinhdaotao','loaihinhdt'),
		'hang_phep' => array('hangdaotaoduocphepdaotao','hangdaotaoduocphep','hangdaotaophep','hangdt'),
		'ngay_hh_gplx' => array('ngayhhgplx'),
		'noi_cap_gcn' => array('noicapgcn'),
		'trinh_do' => array('trinhdo'),
		'chuyen_mon' => array('chuyenmon'),
		'su_pham' => array('supham'),
		'dia_chi' => array('diachi'),
		'sdt' => array('sodienthoai','sdt','dienthoai'),
		'so_gplx' => array('sogplx'),
		'tuyen_dung' => array('tuyendung'),
		'ghi_chu' => array('ghichu'),
	);
	$contains = array(
		'cccd' => array('cmt','cccd','cmnd'),
		'tengv' => array('tengv'),
		'hang_gplx' => array('gplx'),
		'hang_phep' => array('duocphep','hangdaotao'),
		'sdt' => array('dienthoai'),
		'so_gplx' => array('sogplx'),
		'dia_chi' => array('diachi'),
		'ghi_chu' => array('ghichu'),
	);

	list($headerRow, $map, $score) = dt_find_header_row($sheet, $highestRow, $highestCol, $aliases, $contains);
	if($score <= 0 || !isset($map['cccd']))
		dt_import_notice("Không nhận diện được cột 'SoCMT' (CCCD) trong file.", $back, false);

	$preview = !empty($_REQUEST['preview']);
	$previewRows = array(); $insCnt = 0; $updCnt = 0;
	if(!$preview) { dt_backup_tables(array('dt_giaovien'), 'imp_giaovien'); $d->startTransaction(); }
	$ok = 0; $err = 0; $emptyStreak = 0;
	for($r = $headerRow + 1; $r <= $highestRow; $r++)
	{
		$row = dt_read_row($sheet, $r, $highestCol);
		$cccd = dt_normalize_cccd(dt_val($row, $map, 'cccd'));
		$hoTenDem = dt_val($row, $map, 'hotendem');
		$tenGv = dt_val($row, $map, 'tengv');
		$hoten = trim($hoTenDem.' '.$tenGv);

		if($cccd === '' && $hoten === '') { if(++$emptyStreak >= 30) break; continue; }
		$emptyStreak = 0;
		if($cccd === '') { $err++; continue; }

		$data = array(
			'cccd' => $cccd,
			'hoten' => $hoten,
			'gv_key' => dt_gv_key($hoten),
			'ma_csdt' => dt_val($row, $map, 'ma_csdt'),
			'ngaysinh' => dt_gv_fmt_date(dt_val($row, $map, 'ngaysinh')),
			'gioitinh' => dt_val($row, $map, 'gioitinh'),
			'noi_ct' => dt_val($row, $map, 'noi_ct'),
			'hinh_thuc_td' => dt_val($row, $map, 'hinh_thuc_td'),
			'hang_gplx' => dt_val($row, $map, 'hang_gplx'),
			'so_gplx' => dt_val($row, $map, 'so_gplx'),
			'ngay_cap_gplx' => dt_gv_fmt_date(dt_val($row, $map, 'ngay_cap_gplx')),
			'ngay_hh_gplx' => dt_gv_fmt_date(dt_val($row, $map, 'ngay_hh_gplx')),
			'so_qd_gcn' => dt_val($row, $map, 'so_qd_gcn'),
			'ngay_qd_gcn' => dt_gv_fmt_date(dt_val($row, $map, 'ngay_qd_gcn')),
			'loai_hinh_dt' => dt_val($row, $map, 'loai_hinh_dt'),
			'hang_daotao_phep' => dt_val($row, $map, 'hang_phep'),
			'noi_cap_gcn' => dt_val($row, $map, 'noi_cap_gcn'),
			'trinh_do' => dt_val($row, $map, 'trinh_do'),
			'chuyen_mon' => dt_val($row, $map, 'chuyen_mon'),
			'su_pham' => dt_val($row, $map, 'su_pham'),
			'sdt' => dt_val($row, $map, 'sdt'),
			'dia_chi' => dt_val($row, $map, 'dia_chi'),
			'tuyen_dung' => dt_val($row, $map, 'tuyen_dung'),
			'ghi_chu' => dt_val($row, $map, 'ghi_chu'),
		);

		$exist = $d->rawQueryOne("select id from #_dt_giaovien where cccd = ? limit 0,1", array($cccd));
		$isUpdate = ($exist && $exist['id']);
		if($preview)
		{
			$isUpdate ? $updCnt++ : $insCnt++;
			$previewRows[] = array('r'=>$r, 'action'=>$isUpdate?'update':'insert', 'data'=>$data);
			$ok++;
			continue;
		}
		if($isUpdate) { $d->where('id', $exist['id']); $d->update('dt_giaovien', $data); }
		else { $data['ngaytao'] = time(); $data['matkhau'] = dt_gv_hash($cccd); $d->insert('dt_giaovien', $data); }
		$ok++;
	}

	if($preview)
	{
		global $template, $dtPreview;
		$dtPreview = array(
			'title' => 'Giáo viên', 'back' => 'giaovien',
			'cols' => array('hoten'=>'Họ và tên','cccd'=>'CCCD','ngaysinh'=>'Ngày sinh','hang_gplx'=>'GPLX','hang_daotao_phep'=>'Được phép','so_gplx'=>'Số GPLX','sdt'=>'Điện thoại'),
			'rows' => $previewRows, 'insert' => $insCnt, 'update' => $updCnt, 'err' => $err, 'errMsgs' => array(),
			'reupload' => 'index.php?com=daotao&act=uploadGiaovien',
			'submit' => '#', 'hidden' => array(),
		);
		$template = 'daotao/import_preview';
		return;
	}

	$d->commit();
	dt_audit('import', 'giaovien', $ok, $file['name']);
	dt_log_import('giaovien', $file['name'], $ok, $err);
	dt_import_notice("Import giáo viên: $ok dòng thành công".($err?", $err dòng thiếu CCCD":""), "index.php?com=daotao&act=giaovien", ($ok > 0 || $err === 0));
}

/* Định dạng ngày về dd/mm/yyyy: nhận serial Excel, chuỗi 'ddmmyyyy', hoặc chuỗi có sẵn. */
function dt_gv_fmt_date($v)
{
	$v = trim((string)$v);
	if($v === '') return '';
	if(preg_match('/^\d{8}$/', $v)) return substr($v, 0, 2).'/'.substr($v, 2, 2).'/'.substr($v, 4, 4);
	if(is_numeric($v) && (float)$v > 0)
	{
		require_once LIBRARIES.'PHPExcel.php';
		if(class_exists('PHPExcel_Shared_Date')) return date('d/m/Y', PHPExcel_Shared_Date::ExcelToPHP((float)$v));
	}
	return $v;
}

/* Đặt lại mật khẩu cổng giáo viên về mặc định (= CCCD). */
function dt_giaovien_reset_pass()
{
	global $d, $func;
	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	if(!$id) $func->transfer("Không nhận được dữ liệu", "index.php?com=daotao&act=giaovien", false);
	$gv = $d->rawQueryOne("select cccd from #_dt_giaovien where id = ? limit 0,1", array($id));
	if(!$gv || !$gv['cccd']) $func->transfer("Giáo viên không tồn tại", "index.php?com=daotao&act=giaovien", false);
	$d->where('id', $id);
	$d->update('dt_giaovien', array('matkhau' => dt_gv_hash($gv['cccd'])));
	$func->transfer("Đã đặt lại mật khẩu về mặc định (= CCCD)", "index.php?com=daotao&act=giaovien");
}
