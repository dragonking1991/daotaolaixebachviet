<?php
include "ajax_config.php";
require_once LIBRARIES.'qr_helper.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id <= 0) {
	http_response_code(400);
	exit;
}

$item = $d->rawQueryOne("select id, tenvi, cccd, gia, type, hienthi, photo, options2 from #_product where id = ? limit 0,1", array($id));

if(empty($item) || $item['type'] != 'qr' || (int)$item['hienthi'] != 1) {
	http_response_code(404);
	exit;
}

// Serve stored QR image directly if available (saved during Excel import)
$uploadPath = ROOT . '/../upload/product/';
if(!empty($item['photo']) && file_exists($uploadPath . $item['photo'])) {
	header('Content-Type: image/png');
	header('Cache-Control: private, max-age=300');
	readfile($uploadPath . $item['photo']);
	exit;
}

$nameNoAccent = strtoupper(removeVietnameseDiacritics($item['tenvi']));
$nameNoSpace = str_replace(' ', '', $nameNoAccent);
$dateSuffix = date('Ymd');
$transferMsg = $nameNoSpace . ' ' . $item['cccd'] . ' ' . $dateSuffix;
$amount = isset($item['gia']) ? (int)$item['gia'] : 0;
$qrAccountNo = getVietQRAccountNoFromOptions(isset($item['options2']) ? $item['options2'] : '');
$configError = getVietQRConfigError($qrAccountNo, VIETQR_BANK_BIN);

if(isset($_GET['format']) && $_GET['format'] === 'json' && $configError !== '') {
	http_response_code(500);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('error' => $configError), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

if($configError !== '') {
	http_response_code(500);
	header('Content-Type: text/plain; charset=utf-8');
	echo $configError;
	exit;
}

$qrInfo = buildVietQRPayloadWithInfo($qrAccountNo, VIETQR_BANK_BIN, $amount, $transferMsg);
$qrContent = $qrInfo['payload'];

// ?format=json → return decoded QR details (account_no, bank_bin, amount, message)
if(isset($_GET['format']) && $_GET['format'] === 'json') {
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array(
		'account_no' => $qrInfo['account_no'],
		'bank_bin'   => $qrInfo['bank_bin'],
		'amount'     => $qrInfo['amount'],
		'message'    => $qrInfo['message'],
		'config_error' => $qrInfo['config_error'],
	));
	exit;
}

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
