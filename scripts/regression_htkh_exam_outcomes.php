<?php
if(PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
define('SOURCES', dirname(__DIR__).'/admin/sources/');
define('LIBRARIES', dirname(__DIR__).'/libraries/');
require SOURCES.'daotao/helpers.php';
require SOURCES.'daotao/htkh.php';

class HtkhOutcomeTestDatabase
{
	public $rows = array();
	public function rawQuery($sql, $params) { return $this->rows; }
}

$d = new HtkhOutcomeTestDatabase();
$d->rows = array(
	array('id_khoa'=>1,'cccd'=>'1','hoten'=>'Passed via conclusion','ket_qua_tong'=>'','ket_luan'=>'Đạt'),
	array('id_khoa'=>1,'cccd'=>'2','hoten'=>'Passed via total result','ket_qua_tong'=>'Đậu','ket_luan'=>''),
	array('id_khoa'=>1,'cccd'=>'3','hoten'=>'Failed via negative conclusion','ket_qua_tong'=>'Đạt','ket_luan'=>'Không đạt'),
	array('id_khoa'=>1,'cccd'=>'4','hoten'=>'Absent','ket_qua_tong'=>'Vắng','ket_luan'=>''),
	array('id_khoa'=>1,'cccd'=>'5','hoten'=>'No result text','ket_qua_tong'=>'','ket_luan'=>''),
);
$passed = dt_htkh_ky_passed(1);
$names = array_column($passed, 'hoten');
if($names !== array('Passed via conclusion','Passed via total result'))
{
	fwrite(STDERR, 'Unexpected passed list: '.json_encode($names, JSON_UNESCAPED_UNICODE)."\n");
	exit(1);
}
echo "PASS: HTKH recognizes Đạt/Đậu from either imported result field and excludes failures, absences and blanks\n";