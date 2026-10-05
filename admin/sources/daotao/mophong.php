<?php
if(!defined('SOURCES')) die("Error");

function dt_nguong_mophong()
{
	return 70;
}

function dt_mophong_dat($tienDo)
{
	return (float)$tienDo > dt_nguong_mophong() ? 1 : 0;
}

function dt_mophong_list()
{
	global $d, $func, $items, $ds_khoa, $id_khoa_sel, $total_items, $paging, $curPage;
	$ds_khoa = dt_khoa_options();
	$id_khoa_sel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$items = array();
	$total_items = 0;
	$keyword = dt_req('keyword');
	if(!$id_khoa_sel && $keyword === '') return;

	if($id_khoa_sel) { $where = 'h.id_khoa = ?'; $params = array($id_khoa_sel); }
	else { $where = '1'; $params = array(); }
	if($keyword !== '')
	{
		$e = $d->escape(htmlspecialchars($keyword));
		$where .= " and (h.hoten like '%$e%' or h.cccd like '%$e%' or h.ma_hv like '%$e%')";
	}
	$status = dt_req('trang_thai');
	if($status === 'dat') $where .= ' and m.dat = 1';
	elseif($status === 'chua') $where .= ' and (m.id is null or m.dat = 0)';

	$perPage = 30;
	$start = ($curPage * $perPage) - $perPage;
	$sql = "select h.ma_hv,h.cccd,h.hoten,h.hang,m.id,m.tien_do,m.dat from #_dt_hocvien h left join #_dt_mophong m on (m.id_khoa=h.id_khoa and m.cccd=h.cccd) where $where order by h.hoten asc limit $start,$perPage";
	$items = $d->rawQuery($sql, $params);
	$count = $d->rawQueryOne("select count(*) as num from #_dt_hocvien h left join #_dt_mophong m on (m.id_khoa=h.id_khoa and m.cccd=h.cccd) where $where", $params);
	$total_items = (int)($count ? $count['num'] : 0);
	$paging = $func->pagination($total_items, $perPage, $curPage, 'index.php?com=daotao&act=mophong'.($id_khoa_sel ? '&id_khoa='.$id_khoa_sel : '').($keyword !== '' ? '&keyword='.urlencode($keyword) : ''));
}

function dt_mophong_upload_form()
{
	global $ds_khoa, $id_khoa_sel;
	$ds_khoa = dt_khoa_options();
	$id_khoa_sel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
}

