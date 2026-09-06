<?php
if(!defined('SOURCES')) die("Error");

/**
 * Đọc 1 sheet của file .xlsx theo LUỒNG (XMLReader) để tiết kiệm bộ nhớ.
 * @return XdArraySheet|false  (trả $highestRow, $highestColIndex qua tham chiếu)
 */
function xd_stream_read_xlsx($path, $sheetHints, &$highestRow, &$highestColIndex, $maxRow = 20000)
{
	$highestRow = 0; $highestColIndex = 0;
	if(!class_exists('ZipArchive') || !class_exists('XMLReader')) return false;

	$zip = new ZipArchive();
	if($zip->open($path) !== true) return false;
	$wbXml = $zip->getFromName('xl/workbook.xml');
	$relXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
	$zip->close();
	if($wbXml === false || $relXml === false) return false;

	// Tìm sheet mục tiêu theo tên -> r:id
	$sheetRid = ''; $wb = @simplexml_load_string($wbXml);
	if(!$wb || !isset($wb->sheets)) return false;
	$ns = $wb->getDocNamespaces(true);
	$rns = isset($ns['r']) ? $ns['r'] : 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
	foreach($wb->sheets->sheet as $sh)
	{
		$norm = xd_norm_header((string)$sh['name']);
		foreach($sheetHints as $hint) { if($hint !== '' && strpos($norm, $hint) !== false) { $ra = $sh->attributes($rns); $sheetRid = (string)$ra['id']; break 2; } }
	}
	if($sheetRid === '' && isset($wb->sheets->sheet[0])) { $ra = $wb->sheets->sheet[0]->attributes($rns); $sheetRid = (string)$ra['id']; }
	if($sheetRid === '') return false;

	// r:id -> target path
	$target = ''; $rels = @simplexml_load_string($relXml);
	if($rels) foreach($rels->Relationship as $rel) { if((string)$rel['Id'] === $sheetRid) { $target = (string)$rel['Target']; break; } }
	if($target === '') return false;
	$target = str_replace('../', '', $target);
	if(strpos($target, 'xl/') !== 0) $target = 'xl/'.ltrim($target, '/');

	// Shared strings (stream)
	$shared = array();
	$sr = new XMLReader();
	if(@$sr->open('zip://'.$path.'#xl/sharedStrings.xml'))
	{
		$sdom = new DOMDocument();
		while(@$sr->read())
		{
			if($sr->nodeType === XMLReader::ELEMENT && $sr->localName === 'si')
			{
				$node = $sr->expand($sdom);
				$txt = '';
				if($node) foreach($node->getElementsByTagName('t') as $t) $txt .= $t->textContent;
				$shared[] = $txt;
			}
		}
		$sr->close();
	}

	// Sheet rows (stream, expand từng dòng — bộ nhớ hằng số)
	$rows = array(); $maxColIdx = 0; $lastData = 0; $emptyStreak = 0;
	$rd = new XMLReader();
	if(!@$rd->open('zip://'.$path.'#'.$target)) return false;
	$dom = new DOMDocument();
	while(@$rd->read())
	{
		if($rd->nodeType !== XMLReader::ELEMENT || $rd->localName !== 'row') continue;
		$rowNum = (int)$rd->getAttribute('r');
		if($rowNum <= 0) continue;
		if($rowNum > $maxRow) break;

		$node = $rd->expand($dom);
		if(!$node) continue;
		$anyVal = false;
		foreach($node->childNodes as $c)
		{
			if($c->nodeType !== XML_ELEMENT_NODE || $c->localName !== 'c') continue;
			list($colIdx, ) = xd_col_ref_to_index($c->getAttribute('r'));
			if($colIdx < 0) continue;
			$t = $c->getAttribute('t');
			$val = '';
			foreach($c->childNodes as $ch)
			{
				if($ch->nodeType !== XML_ELEMENT_NODE) continue;
				if($ch->localName === 'v')
				{
					$raw = $ch->textContent;
					if($t === 's') { $ii = (int)$raw; $val = isset($shared[$ii]) ? $shared[$ii] : ''; }
					else $val = $raw;
				}
				elseif($ch->localName === 'is') $val = $ch->textContent;
			}
			$val = trim((string)$val);
			if($val !== '')
			{
				$rows[$rowNum][$colIdx] = $val;
				if($colIdx + 1 > $maxColIdx) $maxColIdx = $colIdx + 1;
				$anyVal = true;
			}
		}
		if($anyVal) { $lastData = $rowNum; $emptyStreak = 0; }
		elseif($lastData > 0 && ++$emptyStreak > 200) break;
	}
	$rd->close();

	$highestRow = max(1, $lastData);
	$highestColIndex = max(1, $maxColIdx);
	return new XdArraySheet($rows, $highestRow, $highestColIndex);
}
