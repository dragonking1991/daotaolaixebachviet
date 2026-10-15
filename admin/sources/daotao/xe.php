<?php
if(!defined('SOURCES')) die("Error");

function dt_xe_list()
{
	global $d, $func, $curPage, $items, $paging, $total_items, $xe_stats;

	list($where, $params) = dt_xe_where();

	$per_page = 30;
	$startpoint = ($curPage * $per_page) - $per_page;
	$items = $d->rawQuery("select * from #_dt_xe where 1 $where order by bien_so asc limit $startpoint,$per_page", $params);
	$count = $d->rawQueryOne("select count(*) as num from #_dt_xe where 1 $where", $params);
	$total_items = (int)$count['num'];

	$base_url = "index.php?com=daotao&act=xe";
	$filter_q = array();
	foreach(array('keyword','hang','loai_xe','nhan_hieu','tt_tl','expiry') as $k)
	{
		if(isset($_REQUEST[$k]) && trim((string)$_REQUEST[$k]) !== '')
			$filter_q[$k] = trim((string)$_REQUEST[$k]);
	}
	if(!empty($filter_q)) $base_url .= '&'.http_build_query($filter_q);
	$paging = $func->pagination($count['num'], $per_page, $curPage, $base_url);
	$xe_stats = dt_xe_expiry_stats();
}

/* Thống kê hết hạn GPLX xe tập lái theo hạng (chỉ xe chưa thanh lý). */
function dt_xe_expiry_stats()
{
	global $d;
	$rows = $d->rawQuery("select hang_xe, ngay_hethan from #_dt_xe where (thanh_ly is null or thanh_ly = 0)");
	$stats = array(
		'tong' => count($rows), 'sap_het' => 0, 'het_han' => 0, 'con_han' => 0, 'chua_ngay' => 0,
		'hang' => array(),
	);
	foreach($rows as $row)
	{
		$hang = dt_norm_hang($row['hang_xe']) ?: 'Khác';
		if(!isset($stats['hang'][$hang])) $stats['hang'][$hang] = array('het_han' => 0, 'sap_het' => 0, 'con_han' => 0, 'chua_ngay' => 0, 'tong' => 0);
		$stats['hang'][$hang]['tong']++;
		$info = dt_expiry_info(isset($row['ngay_hethan']) ? $row['ngay_hethan'] : '', 30);
		if($info === null)
		{
			$stats['chua_ngay']++;
			$stats['hang'][$hang]['chua_ngay']++;
			continue;
		}
		$stats[$info['status']]++;
		$stats['hang'][$hang][$info['status']]++;
	}
	ksort($stats['hang']);
	return $stats;
}

/* Thanh lý / hủy thanh lý xe (thanh lý tài sản xe). */
function dt_xe_thanh_ly()
{
	global $d, $func;
	$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
	$huy = isset($_REQUEST['huy']) && $_REQUEST['huy'] == '1';
	if(!$id) $func->transfer("Không nhận được dữ liệu", "index.php?com=daotao&act=xe", false);
	$xe = $d->rawQueryOne("select id, bien_so from #_dt_xe where id = ? limit 0,1", array($id));
	if(!$xe) $func->transfer("Xe không tồn tại", "index.php?com=daotao&act=xe", false);

	$d->where('id', $id);
	$d->update('dt_xe', array('thanh_ly' => $huy ? 0 : 1, 'ngay_thanh_ly' => $huy ? '' : date('d/m/Y')));
	if(function_exists('dt_audit')) dt_audit($huy ? 'huy_thanh_ly' : 'thanh_ly', 'xe', 1, 'id='.$id.' '.$xe['bien_so']);
	$func->transfer($huy ? "Đã hủy thanh lý xe" : "Đã thanh lý xe", "index.php?com=daotao&act=xe");
}

