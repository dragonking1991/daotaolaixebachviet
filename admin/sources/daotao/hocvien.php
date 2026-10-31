<?php
if(!defined('SOURCES')) die("Error");

function dt_hocvien_current_khoa()
{
	return isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
}

function dt_hocvien_sql($sql,$params=array())
{
	global $d;
	$rows=$d->rawQuery($sql,$params);
	if($d->getLastErrorCode()!=='00000')throw new RuntimeException('Không thể xử lý dữ liệu học viên.');
	return $rows;
}

function dt_hocvien_list()
{
	global $d, $func, $curPage, $items, $paging, $ds_khoa, $id_khoa_sel, $total_items;

	$ds_khoa = dt_khoa_options();
	$id_khoa_sel = dt_hocvien_current_khoa();
	if(empty($_SESSION['dt_hocvien_unassigned_csrf']))$_SESSION['dt_hocvien_unassigned_csrf']=bin2hex(random_bytes(24));

	list($where, $params) = dt_hocvien_where();

	$per_page = 30;
	$startpoint = ($curPage * $per_page) - $per_page;
	$sql = "select h.*, k.ma_khoa, k.ten_khoa from #_dt_hocvien h left join #_dt_khoa k on k.id = h.id_khoa where 1 $where order by substring_index(h.hoten, ' ', -1) asc, h.hoten asc limit $startpoint,$per_page";
	$items = $d->rawQuery($sql, $params);
	foreach($items as &$__it) $__it['sum'] = (int)$__it['id_khoa']>0 ? dt_student_summary($__it) : null;
	unset($__it);
	$count = $d->rawQueryOne("select count(*) as num from #_dt_hocvien h where 1 $where", $params);
	$total_items = (int)$count['num'];
	$query=array('com'=>'daotao','act'=>'hocvien');
	foreach(array('id_khoa','keyword','hang','gv','he') as $key)if(isset($_REQUEST[$key])&&trim((string)$_REQUEST[$key])!=='')$query[$key]=trim((string)$_REQUEST[$key]);
	$url = 'index.php?'.http_build_query($query);
	$paging = $func->pagination($count['num'], $per_page, $curPage, $url);
}

