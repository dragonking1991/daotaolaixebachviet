<?php
if(!defined('SOURCES')) die("Error");

function dt_hocvien_current_khoa()
{
	return isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
}

function dt_hocvien_list()
{
	global $d, $func, $curPage, $items, $paging, $ds_khoa, $id_khoa_sel;

	$ds_khoa = dt_khoa_options();
	$id_khoa_sel = dt_hocvien_current_khoa();

	list($where, $params) = dt_hocvien_where();

	$per_page = 30;
	$startpoint = ($curPage * $per_page) - $per_page;
	$sql = "select h.*, k.ma_khoa, k.ten_khoa from #_dt_hocvien h left join #_dt_khoa k on k.id = h.id_khoa where 1 $where order by h.id desc limit $startpoint,$per_page";
	$items = $d->rawQuery($sql, $params);
	foreach($items as &$__it) $__it['sum'] = dt_student_summary($__it);
	unset($__it);
	$count = $d->rawQueryOne("select count(*) as num from #_dt_hocvien h where 1 $where", $params);
	$url = "index.php?com=daotao&act=hocvien".($id_khoa_sel ? "&id_khoa=".$id_khoa_sel : "");
	$paging = $func->pagination($count['num'], $per_page, $curPage, $url);
}

function dt_hocvien_upload_form()
{
	global $ds_khoa, $id_khoa_sel;
	$ds_khoa = dt_khoa_options();
	$id_khoa_sel = dt_hocvien_current_khoa();
}

