<?php
/* Ngày kiểm tra: import/xuất cùng mẫu TTSH, lấy mã khóa từ Đào tạo, xóa kết quả/số giấy trong kỳ.
 * docker compose exec -T web php scripts/regression_kythi_ttsh.php --local-db /tmp/ttsh.xls */
if(PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
if(($argv[1] ?? '') !== '--local-db' || empty($argv[2])) exit("Usage: php scripts/regression_kythi_ttsh.php --local-db /path/to/Mau_TTSH.xls\n");
register_shutdown_function(function() { $e = error_get_last(); if($e && in_array($e['type'], array(E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR), true)) fwrite(STDERR, "FATAL: {$e['message']} @ {$e['file']}:{$e['line']}\n"); });
define('LIBRARIES', dirname(__DIR__).'/libraries/');
define('SOURCES', dirname(__DIR__).'/admin/sources/');
$_SERVER['SERVER_NAME'] = 'localhost';
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
require SOURCES.'daotao/config_daotao.php';
require SOURCES.'daotao/helpers.php';
require SOURCES.'daotao/excel_open.php';
require SOURCES.'daotao/export.php';
require SOURCES.'daotao/khoa.php';
require SOURCES.'daotao/dat.php';
require SOURCES.'daotao/kythi.php';
require SOURCES.'daotao/htkh.php';

function dt_backup_tables($tables, $tag) { return 'temporary-table-regression'; }
function dt_audit($action, $entity, $affected, $detail) {}
function dt_log_import($module, $filename, $rows, $errors) {}
class TtshNotice extends RuntimeException { public $success; public function __construct($m, $s) { parent::__construct($m); $this->success = $s; } }
class TtshFunc { public function transfer($m, $u, $s = true) { throw new TtshNotice($m, $s); } }
function dt_import_notice($m, $u, $s = true) { throw new TtshNotice($m, $s); }
function check($cond, $msg) { if(!$cond) throw new RuntimeException('FAIL: '.$msg); echo "ok  - $msg\n"; }
function run_action($fn) { try { $fn(); } catch(TtshNotice $n) { if(!$n->success) throw new RuntimeException('Action failed: '.$n->getMessage()); return $n->getMessage(); } throw new RuntimeException('Action returned without notice'); }
set_exception_handler(function($e) { fwrite(STDERR, $e->getMessage()."\n".$e->getTraceAsString()."\n"); exit(1); });

$login_admin = 'ttsh_test'; $_SESSION = array('ttsh_test' => array('username' => 'ttsh_test')); $func = new TtshFunc();
if(!$d->rawQueryOne("select column_name from information_schema.columns where table_schema = database() and table_name = 'table_dt_ky_thi_hv' and column_name = 'dl_daotao' limit 0,1"))
	exit("Run the admin once (migration kythi_ttsh_v1) before this regression.\n");
foreach(array('dt_ky_thi_hv','dt_ky_thi','dt_hocvien','dt_khoa','dt_htkh') as $table)
{
	$def = $d->rawQuery('show create table table_'.$table);
	$sql = preg_replace('/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $def[0]['Create Table']);
	$d->rawQuery(preg_replace('/ AUTO_INCREMENT=\d+/', '', $sql));
}
$file = $argv[2];
$d->rawQuery("insert into #_dt_khoa (ma_khoa, ten_khoa, hang) values ('SYS-K123', 'K123-B(STD) he thong', 'B11')");
$khoaId = (int)$d->getLastInsertId();
$d->rawQuery("insert into #_dt_hocvien (id_khoa, ma_hv, cccd, hoten, hang, gv_hoten) values (?, 'SYS-HV-1', '075304024872', 'PHẠM NGUYỄN HỒNG ÁNH', 'B11', 'Giáo viên Hệ thống')", array($khoaId));
$d->rawQuery("insert into #_dt_ky_thi (ten, ngay_kt) values ('TTSH regression', '2026-11-07')");
$kyId = (int)$d->getLastInsertId();
$ky = dt_kythi_load($kyId);

// 1. Import danh sách bằng file mẫu TTSH -> mã khóa lấy từ hệ thống khi có, còn lại theo file.
$_REQUEST = array('id' => $kyId); $_POST = array('roster_mode' => 'replace');
$_FILES = array('file-excel' => array('name' => basename($file), 'tmp_name' => $file, 'error' => 0));
echo run_action('dt_kythi_import_roster'), "\n";
$total = (int)$d->rawQueryOne("select count(*) c from #_dt_ky_thi_hv where id_ky = ?", array($kyId))['c'];
check($total >= 150, "roster imported $total rows");
check((int)$d->rawQueryOne("select count(*) c from #_dt_ky_thi_hv where id_ky = ? and ma_khoa = ''", array($kyId))['c'] === 0, 'every roster row has Mã khóa');
$anh = $d->rawQueryOne("select * from #_dt_ky_thi_hv where id_ky = ? and cccd = '075304024872'", array($kyId));
check($anh['ma_khoa'] === 'SYS-K123' && $anh['ten_khoa'] === 'K123-B(STD) he thong' && $anh['hang'] === 'B11' && $anh['giaovien'] === 'Giáo viên Hệ thống' && (int)$anh['id_khoa'] === $khoaId, 'system course/rank/teacher win over file');
check((int)$d->rawQueryOne("select count(*) c from #_dt_ky_thi_hv where id_ky = ? and co_ket_qua = 1", array($kyId))['c'] === 0, 'roster import does not mark results');

// 2. Import kết quả mẫu TTSH.
$_POST = array();
echo run_action('dt_kythi_import_excel'), "\n";
$bao = $d->rawQueryOne("select * from #_dt_ky_thi_hv where id_ky = ? and hoten = 'VÕ GIA BẢO'", array($kyId));
check($bao['diem_lt'] === '24' && $bao['lt'] === 'Không đạt' && $bao['ket_luan'] === 'Không đạt' && (int)$bao['co_ket_qua'] === 1, 'KQ LT / Kết luận CSĐT imported');
$snap = json_decode($bao['dl_daotao'], true);
check(($snap['p_lt'] ?? '') === 'Đạt' && (string)($snap['km_d'] ?? '') === '832.433', 'training columns kept for student not in system');
$dat = $d->rawQueryOne("select * from #_dt_ky_thi_hv where id_ky = ? and hoten = 'TĂNG CHÍ ĐẠT'", array($kyId));
check($dat['lt'] === 'Vắng' && dt_kythi_result_status($dat) === 'vang', 'absent student classified vắng');
$anh = $d->rawQueryOne("select * from #_dt_ky_thi_hv where id_ky = ? and cccd = '075304024872'", array($kyId));
check($anh['diem_hinh'] === '85' && $anh['hinh'] === 'Đạt' && $anh['ket_luan'] === 'Đạt' && $anh['ma_khoa'] === 'SYS-K123', 'scores KT hình + system course after result import');
$before = $d->rawQuery("select cccd, ".implode(',', dt_kythi_result_fields()).", co_ket_qua from #_dt_ky_thi_hv where id_ky = ? order by cccd", array($kyId));

// 3. Xuất mẫu TTSH -> header giống hệt file import.
$rows = $d->rawQuery("select * from #_dt_ky_thi_hv where id_ky = ? order by hoten", array($kyId));
$xls = dt_kythi_ttsh_build($ky, $rows);
$out = sys_get_temp_dir().'/ttsh_roundtrip_'.getmypid().'.xls';
PHPExcel_IOFactory::createWriter($xls, 'Excel5')->save($out);
$src = PHPExcel_IOFactory::createReader('Excel5')->load($file)->getSheet(0);
$exp = PHPExcel_IOFactory::createReader('Excel5')->load($out)->getSheet(0);
$same = true;
for($c = 0; $c < 36; $c++) if(trim((string)$src->getCellByColumnAndRow($c, 2)->getValue()) !== trim((string)$exp->getCellByColumnAndRow($c, 2)->getValue())) { $same = false; echo "  col $c differs\n"; }
check($same, 'exported header row equals TTSH template header row');
check($exp->getCell('A1')->getValue() === 'Tốt nghiệp 07/11/2026', 'title row');

// 4. Xóa kết quả trong kỳ -> import lại file vừa xuất -> kết quả khôi phục y hệt.
$_POST = array('id' => $kyId);
echo run_action('dt_kythi_clear_results'), "\n";
check((int)$d->rawQueryOne("select count(*) c from #_dt_ky_thi_hv where id_ky = ? and (co_ket_qua = 1 or lt <> '' or ket_luan <> '')", array($kyId))['c'] === 0, 'results cleared, roster kept');
check((int)$d->rawQueryOne("select count(*) c from #_dt_ky_thi_hv where id_ky = ?", array($kyId))['c'] === $total, 'roster count unchanged');
$_POST = array(); $_FILES['file-excel'] = array('name' => 'roundtrip.xls', 'tmp_name' => $out, 'error' => 0);
echo run_action('dt_kythi_import_excel'), "\n";
$after = $d->rawQuery("select cccd, ".implode(',', dt_kythi_result_fields()).", co_ket_qua from #_dt_ky_thi_hv where id_ky = ? order by cccd", array($kyId));
$diff = 0;
foreach($before as $i => $b) if($b != $after[$i]) { $diff++; if($diff < 4) echo '  diff ', json_encode(array($b, $after[$i]), JSON_UNESCAPED_UNICODE), "\n"; }
check($diff === 0, 'export -> import round-trip preserves all result fields');
@unlink($out);

// 5. Lấy mã khóa từ hệ thống.
$d->rawQuery("update #_dt_ky_thi_hv set ma_khoa = '', ten_khoa = '', hang = 'B(STD)' where id_ky = ? and cccd = '075304024872'", array($kyId));
$_POST = array('id' => $kyId);
echo run_action('dt_kythi_sync_khoa'), "\n";
$anh = $d->rawQueryOne("select * from #_dt_ky_thi_hv where id_ky = ? and cccd = '075304024872'", array($kyId));
check($anh['ma_khoa'] === 'SYS-K123' && $anh['hang'] === 'B11', 'sync restores system course');

// 6. Xóa số giấy xác nhận chỉ của học viên trong kỳ.
$d->rawQuery("insert into #_dt_htkh (id_khoa, cccd, so_giay) values (?, '075304024872', 'G1'), (0, ?, 'G2'), (999, '000000000999', 'KHAC')", array($khoaId, $bao['cccd']));
check(dt_kythi_htkh_count($kyId) === 2, 'htkh count in period');
echo run_action('dt_kythi_clear_htkh'), "\n";
$left = $d->rawQuery("select so_giay from #_dt_htkh");
check(count($left) === 1 && $left[0]['so_giay'] === 'KHAC', 'only certificates of the period deleted');

$d->rawQuery("insert into #_dt_ky_thi (ten, ngay_kt) values ('Rank statistics', '2026-11-07')");
$rankKyId = (int)$d->getLastInsertId();
$d->rawQuery("insert into #_dt_ky_thi_hv (id_ky, cccd, hoten, hang, ma_khoa, ten_khoa, co_ket_qua, ket_luan) values
	(?, '001000000001', 'A', 'B11', 'K1', 'Name one', 1, 'Đạt'),
	(?, '001000000002', 'B', 'B(STD)', 'K1', 'Name variant', 1, 'Không đạt'),
	(?, '001000000003', 'C', 'B(TĐ)', 'K2', 'Name two', 0, ''),
	(?, '001000000004', 'D', 'C1', 'K3', 'Name three', 1, 'Đạt'),
	(?, '001000000005', 'E', '', '', '', 0, '')", array_fill(0, 5, $rankKyId));
$_REQUEST = array('id' => $rankKyId);
dt_kythi_detail();
check($total_items === 5 && count($result_rank_stats['B11']['khoa']) === 2, 'rank totals count distinct codes despite course name variants');
check($result_rank_stats['B11']['so_hv'] === 3 && $result_rank_stats['B11']['so_dat'] === 1, 'automatic B aliases share rank totals and pass count');
check($result_rank_stats['']['thieu_khoa'] === 1 && count($result_rank_stats['']['khoa']) === 0, 'unknown course does not inflate course count');
$_REQUEST['hang'] = 'B11';
dt_kythi_detail();
check($total_items === 3 && count($result_rank_stats) === 1 && count($result_stats) === 3, 'rank filter applies to roster and statistics');
$_REQUEST['ma_khoa'] = 'K1';
dt_kythi_detail();
check($total_items === 2 && count($result_rank_stats['B11']['khoa']) === 1, 'rank and course filters combine');
$_REQUEST = array('id' => $rankKyId, 'hang' => 'none');
dt_kythi_detail();
check($total_items === 1 && isset($result_rank_stats['']), 'missing rank filter works');
echo "ALL PASSED\n";
