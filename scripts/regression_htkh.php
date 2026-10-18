<?php
/* Run inside the web container: php scripts/regression_htkh.php [output-directory] */
if(PHP_SAPI !== 'cli')
{
	http_response_code(403);
	exit('CLI only');
}
define('SOURCES', dirname(__DIR__).'/admin/sources/');
define('LIBRARIES', dirname(__DIR__).'/libraries/');
require SOURCES.'daotao/helpers.php';
require SOURCES.'daotao/export.php';
require SOURCES.'daotao/htkh.php';

function htkh_check($condition, $message)
{
	if(!$condition) throw new RuntimeException($message);
}

function dt_audit($action, $section, $count, $detail) {}
function dt_can($section, $action) { return true; }

class HtkhTransfer extends RuntimeException
{
	public $success;
	public $back;
	public function __construct($message, $back, $success)
	{
		parent::__construct($message);
		$this->back = $back;
		$this->success = $success;
	}
}

class HtkhTestFunctions
{
	public function transfer($message, $back, $success = true)
	{
		throw new HtkhTransfer($message, $back, $success);
	}
}

class HtkhTestDatabase
{
	public $students = array();
	public $certificates = array();
	public $failSave = false;
	public $rolledBack = false;
	private $snapshot;
	private $whereId;

	public function rawQuery($sql, $params = array())
	{
		if(strpos($sql, 'from #_dt_ky_thi_hv') === false) throw new RuntimeException('Unexpected query: '.$sql);
		return array_values(array_filter($this->students, function($row) use ($sql, $params) {
			return $row['id_ky'] === $params[0] && (strpos($sql, 'co_ket_qua = 1') === false || $row['co_ket_qua'] === 1);
		}));
	}

	public function rawQueryOne($sql, $params = array())
	{
		if(strpos($sql, 'from #_dt_ky_thi where') !== false)
			return $params[0] === 7 ? array('id' => 7, 'ten' => 'Kỳ thử', 'ngay_kt' => '2026-10-17') : null;
		if(strpos($sql, 'from #_dt_htkh') !== false)
			return $this->certificates[$params[0].'|'.$params[1]] ?? null;
		if(strpos($sql, 'from #_dt_khoa') !== false)
			return array('ma_khoa' => 'K'.$params[0], 'ten_khoa' => 'Khóa '.$params[0], 'thoi_gian_hoc' => '', 'ngay_khaigiang' => '2026-06-01', 'ngay_manhoa' => '2026-10-16');
		throw new RuntimeException('Unexpected query: '.$sql);
	}

	public function startTransaction() { $this->snapshot = $this->certificates; }
	public function commit() { return true; }
	public function rollback() { $this->certificates = $this->snapshot; $this->rolledBack = true; }
	public function where($key, $value) { $this->whereId = $value; }
	public function update($table, $data)
	{
		if($this->failSave) return false;
		$data['id'] = $this->whereId;
		$this->certificates[$data['id_khoa'].'|'.$data['cccd']] = $data;
		return true;
	}
	public function insert($table, $data)
	{
		if($this->failSave) return false;
		$data['id'] = count($this->certificates) + 1;
		$this->certificates[$data['id_khoa'].'|'.$data['cccd']] = $data;
		return $data['id'];
	}
}

function htkh_expect_transfer($callback, $success)
{
	try { $callback(); }
	catch(HtkhTransfer $result)
	{
		htkh_check($result->success === $success, 'Unexpected transfer: '.$result->getMessage());
		return $result;
	}
	throw new RuntimeException('Expected a user-facing message');
}

