<?php
/* Thư viện logic phân hệ đào tạo — dùng chung cho admin (admin/sources/daotao/*)
 * và cổng công khai (ajax/*). Không phụ thuộc hằng số SOURCES. Cần global $d khi truy vấn. */

if(!function_exists('dt_mb_lower'))
{
	function dt_mb_lower($s)
	{
		return function_exists('mb_strtolower') ? mb_strtolower((string)$s, 'UTF-8') : strtolower((string)$s);
	}

	function dt_strip_diacritics($s)
	{
		$s = dt_mb_lower(trim((string)$s));
		$search  = array('à','á','ả','ã','ạ','ă','ằ','ắ','ẳ','ẵ','ặ','â','ầ','ấ','ẩ','ẫ','ậ','đ','è','é','ẻ','ẽ','ẹ','ê','ề','ế','ể','ễ','ệ','ì','í','ỉ','ĩ','ị','ò','ó','ỏ','õ','ọ','ô','ồ','ố','ổ','ỗ','ộ','ơ','ờ','ớ','ở','ỡ','ợ','ù','ú','ủ','ũ','ụ','ư','ừ','ứ','ử','ữ','ự','ỳ','ý','ỷ','ỹ','ỵ');
		$replace = array('a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','d','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y');
		return str_replace($search, $replace, $s);
	}

	function dt_norm_header($label)
	{
		return preg_replace('/[^a-z0-9]+/', '', dt_strip_diacritics($label));
	}

	function dt_normalize_cccd($value)
	{
		return preg_replace('/\D+/', '', (string)$value);
	}

	function dt_cccd_variants($cccd)
	{
		$cccd = dt_normalize_cccd($cccd);
		$variants = array($cccd);
		if(strlen($cccd) == 11) $variants[] = '0'.$cccd;
		elseif(strlen($cccd) == 12 && substr($cccd, 0, 1) === '0') $variants[] = substr($cccd, 1);
		return array_values(array_unique(array_filter($variants, function($v){ return $v !== ''; })));
	}

	function dt_gv_key($name)
	{
		$name = dt_strip_diacritics($name);
		if($name === '') return '';
		$name = preg_replace('/[^a-z0-9\s]+/', ' ', $name);
		$name = preg_replace('/\s+/', ' ', trim($name));
		$name = preg_replace('/^(thay|co)\s+/', '', $name);
		return trim($name);
	}

	function dt_norm_hang($raw)
	{
		$h = dt_strip_diacritics($raw);
		$h = preg_replace('/\s+/', ' ', trim($h));
		if($h === '') return '';
		// "X len Y" (nâng hạng) -> lấy hạng đích Y
		if(strpos($h, 'len') !== false)
		{
			$parts = explode('len', $h);
			$tail = trim(end($parts));
			if($tail !== '') $h = $tail;
		}
		$compact = preg_replace('/[^a-z0-9]+/', '', $h);
		if($compact === 'b11') return 'B11';
		if($compact === 'b1') return 'B1';
		if($compact === 'c1') return 'C1';
		if($compact === 'ce') return 'CE';
		if(strpos($h, 'tu dong') !== false && strpos($h, 'b') !== false) return 'B11';
		if((strpos($h, 'co khi') !== false || strpos($h, 'so san') !== false) && strpos($h, 'b') !== false) return 'B1';
		if(strpos($compact, 'ce') !== false) return 'CE';
		if(strpos($compact, 'c1') !== false) return 'C1';
		if($compact === 'b') return 'B1';
		if($compact === 'c') return 'C';
		if($compact === 'e') return 'CE';
		if(strpos($h, 'b') === 0) return 'B1';
		if(strpos($h, 'c') === 0) return 'C';
		return strtoupper($compact);
	}

	/* -------- Cấu hình ngưỡng -------- */
	function dt_mon_lythuyet()
	{
		return array(
			'cau_tao'  => 'Cấu tạo sửa chữa',
			'ky_thuat' => 'Kỹ thuật lái xe',
			'phan_1'   => 'Pháp luật - Phần 1',
			'phan_2'   => 'Pháp luật - Phần 2',
			'phan_3'   => 'Pháp luật - Phần 3',
			'dao_duc'  => 'Đạo đức người lái xe',
			'mophong'  => 'Mô phỏng',
		);
	}
	function dt_nguong_lythuyet() { return array('tien_do' => 70, 'diem_kt' => 5); }
	function dt_nguong_dat()
	{
		return array(
			'B11' => array('a' => 12, 'km' => 710, 'dem' => 1, 'c' => 0, 'd' => 0),
			'B1'  => array('a' => 20, 'km' => 810, 'dem' => 1, 'c' => 1, 'd' => 19),
			'C1'  => array('a' => 24, 'km' => 825, 'dem' => 1, 'c' => 1, 'd' => 23),
			'C'   => array('a' => 5,  'km' => 210, 'dem' => 1, 'c' => 0, 'd' => 0),
			'CE'  => array('a' => 5,  'km' => 210, 'dem' => 1, 'c' => 0, 'd' => 0),
		);
	}
	function dt_nguong_hinh()
	{
		return array(
			'B11' => array('km' => 120, 'gio' => 34, 'km_max' => 130, 'gio_max' => 36),
			'B1'  => array('km' => 120, 'gio' => 34, 'km_max' => 130, 'gio_max' => 36),
			'C1'  => array('km' => 115, 'gio' => 35, 'km_max' => 120, 'gio_max' => 37),
			'C'   => array('km' => 15,  'gio' => 7,  'km_max' => 20,  'gio_max' => 8),
			'CE'  => array('km' => 15,  'gio' => 7,  'km_max' => 20,  'gio_max' => 8),
		);
	}

	/* -------- Đánh giá -------- */
	function dt_lythuyet_dat($tienDo, $diemKt)
	{
		$ng = dt_nguong_lythuyet();
		return ((float)$tienDo > $ng['tien_do'] && (float)$diemKt > $ng['diem_kt']) ? 1 : 0;
	}
	function dt_hinh_dat($hang, $gio, $km)
	{
		$hang = dt_norm_hang($hang);
		$ng = dt_nguong_hinh();
		if(!isset($ng[$hang])) return 0;
		return ((float)$km >= $ng[$hang]['km'] && (float)$gio > $ng[$hang]['gio']) ? 1 : 0;
	}
	function dt_dat_danhgia($hang, $agg)
	{
		$hang = dt_norm_hang($hang);
		$ng = dt_nguong_dat();
		if(!isset($ng[$hang])) return array(0, array('Hạng không xác định'));
		$r = $ng[$hang]; $thieu = array();
		if($agg['a'] < $r['a']) $thieu[] = 'Giờ (A) < '.$r['a'];
		if($r['dem'] > 0 && $agg['b'] < $r['dem']) $thieu[] = 'Giờ đêm (B) < '.$r['dem'];
		if($r['c'] > 0 && $agg['c'] < $r['c']) $thieu[] = 'Giờ tự động (C) < '.$r['c'];
		if($r['d'] > 0 && $agg['d'] < $r['d']) $thieu[] = 'Giờ số sàn (D) < '.$r['d'];
		if($agg['e'] < $r['km']) $thieu[] = 'Quãng đường (E) < '.$r['km'].' km';
		return array(empty($thieu) ? 1 : 0, $thieu);
	}

	function dt_night_hours($startDt, $endDt)
	{
		$s = $startDt ? strtotime($startDt) : false;
		$e = $endDt ? strtotime($endDt) : false;
		if($s === false || $e === false || $e <= $s) return 0.0;
		$minutes = 0; $iter = 0;
		for($t = $s; $t < $e && $iter < 2000; $t += 60, $iter++)
		{
			$h = (int)date('G', $t);
			if($h >= 18 || $h < 5) $minutes++;
		}
		return round($minutes / 60, 4);
	}

	function dt_dat_aggregate($cccd, $idKhoa)
	{
		global $d;
		$rows = $d->rawQuery("select gio_thuchanh, la_xe_tudong, gio_dem, km from #_dt_dat_phien where id_khoa = ? and cccd = ?", array($idKhoa, $cccd));
		$agg = array('a' => 0, 'b' => 0, 'c' => 0, 'd' => 0, 'e' => 0);
		foreach($rows as $rw)
		{
			$gt = (float)$rw['gio_thuchanh'];
			if((int)$rw['la_xe_tudong'] === 1) $agg['c'] += $gt; else $agg['d'] += $gt;
			$agg['b'] += (float)$rw['gio_dem'];
			$agg['e'] += (float)$rw['km'];
		}
		$agg['a'] = $agg['c'] + $agg['d'];
		foreach($agg as $k => $v) $agg[$k] = round($v, 4);
		return $agg;
	}

	function dt_find_hocvien_by_cccd($cccd, $idKhoa = 0)
	{
		global $d;
		$variants = dt_cccd_variants($cccd);
		if(empty($variants)) return null;
		$in = implode(',', array_fill(0, count($variants), '?'));
		$params = $variants;
		$sql = "select * from #_dt_hocvien where cccd in ($in)";
		if($idKhoa) { $sql .= " and id_khoa = ?"; $params[] = (int)$idKhoa; }
		$sql .= " order by id desc limit 0,1";
		return $d->rawQueryOne($sql, $params);
	}

	function dt_find_hocvien_by_mahv($maHv, $idKhoa = 0)
	{
		global $d;
		$maHv = trim((string)$maHv);
		if($maHv === '') return null;
		$sql = "select * from #_dt_hocvien where ma_hv = ?";
		$params = array($maHv);
		if($idKhoa) { $sql .= " and id_khoa = ?"; $params[] = (int)$idKhoa; }
		$sql .= " limit 0,1";
		return $d->rawQueryOne($sql, $params);
	}

	function dt_student_summary($hv)
	{
		global $d;
		$idKhoa = (int)$hv['id_khoa'];
		$cccd = $hv['cccd'];
		$maHv = $hv['ma_hv'];

		$tongMon = count(dt_mon_lythuyet());
		$rDat = $d->rawQueryOne("select count(*) as c from #_dt_lythuyet where id_khoa = ? and cccd = ? and dat = 1", array($idKhoa, $cccd));
		$soDat = (int)($rDat ? $rDat['c'] : 0);
		$rCo = $d->rawQueryOne("select count(*) as c from #_dt_lythuyet where id_khoa = ? and cccd = ?", array($idKhoa, $cccd));
		$soCo = (int)($rCo ? $rCo['c'] : 0);
		$lyThuyetDat = ($soDat >= $tongMon) ? 1 : 0;

		$cabin = $d->rawQueryOne("select dat from #_dt_cabin_kq where id_khoa = ? and ma_hv = ? limit 0,1", array($idKhoa, $maHv));
		$cabinDat = ($cabin && (int)$cabin['dat'] === 1) ? 1 : 0;

		$hinh = $d->rawQueryOne("select gio, km from #_dt_thuchanh_hinh where id_khoa = ? and cccd = ? limit 0,1", array($idKhoa, $cccd));
		$hinhDat = $hinh ? dt_hinh_dat($hv['hang'], $hinh['gio'], $hinh['km']) : 0;

		$agg = dt_dat_aggregate($cccd, $idKhoa);
		list($datDat, $datThieu) = dt_dat_danhgia($hv['hang'], $agg);

		$duDieuKien = ($lyThuyetDat && $cabinDat && $hinhDat && $datDat) ? 1 : 0;

		return array(
			'ly_thuyet' => array('dat' => $lyThuyetDat, 'so_dat' => $soDat, 'so_co' => $soCo, 'tong' => $tongMon),
			'cabin' => array('dat' => $cabinDat, 'co' => $cabin ? 1 : 0),
			'hinh' => array('dat' => $hinhDat, 'gio' => $hinh ? (float)$hinh['gio'] : 0, 'km' => $hinh ? (float)$hinh['km'] : 0),
			'dat' => array('dat' => $datDat, 'agg' => $agg, 'thieu' => $datThieu),
			'du_dieu_kien' => $duDieuKien,
		);
	}

	function dt_gv_hash($plain) { return md5('dt_gv_'.$plain.'_bachviet'); }

	/* Cấu hình hiển thị cổng giáo viên (đọc table_dt_setting key 'cong_gv', hợp nhất mặc định). */
	function dt_portal_conggv()
	{
		global $d;
		$cfg = array(
			'show_lythuyet' => 1, 'show_cabin' => 1, 'show_hinh' => 1, 'show_dat' => 1, 'show_ketluan' => 1,
			'show_detail' => 1, 'allow_hinh_input' => 1,
		);
		$row = null;
		try { $row = $d->rawQueryOne("select sval from #_dt_setting where skey = 'cong_gv' limit 0,1"); }
		catch(Exception $e) { $row = null; }
		if($row && isset($row['sval']) && $row['sval'] !== '')
		{
			$saved = json_decode($row['sval'], true);
			if(is_array($saved)) foreach($cfg as $k => $v) if(isset($saved[$k])) $cfg[$k] = (int)$saved[$k];
		}
		return $cfg;
	}

	/* Trạng thái từng môn lý thuyết (gồm mô phỏng) của học viên: dat|chua_dat|chua_kiem_tra|chua_du_lieu. */
	function dt_portal_lythuyet_mon_status($hv)
	{
		global $d;
		$idKhoa = (int)$hv['id_khoa']; $cccd = $hv['cccd'];
		$rows = $d->rawQuery("select mon, diem_kt, dat from #_dt_lythuyet where id_khoa = ? and cccd = ?", array($idKhoa, $cccd));
		$by = array();
		foreach($rows as $rw) $by[$rw['mon']] = $rw;
		$out = array();
		foreach(dt_mon_lythuyet() as $mk => $lbl)
		{
			if(!isset($by[$mk])) { $out[$mk] = array('label' => $lbl, 'trang_thai' => 'chua_du_lieu'); continue; }
			$rw = $by[$mk];
			if((int)$rw['dat'] === 1) { $out[$mk] = array('label' => $lbl, 'trang_thai' => 'dat'); continue; }
			if($mk !== 'mophong' && (float)$rw['diem_kt'] <= 0) $out[$mk] = array('label' => $lbl, 'trang_thai' => 'chua_kiem_tra');
			else $out[$mk] = array('label' => $lbl, 'trang_thai' => 'chua_dat');
		}
		return $out;
	}

	/* Nội dung ô "Lý thuyết": "Đạt" hoặc liệt kê môn còn thiếu để GV biết cần dạy gì thêm. */
	function dt_portal_lythuyet_cell($hv, $lyThuyetDat)
	{
		if($lyThuyetDat) return array();
		$parts = array();
		foreach(dt_portal_lythuyet_mon_status($hv) as $m)
		{
			if($m['trang_thai'] === 'dat') continue;
			if($m['trang_thai'] === 'chua_kiem_tra') $tag = 'chưa kiểm tra';
			elseif($m['trang_thai'] === 'chua_du_lieu') $tag = 'chưa có dữ liệu';
			else $tag = 'chưa đạt';
			$parts[] = $m['label'].': '.$tag;
		}
		return $parts;
	}

	/* Nội dung còn thiếu của Thực hành trong hình theo hạng (để GV biết cần nhập đủ bao nhiêu). */
	function dt_portal_hinh_missing($hang, $gio, $km)
	{
		$ng = dt_nguong_hinh();
		$h = dt_norm_hang($hang);
		if(!isset($ng[$h])) return array();
		$miss = array();
		if((float)$gio <= $ng[$h]['gio']) $miss[] = 'Cần > '.$ng[$h]['gio'].' giờ';
		if((float)$km < $ng[$h]['km']) $miss[] = 'Cần ≥ '.$ng[$h]['km'].' km';
		return $miss;
	}

	/* Cảnh báo (không chặn) khi Giờ/Km vượt trần tối đa theo hạng. */
	function dt_hinh_cap_warn($hang, $gio, $km)
	{
		$ng = dt_nguong_hinh();
		$h = dt_norm_hang($hang);
		if(!isset($ng[$h])) return array();
		$w = array();
		if(isset($ng[$h]['gio_max']) && (float)$gio > $ng[$h]['gio_max']) $w[] = 'Giờ vượt trần '.$ng[$h]['gio_max'].'h';
		if(isset($ng[$h]['km_max']) && (float)$km > $ng[$h]['km_max']) $w[] = 'Km vượt trần '.$ng[$h]['km_max'].'km';
		return $w;
	}

	/* Bảo đảm bảng nhật ký cập nhật của giáo viên tồn tại (cổng chạy độc lập với admin). */
	function dt_portal_ensure_gv_log()
	{
		global $d;
		static $done = false;
		if($done) return;
		try { $d->rawQuery("CREATE TABLE IF NOT EXISTS table_dt_gv_log (id INT AUTO_INCREMENT PRIMARY KEY, gv_key VARCHAR(255) DEFAULT '', gv_cccd VARCHAR(20) DEFAULT '', hv_cccd VARCHAR(20) DEFAULT '', hv_hoten VARCHAR(255) DEFAULT '', noi_dung VARCHAR(500) DEFAULT '', ngaytao INT DEFAULT 0, KEY idx_dt_gvlog_key (gv_key), KEY idx_dt_gvlog_ngay (ngaytao)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }
		catch(Exception $e) {}
		$done = true;
	}

	/* Ghi một dòng nhật ký cập nhật của giáo viên. */
	function dt_portal_gv_log($gvKey, $gvCccd, $hvCccd, $hvHoten, $noiDung)
	{
		global $d;
		dt_portal_ensure_gv_log();
		try {
			$d->insert('dt_gv_log', array(
				'gv_key' => (string)$gvKey, 'gv_cccd' => (string)$gvCccd,
				'hv_cccd' => (string)$hvCccd, 'hv_hoten' => (string)$hvHoten,
				'noi_dung' => mb_substr((string)$noiDung, 0, 490), 'ngaytao' => time(),
			));
		} catch(Exception $e) {}
	}

	/* Lấy nhật ký cập nhật của giáo viên trong $days ngày gần đây. */
	function dt_portal_gv_history($gvKey, $days = 30, $limit = 50)
	{
		global $d;
		dt_portal_ensure_gv_log();
		$since = time() - $days * 86400;
		$rows = array();
		try { $rows = $d->rawQuery("select hv_hoten, hv_cccd, noi_dung, ngaytao from #_dt_gv_log where gv_key = ? and ngaytao >= ? order by ngaytao desc limit ".(int)$limit, array($gvKey, $since)); }
		catch(Exception $e) { $rows = array(); }
		return is_array($rows) ? $rows : array();
	}
}
