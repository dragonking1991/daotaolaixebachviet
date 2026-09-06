<?php
if(!defined('SOURCES')) die("Error");

/* ============================ Thuật toán lọc thanh toán ============================ */

/**
 * Chạy thuật toán lọc, trả về danh sách học viên được chọn (chưa ghi ngày TT).
 * @return array [selected(list hocvien), summaryByGv, config]
 */
function xd_run_algorithm($d, $ky = '', $fromDate = '', $toDate = '')
{
	$config = getXdConfig($d);
	$dinhMuc = max(1, (int)$config['dinh_muc']); // tránh chia 0

	// Tổng hóa đơn hợp lệ (chưa quyết toán) theo GV (định danh theo TÊN chuẩn hóa gv_key)
	$hoadonWhere = " and da_quyettoan = 0";
	$hoadonParams = array();
	if($ky !== '') { $hoadonWhere .= " and ky = ?"; $hoadonParams[] = $ky; }
	if($fromDate !== '') { $hoadonWhere .= " and ngay_hoa_don >= ?"; $hoadonParams[] = $fromDate; }
	if($toDate !== '') { $hoadonWhere .= " and ngay_hoa_don <= ?"; $hoadonParams[] = $toDate; }

	$sumRows = $d->rawQuery(
		"select gv_key, max(gv_hoten) as gv_hoten, sum(tong_tien) as s_hd, count(*) as so_hd
		 from #_xd_hoadon where gv_key <> '' $hoadonWhere group by gv_key",
		$hoadonParams
	);

	$selected = array();
	$summary = array();

	if(empty($sumRows)) return array($selected, $summary, $config);

	foreach($sumRows as $g)
	{
		$gvKey = $g['gv_key'];
		$sHd = (float)$g['s_hd'];
		$n = (int)floor($sHd / $dinhMuc);

		$row = array(
			'gv_key' => $gvKey,
			'gv_hoten' => $g['gv_hoten'],
			's_hd' => $sHd,
			'so_hd' => (int)$g['so_hd'],
			'n_max' => $n,
			'so_hv_chon' => 0,
			'tong_chi' => 0.0,
			'dinh_muc_toi_da' => $n * $dinhMuc,
			'chenh_lech' => $sHd - ($n * $dinhMuc),
			'ke_toan_kiem_tra' => 0,
			'quan_ly_duyet' => 0,
		);
		$status = $d->rawQueryOne("select min(ke_toan_kiem_tra) as ke_toan_kiem_tra, min(quan_ly_duyet) as quan_ly_duyet from #_xd_hoadon where gv_key = ? and da_quyettoan = 0", array($gvKey));
		if($status) { $row['ke_toan_kiem_tra'] = (int)$status['ke_toan_kiem_tra']; $row['quan_ly_duyet'] = (int)$status['quan_ly_duyet']; }

		if($n > 0)
		{
			$hocviens = $d->rawQuery(
				"select id, ho_ten, cccd, nhom, gv_key, gv_hoten from #_xd_hocvien
				 where gv_key = ? and ngay_thanh_toan is null order by id asc limit ".(int)$n,
				array($gvKey)
			);
			if(!empty($hocviens))
			{
				foreach($hocviens as $hv)
				{
					$soTien = xdMucTheoNhom($config, $hv['nhom']);
					$hv['dinh_muc'] = $dinhMuc;
					$hv['so_tien_thanh_toan'] = $soTien;
					$selected[] = $hv;
					$row['so_hv_chon']++;
					$row['tong_chi'] += $soTien;
				}
			}
		}

		$summary[] = $row;
	}

	return array($selected, $summary, $config);
}