function dt_hocvien_upload_excel()
{
	global $d, $func;

	$idKhoaSel = dt_hocvien_current_khoa();
	$back = "index.php?com=daotao&act=uploadHocvien".($idKhoaSel ? "&id_khoa=".$idKhoaSel : "");

	if(!isset($_FILES['file-excel']) || $_FILES['file-excel']['error'] != 0)
		dt_import_notice("Vui lòng chọn file Excel", $back, false);
	$file = $_FILES['file-excel'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

	list($sheet, $highestRow, $highestCol) = dt_open_upload_sheet($file, $ext, $back);

	$aliases = array(
		'ma_hv' => array('madangky','mahocvien','mahv','madinhdanh'),
		'cccd' => array('socmndhc','socmnd','socccd','cccd','cmndhc','socmndhochieu'),
		'hoten' => array('hovaten','hoten','hotenhocvien'),
		'ngaysinh' => array('ngaysinh','namsinh','ngaythangnamsinh'),
		'hang' => array('hang','hangdaotao'),
		'khoa' => array('khoa','makhoahoc','makhoa','khoahoc'),
		'giaovien' => array('giaovien','gvhuongdan','gv'),
		'nguoi_gioithieu' => array('nguoinophoso','nguoigioithieu','nguoinop','nguoigt'),
		'he_daotao' => array('hedaotao','nhomdaotao','nhom','he'),
	);
	$contains = array(
		'ma_hv' => array('madangky','mahoc','mahv'),
		'cccd' => array('cmnd','cccd','hochieu'),
		'hoten' => array('hoten','hovaten'),
		'ngaysinh' => array('sinh'),
		'khoa' => array('khoa','makhoa'),
		'giaovien' => array('giaovien'),
		'nguoi_gioithieu' => array('nguoinop','gioithieu'),
	);

	list($headerRow, $map, $score) = dt_find_header_row($sheet, $highestRow, $highestCol, $aliases, $contains);
	if($score <= 0 || !isset($map['cccd']) || !isset($map['ma_hv']))
		dt_import_notice("Không nhận diện được cột 'Mã đăng ký' và 'Số CMND/CCCD' trong file. Vui lòng kiểm tra lại tiêu đề.", $back, false);

	$hasKhoaCol = isset($map['khoa']);
	if(!$hasKhoaCol && !$idKhoaSel)
		dt_import_notice("File không có cột 'Khóa' và bạn chưa chọn khóa. Vui lòng chọn khóa hoặc dùng file có cột Khóa.", $back, false);

	$preview = !empty($_REQUEST['preview']);
	$previewRows = array(); $insCnt = 0; $updCnt = 0;
	if(!$preview) { dt_backup_tables(array('dt_hocvien','dt_khoa'), 'imp_hocvien'); $d->startTransaction(); }
	$ok = 0; $err = 0; $errMsgs = array(); $emptyStreak = 0;
	for($r = $headerRow + 1; $r <= $highestRow; $r++)
	{
		$row = dt_read_row($sheet, $r, $highestCol);
		$maHv = dt_val($row, $map, 'ma_hv');
		$cccdRaw = dt_val($row, $map, 'cccd');
		$hoten = dt_val($row, $map, 'hoten');
		$khoaCode = dt_val($row, $map, 'khoa');
		$hangRow = dt_val($row, $map, 'hang');

		if($maHv === '' && $cccdRaw === '' && $hoten === '' && $khoaCode === '') { if(++$emptyStreak >= 30) break; continue; }
		$emptyStreak = 0;

		$cccd = dt_normalize_cccd($cccdRaw);
		if($maHv === '' || $cccd === '')
		{
			$err++;
			$reason = "Dòng $r: thiếu ".($maHv===''?'mã học viên':'')." ".($cccd===''?'CCCD':'');
			if(count($errMsgs) < 12) $errMsgs[] = $reason;
			if($preview) $previewRows[] = array('r'=>$r, 'action'=>'error', 'data'=>array('ma_hv'=>$maHv, 'cccd'=>$cccd, 'hoten'=>$hoten));
			continue;
		}

		// Xác định khóa: ưu tiên cột Khóa trong file (tự tạo nếu thiếu), else khóa đã chọn
		if($hasKhoaCol && $khoaCode !== '')
			$idKhoa = $preview ? ($khoaCode) : dt_khoa_ensure($khoaCode, $khoaCode, $hangRow);
		else
			$idKhoa = $idKhoaSel;
		if(!$preview && !$idKhoa)
		{
			$err++;
			if(count($errMsgs) < 12) $errMsgs[] = "Dòng $r: thiếu khóa";
			continue;
		}
		$hang = $hangRow !== '' ? dt_norm_hang($hangRow) : (!$preview ? dt_khoa_hang($idKhoa) : '');

		$gvHoten = dt_val($row, $map, 'giaovien');
		$data = array(
			'id_khoa' => $preview ? 0 : $idKhoa,
			'ma_hv' => $maHv,
			'cccd' => $cccd,
			'hoten' => $hoten,
			'ngaysinh' => dt_val($row, $map, 'ngaysinh'),
			'hang' => $hang,
			'gv_hoten' => $gvHoten,
			'gv_key' => dt_gv_key($gvHoten),
			'nguoi_gioithieu' => dt_val($row, $map, 'nguoi_gioithieu'),
			'he_daotao' => dt_val($row, $map, 'he_daotao'),
		);
		if($preview) $data['khoa'] = $khoaCode !== '' ? $khoaCode : (string)$idKhoaSel;

		if($preview)
		{
			$exist = $idKhoaSel ? $d->rawQueryOne("select id from #_dt_hocvien where cccd = ? limit 0,1", array($cccd)) : null;
			$isUpdate = ($exist && $exist['id']);
			$isUpdate ? $updCnt++ : $insCnt++;
			$previewRows[] = array('r'=>$r, 'action'=>$isUpdate?'update':'insert', 'data'=>$data);
			$ok++;
			continue;
		}

		$exist = $d->rawQueryOne("select id from #_dt_hocvien where id_khoa = ? and cccd = ? limit 0,1", array($idKhoa, $cccd));
		if($exist && $exist['id']) { $d->where('id', $exist['id']); $d->update('dt_hocvien', $data); }
		else { $data['ngaytao'] = time(); $d->insert('dt_hocvien', $data); }
		$ok++;
	}

	if($preview)
	{
		global $template, $dtPreview;
		$dtPreview = array(
			'title' => 'Học viên', 'back' => 'hocvien',
			'cols' => array('ma_hv'=>'Mã HV','hoten'=>'Họ và tên','cccd'=>'CCCD','ngaysinh'=>'Ngày sinh','hang'=>'Hạng','khoa'=>'Khóa','gv_hoten'=>'Giáo viên','nguoi_gioithieu'=>'Người GT'),
			'rows' => $previewRows, 'insert' => $insCnt, 'update' => $updCnt, 'err' => $err, 'errMsgs' => $errMsgs,
			'reupload' => 'index.php?com=daotao&act=uploadHocvien'.($idKhoaSel ? '&id_khoa='.$idKhoaSel : ''),
			'submit' => '#', 'hidden' => array(),
		);
		$template = 'daotao/import_preview';
		return;
	}

	$d->commit();
	dt_audit('import', 'hocvien', $ok, $file['name'].($hasKhoaCol ? ' (khóa theo file)' : ' (khóa '.$idKhoaSel.')'));
	dt_log_import('hocvien', $file['name'], $ok, $err);

	$msg = "Import học viên: $ok dòng thành công".($err ? ", $err dòng lỗi" : "");
	if(!empty($errMsgs)) $msg .= " — ".implode('; ', $errMsgs);
	dt_import_notice($msg, "index.php?com=daotao&act=hocvien".($idKhoaSel ? "&id_khoa=".$idKhoaSel : ""), $err === 0);
}
