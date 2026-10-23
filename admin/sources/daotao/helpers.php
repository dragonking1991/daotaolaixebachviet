<?php
if(!defined('SOURCES')) die("Error");

/* ============================ Helpers dùng chung (đào tạo) ============================ */

function dt_mb_lower($s)
{
	return function_exists('mb_strtolower') ? mb_strtolower((string)$s, 'UTF-8') : strtolower((string)$s);
}

/* Bỏ dấu tiếng Việt + chữ thường. Mảng search/replace phải cùng số phần tử (67). */
function dt_strip_diacritics($s)
{
	$s = dt_mb_lower(trim((string)$s));
	$search  = array('à','á','ả','ã','ạ','ă','ằ','ắ','ẳ','ẵ','ặ','â','ầ','ấ','ẩ','ẫ','ậ','đ','è','é','ẻ','ẽ','ẹ','ê','ề','ế','ể','ễ','ệ','ì','í','ỉ','ĩ','ị','ò','ó','ỏ','õ','ọ','ô','ồ','ố','ổ','ỗ','ộ','ơ','ờ','ớ','ở','ỡ','ợ','ù','ú','ủ','ũ','ụ','ư','ừ','ứ','ử','ữ','ự','ỳ','ý','ỷ','ỹ','ỵ');
	$replace = array('a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','d','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y');
	return str_replace($search, $replace, $s);
}

/* Chuẩn hóa tiêu đề cột: bỏ dấu, bỏ mọi ký tự không phải chữ/số. */
function dt_norm_header($label)
{
	return preg_replace('/[^a-z0-9]+/', '', dt_strip_diacritics($label));
}

function dt_normalize_cccd($value)
{
	return preg_replace('/\D+/', '', (string)$value);
}

/* Biến thể CCCD 11/12 số (giữ số 0 đầu) để so khớp linh hoạt. */
function dt_cccd_variants($cccd)
{
	$cccd = dt_normalize_cccd($cccd);
	$variants = array($cccd);
	if(strlen($cccd) == 11) $variants[] = '0'.$cccd;
	elseif(strlen($cccd) == 12 && substr($cccd, 0, 1) === '0') $variants[] = substr($cccd, 1);
	return array_values(array_unique(array_filter($variants, function($v){ return $v !== ''; })));
}

/* Khóa định danh giáo viên theo tên (file import không có CCCD giáo viên). */
function dt_gv_key($name)
{
	$name = dt_strip_diacritics($name);
	if($name === '') return '';
	$name = preg_replace('/[^a-z0-9\s]+/', ' ', $name);
	$name = preg_replace('/\s+/', ' ', trim($name));
	$name = preg_replace('/^(thay|co)\s+/', '', $name);
	return trim($name);
}

function dt_username()
{
	global $login_admin;
	return isset($_SESSION[$login_admin]['username']) ? $_SESSION[$login_admin]['username'] : '';
}

/**
 * Chuẩn hóa hạng dùng chung cho xe và DAT.
 * B số cơ khí/số sàn -> B1; B số tự động -> B11; C1 -> C1; C (kể cả "B lên C") -> C; CE -> CE.
 */
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

	// Ưu tiên các mã tường minh trong file xe (B11/B1/C1/CE/C)
	$compact = preg_replace('/[^a-z0-9]+/', '', $h);
	if($compact === 'b11') return 'B11';
	if($compact === 'b1') return 'B1';
	if($compact === 'c1') return 'C1';
	if($compact === 'ce') return 'CE';
	if($compact === 'fc') return 'CE';

	// Diễn giải bằng lời
	if(strpos($h, 'tu dong') !== false && (strpos($h, 'b') !== false)) return 'B11';
	if((strpos($h, 'co khi') !== false || strpos($h, 'so san') !== false) && strpos($h, 'b') !== false) return 'B1';
	if(strpos($compact, 'ce') !== false) return 'CE';
	if(strpos($compact, 'c1') !== false) return 'C1';

	// Fallback theo ký tự đầu
	if($compact === 'b') return 'B1';
	if($compact === 'c') return 'C';
	if($compact === 'e') return 'CE';
	if(strpos($h, 'b') === 0) return 'B1';
	if(strpos($h, 'c') === 0) return 'C';
	return strtoupper($compact);
}