$d = new HtkhTestDatabase();
$func = new HtkhTestFunctions();
foreach(array(
	array(101, 'Đạt', 1), array(101, 'ĐẠT', 1), array(102, 'Đạt', 1),
	array(101, 'Không đạt', 1), array(101, 'Chưa đạt', 1), array(101, 'Đạt', 0),
	array(0, 'Đạt', 1), array(0, 'Đạt', 1),
) as $i => $fixture)
{
	$d->students[] = array(
		'id_ky' => 7, 'id_khoa' => $fixture[0], 'cccd' => '000000000'.str_pad($i, 3, '0', STR_PAD_LEFT),
		'ma_hv' => 'TEST-'.$i, 'hoten' => 'Học viên thử '.$i.' & <A>', 'nam_sinh' => '01/02/1990',
		'hang' => $i === 1 ? 'CE' : 'B11', 'ma_khoa' => $fixture[0] ? 'K'.$fixture[0] : 'CU-'.$i,
		'ten_khoa' => 'Khóa thử '.$fixture[0], 'ket_luan' => $fixture[1], 'co_ket_qua' => $fixture[2],
	);
}
$d->students[] = array_merge($d->students[0], array('id_ky' => 8, 'id_khoa' => 999));
$options = dt_htkh_ky_khoa_options(7);
htkh_check(count($options) === 4, 'Course options must include two separate legacy courses and exclude other periods');
htkh_check(count(dt_htkh_ky_passed(7)) === 5, 'Only passed students with results are eligible');
htkh_check(count(dt_htkh_ky_passed(7, 'id:101')) === 2, 'Course filter must match exactly');
htkh_check(count(dt_htkh_ky_passed(7, dt_htkh_khoa_key($d->students[6]))) === 1, 'Legacy course filter must not include all id_khoa=0 rows');
echo "[PASS] Period, course and passed-student filtering\n";

$_REQUEST = array('id_ky' => 7, 'ky_khoa' => 'id:999');
htkh_expect_transfer('dt_htkh_ky_selection', false);
$_REQUEST = array('id_ky' => 999);
htkh_expect_transfer('dt_htkh_ky_selection', false);
$_REQUEST = array('id_ky' => 7, 'ky_khoa' => 'id:101');
htkh_expect_transfer('dt_htkh_ky_documents', false);
$_POST = array('nguoi_ky' => 'Nguyễn Văn Thử & Cộng sự', 'ngay_ky' => '31/02/2026', 'chuc_danh' => 'kt_pgd', 'hang_ce' => 'd2');
htkh_expect_transfer('dt_htkh_cap_giay_ky', false);
htkh_check(!$d->certificates, 'Invalid date must not write certificates');
$_POST['ngay_ky'] = '18/10/2026';
$_POST['nguoi_ky'] = '';
htkh_expect_transfer('dt_htkh_cap_giay_ky', false);
$_POST['nguoi_ky'] = 'Nguyễn Văn Thử & Cộng sự';
$result = htkh_expect_transfer('dt_htkh_cap_giay_ky', true);
htkh_check(count($d->certificates) === 2, 'Issuing must not affect other courses, failed students or pending results');
htkh_check(strpos($result->back, 'id_ky=7') !== false && strpos($result->back, 'ky_khoa=id%3A101') !== false, 'Issuing must preserve both filters');
foreach($d->certificates as $certificate)
{
	htkh_check($certificate['ngay_ky'] === '18/10/2026' && $certificate['nguoi_ky'] === $_POST['nguoi_ky'], 'Signature information must be persisted');
	htkh_check($certificate['hang_ce'] === 'd2' && $certificate['chuc_danh'] === 'kt_pgd', 'Signing role and CE type must be persisted');
}
$key = '101|'.$d->students[0]['cccd'];
$d->certificates[$key]['so_giay'] = '0000123 - B';
htkh_expect_transfer('dt_htkh_cap_giay_ky', true);
htkh_check($d->certificates[$key]['so_giay'] === '0000123 - B', 'Existing certificate numbers must be retained');
$before = $d->certificates;
$d->failSave = true;
htkh_expect_transfer('dt_htkh_cap_giay_ky', false);
htkh_check($d->rolledBack && $d->certificates === $before, 'A failed save must rollback and report an error');
$d->failSave = false;
echo "[PASS] Validation, filtered issuance, signature persistence, existing numbers and rollback\n";

