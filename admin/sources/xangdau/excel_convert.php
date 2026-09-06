<?php
if(!defined('SOURCES')) die("Error");

/* ============================ Chuyển đổi & mở file Excel ============================ */

/**
 * Chuyển đổi file .xlsb -> .xlsx bằng công cụ có sẵn trên máy chủ.
 * Dò theo thứ tự: LibreOffice (soffice) -> Gnumeric (ssconvert).
 * @return string|false đường dẫn file .xlsx tạm, hoặc false nếu không có công cụ.
 */
function xd_convert_to_xlsx($inputFile)
{
	if(!function_exists('shell_exec')) return false;

	$outDir = sys_get_temp_dir().'/xd_conv_'.uniqid();
	@mkdir($outDir, 0777, true);
	$base = pathinfo($inputFile, PATHINFO_FILENAME);
	$expected = $outDir.'/'.$base.'.xlsx';

	// 1) LibreOffice headless
	$soffice = '';
	foreach(array('soffice', '/Applications/LibreOffice.app/Contents/MacOS/soffice', '/usr/bin/soffice', '/opt/libreoffice/program/soffice') as $cand)
	{
		$which = @shell_exec('command -v '.escapeshellarg($cand).' 2>/dev/null');
		if($cand[0] === '/' && is_executable($cand)) { $soffice = $cand; break; }
		if(!empty($which)) { $soffice = trim($which); break; }
	}
	if($soffice !== '')
	{
		// LibreOffice headless cần một thư mục profile GHI ĐƯỢC (quan trọng khi chạy dưới user www-data).
		$profile = $outDir.'/loprofile';
		@mkdir($profile, 0777, true);
		$timeout = trim((string)@shell_exec('command -v timeout 2>/dev/null'));
		// Gán HOME phải đứng TRƯỚC 'timeout' (nếu không timeout sẽ hiểu HOME=... là tên lệnh).
		$prefix = 'HOME='.escapeshellarg($outDir).' ';
		if($timeout !== '') $prefix .= escapeshellarg($timeout).' 180 ';
		$cmd = $prefix.escapeshellarg($soffice)
			.' -env:UserInstallation=file://'.$profile
			.' --headless --calc --convert-to xlsx --outdir '.escapeshellarg($outDir).' '.escapeshellarg($inputFile).' 2>&1';
		@shell_exec($cmd);
		if(is_file($expected) && filesize($expected) > 0) return $expected;
		// LibreOffice có thể đặt tên khác: quét file .xlsx đầu tiên trong outDir
		$found = glob($outDir.'/*.xlsx');
		if(!empty($found) && is_file($found[0])) return $found[0];
	}

	// 2) Gnumeric ssconvert
	$ssconvert = @shell_exec('command -v ssconvert 2>/dev/null');
	if(!empty($ssconvert))
	{
		@shell_exec(escapeshellarg(trim($ssconvert)).' '.escapeshellarg($inputFile).' '.escapeshellarg($expected).' 2>&1');
		if(is_file($expected) && filesize($expected) > 0) return $expected;
	}

	return false;
}

/**
 * Xóa file .xlsx tạm sinh ra từ chuyển đổi .xlsb cùng cả thư mục tạm (profile LibreOffice).
 */
function xd_cleanup_converted($convertedFile)
{
	if($convertedFile === '' || !is_file($convertedFile)) return;
	$dir = dirname($convertedFile);
	@unlink($convertedFile);
	// Chỉ xóa đệ quy nếu là thư mục tạm của module (an toàn)
	if(strpos(basename($dir), 'xd_conv_') === 0) xd_rmtree($dir);
}

function xd_rmtree($dir)
{
	if(!is_dir($dir)) { if(is_file($dir)) @unlink($dir); return; }
	$items = @scandir($dir);
	if(is_array($items))
	{
		foreach($items as $it)
		{
			if($it === '.' || $it === '..') continue;
			$path = $dir.'/'.$it;
			if(is_dir($path)) xd_rmtree($path);
			else @unlink($path);
		}
	}
	@rmdir($dir);
}

function xd_col_ref_to_index($ref)
{
	if(!preg_match('/^([A-Za-z]+)(\d+)$/', $ref, $m)) return array(-1, -1);
	$letters = strtoupper($m[1]); $idx = 0;
	$len = strlen($letters);
	for($i = 0; $i < $len; $i++) $idx = $idx * 26 + (ord($letters[$i]) - 64);
	return array($idx - 1, (int)$m[2]);
}