/**
 * Dò header theo alias + fallback substring.
 * $aliasGroups: map field => array các tiêu đề chuẩn hóa (norm) chấp nhận.
 * $containsRules: map field => array các chuỗi con (norm) — dùng nếu chưa khớp alias chính xác.
 * @return array [map field=>colIndex, score]
 */
function dt_detect_header($headerCells, $aliasGroups, $containsRules = array())
{
	$normCols = array();
	foreach($headerCells as $idx => $label) $normCols[$idx] = dt_norm_header($label);

	$map = array();
	$score = 0;

	// Pass 1: khớp alias chính xác
	foreach($aliasGroups as $field => $aliases)
	{
		foreach($normCols as $idx => $norm)
		{
			if($norm === '') continue;
			if(in_array($norm, $aliases, true)) { $map[$field] = $idx; $score++; break; }
		}
	}

	// Pass 2: khớp substring cho field chưa tìm thấy
	foreach($containsRules as $field => $subs)
	{
		if(isset($map[$field])) continue;
		foreach($normCols as $idx => $norm)
		{
			if($norm === '' || in_array($idx, $map, true)) continue;
			foreach($subs as $sub)
			{
				if($sub !== '' && strpos($norm, $sub) !== false) { $map[$field] = $idx; $score++; break 2; }
			}
		}
	}

	return array($map, $score);
}

/* Lấy giá trị ô theo field đã map; trả '' nếu field không map (không đoán cột). */
function dt_val($row, $map, $field)
{
	if(!isset($map[$field])) return '';
	$idx = $map[$field];
	return isset($row[$idx]) ? trim((string)$row[$idx]) : '';
}

/* Parse số thập phân từ ô (chấp nhận dấu phẩy hoặc chấm). */
function dt_parse_number($v)
{
	$v = trim((string)$v);
	if($v === '') return 0.0;
	$v = str_replace(array(' ', "\xC2\xA0"), '', $v);
	// Nếu có cả , và . -> giả định , là ngăn nghìn
	if(strpos($v, ',') !== false && strpos($v, '.') !== false) $v = str_replace(',', '', $v);
	else $v = str_replace(',', '.', $v);
	return is_numeric($v) ? (float)$v : 0.0;
}

/* Parse ngày về Y-m-d từ chuỗi D/M/Y hoặc serial Excel. */
function dt_parse_date($v)
{
	$v = trim((string)$v);
	if($v === '') return null;
	if(preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})/', $v, $m))
		return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
	if(preg_match('/^\d{4}-\d{2}-\d{2}/', $v)) return substr($v, 0, 10);
	if(is_numeric($v) && (float)$v > 0)
	{
		require_once LIBRARIES.'PHPExcel.php';
		if(class_exists('PHPExcel_Shared_Date'))
			return date('Y-m-d', PHPExcel_Shared_Date::ExcelToPHP((float)$v));
	}
	return null;
}

/* Parse datetime về 'Y-m-d H:i:s' từ chuỗi hoặc serial Excel. */
function dt_parse_datetime($v)
{
	$v = trim((string)$v);
	if($v === '') return null;
	if(is_numeric($v) && (float)$v > 0)
	{
		require_once LIBRARIES.'PHPExcel.php';
		if(class_exists('PHPExcel_Shared_Date'))
			return date('Y-m-d H:i:s', PHPExcel_Shared_Date::ExcelToPHP((float)$v));
	}
	$ts = strtotime(str_replace('/', '-', $v));
	// strtotime hiểu d-m-Y sai; xử lý riêng D/M/Y H:i
	if(preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})[ T]+(\d{1,2}):(\d{2})(?::(\d{2}))?/', $v, $m))
		return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $m[3], $m[2], $m[1], $m[4], $m[5], isset($m[6])?$m[6]:0);
	return $ts ? date('Y-m-d H:i:s', $ts) : null;
}

