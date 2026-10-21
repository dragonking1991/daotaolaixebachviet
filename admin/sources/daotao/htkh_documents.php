<?php
if(!defined('SOURCES')) die("Error");

/* Logo in trên giấy xác nhận (in lên phôi có sẵn hoa văn nên không chèn nền). */
function dt_htkh_assets()
{
	$dir = dirname(rtrim(SOURCES, '/\\')).'/assets/images/';
	return array(
		'logo_file' => $dir.'htkh-logo.png',
		'logo_url' => 'assets/images/htkh-logo.png',
	);
}

/* Bố cục dùng chung cho cả 3 định dạng — các trường cố định theo mẫu thông tư.
 * Mỗi dòng: 'image' (logo căn giữa), 'text' (toàn hàng) hoặc 'left'/'right' (hai cột, 'split' = % cột trái, bội số của 5).
 * Nội dung có thể là chuỗi hoặc mảng đoạn array(text, bold). */
function dt_htkh_document_rows($doc)
{
	$g = function($key) use ($doc) { return isset($doc[$key]) ? (string)$doc[$key] : ''; };
	$sign = array('left' => '', 'split' => 45, 'ralign' => 'center', 'size' => 13, 'nowrap' => true);
	return array(
		array('text' => 'CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM', 'align' => 'center', 'bold' => true, 'size' => 13, 'height' => 20, 'nowrap' => true),
		array('text' => 'Độc lập - Tự do - Hạnh phúc', 'align' => 'center', 'bold' => true, 'underline' => true, 'size' => 13, 'height' => 22, 'nowrap' => true),
		array('image' => true, 'height' => 90),
		array('text' => 'GIẤY XÁC NHẬN HOÀN THÀNH KHÓA ĐÀO TẠO LÁI XE', 'align' => 'center', 'bold' => true, 'size' => 14, 'height' => 26, 'nowrap' => true),
		array('text' => 'Số: '.$g('so_giay'), 'align' => 'center', 'bold' => true, 'size' => 13, 'height' => 22, 'nowrap' => true),
		array('text' => '', 'height' => 24),
		array('text' => 'TRUNG TÂM GIÁO DỤC NGHỀ NGHIỆP BÁCH VIỆT', 'align' => 'center', 'bold' => true, 'color' => 'C00000', 'size' => 14, 'height' => 26, 'nowrap' => true),
		array('text' => '', 'height' => 26),
		array('text' => 'Xác nhận cho Ông (Bà) :  '.$g('hoten'), 'size' => 14, 'height' => 28),
		array('text' => 'Ngày tháng năm sinh :  '.$g('ngaysinh'), 'size' => 14, 'height' => 28),
		array('text' => 'Đã hoàn thành chương trình đào tạo lái xe hạng :  '.$g('hang'), 'size' => 14, 'height' => 28),
		array('left' => 'Khóa :  '.$g('khoa'), 'right' => 'Thời gian học :  '.$g('so_ngay'), 'split' => 30, 'lalign' => 'left', 'ralign' => 'left', 'size' => 14, 'height' => 28, 'nowrap' => true),
		array('text' => $g('tu_den'), 'size' => 14, 'height' => 28, 'nowrap' => true),
		array('text' => array(array('Tại ', false), array('Trung tâm Giáo dục nghề nghiệp Bách Việt', true), array('.', false)), 'size' => 14, 'height' => 28, 'nowrap' => true),
		array('text' => '', 'height' => 12),
		array_merge($sign, array('right' => $g('ngay_ky_text'), 'height' => 20)),
		array_merge($sign, array('right' => $g('chuc_danh'), 'bold' => true, 'nowrap' => false, 'height' => 36)),
		array('text' => '', 'height' => 70),
		array_merge($sign, array('right' => $g('nguoi_ky'), 'bold' => true, 'height' => 22)),
	);
}

function dt_htkh_row_parts($row, $value)
{
	if(is_array($value)) return $value;
	return array(array((string)$value, !empty($row['bold'])));
}

/* Trả về các ô của 1 dòng: array(parts, align, % độ rộng). */
function dt_htkh_row_cells($row)
{
	if(isset($row['left']))
	{
		$split = isset($row['split']) ? (int)$row['split'] : 50;
		return array(
			array(dt_htkh_row_parts($row, $row['left']), $row['lalign'] ?? 'left', $split),
			array(dt_htkh_row_parts($row, $row['right']), $row['ralign'] ?? 'center', 100 - $split),
		);
	}
	return array(array(dt_htkh_row_parts($row, $row['text'] ?? ''), $row['align'] ?? 'left', 100));
}

