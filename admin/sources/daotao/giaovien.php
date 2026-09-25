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

	$where = ""; $params = array();
	if(isset($_REQUEST['keyword']) && $_REQUEST['keyword'] !== '')
	{
		$kw = $d->escape(htmlspecialchars($_REQUEST['keyword']));
		$where .= " and (hoten like '%$kw%' or cccd like '%$kw%')";
	}

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
		$func->transfer("Vui lòng chọn file Excel", $back, false);
	$file = $_FILES['file-excel'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

	list($sheet, $highestRow, $highestCol) = dt_open_upload_sheet($file, $ext, $back, array('giaovien'));

	$aliases = array(
		'hotendem' => array('hotendem'),
		'tengv' => array('tengv','ten'),
		'cccd' => array('socmt','socccd','cccd','socmnd'),
		'ngaysinh' => array('ngaysinh'),
		'gioitinh' => array('gioitinh'),
		'hang_gplx' => array('hanggplx'),
		'hang_phep' => array('hangdaotaoduocphep','hangdaotaophep','hangdt'),
		'sdt' => array('sodienthoai','sdt','dienthoai'),
		'dia_chi' => array('diachi'),
	);
	$contains = array(
		'cccd' => array('cmt','cccd','cmnd'),
		'tengv' => array('tengv'),
		'hang_gplx' => array('gplx'),
		'hang_phep' => array('duocphep','hangdaotao'),
		'sdt' => array('dienthoai'),
	);

	list($headerRow, $map, $score) = dt_find_header_row($sheet, $highestRow, $highestCol, $aliases, $contains);
	if($score <= 0 || !isset($map['cccd']))
		$func->transfer("Không nhận diện được cột 'SoCMT' (CCCD) trong file.", $back, false);

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
			'ngaysinh' => dt_val($row, $map, 'ngaysinh'),
			'gioitinh' => dt_val($row, $map, 'gioitinh'),
			'hang_gplx' => dt_val($row, $map, 'hang_gplx'),
			'hang_daotao_phep' => dt_val($row, $map, 'hang_phep'),
			'sdt' => dt_val($row, $map, 'sdt'),
			'dia_chi' => dt_val($row, $map, 'dia_chi'),
		);

		$exist = $d->rawQueryOne("select id from #_dt_giaovien where cccd = ? limit 0,1", array($cccd));
		if($exist && $exist['id']) { $d->where('id', $exist['id']); $d->update('dt_giaovien', $data); }
		else { $data['ngaytao'] = time(); $data['matkhau'] = dt_gv_hash($cccd); $d->insert('dt_giaovien', $data); }
		$ok++;
	}

	dt_log_import('giaovien', $file['name'], $ok, $err);
	$func->transfer("Import giáo viên: $ok dòng thành công".($err?", $err dòng thiếu CCCD":""), "index.php?com=daotao&act=giaovien", $err === 0);
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