function dt_hocvien_delete_unassigned()
{
	global $d, $func;
	$back='index.php?com=daotao&act=hocvien&id_khoa=-1';
	if(!dt_is_admin())$func->transfer('Chỉ quản trị viên được xóa hàng loạt học viên chưa gán khóa.',$back,false);
	if(empty($_SESSION['dt_hocvien_unassigned_csrf'])||!hash_equals($_SESSION['dt_hocvien_unassigned_csrf'],(string)($_POST['csrf']??'')))$func->transfer('Phiên thao tác không hợp lệ; vui lòng tải lại trang.',$back,false);
	$lock=dt_hocvien_sql('select get_lock(?,10) as acquired',array('bachviet_sh_summary_import'));
	if((int)($lock[0]['acquired']??0)!==1)$func->transfer('Đang có lượt import kết quả sát hạch; vui lòng thử lại.',$back,false);
	try
	{
		$financial=dt_hocvien_sql('select count(*) as num from #_dt_taichinh t inner join #_dt_hocvien h on h.id=t.id_hocvien where h.id_khoa=0');
		if((int)$financial[0]['num']>0)$func->transfer('Có hồ sơ chưa gán khóa còn giao dịch tài chính; không xóa để giữ lịch sử tiền.',$back,false);
		$students=dt_hocvien_sql('select id from #_dt_hocvien where id_khoa=0 order by id');
		if(!$students)$func->transfer('Hiện không có học viên chưa gán khóa.',$back,false);
		if(!dt_backup_tables(array('dt_hocvien'),'del_unassigned_hocvien'))$func->transfer('Không sao lưu được dữ liệu; chưa xóa học viên.',$back,false);
		$d->startTransaction();
		try
		{
			$students=dt_hocvien_sql('select id from #_dt_hocvien where id_khoa=0 order by id for update');
			$ids=array_map(function($row){return (int)$row['id'];},$students);$deleted=count($ids);
			foreach(array_chunk($ids,500) as $chunk)
			{
				$marks=implode(',',array_fill(0,count($chunk),'?'));
				dt_hocvien_sql('delete from #_dt_hocvien where id_khoa=0 and id in ('.$marks.')',$chunk);
			}
			if($d->commit()===false)throw new RuntimeException('Không thể hoàn tất xóa học viên.');
		}
		catch(Exception $error){$d->rollback();throw $error;}
		dt_audit('delete','hocvien_unassigned',$deleted,'exam results and source records retained');
		$func->transfer('Đã xóa '.$deleted.' hồ sơ chưa gán khóa; kết quả sát hạch và dữ liệu nguồn được giữ nguyên.',$back);
	}
	finally{$d->rawQuery("select release_lock('bachviet_sh_summary_import')");}
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
		'hoc_phi_da_dong' => array('sotiendadong','tiendadong','hocphidadong'),
		'hoc_phi_con_thieu' => array('sotienconthieu','tienconthieu','hocphiconthieu'),
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
	$syncKhoaIds = array();
	if(!$preview)
	{
		if(!dt_backup_tables(array('dt_hocvien','dt_khoa'), 'imp_hocvien'))dt_import_notice('Không thể sao lưu trước khi nhập học viên.',$back,false);
		$d->startTransaction();
	}
	$ok = 0; $err = 0; $errMsgs = array(); $emptyStreak = 0; $writeFailRows = array(); $previewKhoaIds = array();
	for($r = $headerRow + 1; $r <= $highestRow; $r++)
	{
		$row = dt_read_row($sheet, $r, $highestCol);
		$maHv = dt_val($row, $map, 'ma_hv');
		$cccdRaw = dt_val($row, $map, 'cccd');
		$hoten = dt_val($row, $map, 'hoten');
		$khoaCode = dt_val($row, $map, 'khoa');
		$hangRow = dt_val($row, $map, 'hang');

		if($maHv === '' && $cccdRaw === '' && $hoten === '' && $khoaCode === '') { continue; }
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
		try
		{
			foreach(array('hoc_phi_da_dong','hoc_phi_con_thieu') as $field)
				if(isset($map[$field])) $data[$field] = dt_finance_import_amount(dt_val($row, $map, $field));
		}
		catch(InvalidArgumentException $error)
		{
			$err++; if(count($errMsgs) < 12) $errMsgs[] = 'Dòng '.$r.': '.$error->getMessage();
			continue;
		}
		if($preview) $data['khoa'] = $khoaCode !== '' ? $khoaCode : (string)$idKhoaSel;

		if($preview)
		{
			/* Cùng quy tắc với import thật: khóa theo cột Khóa (tra mã khóa, chưa tạo) hoặc khóa đã chọn, rồi tìm theo (khóa, CCCD). */
			$previewKhoaId = 0;
			if($hasKhoaCol && $khoaCode !== '')
			{
				$codeKey = dt_mb_lower(trim(preg_replace('/\s+/', ' ', $khoaCode)));
				if(!array_key_exists($codeKey, $previewKhoaIds))
				{
					$khoaRow = $d->rawQueryOne("select id from #_dt_khoa where ma_khoa = ? limit 0,1", array(trim(preg_replace('/\s+/', ' ', $khoaCode))));
					$previewKhoaIds[$codeKey] = $khoaRow ? (int)$khoaRow['id'] : 0;
				}
				$previewKhoaId = $previewKhoaIds[$codeKey];
				if(!$previewKhoaId) $data['khoa'] = $khoaCode.' (khóa mới)';
			}
			else $previewKhoaId = (int)$idKhoaSel;
			$exist = $previewKhoaId ? $d->rawQueryOne("select id from #_dt_hocvien where id_khoa = ? and cccd = ? limit 0,1", array($previewKhoaId, $cccd)) : null;
			$isUpdate = ($exist && $exist['id']);
			$isUpdate ? $updCnt++ : $insCnt++;
			$previewRows[] = array('r'=>$r, 'action'=>$isUpdate?'update':'insert', 'data'=>$data);
			$ok++;
			continue;
		}

		$exist = $d->rawQueryOne("select id from #_dt_hocvien where id_khoa = ? and cccd = ? limit 0,1", array($idKhoa, $cccd));
		if($exist && $exist['id']) { $d->where('id', $exist['id']); $written = $d->update('dt_hocvien', $data); }
		else { $data['ngaytao'] = time(); $written = $d->insert('dt_hocvien', $data); }
		if($written === false || $d->getLastErrorCode() !== '00000')
		{
			$err++;
			$writeFailRows[] = $r;
			if(count($errMsgs) < 12) $errMsgs[] = "Dòng $r: lỗi ghi cơ sở dữ liệu";
			continue;
		}
		$ok++;
		$syncKhoaIds[(int)$idKhoa] = (int)$idKhoa;
	}

	if($preview)
	{
		global $template, $dtPreview;
		$dtPreview = array(
			'title' => 'Học viên', 'back' => 'hocvien',
			'cols' => array('ma_hv'=>'Mã HV','hoten'=>'Họ và tên','cccd'=>'CCCD','ngaysinh'=>'Ngày sinh','hang'=>'Hạng','khoa'=>'Khóa','gv_hoten'=>'Giáo viên','nguoi_gioithieu'=>'Người GT'),
			'rows' => $previewRows, 'insert' => $insCnt, 'update' => $updCnt, 'err' => $err, 'errMsgs' => $errMsgs,
			'reupload' => 'index.php?com=daotao&act=uploadHocvien'.($idKhoaSel ? '&id_khoa='.$idKhoaSel : ''),
			'submit' => 'index.php?com=daotao&act=uploadHocvienExcel', 'hidden' => array(),
			'staged_token' => ik_stage_upload('daotao.hocvien'), 'filename' => $file['name'],
			'mapping' => dt_preview_mapping($sheet, $headerRow, $highestCol, $map, array('ma_hv' => 'Mã đăng ký *', 'cccd' => 'Số CCCD *', 'hoten' => 'Họ và tên', 'ngaysinh' => 'Ngày sinh', 'hang' => 'Hạng', 'khoa' => 'Khóa', 'giaovien' => 'Giáo viên', 'nguoi_gioithieu' => 'Người nộp hồ sơ', 'he_daotao' => 'Hệ đào tạo', 'hoc_phi_da_dong' => 'Số tiền đã đóng', 'hoc_phi_con_thieu' => 'Số tiền còn thiếu')),
			'note' => (!$hasKhoaCol && $idKhoaSel) ? 'Khóa lấy theo lựa chọn trên form.' : 'Khóa lấy theo cột Khóa trong file; mã khóa chưa có sẽ được tạo mới khi import.',
		);
		$template = 'daotao/import_preview';
		return;
	}

	if($writeFailRows || $d->transactionFailed()) { $d->rollback(); dt_import_notice('Import thất bại khi ghi cơ sở dữ liệu'.($writeFailRows ? ' ở dòng '.implode(', ', array_slice($writeFailRows, 0, 15)) : '').'; đã hoàn tác toàn bộ file, chưa lưu dòng nào. Vui lòng kiểm tra dữ liệu (độ dài, định dạng) rồi thử lại.', $back, false); }
	$d->commit();
	$syncTotals = array('xd_added' => 0, 'ce_added' => 0, 'existing' => 0);
	$syncErrors = array();
	foreach($syncKhoaIds as $syncKhoaId)
	{
		$syncResult = xd_sync_training_students($syncKhoaId);
		foreach($syncTotals as $key => $count) $syncTotals[$key] += (int)($syncResult[$key] ?? 0);
		if(empty($syncResult['ok'])) $syncErrors = array_merge($syncErrors, $syncResult['errors'] ?? array($syncResult['error'] ?? 'Không thể đồng bộ học viên.'));
	}
	dt_audit('import', 'hocvien', $ok, $file['name'].($hasKhoaCol ? ' (khóa theo file)' : ' (khóa '.$idKhoaSel.')'));
	dt_log_import('hocvien', $file['name'], $ok, $err);

	$msg = "Import học viên: $ok dòng thành công".($err ? ", $err dòng lỗi" : "");
	if(!empty($syncKhoaIds))
		$msg .= " Đồng bộ sang XD: thêm ".$syncTotals['xd_added']." học viên đạt DAT (mọi hạng); giữ nguyên ".$syncTotals['existing']." hồ sơ XD đã có.";
	if(!empty($syncErrors)) $msg .= " Có lỗi đồng bộ: ".implode('; ', $syncErrors);
	if(!empty($errMsgs)) $msg .= " — ".implode('; ', $errMsgs);
	dt_import_notice($msg, "index.php?com=daotao&act=hocvien".($idKhoaSel ? "&id_khoa=".$idKhoaSel : ""), ($ok > 0 || $err === 0) && empty($syncErrors));
}