function dt_xe_upload_excel()
{
	global $d, $func;

	$back = "index.php?com=daotao&act=uploadXe";
	if(!isset($_FILES['file-excel']) || $_FILES['file-excel']['error'] != 0)
		dt_import_notice("Vui lòng chọn file Excel", $back, false);
	$file = $_FILES['file-excel'];
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

	list($sheet, $highestRow, $highestCol) = dt_open_upload_sheet($file, $ext, $back, array('xe'));

	$aliases = array(
		'bien_so' => array('bienso','biensoxe'),
		'hang_xe' => array('hangxetaplai','hangxe','hang'),
		'hang_dt' => array('hangdt','hangdaotao'),
		'so_dangky' => array('sodangkyxe','sodangky'),
		'so_khung' => array('sokhung'),
		'so_may' => array('somay'),
		'loai_xe' => array('loaixe'),
		'nhan_hieu' => array('nhanhieu'),
		'giaovien' => array('giaovien','gv'),
		'ma_csdt' => array('macsdt','macosdt'),
		'nam_sx' => array('namsanxuat','namsx'),
		'so_gpxtl' => array('sogiayphepxetl','sogiayphepxetaplai','sogpxtl','sogplxtl'),
		'ngay_cap_gpxtl' => array('ngaycapgpxtl','ngaycapgplxtl','ngaycapgiayphepxetaplai'),
		'ngay_cap_gcnkd' => array('ngaycapgcnkd','ngaycapgcn','ngaycapkiemdinh'),
		'ngay_hethan_gcnkd' => array('ngayhethangcnkd','ngayhethangcn','ngayhethankiemdinh','ngayhhgcnkd'),
		'so_huu' => array('sohuu','sohuuhay1','chinhchu'),
		'so_imei' => array('soimei','imei'),
		'so_seri' => array('soseri','seri','serino'),
		'ngay_hethan' => array(
			'ngayhethangpxtl','ngayhethangiayphepxetaplai','ngayhethangplxtl','ngayhethangplxtaplai','ngayhethangplxetaplai',
			'ngayhethangplx','ngayhethan','ngayhethanggplx','ngayhetgplx','ngayhethanggiayphep','ngayhethanggiayphepxe',
			'ngayhethanggplxtaplai','ngayhethanxetaplai','hanxetaplai','hangiayphepxetaplai'
		),
	);
	$contains = array(
		'bien_so' => array('bienso'),
		'hang_xe' => array('hangxe','hangtaplai'),
		'giaovien' => array('giaovien'),
		'ngay_hethan' => array('ngayhethangpxtl','ngayhethan','hethangplx','hethanggplx','gpxtl','gplx','taplai','hanxetaplai','giayphep'),
	);

	list($headerRow, $map, $score) = dt_find_header_row($sheet, $highestRow, $highestCol, $aliases, $contains);
	if($score <= 0 || !isset($map['bien_so']))
		dt_import_notice("Không nhận diện được cột 'Biển số xe' trong file.", $back, false);

	dt_backup_tables(array('dt_xe'), 'imp_xe');
	$d->startTransaction();
	$ok = 0; $err = 0; $errMsgs = array(); $emptyStreak = 0;
	for($r = $headerRow + 1; $r <= $highestRow; $r++)
	{
		$row = dt_read_row($sheet, $r, $highestCol);
		$bienSo = preg_replace('/\s+/', '', dt_val($row, $map, 'bien_so'));
		if($bienSo === '') { if(++$emptyStreak >= 30) break; continue; }
		$emptyStreak = 0;

		$gvHoten = dt_val($row, $map, 'giaovien');
		$data = array(
			'bien_so' => $bienSo,
			'hang_xe' => dt_norm_hang(dt_val($row, $map, 'hang_xe')),
			'hang_dt' => dt_val($row, $map, 'hang_dt'),
			'so_dangky' => dt_val($row, $map, 'so_dangky'),
			'so_khung' => dt_val($row, $map, 'so_khung'),
			'so_may' => dt_val($row, $map, 'so_may'),
			'loai_xe' => dt_val($row, $map, 'loai_xe'),
			'nhan_hieu' => dt_val($row, $map, 'nhan_hieu'),
			'gv_hoten' => $gvHoten,
			'gv_key' => dt_gv_key($gvHoten),
		);
		if(isset($map['ngay_hethan'])) $data['ngay_hethan'] = dt_gv_fmt_date(dt_val($row, $map, 'ngay_hethan'));
		// Cột bổ sung theo mẫu mới (Update 5): chỉ ghi khi file có cột tương ứng.
		if(isset($map['ma_csdt'])) $data['ma_csdt'] = dt_val($row, $map, 'ma_csdt');
		if(isset($map['nam_sx'])) $data['nam_sx'] = dt_val($row, $map, 'nam_sx');
		if(isset($map['so_gpxtl'])) $data['so_gpxtl'] = dt_val($row, $map, 'so_gpxtl');
		if(isset($map['ngay_cap_gpxtl'])) $data['ngay_cap_gpxtl'] = dt_gv_fmt_date(dt_val($row, $map, 'ngay_cap_gpxtl'));
		if(isset($map['ngay_cap_gcnkd'])) $data['ngay_cap_gcnkd'] = dt_gv_fmt_date(dt_val($row, $map, 'ngay_cap_gcnkd'));
		if(isset($map['ngay_hethan_gcnkd'])) $data['ngay_hethan_gcnkd'] = dt_gv_fmt_date(dt_val($row, $map, 'ngay_hethan_gcnkd'));
		if(isset($map['so_huu'])) $data['so_huu'] = dt_val($row, $map, 'so_huu');
		if(isset($map['so_imei'])) $data['so_imei'] = dt_val($row, $map, 'so_imei');
		if(isset($map['so_seri'])) $data['so_seri'] = dt_val($row, $map, 'so_seri');

		$exist = $d->rawQueryOne("select id from #_dt_xe where bien_so = ? limit 0,1", array($bienSo));
		if($exist && $exist['id']) { $d->where('id', $exist['id']); $d->update('dt_xe', $data); }
		else { $data['ngaytao'] = time(); $d->insert('dt_xe', $data); }
		$ok++;
	}

	$d->commit();
	dt_audit('import', 'xe', $ok, $file['name']);
	dt_log_import('xe', $file['name'], $ok, $err);
	dt_import_notice("Import xe: $ok dòng thành công", "index.php?com=daotao&act=xe");
}

/* Trả về true nếu biển số thuộc xe số tự động (hạng B11). */
function dt_xe_la_tudong($bienSo)
{
	global $d;
	static $cache = array();
	$bienSo = preg_replace('/\s+/', '', (string)$bienSo);
	if($bienSo === '') return false;
	if(isset($cache[$bienSo])) return $cache[$bienSo];
	$row = $d->rawQueryOne("select hang_xe from #_dt_xe where bien_so = ? limit 0,1", array($bienSo));
	$cache[$bienSo] = ($row && dt_norm_hang($row['hang_xe']) === 'B11');
	return $cache[$bienSo];
}