function dt_mophong_upload_excel()
{
	global $d, $func;
	$idKhoaSel = isset($_REQUEST['id_khoa']) ? (int)$_REQUEST['id_khoa'] : 0;
	$back = 'index.php?com=daotao&act=uploadMophong'.($idKhoaSel ? '&id_khoa='.$idKhoaSel : '');
	if(!$idKhoaSel) dt_import_notice('Vui lòng chọn khóa trước khi import mô phỏng', $back, false);
	if(!isset($_FILES['file-excel']) || $_FILES['file-excel']['error'] != 0)
		dt_import_notice('Vui lòng chọn file Excel', $back, false);

	$file = $_FILES['file-excel'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	list($sheet, $highestRow, $highestCol) = dt_open_upload_sheet($file, $ext, $back);
	$aliases = array(
		'cccd' => array('cccd','socccd','socmnd','madangnhap','madangky'),
		'hoten' => array('hovaten','hoten'),
		'tien_do' => array('tiendohoanthanh','tiendo','tiendomophong','diemmophong','diemthi','diem'),
	);
	$contains = array(
		'cccd' => array('cccd','madangnhap','madangky'),
		'tien_do' => array('tiendohoanthanh','tiendomophong','diemmophong','diemthi'),
	);
	list($headerRow, $map, $score) = dt_find_header_row($sheet, $highestRow, $highestCol, $aliases, $contains, 15);
	if(!isset($map['cccd']) || !isset($map['tien_do']))
		dt_import_notice("Không nhận diện được cột CCCD/Mã đăng nhập và Tiến độ hoàn thành trong file mô phỏng.", $back, false);

	dt_backup_tables(array('dt_mophong'), 'imp_mophong');
	$d->startTransaction();
	$ok = 0; $err = 0; $errMsgs = array(); $emptyStreak = 0;
	for($r = $headerRow + 1; $r <= $highestRow; $r++)
	{
		$row = dt_read_row($sheet, $r, $highestCol);
		$cccdRaw = dt_val($row, $map, 'cccd');
		$progressRaw = dt_val($row, $map, 'tien_do');
		if($cccdRaw === '' && $progressRaw === '') { if(++$emptyStreak >= 40) break; continue; }
		$emptyStreak = 0;
		$cccd = dt_normalize_cccd($cccdRaw);
		if(strlen($cccd) < 9 || $progressRaw === '')
		{
			$err++;
			if(count($errMsgs) < 12) $errMsgs[] = "Dòng $r: thiếu CCCD hoặc tiến độ hoàn thành";
			continue;
		}

		$hv = dt_find_hocvien_by_cccd($cccd, $idKhoaSel);
		if(!$hv)
		{
			$err++;
			if(count($errMsgs) < 12) $errMsgs[] = "Dòng $r: CCCD $cccd chưa có trong danh sách học viên";
			continue;
		}
		$progress = dt_mophong_read_progress($sheet, $map['tien_do'], $r, $progressRaw);
		$data = array('id_khoa' => (int)$hv['id_khoa'], 'cccd' => $hv['cccd'], 'tien_do' => $progress, 'dat' => dt_mophong_dat($progress));
		$exist = $d->rawQueryOne('select id from #_dt_mophong where id_khoa = ? and cccd = ? limit 0,1', array($data['id_khoa'], $data['cccd']));
		if($exist && $exist['id']) { $d->where('id', (int)$exist['id']); $d->update('dt_mophong', $data); }
		else { $data['ngaytao'] = time(); $d->insert('dt_mophong', $data); }
		$ok++;
	}
	$d->commit();
	dt_audit('import', 'mophong', $ok, $file['name']);
	dt_log_import('mophong', $file['name'], $ok, $err);
	$msg = 'Import mô phỏng: '.$ok.' học viên'.($err ? ', '.$err.' lỗi' : '').' (Đạt khi tiến độ > '.dt_nguong_mophong().'%)';
	if($errMsgs) $msg .= ' — '.implode('; ', $errMsgs);
	dt_import_notice($msg, 'index.php?com=daotao&act=mophong'.($idKhoaSel ? '&id_khoa='.$idKhoaSel : ''), ($ok > 0 || $err === 0));
}

function dt_mophong_read_progress($sheet, $column, $row, $raw)
{
	$cell = $sheet->getCellByColumnAndRow($column, $row);
	$value = $cell->getValue();
	$format = $cell->getStyle()->getNumberFormat()->getFormatCode();
	if(is_numeric($value) && strpos((string)$format, '%') !== false) return (float)$value * 100;
	return dt_parse_number(str_replace('%', '', (string)$raw));
}

function dt_mophong_export()
{
	global $d, $func;
	$idKhoa = (int)dt_req('id_khoa', 0);
	if(!$idKhoa) $func->transfer('Vui lòng chọn khóa để xuất mô phỏng', 'index.php?com=daotao&act=mophong', false);
	list($where, $params) = dt_mophong_where($idKhoa);
	$rows = $d->rawQuery("select h.ma_hv,h.hoten,h.cccd,m.tien_do,m.dat from #_dt_hocvien h left join #_dt_mophong m on (m.id_khoa=h.id_khoa and m.cccd=h.cccd) where $where order by h.hoten asc", $params);
	$out = array(); $stt = 1;
	foreach($rows as $row) $out[] = array($stt++, $row['ma_hv'], $row['hoten'], $row['cccd'], $row['tien_do'], empty($row['dat']) ? (isset($row['tien_do']) ? 'Chưa đạt' : 'Chưa nhập') : 'Đạt');
	dt_xlsx_simple('Mo phong', 'mo-phong-'.date('Ymd_His').'.xlsx', array('STT','Mã HV','Họ và tên','CCCD','Tiến độ hoàn thành (%)','Kết quả'), $out, array(1, 3));
}

function dt_mophong_where($idKhoa)
{
	global $d;
	$where = 'h.id_khoa = ?'; $params = array((int)$idKhoa);
	$keyword = dt_req('keyword');
	if($keyword !== '')
	{
		$e = $d->escape(htmlspecialchars($keyword));
		$where .= " and (h.hoten like '%$e%' or h.cccd like '%$e%' or h.ma_hv like '%$e%')";
	}
	$status = dt_req('trang_thai');
	if($status === 'dat') $where .= ' and m.dat = 1';
	elseif($status === 'chua') $where .= ' and (m.id is null or m.dat = 0)';
	return array($where, $params);
}