/* ============================ Đánh giá kết quả ============================ */

/* Tat ca cac mon dat khi tien do >= 70%. */
function dt_lythuyet_dat($tienDo, $diemKt, $mon = '')
{
	$ng = dt_nguong_lythuyet();
	return ((float)$tienDo >= $ng['tien_do']) ? 1 : 0;
}

/* Chỉ override = 1 xác nhận Đạt; NULL/0 đều tự xét (PDODb lưu NULL thành 0). */
function dt_hinh_dat($hang, $gio, $km, $override = null)
{
	if((int)$override === 1) return 1;
	$hang = dt_norm_hang($hang);
	$ng = dt_nguong_hinh();
	if(!isset($ng[$hang])) return 0;
	return ((float)$km >= $ng[$hang]['km'] && (float)$gio > $ng[$hang]['gio']) ? 1 : 0;
}

/* Lý do vượt trần theo hạng, dùng để chặn lưu thực hành hình. */
function dt_hinh_cap_warn($hang, $gio, $km)
{
	$h = dt_norm_hang($hang);
	$ng = dt_nguong_hinh();
	if(!isset($ng[$h])) return array();
	$w = array();
	if(isset($ng[$h]['gio_max']) && (float)$gio > $ng[$h]['gio_max']) $w[] = 'Giờ ('.rtrim(rtrim(number_format((float)$gio,2,'.',''),'0'),'.').') vượt trần '.$ng[$h]['gio_max'].'h';
	if(isset($ng[$h]['km_max']) && (float)$km > $ng[$h]['km_max']) $w[] = 'Km ('.rtrim(rtrim(number_format((float)$km,2,'.',''),'0'),'.').') vượt trần '.$ng[$h]['km_max'].'km';
	return $w;
}

/**
 * Đánh giá DAT từ các tham số đã cộng dồn.
 * $agg: array('a','b','c','d','e') — a=tổng giờ, b=giờ đêm, c=giờ tự động, d=giờ số sàn, e=km.
 * @return array [dat(0/1), thieu(mảng lý do)]
 */
function dt_dat_danhgia($hang, $agg)
{
	$hang = dt_norm_hang($hang);
	$ng = dt_nguong_dat();
	if(!isset($ng[$hang])) return array(0, array('Hạng không xác định'));
	$r = $ng[$hang];
	$thieu = array();
	$f = function($v) { return rtrim(rtrim(number_format((float)$v, 2, '.', ''), '0'), '.'); };
	if($agg['a'] < $r['a']) $thieu[] = 'Thiếu '.$f($r['a'] - $agg['a']).' giờ tổng (A)';
	if($r['dem'] > 0 && $agg['b'] < $r['dem']) $thieu[] = 'Thiếu '.$f($r['dem'] - $agg['b']).' giờ đêm (B)';
	if($r['c'] > 0 && $agg['c'] < $r['c']) $thieu[] = 'Thiếu '.$f($r['c'] - $agg['c']).' giờ tự động (C)';
	if($r['d'] > 0 && $agg['d'] < $r['d']) $thieu[] = 'Thiếu '.$f($r['d'] - $agg['d']).' giờ số sàn (D)';
	if($agg['e'] < $r['km']) $thieu[] = 'Thiếu '.$f($r['km'] - $agg['e']).' km (E)';
	return array(empty($thieu) ? 1 : 0, $thieu);
}

/* Lấy trạng thái override Đạt DAT thủ công: 1 = Đạt override, null = không override. */
function dt_dat_override_get($idKhoa, $cccd)
{
	global $d;
	$r = $d->rawQueryOne("select dat from #_dt_dat_override where id_khoa = ? and cccd = ? limit 0,1", array((int)$idKhoa, $cccd));
	return ($r && (int)$r['dat'] === 1) ? 1 : null;
}