/* ---------------- HTML (xem + in + lưu PDF) ---------------- */
function dt_htkh_documents_html($documents, $back)
{
	$e = function($text) { return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); };
	$assets = dt_htkh_assets();
	$html = '<!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Giấy xác nhận HTKH</title>
	<style>@page{size:A4 portrait;margin:0}*{box-sizing:border-box}
	body{font-family:"Times New Roman",serif;color:#111;margin:0;background:#e9edf3}
	.toolbar{text-align:center;padding:12px}
	.toolbar button,.toolbar a{margin:0 6px;font-size:14px;padding:6px 14px;border:1px solid #2563eb;border-radius:4px;background:#2563eb;color:#fff;text-decoration:none;cursor:pointer}
	.toolbar a{background:#fff;color:#2563eb}
	.cert{position:relative;width:210mm;min-height:297mm;margin:10px auto;padding:15mm 20mm;background:#fff;overflow:hidden;page-break-after:always}
	.cert:last-child{page-break-after:auto}
	.cert .r{display:flex;align-items:center}
	.cert .r>div{white-space:pre-line;overflow-wrap:anywhere}
	.cert .r.nw>div{white-space:pre;overflow-wrap:normal}
	.cert img.logo{display:block;margin:0 auto;height:26mm}
	@media print{body{background:#fff}.toolbar{display:none}.cert{margin:0;width:210mm}}</style></head><body>
	<div class="toolbar"><button onclick="window.print()">In / Lưu PDF</button><a href="'.$e($back).'">Quay lại</a></div>';
	foreach($documents as $doc)
	{
		$html .= '<div class="cert">';
		foreach(dt_htkh_document_rows($doc) as $row)
		{
			$h = 'min-height:'.($row['height'] ?? 24).'pt;';
			if(!empty($row['image']))
			{
				$html .= '<div class="r" style="'.$h.'justify-content:center"><img class="logo" src="'.$e($assets['logo_url']).'" alt="Logo"></div>';
				continue;
			}
			$font = 'font-size:'.($row['size'] ?? 14).'pt;'
				.(!empty($row['italic']) ? 'font-style:italic;' : '')
				.(!empty($row['underline']) ? 'text-decoration:underline;' : '')
				.(!empty($row['color']) ? 'color:#'.$row['color'].';' : '');
			$html .= '<div class="r'.(!empty($row['nowrap']) ? ' nw' : '').'" style="'.$h.$font.'">';
			foreach(dt_htkh_row_cells($row) as $cell)
			{
				$html .= '<div style="width:'.$cell[2].'%;text-align:'.$cell[1].'">';
				foreach($cell[0] as $part)
					$html .= '<span style="font-weight:'.($part[1] ? 'bold' : 'normal').'">'.$e($part[0]).'</span>';
				$html .= '</div>';
			}
			$html .= '</div>';
		}
		$html .= '</div>';
	}
	return $html.'</body></html>';
}

/* ---------------- Excel (.xlsx) ---------------- */
function dt_htkh_documents_xlsx($documents)
{
	require_once LIBRARIES.'PHPExcel.php';
	$assets = dt_htkh_assets();
	$book = new PHPExcel();
	$sheet = $book->getActiveSheet();
	$sheet->setTitle('Giay xac nhan HTKH');
	$book->getDefaultStyle()->getFont()->setName('Courier New')->setSize(10);
	/* Lưới 20 cột nội dung × 32px giữa 2 cột lề 75px; Courier New 10 có độ rộng chữ số 8px. */
	$marginPx = 75; $unitPx = 32; $units = 20;
	for($i = 0; $i <= $units + 1; $i++)
		$sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($i))->setWidth((($i === 0 || $i === $units + 1) ? $marginPx : $unitPx) / 8);
	$lastCol = PHPExcel_Cell::stringFromColumnIndex($units + 1);
	$setFont = function($font, $row, $bold) {
		$font->setName('Times New Roman')->setSize($row['size'] ?? 14)->setBold($bold)->setItalic(!empty($row['italic']));
		if(!empty($row['underline'])) $font->setUnderline(PHPExcel_Style_Font::UNDERLINE_SINGLE);
		if(!empty($row['color'])) $font->getColor()->setRGB($row['color']);
	};
	$sheet->setShowGridlines(false);
	$setup = $sheet->getPageSetup();
	$setup->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
	$setup->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
	$setup->setFitToWidth(1)->setFitToHeight(0);
	$sheet->getPageMargins()->setTop(0)->setBottom(0)->setLeft(0)->setRight(0)->setHeader(0)->setFooter(0);

	$logoSize = is_file($assets['logo_file']) ? @getimagesize($assets['logo_file']) : false;
	$number = 1;
	foreach($documents as $index => $doc)
	{
		if($index > 0) $sheet->setBreak('A'.($number - 1), PHPExcel_Worksheet::BREAK_ROW);
		$sheet->getRowDimension($number++)->setRowHeight(15 * 72 / 25.4);
		foreach(dt_htkh_document_rows($doc) as $row)
		{
			$sheet->getRowDimension($number)->setRowHeight($row['height'] ?? 24);
			if(!empty($row['image']))
			{
				if($logoSize)
				{
					$logoH = 98;
					$logoW = (int)round($logoH * $logoSize[0] / max(1, $logoSize[1]));
					$leftPx = $units * $unitPx / 2 - $logoW / 2;
					$colIdx = 1 + (int)floor($leftPx / $unitPx);
					$logo = new PHPExcel_Worksheet_Drawing();
					$logo->setName('Logo');
					$logo->setPath($assets['logo_file']);
					$logo->setResizeProportional(true);
					$logo->setHeight($logoH);
					$logo->setCoordinates(PHPExcel_Cell::stringFromColumnIndex($colIdx).$number);
					$logo->setOffsetX((int)round($leftPx - ($colIdx - 1) * $unitPx))->setOffsetY(11);
					$logo->setWorksheet($sheet);
				}
				$number++;
				continue;
			}
			$col = 1;
			foreach(dt_htkh_row_cells($row) as $cell)
			{
				$span = max(1, (int)round($cell[2] * $units / 100));
				$from = PHPExcel_Cell::stringFromColumnIndex($col);
				$range = $from.$number.':'.PHPExcel_Cell::stringFromColumnIndex($col + $span - 1).$number;
				$col += $span;
				if($span > 1) $sheet->mergeCells($range);
				$style = $sheet->getStyle($range);
				$style->getAlignment()->setWrapText(empty($row['nowrap']))->setVertical('center')->setHorizontal($cell[1]);
				if(count($cell[0]) > 1)
				{
					$setFont($style->getFont(), $row, !empty($row['bold']));
					$rich = new PHPExcel_RichText();
					foreach($cell[0] as $part) $setFont($rich->createTextRun($part[0])->getFont(), $row, $part[1]);
					$sheet->getCell($from.$number)->setValue($rich);
				}
				else
				{
					$setFont($style->getFont(), $row, $cell[0][0][1]);
					$sheet->setCellValueExplicit($from.$number, (string)$cell[0][0][0], PHPExcel_Cell_DataType::TYPE_STRING);
				}
			}
			$number++;
		}
	}
	$setup->setPrintArea('A1:'.$lastCol.($number - 1));
	return $book;
}

/* ---------------- Word (.docx) — ghi OOXML trực tiếp để giữ Unicode ---------------- */
function dt_htkh_emu($mm) { return (int)round($mm * 36000); }

function dt_htkh_documents_docx_body($documents, $logoRid)
{
	$e = function($text) { return htmlspecialchars((string)$text, ENT_XML1 | ENT_QUOTES, 'UTF-8'); };
	$run = function($text, $row, $bold) use ($e) {
		$size = ($row['size'] ?? 14) * 2;
		$rpr = '<w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>'
			.($bold ? '<w:b/>' : '').(!empty($row['italic']) ? '<w:i/>' : '')
			.(!empty($row['color']) ? '<w:color w:val="'.$row['color'].'"/>' : '')
			.'<w:sz w:val="'.$size.'"/><w:szCs w:val="'.$size.'"/>'
			.(!empty($row['underline']) ? '<w:u w:val="single"/>' : '').'</w:rPr>';
		$xml = '<w:r>'.$rpr;
		foreach(explode("\n", (string)$text) as $i => $part)
			$xml .= ($i > 0 ? '<w:br/>' : '').'<w:t xml:space="preserve">'.$e($part).'</w:t>';
		return $xml.'</w:r>';
	};
	/* Lưới 20 cột (5%/cột) trên vùng nội dung 9638 twip. */
	$units = 20; $total = 9638;
	$unitW = (int)floor($total / $units);
	$grid = '';
	for($i = 0; $i < $units; $i++) $grid .= '<w:gridCol w:w="'.($i === $units - 1 ? $total - $unitW * ($units - 1) : $unitW).'"/>';
	$tc = function($span, $nowrap, $inner) use ($unitW, $units, $total) {
		$w = $span === $units ? $total : $span * $unitW;
		return '<w:tc><w:tcPr><w:tcW w:w="'.$w.'" w:type="dxa"/>'.($span > 1 ? '<w:gridSpan w:val="'.$span.'"/>' : '')
			.($nowrap ? '<w:noWrap/>' : '').'<w:vAlign w:val="center"/></w:tcPr>'.$inner.'</w:tc>';
	};
	$body = '';
	foreach($documents as $index => $doc)
	{
		if($index > 0) $body .= '<w:p><w:pPr><w:spacing w:before="0" w:after="0"/></w:pPr><w:r><w:br w:type="page"/></w:r></w:p>';
		$body .= '<w:tbl><w:tblPr><w:tblW w:w="'.$total.'" w:type="dxa"/><w:tblLayout w:type="fixed"/><w:tblCellMar><w:left w:w="0" w:type="dxa"/><w:right w:w="0" w:type="dxa"/></w:tblCellMar><w:tblBorders/></w:tblPr><w:tblGrid>'.$grid.'</w:tblGrid>';
		foreach(dt_htkh_document_rows($doc) as $row)
		{
			$body .= '<w:tr><w:trPr><w:cantSplit/><w:trHeight w:val="'.(($row['height'] ?? 24) * 20).'" w:hRule="atLeast"/></w:trPr>';
			if(!empty($row['image']))
			{
				$content = $logoRid ? dt_htkh_docx_inline_image($logoRid, 26, 26) : '';
				$body .= $tc($units, false, '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="0"/></w:pPr>'.$content.'</w:p>');
			}
			else
			{
				foreach(dt_htkh_row_cells($row) as $cell)
				{
					$runs = '';
					foreach($cell[0] as $part) $runs .= $run($part[0], $row, $part[1]);
					$span = max(1, (int)round($cell[2] * $units / 100));
					$body .= $tc($span, !empty($row['nowrap']), '<w:p><w:pPr><w:jc w:val="'.$cell[1].'"/><w:spacing w:before="0" w:after="0"/></w:pPr>'.$runs.'</w:p>');
				}
			}
			$body .= '</w:tr>';
		}
		$body .= '</w:tbl>';
	}
	$sectPr = '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="850" w:right="1134" w:bottom="850" w:left="1134" w:header="454" w:footer="454" w:gutter="0"/></w:sectPr>';
	$ns = 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"';
	return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document '.$ns.'><w:body>'.$body.$sectPr.'</w:body></w:document>';
}

/* Ảnh chèn thẳng dòng (inline) — dùng cho logo căn giữa ở mỗi giấy. */
function dt_htkh_docx_inline_image($rid, $wMm, $hMm)
{
	$cx = dt_htkh_emu($wMm); $cy = dt_htkh_emu($hMm);
	return '<w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0"><wp:extent cx="'.$cx.'" cy="'.$cy.'"/><wp:effectExtent l="0" t="0" r="0" b="0"/>'
		.'<wp:docPr id="103" name="Logo"/><wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr>'
		.'<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic>'
		.'<pic:nvPicPr><pic:cNvPr id="103" name="Logo"/><pic:cNvPicPr/></pic:nvPicPr>'
		.'<pic:blipFill><a:blip r:embed="'.$rid.'"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
		.'<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$cx.'" cy="'.$cy.'"/></a:xfrm>'
		.'<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r>';
}

function dt_htkh_documents_docx_save($documents, $path)
{
	$assets = dt_htkh_assets();
	$hasLogo = is_file($assets['logo_file']);
	$logoRid = $hasLogo ? 'rIdLogo' : '';

	$document = dt_htkh_documents_docx_body($documents, $logoRid);

	$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
		.'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
		.'<Default Extension="xml" ContentType="application/xml"/>'
		.'<Default Extension="png" ContentType="image/png"/>'
		.'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
		.'</Types>';
	$rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>';
	$docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
		.($hasLogo ? '<Relationship Id="'.$logoRid.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/htkh-logo.png"/>' : '').'</Relationships>';

	$zip = new ZipArchive();
	if($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Không thể tạo file Word HTKH');
	try
	{
		$parts = array(
			'[Content_Types].xml' => $contentTypes,
			'_rels/.rels' => $rootRels,
			'word/document.xml' => $document,
			'word/_rels/document.xml.rels' => $docRels,
		);
		foreach($parts as $name => $content)
			if(!$zip->addFromString($name, $content)) throw new RuntimeException('Không thể ghi nội dung file Word HTKH');
		if($hasLogo && !$zip->addFile($assets['logo_file'], 'word/media/htkh-logo.png')) throw new RuntimeException('Không thể nhúng logo vào Word HTKH');
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
