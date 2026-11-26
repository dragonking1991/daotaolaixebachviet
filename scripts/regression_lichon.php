<?php
if(PHP_SAPI !== 'cli') exit('CLI only');
/* Kiểm tra thuật toán xếp lịch ôn sát hạch (lo_schedule) — không cần DB. */
require dirname(__DIR__).'/libraries/lichon_lib.php';
function lo_check($ok, $msg) { if(!$ok) throw new RuntimeException($msg); }
function lo_reg($id, $gv, $hang, $gio, $ngay = null, $bd = null, $kt = null)
{
	return array('id' => $id, 'id_gv' => $gv, 'hang' => $hang, 'so_gio' => $gio, 'uu_tien_ngay' => $ngay, 'uu_tien_gio_bd' => $bd, 'uu_tien_gio_kt' => $kt);
}
function lo_by_reg($res, $id) { return array_values(array_filter($res['new'], function($s) use ($id) { return $s['id_dangky'] === $id; })); }

lo_check(lo_parse_xe_list('21, 23-25 ,89') === array('21', '23', '24', '25', '89'), 'Parse xe list with ranges');
$err = '';
lo_check(lo_parse_xe_list('21, 30-20', $err) === false && $err !== '', 'Reject reversed range');
lo_check(lo_short_name('Võ Duy Minh') === 'Duy Minh', 'Short name uses the last two words');
lo_check(lo_short_name('Lê Trung Cường', array('Trung Cường')) === 'Trung Cường L', 'Disambiguate duplicate two-word name');
lo_check(lo_short_name('Minh') === 'Minh', 'Single-word name is kept');

$cfg = array(
	array('id' => 1, 'ngay' => '2026-11-23', 'hang' => 'B số sàn', 'gio_bd' => 7, 'gio_kt' => 17, 'xe' => array('21', '23')),
	array('id' => 2, 'ngay' => '2026-11-23', 'hang' => 'B tự động', 'gio_bd' => 7, 'gio_kt' => 19, 'xe' => array('82')),
	array('id' => 3, 'ngay' => '2026-11-24', 'hang' => 'B số sàn', 'gio_bd' => 7, 'gio_kt' => 17, 'xe' => array('21')),
);

/* Liên tục giữa các hạng trong cùng ngày. */
$res = lo_schedule($cfg, array(lo_reg(1, 1, 'B số sàn', 4), lo_reg(2, 1, 'B tự động', 2)), array(), 3);
$hours = array_map(function($s) { return $s['gio']; }, $res['new']);
sort($hours);
lo_check(!$res['unplaced'] && $hours === range(7, 12), 'One GV across two classes must be a continuous block');

/* Ưu tiên ngày + khung giờ. */
$res = lo_schedule($cfg, array(lo_reg(1, 1, 'B số sàn', 3, '2026-11-24', 13, 17)), array(), 3);
foreach($res['new'] as $s) lo_check($s['id_ngay'] === 3 && $s['gio'] >= 13 && $s['gio'] < 17, 'Preference day/window must be respected');

/* Vượt sức chứa 1 ngày → song song tối đa 3h, phần còn lại sang ngày sau. */
$res = lo_schedule($cfg, array(lo_reg(1, 1, 'B số sàn', 16)), array(), 3);
$day1 = array_filter($res['new'], function($s) { return $s['id_ngay'] === 1; });
$day2 = array_filter($res['new'], function($s) { return $s['id_ngay'] === 3; });
lo_check(count($day1) === 13 && count($day2) === 3 && !$res['unplaced'], 'Overflow uses ≤3h parallel then next day');

/* GV chọn "tập trong 2 ngày" → không xếp song song, phần dư sang ngày sau. */
$reg = lo_reg(1, 1, 'B số sàn', 16);
$reg['xac_nhan'] = 'hai_ngay';
$res = lo_schedule($cfg, array($reg), array(), 3);
$day1 = array_filter($res['new'], function($s) { return $s['id_ngay'] === 1; });
$day2 = array_filter($res['new'], function($s) { return $s['id_ngay'] === 3; });
lo_check(count($day1) === 10 && count($day2) === 6 && !$res['unplaced'], 'Two-day choice must not schedule parallel hours');

/* Ô có sẵn được giữ nguyên, không xếp trùng ô. */
$existing = array(array('id_ngay' => 1, 'so_xe' => '21', 'gio' => 7, 'id_dangky' => 9));
$res = lo_schedule($cfg, array(lo_reg(9, 2, 'B số sàn', 1), lo_reg(1, 1, 'B số sàn', 10)), $existing, 0);
foreach($res['new'] as $s) lo_check(!($s['id_ngay'] === 1 && $s['so_xe'] === '21' && $s['gio'] === 7), 'Existing slot must not be overwritten');
lo_check(!lo_by_reg($res, 9), 'Already satisfied registration must not get more hours');

/* Hết chỗ → báo giờ chưa xếp. */
$res = lo_schedule(array($cfg[1]), array(lo_reg(1, 1, 'B tự động', 15)), array(), 0);
lo_check(($res['unplaced'][1] ?? 0) === 3, 'Unplaced hours must be reported');

/* Dữ liệu ưu tiên rỗng do PDODb ghi thành 0000-00-00 / 0 không được hiểu là yêu cầu. */
$n = lo_norm_prefs(array('uu_tien_ngay' => '0000-00-00', 'uu_tien_gio_bd' => '0', 'uu_tien_gio_kt' => '0'));
lo_check($n['uu_tien_ngay'] === null && $n['uu_tien_gio_bd'] === null && $n['uu_tien_gio_kt'] === null, 'Zero-filled preferences must mean no preference');
$n = lo_norm_prefs(array('uu_tien_ngay' => null, 'uu_tien_gio_bd' => '13', 'uu_tien_gio_kt' => '0'));
lo_check($n['uu_tien_gio_bd'] === 13 && $n['uu_tien_gio_kt'] === 24, 'Open-ended window must extend to 24h');
$res = lo_schedule($cfg, array(array('id' => 1, 'id_gv' => 1, 'hang' => 'B số sàn', 'so_gio' => 2, 'uu_tien_ngay' => '0000-00-00', 'uu_tien_gio_bd' => '0', 'uu_tien_gio_kt' => '0')), array(), 3);
lo_check(count($res['new']) === 2, 'Zero-filled preferences must not block scheduling');

echo "PASS lich on: zero-filled preferences, parse xe, two-word short names, continuity across classes, preferences, parallel cap + next day, two-day choice, existing slots, unplaced report\n";