/* ============================ Tra cứu học viên (cầu nối) ============================ */

function dt_find_hocvien_by_cccd($cccd, $idKhoa = 0)
{
	global $d;
	$variants = dt_cccd_variants($cccd);
	if(empty($variants)) return null;
	$in = implode(',', array_fill(0, count($variants), '?'));
	$params = $variants;
	$sql = "select * from #_dt_hocvien where cccd in ($in)";
	if($idKhoa) { $sql .= " and id_khoa = ?"; $params[] = (int)$idKhoa; }
	$sql .= " limit 0,1";
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

/* Cabin được xem là đạt khi thực sự đạt HOẶC khóa không yêu cầu cabin (nâng hạng C/CE). */
function dt_cabin_pass($cabin)
{
	return (!empty($cabin['dat']) || empty($cabin['yeu_cau'])) ? 1 : 0;
}

/**
 * Trạng thái từng môn lý thuyết của học viên (mô phỏng gộp chung trong nội dung lý thuyết).
 * @return array mon => ['label','trang_thai'] với trang_thai ∈ dat|chua_dat|chua_kiem_tra|chua_du_lieu
 */
function dt_lythuyet_mon_status($hv)
{
	global $d;
	$idKhoa = (int)$hv['id_khoa'];
	$cccd = $hv['cccd'];
	$monSet = dt_mon_lythuyet_for_hang($hv['hang']);
	$rows = $d->rawQuery("select mon, tien_do, diem_kt, dat from #_dt_lythuyet where id_khoa = ? and cccd = ?", array($idKhoa, $cccd));
	$by = array();
	foreach($rows as $rw) $by[$rw['mon']] = $rw;
	$mophong = $d->rawQueryOne("select tien_do, dat from #_dt_mophong where id_khoa = ? and cccd = ? limit 0,1", array($idKhoa, $cccd));
	if($mophong) $by['mophong'] = array('tien_do' => $mophong['tien_do'], 'diem_kt' => 0, 'dat' => $mophong['dat']);
	$out = array();
	foreach($monSet as $mk => $lbl)
	{
		if(!isset($by[$mk])) { $out[$mk] = array('label' => $lbl, 'trang_thai' => 'chua_du_lieu'); continue; }
		$rw = $by[$mk];
		if(dt_lythuyet_dat($rw['tien_do'], $rw['diem_kt'], $mk)) { $out[$mk] = array('label' => $lbl, 'trang_thai' => 'dat'); continue; }
		// Chưa đạt: điểm kiểm tra = 0 (và không phải mô phỏng) => chưa kiểm tra; còn lại => chưa đạt.
		if($mk !== 'mophong' && (float)$rw['diem_kt'] <= 0) $out[$mk] = array('label' => $lbl, 'trang_thai' => 'chua_kiem_tra');
		else $out[$mk] = array('label' => $lbl, 'trang_thai' => 'chua_dat');
	}
	return $out;
}

/* Nội dung ô "Lý thuyết" cho mô đun tổng hợp: "Đạt" hoặc liệt kê môn chưa kiểm tra / chưa đạt. */
function dt_lythuyet_cell_text($hv, $lyThuyetDat)
{
	if($lyThuyetDat) return 'Đạt';
	$parts = array();
	foreach(dt_lythuyet_mon_status($hv) as $m)
	{
		if($m['trang_thai'] === 'dat') continue;
		if($m['trang_thai'] === 'chua_kiem_tra') $tag = 'chưa kiểm tra';
		elseif($m['trang_thai'] === 'chua_du_lieu') $tag = 'chưa có dữ liệu';
		else $tag = 'chưa đạt';
		$parts[] = $m['label'].': '.$tag;
	}
	return $parts ? implode("\n", $parts) : 'Chưa đạt';
}

/* Nội dung ô "DAT" cho mô đun tổng hợp: "Đạt" hoặc liệt kê nội dung chưa đủ. */
function dt_dat_cell_text($sum)
{
	if(!empty($sum['dat']['dat'])) return 'Đạt';
	$thieu = isset($sum['dat']['thieu']) ? $sum['dat']['thieu'] : array();
	return $thieu ? implode("\n", $thieu) : 'Chưa đủ';
}

/**
 * Tổng hợp trạng thái 4 module của một học viên.
 * Yêu cầu dat.php đã được nạp (dùng dt_dat_aggregate).
 * @return array trạng thái từng module + kết luận đủ/chưa đủ điều kiện.
 */
function dt_student_summary($hv)
{
	global $d;
	$idKhoa = (int)$hv['id_khoa'];
	$cccd = $hv['cccd'];
	$maHv = $hv['ma_hv'];

	// Lý thuyết: nâng hạng (C/CE) cần 4 môn check; còn lại cần 7 môn.
	$monSet = dt_mon_lythuyet_for_hang($hv['hang']);
	$tongMon = count($monSet);
	$ltRows = $d->rawQuery("select mon, tien_do, diem_kt from #_dt_lythuyet where id_khoa = ? and cccd = ?", array($idKhoa, $cccd));
	$ltByMon = array();
	foreach($ltRows as $rw) $ltByMon[$rw['mon']] = dt_lythuyet_dat($rw['tien_do'], $rw['diem_kt'], $rw['mon']);
	$mophong = $d->rawQueryOne("select tien_do from #_dt_mophong where id_khoa = ? and cccd = ? limit 0,1", array($idKhoa, $cccd));
	if($mophong) $ltByMon['mophong'] = dt_lythuyet_dat($mophong['tien_do'], 0, 'mophong');
	$soDat = 0; $soCo = 0;
	foreach($monSet as $mk => $lbl)
	{
		if(isset($ltByMon[$mk])) { $soCo++; if($ltByMon[$mk] === 1) $soDat++; }
	}
	$lyThuyetDat = ($soDat >= $tongMon) ? 1 : 0;

	// Cabin — nâng hạng (C = B lên C, CE) mặc định không yêu cầu học cabin.
	$cabinYeuCau = dt_is_nang_hang($hv['hang']) ? 0 : 1;
	$cabin = $d->rawQueryOne("select dat from #_dt_cabin_kq where id_khoa = ? and ma_hv = ? limit 0,1", array($idKhoa, $maHv));
	$cabinDat = ($cabin && (int)$cabin['dat'] === 1) ? 1 : 0;
	$cabinCo = $cabin ? 1 : 0;

	// Thực hành trong hình
	$hinh = $d->rawQueryOne("select gio, km, dat_override from #_dt_thuchanh_hinh where id_khoa = ? and cccd = ? limit 0,1", array($idKhoa, $cccd));
	$hinhDat = $hinh ? dt_hinh_dat($hv['hang'], $hinh['gio'], $hinh['km'], $hinh['dat_override']) : 0;

	// DAT
	$agg = function_exists('dt_dat_aggregate') ? dt_dat_aggregate($cccd, $idKhoa) : array('a'=>0,'b'=>0,'c'=>0,'d'=>0,'e'=>0);
	list($datDat, $datThieu) = dt_dat_danhgia($hv['hang'], $agg);
	if(dt_dat_override_get($idKhoa, $cccd) === 1) { $datDat = 1; $datThieu = array(); }

	$duDieuKien = ($lyThuyetDat && ($cabinDat || !$cabinYeuCau) && $hinhDat && $datDat) ? 1 : 0;
	return array(
		'ly_thuyet' => array('dat' => $lyThuyetDat, 'so_dat' => $soDat, 'so_co' => $soCo, 'tong' => $tongMon),
		'cabin' => array('dat' => $cabinDat, 'co' => $cabinCo, 'yeu_cau' => $cabinYeuCau),
		'hinh' => array('dat' => $hinhDat, 'gio' => $hinh ? (float)$hinh['gio'] : 0, 'km' => $hinh ? (float)$hinh['km'] : 0),
		'dat' => array('dat' => $datDat, 'agg' => $agg, 'thieu' => $datThieu),
		'du_dieu_kien' => $duDieuKien,
	);
}

/* Quyền: giống mẫu cabin_permission_denied. */
function dt_permission_denied($permissions = array())
{
	global $func, $login_admin;
	if(!$func->check_permission()) return false;
	if(!isset($_SESSION[$login_admin]['active']) || $_SESSION[$login_admin]['active'] != true) return true;
	if(!isset($_SESSION['list_quyen']) || !is_array($_SESSION['list_quyen'])) return true;
	foreach($permissions as $permission)
		if(in_array($permission, $_SESSION['list_quyen'])) return false;
	return true;
}

/* Admin/root (tài khoản 'admin' hoặc dev) — được phép bỏ qua ràng buộc cam kết khi thanh lý. */
function dt_is_admin()
{
	global $func;
	return !$func->check_permission();
}

/* Thông tin hết hạn theo ngày (dd/mm/yyyy hoặc Y-m-d). Cảnh báo khi còn dưới 30 ngày.
 * @return array|null [status: con_han|sap_het|het_han, days: số ngày (âm nếu đã hết hạn), ts]. */
function dt_expiry_info($dateStr, $warnDays = 30)
{
	$dateStr = trim((string)$dateStr);
	if($dateStr === '') return null;
	$ts = false;
	if(preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})/', $dateStr, $m)) $ts = mktime(0, 0, 0, (int)$m[2], (int)$m[1], (int)$m[3]);
	elseif(preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $dateStr, $m)) $ts = mktime(0, 0, 0, (int)$m[2], (int)$m[3], (int)$m[1]);
	if($ts === false || $ts === -1) return null;
	$today = mktime(0, 0, 0, (int)date('n'), (int)date('j'), (int)date('Y'));
	$days = (int)floor(($ts - $today) / 86400);
	$status = $days < 0 ? 'het_han' : ($days < $warnDays ? 'sap_het' : 'con_han');
	return array('status' => $status, 'days' => $days, 'ts' => $ts);
}

