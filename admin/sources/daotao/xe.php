<?php
if(!defined('SOURCES')) die("Error");

function dt_xe_list()
{
	global $d, $func, $curPage, $items, $paging;

	$where = ""; $params = array();
	if(isset($_REQUEST['keyword']) && $_REQUEST['keyword'] !== '')
	{
		$kw = $d->escape(htmlspecialchars($_REQUEST['keyword']));
		$where .= " and (bien_so like '%$kw%' or gv_hoten like '%$kw%')";
	}
	if(isset($_REQUEST['hang']) && $_REQUEST['hang'] !== '')
	{
		$where .= " and hang_xe = ?"; $params[] = dt_norm_hang($_REQUEST['hang']);
	}

	$per_page = 30;
	$startpoint = ($curPage * $per_page) - $per_page;
	$items = $d->rawQuery("select * from #_dt_xe where 1 $where order by bien_so asc limit $startpoint,$per_page", $params);
	$count = $d->rawQueryOne("select count(*) as num from #_dt_xe where 1 $where", $params);
	$paging = $func->pagination($count['num'], $per_page, $curPage, "index.php?com=daotao&act=xe");
}

function dt_xe_upload_excel()
{
	global $d, $func;

	$back = "index.php?com=daotao&act=uploadXe";
	if(!isset($_FILES['file-excel']) || $_FILES['file-excel']['error'] != 0)
		$func->transfer("Vui lòng chọn file Excel", $back, false);
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
	);
	$contains = array(
		'bien_so' => array('bienso'),
		'hang_xe' => array('hangxe','hangtaplai'),
		'giaovien' => array('giaovien'),
	);

	list($headerRow, $map, $score) = dt_find_header_row($sheet, $highestRow, $highestCol, $aliases, $contains);
	if($score <= 0 || !isset($map['bien_so']))
		$func->transfer("Không nhận diện được cột 'Biển số xe' trong file.", $back, false);

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

		$exist = $d->rawQueryOne("select id from #_dt_xe where bien_so = ? limit 0,1", array($bienSo));
		if($exist && $exist['id']) { $d->where('id', $exist['id']); $d->update('dt_xe', $data); }
		else { $data['ngaytao'] = time(); $d->insert('dt_xe', $data); }
		$ok++;
	}

	dt_log_import('xe', $file['name'], $ok, $err);
	$func->transfer("Import xe: $ok dòng thành công", "index.php?com=daotao&act=xe");
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
