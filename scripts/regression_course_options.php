<?php
if(PHP_SAPI !== 'cli') exit('CLI only');
require dirname(__DIR__).'/libraries/course_options.php';
function course_check($condition, $message)
{
	if(!$condition) throw new RuntimeException($message);
}
course_check(course_option_label(array('ma_khoa'=>'79044K26C1005','ten_khoa'=>'K13-C1','so_hoc_vien'=>63)) ===
	'79044K26C1005 — K13-C1 · 63 HV', 'Training course label/count incorrect');
course_check(course_option_label(array('khoa'=>'XD-1','ten_khoa'=>'Khóa XD','so'=>62)) ===
	'XD-1 — Khóa XD · 62 HV', 'XD course label/count incorrect');
course_check(course_option_label(array('khoa'=>'SOURCE','total'=>0)) ===
	'SOURCE — Chưa có tên khóa · 0 HV', 'Missing course name or zero count unclear');
course_check(course_option_label(array('ma_khoa'=>'','ten_khoa'=>'Legacy','so_hoc_vien'=>3)) ===
	'Chưa có mã khóa — Legacy · 3 HV', 'Missing code must be explicit');
$root = dirname(__DIR__);
$selectors = 0;
foreach(array('daotao','xangdau') as $module)
{
	$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/admin/templates/'.$module));
	foreach($files as $file)
	{
		if(!$file->isFile() || $file->getExtension() !== 'php') continue;
		$content = file_get_contents($file->getPathname());
		preg_match_all('/<select\b[^>]*name="(?:id_khoa|khoa|source_khoa|ky_khoa)"[^>]*>(.*?)<\/select>/s', $content, $matches);
		foreach($matches[1] as $body)
		{
			$selectors++;
			course_check(strpos($body,'course_option_label(')!==false || strpos($body,'xd_hocvien_course_label(')!==false ||
				strpos($body,'$htkh_ky_khoa')!==false, 'Unformatted course selector: '.$file->getPathname());
		}
	}
}
course_check($selectors >= 22, 'Course-selector coverage unexpectedly decreased');
$crud = file_get_contents($root.'/admin/templates/daotao/crud_form_tpl.php');
course_check(strpos($crud,"if(\$field === 'id_khoa')")!==false &&
	strpos($crud,'course_option_label($k)')!==false, 'Dynamic CRUD course selector missing shared label');
$script = file_get_contents($root.'/admin/assets/js/course-select.js');
course_check(strpos($script,'minimumInputLength: 0')!==false &&
	strpos($script,"$(search).val('').trigger('input')")!==false,
	'Opening course selector must reset search and show all courses without typing');
foreach(array('id_khoa','data[id_khoa]','khoa','source_khoa','ky_khoa') as $name)
	course_check(strpos($script,"'".$name."'")!==false, 'Search initialization missing course field '.$name);
course_check(strpos(file_get_contents($root.'/admin/index.php'),'assets/js/course-select.js')!==false,
	'Course search script is not loaded');
$globalScript = file_get_contents($root.'/admin/assets/js/myscript.php');
course_check(strpos($globalScript, "$('select.select2').filter(function()")!==false &&
	strpos($globalScript, "return !$(this).data('select2');")!==false &&
	strpos($globalScript, "$('.select2').select2()")===false,
	'Global Select2 initializer must skip generated containers and initialized selects');
define('SOURCES', $root.'/admin/sources/');
function dt_can($section, $permission) { return false; }
$total_items = 1;
$items = array(array('id'=>91,'ma_khoa'=>'79044K26C1005','ten_khoa'=>'K13-C1','hang'=>'C1',
	'he_daotao'=>'','ngay_khaigiang'=>null,'ngay_manhoa'=>null,'so_hoc_vien'=>63));
ob_start();
require $root.'/admin/templates/daotao/khoa/items_tpl.php';
$courseList = ob_get_clean();
course_check(strpos($courseList,'class="dt-title" title="Tên khóa">K13-C1</div>')!==false &&
	strpos($courseList,'class="dt-sub">Mã khóa: 79044K26C1005</div>')!==false,
	'Training course title/code are not clearly distinguished');
echo "PASS shared course labels, counts, missing metadata and $selectors course-selector/search surfaces\n";