/* Cam kết gắn bó của giáo viên: từ số năm + ngày bắt đầu, tính ngày kết thúc và thời hạn còn lại.
 * @return array|null [con_hieu_luc(bool), nam(int), tu_ngay, den_ngay, con_ngay, con_text]. */
function dt_cam_ket_info($nam, $tuNgay)
{
	$nam = (int)$nam;
	$tuNgay = trim((string)$tuNgay);
	if($nam <= 0) return null;
	$start = false;
	if(preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})/', $tuNgay, $m)) $start = mktime(0, 0, 0, (int)$m[2], (int)$m[1], (int)$m[3]);
	elseif(preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $tuNgay, $m)) $start = mktime(0, 0, 0, (int)$m[2], (int)$m[3], (int)$m[1]);
	if($start === false || $start === -1) return array('con_hieu_luc' => true, 'nam' => $nam, 'tu_ngay' => '', 'den_ngay' => '', 'con_ngay' => null, 'con_text' => $nam.' năm');
	$end = mktime(0, 0, 0, (int)date('n', $start), (int)date('j', $start), (int)date('Y', $start) + $nam);
	$today = mktime(0, 0, 0, (int)date('n'), (int)date('j'), (int)date('Y'));
	$conNgay = (int)floor(($end - $today) / 86400);
	$conHieuLuc = $conNgay > 0;
	if($conNgay <= 0) $conText = 'Hết cam kết';
	elseif($conNgay >= 365) $conText = round($conNgay / 365, 1).' năm';
	else $conText = $conNgay.' ngày';
	return array('con_hieu_luc' => $conHieuLuc, 'nam' => $nam, 'tu_ngay' => date('d/m/Y', $start), 'den_ngay' => date('d/m/Y', $end), 'con_ngay' => $conNgay, 'con_text' => $conText);
}