list($ky, $documents) = dt_htkh_ky_documents();
htkh_check(count($documents) === 2, 'Document selection must match the course filter');
htkh_check($documents[1]['hang'] === 'D2 lên CE', 'CE selection must reach certificate output');
htkh_check($documents[0]['thoi_gian'] === '01/06/2026 - 16/10/2026', 'Training dates must reach certificate output');
htkh_check($documents[0]['nguoi_ky'] === $_POST['nguoi_ky'], 'Vietnamese signature must round-trip');
$_REQUEST['ky_khoa'] = dt_htkh_khoa_key($d->students[6]);
htkh_expect_transfer('dt_htkh_cap_giay_ky', true);
list($legacyKy, $legacyDocuments) = dt_htkh_ky_documents();
htkh_check(count($legacyDocuments) === 1 && $legacyDocuments[0]['hoten'] === $d->students[6]['hoten'], 'Legacy issuance and output must target only the selected legacy course');
htkh_check(count($d->certificates) === 3, 'Legacy issuance must not issue certificates for other legacy courses');
$_REQUEST['ky_khoa'] = '';
list($allKy, $allDocuments) = dt_htkh_ky_documents();
htkh_check(count($allDocuments) === 3, 'All-courses output must include only issued certificates');
$_REQUEST['ky_khoa'] = 'id:101';
echo "[PASS] Legacy course issuance and all-courses issued-only output\n";
$html = dt_htkh_documents_html($documents, dt_htkh_ky_back());
htkh_check(substr_count($html, '<div class="cert">') === 2, 'Print must contain one certificate per student');
htkh_check(strpos($html, '&amp; &lt;A&gt;') !== false, 'HTML output must escape student text');

