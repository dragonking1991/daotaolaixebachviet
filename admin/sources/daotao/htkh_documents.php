<?php
if(!defined('SOURCES')) die("Error");

/* One layout feeds all three formats so exported certificates match the print view. */
function dt_htkh_document_rows($document)
{
	return array(
		array('left' => 'SỞ GIAO THÔNG VẬN TẢI', 'right' => 'CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM', 'size' => 11, 'height' => 32),
		array('left' => 'TRUNG TÂM GDNN BÁCH VIỆT', 'right' => 'Độc lập - Tự do - Hạnh phúc', 'bold' => true, 'size' => 11, 'height' => 32),
		array('text' => ''),
		array('text' => 'GIẤY XÁC NHẬN', 'align' => 'center', 'bold' => true, 'size' => 18),
		array('text' => 'HOÀN THÀNH KHÓA ĐÀO TẠO', 'align' => 'center', 'bold' => true),
		array('text' => 'Số: '.$document['so_giay'], 'align' => 'center'),
		array('text' => ''),
		array('text' => 'Họ và tên: '.$document['hoten'], 'bold' => true),
		array('text' => 'Ngày sinh: '.$document['ngaysinh']),
		array('text' => 'Đã hoàn thành chương trình đào tạo lái xe hạng: '.$document['hang'], 'height' => 44),
		array('text' => 'Khóa: '.$document['khoa'], 'height' => 36),
		array('text' => 'Thời gian học: '.$document['thoi_gian']),
		array('text' => ''),
		array('left' => '', 'right' => '....., '.$document['ngay_ky']),
		array('left' => '', 'right' => $document['chuc_danh'], 'bold' => true, 'height' => 40),
		array('text' => ''),
		array('text' => ''),
		array('text' => ''),
		array('left' => '', 'right' => $document['nguoi_ky'], 'bold' => true),
	);
}

function dt_htkh_documents_html($documents, $back)
{
	$e = function($text) { return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); };
	$html = '<!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Giấy xác nhận HTKH</title>
	<style>@page{size:A4 portrait;margin:20mm}*{box-sizing:border-box}
	body{font-family:"Times New Roman",serif;color:#111;margin:0;background:#eee}
	.toolbar{text-align:center;padding:12px}.toolbar button,.toolbar a{margin:0 8px}
	.cert{width:170mm;margin:10mm auto;background:white;page-break-after:always}
	.cert:last-child{page-break-after:auto}table{width:100%;table-layout:fixed;border-collapse:collapse}
	td{font-size:12pt;vertical-align:middle;white-space:pre-line;overflow-wrap:anywhere;padding:0}
	@media print{body{background:white}.toolbar{display:none}.cert{margin:0;width:100%}}</style></head><body>
	<div class="toolbar"><button onclick="window.print()">In / Lưu PDF</button><a href="'.$e($back).'">Quay lại</a></div>';
	foreach($documents as $document)
	{
		$html .= '<div class="cert"><table>';
		foreach(dt_htkh_document_rows($document) as $row)
		{
			$style = 'height:'.($row['height'] ?? 25).'pt;font-size:'.($row['size'] ?? 12).'pt;font-weight:'.(!empty($row['bold']) ? 'bold' : 'normal').';';
			$html .= '<tr>';
			if(isset($row['left']))
				$html .= '<td style="'.$style.'text-align:center">'.$e($row['left']).'</td><td style="'.$style.'text-align:center">'.$e($row['right']).'</td>';
			else
				$html .= '<td colspan="2" style="'.$style.'text-align:'.($row['align'] ?? 'left').'">'.$e($row['text']).'</td>';
			$html .= '</tr>';
		}
		$html .= '</table></div>';
	}
	return $html.'</body></html>';
}

