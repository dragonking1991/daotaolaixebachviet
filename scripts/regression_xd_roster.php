<?php
if(PHP_SAPI !== 'cli' || ($argv[1] ?? '') !== '--local-db')
	exit("Usage: php scripts/regression_xd_roster.php --local-db\n");
define('LIBRARIES', dirname(__DIR__).'/libraries/');
define('SOURCES', dirname(__DIR__).'/admin/sources/');
$_SERVER['SERVER_NAME'] = 'localhost';
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
require LIBRARIES.'PHPExcel.php';
foreach(array('helpers','classes','excel_cell','excel_stream','excel_convert','excel_open','hocvien_financial_import') as $module)
	require SOURCES.'xangdau/'.$module.'.php';
function roster_assert($condition, $message) { if(!$condition) throw new RuntimeException($message); }
function roster_rejects($callback, $message)
{
	try { $callback(); }
	catch(InvalidArgumentException $error) { return; }
	throw new RuntimeException($message);
}
$backups = array(); $temporaryFiles = array(); $failure = null; $login_admin = 'xd_test';
$_SESSION[$login_admin]['username'] = 'regression';
$definition = xd_roster_query('show create table #_xd_hocvien');
$sql = preg_replace('/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $definition[0]['Create Table']);
$sql = preg_replace('/ AUTO_INCREMENT=\d+/', '', $sql);
xd_roster_query($sql);
foreach(array('dt_khoa','dt_hocvien') as $table)
{
	$definition = xd_roster_query('show create table #_'.$table);
	$sql = preg_replace('/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $definition[0]['Create Table']);
	xd_roster_query(preg_replace('/ AUTO_INCREMENT=\d+/', '', $sql));
}
try
{
	$columns = xd_roster_query('show columns from #_xd_hocvien');
	$columns = array_column($columns, 'Field');
	foreach(xd_student_financial_fields() as $key => $label)
		if(!in_array($key, $columns, true)) xd_roster_query("alter table #_xd_hocvien add `$key` decimal(18,2) not null default 0");
	if(!in_array('ngay_nop_ho_so', $columns, true)) xd_roster_query('alter table #_xd_hocvien add ngay_nop_ho_so date null default null');
	$headers = array('STT','Mã đăng ký','Số Hồ sơ','Họ và tên','Ngày sinh','Giới tính','Quốc tịch','Số CMND/ HC',
		'Địa chỉ thường trú','Nơi cư trú','Ngày nhận Hồ sơ','Hệ đào tạo','Giáo viên','Xe',
		'Số tiền học phí','Miễn giảm','Số tiền đã đóng','Số tiền còn thiếu','Số tiền tạm ứng','Thanh toán Lương','Ngày nộp hồ sơ','Số điện thoại','Người nộp hồ sơ');
	$values = array(1,'REG-1','FILE-1','Regression Student','1993-10-24','','','099000000001','','','','CK','Teacher A','',
		17000000,1000,12000000,4999000,2000000,500000,'29/10/2026','','Submitter');
	$sheet = new XdArraySheet(array(1=>$headers, 2=>$values), 2, count($headers));
	list($header, $map) = xd_roster_header($sheet, 2, count($headers));
	roster_assert($map['hoc_phi'] === 14 && $map['mien_giam'] === 15 && $map['thanh_toan_luong_nguon'] === 19, 'Money columns are shifted');
	$rows = xd_roster_parse($sheet, 2, $map, $header, array('khoa'=>'XD-TEST'));
	roster_assert($rows[0]['financial']['mien_giam'] === 1000, 'VND amount was multiplied');
	roster_assert($rows[0]['ngay_nop_ho_so'] === '2026-10-29' && $map['ngay_nop_ho_so'] === 20, 'Submission date header or Vietnamese date parsing failed');
	$fallbackRows = $rows; $fallbackRows[0]['khoa'] = '';
	roster_rejects(function() use ($fallbackRows) { xd_roster_plan($fallbackRows); }, 'Missing training course accepted');
	xd_roster_query("insert into #_dt_khoa (id,ma_khoa,ten_khoa) values (1,'TRAIN-1','Training One'),(2,'','Training Two')");
	xd_roster_query("insert into #_dt_hocvien (id_khoa,cccd,hoten) values (1,'99000000001','  REGRESSION   STUDENT  ')");
	$fallbackPlan = xd_roster_plan($fallbackRows);
	roster_assert($fallbackPlan['rows'][0]['khoa'] === 'TRAIN-1', 'Name/CCCD normalized fallback failed');
	$mismatchRows = $fallbackRows; $mismatchRows[0]['ho_ten'] = 'Different Name';
	roster_rejects(function() use ($mismatchRows) { xd_roster_plan($mismatchRows); }, 'CCCD-only match accepted');
	$mismatchRows = $fallbackRows; $mismatchRows[0]['cccd'] = '099000000099';
	roster_rejects(function() use ($mismatchRows) { xd_roster_plan($mismatchRows); }, 'Name-only match accepted');
	xd_roster_query("insert into #_dt_hocvien (id_khoa,cccd,hoten) values (2,'99000000001','Regression Student')");
	roster_rejects(function() use ($fallbackRows) { xd_roster_save($fallbackRows); }, 'Conflicting training courses accepted');
	roster_assert(count(xd_roster_query('select id from #_xd_hocvien')) === 0, 'Ambiguous fallback wrote a student');
	roster_assert(xd_roster_plan($rows)['rows'][0]['khoa'] === 'XD-TEST', 'Explicit course lost priority');
	xd_roster_query('delete from #_dt_hocvien where id_khoa=1');
	$fallbackPlan = xd_roster_plan($fallbackRows);
	roster_assert($fallbackPlan['rows'][0]['khoa'] === 'Training Two', 'Blank training code did not use course name');
	$fallbackSaved = xd_roster_save($fallbackRows); $backups[] = $fallbackSaved['backup'];
	$storedFallback = xd_roster_query('select * from #_xd_hocvien');
	roster_assert(count($storedFallback) === 1 && $storedFallback[0]['khoa'] === 'Training Two', 'Fallback not persisted');
	xd_roster_query("update #_dt_khoa set ma_khoa='CHANGED' where id=2");
	roster_assert(xd_roster_plan($fallbackRows)['rows'][0]['khoa'] === 'Training Two', 'Existing XD course overwritten by training fallback');
	xd_roster_query('delete from #_xd_hocvien');
	echo "PASS training course fallback, normalized name/identity, explicit priority, mismatch, ambiguity rollback, persistence and existing course preservation\n";
	roster_assert(xd_roster_submission_date('2024-02-29') === '2024-02-29', 'ISO leap day rejected');
	foreach(array('10-07-2026'=>'2026-07-10','30-07-2026'=>'2026-07-30','03-08-2026'=>'2026-08-03','3-8-2026'=>'2026-08-03','29-02-2024'=>'2024-02-29') as $input => $expectedDate)
		roster_assert(xd_roster_submission_date($input) === $expectedDate, 'Excel-style day-month-year date rejected or reversed: '.$input);
	$dashValues = $values; $dashValues[20] = '10-07-2026';
	$dashSheet = new XdArraySheet(array(1=>$headers,2=>$dashValues),2,count($headers));
	$dashRows = xd_roster_parse($dashSheet,2,$map,1,array('khoa'=>'XD-TEST'));
	roster_assert($dashRows[0]['ngay_nop_ho_so'] === '2026-07-10', 'Roster did not parse the date shown in Excel');
	$serial = PHPExcel_Shared_Date::FormattedPHPToExcel(2026,10,29);
	roster_assert(xd_roster_submission_date($serial + 0.75) === '2026-10-29', 'Excel serial day shifted');
	foreach(array('31/02/2026','31-02-2026','29-02-2026','30-13-2026','10-07/2026','10/07-2026','2026-02-29','29/13/2026','2026-00-01','tomorrow','0','-1','#REF!') as $invalidDate)
		roster_rejects(function() use ($invalidDate) { xd_roster_submission_date($invalidDate); }, 'Invalid submission date accepted: '.$invalidDate);
	$receiptOnlyHeaders = $headers; unset($receiptOnlyHeaders[20]);
	$receiptOnlySheet = new XdArraySheet(array(1=>$receiptOnlyHeaders,2=>$values),2,count($headers));
	list($unusedHeader,$receiptMap) = xd_roster_header($receiptOnlySheet,2,count($headers));
	roster_assert(!isset($receiptMap['ngay_nop_ho_so']), 'Receipt date was mapped as submission date');
	$noDateRows = xd_roster_parse($receiptOnlySheet,2,$receiptMap,1,array('khoa'=>'XD-TEST'));
	roster_assert(!array_key_exists('ngay_nop_ho_so',$noDateRows[0]), 'Missing date column did not remain absent');
	$badDateValues = $values; $badDateValues[20] = '#REF!';
	$badDateSheet = new XdArraySheet(array(1=>$headers,2=>$badDateValues),2,count($headers));
	try
	{
		xd_roster_parse($badDateSheet,2,$map,1,array('khoa'=>'XD-TEST'));
		throw new RuntimeException('Cached date error was accepted');
	}
	catch(InvalidArgumentException $error)
	{
		roster_assert(strpos($error->getMessage(),'Dòng 2: Ngày nộp hồ sơ:') !== false, 'Date error lacks row and field context');
	}
	$sourceWorkbook = new PHPExcel();
	$sourceSheet = $sourceWorkbook->getActiveSheet();
	foreach(array(1=>$headers,2=>$values) as $rowNumber => $cells)
		foreach($cells as $column => $value) $sourceSheet->setCellValueByColumnAndRow($column,$rowNumber,$value);
	$sourceSheet->setCellValue('L2','="CK"');
	$sourceSheet->setCellValue('O2','=17000000');
	$sourceSheet->setCellValue('U2','='.$serial);
	foreach(array('xls'=>'Excel5','xlsx'=>'Excel2007') as $extension => $writerType)
	{
		$file = tempnam(sys_get_temp_dir(),'xd_roster_test_'); $temporaryFiles[] = $file;
		$writer = PHPExcel_IOFactory::createWriter($sourceWorkbook,$writerType);
		$writer->setPreCalculateFormulas(true); $writer->save($file);
		list($loadedWorkbook,$loadedSheet,$maxRow,$maxCol) = xd_open_upload_sheet(array('tmp_name'=>$file),$extension,'',array(),true);
		list($loadedHeader,$loadedMap) = xd_roster_header($loadedSheet,$maxRow,$maxCol);
		$loadedRows = xd_roster_parse($loadedSheet,$maxRow,$loadedMap,$loadedHeader,array('khoa'=>'XD-TEST'));
		roster_assert($loadedRows === $rows,'XLS/XLSX cached formula or roster round-trip mismatch: '.$extension);
		$dashWorkbook = new PHPExcel();
		$dashWorkbook->getActiveSheet()->fromArray(array($headers,$dashValues),null,'A1');
		$dashWriter = PHPExcel_IOFactory::createWriter($dashWorkbook,$writerType);
		$dashFile = tempnam(sys_get_temp_dir(),'xd_dash_date_'); $temporaryFiles[] = $dashFile;
		$dashWriter->save($dashFile);
		list($unusedDashWorkbook,$dashLoadedSheet,$dashMaxRow,$dashMaxCol) = xd_open_upload_sheet(array('tmp_name'=>$dashFile),$extension,'',array(),true);
		list($dashHeader,$dashMap) = xd_roster_header($dashLoadedSheet,$dashMaxRow,$dashMaxCol);
		$dashLoadedRows = xd_roster_parse($dashLoadedSheet,$dashMaxRow,$dashMap,$dashHeader,array('khoa'=>'XD-TEST'));
		roster_assert($dashLoadedRows[0]['ngay_nop_ho_so'] === '2026-07-10', 'Actual Excel dd-mm-yyyy text import failed: '.$extension);
		if($extension === 'xlsx')
		{
			$zip = new ZipArchive(); $zip->open($file);
			$xml = simplexml_load_string($zip->getFromName('xl/worksheets/sheet1.xml'));
			foreach($xml->sheetData->row as $xmlRow) foreach($xmlRow->c as $cell)
				if((string)$cell['r'] === 'O2') unset($cell->v);
			$zip->addFromString('xl/worksheets/sheet1.xml',$xml->asXML()); $zip->close();
			list($unused,$strictSheet,$strictMaxRow,$strictMaxCol) = xd_open_upload_sheet(array('tmp_name'=>$file),'xlsx','',array(),true);
			roster_rejects(function() use ($strictSheet,$strictMaxRow,$loadedMap,$loadedHeader) {
				xd_roster_parse($strictSheet,$strictMaxRow,$loadedMap,$loadedHeader,array('khoa'=>'XD-TEST'));
			},'Streaming XLSX reader silently cleared an uncached formula amount');
		}
	}
	$plan = xd_roster_plan($rows);
	roster_assert($plan['new'] === 1 && $plan['updated'] === 0, 'New preview counts incorrect');
	roster_assert(count(xd_roster_query('select id from #_xd_hocvien')) === 0, 'Preview wrote students');
	$result = xd_roster_save($rows); $backups[] = $result['backup'];
	$stored = xd_roster_query('select * from #_xd_hocvien')[0];
	roster_assert($stored['ngay_nop_ho_so'] === '2026-10-29', 'Submission date not stored on insert');
	roster_assert($stored['ngay_thanh_toan'] === null && (int)$stored['so_tien_thanh_toan'] === 0 && (int)$stored['id_bangke'] === 0, 'Source amounts created fuel settlement');
	foreach($rows[0]['financial'] as $key => $amount) roster_assert((int)$stored[$key] === $amount, 'Saved money mismatch: '.$key);
	xd_roster_query("update #_xd_hocvien set nhom='DAT', user_tao='Đồng bộ DAT', thu_tu_file=987, lan_import=123,
		dinh_muc=3500000,dinh_muc_ca_nhan=3000000,da_dieu_chinh_tt=1,
		so_tien_thanh_toan=1200000,ngay_thanh_toan='2026-10-20',id_bangke=77,quan_ly_duyet=1,ke_toan_kiem_tra=1");
	$before = xd_roster_query('select * from #_xd_hocvien')[0];
	$updated = $rows;
	$updated[0]['cccd'] = '99000000001';
	$updated[0]['khoa'] = 'DIFFERENT';
	$updated[0]['nhom'] = 'BT';
	$updated[0]['gv_hoten'] = 'Teacher B';
	$updated[0]['financial'] = array('hoc_phi_da_dong'=>13000000);
	$updated[0]['ngay_nop_ho_so'] = '2026-10-30';
	$groupPreview = xd_roster_plan($updated);
	roster_assert($groupPreview['group_changes'] === 1 && $groupPreview['rows'][0]['nhom'] === 'BT' &&
		$groupPreview['rows'][0]['existing']['nhom'] === 'DAT', 'Preview kept old DAT instead of Excel BT');
	roster_assert(xd_roster_query('select * from #_xd_hocvien')[0] === $before, 'Group preview wrote a student');
	$xd_roster_preview = $groupPreview; $xd_roster_preview['token'] = 'test';
	ob_start(); require dirname(__DIR__).'/admin/templates/xangdau/uploadHocvien/preview_tpl.php'; $groupHtml = ob_get_clean();
	roster_assert(strpos($groupHtml,'DAT → BT') !== false && strpos($groupHtml,'Sẽ đổi nhóm 1 hồ sơ') !== false, 'Preview did not expose old/new groups');
	$result = xd_roster_save($updated); $backups[] = $result['backup'];
	$after = xd_roster_query('select * from #_xd_hocvien')[0];
	$expected = $before; $expected['nhom'] = 'BT'; $expected['hoc_phi_da_dong'] = $after['hoc_phi_da_dong']; $expected['ngay_nop_ho_so'] = '2026-10-30';
	roster_assert($after === $expected && (int)$after['hoc_phi_da_dong'] === 13000000, 'Reimport changed history, DAT linkage or absent columns');
	foreach(array('CK','DAT','BT') as $excelGroup)
	{
		$groupRows = $updated; $groupRows[0]['nhom'] = $excelGroup;
		$result = xd_roster_save($groupRows); $backups[] = $result['backup'];
		$groupExpected = $after; $groupExpected['nhom'] = $excelGroup;
		roster_assert(xd_roster_query('select * from #_xd_hocvien')[0] === $groupExpected, 'Settled/DAT student group not saved or history changed: '.$excelGroup);
	}
	foreach(array(false,true) as $hasGroupColumn)
	{
		$emptyGroupValues = $values; $emptyGroupValues[11] = '';
		$emptyGroupMap = $map;
		if(!$hasGroupColumn) unset($emptyGroupMap['nhom']);
		$emptyGroupSheet = new XdArraySheet(array(1=>$headers,2=>$emptyGroupValues),2,count($headers));
		$emptyGroupRows = xd_roster_parse($emptyGroupSheet,2,$emptyGroupMap,1,array('khoa'=>'XD-TEST','nhom'=>'CK'));
		roster_assert($emptyGroupRows[0]['nhom_tu_file'] === false, 'Blank/missing group marked as explicitly supplied');
		$emptyGroupRows[0]['financial'] = array(); unset($emptyGroupRows[0]['ngay_nop_ho_so']);
		$result = xd_roster_save($emptyGroupRows); $backups[] = $result['backup'];
		roster_assert(xd_roster_query('select * from #_xd_hocvien')[0] === $after, 'Form default overwrote an existing group with blank/missing Excel group');
	}
	$invalidGroupRows = $updated; $invalidGroupRows[0]['nhom'] = 'INVALID';
	roster_rejects(function() use ($invalidGroupRows) { xd_roster_save($invalidGroupRows); }, 'Invalid group on existing settled student accepted');
	roster_assert(xd_roster_query('select * from #_xd_hocvien')[0] === $after, 'Invalid group changed student history');
	$result = xd_roster_save($updated); $backups[] = $result['backup'];
	roster_assert($result['new'] === 0 && $result['updated'] === 1 && count(xd_roster_query('select id from #_xd_hocvien')) === 1, 'Repeat import duplicated student');
	foreach(array(null, '') as $blankDate)
	{
		$blankRows = $updated; $blankRows[0]['ngay_nop_ho_so'] = $blankDate;
		$result = xd_roster_save($blankRows); $backups[] = $result['backup'];
		roster_assert(xd_roster_query('select * from #_xd_hocvien')[0] === $after, 'Blank date cleared a stored submission date');
	}
	$dateOnlyMap = $map;
	foreach(xd_student_financial_fields() as $key => $label) unset($dateOnlyMap[$key]);
	unset($dateOnlyMap['nhom']);
	$dateOnlyRows = xd_roster_parse($sheet,2,$dateOnlyMap,1,array('khoa'=>'XD-TEST'));
	roster_assert(!$dateOnlyRows[0]['financial'], 'Date-only file unexpectedly parsed money');
	$dateOnlyRows[0]['ngay_nop_ho_so'] = '2026-10-30';
	$result = xd_roster_save($dateOnlyRows); $backups[] = $result['backup'];
	roster_assert(xd_roster_query('select * from #_xd_hocvien')[0] === $after, 'Date-only import changed money or protected history');
	unset($updated[0]['ngay_nop_ho_so']);
	$result = xd_roster_save($updated); $backups[] = $result['backup'];
	roster_assert(xd_roster_query('select * from #_xd_hocvien')[0] === $after, 'Absent date cleared a stored submission date');
	foreach(array('-1','1.25','abc','10000000000000','#REF!') as $invalid)
		roster_rejects(function() use ($invalid) { xd_roster_amount($invalid); }, 'Invalid money accepted');
	roster_assert(xd_roster_amount('1.234.567') === 1234567 && xd_roster_amount('1,234,567') === 1234567 && xd_roster_amount('') === 0, 'Money format parsing failed');
	$duplicate = new XdArraySheet(array(1=>$headers,2=>$values,3=>$values), 3, count($headers));
	roster_rejects(function() use ($duplicate,$map) { xd_roster_parse($duplicate,3,$map,1); }, 'Duplicate rows accepted');
	$workbook = new PHPExcel(); $formulaSheet = $workbook->getActiveSheet();
	$formulaSheet->setCellValue('A1', '=INDEX(#REF!,1)'); $formulaSheet->getCell('A1')->setCalculatedValue(345000);
	roster_assert(xd_roster_value($formulaSheet,0,1) === '345000', 'Cached external formula ignored');
	$formulaSheet->setCellValue('A2', '=1/0');
	roster_rejects(function() use ($formulaSheet) { xd_roster_value($formulaSheet,0,2); }, 'Uncached formula accepted');
	$invalidBatch = array_merge($updated,$rows);
	$invalidBatch[1]['cccd'] = '099000000002'; $invalidBatch[1]['nhom'] = '';
	roster_rejects(function() use ($invalidBatch) { xd_roster_save($invalidBatch); }, 'Invalid batch accepted');
	roster_assert(xd_roster_query('select * from #_xd_hocvien')[0] === $after, 'Failed import partially wrote rows');
	$preview = array('rows'=>xd_roster_plan($dateOnlyRows)['rows'], 'new'=>0,'updated'=>1,'missing_teachers'=>0,'totals'=>$plan['totals'],'token'=>'test');
	$xd_roster_preview = $preview;
	ob_start(); require dirname(__DIR__).'/admin/templates/xangdau/uploadHocvien/preview_tpl.php'; $html = ob_get_clean();
	roster_assert(strpos($html,'confirm_roster') !== false && strpos($html,'Thanh toán Lương') !== false && strpos($html,'Ngày nộp hồ sơ') !== false, 'Preview confirmation or source labels missing');
	roster_assert(strpos($html,'30/10/2026') !== false, 'Preview submission date format is incorrect');
	echo "PASS roster headers, XLS/XLSX cached formulas, exact VND, submission dates, Excel BT/CK/DAT group updates on settled/DAT records, blank/missing group preservation, preview, six amounts, repeat identities, protected history, invalid data and rollback\n";
}
catch(Throwable $error) { $failure = $error; }
finally
{
	$d->rawQuery('drop temporary table if exists table_xd_hocvien');
	foreach($backups as $backup)
	{
		$path = dirname(__DIR__).'/backups/'.$backup;
		if(is_file($path)) unlink($path);
	}
	foreach($temporaryFiles as $file) if(is_file($file)) unlink($file);
}
if($failure) { fwrite(STDERR,$failure->getMessage()."\n"); exit(1); }