$providedDirectory = $argv[1] ?? '';
$directory = $providedDirectory;
if($directory === '')
{
	$directory = tempnam(sys_get_temp_dir(), 'htkh_regression_');
	if($directory === false || !unlink($directory) || !mkdir($directory)) throw new RuntimeException('Cannot create temporary output directory');
}
htkh_check(is_dir($directory), 'Output directory does not exist');
try
{
	$book = dt_htkh_documents_xlsx($documents);
	$sheet = $book->getActiveSheet();
	$rowsPerPage = count(dt_htkh_document_rows($documents[0]));
	$pageHeight = 0;
	foreach(dt_htkh_document_rows($documents[0]) as $row) $pageHeight += $row['height'] ?? 25;
	htkh_check($pageHeight < (297 - 40) * 72 / 25.4, 'Certificate row heights must fit the printable height of an A4 page');
	htkh_check(count($sheet->getBreaks()) === 1, 'Excel needs one explicit page break for two students');
	htkh_check(isset($sheet->getBreaks()['A'.$rowsPerPage]), 'Excel page break must fall between certificates');
	htkh_check($sheet->getCell('A6')->getValue() === 'Số: 0000123 - B', 'Excel must preserve leading zeroes in certificate numbers');
	htkh_check($sheet->getCell('A6')->getDataType() === PHPExcel_Cell_DataType::TYPE_STRING, 'Excel certificate values must be explicit strings');
	htkh_check($sheet->getPageSetup()->getFitToWidth() === 1 && $sheet->getPageSetup()->getFitToHeight() === 0, 'Excel must fit each certificate horizontally, not squeeze all certificates onto one page');
	PHPExcel_IOFactory::createWriter($book, 'Excel2007')->save($directory.'/certificates.xlsx');
	$loaded = PHPExcel_IOFactory::load($directory.'/certificates.xlsx');
	htkh_check($loaded->getActiveSheet()->getCell('E19')->getValue() === $_POST['nguoi_ky'], 'Saved XLSX must retain Vietnamese signature');
	dt_htkh_documents_docx_save($documents, $directory.'/certificates.docx');
	$zip = new ZipArchive();
	htkh_check($zip->open($directory.'/certificates.docx') === true, 'DOCX must be a real ZIP package');
	$wordXml = $zip->getFromName('word/document.xml');
	htkh_check(simplexml_load_string($wordXml) !== false, 'Word document XML must be well formed');
	htkh_check(substr_count($wordXml, '<w:br w:type="page"/>') === 1, 'Word needs exactly one page break between two certificates');
	htkh_check(substr_count($wordXml, '<w:tbl>') === 2, 'Word must include exactly one certificate table per student');
	htkh_check(strpos($wordXml, 'Nguyễn Văn Thử &amp; Cộng sự') !== false, 'Word must retain correct UTF-8 Vietnamese');
	htkh_check(strpos($wordXml, 'w:w="11906" w:h="16838"') !== false, 'Word paper must be A4');
	$zip->close();
	file_put_contents($directory.'/print.html', $html);
	$ds_ky = array(array('id' => 7, 'ten' => 'Kỳ thử', 'ngay_kt' => '2026-10-17', 'so_kq' => 5));
	$htkh_ky_khoa = $options;
	$htkh_ky_passed = dt_htkh_ky_passed(7, 'id:101');
	$ds_khoa = $items = array();
	$id_khoa_sel = $total_items = 0;
	ob_start();
	require dirname(__DIR__).'/admin/templates/daotao/htkh/items_tpl.php';
	$template = ob_get_clean();
	htkh_check(strpos($template, 'ky_khoa=id%3A101') !== false, 'Export and print links must retain the course filter');
	htkh_check(strpos($template, 'name="ky_khoa" value="id:101"') !== false, 'Issuing form must retain the course filter');
	htkh_check(strpos($template, 'Học viên thử 2 &amp; &lt;A&gt;') === false, 'Preview must exclude other courses');
	file_put_contents($directory.'/admin.html', '<!doctype html><html lang="vi"><head><meta charset="utf-8"><link rel="stylesheet" href="http://localhost:8080/admin/assets/css/adminlte.css"></head><body>'.$template.'</body></html>');
	$id_khoa_sel = 101;
	ob_start();
	require dirname(__DIR__).'/admin/templates/daotao/htkh/items_tpl.php';
	$courseTemplate = ob_get_clean();
	$dom = new DOMDocument();
	$previousErrors = libxml_use_internal_errors(true);
	$dom->loadHTML('<?xml encoding="UTF-8">'.$courseTemplate);
	libxml_clear_errors();
	libxml_use_internal_errors($previousErrors);
	$xpath = new DOMXPath($dom);
	$courseForm = $xpath->query('//form[contains(@action, "act=htkhCapGiay&")]')->item(0);
	htkh_check($courseForm !== null, 'Course issuance form must remain available');
	htkh_check(strpos($courseForm->getAttribute('class'), 'form-inline') === false, 'Course issuance must use the responsive grouped layout');
	foreach(array('nguoi-ky', 'ngay-ky', 'chuc-danh', 'hang-ce', 'tat-ca') as $field)
		htkh_check($xpath->query('.//label[@for="htkh-khoa-'.$field.'"]', $courseForm)->length === 1, 'Course field must have an associated visible label: '.$field);
	htkh_check(!$xpath->query('.//input[@name="tat_ca"]', $courseForm)->item(0)->hasAttribute('checked'), 'All-student override must remain off by default');
	htkh_check(strpos($courseForm->getAttribute('onsubmit'), 'this.elements.tat_ca.checked') !== false, 'Confirmation must distinguish the all-student override');
	file_put_contents($directory.'/course.html', '<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="http://localhost:8080/admin/assets/css/adminlte.css"></head><body><main class="p-3">'.$dom->saveHTML($courseForm).'</main></body></html>');
	echo "[PASS] Responsive course issuance layout, field labels and default eligibility scope\n";
	echo "[PASS] Shared document content, native XLSX/DOCX, Unicode, page-break settings and template filters\n";
	if($providedDirectory !== '') echo "Output: ".$directory."\n";
}
finally
{
	if($providedDirectory === '')
	{
		foreach(array('certificates.xlsx', 'certificates.docx', 'print.html', 'admin.html', 'course.html') as $name)
			if(is_file($directory.'/'.$name)) unlink($directory.'/'.$name);
		rmdir($directory);
	}
}