function dt_htkh_documents_xlsx($documents)
{
	require_once LIBRARIES.'PHPExcel.php';
	$book = new PHPExcel();
	$sheet = $book->getActiveSheet();
	$sheet->setTitle('Giay xac nhan HTKH');
	$book->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(12);
	foreach(range('A', 'H') as $column) $sheet->getColumnDimension($column)->setWidth(11);
	$sheet->setShowGridlines(false);
	$setup = $sheet->getPageSetup();
	$setup->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
	$setup->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
	$setup->setFitToWidth(1)->setFitToHeight(0);
	$sheet->getPageMargins()->setTop(0.7874)->setBottom(0.7874)->setLeft(0.7874)->setRight(0.7874);
	$number = 1;
	foreach($documents as $document)
	{
		if($number > 1) $sheet->setBreak('A'.($number - 1), PHPExcel_Worksheet::BREAK_ROW);
		foreach(dt_htkh_document_rows($document) as $row)
		{
			$sheet->getRowDimension($number)->setRowHeight($row['height'] ?? 25);
			$cells = isset($row['left']) ? array(array('A', 'D', $row['left']), array('E', 'H', $row['right'])) : array(array('A', 'H', $row['text']));
			foreach($cells as $cell)
			{
				$range = $cell[0].$number.':'.$cell[1].$number;
				$sheet->mergeCells($range);
				$sheet->setCellValueExplicit($cell[0].$number, (string)$cell[2], PHPExcel_Cell_DataType::TYPE_STRING);
				$style = $sheet->getStyle($range);
				$style->getFont()->setSize($row['size'] ?? 12)->setBold(!empty($row['bold']));
				$style->getAlignment()->setWrapText(true)->setVertical('center')->setHorizontal(isset($row['left']) ? 'center' : ($row['align'] ?? 'left'));
			}
			$number++;
		}
	}
	$setup->setPrintArea('A1:H'.($number - 1));
	return $book;
}

function dt_htkh_documents_docx_save($documents, $path)
{
	/* The bundled PHPWord applies Latin-1 conversion to UTF-8 text; write Unicode OOXML directly. */
	$e = function($text) { return htmlspecialchars((string)$text, ENT_XML1 | ENT_QUOTES, 'UTF-8'); };
	$xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>';
	foreach($documents as $index => $document)
	{
		if($index > 0) $xml .= '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
		$xml .= '<w:tbl><w:tblPr><w:tblW w:w="9638" w:type="dxa"/><w:tblLayout w:type="fixed"/><w:tblCellMar><w:left w:w="0" w:type="dxa"/><w:right w:w="0" w:type="dxa"/></w:tblCellMar></w:tblPr><w:tblGrid><w:gridCol w:w="4819"/><w:gridCol w:w="4819"/></w:tblGrid>';
		foreach(dt_htkh_document_rows($document) as $row)
		{
			$split = isset($row['left']);
			$xml .= '<w:tr><w:trPr><w:cantSplit/><w:trHeight w:val="'.(($row['height'] ?? 25) * 20).'" w:hRule="atLeast"/></w:trPr>';
			foreach($split ? array($row['left'], $row['right']) : array($row['text']) as $text)
			{
				$xml .= '<w:tc><w:tcPr><w:tcW w:w="'.($split ? 4819 : 9638).'" w:type="dxa"/>'.(!$split ? '<w:gridSpan w:val="2"/>' : '').'<w:vAlign w:val="center"/></w:tcPr><w:p><w:pPr><w:jc w:val="'.($split ? 'center' : ($row['align'] ?? 'left')).'"/><w:spacing w:before="0" w:after="0"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:sz w:val="'.(($row['size'] ?? 12) * 2).'"/>'.(!empty($row['bold']) ? '<w:b/>' : '').'</w:rPr>';
				foreach(explode("\n", (string)$text) as $line => $part)
					$xml .= ($line > 0 ? '<w:br/>' : '').'<w:t xml:space="preserve">'.$e($part).'</w:t>';
				$xml .= '</w:r></w:p></w:tc>';
			}
			$xml .= '</w:tr>';
		}
		$xml .= '</w:tbl><w:p><w:pPr><w:spacing w:before="0" w:after="0"/><w:rPr><w:sz w:val="2"/></w:rPr></w:pPr></w:p>';
	}
	$xml .= '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" w:header="0" w:footer="0" w:gutter="0"/></w:sectPr></w:body></w:document>';
	$zip = new ZipArchive();
	if($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Không thể tạo file Word HTKH');
	try
	{
		$parts = array(
			'[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>',
			'_rels/.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>',
			'word/document.xml' => $xml,
		);
		foreach($parts as $name => $content)
			if(!$zip->addFromString($name, $content)) throw new RuntimeException('Không thể ghi nội dung file Word HTKH');
	}
	finally
	{
		if(!$zip->close()) throw new RuntimeException('Không thể hoàn tất file Word HTKH');
	}
}

function dt_htkh_documents_docx_download($documents, $filename)
{
	$path = tempnam(sys_get_temp_dir(), 'htkh_');
	if($path === false) throw new RuntimeException('Không thể tạo file tạm xuất Word HTKH');
	try
	{
		dt_htkh_documents_docx_save($documents, $path);
		if(ob_get_length()) ob_end_clean();
		header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		if(readfile($path) === false) throw new RuntimeException('Không thể tải file Word HTKH');
	}
	finally
	{
		unlink($path);
	}
}
