<?php
include "ajax_config.php";
require_once LIBRARIES.'qr_helper.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id <= 0) {
	http_response_code(400);
	exit;
}

$item = $d->rawQueryOne("select id, tenvi, cccd, gia, type, hienthi from #_product where id = ? limit 0,1", array($id));

if(empty($item) || $item['type'] != 'qr' || (int)$item['hienthi'] != 1) {
	http_response_code(404);
	exit;
}

$nameNoAccent = strtoupper(removeVietnameseDiacritics($item['tenvi']));
$nameNoSpace = str_replace(' ', '', $nameNoAccent);
$dateSuffix = date('Ymd');
$transferMsg = $nameNoSpace . ' ' . $item['cccd'] . ' ' . $dateSuffix;
$amount = isset($item['gia']) ? (int)$item['gia'] : 0;

$qrContent = buildVietQRPayload(VIETQR_ACCOUNT_NO, VIETQR_BANK_BIN, $amount, $transferMsg);
$tmpFile = tempnam(sys_get_temp_dir(), 'qr_');

if($tmpFile === false) {
	http_response_code(500);
	exit;
}

$logoPath = ROOT.'/../assets/images/logo-vietcombank.png';
generateQRWithLogo($qrContent, $tmpFile, $logoPath);

if(!file_exists($tmpFile)) {
	http_response_code(500);
	exit;
}

header('Content-Type: image/png');
header('Cache-Control: private, max-age=300');
readfile($tmpFile);
unlink($tmpFile);
exit;
